<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY report of advance/loan over-recoveries caused by the double-interest
 * bug (fixed 2026-10-16): payroll recovered amount + interest_amount per
 * installment, but `amount` already includes the interest. Nothing is refunded
 * or written here — HR reviews this list first (founder, 2026-10-15).
 *
 * Per locked/approved run and employee: what should have been recovered
 * (Σ installment), what the run deducted, the interest counted twice, and the
 * refund due in the run's currency. Finalized settlements that included a loan
 * payment are listed separately for manual review: which installments they
 * covered can't be reconstructed.
 *
 *   php artisan payroll:advance-overrecovery-report [--resort=ID] [--csv=path]
 */
class AdvanceOverRecoveryReport extends Command
{
    protected $signature = 'payroll:advance-overrecovery-report {--resort= : Only this resort id} {--csv= : Also write the rows to this CSV file}';
    protected $description = 'List advance/loan over-recoveries from the double-interest bug (read-only, no refunds).';

    public function handle(): int
    {
        $runs = DB::table('payroll')->whereIn('status', ['locked', 'approved'])
            ->when($this->option('resort'), fn ($q, $r) => $q->where('resort_id', $r))
            ->orderBy('resort_id')->orderBy('start_date')->get(['id', 'resort_id', 'start_date', 'end_date']);

        $rows = [];
        foreach ($runs as $run) {
            $settings = DB::table('resort_site_settings')->where('resort_id', $run->resort_id)->first(['currency', 'DollertoMVR']);
            $runCurrency = ($settings->currency ?? 'Dollar') === 'MVR' ? 'MVR' : 'USD';
            $rate = (float) (($settings->DollertoMVR ?? 0) ?: 15.42);
            $toRun = fn (float $v, ?string $cur) => match (true) {
                $runCurrency === 'MVR' && ($cur ?: 'USD') === 'USD' => $v * $rate,
                $runCurrency === 'USD' && $cur === 'MVR' => $v / $rate,
                default => $v,
            };

            foreach (DB::table('payroll_deductions')->where('payroll_id', $run->id)->where('advance_loan', '>', 0)->get(['employee_id', 'Emp_id', 'advance_loan']) as $d) {
                $installments = DB::table('payroll_recovery_schedule as rs')->join('payroll_advance as pa', 'pa.id', '=', 'rs.payroll_advance_id')
                    ->where('rs.employee_id', $d->employee_id)->whereBetween('rs.repayment_date', [$run->start_date, $run->end_date])
                    ->get(['rs.amount', 'rs.interest_amount', 'pa.currency']);
                $should = $installments->sum(fn ($i) => $toRun((float) $i->amount, $i->currency));
                $doubled = $installments->sum(fn ($i) => $toRun((float) $i->interest_amount, $i->currency));
                $deducted = (float) $d->advance_loan;
                $refund = round(max(0, min($doubled, $deducted - $should)), 2);
                $rows[] = [
                    'resort_id' => $run->resort_id, 'payroll_id' => $run->id, 'period' => "{$run->start_date} – {$run->end_date}",
                    'employee' => $d->Emp_id, 'currency' => $runCurrency, 'installments' => $installments->count(),
                    'should_recover' => round($should, 2), 'deducted' => round($deducted, 2),
                    'interest_counted_twice' => round($doubled, 2), 'refund_due' => $refund,
                ];
            }
        }

        $due = array_values(array_filter($rows, fn ($r) => $r['refund_due'] > 0));
        $this->info(count($rows) . ' run deduction(s) checked, ' . count($due) . ' over-recovered.');
        if ($rows) {
            $this->table(array_keys($rows[0]), $rows);
        }

        $settlements = DB::table('final_settlements as fs')->join('employees as e', 'e.id', '=', 'fs.employee_id')
            ->where('fs.status', 'finalized')->where('fs.loan_payment', '>', 0)
            ->when($this->option('resort'), fn ($q, $r) => $q->where('e.resort_id', $r))
            ->get(['e.resort_id', 'e.Emp_id', 'fs.reference_no', 'fs.loan_payment', 'fs.last_working_date']);
        if ($settlements->isNotEmpty()) {
            $this->warn($settlements->count() . ' finalized settlement(s) included a loan payment — review by hand (same double-interest formula):');
            $this->table(['resort_id', 'employee', 'reference', 'loan_payment', 'last_working_date'],
                $settlements->map(fn ($s) => [$s->resort_id, $s->Emp_id, $s->reference_no, $s->loan_payment, $s->last_working_date])->all());
        }

        if ($path = $this->option('csv')) {
            $fh = fopen($path, 'w');
            fputcsv($fh, $rows ? array_keys($rows[0]) : ['no rows']);
            foreach ($rows as $r) {
                fputcsv($fh, $r);
            }
            fclose($fh);
            $this->info("Written to {$path}");
        }
        $this->comment('Read-only: nothing was refunded or changed.');
        return self::SUCCESS;
    }
}
