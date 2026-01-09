<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollAttendanceActivityLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_attendance_activity_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id')->nullable();
            $table->unsignedInteger('payroll_id')->nullable();
            $table->unsignedInteger('user_id')->nullable()->comment('User who made the change');
            $table->unsignedInteger('employee_id')->comment('Employee whose data was changed');
            $table->string('field')->comment('Field that was updated');
            $table->text('old_value')->nullable()->comment('Previous value');
            $table->text('new_value')->nullable()->comment('Updated value');            
            // ✅ Foreign keys
            $table->foreign('user_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('payroll_id')->references('id')->on('payroll');

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
        Schema::dropIfExists('payroll_attendance_activity_log');
    }
}
