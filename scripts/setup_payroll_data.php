<?php

/**
 * Complete Payroll Data Setup Script
 *
 * This script does everything needed to set up payroll data on the server:
 * 1. Seeds dummy attendance records for last 3 payroll periods
 * 2. Fixes old attendance records with missing Status/CheckIn/CheckOut
 * 3. Sets varied working days per employee (realistic distribution)
 *
 * Usage:
 *   php scripts/setup_payroll_data.php
 *
 * Change $resortId below to match your server's resort ID.
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
$resortId = 26;  // ⚠️ CHANGE THIS to your server's resort ID
$periodsToFill = 3; // Number of past payroll periods to fill
// ====================================

echo "========================================\n";
echo "  Payroll Data Setup Script\n";
echo "========================================\n\n";

// Auto-detect cutoff day
$config = PayrollConfig::where('resort_id', $resortId)->first();
$cutoffDay = $config ? $config->cutoff_day : 28;
$today = Carbon::now();

echo "Resort ID: {$resortId}\n";
echo "Cutoff Day: {$cutoffDay}\n";
echo "Today: {$today->format('Y-m-d')}\n\n";

// Get employees
$employees = Employee::where('resort_id', $resortId)
    ->whereIn('status', ['Active', 'Probationary', 'Resigned'])
    ->get();

if ($employees->isEmpty()) {
    echo "ERROR: No employees found for resort_id: {$resortId}\n";
    exit(1);
}

echo "Found {$employees->count()} employees\n\n";

// =============================================
// STEP 1: Seed dummy attendance records
// =============================================
echo "--- STEP 1: Seeding Attendance Records ---\n";

$periods = [];
for ($i = 1; $i <= $periodsToFill; $i++) {
    $start = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i);
    $end = Carbon::create($today->year, $today->month, $cutoffDay)->subMonths($i - 1)->subDay();

    if ($end->greaterThan($today)) {
        $end = $today->copy();
    }

    $periods[] = ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
}

foreach ($periods as $p) {
    echo "  Period: {$p['start']} to {$p['end']}\n";
}

$statuses = ['Present', 'Present', 'Present', 'Present', 'Present', 'Day Off'];
$seededCount = 0;
$skippedCount = 0;

foreach ($periods as $period) {
    $dateRange = CarbonPeriod::create($period['start'], $period['end']);

    foreach ($employees as $employee) {
        foreach ($dateRange as $date) {
            $exists = ParentAttendace::where('resort_id', $resortId)
                ->where('Emp_id', $employee->id)
                ->where('date', $date->format('Y-m-d'))
                ->exists();

            if ($exists) {
                $skippedCount++;
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

            $seededCount++;
        }
    }
}

echo "  Created: {$seededCount} records\n";
echo "  Skipped (already existed): {$skippedCount} records\n\n";

// =============================================
// STEP 2: Fix old broken attendance records
// =============================================
echo "--- STEP 2: Fixing Broken Attendance Records ---\n";

// Fix Day Off records with null CheckingTime/CheckingOutTime
$dayOffFixed = ParentAttendace::where('resort_id', $resortId)
    ->where('Status', 'Day Off')
    ->where(function ($q) {
        $q->whereNull('CheckingTime')->orWhereNull('CheckingOutTime');
    })
    ->update([
        'CheckingTime'      => '00:00:00',
        'CheckingOutTime'   => '00:00:00',
        'DayWiseTotalHours' => 0,
    ]);
echo "  Day Off records fixed: {$dayOffFixed}\n";

// Fix Present records with null CheckingOutTime
$presentFixed = ParentAttendace::where('resort_id', $resortId)
    ->where('Status', 'Present')
    ->whereNull('CheckingOutTime')
    ->update(['CheckingOutTime' => '17:00:00']);
echo "  Present records with null checkout fixed: {$presentFixed}\n";

// Fix Present records with null CheckingTime
$checkInFixed = ParentAttendace::where('resort_id', $resortId)
    ->where('Status', 'Present')
    ->whereNull('CheckingTime')
    ->update(['CheckingTime' => '09:00:00']);
echo "  Present records with null checkin fixed: {$checkInFixed}\n";

// Fix records with null or empty Status
$nullStatusFixed = ParentAttendace::where('resort_id', $resortId)
    ->where(function ($q) {
        $q->whereNull('Status')->orWhere('Status', '');
    })
    ->update([
        'Status'          => 'Present',
        'CheckingTime'    => '09:00:00',
        'CheckingOutTime' => '17:00:00',
    ]);
echo "  Null/empty status records fixed: {$nullStatusFixed}\n";

// Verify
$remaining = ParentAttendace::where('resort_id', $resortId)
    ->where(function ($q) {
        $q->whereNull('Status')
           ->orWhere('Status', '')
           ->orWhereNull('CheckingTime')
           ->orWhereNull('CheckingOutTime');
    })->count();
echo "  Remaining invalid records: {$remaining}\n\n";

// =============================================
// STEP 3: Set varied working days per employee
// =============================================
echo "--- STEP 3: Setting Varied Working Days ---\n";

$targetPresentDays = [16, 20, 16, 18, 10, 22, 19, 15, 21, 17, 20, 18, 16, 22, 19, 15, 21, 17];

// Apply to the most recent complete payroll period
$recentPeriod = $periods[0]; // Most recent period
echo "  Adjusting period: {$recentPeriod['start']} to {$recentPeriod['end']}\n";

foreach ($employees as $idx => $emp) {
    $target = $targetPresentDays[$idx % count($targetPresentDays)];

    $presentRecords = ParentAttendace::where('resort_id', $resortId)
        ->where('Emp_id', $emp->id)
        ->whereBetween('date', [$recentPeriod['start'], $recentPeriod['end']])
        ->where('Status', 'Present')
        ->get();

    $currentPresent = $presentRecords->count();
    $toConvert = $currentPresent - $target;

    if ($toConvert > 0) {
        $idsToConvert = $presentRecords->shuffle()->take($toConvert)->pluck('id');
        ParentAttendace::whereIn('id', $idsToConvert)->update([
            'Status'            => 'Day Off',
            'CheckingTime'      => '00:00:00',
            'CheckingOutTime'   => '00:00:00',
            'DayWiseTotalHours' => 0,
            'OverTime'          => null,
            'OTStatus'          => null,
        ]);
    }

    $finalPresent = ParentAttendace::where('resort_id', $resortId)
        ->where('Emp_id', $emp->id)
        ->whereBetween('date', [$recentPeriod['start'], $recentPeriod['end']])
        ->where('Status', 'Present')
        ->count();

    echo "  {$emp->Emp_id}: {$finalPresent} working days (target: {$target})\n";
}

echo "\n========================================\n";
echo "  Setup Complete!\n";
echo "========================================\n";
