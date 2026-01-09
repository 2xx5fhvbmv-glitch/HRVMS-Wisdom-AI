<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExitClearanceFormAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exit_clearance_form_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emp_resignation_id');
            $table->unsignedBigInteger('form_id');
            $table->enum('assigned_to_type',['employee','department'])->default('employee');
            $table->unsignedInteger('assigned_to_id')->nullable();
            $table->unsignedInteger('assigned_by')->nullable();
            $table->date('assigned_date')->nullable();
            $table->date('deadline_date')->nullable();
            $table->enum('status', ['Pending', 'Completed', 'Overdue'])->default('Pending');
            $table->date('completed_date')->nullable();
            $table->timestamps();

            $table->foreign('emp_resignation_id')->references('id')->on('employee_resignation')->onDelete('cascade');
            $table->foreign('form_id')->references('id')->on('exit_clearance_form')->onDelete('cascade');
            $table->foreign('assigned_to_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exit_clearance_form_assignments');
    }
}
