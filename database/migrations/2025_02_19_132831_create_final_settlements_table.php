<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinalSettlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('final_settlements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->decimal('pension', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('leave_balance', 10, 2)->nullable();
            $table->decimal('leave_encashment', 10, 2)->nullable();
            $table->decimal('loan_payment', 10, 2)->nullable();
            $table->decimal('basic_salary', 10, 2)->nullable();
            $table->decimal('service_charge', 10, 2)->nullable();
            $table->decimal('total_earnings', 10, 2)->nullable();
            $table->decimal('total_deductions', 10, 2)->nullable();
            $table->decimal('net_pay', 10, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->date('last_working_date')->nullable();
            $table->date('doc_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->enum('status', ['draft', 'review', 'finalized'])->default('draft');
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
        Schema::dropIfExists('final_settlements');
    }
}
