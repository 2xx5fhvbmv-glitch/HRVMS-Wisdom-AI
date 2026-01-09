<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinaryInvestigationParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinary_investigation_parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Disciplinary_id'); 
            $table->string('Committee_member_id');
            $table->string('invesigation_date');
            $table->string('resolution_date');
            $table->string('investigation_file')->nullable();
            $table->string('outcome_type')->nullable();
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disciplinary_investigation_parents');
    }
}
