<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleSalaryIncrementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people_salary_increment', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('resort_id');
            $table->string('increment_type');
            $table->decimal('previous_salary', 12, 2);
            $table->decimal('new_salary', 12, 2);
            $table->decimal('increment_amount', 12, 2);
            $table->enum('pay_increase_type',['Percentage', 'Fixed'])->default('Percentage');
            $table->decimal('value', 5, 2);
            $table->date('effective_date');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Hold','Change-Request'])->default('Pending');
            $table->string('remarks')->nullable();
            $table->date('due_date')->comment('date which is hold this increment')->nullable();
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->foreign('employee_id')->references('id')->on('employees');
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
        Schema::dropIfExists('people_salary_increment');
    }
}
