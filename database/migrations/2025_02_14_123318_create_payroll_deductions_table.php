<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedInteger('employee_id');
            $table->string('Emp_id');
            $table->decimal('attendance_deduction', 10, 2)->default(0);
            $table->decimal('city_ledger', 10, 2)->default(0);
            $table->decimal('staff_shop', 10, 2)->default(0);
            $table->decimal('pension', 10, 2)->default(0);
            $table->decimal('ewt', 10, 2)->default(0);
            $table->decimal('other', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
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
        Schema::dropIfExists('payroll_deductions');
    }
}
