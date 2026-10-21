<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: what every expat costs to keep legal, in the shapes the Visa module writes them — work permit fee
 * and quota slot fee as 12 monthly installments (paid for the months gone, unpaid ahead), and the yearly visa,
 * insurance and work-permit medical renewals on the employee's anniversary. These feed the Visa renewal pages
 * and the five renewal cards on People → Initial Liability Estimation. Amounts in MVR, as the module stores them.
 */
class DemoVisaSeeder
{
    use SeedsDemoRows;

    const WORK_PERMIT_FEE = 350;  // per month
    const QUOTA_FEE = 666;        // per month
    const VISA_FEE = 1000;        // per year
    const INSURANCE = 771;        // per year (USD 50)
    const MEDICAL = 771;          // per year

    private array $counts = ['expats with permits' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $today = Carbon::today();
        $hr = $this->login('hr');
        $expats = $this->people()->filter(fn ($p) => $p->nationality !== 'Maldivian' && !in_array($p->employment_type, ['Casual', 'Internship'], true));
        $installments = [];
        foreach ($expats as $p) {
            // The permit year runs from the joining anniversary that started the current cycle.
            $joined = Carbon::parse($p->joining_date);
            $cycle = $joined->copy()->year($today->year);
            if ($cycle->gt($today)) {
                $cycle->subYear();
            }
            $cycle = $cycle->max($joined);
            $wp = 'WP' . str_pad((string) mt_rand(100000, 999999), 8, '0', STR_PAD_LEFT);
            for ($m = 0; $m < 12; $m++) {
                $due = $cycle->copy()->addMonthsNoOverflow($m);
                $paid = $due->lte($today);
                foreach (['work_permits' => self::WORK_PERMIT_FEE, 'quota_slot_renewals' => self::QUOTA_FEE] as $table => $amount) {
                    $installments[$table][] = ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'Month' => $due->format('m'), 'Currency' => 'MVR',
                        'Amt' => $amount, 'Due_Date' => $due->format('Y-m-d'), 'Payment_Date' => $paid ? $due->copy()->addDays(mt_rand(0, 4))->format('Y-m-d') : null,
                        'Work_Permit_Number' => $table === 'work_permits' ? $wp : null, 'Status' => $paid ? 'Paid' : 'Unpaid',
                        'paid_by' => $paid ? $hr->admin : null, 'ReceiptNumber' => $paid ? 'RC-' . mt_rand(100000, 999999) : null, 'PaymentType' => 'Installment',
                        'created_at' => $cycle, 'updated_at' => $paid ? $due : $cycle];
                }
            }
            $end = $cycle->copy()->addYear()->subDay();
            $paidAt = $cycle->copy()->addDays(3);
            $stamp = ['resort_id' => $this->resortId, 'employee_id' => $p->id, 'created_at' => $cycle, 'updated_at' => $paidAt];
            $this->row('visa_renewals', $stamp + ['WP_No' => $wp, 'Visa_Number' => 'V' . mt_rand(1000000, 9999999), 'start_date' => $cycle->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'), 'Amt' => self::VISA_FEE, 'Status' => 'Paid', 'paid_date' => $paidAt->format('Y-m-d')]);
            $this->row('employee_insurances', $stamp + ['insurance_company' => 'Allied Insurance Maldives', 'insurance_policy_number' => 'AIM-' . mt_rand(100000, 999999),
                'insurance_coverage' => 'Medical – MVR 100,000', 'insurance_start_date' => $cycle->format('Y-m-d'), 'insurance_end_date' => $end->format('Y-m-d'),
                'Currency' => 'MVR', 'Premium' => self::INSURANCE, 'Status' => $this->chance(0.85) ? 'Paid' : 'Pending', 'paid_date' => $paidAt->format('Y-m-d')]);
            $this->row('work_permit_medical_renewals', $stamp + ['Reference_Number' => 'MED-' . mt_rand(10000, 99999), 'Cost' => (string) self::MEDICAL,
                'Currency' => 'MVR', 'Medical_Center_name' => 'ADK Hospital', 'start_date' => $cycle->format('Y-m-d'), 'end_date' => $end->format('Y-m-d'),
                'Amt' => self::MEDICAL, 'Status' => 'Paid', 'paid_date' => $paidAt->format('Y-m-d')]);
            $this->counts['expats with permits']++;
        }
        foreach ($installments as $table => $rows) {
            $this->rows($table, $rows);
        }
        return $this->counts;
    }
}
