<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesExperianceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_experiance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('duration')->nullable();
            $table->string('location')->nullable();
            $table->string('reason_for_leaving')->nullable();
            $table->string('reference_name')->nullable();
            $table->string('reference_contact')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('employees_experiance');
    }
}
