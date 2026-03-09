<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentAttendace;
use App\Models\Employee;
use App\Models\PayrollConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DummyAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $resortId = 26; // Change this for your resort

        // Auto-detect cutoff day
        $config = PayrollConfig::where('resort_id', $resortId)->first();
        $cutoffDay = $config ? $config->cutoff_day : 28;

        $today = Carbon::now();

        // Dynamically generate periods: last 3 payroll periods up to today
        $periods = [];
        for ($i = 1; $i <= 3; $i++) {
            $start = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i);
            $end = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i - 1)->subDay();

            // Don't go beyond today for the most recent period
            if ($end->greaterThan($today)) {
                $end = $today->copy();
            }

            $periods[] = ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
        }

        $employees = Employee::where('resort_id', $resortId)
            ->whereIn('status', ['Active', 'Probationary', 'Resigned'])
            ->get();

        if ($employees->isEmpty()) {
            $this->command->error('No employees found for resort_id: ' . $resortId);
            return;
        }

        $this->command->info("Cutoff Day: {$cutoffDay}");
        foreach ($periods as $p) {
            $this->command->info("Period: {$p['start']} to {$p['end']}");
        }

        $statuses = ['Present', 'Present', 'Present', 'Present', 'Present', 'Day Off'];
        $count = 0;

        foreach ($periods as $period) {
            $dateRange = CarbonPeriod::create($period['start'], $period['end']);

            foreach ($employees as $employee) {
                foreach ($dateRange as $date) {
                    $exists = ParentAttendace::where('resort_id', $resortId)
                        ->where('Emp_id', $employee->id)
                        ->where('date', $date->format('Y-m-d'))
                        ->exists();

                    if ($exists) continue;

                    $status = $statuses[array_rand($statuses)];
                    $isPresent = $status === 'Present';

                    $checkIn = $isPresent ? $date->copy()->setTime(rand(7, 9), rand(0, 59), 0) : null;
                    $checkOut = $isPresent ? $date->copy()->setTime(rand(16, 18), rand(0, 59), 0) : null;
                    $hoursWorked = ($checkIn && $checkOut) ? round($checkOut->diffInMinutes($checkIn) / 60, 2) : 0;

                    $hasOT = $isPresent && rand(1, 10) === 1;
                    $overtime = $hasOT ? rand(1, 3) : null;

                    ParentAttendace::create([
                        'resort_id'          => $resortId,
                        'Emp_id'             => $employee->id,
                        'Shift_id'           => 1,
                        'date'               => $date->format('Y-m-d'),
                        'Status'             => $status,
                        'CheckingTime'       => $checkIn ? $checkIn->format('H:i:s') : null,
                        'CheckingOutTime'    => $checkOut ? $checkOut->format('H:i:s') : null,
                        'DayWiseTotalHours'  => $hoursWorked,
                        'OverTime'           => $overtime,
                        'OTStatus'           => $hasOT ? 'Approved' : null,
                        'CheckInCheckOut_Type' => 'System',
                    ]);

                    $count++;
                }
            }
        }

        $this->command->info("Created {$count} dummy attendance records for {$employees->count()} employees.");
    }
}
