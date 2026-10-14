<?php

namespace Database\Seeders\Demo;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo ENV slice 4: payroll. Needs DemoTimeSeeder's attendance first.
 *
 * Twelve locked monthly runs and last month's run sent for approval, waiting
 * on Finance EXCOM → HR EXCOM → GM. Rows are inserted in the shapes the run
 * wizard saves (PayrollController save* steps + its page script), with each
 * employee's pay worked out the way fetchTimeAttendance works it out —
 * through the same Common::payroll* helpers — so re-opening the pending run
 * shows the same figures. Amounts are USD (the resort currency is 'Dollar').
 * Also: salary advances and loans with recovery schedules (paid by the
 * locked runs), and a staff shop whose consented purchases payroll deducts.
 */
class DemoPayrollSeeder
{
    const RUNS = 13; // 12 locked + last month pending approval
    // Monthly service-charge pool (USD), high season Dec–Apr.
    const SC_POOL = [1 => 52000, 2 => 55000, 3 => 50000, 4 => 44000, 5 => 30000, 6 => 24000,
        7 => 26000, 8 => 30000, 9 => 25000, 10 => 32000, 11 => 41000, 12 => 58000];

    private float $rate;
    private object $employees;
    private array $approvers;   // step => [employee id, name]
    private array $counts = ['payroll runs' => 0, 'payslips' => 0, 'advances' => 0, 'shop purchases' => 0];

    public function __construct(private int $resortId, private int $adminId)
    {
    }

    public function seed(): array
    {
        $this->rate = (float) DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') ?: 15.42;
        $this->employees = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $this->resortId)->where('e.status', 'Active')->whereNull('e.deleted_at')->orderBy('e.id')
            ->get(['e.id', 'e.Emp_id', 'e.Position_id', 'e.Dept_id', 'e.payment_mode', 'e.basic_salary', 'e.basic_salary_currency',
                'e.nationality', 'e.joining_date', 'e.Admin_Parent_id', 'a.first_name', 'a.last_name', 'a.email'])
            ->keyBy('id');
        $by = fn ($login) => $this->employees->firstWhere('email', $login . '@' . config('demo.login_domain'));
        foreach ([1 => ['Finance EXCOM', 'finance'], 2 => ['HR EXCOM', 'hr'], 3 => ['GM', 'gm']] as $step => [$role, $login]) {
            $e = $by($login);
            $this->approvers[$step] = [$role, $e->id, trim($e->first_name . ' ' . $e->last_name)];
        }

