<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeInsuranceChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_insurance_children', function (Blueprint $table) 
        {
            $table->id();
            $table->unsignedBigInteger('employee_insurances_id');
            $table->string('insurance_company');
            $table->string('insurance_policy_number');
            $table->string('insurance_coverage');
            $table->date('insurance_start_date');
            $table->date('insurance_end_date');
            $table->string('insurance_file')->nullable();
            $table->foreign('employee_insurances_id')->references('id')->on('employee_insurances')->onDelete('cascade');
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
        Schema::dropIfExists('employee_insurance_children');
    }
}
