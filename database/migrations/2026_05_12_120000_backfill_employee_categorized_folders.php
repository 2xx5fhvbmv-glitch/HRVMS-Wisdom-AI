<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfill the per-employee "categorized" folder in filemangement_systems
 * that the file-management module otherwise creates lazily (first time an
 * employee uploads a leave attachment). Without this row, privileged users
 * on the File Management page see only the handful of employees who have
 * ever uploaded a document — instead of every active employee.
 *
 * The folder shape mirrors the on-demand insert in
 * App\Http\Controllers\API\LeaveController so anything that looks the
 * folder up by (resort_id, Folder_Name=Emp_id, Folder_Type='categorized')
 * keeps working unchanged.
 *
 * Idempotent: re-running skips employees whose folder already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('employees')
            ->whereNull('deleted_at')
            ->where('status', 'Active')
            ->whereNotNull('Emp_id')
            ->where('Emp_id', '!=', '')
            ->whereNotNull('resort_id')
            ->orderBy('id')
            ->chunkById(500, function ($employees) use ($now) {
                $rows = [];

                foreach ($employees as $emp) {
                    $exists = DB::table('filemangement_systems')
                        ->where('resort_id', $emp->resort_id)
                        ->where('Folder_Name', $emp->Emp_id)
                        ->where('Folder_Type', 'categorized')
                        ->where('UnderON', 0)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $rows[] = [
                        'resort_id'        => $emp->resort_id,
                        'Folder_unique_id' => Str::random(10),
                        'UnderON'          => 0,
                        'Folder_Name'      => $emp->Emp_id,
                        'Folder_Type'      => 'categorized',
                        'created_by'       => null,
                        'modified_by'      => null,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('filemangement_systems')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Intentional no-op. The forward migration only inserts rows the
        // application would have created on demand anyway; tearing them
        // back out could orphan child folders / files that were added
        // afterwards. Leave them in place on rollback.
    }
};
