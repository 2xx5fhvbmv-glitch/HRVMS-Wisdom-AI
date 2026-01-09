<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningRequestsEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_requests_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('learning_request_id');
            $table->unsignedInteger('employee_id');
            $table->timestamps();

            $table->foreign('learning_request_id')->references('id')->on('learning_requests')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_requests_employees');
    }
}
