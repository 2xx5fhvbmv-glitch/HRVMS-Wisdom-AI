<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Some quota-slot schedules were created with 0 amounts (a manual-entry path that
 * didn't pull the fee from config). The slot fee should be the resort's
 * configured Quota Slot amount split across the employee's schedule months
 * (months 1..n-1 = floor(total/n), last month absorbs the remainder so the sum
 * equals the configured total). This corrects employees whose slot rows sum to 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        $resortIds = DB::table('quota_slot_renewals')->distinct()->pluck('resort_id');

        foreach ($resortIds as $resortId) {
            $total = DB::table('resort_budget_costs')
                ->where('resort_id', $resortId)
                ->whereIn('particulars', ['Quota Slot Payment', 'QUOTA SLOT PAYMENT', 'quota slot payment', 'Quota Slot Deposit', 'QUOTA SLOT DEPOSIT'])
                ->where('status', 'active')
                ->orderBy('updated_at', 'desc')
                ->value('amount');

            if ($total === null || (float) $total <= 0) {
                continue;
            }
            $total = (float) $total;

            // Employees in this resort whose slot rows sum to exactly 0.
            $empIds = DB::table('quota_slot_renewals')
                ->select('employee_id')
                ->where('resort_id', $resortId)
                ->groupBy('employee_id')
                ->havingRaw('COALESCE(SUM(Amt),0) = 0')
                ->pluck('employee_id');

            foreach ($empIds as $empId) {
                $rows = DB::table('quota_slot_renewals')
                    ->where('resort_id', $resortId)->where('employee_id', $empId)
                    ->orderBy('Due_Date', 'asc')->orderBy('id', 'asc')
                    ->get(['id']);

                $n = $rows->count();
                if ($n === 0) {
                    continue;
                }
                $base = floor($total / $n);
                $last = $total - ($base * ($n - 1));

                foreach ($rows->values() as $idx => $row) {
                    $amt = ($idx === $n - 1) ? $last : $base;
                    DB::table('quota_slot_renewals')->where('id', $row->id)->update(['Amt' => $amt]);
                }
                Log::info("Slot fee fix: resort {$resortId} emp {$empId} -> {$n} months split of {$total}.");
            }
        }
    }

    public function down(): void
    {
        // Data correction — not reversible.
    }
};
