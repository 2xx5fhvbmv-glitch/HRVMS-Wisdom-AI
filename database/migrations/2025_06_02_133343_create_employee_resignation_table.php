<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeResignationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_resignation', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('reason');
            $table->date('resignation_date');
            $table->date('last_working_day')->nullable();
            $table->enum('immediate_release', ['Yes', 'No'])->default('No'); // Yes, No
            $table->text('comments')->nullable();
            $table->text('resignation_letter')->nullable();
            $table->enum('status',['Pending','Approved','Rejected','On Hold'])->default('Pending'); // Pending, Approved, Rejected
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('reason')->references('id')->on('employee_resignation_reasons')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_resignation');
    }
}
