<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeResignationWithdrawalConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_resignation_withdrawal_configuration', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->integer('enable_resignation_withdrawal')->default(0)->comment('0=Disable,1=Enable');
            $table->integer('required_resignation_withdrawal_reason')->default(0)->comment('0=Disable,1=Enable');
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->foreign('resort_id')->references('id')->on('resorts');

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
        Schema::dropIfExists('employee_resignation_withdrawal_configuration');
    }
}
