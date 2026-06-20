<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestChild;
use App\Models\Employee;

/**
 * LOCAL TEST DATA ONLY — populates the Visa "Payment Request List"
 * (/resort/visa/payment-request/index) with a few dummy Pending payment
 * requests so the page can be demoed on local where no real requests exist.
 *
 * This seeder is intentionally NOT registered in DatabaseSeeder, so it never
 * runs as part of a deploy/migrate. Run it explicitly:
 *
 *   php artisan db:seed --class=DummyVisaPaymentRequestSeeder
 *
 * It is idempotent — every run first removes the rows it previously created
 * (marked with Reason = 'DUMMY_SEED'). To remove the dummy data entirely:
 *
 *   PaymentRequest::where('Reason','DUMMY_SEED') -> (delete children) -> delete()
 */
class DummyVisaPaymentRequestSeeder extends Seeder
{
    /** Resort to seed (26 = Demo Resort, the one used for testing). */
    const RESORT_ID = 26;

    const MARKER = 'DUMMY_SEED';

    public function run()
    {
        $resortId = self::RESORT_ID;

        // --- Clean up any previous dummy rows (idempotent) -----------------
        $oldIds = PaymentRequest::where('resort_id', $resortId)
            ->where('Reason', self::MARKER)
            ->pluck('id');
        if ($oldIds->isNotEmpty()) {
            PaymentRequestChild::whereIn('Requested_Id', $oldIds)->delete();
            PaymentRequest::whereIn('id', $oldIds)->delete();
        }

        // --- Pick a few expat employees to attach as children -------------
        $employeeIds = Employee::where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where('nationality', '!=', 'Maldivian')
            ->take(6)
            ->pluck('id')
            ->all();

        if (empty($employeeIds)) {
            $this->command->warn("No expat employees found for resort {$resortId}; created parent requests without children.");
        }

        // --- Create 3 dummy payment requests ------------------------------
        $samples = [
            ['seq' => 901, 'days_ago' => 1, 'children' => 2],
            ['seq' => 902, 'days_ago' => 5, 'children' => 3],
            ['seq' => 903, 'days_ago' => 12, 'children' => 1],
        ];

        $created = 0;
        foreach ($samples as $i => $s) {
            $request = PaymentRequest::create([
                'resort_id'    => $resortId,
                'Requestd_id'  => 'PR-DR-' . $s['seq'],
                'Request_date' => Carbon::now()->subDays($s['days_ago']),
                'Reason'       => self::MARKER,
                'Status'       => 'Pending',
            ]);

            for ($c = 0; $c < $s['children']; $c++) {
                $empId = $employeeIds[($i + $c) % max(count($employeeIds), 1)] ?? null;
                if (!$empId) {
                    break;
                }

                $wpDate    = Carbon::now()->addDays(20 + $c)->toDateString();
                $quotaDate = Carbon::now()->addDays(40 + $c)->toDateString();
                $insDate   = Carbon::now()->addDays(60 + $c)->toDateString();
                $medDate   = Carbon::now()->addDays(30 + $c)->toDateString();

                $wpAmt    = 10000;
                $quotaAmt = 25000;
                $insAmt   = 25000;
                $medAmt   = 5000;
                $visaAmt  = 0;

                $overall = collect([$wpAmt, $quotaDate, $insDate, $medAmt, $visaAmt])
                    ->filter(fn ($v) => $v && $v !== '0')
                    ->count();

                PaymentRequestChild::create([
                    'Requested_Id'        => $request->id,
                    'Employee_id'         => $empId,
                    'WorkPermitDate'      => $wpDate,
                    'WorkPermitAmt'       => $wpAmt,
                    'QuotaslotDate'       => $quotaDate,
                    'QuotaslotAmt'        => $quotaAmt,
                    'InsuranceDate'       => $insDate,
                    'InsurancePrimume'    => $insAmt,
                    'MedicalReportDate'   => $medDate,
                    'MedicalReportFees'   => $medAmt,
                    'VisaDate'            => null,
                    'VisaAmt'             => $visaAmt,
                    'LastVisaDate'        => null,
                    'LastMedicalReportDate' => null,
                    'LastInsuranceDate'   => null,
                    'LastQuotaslotDate'   => null,
                    'LastWorkPermitDate'  => null,
                    'WorkPermitShow'      => 'yes',
                    'QuotaslotShow'       => 'yes',
                    'InsuranceShow'       => 'yes',
                    'MedicalReportShow'   => 'yes',
                    'VisaShow'            => 'no',
                    'OverallSteps'        => $overall,
                    'ChildStatus'         => 'Pending',
                    'OngoingSteps'        => 0,
                ]);
            }

            $created++;
        }

        $this->command->info("Seeded {$created} dummy Pending payment request(s) for resort {$resortId}. View at /resort/visa/payment-request/index");
    }
}
