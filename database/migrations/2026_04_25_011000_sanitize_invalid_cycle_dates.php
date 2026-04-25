<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several PerformanceCycle rows were saved with Self/Manager activity dates as
 * the literal string "0000-00-00" (no date picker default) which Carbon parses
 * as 30 Nov -0001 — making the review window banner read "Closed on: 30 Nov -0001"
 * for users.
 *
 * Replace those bogus values with NULL so the controller's window-status logic
 * treats them as "no window configured" and lets the form render.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('performance_cycles')) return;

        $bogus = ['0000-00-00', '0000-00-00 00:00:00'];
        $columns = [
            'Self_Activity_Start_Date',
            'Self_Activity_End_Date',
            'Manager_Activity_Start_Date',
            'Manager_Activity_End_Date',
        ];

        foreach ($columns as $col) {
            if (!Schema::hasColumn('performance_cycles', $col)) continue;
            DB::table('performance_cycles')
                ->whereIn($col, $bogus)
                ->update([$col => null]);
        }
    }

    public function down(): void
    {
        // No-op.
    }
};
