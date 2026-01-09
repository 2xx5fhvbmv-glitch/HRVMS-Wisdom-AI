<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollRecoveryScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_recovery_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_advance_id');
            $table->unsignedInteger('employee_id');
            $table->date('repayment_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('interest', 12, 2)->nullable();
            $table->decimal('interest_amount', 12, 2)->nullable();
            $table->decimal('remaining_balance', 12, 2)->nullable();
            $table->enum('status', ['Pending', 'Paid'])->default('Pending');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('modified_by')->nullable();
            $table->foreign('payroll_advance_id')->references('id')->on('payroll_advance')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
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
        Schema::dropIfExists('payroll_recovery_schedule');
    }
}
