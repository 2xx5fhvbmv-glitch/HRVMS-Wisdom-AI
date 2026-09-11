<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSickStatusToAttendanceEnums extends Migration
{
    /**
     * Add 'Sick' to the attendance status vocabulary — Casual/Intern's
     * manager-marked daily status (Present/Absent/Sick/DayOff/leave) per
     * the Casual & Intern support plan. 'On Leave' is NOT added — the
     * existing FullDayLeave value already covers that case, reused rather
     * than duplicated. parent_attendaces is the real day-to-day attendance
     * record; duty_roster_entries is the schedule it's created from and
     * shares the same enum shape.
     */
    public function up()
    {
        DB::statement("ALTER TABLE parent_attendaces MODIFY Status ENUM('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave','Sick') NOT NULL");
        DB::statement("ALTER TABLE duty_roster_entries MODIFY Status ENUM('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave','Sick') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE parent_attendaces MODIFY Status ENUM('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave') NOT NULL");
        DB::statement("ALTER TABLE duty_roster_entries MODIFY Status ENUM('On-Time','Late','Absent','Present','DayOff','ShortLeave','HalfDayLeave','FullDayLeave') NOT NULL");
    }
}
