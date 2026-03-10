<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $resortId = 26;
        $shiftId = 20; // Morning Shift

        // Employee code => integer ID mapping
        $empMap = [
            'DR-1'  => 170,
            'DR-2'  => 171,
            'DR-4'  => 173,
            'DR-5'  => 174,
            'DR-7'  => 176,
            'DR-8'  => 177,
            'DR-10' => 179,
            'DR-11' => 180,
            'DR-13' => 182,
            'DR-14' => 183,
            'DR-15' => 184,
            'DR-17' => 186,
            'DR-18' => 187,
            'DR-19' => 188,
            'DR-20' => 189,
        ];

        // Dates: Feb 25 to Mar 24, 2026
        $dates = [
            '2026-02-25', '2026-02-26', '2026-02-27', '2026-02-28',
            '2026-03-01', '2026-03-02', '2026-03-03', '2026-03-04',
            '2026-03-05', '2026-03-06', '2026-03-07', '2026-03-08',
            '2026-03-09', '2026-03-10', '2026-03-11', '2026-03-12',
            '2026-03-13', '2026-03-14', '2026-03-15', '2026-03-16',
            '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20',
            '2026-03-21', '2026-03-22', '2026-03-23', '2026-03-24',
        ];

        // Attendance data per employee (in order of dates above)
        // P=Present, F=DayOff, A=Absent, OT=Present+OT, FOT=DayOff+OT, HOT=DayOff+OT(Holiday), UL=Absent(Unpaid Leave), AL=Absent(Annual Leave)
        $attendance = [
            'DR-1'  => ['P','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-2'  => ['P','P','P','FOT','HOT','P','P','P','P','P','FOT','P','P','P','P','P','P','FOT','P','P','P','P','P','P','FOT','P','P','P'],
            'DR-4'  => ['P','P','P','F','HOT','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-5'  => ['P','P','OT','F','P','P','P','P','OT','P','F','P','P','P','P','P','P','F','P','P','OT','P','P','P','F','P','P','P'],
            'DR-7'  => ['P','P','P','FOT','P','P','P','P','P','P','F','P','P','P','P','P','P','FOT','P','P','P','P','P','P','F','P','P','P'],
            'DR-8'  => ['P','A','P','F','P','P','P','A','P','P','F','P','A','P','P','P','P','F','P','P','P','A','P','P','F','P','P','P'],
            'DR-10' => ['P','P','A','F','P','P','P','P','P','P','F','P','P','P','A','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-11' => ['P','P','P','F','P','P','P','P','A','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-13' => ['P','UL','UL','F','P','P','P','P','P','P','F','P','P','P','UL','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-14' => ['UL','UL','P','F','P','P','P','UL','P','P','F','P','P','P','UL','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-15' => ['UL','UL','UL','F','UL','UL','UL','UL','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-17' => ['AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-18' => ['P','P','P','F','AL','AL','AL','AL','AL','AL','F','AL','AL','AL','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-19' => ['P','OT','P','F','P','P','P','OT','P','P','FOT','P','P','P','P','OT','P','F','P','P','P','P','P','P','F','P','P','P'],
            'DR-20' => ['P','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P','P','P','P','F','P','P','P'],
        ];

        // First create/find a duty roster for this date range
        $rosterId = DB::table('duty_rosters')->insertGetId([
            'resort_id'  => $resortId,
            'Shift_id'   => $shiftId,
            'Emp_id'     => $empMap['DR-1'],
            'ShiftDate'  => '02/25/2026 - 03/24/2026',
            'Year'       => '2026',
            'created_by' => 259,
            'modified_by'=> 259,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $count = 0;
        $now = now();

        foreach ($attendance as $empCode => $dailyStatuses) {
            $empId = $empMap[$empCode];

            foreach ($dailyStatuses as $index => $rawStatus) {
                $date = $dates[$index];

                // Delete existing record for this employee+date so we replace it
                DB::table('parent_attendaces')
                    ->where('resort_id', $resortId)
                    ->where('Emp_id', $empId)
                    ->where('date', $date)
                    ->delete();

                // Map raw status to DB values
                $status = 'Present';
                $overtime = null;
                $otStatus = null;
                $checkIn = null;
                $checkOut = null;
                $totalHours = null;
                $note = null;

                switch ($rawStatus) {
                    case 'P':
                        $status = 'Present';
                        $checkIn = sprintf('%02d:%02d:00', rand(7, 8), rand(0, 59));
                        $checkOut = sprintf('%02d:%02d:00', rand(16, 17), rand(0, 59));
                        $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                        break;

                    case 'OT':
                        $status = 'Present';
                        $checkIn = sprintf('%02d:%02d:00', rand(7, 8), rand(0, 59));
                        $checkOut = sprintf('%02d:%02d:00', rand(19, 21), rand(0, 59));
                        $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                        $overtime = rand(2, 4);
                        $otStatus = 'Approved';
                        break;

                    case 'F':
                        $status = 'DayOff';
                        break;

                    case 'FOT':
                        $status = 'DayOff';
                        $overtime = rand(4, 8);
                        $otStatus = 'Approved';
                        $checkIn = sprintf('%02d:%02d:00', rand(8, 9), rand(0, 59));
                        $checkOut = sprintf('%02d:%02d:00', rand(16, 18), rand(0, 59));
                        $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                        $note = 'Day off overtime';
                        break;

                    case 'HOT':
                        $status = 'DayOff';
                        $overtime = rand(4, 8);
                        $otStatus = 'Approved';
                        $checkIn = sprintf('%02d:%02d:00', rand(8, 9), rand(0, 59));
                        $checkOut = sprintf('%02d:%02d:00', rand(16, 18), rand(0, 59));
                        $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                        $note = 'Holiday overtime';
                        break;

                    case 'A':
                        $status = 'Absent';
                        break;

                    case 'UL':
                        $status = 'Absent';
                        $note = 'Unpaid Leave';
                        break;

                    case 'AL':
                        $status = 'FullDayLeave';
                        $note = 'Annual Leave';
                        break;
                }

                DB::table('parent_attendaces')->insert([
                    'resort_id'          => $resortId,
                    'roster_id'          => $rosterId,
                    'Shift_id'           => $shiftId,
                    'Emp_id'             => $empId,
                    'date'               => $date,
                    'Status'             => $status,
                    'CheckingTime'       => $checkIn,
                    'CheckingOutTime'    => $checkOut,
                    'DayWiseTotalHours'  => $totalHours,
                    'OverTime'           => $overtime,
                    'OTStatus'           => $otStatus,
                    'note'               => $note,
                    'CheckInCheckOut_Type' => 'Manual',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                $count++;
            }
        }

        $this->command->info("Inserted {$count} attendance records for 15 employees (Feb 25 - Mar 24, 2026).");
    }
}
