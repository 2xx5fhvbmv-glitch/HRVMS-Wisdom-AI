<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollAdvanceGuarantorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_advance_guarantor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_advance_id');
            $table->unsignedInteger('guarantor_id')->comment('employee_id');
            $table->string('guarantor_name');
            $table->string('guarantor_position');
            $table->string('guarantor_department');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Hold'])->default('Pending');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('modified_by')->nullable();
            $table->foreign('payroll_advance_id')->references('id')->on('payroll_advance')->onDelete('cascade');
            $table->foreign('guarantor_id')->references('id')->on('employees')->onDelete('cascade');
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
        Schema::dropIfExists('payroll_advance_guarantor');
    }
}
