<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentRequestChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_request_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Requested_Id')->nullable();
            $table->unsignedInteger('Employee_id');
            $table->date('WorkPermitDate')->nullable();
            $table->date('LastWorkPermitDate')->nullable();
            $table->decimal('WorkPermitAmt', 10, 2)->nullable();

            $table->date('QuotaslotDate')->nullable();
            $table->date('LastQuotaslotDate')->nullable();
            $table->decimal('QuotaslotAmt', 10, 2)->nullable();

            $table->date('InsuranceDate')->nullable();
            $table->date('LastInsuranceDate')->nullable();
            $table->decimal('InsurancePrimume', 10, 2)->nullable();

            $table->date('MedicalReportDate')->nullable();
            $table->date('LastMedicalReportDate')->nullable();
            $table->decimal('MedicalReportFees', 10, 2)->nullable();
            
            $table->date('VisaDate')->nullable();
            $table->date('LastVisaDate')->nullable();
            $table->decimal('VisaAmt', 10, 2)->nullable();


            $table->foreign('Employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Requested_Id')->references('id')->on('payment_requests')->onDelete('cascade');
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
        Schema::dropIfExists('payment_request_children');
    }
}
