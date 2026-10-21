<?php

namespace Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: what every expat costs to keep legal, in the shapes the Visa module writes them — work permit fee
 * and quota slot fee as 12 monthly installments (paid for the months gone, unpaid ahead), and the yearly visa,
 * insurance and work-permit medical renewals on the employee's anniversary — then the payment requests that paid
 * them, the Xpat wallets and their transfers, and each expat's expenses since joining. These feed the Visa pages
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
                        'Amt' => $amount, 'Due_Date' => $due->format('Y-m-d'), 'Payment_Date' => $paid ? $due->copy()->addDays(mt_rand(0, 4))->min($today)->format('Y-m-d') : null,
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
                'Currency' => 'MVR', 'Premium' => self::INSURANCE] + ($this->chance(0.85) ? ['Status' => 'Paid', 'paid_date' => $paidAt->format('Y-m-d')] : ['Status' => 'Pending']));
            $this->row('work_permit_medical_renewals', $stamp + ['Reference_Number' => 'MED-' . mt_rand(10000, 99999), 'Cost' => (string) self::MEDICAL,
                'Currency' => 'MVR', 'Medical_Center_name' => 'ADK Hospital', 'start_date' => $cycle->format('Y-m-d'), 'end_date' => $end->format('Y-m-d'),
                'Amt' => self::MEDICAL, 'Status' => 'Paid', 'paid_date' => $paidAt->format('Y-m-d')]);
            $this->counts['expats with permits']++;
        }
        foreach ($installments as $table => $rows) {
            $this->rows($table, $rows);
        }
        $ids = $expats->keys()->all();
        $this->paymentRequests($ids, $today);
        $this->wallets($expats, $ids, $today);
        return $this->counts;
    }

    /**
     * PaymentRequestController's requests: one per past month for the fees that fell due in it (all settled — every
     * linked row Paid, each fee's Show flag off and Step on, as XpactEmployeeController / RenewalController leave a
     * child once paid), a rejected duplicate, and this month's request for the next 30 days of dues, still open.
     */
    private function paymentRequests(array $expatIds, Carbon $today): void
    {
        $initials = collect(explode(' ', (string) DB::table('resorts')->where('id', $this->resortId)->value('resort_name')))
            ->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
        $rowsIn = fn (string $table, string $column, Carbon $from, Carbon $to) => DB::table($table)->where('resort_id', $this->resortId)
            ->whereIn('employee_id', $expatIds)->whereBetween($column, [$from->format('Y-m-d'), $to->format('Y-m-d')])->get()->groupBy('employee_id');
        $number = 0;
        $request = function (Carbon $from, Carbon $to, Carbon $raised, bool $open, string $status = 'Pending', ?string $reason = null, ?int $limit = null)
            use ($rowsIn, $initials, &$number) {
            $wp = $rowsIn('work_permits', 'Due_Date', $from, $to);
            $quota = $rowsIn('quota_slot_renewals', 'Due_Date', $from, $to);
            $yearly = ['visa_renewals' => 'start_date', 'employee_insurances' => 'insurance_start_date', 'work_permit_medical_renewals' => 'start_date'];
            $renewals = array_map(fn ($table) => $rowsIn($table, $yearly[$table], $from, $to), array_combine(array_keys($yearly), array_keys($yearly)));
            $people = $wp->keys()->merge($quota->keys())->unique()->values();
            if ($limit) {
                $people = $people->take($limit);
            }
            if ($people->isEmpty()) {
                return;
            }
            $id = $this->row('payment_requests', ['resort_id' => $this->resortId, 'Requestd_id' => 'PR-' . $initials . '-' . (++$number),
                'Request_date' => $raised->format('Y-m-d'), 'Status' => $status, 'Reason' => $reason, 'created_at' => $raised, 'updated_at' => $raised]);
            $children = [];
            foreach ($people as $empId) {
                $fee = fn ($rows) => $rows ? $rows->first() : null;
                [$w, $q] = [$fee($wp->get($empId)), $fee($quota->get($empId))];
                [$v, $i, $m] = [$fee($renewals['visa_renewals']->get($empId)), $fee($renewals['employee_insurances']->get($empId)), $fee($renewals['work_permit_medical_renewals']->get($empId))];
                $items = array_filter([$w, $q, $v, $i, $m]);
                $flag = fn ($item) => $item ? ($open ? ['yes', 'no'] : ['no', 'yes']) : ['no', 'no']; // [Show, Step]
                $children[] = ['Requested_Id' => $id, 'Employee_id' => $empId,
                    'WorkPermitDate' => $w->Due_Date ?? null, 'WorkPermitAmt' => $w->Amt ?? 0, 'WorkPermitMonths' => $w ? 1 : 0, 'WorkPermitIds' => $w->id ?? null,
                    'WorkPermitShow' => $flag($w)[0], 'WorkPermitStep' => $flag($w)[1],
                    'QuotaslotDate' => $q->Due_Date ?? null, 'QuotaslotAmt' => $q->Amt ?? 0, 'QuotaslotMonths' => $q ? 1 : 0, 'QuotaslotIds' => $q->id ?? null,
                    'QuotaslotShow' => $flag($q)[0], 'QuotaslotStep' => $flag($q)[1],
                    'VisaDate' => $v->end_date ?? null, 'VisaAmt' => $v->Amt ?? 0, 'VisaShow' => $flag($v)[0], 'VisaStep' => $flag($v)[1],
                    'InsuranceDate' => $i->insurance_end_date ?? null, 'InsurancePrimume' => $i->Premium ?? 0, 'InsuranceShow' => $flag($i)[0], 'InsuranceStep' => $flag($i)[1],
                    'MedicalReportDate' => $m->end_date ?? null, 'MedicalReportFees' => $m->Amt ?? 0, 'MedicalReportShow' => $flag($m)[0], 'MedicalReportStep' => $flag($m)[1],
                    'OverallSteps' => count($items), 'OngoingSteps' => $open ? 0 : count($items), 'ChildStatus' => $open ? 'Pending' : 'Complete',
                    'created_at' => $raised, 'updated_at' => $raised];
            }
            $this->rows('payment_request_children', $children);
            $this->counts['payment requests'] = ($this->counts['payment requests'] ?? 0) + 1;
        };
        for ($m = 3; $m >= 1; $m--) {
            $month = $today->copy()->startOfMonth()->subMonthsNoOverflow($m);
            $request($month, $month->copy()->endOfMonth(), $month->copy()->subDays(5)->setTime(10, 0), false, 'Approved');
            if ($m === 2) {
                $request($month, $month->copy()->endOfMonth(), $month->copy()->subDays(4)->setTime(15, 0), true, 'Rejact', 'Duplicate of PR-' . $initials . '-' . $number . ' — raised twice.', 3);
            }
        }
        $request($today->copy()->addDay(), $today->copy()->addDays(30), $today->copy()->subDays(2)->setTime(11, 0), true);
    }

    /**
     * The resort's Xpat wallets (Deposited / Reserved / Available / Withdrawn, MVR) with the transfers that explain
     * their balances: each month's work-permit + slot fees are moved Available → Reserved before they fall due and
     * Reserved → Withdrawn once paid on the Xpat portal; this month's are still reserved. The Xpat portal's own
     * figures match except Available, which is one slot fee lower — so the dashboard's reconciliation has a gap
     * to show. Plus each expat's running "expenses since joining".
     */
    private function wallets(object $expats, array $expatIds, Carbon $today): void
    {
        $rates = DB::table('visa_nationalities')->where('resort_id', $this->resortId)->get(['nationality', 'amt'])
            ->mapWithKeys(fn ($r) => [strtolower(trim($r->nationality)) => (float) $r->amt]);
        $deposit = function (string $nationality) use ($rates) { // same either-side prefix match as Common::applyNationalityMatch
            $n = strtolower(trim($nationality));
            foreach ($rates as $name => $amt) {
                if ($name === $n || str_starts_with($name, $n) || str_starts_with($n, $name)) {
                    return $amt;
                }
            }
            return 3500.0;
        };
        $monthFees = function (Carbon $month) use ($expatIds) {
            $range = [$month->copy()->startOfMonth()->format('Y-m-d'), $month->copy()->endOfMonth()->format('Y-m-d')];
            return (float) DB::table('work_permits')->where('resort_id', $this->resortId)->whereIn('employee_id', $expatIds)->whereBetween('Due_Date', $range)->sum('Amt')
                + (float) DB::table('quota_slot_renewals')->where('resort_id', $this->resortId)->whereIn('employee_id', $expatIds)->whereBetween('Due_Date', $range)->sum('Amt');
        };

        $deposits = $expats->mapWithKeys(fn ($p) => [$p->id => $deposit((string) $p->nationality)]);
        $months = collect([3, 2, 1, 0])->map(fn ($m) => $today->copy()->startOfMonth()->subMonthsNoOverflow($m));
        $balance = ['Deposited' => $deposits->sum(), 'Reserved' => 0.0, 'Available' => round($months->sum(fn ($m) => $monthFees($m)) + 45000, -3), 'Withdrawn' => 0.0];
        $opened = $months->first()->copy()->subMonth();
        $walletIds = [];
        foreach (array_keys($balance) as $name) {
            $walletIds[$name] = $this->row('visa_wallets', ['resort_id' => $this->resortId, 'WalletName' => $name, 'Amt' => 0, 'Status' => 'Active',
                'created_at' => $opened, 'updated_at' => $opened]);
        }
        $initials = 'XXX'; // VisaTransectionHistory::boot prefix (resorts has no `name` column, so the app always writes XXX)
        $transfer = function (string $from, string $to, float $amount, Carbon $on, string $comment) use (&$balance, $walletIds, $initials) {
            $balance[$from] -= $amount;
            $balance[$to] += $amount;
            $this->row('visa_transection_histories', ['resort_id' => $this->resortId, 'transaction_id' => $initials . '-' . $on->format('YmdHis'),
                'from_wallet' => $walletIds[$from], 'to_wallet' => $walletIds[$to], 'Amt' => $amount, 'from_wallet_realAmt' => round($balance[$from], 2),
                'to_wallet_realAmt' => round($balance[$to], 2), 'Payment_Date' => $on->format('Y-m-d'), 'comments' => $comment, 'created_at' => $on, 'updated_at' => $on]);
        };
        foreach ($months as $month) {
            $fees = $monthFees($month);
            $transfer('Available', 'Reserved', $fees, $month->copy()->subDays(3)->setTime(10, 15), 'Reserved for ' . $month->format('M Y') . ' work permit and quota slot fees');
            if ($month->lt($today->copy()->startOfMonth())) {
                $transfer('Reserved', 'Withdrawn', $fees, $month->copy()->endOfMonth()->subDays(2)->setTime(16, 40), $month->format('M Y') . ' fees paid on the Xpat portal');
            }
        }
        foreach ($walletIds as $name => $walletId) {
            DB::table('visa_wallets')->where('id', $walletId)->update(['Amt' => round($balance[$name], 2), 'updated_at' => now()]);
            DB::table('visa_xpact_amounts')->updateOrInsert(['resort_id' => $this->resortId, 'Xpact_WalletName' => $name],
                ['Xpact_Amt' => round($balance[$name] - ($name === 'Available' ? self::QUOTA_FEE : 0), 2), 'updated_at' => now()]);
        }

        // Expenses since joining — the per-employee totals the Xpat employee pages and deposit refunds read.
        $paid = fn (string $table, string $column) => DB::table($table)->where('resort_id', $this->resortId)->whereIn('employee_id', $expatIds)
            ->where('Status', 'Paid')->groupBy('employee_id')->selectRaw("employee_id, SUM({$column}) as total")->pluck('total', 'employee_id');
        [$wp, $slot, $insurance, $medical, $visa] = [$paid('work_permits', 'Amt'), $paid('quota_slot_renewals', 'Amt'), $paid('employee_insurances', 'Premium'),
            $paid('work_permit_medical_renewals', 'Amt'), $paid('visa_renewals', 'Amt')];
        $rows = [];
        foreach ($expats as $p) {
            $rows[] = ['resort_id' => $this->resortId, 'employees_id' => $p->id, 'Deposit_Amt' => $deposits[$p->id], 'Total_work_permit' => (float) ($wp[$p->id] ?? 0),
                'Total_slot_Payment' => (float) ($slot[$p->id] ?? 0), 'Total_insurance_Payment' => (float) ($insurance[$p->id] ?? 0),
                'Total_Work_Permit_Medical_Payment' => (float) ($medical[$p->id] ?? 0), 'Total_Visa_Payment' => (float) ($visa[$p->id] ?? 0),
                'Date' => Carbon::parse($p->joining_date)->format('Y-m-d'), 'Year' => (string) $today->year, 'created_at' => $p->joining_date, 'updated_at' => now()];
        }
        $this->rows('total_expensess_since_joings', $rows);
    }
}
