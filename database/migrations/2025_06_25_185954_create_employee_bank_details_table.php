<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeBankDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_type')->nullable();
            $table->string('IFSC_BIC')->nullable();
            $table->string('account_holder_name');
            $table->string('account_no')->nullable();
            $table->enum('currency',['USD','MVR'])->default('USD');
            $table->string('IBAN')->nullable();
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
        Schema::dropIfExists('employee_bank_details');
    }
}
