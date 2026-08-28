<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            // Lets two positions sharing the same Rank sit on different
            // Benefit Grids (e.g. Finance Director and Finance Manager both
            // rank HOD, but different grades) — previously grade assignment
            // only ever happened at the Rank level via
            // resort_benefit_grade_level_ranks, so any position sharing a
            // rank was stuck sharing the same default grade too. Same
            // "still valid/active" resolution rule as employees.benefit_grid_level.
            $table->integer('benefit_grid_level')->nullable()->after('Rank');
        });
    }

    public function down()
    {
        Schema::table('resort_positions', function (Blueprint $table) {
            $table->dropColumn('benefit_grid_level');
        });
    }
};
