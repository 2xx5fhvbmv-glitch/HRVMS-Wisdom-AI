<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeNoticePeriodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_notice_period', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('title');
            $table->string('period')->comment('Days Ex-30')->nullable();
            $table->integer('immediate_release')->default(0);
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->integer('created_by');
            $table->integer('modified_by');
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
        Schema::dropIfExists('employee_notice_period');
    }
}
