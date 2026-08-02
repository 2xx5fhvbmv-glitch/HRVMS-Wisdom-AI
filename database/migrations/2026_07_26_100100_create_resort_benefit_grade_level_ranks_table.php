<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortBenefitGradeLevelRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // A rank belongs to at most one active custom grade tag per resort at
        // a time (unique on resort_id+rank) — this is what lets
        // BenifitGridController derive its rank array from resort config
        // instead of a hardcoded switch, without changing anything about how
        // downstream benefit/payroll code keys off rank.
        Schema::create('resort_benefit_grade_level_ranks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('resort_id');
            $table->unsignedInteger('grade_level_id');
            $table->integer('rank');
            $table->timestamps();

            $table->foreign('grade_level_id')->references('id')->on('resort_benefit_grade_levels')->onDelete('cascade');
            $table->unique(['resort_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_benefit_grade_level_ranks');
    }
}
