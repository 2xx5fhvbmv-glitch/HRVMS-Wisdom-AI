<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrivanceInvestigationModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grivance_investigation_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Grievance_s_id');
            $table->integer('Committee_id');
            $table->string('inves_start_date')->nullable();
            $table->string('resolution_date')->nullable();
            $table->longText('investigation_files')->nullable();
            $table->timestamps();
            $table->foreign('Grievance_s_id')->references('id')->on('grivance_submission_models');  
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
        Schema::dropIfExists('grivance_investigation_models');
    }
}
