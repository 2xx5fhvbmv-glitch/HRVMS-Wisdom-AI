<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrivanceSubmissionWitnessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grivance_submission_witnesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('G_S_Parent_id');
            $table->integer('Witness_id');
            $table->enum('Wintness_Status',['Active','In-Active'])->default('Active');
            $table->foreign('G_S_Parent_id')->references('id')->on('grivance_submission_models');  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grivance_submission_witnesses');
    }
}
