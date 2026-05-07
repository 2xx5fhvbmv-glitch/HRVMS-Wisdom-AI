<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the "Disciplinary List" sidebar nav item to "Disciplinary".
 * Keyed by internal_route so the migration is idempotent and not coupled
 * to a hardcoded id (the ResortModulePagesSeeder seeded id may differ
 * between environments).
 */
return new class extends Migration {
    public function up()
    {
        DB::table('module_pages')
            ->where('internal_route', 'GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex')
            ->where('page_name', 'Disciplinary List')
            ->update(['page_name' => 'Disciplinary']);
    }

    public function down()
    {
        DB::table('module_pages')
            ->where('internal_route', 'GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex')
            ->where('page_name', 'Disciplinary')
            ->update(['page_name' => 'Disciplinary List']);
    }
};
