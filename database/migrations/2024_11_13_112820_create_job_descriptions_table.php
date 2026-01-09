<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobDescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('Resort_id');
            $table->unsignedInteger( 'Division_id');
            $table->unsignedInteger( 'Department_id');
            $table->unsignedInteger( 'Position_id')->nullable();
            $table->unsignedInteger( 'Section_id')->nullable();

            $table->text('jobdescription')->nullable();
            $table->string('slug',100)->nullable();
            $table->enum('compliance',['Approved','Rejected'])->default('Rejected');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('Resort_id')->references('id')->on('resorts');
            $table->foreign( 'Division_id')->references('id')->on('resort_divisions');
            $table->foreign( 'Department_id')->references('id')->on('resort_departments');
            $table->foreign( 'Position_id')->references('id')->on('resort_positions');
            $table->foreign( 'Section_id')->references('id')->on('resort_sections');
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
        Schema::dropIfExists('job_descriptions');
    }
}
