<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrivanceSubmissionModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grivance_submission_models', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedInteger('resort_id');
            $table->string('Grivance_id')->unique();
            $table->integer('Committee_id');

            $table->integer('Grivance_Cat_id');
            $table->integer('Grivance_offence_id');
            $table->integer('Employee_id');
            $table->string('date')->nullable();
            $table->longText('Grivance_description')->nullable();
            $table->dateTime('Grivance_date_time')->nullable();
            $table->string('location')->nullable();
            $table->integer('witness_id')->nullable();
            $table->longText('Grivance_Eexplination_description')->nullable();
            $table->text('Attachements')->nullable();

            $table->enum('Grivance_Submission_Type',['Yes','No','NotApplicable'])->default('No');
            $table->enum('status', ['pending', 'in_review', 'resolved', 'rejected'])->default('pending');
            $table->enum('Priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->enum('Assigned', ['Yes', 'No','DeliverToHr'])->default('No');
            $table->enum('SentToGM', ['Yes', 'No'])->default('No');
            
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable(); 
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
        Schema::dropIfExists('grivance_submission_models');
    }
}
