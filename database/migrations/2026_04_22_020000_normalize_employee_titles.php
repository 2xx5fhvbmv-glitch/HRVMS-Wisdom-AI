<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalizes employees.title based on gender.
 * Excel import always left title = "Mr" (column default), which is wrong for female staff.
 *
 *  - male    → Mr
 *  - female  → Miss  (safest default; UI lets HR switch to Mrs later)
 *  - unknown → leave untouched
 */
class NormalizeEmployeeTitles extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employees') || !Schema::hasColumn('employees', 'title')) {
            return;
        }

        // Set "Mr" for male employees who have no/empty title
        DB::table('employees')
            ->join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
            ->whereRaw('LOWER(resort_admins.gender) = ?', ['male'])
            ->where(function ($q) {
                $q->whereNull('employees.title')->orWhere('employees.title', '');
            })
            ->update(['employees.title' => 'Mr', 'employees.updated_at' => now()]);

        // Female employees currently stored as "Mr" → remap to "Miss"
        DB::table('employees')
            ->join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
            ->whereRaw('LOWER(resort_admins.gender) = ?', ['female'])
            ->where('employees.title', 'Mr')
            ->update(['employees.title' => 'Miss', 'employees.updated_at' => now()]);

        // Female employees with blank/null title → "Miss"
        DB::table('employees')
            ->join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
            ->whereRaw('LOWER(resort_admins.gender) = ?', ['female'])
            ->where(function ($q) {
                $q->whereNull('employees.title')->orWhere('employees.title', '');
            })
            ->update(['employees.title' => 'Miss', 'employees.updated_at' => now()]);
    }

    public function down()
    {
        // Irreversible — original blank/Mr-for-female state wasn't distinguishable from valid data.
    }
}
