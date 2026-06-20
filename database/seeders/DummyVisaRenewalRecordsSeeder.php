<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\Employee;
use App\Models\WorkPermit;
use App\Models\EmployeeInsurance;
use App\Models\QuotaSlotRenewal;
use App\Models\WorkPermitMedicalRenewal;

/**
 * LOCAL TEST DATA ONLY — gives a handful of expat employees work-permit,
 * insurance, quota-slot and medical renewal records dated in the CURRENT month
 * so the Visa "Create Payment Request" page (/resort/visa/payment-request)
 * actually lists employees once a Payment Type (or "All") is selected.
 *
 * NOT registered in DatabaseSeeder — never runs on deploy. Run explicitly:
 *
 *   php artisan db:seed --class=DummyVisaRenewalRecordsSeeder
 *
 * Idempotent: every run first deletes the rows it previously created (each is
 * tagged with a 'DUMMY_SEED' marker in a reference/receipt/policy field).
 * To remove the dummy data, re-deleting those marked rows is enough.
 */
class DummyVisaRenewalRecordsSeeder extends Seeder
{
    /** Resort to seed (26 = Demo Resort, used for testing). */
    const RESORT_ID = 26;

    const MARKER = 'DUMMY_SEED';

    public function run()
    {
        $resortId = self::RESORT_ID;

        // --- Idempotent cleanup of previously seeded dummy rows -----------
        WorkPermit::where('resort_id', $resortId)->where('ReceiptNumber', self::MARKER)->delete();
        EmployeeInsurance::where('resort_id', $resortId)->where('insurance_policy_number', self::MARKER)->delete();
        QuotaSlotRenewal::where('resort_id', $resortId)->where('ReceiptNumber', self::MARKER)->delete();
        WorkPermitMedicalRenewal::where('resort_id', $resortId)->where('Reference_Number', self::MARKER)->delete();

        // --- Target a few active expat employees --------------------------
        $employees = Employee::where('resort_id', $resortId)
            ->where('status', 'Active')
            ->where('nationality', '!=', 'Maldivian')
            ->take(6)
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn("No expat employees found for resort {$resortId}; nothing seeded.");
            return;
        }

        // All dates land inside the current month so they fall within the
        // page's default (current-month) window and a typical selected range.
        $monthLabel = Carbon::now()->format('F Y');
        $wpDue   = Carbon::now()->addDays(2)->toDateString();
        $insEnd  = Carbon::now()->addDays(5)->toDateString();
        $medEnd  = Carbon::now()->addDays(8)->toDateString();
        $slotDue = Carbon::now()->addDays(3)->toDateString();

        foreach ($employees as $emp) {
            WorkPermit::create([
                'resort_id'         => $resortId,
                'employee_id'       => $emp->id,
                'Month'             => $monthLabel,
                'Currency'          => 'MVR',
                'Amt'               => 10000,
                'Due_Date'          => $wpDue,
                'Work_Permit_Number'=> 'WP-DUMMY-' . $emp->id,
                'Status'            => 'Unpaid',
                'ReceiptNumber'     => self::MARKER,
            ]);

            EmployeeInsurance::create([
                'resort_id'               => $resortId,
                'employee_id'             => $emp->id,
                'Currency'                => 'MVR',
                'Premium'                 => 25000,
                'insurance_company'       => 'Dummy Insurance Co.',
                'insurance_policy_number' => self::MARKER,
                'insurance_start_date'    => Carbon::now()->subMonths(11)->toDateString(),
                'insurance_end_date'      => $insEnd,
                'Status'                  => 'Unpaid',
            ]);

            QuotaSlotRenewal::create([
                'resort_id'     => $resortId,
                'employee_id'   => $emp->id,
                'Month'         => $monthLabel,
                'Currency'      => 'MVR',
                'Amt'           => 25000,
                'Due_Date'      => $slotDue,
                'Status'        => 'Unpaid',
                'ReceiptNumber' => self::MARKER,
            ]);

            WorkPermitMedicalRenewal::create([
                'resort_id'           => $resortId,
                'employee_id'         => $emp->id,
                'Reference_Number'    => self::MARKER,
                'Amt'                 => 5000,
                'Currency'            => 'MVR',
                'Medical_Center_name' => 'Dummy Medical Center',
                'start_date'          => Carbon::now()->subDays(20)->toDateString(),
                'end_date'            => $medEnd,
                'Status'              => 'Unpaid',
            ]);
        }

        $this->command->info(
            "Seeded renewal records (work permit / insurance / quota / medical) for {$employees->count()} employees in resort {$resortId}. "
            . "Open /resort/visa/payment-request, pick a date range covering {$monthLabel} and tick 'All' (or a type)."
        );
    }
}
