<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\CasualPositionPayConfig;
use App\Models\Employee;
use App\Models\ParentAttendace;
use App\Models\Payroll;
use App\Models\PayrollConfig;
use App\Models\PublicHoliday;
use App\Models\ResortSiteSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * §35 — Casual payroll run. Only reachable when the resort's
 * casual_payment_model is 'direct_pay' (see People → Configuration →
 * Casuals — Payment Model). Deliberately separate from
 * PayrollController::fetchTimeAttendance() (Permanent+Intern) — basic
 * salary comes from CasualPositionPayConfig (position-level, not
 * employees.basic_salary), there's no service charge, and the
 * attendance/leave model is the simple manual-mark one from
 * AttandanceRegisterController::nonPermanentMark(), not punch-based.
 *
 * Everything downstream of "compute earnings" (select employees, save
 * attendance, save deductions, save reviews, lock, send for approval,
 * approve, view/payslip) reuses PayrollController's existing endpoints
 * as-is — they're already payroll_id/employee_id-generic with no
 * Permanent-specific assumptions. Only the parts that are genuinely
 * different for Casual live here.
 */
class CasualPayrollController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.casualPayroll.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $resort_id = $this->resort->resort_id;
        $settings = ResortSiteSettings::where('resort_id', $resort_id)->first();
        $paymentModel = $settings->casual_payment_model ?? 'lump_sum';

        // Lump-sum resorts never see this screen — the resort pays the
        // service provider directly, this app only tracks attendance/OT.
        if ($paymentModel !== 'direct_pay') {
            return redirect()->route('people.casualPaymentModel.index')
                ->with('error', 'Casual Payroll is only available when the payment model is set to "Paid directly, plus service provider admin fee". Configure it first.');
        }

        $page_title = 'Casual Payroll';
        $currency = $settings->currency ?? 'USD';
        $cutoffDay = (int) (PayrollConfig::where('resort_id', $resort_id)->value('cutoff_day') ?? 15);

        $availablePeriods = [];
        $today = Carbon::now();
        for ($i = 1; $i <= 6; $i++) {
            $baseStart = Carbon::create($today->year, $today->month, 1)->subMonths($i);
            $startDay = min($cutoffDay, $baseStart->daysInMonth);
            $periodStart = $baseStart->copy()->day($startDay)->addDay();

            $baseEnd = Carbon::create($today->year, $today->month, 1)->subMonths($i - 1);
            $endDay = min($cutoffDay, $baseEnd->daysInMonth);
            $periodEnd = $baseEnd->copy()->day($endDay);

            $existingPayroll = Payroll::where('resort_id', $resort_id)
                ->where('payroll_category', 'Casual')
                ->where('start_date', $periodStart->format('Y-m-d'))
                ->where('end_date', $periodEnd->format('Y-m-d'))
                ->first(['id', 'status']);

            $isPaid = $existingPayroll && in_array($existingPayroll->status, ['locked', 'completed']);
            $isPendingApproval = $existingPayroll && in_array($existingPayroll->status, ['pending_approval', 'approved']);

            $availablePeriods[] = [
                'start_date' => $periodStart->format('Y-m-d'),
                'end_date' => $periodEnd->format('Y-m-d'),
                'label' => $periodStart->format('d M Y') . ' - ' . $periodEnd->format('d M Y'),
                'is_paid' => $isPaid,
                'is_pending_approval' => $isPendingApproval,
                'status_label' => $isPaid ? '(Paid)' : ($isPendingApproval ? '(Pending Approval)' : '(Unpaid)'),
                'payroll_id' => $existingPayroll->id ?? null,
            ];
        }
        $availablePeriods = array_reverse($availablePeriods);

        return view('resorts.payroll.casual-run.index', compact('page_title', 'currency', 'cutoffDay', 'availablePeriods'));
    }

    /**
     * Casual employees whose position has a pay config — an employee
     * without one can't be run through payroll (there's no basic salary to
     * pay them with) so they're excluded rather than silently paid $0.
     */
    public function getEmployees(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $configuredPositionIds = CasualPositionPayConfig::where('resort_id', $resort_id)->pluck('position_id');

        $query = Employee::with(['resortAdmin', 'position', 'department'])
            ->where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereIn('employment_type', Common::manningCategoryEmploymentTypes('Casual'))
            ->whereIn('Position_id', $configuredPositionIds);

        if ($request->searchTerm) {
            $query->whereHas('resortAdmin', function ($q) use ($request) {
                $q->where('first_name', 'LIKE', "%{$request->searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$request->searchTerm}%");
            });
        }

        return datatables()->of($query)
            ->addColumn('name', fn($e) => trim(($e->resortAdmin->first_name ?? '') . ' ' . ($e->resortAdmin->last_name ?? '')))
            ->addColumn('position', fn($e) => $e->position->position_title ?? '')
            ->addColumn('department', fn($e) => $e->department->name ?? '')
            ->make(true);
    }

    /**
     * Earnings for the selected Casual employees over a period — position-
     * sourced basic salary (not employees.basic_salary), same
     * proration/OT-rate shape as the Permanent run's fetchTimeAttendance(),
     * no service charge. Commission is tracked for the resort's own record
     * (what they owe the service provider), never added into the
     * employee's own pay.
     */
    public function fetchTimeAttendance(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $request->validate([
            'employees' => 'required|array',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        // Same tenant-scoping reasoning as the Permanent equivalent — never
        // trust a client-posted employee id list at face value.
        $scopedEmployeeIds = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereIn('employment_type', Common::manningCategoryEmploymentTypes('Casual'))
            ->whereIn('id', $request->employees)
            ->pluck('id')
            ->all();

        $employees = Employee::with(['resortAdmin', 'position', 'department'])
            ->whereIn('id', $scopedEmployeeIds)
            ->get()
            ->keyBy('id');

        $payConfigByPosition = CasualPositionPayConfig::where('resort_id', $resort_id)
            ->whereIn('position_id', $employees->pluck('Position_id')->unique())
            ->get()
            ->keyBy('position_id');

        $periodStart = Carbon::parse($request->startDate);
        $periodEnd = Carbon::parse($request->endDate);
        $totalDaysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

        $attendanceByEmp = ParentAttendace::whereIn('Emp_id', $scopedEmployeeIds)
            ->whereBetween('date', [$request->startDate, $request->endDate])
            ->get()
            ->groupBy('Emp_id');

        $settings = ResortSiteSettings::where('resort_id', $resort_id)->first();
        $dollarToMvr = (float) ($settings->DollertoMVR ?? 15.42) ?: 15.42;
        $displayCurrency = strtoupper($request->input('currency', $settings->currency ?? 'USD')) === 'MVR' ? 'MVR' : 'USD';

        // Converts an amount from its own stored currency to the display
        // currency this run is being computed in. FX derives from
        // DollertoMVR only — never a stored inverse rate (project rule).
        $toDisplay = function (float $amount, string $fromCurrency) use ($displayCurrency, $dollarToMvr) {
            if ($fromCurrency === $displayCurrency) {
                return $amount;
            }
            return $fromCurrency === 'USD' ? $amount * $dollarToMvr : $amount / $dollarToMvr;
        };

        $result = [];
        $unconfigured = [];

        foreach ($employees as $empId => $employee) {
            $payConfig = $payConfigByPosition->get($employee->Position_id);
            if (!$payConfig) {
                $unconfigured[] = trim(($employee->resortAdmin->first_name ?? '') . ' ' . ($employee->resortAdmin->last_name ?? ''));
                continue;
            }

            $basic = $toDisplay((float) $payConfig->basic_salary, $payConfig->basic_salary_currency);
            $commission = $toDisplay((float) $payConfig->commission_amount, $payConfig->commission_currency);

            $records = ($attendanceByEmp->get($empId) ?? collect())->values();

            $dayOffCount = $records->where('Status', 'DayOff')->count();
            // Casual/Intern have no paid-leave-category entitlement the way
            // Permanent does (§1 of the founder's own spec: supervisor marks
            // the full daily status, no benefit-grid leave allocation
            // applies) — only Present + DayOff are paid; Absent/Sick/
            // ShortLeave/HalfDayLeave/FullDayLeave are all unpaid days.
            $presentDayEquivalent = 0.0;
            foreach ($records->where('Status', 'Present') as $rec) {
                $hasHours = !empty($rec->DayWiseTotalHours) && !in_array($rec->DayWiseTotalHours, ['0', '0:0', '0:00', '00:00'], true);
                if (!$hasHours) {
                    $presentDayEquivalent += 1.0;
                    continue;
                }
                $parts = explode(':', $rec->DayWiseTotalHours);
                $dayHours = (int) ($parts[0] ?? 0) + ((int) ($parts[1] ?? 0) / 60);
                $presentDayEquivalent += min(1.0, $dayHours / 8);
            }
            $presentCount = $records->where('Status', 'Present')->count();
            $unpaidCount = $records->whereIn('Status', ['Absent', 'Sick', 'ShortLeave', 'HalfDayLeave', 'FullDayLeave'])->count();

            $regularOT = $fridayOT = $holidayOT = 0.0;
            foreach ($records as $rec) {
                if (empty($rec->OverTime) || in_array($rec->OverTime, ['0', '0:0', '0:00', '00:00', '00:00:00', '-', ''], true)
                    || strtolower(trim($rec->OTStatus ?? '')) !== 'approved') {
                    continue;
                }
                $otParts = explode(':', $rec->OverTime);
                $hours = (int) ($otParts[0] ?? 0) + ((int) ($otParts[1] ?? 0) / 60);

                $isFriday = Carbon::parse($rec->date)->isFriday();
                $isPublicHoliday = PublicHoliday::where('holiday_date', Carbon::parse($rec->date)->format('d M Y'))->exists();
                $isResortHoliday = DB::table('resortholidays')->where('resort_id', $resort_id)->where('PublicHolidaydate', $rec->date)->exists();
                $isDayOff = $rec->Status === 'DayOff';

                if ($isFriday) {
                    $fridayOT += $hours;
                } elseif ($isPublicHoliday || $isResortHoliday || $isDayOff) {
                    $holidayOT += $hours;
                } else {
                    $regularOT += $hours;
                }
            }

            $perDay = $totalDaysInPeriod > 0 ? $basic / $totalDaysInPeriod : 0;
            $earnedSalary = round($perDay * ($presentDayEquivalent + $dayOffCount), 2);
            $absentDeduction = round($perDay * $unpaidCount, 2);

            $perHourSalary = $totalDaysInPeriod > 0 ? $basic / $totalDaysInPeriod / 8 : 0;
            $regularOTPay = round($perHourSalary * 1.25 * $regularOT, 2);
            $fridayOTPay = round($perHourSalary * 1.50 * $fridayOT, 2);
            $holidayOTPay = round($perHourSalary * 1.50 * $holidayOT, 2);
            $totalOTPay = round($regularOTPay + $fridayOTPay + $holidayOTPay, 2);

            // No service charge (§35) — normal pay is earnings + OT only.
            // Commission is reported alongside for the resort's own
            // records, never folded into what the employee is paid.
            $normalPay = round($earnedSalary + $totalOTPay, 2);

            $result[] = [
                'id' => $employee->Emp_id,
                'employee_id' => $employee->id,
                'name' => trim(($employee->resortAdmin->first_name ?? '') . ' ' . ($employee->resortAdmin->last_name ?? '')),
                'position' => $employee->position->position_title ?? '',
                'present' => $presentCount,
                'day_offs' => $dayOffCount,
                'unpaid_days' => $unpaidCount,
                'regular_ot' => round($regularOT, 2),
                'friday_ot' => round($fridayOT, 2),
                'holiday_ot' => round($holidayOT, 2),
                'basic_salary' => round($basic, 2),
                'earned_salary' => $earnedSalary,
                'absent_deduction' => $absentDeduction,
                'regular_ot_pay' => $regularOTPay,
                'friday_ot_pay' => $fridayOTPay,
                'holiday_ot_pay' => $holidayOTPay,
                'total_ot_pay' => $totalOTPay,
                'service_provider_commission' => round($commission, 2),
                'normal_pay' => $normalPay,
                'currency' => $displayCurrency,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'unconfigured_employees' => $unconfigured,
            'currency' => $displayCurrency,
        ]);
    }
}
