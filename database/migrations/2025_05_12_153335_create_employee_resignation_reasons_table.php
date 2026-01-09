<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeResignationReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_resignation_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->text('reason');
            $table->enum('status',['Inactive','Active'])->default('Active');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->integer('created_by');
            $table->integer('modified_by');
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
        Schema::dropIfExists('employee_resignation_reasons');
    }
}
