<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollTimeAndAttandanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_time_and_attandance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedInteger('employee_id');
            $table->string('Emp_id');
            $table->integer('present_days');
            $table->integer('absent_days');
            $table->string('leave_types')->nullable();
            $table->float('regular_ot_hours')->nullable();
            $table->float('holiday_ot_hours')->nullable();
            $table->float('total_ot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('payroll_id')->references('id')->on('payroll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_time_and_attandance');
    }
}
