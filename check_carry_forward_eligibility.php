<?php
/**
 * One-time check: Count employees who have completed 1 year from joining date
 * (eligible to use carry forward leaves if that rule were enforced).
 * Run: php check_carry_forward_eligibility.php
 * Delete this file after use if desired.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use Carbon\Carbon;

$oneYearAgo = Carbon::now()->subYear()->format('Y-m-d');

$eligibleCount = Employee::whereNotNull('joining_date')
    ->where('joining_date', '!=', '0000-00-00')
    ->where('joining_date', '<=', $oneYearAgo)
    ->where('status', 'Active')
    ->count();

$totalActiveWithJoin = Employee::whereNotNull('joining_date')
    ->where('joining_date', '!=', '0000-00-00')
    ->where('status', 'Active')
    ->count();

$notYetOneYear = Employee::whereNotNull('joining_date')
    ->where('joining_date', '!=', '0000-00-00')
    ->where('joining_date', '>', $oneYearAgo)
    ->where('status', 'Active')
    ->count();

echo "=== Carry forward eligibility check (1 year from joining) ===\n";
echo "Active employees with valid joining date: {$totalActiveWithJoin}\n";
echo "Completed 1+ year (would be eligible for carry forward): {$eligibleCount}\n";
echo "Not yet 1 year: {$notYetOneYear}\n";
