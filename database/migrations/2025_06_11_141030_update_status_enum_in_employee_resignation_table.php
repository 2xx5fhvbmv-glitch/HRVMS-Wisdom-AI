<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInEmployeeResignationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            DB::statement("ALTER TABLE employee_resignation MODIFY hr_meeting_status ENUM('Not Scheduled','Scheduled','Completed','Employee Schedule Confirm') NOT NULL DEFAULT 'Not Scheduled'");
            DB::statement("ALTER TABLE employee_resignation MODIFY hod_meeting_status ENUM('Not Scheduled','Scheduled','Completed','Employee Schedule Confirm') NOT NULL DEFAULT 'Not Scheduled'");
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
            DB::statement("ALTER TABLE employee_resignation MODIFY hr_meeting_status ENUM('Not Scheduled','Scheduled','Completed') NOT NULL DEFAULT 'Not Scheduled'");
            DB::statement("ALTER TABLE employee_resignation MODIFY hod_meeting_status ENUM('Not Scheduled','Scheduled','Completed') NOT NULL DEFAULT 'Not Scheduled'");
        });
    }
}
