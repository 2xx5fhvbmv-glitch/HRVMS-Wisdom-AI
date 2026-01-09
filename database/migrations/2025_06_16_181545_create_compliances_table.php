<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompliancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     *  
     * @return void
     */

    public function up()
    {
        Schema::create('compliances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->string('module_name');
            $table->string('compliance_breached_name');
            $table->text('description')->nullable();
            $table->dateTime('reported_on')->nullable();
            $table->enum('status', ['Breached', 'Resolved'])->default('Breached');
            $table->unsignedInteger('assigned_to')->nullable(); 

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('employees')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('compliances');
    }
}
