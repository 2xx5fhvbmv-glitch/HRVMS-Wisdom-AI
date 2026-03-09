<?php

/**
 * Standalone script to seed dummy attendance data.
 *
 * Usage on server:
 *   php scripts/seed_dummy_attendance.php
 *
 * Or via artisan:
 *   php artisan db:seed --class=DummyAttendanceSeeder
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ParentAttendace;
use App\Models\Employee;
use App\Models\PayrollConfig;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// ========== CONFIGURATION ==========
$resortId = 26;  // Change this to your resort ID
$periodsToFill = 3;
// ====================================

$config = PayrollConfig::where('resort_id', $resortId)->first();
$cutoffDay = $config ? $config->cutoff_day : 28;
$today = Carbon::now();

echo "Resort ID: {$resortId}\n";
echo "Cutoff Day: {$cutoffDay}\n";

// Dynamically generate periods
$periods = [];
for ($i = 1; $i <= $periodsToFill; $i++) {
    $start = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i);
    $end = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i - 1)->subDay();

    if ($end->greaterThan($today)) {
        $end = $today->copy();
    }

    $periods[] = ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
}

echo "Periods to fill:\n";
foreach ($periods as $p) {
    echo "  {$p['start']} to {$p['end']}\n";
}

$employees = Employee::where('resort_id', $resortId)
    ->whereIn('status', ['Active', 'Probationary', 'Resigned'])
    ->get();

if ($employees->isEmpty()) {
    echo "ERROR: No employees found for resort_id: {$resortId}\n";
    exit(1);
}

echo "Found {$employees->count()} employees\n";

$statuses = ['Present', 'Present', 'Present', 'Present', 'Present', 'Day Off'];
$count = 0;
$skipped = 0;

foreach ($periods as $period) {
    $dateRange = CarbonPeriod::create($period['start'], $period['end']);

    foreach ($employees as $employee) {
        foreach ($dateRange as $date) {
            $exists = ParentAttendace::where('resort_id', $resortId)
                ->where('Emp_id', $employee->id)
                ->where('date', $date->format('Y-m-d'))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $status = $statuses[array_rand($statuses)];
            $isPresent = $status === 'Present';

            $checkIn = $isPresent ? $date->copy()->setTime(rand(7, 9), rand(0, 59), 0) : null;
            $checkOut = $isPresent ? $date->copy()->setTime(rand(16, 18), rand(0, 59), 0) : null;
            $hoursWorked = ($checkIn && $checkOut) ? round($checkOut->diffInMinutes($checkIn) / 60, 2) : 0;

            $hasOT = $isPresent && rand(1, 10) === 1;
            $overtime = $hasOT ? rand(1, 3) : null;

            ParentAttendace::create([
                'resort_id'            => $resortId,
                'Emp_id'               => $employee->id,
                'Shift_id'             => 1,
                'date'                 => $date->format('Y-m-d'),
                'Status'               => $status,
                'CheckingTime'         => $checkIn ? $checkIn->format('H:i:s') : null,
                'CheckingOutTime'      => $checkOut ? $checkOut->format('H:i:s') : null,
                'DayWiseTotalHours'    => $hoursWorked,
                'OverTime'             => $overtime,
                'OTStatus'             => $hasOT ? 'Approved' : null,
                'CheckInCheckOut_Type' => 'System',
            ]);

            $count++;
        }
    }
}

echo "\nDone!\n";
echo "Created: {$count} records\n";
echo "Skipped (already existed): {$skipped} records\n";
