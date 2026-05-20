<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seed a "Probation Failure" reason row in employee_resignation_reasons for
 * every resort, so the failProbation() flow can create an EmployeeResignation
 * record (which requires a NOT-NULL reason FK) and the employee shows up on
 * the Exit Clearance page alongside voluntary resignations.
 *
 * Idempotent — skips resorts that already have a row whose reason text is
 * "Probation Failure".
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();
        $resortIds = DB::table('resorts')->pluck('id');

        foreach ($resortIds as $resortId) {
            $exists = DB::table('employee_resignation_reasons')
                ->where('resort_id', $resortId)
                ->where('reason', 'Probation Failure')
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('employee_resignation_reasons')->insert([
                'resort_id'   => $resortId,
                'reason'      => 'Probation Failure',
                'status'      => 'Active',
                'created_by'  => 0,
                'modified_by' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Leave seeded rows in place — removing them would orphan any
        // EmployeeResignation records created via failProbation().
    }
};
