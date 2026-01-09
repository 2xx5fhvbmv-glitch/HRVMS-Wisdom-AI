<?php

namespace App\Services;

use App\Models\PayrollConfig;
use App\Models\ParentAttendace;
use App\Models\PublicHoliday;
use App\Models\EmployeeAllowance;
use App\Models\ResortSiteSettings;
use App\Models\PayrollRecoverySchedule;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FinalSettlementService
{
    public function calculateFinalMonthData(Employee $employee, $resortId)
    {
        // dd($resortId);
        if($employee->resignation){
             $lastDay = Carbon::parse($employee->resignation->last_working_day);
        }
        else {
            $lastDay = Carbon::now()->endOfMonth();
        }
        $cutoffDay = PayrollConfig::where('resort_id', $resortId)->value('cutoff_day') ?? 15;


        // Payroll period range
        if ($lastDay->day >= $cutoffDay) {
            $payrollStart = $lastDay->copy()->day($cutoffDay);
            $payrollEnd = $lastDay->copy()->addMonth()->day($cutoffDay - 1);
        } else {
            $payrollEnd = $lastDay->copy()->day($cutoffDay - 1);
            $payrollStart = $lastDay->copy()->subMonth()->day($cutoffDay);
        }
        $payment_mode = $employee->payment_mode;
        $attendance = ParentAttendace::where('Emp_id', $employee->Emp_id)
            ->whereBetween('date', [$payrollStart, $lastDay])
            ->get();

        $daysWorked = $attendance->where('Status', 'Present')->count();
        $totalDays = $payrollEnd->diffInDays($payrollStart) + 1;

        $settings = ResortSiteSettings::where('resort_id', $resortId)->first();
        // dd($settings);
        $currency = $employee->basic_salary_currency;

        $basic = floatval($employee->basic_salary);
        $basicMVR = ($currency === 'USD') ? $basic * $settings->DollertoMVR : $basic;

        $dailySalary = round($basicMVR / $totalDays, 2);
        $leaveBalance = $this->getLeaveBalance($employee, $resortId);
       
        $leaveEncashment = round($leaveBalance['total_days'] * $dailySalary, 2);

        $regularOT = $holidayOT = 0;
        foreach ($attendance as $record) {
            if (!empty($record->OverTime) && str_contains($record->OverTime, ':')) {
                [$h, $m] = explode(':', $record->OverTime);
                $hours = (int)$h + ((int)$m / 60);
                $isHoliday = PublicHoliday::where('holiday_date', date('d-m-Y', strtotime($record->date)))->exists();
                $isHoliday ? $holidayOT += $hours : $regularOT += $hours;
            }
        }

        $hourlyRate = $basicMVR / ($totalDays * 8);
        $regularOtAmount = round($regularOT * $hourlyRate * 1.25, 2);
        $holidayOtAmount = round($holidayOT * $hourlyRate * 1.5, 2);
        $totalOtAmount = $regularOtAmount + $holidayOtAmount;

        $allowances = EmployeeAllowance::with('allowanceName')
            ->where('employee_id', $employee->id)
            ->get();
        // dd($allowances);
        $allowanceDetails = [];
        $totalAllowance = 0;
        foreach ($allowances as $a) {
            $amount = floatval($a->amount);
            $convertedAmount = ($currency === 'USD') ? $amount * $settings->DollertoMVR : $amount;
            $totalAllowance += $convertedAmount;

            $allowanceDetails[] = [
                'id'   => $a->id,
                'name' => $a->allowanceName->particulars ?? 'N/A',
                'original_amount' => $amount,
                'converted_amount' => round($convertedAmount, 2),
                'unit' => $a->amount_unit,
            ];
        }

        $ramadanBonus = 0;
        $nextMonth = Carbon::parse($payrollStart)->addMonthNoOverflow()->startOfMonth();
        $isNextMonthRamadan = Cache::remember(
            'is_ramadan_' . $nextMonth->format('Y_m'),
            now()->addDays(10),
            function () use ($nextMonth) {
                $response = Http::get("https://api.aladhan.com/v1/gToH", [
                    'date' => $nextMonth->format('d-m-Y')
                ]);
                return $response->successful() && ($response['data']['hijri']['month']['number'] == 9);
            }
        );

        if ($isNextMonthRamadan && $employee->resort?->benefitGrid) {
            $grid = $employee->resort->benefitGrid;
            $eligibility = $grid->ramadan_bonus_eligibility;
            $isLocal = strtolower($employee->nationality) === 'maldivian';
            $eligible = match ($eligibility) {
                'all' => true,
                'all_muslim' => $employee->religion === 'Muslim',
                'local_muslim' => $employee->religion === 'Muslim' && $isLocal,
                'all_local' => $isLocal,
                default => false
            };
            if ($eligible && $grid->ramadan_bonus) {
                $ramadanBonus = floatval($grid->ramadan_bonus);
            }
        }

        $pension = round($basicMVR * 0.07, 2);
        $taxableIncome = $basicMVR + $totalOtAmount + $totalAllowance + $ramadanBonus - $pension;

        $proratedBasic = round($dailySalary * $daysWorked, 2);
        $earnedSalary = $proratedBasic + $totalOtAmount + $totalAllowance + $ramadanBonus + $leaveEncashment;

        $recoveryData = PayrollRecoverySchedule::where('employee_id', $employee->id)
            ->where('status', 'Pending')
            ->get()
            ->sum(function ($item) use ($currency, $settings) {
                $amount = ($item->amount ?? 0) + ($item->interest_amount ?? 0);
                return ($currency === 'USD') ? $amount * $settings->DollertoMVR : $amount;
            });
        // Remove dd() when done debugging
        // dd($recoveryData);

        return [
            'pension' => round($pension,2),
            'ewt' => round(Common::calculateEWT($taxableIncome),2),
            'taxable_income' => round($taxableIncome, 2),
            'leave_balance' => $leaveBalance['total_days'],
            'leave_encashment' => round($leaveEncashment,2),
            'daily_salary' => round($dailySalary,2),
            'worked_days' => $daysWorked,
            'total_days' => $totalDays,
            'basic_salary_mvr' => round($basicMVR, 2),
            'proratedBasic' => round($proratedBasic, 2),
            'regular_ot_hours' => $regularOT,
            'holiday_ot_hours' => $holidayOT,
            'regular_ot_amount' => round($regularOtAmount,2),
            'holiday_ot_amount' => round($holidayOtAmount,2),
            'total_ot_amount' => round($totalOtAmount,2),
            'ramadan_bonus' => round($ramadanBonus,2),
            'loan_recovery' => round($recoveryData,2),
            'total_allowances_mvr' => round($totalAllowance, 2),
            'allowances' => $allowanceDetails,
            'earned_salary' => round($earnedSalary, 2),
            'payment_mode' => $payment_mode,
            'payroll_start' => $payrollStart->format('d M Y'),


        ];
    }

    public function getLeaveBalance(Employee $employee, $resortId): array
    {
        $emp_grade = Common::getEmpGrade($employee->rank);

        $benefit_grids = DB::table('resort_benifit_grid as rbg')
            ->join('resort_benefit_grid_child as rbgc', 'rbg.id', '=', 'rbgc.benefit_grid_id')
            ->join('leave_categories as lc', 'lc.id', '=', 'rbgc.leave_cat_id')
            ->where('rbg.emp_grade', $emp_grade)
            ->where('rbgc.rank', $employee->rank)
            ->where('lc.resort_id', $resortId)
            ->select(
                'lc.id as leave_category_id',
                'lc.leave_type',
                'lc.color',
                'lc.resort_id',
                'lc.carry_forward',
                'lc.carry_max',
                'lc.earned_leave',
                'lc.earned_max',
                'lc.frequency',
                'rbgc.allocated_days'
            )
            ->get();

        $currentYearStart = Carbon::now()->startOfYear()->format('Y-m-d');
        $currentYearEnd = Carbon::now()->endOfYear()->format('Y-m-d');

        $leaveUsage = DB::table('employees_leaves')
            ->select('leave_category_id', DB::raw('SUM(total_days) as used_days'))
            ->where('emp_id', $employee->emp_id)
            ->where('status', 'Approved')
            ->where(function ($query) use ($currentYearStart, $currentYearEnd) {
                $query->whereBetween('from_date', [$currentYearStart, $currentYearEnd])
                    ->orWhereBetween('to_date', [$currentYearStart, $currentYearEnd]);
            })
            ->groupBy('leave_category_id')
            ->get()
            ->keyBy('leave_category_id');

        $leaveBalances = [];
        $totalLeaveDays = 0;

        foreach ($benefit_grids as $grid) {
            $usedDays = $leaveUsage->get($grid->leave_category_id)->used_days ?? 0;
            $available = $grid->allocated_days;

            if ($grid->carry_forward === 'yes') {
                $lastYear = Carbon::now()->subYear();
                $lastYearStart = $lastYear->startOfYear()->format('Y-m-d');
                $lastYearEnd = $lastYear->endOfYear()->format('Y-m-d');

                $lastYearUsed = DB::table('employees_leaves')
                    ->select(DB::raw('SUM(total_days) as used_days'))
                    ->where('emp_id', $employee->emp_id)
                    ->where('leave_category_id', $grid->leave_category_id)
                    ->where('status', 'Approved')
                    ->where(function ($query) use ($lastYearStart, $lastYearEnd) {
                        $query->whereBetween('from_date', [$lastYearStart, $lastYearEnd])
                            ->orWhereBetween('to_date', [$lastYearStart, $lastYearEnd]);
                    })
                    ->value('used_days') ?? 0;

                $unused = max($grid->allocated_days - $lastYearUsed, 0);
                $carryForward = min($unused, $grid->carry_max);
                $available += $carryForward;
            }

            if ($grid->earned_leave === 'yes') {
                $monthsElapsed = Carbon::now()->month;
                $earnedLeave = min($monthsElapsed * ($grid->earned_max / 12), $grid->earned_max);
                $available += $earnedLeave;
            }

            $finalAvailable = max(0, $available - $usedDays);

            $leaveBalances[] = [
                'leave_category_id' => $grid->leave_category_id,
                'leave_type' => $grid->leave_type,
                'available_days' => $finalAvailable,
            ];

            $totalLeaveDays += $finalAvailable;
        }

        return [
            'total_days' => $totalLeaveDays,
            'details' => $leaveBalances
        ];
    }
}
