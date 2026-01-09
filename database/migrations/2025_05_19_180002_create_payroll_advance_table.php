<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollAdvanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_advance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('resort_id');
            $table->string('request_type');
            $table->decimal('request_amount', 12, 2);
            $table->date('request_date');
            $table->string('pourpose');
            $table->json('attechments')->nullable();
            $table->enum('hr_status', ['Pending', 'Approved', 'Rejected', 'Hold'])->default('Pending');
            $table->enum('finance_status', ['Pending', 'Approved', 'Rejected', 'Hold'])->default('Pending');
            $table->enum('gm_status', ['Pending', 'Approved', 'Rejected', 'Hold'])->default('Pending');
            $table->string('remarks')->nullable();
            $table->unsignedInteger('hr_approved_by')->nullable();
            $table->unsignedInteger('finance_approved_by')->nullable();
            $table->unsignedInteger('gm_approved_by')->nullable();
            $table->string('reject_reason')->nullable();
            $table->date('action_date')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('modified_by')->nullable();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            
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
        Schema::dropIfExists('payroll_advance');
    }
}