        $first = Carbon::today()->startOfMonth()->subMonths(self::RUNS);
        $advanceDue = $this->advances($first);
        $shop = $this->shop($first);
        for ($i = 0; $i < self::RUNS; $i++) {
            $start = $first->copy()->addMonths($i);
            $this->run($start, $start->copy()->endOfMonth(), $i === self::RUNS - 1, $advanceDue, $shop);
        }
        return $this->counts;
    }

    private function run(Carbon $start, Carbon $end, bool $pending, array $advanceDue, array $shop): void
    {
        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');
        $created = $end->copy()->addDays(2)->setTime(9, 15);
        $locked = $end->copy()->addDays(4)->setTime(11, 30);
        $payrollId = DB::table('payroll')->insertGetId([
            'resort_id' => $this->resortId, 'payroll_category' => 'Permanent', 'start_date' => $from, 'end_date' => $to,
            'total_payroll' => 0, 'total_employees' => 0, 'draft_date' => $created->format('Y-m-d'),
            'payment_date' => $end->copy()->addDays(5)->format('Y-m-d'), 'status' => $pending ? 'pending_approval' : 'locked',
            'created_at' => $created, 'updated_at' => $pending ? $created->copy()->addDay() : $locked,
        ]);

        // Like fetchTimeAttendance: only people with attendance in the period are paid in it.
        $attendance = DB::table('parent_attendaces')->where('resort_id', $this->resortId)->whereBetween('date', [$from, $to])->get()->groupBy('Emp_id');
        $people = $this->employees->only($attendance->keys()->all());
        $context = Common::payrollRosterContext($this->resortId, $people->keys(), $from, $to, $attendance->flatten()->pluck('id'));
        $holidays = DB::table('resortholidays')->where('resort_id', $this->resortId)->whereBetween('PublicHolidaydate', [$from, $to])
            ->pluck('PublicHolidaydate')->flip();
        $days = $start->daysInMonth;

        $lines = [];
        foreach ($people as $id => $e) {
            $records = $attendance[$id]->all();
            $marks = Common::payrollDayMarks($records, $context, $id);
            $presentRows = collect($records)->whereIn('Status', ['Present', 'On-Time', 'Late', 'ShortLeave', 'HalfDay']);
            $presentEquivalent = $presentRows->sum(fn ($r) => Common::payrollDayFraction($r, $context));
            $dayOff = $marks['day_off_dates']->count();

            $regular = $friday = $holiday = 0.0;
            foreach ($records as $r) {
                $isFriday = Carbon::parse($r->date)->isFriday();
                if ($r->OverTime && strtolower((string) $r->OTStatus) === 'approved') {
                    $h = $this->hours($r->OverTime);
                    if ($isFriday) {
                        $friday += $h;
                    } elseif (isset($holidays[$r->date])) {
                        $holiday += $h;
                    } else {
                        $regular += $h;
                    }
                }
                if ($isFriday && in_array($r->Status, ['Present', 'On-Time', 'Late'], true) && $this->hours($r->DayWiseTotalHours) > 0) {
                    $friday += $this->hours($r->DayWiseTotalHours); // Friday work is paid at the Friday rate on top
                }
            }

            $basic = (float) $e->basic_salary / ($e->basic_salary_currency === 'MVR' ? $this->rate : 1);
            $perDay = $basic / $days;
            $perHour = $perDay / 8;
            $earned = round($perDay * ($presentEquivalent + $dayOff), 2);
            $regularPay = round($perHour * 1.25 * $regular, 2);
            $fridayPay = round($perHour * 1.5 * $friday, 2);
            $holidayPay = round($perHour * 1.5 * $holiday, 2);
            $lines[$id] = [
                'e' => $e, 'present' => $presentRows->count(), 'absent' => $marks['no_shows']->count() + collect($records)->where('Status', 'Absent')->count(),
                'workdays' => $presentRows->count() + $dayOff, 'regular' => $regular, 'holiday' => $holiday,
                'earned' => $earned, 'regularPay' => $regularPay, 'holidayPay' => $holidayPay, 'otPay' => round($regularPay + $fridayPay + $holidayPay, 2),
            ];
        }

        // Service charge: the month's pool shared by days worked, floored to cents, the last person takes the remainder.
        $pool = round(self::SC_POOL[$start->month] * mt_rand(90, 110) / 100, -2);
        $workdays = array_sum(array_column($lines, 'workdays')) ?: 1;
        $shared = 0.0;
        $ids = array_keys($lines);
        foreach ($ids as $n => $id) {
            $lines[$id]['sc'] = $n === count($ids) - 1 ? round($pool - $shared, 2) : floor($pool * $lines[$id]['workdays'] / $workdays * 100) / 100;
            $shared += $lines[$id]['sc'];
        }
        DB::table('resort_service_charges')->insert(['resort_id' => $this->resortId, 'month' => $start->month, 'year' => $start->year,
            'service_charge' => $pool, 'created_at' => $created, 'updated_at' => $created]);

        $rows = ['payroll_employees' => [], 'payroll_time_and_attandance' => [], 'payroll_service_charges' => [], 'payroll_deductions' => [], 'payroll_reviews' => []];
        $stamp = ['payroll_id' => $payrollId, 'created_at' => $created, 'updated_at' => $created];
        $total = 0.0;
        foreach ($lines as $id => $l) {
            $e = $l['e'];
            $key = $stamp + ['employee_id' => $id, 'Emp_id' => $e->Emp_id];
            $pension = $e->nationality === 'Maldivian' ? round($l['earned'] * 0.07, 2) : 0.0;
            $taxableMvr = max(($l['earned'] + $l['sc'] + $l['otPay']) * $this->rate - $pension * $this->rate, 0);
            $ewt = round(Common::calculateEWT($taxableMvr) / $this->rate, 2);
            $advance = round($advanceDue[$id][$start->format('Y-m')] ?? 0, 2);
            $shopDue = round($shop[$id][$start->format('Y-m')] ?? 0, 2);
            $deductions = round($shopDue + $advance + $pension + $ewt, 2);
            $earnings = round($l['otPay'] + $l['sc'] + $l['earned'], 2);
            $total += $earnings - $deductions;

            $rows['payroll_employees'][] = $stamp + ['employee_id' => $id, 'Emp_id' => $e->Emp_id, 'position' => $e->Position_id,
                'department' => $e->Dept_id, 'section' => null, 'paymentMethod' => $e->payment_mode ?: 'Cash'];
            $rows['payroll_time_and_attandance'][] = $key + ['present_days' => $l['present'], 'absent_days' => $l['absent'], 'leave_types' => '-',
                'regular_ot_hours' => round($l['regular'], 2), 'holiday_ot_hours' => round($l['holiday'], 2),
                'total_ot' => round($l['regular'] + $l['holiday'], 2), 'notes' => null];
            $rows['payroll_service_charges'][] = $key + ['total_working_days' => $l['workdays'], 'service_charge_amount' => $l['sc']];
            $rows['payroll_deductions'][] = $key + ['attendance_deduction' => 0, 'city_ledger' => 0, 'staff_shop' => $shopDue,
                'advance_loan' => $advance, 'pension' => $pension, 'ewt' => $ewt, 'other' => 0, 'total_deductions' => $deductions];
            $rows['payroll_reviews'][] = $key + ['service_charge' => $l['sc'], 'service_provider_commission' => null,
                'regularOTPay' => $l['regularPay'], 'holidayOTPay' => $l['holidayPay'], 'earnings_basic' => $l['earned'], 'earned_salary' => $l['earned'],
                'earnings_overtime' => $l['otPay'], 'earnings_allowance' => 0, 'kpi_bonus' => 0, 'total_earnings' => $earnings,
                'total_deductions' => $deductions, 'net_salary' => round($earnings - $deductions, 2)];
        }
        foreach ($rows as $table => $data) {
            foreach (array_chunk($data, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        // Finance EXCOM → HR EXCOM → GM; the GM's approval locked the run (PayrollController::finalizeAndLockPayroll).
        $signed = [1 => $end->copy()->addDays(3)->setTime(10, 5), 2 => $end->copy()->addDays(3)->setTime(15, 40), 3 => $locked];
        foreach ($this->approvers as $step => [$role, $empId, $name]) {
            DB::table('payroll_approvals')->insert([
                'payroll_id' => $payrollId, 'resort_id' => $this->resortId, 'step_order' => $step, 'role_title' => $role,
                'approver_id' => $pending ? null : $empId, 'approver_name' => $pending ? null : $name, 'status' => $pending ? 'pending' : 'approved',
                'remarks' => $pending ? null : ($step === 3 ? 'Approved for payment.' : null), 'approved_at' => $pending ? null : $signed[$step],
                'signature_img' => null, 'signature_name' => $pending ? null : $name, 'signed_at' => $pending ? null : $signed[$step],
                'created_at' => $created, 'updated_at' => $pending ? $created : $signed[$step],
            ]);
        }
        if (!$pending) {
            DB::table('payroll')->where('id', $payrollId)->update(['total_payroll' => round($total, 2), 'total_employees' => count($lines)]);
            // What the lock does: this period's installments are recovered, and shop purchases deducted are paid.
            DB::table('payroll_recovery_schedule')->whereIn('employee_id', array_keys($lines))->where('status', 'Pending')
                ->whereBetween('repayment_date', [$from, $to])->update(['status' => 'Paid', 'updated_at' => $locked]);
            DB::table('payments')->whereIn('emp_id', array_keys($lines))->where('status', 'Consented')->whereBetween('purchased_date', [$from, $to])
                ->update(['status' => 'Paid', 'payroll_deducted' => DB::raw('price'), 'updated_at' => $locked]);
        }
        $this->counts['payroll runs']++;
        $this->counts['payslips'] += count($lines);
    }

    /**
     * Approved salary advances and loans, repaid on the 5th of each following month,
     * plus a few requests still going through HR → Finance → GM.
     * Returns [employee id][Y-m] => amount due in that month's run.
     */
    private function advances(Carbon $first): array
    {
        $staff = $this->employees->filter(fn ($e) => !in_array($e->email, ['gm@' . config('demo.login_domain')], true))->values();
        $hr = $this->approvers[2][1];
        $finance = $this->approvers[1][1];
        $gm = $this->approvers[3][1];
        $purposes = ['Family emergency back home', 'Medical bills for a parent', 'School fees for my children', 'House repair after the monsoon',
            'Wedding expenses', 'Travel home for a funeral', 'Motorbike for commuting on leave'];
        $due = [];
        $plans = [];
        for ($i = 0; $i < 9; $i++) {
            $plans[] = ['date' => $first->copy()->addDays(mt_rand(5, 330)), 'state' => 'approved'];
        }
        $plans[] = ['date' => Carbon::today()->subDays(2), 'state' => 'pending'];
        $plans[] = ['date' => Carbon::today()->subDays(4), 'state' => 'pending'];
        $plans[] = ['date' => Carbon::today()->subDays(6), 'state' => 'finance'];

        foreach ($plans as $plan) {
            $date = $plan['date'];
            $eligible = $staff->filter(fn ($s) => Carbon::parse($s->joining_date)->lt($date->copy()->subMonth()))->values();
            $e = $eligible[mt_rand(0, count($eligible) - 1)];
            $loan = mt_rand(0, 1) === 1;
            $amount = $loan ? mt_rand(8, 30) * 100 : mt_rand(3, 8) * 100;
            $installments = $loan ? mt_rand(4, 8) : mt_rand(1, 2);
            $approved = $plan['state'] === 'approved';
            $id = DB::table('payroll_advance')->insertGetId([
                'employee_id' => $e->id, 'resort_id' => $this->resortId, 'request_type' => $loan ? 'Loan Request' : 'Salary Advance',
                'request_amount' => $amount, 'currency' => 'USD', 'request_date' => $date->format('Y-m-d'), 'pourpose' => $purposes[mt_rand(0, count($purposes) - 1)],
                'status' => $approved ? 'Approved' : ($plan['state'] === 'finance' ? 'In-Progress' : 'Pending'),
                'recovery_status' => $approved ? 'In Progress' : 'Pending', 'priority' => ['Low', 'Medium', 'High'][mt_rand(0, 2)],
                'hr_status' => $plan['state'] === 'pending' ? 'Pending' : 'Approved', 'hr_action_date' => $plan['state'] === 'pending' ? null : $date->copy()->addDay()->format('Y-m-d'),
                'hr_approved_by' => $plan['state'] === 'pending' ? null : $hr,
                'finance_status' => $approved ? 'Approved' : 'Pending', 'finance_action_date' => $approved ? $date->copy()->addDays(2)->format('Y-m-d') : null,
                'finance_approved_by' => $approved ? $finance : null,
                'gm_status' => $approved ? 'Approved' : 'Pending', 'gm_action_date' => $approved ? $date->copy()->addDays(3)->format('Y-m-d') : null,
                'gm_approved_by' => $approved ? $gm : null, 'action_date' => $approved ? $date->copy()->addDays(3)->format('Y-m-d') : null,
                'created_by' => $e->Admin_Parent_id, 'modified_by' => $e->Admin_Parent_id,
                'created_at' => $date->copy()->setTime(9, 0), 'updated_at' => $date->copy()->addDays($approved ? 3 : 1)->setTime(12, 0),
            ]);
            $this->counts['advances']++;
            if (!$approved) {
                continue;
            }
            // AdvanceSalaryController::paymentRescheduleStore: one installment on the 5th of each month, no interest here.
            $each = round($amount / $installments, 2);
            for ($n = 0; $n < $installments; $n++) {
                $repay = $date->copy()->addMonthsNoOverflow($n + 1)->day(5);
                $installment = $n === $installments - 1 ? round($amount - $each * $n, 2) : $each;
                DB::table('payroll_recovery_schedule')->insert([
                    'payroll_advance_id' => $id, 'employee_id' => $e->id, 'repayment_date' => $repay->format('Y-m-d'), 'remark' => null,
                    'amount' => $installment, 'interest' => 0, 'interest_amount' => 0, 'remaining_balance' => round($amount - $each * ($n + 1), 2) > 0 ? round($amount - $each * ($n + 1), 2) : 0,
                    'status' => 'Pending', 'created_by' => $this->adminId, 'modified_by' => $this->adminId,
                    'created_at' => $date->copy()->addDays(3), 'updated_at' => $date->copy()->addDays(3),
                ]);
                $due[$e->id][$repay->format('Y-m')] = ($due[$e->id][$repay->format('Y-m')] ?? 0) + $installment;
            }
        }
        return $due;
    }

    /**
     * The staff shop: purchases the employee consented to are deducted by
     * that month's payroll (and marked Paid when it locks). This month's are
     * still waiting for the employee's consent.
     * Returns [employee id][Y-m] => amount the run deducts.
     */
    private function shop(Carbon $first): array
    {
        $at = $first->copy()->subDay();
        $shopkeeperId = DB::table('shopkeepers')->insertGetId([
            'resort_id' => $this->resortId, 'name' => 'Staff Shop', 'email' => 'shop@' . config('demo.login_domain'),
            'password' => Hash::make(config('demo.password')), 'contact_no' => '+960 664 0101', 'must_change_password' => 0,
            'created_at' => $at, 'updated_at' => $at,
        ]);
        $catalogue = ['Toothpaste' => 3.5, 'Shampoo' => 6, 'Soft drink (6-pack)' => 5, 'Instant noodles (box)' => 9, 'Phone top-up 100 MVR' => 6.5,
            'Sunscreen' => 14, 'Snack bar box' => 7.5, 'Laundry detergent' => 8, 'Bottled water (12)' => 4.5, 'Insect repellent' => 6];
        $products = [];
        foreach ($catalogue as $name => $price) {
            $products[] = [DB::table('products')->insertGetId(['shopkeeper_id' => $shopkeeperId, 'name' => $name, 'price' => $price,
                'currency_type' => 'USD', 'created_at' => $at, 'updated_at' => $at]), $price];
        }

        $staff = $this->employees->values();
        $thisMonth = Carbon::today()->startOfMonth();
        $due = [];
        $rows = [];
        for ($month = $first->copy(); $month->lte($thisMonth); $month->addMonth()) {
            $last = $month->isSameMonth($thisMonth) ? Carbon::today() : $month->copy()->endOfMonth();
            for ($i = 0; $i < 45; $i++) {
                $e = $staff[mt_rand(0, count($staff) - 1)];
                [$productId, $price] = $products[mt_rand(0, count($products) - 1)];
                $qty = mt_rand(1, 3);
                $bought = $month->copy()->addDays(mt_rand(0, (int) $month->diffInDays($last)));
                if ($bought->lt(Carbon::parse($e->joining_date))) {
                    continue;
                }
                // Consented purchases go to payroll; now and then one is refused. This month's await consent.
                $status = $month->isSameMonth($thisMonth) ? 'Pending Consent' : (mt_rand(1, 20) === 1 ? 'Rejected' : 'Consented');
                $rows[] = ['shopkeeper_id' => $shopkeeperId, 'order_id' => 'ORD-' . strtoupper(substr(md5((string) mt_rand()), 0, 8)),
                    'emp_id' => $e->id, 'purchased_date' => $bought->format('Y-m-d'), 'product_id' => $productId, 'quantity' => $qty,
                    'price' => round($price * $qty, 2), 'status' => $status, 'cash_paid' => 0, 'payroll_deducted' => 0,
                    'created_at' => $bought->copy()->setTime(mt_rand(8, 21), mt_rand(0, 59)), 'updated_at' => $bought->copy()->setTime(22, 0)];
                if ($status === 'Consented') {
                    $due[$e->id][$bought->format('Y-m')] = ($due[$e->id][$bought->format('Y-m')] ?? 0) + round($price * $qty, 2);
                }
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
        $this->counts['shop purchases'] = count($rows);
        return $due;
    }

    private function hours(?string $hm): float
    {
        [$h, $m] = array_pad(explode(':', (string) $hm), 2, 0);
        return (int) $h + (int) $m / 60;
    }
}
