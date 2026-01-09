<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrivanceInvestigationChildModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grivance_investigation_child_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investigation_p_id');
            $table->string('follow_up_action');
            $table->longText('follow_up_description')->nullable();
            $table->string('investigation_stage')->nullable();
            $table->longText('Grivance_Eexplination_description')->nullable();
            $table->string('resolution_date')->nullable();
            $table->longText('inves_find_recommendations')->nullable();
            $table->longText('resolution_note')->nullable();
            $table->integer('Committee_member_id')->nullable();
            $table->timestamps();
            $table->foreign('investigation_p_id')->references('id')->on('grivance_investigation_models');  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grivance_investigation_child_models');
    }
}
