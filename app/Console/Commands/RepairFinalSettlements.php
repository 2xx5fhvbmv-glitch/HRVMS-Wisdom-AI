<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\FinalSettlement;
use App\Models\FinalSettlementDeductions;
use App\Models\ResortSiteSettings;
use App\Services\FinalSettlementService;
use Illuminate\Console\Command;

/**
 * Backfill total_earnings / total_deductions / net_pay on existing
 * final_settlements rows using the same currency-unified logic the
 * fixed store endpoint applies to new submissions.
 *
 * Why this exists: rows submitted before the store-endpoint currency
 * fix have mixed-unit math baked in — net_pay was computed as
 * (earned_salary − pension − tax − loan − custom_deductions_in_MVR),
 * which produced nonsense like "-531.76 MVR" against a review that
 * read "-60.40 USD". The fixed store now writes everything in the
 * employee's basic_salary_currency; this command brings legacy rows
 * onto the same convention without touching their `finalized` status.
 *
 * Usage:
 *   php artisan settlements:repair                 # dry-run all rows
 *   php artisan settlements:repair --apply         # write changes
 *   php artisan settlements:repair --id=1 --apply  # one row
 */
class RepairFinalSettlements extends Command
{
    protected $signature = 'settlements:repair
                            {--id= : Repair only this final_settlement id}
                            {--apply : Persist changes (default is dry-run)}';

    protected $description = 'Recompute total_earnings / total_deductions / net_pay on existing F&F rows using the new currency-unified formula.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $rowId = $this->option('id');

        $query = FinalSettlement::query()->with(['employee.resortAdmin']);
        if ($rowId) {
            $query->where('id', (int) $rowId);
        }
        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('No final_settlements rows match.');
            return self::SUCCESS;
        }

        $this->info(($apply ? 'APPLYING' : 'DRY-RUN') . ' on ' . $rows->count() . ' row(s).');
        $headers = ['ID', 'Emp', 'Ccy', 'Old TE', 'New TE', 'Old TD', 'New TD', 'Old Net', 'New Net'];
        $tableRows = [];

        $svc = new FinalSettlementService();

        foreach ($rows as $fs) {
            $employee = $fs->employee;
            if (!$employee) {
                $this->warn("FS #{$fs->id}: orphan (employee missing) — skipping");
                continue;
            }

            $payCurrency = strtoupper((string) ($employee->basic_salary_currency ?? 'MVR'));
            $settings = ResortSiteSettings::where('resort_id', $employee->resort_id)->first();
            $usdToMvr = (float) ($settings->DollertoMVR ?? 15.42);

            $toPay = function ($amount, $unit) use ($payCurrency, $usdToMvr) {
                $unit = strtoupper($unit ?? $payCurrency);
                if ($unit === $payCurrency || $usdToMvr <= 0) return (float) $amount;
                if ($unit === 'USD' && $payCurrency === 'MVR') return (float) $amount * $usdToMvr;
                if ($unit === 'MVR' && $payCurrency === 'USD') return (float) $amount / $usdToMvr;
                return (float) $amount;
            };

            // Notice Period Charge from the service (MVR → payCurrency).
            // FinalSettlementService throws on edge cases (e.g. employees
            // without resignations); fall back to 0 so a single bad row
            // doesn't abort the whole batch.
            $noticeCharge = 0.0;
            try {
                $calc = $svc->calculateFinalMonthData($employee, $employee->resort_id);
                $noticeCharge = round($toPay($calc['notice_period_charge_mvr'] ?? 0, 'MVR'), 2);
            } catch (\Throwable $e) {
                $this->warn("FS #{$fs->id}: notice charge calc failed ({$e->getMessage()}); using 0");
            }

            $isMaldivian = strtolower((string) ($employee->nationality ?? '')) === 'maldivian';
            $pensionApplied = $isMaldivian ? (float) $fs->pension : 0.0;

            // Custom deductions — the legacy store wrote `amount` already
            // converted to MVR (regardless of the unit HR submitted). Treat
            // it as MVR here, convert to payCurrency.
            $customDedTotal = 0.0;
            $customRows = FinalSettlementDeductions::where('final_settlement_id', $fs->id)->get();
            foreach ($customRows as $d) {
                // Defensive: if amount_unit was recorded as the same as
                // payCurrency (post-fix rows), don't double-convert.
                $unitOnRow = strtoupper((string) ($d->amount_unit ?? 'MVR'));
                $assumedUnit = $unitOnRow === $payCurrency ? $payCurrency : 'MVR';
                $customDedTotal += $toPay((float) $d->amount, $assumedUnit);
            }
            $customDedTotal = round($customDedTotal, 2);

            // Earnings — stored values are already in payCurrency for
            // user-edited fields (service_charge, leave_encashment after
            // recalc, earned_salary for the basic-for-N-days slice).
            $earned       = (float) ($fs->total_earnings ?? 0);   // pre-fix: this was just earned_salary
            $service      = (float) ($fs->service_charge ?? 0);
            $leaveEnc     = (float) ($fs->leave_encashment ?? 0);
            // Total earnings = the full gross. If this row was stored by
            // the new endpoint, $earned will already equal the gross and
            // we'd double-add service + leaveEnc. Heuristic: if $earned
            // > service + leaveEnc (so already includes them), trust it.
            $candidateGross = $service + $leaveEnc;
            $newTotalEarnings = round(($earned >= $candidateGross) ? $earned : ($earned + $candidateGross), 2);

            $newTotalDeductions = round(
                ((float) $fs->tax)
                + $pensionApplied
                + ((float) $fs->loan_payment)
                + $noticeCharge
                + $customDedTotal,
                2
            );
            $newNetPay = round($newTotalEarnings - $newTotalDeductions, 2);

            $tableRows[] = [
                $fs->id,
                $employee->Emp_id,
                $payCurrency,
                number_format((float) $fs->total_earnings, 2),
                number_format($newTotalEarnings, 2),
                number_format((float) $fs->total_deductions, 2),
                number_format($newTotalDeductions, 2),
                number_format((float) $fs->net_pay, 2),
                number_format($newNetPay, 2),
            ];

            if ($apply) {
                $fs->update([
                    'total_earnings'   => $newTotalEarnings,
                    'total_deductions' => $newTotalDeductions,
                    'net_pay'          => $newNetPay,
                    'pension'          => $pensionApplied,
                ]);
            }
        }

        $this->table($headers, $tableRows);

        if (!$apply) {
            $this->comment('Dry-run — pass --apply to write these changes.');
        } else {
            $this->info('Applied.');
        }
        return self::SUCCESS;
    }
}
