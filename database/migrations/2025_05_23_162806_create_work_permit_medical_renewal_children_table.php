<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkPermitMedicalRenewalChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_permit_medical_renewal_children', function (Blueprint $table) 
        {
            $table->id();
            $table->unsignedBigInteger('permit_medical_id');
            $table->string('Reference_Number')->nullable();
            $table->string('Cost');
            $table->string('Medical_Center_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('medical_file')->nullable();
            $table->foreign('permit_medical_id')->references('id')->on('work_permit_medical_renewals')->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('work_permit_medical_renewal_children');
    }
}
