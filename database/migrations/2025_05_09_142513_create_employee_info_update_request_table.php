<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeInfoUpdateRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_info_update_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->string('title');
            $table->longText('info_payload');
            $table->enum('status',['Approved','Rejected','Pending'])->default('Pending');
            $table->text('reject_reason')->nullable();
            $table->integer('created_by');
            $table->integer('modified_by');
            
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('employee_id')->references('id')->on('employees');
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
        Schema::dropIfExists('employee_info_update_request');
    }
}
