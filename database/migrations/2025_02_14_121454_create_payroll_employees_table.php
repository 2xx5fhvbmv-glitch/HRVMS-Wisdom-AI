<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedInteger('employee_id');
            $table->string('Emp_id');
            $table->unsignedInteger('position');
            $table->unsignedInteger('department');
            $table->unsignedInteger('section')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payroll');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('position')->references('id')->on('resort_positions');
            $table->foreign('department')->references('id')->on('resort_departments');
            $table->foreign('section')->references('id')->on('resort_sections');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_employees');
    }
}
