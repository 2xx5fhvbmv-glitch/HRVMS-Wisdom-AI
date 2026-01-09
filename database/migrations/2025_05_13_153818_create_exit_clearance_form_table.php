<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExitClearanceFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exit_clearance_form', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('form_name');
            $table->unsignedInteger('department_id');
            $table->json('form_structure');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('department_id')->references('id')->on('resort_departments');
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
        Schema::dropIfExists('exit_clearance_form');
    }
}
