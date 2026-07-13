<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTravelQuotasTable extends Migration
{
    public function up()
    {
        Schema::create('employee_travel_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('transportation');
            $table->unsignedInteger('total_allowed')->default(0);
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('transportation')->references('id')->on('resort_transportations')->onDelete('cascade');
            $table->unique(['employee_id', 'transportation']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_travel_quotas');
    }
}
