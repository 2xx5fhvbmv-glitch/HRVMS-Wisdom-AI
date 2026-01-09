<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHodStatusColumnToEmployeeResignationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->enum('hod_status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('status');
            $table->enum('hod_meeting_status', ['Not Scheduled', 'Scheduled', 'Completed'])->default('Not Scheduled')->after('hod_status');
            $table->text('hod_comments')->nullable()->after('hod_meeting_status');
            $table->unsignedInteger('hod_id')->nullable()->after('hod_comments');

            $table->enum('hr_status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('hod_id');
            $table->enum('hr_meeting_status', ['Not Scheduled', 'Scheduled', 'Completed'])->default('Not Scheduled')->after('hr_status');
            $table->text('hr_comments')->nullable()->after('hr_meeting_status');
            $table->unsignedBigInteger('hr_id')->nullable()->after('hr_comments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn(['hod_status', 'hod_meeting_status', 'hod_comments', 'hod_id', 'hr_status', 'hr_meeting_status', 'hr_comments', 'hr_id']);
        });
    }
}
