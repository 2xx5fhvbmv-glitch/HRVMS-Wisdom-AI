<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Employee 183 (Deepika Iyer, DR-14, resort 26) has Dept_id=0 — not a
     * real department, just falsy data that made her show up in a stray
     * "No Department" group on the duty roster and crashed the Salary
     * Advance list's department->name lookup. Confirmed with the resort
     * owner she belongs to F and B Service (dept id 80, the same
     * department her fellow waitresses are already in).
     */
    public function up(): void
    {
        DB::table('employees')
            ->where('id', 183)
            ->where('resort_id', 26)
            ->where('Dept_id', 0)
            ->update(['Dept_id' => 80]);
    }

    public function down(): void
    {
        DB::table('employees')
            ->where('id', 183)
            ->where('resort_id', 26)
            ->where('Dept_id', 80)
            ->update(['Dept_id' => 0]);
    }
};
