<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCasualInternVacanciesModulePage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Seeder-only edits don't reach an install that already ran it —
        // per CLAUDE.md's module_pages invariant, a live label/menu change
        // needs a migration too.
        if (\DB::table('module_pages')->where('internal_route', 'resort.ta.CasualInternVacancies')->exists()) {
            return;
        }
        \DB::table('module_pages')->insert([
            'page_name' => 'Casual & Interns',
            'status' => 'Active',
            'Module_Id' => 3,
            'internal_route' => 'resort.ta.CasualInternVacancies',
            'TypeOfPage' => 'InsideOfMenu',
            'type' => 'normal',
            'place_order' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::table('module_pages')->where('internal_route', 'resort.ta.CasualInternVacancies')->delete();
    }
}
