<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * job_advertisements was architecturally one poster per resort
 * (updateOrCreate keyed only on Resort_id) — every vacancy card showed the
 * same shared poster because there was no way to tell posters apart per
 * vacancy. This column makes a poster optionally belong to a specific
 * vacancy; null keeps today's exact behavior (the resort-wide default,
 * used as a fallback when a vacancy has no poster of its own).
 *
 * vacancies.id is INT UNSIGNED (Schema::increments in
 * create_vacancies_table.php), not bigint — matching that type here so the
 * FK constraint is valid.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('job_advertisements', function (Blueprint $table) {
            $table->unsignedInteger('vacancy_id')->nullable()->after('Resort_id');
            $table->foreign('vacancy_id')->references('id')->on('vacancies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('job_advertisements', function (Blueprint $table) {
            $table->dropForeign(['vacancy_id']);
            $table->dropColumn('vacancy_id');
        });
    }
};
