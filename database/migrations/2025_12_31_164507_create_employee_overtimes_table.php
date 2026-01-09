<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeOvertimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_overtimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('Emp_id');
            $table->unsignedBigInteger('Shift_id');
            $table->unsignedInteger('roster_id')->nullable();
            $table->unsignedInteger('parent_attendance_id')->nullable();
            $table->date('date');
            $table->string('start_time'); // Overtime start time (HH:MM format)
            $table->string('end_time'); // Overtime end time (HH:MM format)
            $table->string('total_time'); // Total overtime duration (HH:MM format)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->enum('overtime_type', ['before_shift', 'after_shift', 'split'])->nullable(); // Type of overtime
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('Emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('Shift_id')->references('id')->on('shift_settings')->onDelete('cascade');
            $table->foreign('roster_id')->references('id')->on('duty_rosters')->onDelete('set null');
            $table->foreign('parent_attendance_id')->references('id')->on('parent_attendaces')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('resort_admins')->onDelete('set null');
            
            // Index for faster queries
            $table->index(['Emp_id', 'date']);
            $table->index(['resort_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_overtimes');
    }
}
