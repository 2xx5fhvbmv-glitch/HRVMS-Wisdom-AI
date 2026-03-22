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
            'DR-21' => 192,
            'DR-22' => 191,
            'DR-23' => 194,
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

        // ── Attendance Sheet Data (P=Present, A=Absent, DO=DayOff, AL=Annual Leave, UL=Unpaid Leave) ──
        $attendance = [
            'DR-1'  => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-2'  => ['P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P','P'],
            'DR-4'  => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-5'  => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-7'  => ['P','P','P','P','P','P','P','P','P','P','DO','P','P','P','P','P','P','P','P','P','P','P','P','P','DO','P','P','P'],
            'DR-8'  => ['P','A','P','DO','P','P','P','A','P','P','DO','P','A','P','P','P','P','DO','P','P','P','A','P','P','DO','P','P','P'],
            'DR-10' => ['P','P','A','DO','P','P','P','P','P','P','DO','P','P','P','A','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-11' => ['P','P','P','DO','P','P','P','P','A','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-13' => ['P','UL','UL','DO','P','P','P','P','P','P','DO','P','P','P','UL','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-14' => ['UL','UL','P','DO','P','P','P','UL','P','P','DO','P','P','P','UL','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-15' => ['UL','UL','UL','DO','UL','UL','UL','UL','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-17' => ['AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','AL','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-18' => ['P','P','P','DO','AL','AL','AL','AL','AL','AL','DO','AL','AL','AL','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-19' => ['P','P','P','DO','P','P','P','P','P','P','P','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-20' => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-21' => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-22' => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
            'DR-23' => ['P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P','P','P','P','DO','P','P','P'],
        ];

        // ── OT Sheet Data (overtime hours per day, 0 = no overtime) ──
        $otHours = [
            'DR-1'  => [0,1,0,0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,1,0,0,0,0,0,0],
            'DR-2'  => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-4'  => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-5'  => [0,0,2,0,0,3,0,0,2,0,0,0,0,0,0,0,0,0,0,0,5,0,0,0,0,0,0,2],
            'DR-7'  => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-8'  => [0,0,0,0,0,0,0,3,0,0,0,0,0,0,0,0,0,0,3,0,0,0,3,0,0,0,0,0],
            'DR-10' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-11' => [0,0,0,1,0,0,0,0,0,0,0,1,0,0,0,0,1,0,0,0,0,0,1,0,0,0,1,0],
            'DR-13' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-14' => [0,0,0,0,0,0,1,0,0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,0,0,1,0,1],
            'DR-15' => [0,0,2,0,0,2,0,0,0,0,2,0,0,0,0,0,0,2,0,0,0,0,0,2,0,0,0,0],
            'DR-17' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2,0,0,0,0,0,0,2,0],
            'DR-18' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-19' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-20' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            'DR-21' => [2,0,0,0,2,0,0,0,0,0,0,0,2,0,0,0,0,0,0,0,2,0,0,0,0,0,2,0],
            'DR-22' => [0,0,0,0,0,0,0,0,0,0,0,2,0,0,0,0,0,0,2,0,0,0,0,0,0,2,0,0],
            'DR-23' => [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
        ];

        // ── Delete old seeded data for these employees in this date range ──
        $empIds = array_values($empMap);

        // Delete existing attendance records
        $existingIds = DB::table('parent_attendaces')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->whereBetween('date', ['2026-02-25', '2026-03-24'])
            ->pluck('id');

        if ($existingIds->isNotEmpty()) {
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $existingIds)->delete();
            DB::table('break_attendaces')->whereIn('Parent_attd_id', $existingIds)->delete();
            DB::table('parent_attendaces')->whereIn('id', $existingIds)->delete();
            $this->command->info("Deleted " . $existingIds->count() . " old attendance records.");
        }

        // Delete old employee_overtimes for these employees
        DB::table('employee_overtimes')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->whereBetween('date', ['2026-02-25', '2026-03-24'])
            ->delete();

        // Delete old duty roster entries for these employees
        DB::table('duty_roster_entries')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->whereBetween('date', ['2026-02-25', '2026-03-24'])
            ->delete();

        // Delete old duty rosters created by this seeder
        DB::table('duty_rosters')
            ->where('resort_id', $resortId)
            ->where('ShiftDate', '02/25/2026 - 03/24/2026')
            ->delete();

        // ── Create duty roster ──
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

        // ── Create duty roster entries ──
        $rosterEntries = [];
        foreach ($empMap as $empCode => $empId) {
            foreach ($dates as $index => $date) {
                $attStatus = $attendance[$empCode][$index];
                $rosterStatus = 'Working';
                if ($attStatus === 'DO') $rosterStatus = 'DayOff';
                elseif ($attStatus === 'AL') $rosterStatus = 'FullDayLeave';

                $rosterEntries[] = [
                    'resort_id'   => $resortId,
                    'roster_id'   => $rosterId,
                    'Emp_id'      => $empId,
                    'Shift_id'    => $shiftId,
                    'date'        => $date,
                    'Status'      => $rosterStatus,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }
        // Insert in chunks
        foreach (array_chunk($rosterEntries, 100) as $chunk) {
            DB::table('duty_roster_entries')->insert($chunk);
        }
        $this->command->info("Inserted " . count($rosterEntries) . " duty roster entries.");

        // ── Create attendance records ──
        $count = 0;
        $now = now();

        foreach ($attendance as $empCode => $dailyStatuses) {
            $empId = $empMap[$empCode];
            $empOt = $otHours[$empCode];

            foreach ($dailyStatuses as $index => $attStatus) {
                $date = $dates[$index];
                $ot = $empOt[$index];

                $status = 'Present';
                $overtime = null;
                $otStatusVal = null;
                $checkIn = null;
                $checkOut = null;
                $totalHours = null;
                $note = null;

                // Helper: convert decimal hours to H:MM format
                $toHMM = function($decimalHours) {
                    $h = intdiv((int)round($decimalHours * 60), 60);
                    $m = ((int)round($decimalHours * 60)) % 60;
                    return sprintf('%d:%02d', $h, $m);
                };

                $shiftHours = 8; // Morning Shift = 8 hours

                switch ($attStatus) {
                    case 'P':
                        $status = 'Present';
                        // Standard shift: 8 hours
                        $totalHours = sprintf('%d:00', $shiftHours);

                        if ($ot > 0) {
                            // Present with explicit OT from xlsx
                            $checkIn = sprintf('%02d:%02d:00', rand(7, 8), rand(0, 15));
                            $totalMinutes = ($shiftHours + $ot) * 60 + rand(0, 15);
                            $checkOutTs = strtotime($checkIn) + ($totalMinutes * 60);
                            $checkOut = date('H:i:00', $checkOutTs);
                            $overtime = sprintf('%02d:00', $ot);
                            $otStatusVal = 'Approved';
                        } else {
                            // Regular present — exactly 8h shift, minor variation in check-in/out
                            $checkInMinOffset = rand(0, 15); // 0-15 min after shift start
                            $checkIn = sprintf('%02d:%02d:00', 4 + intdiv($checkInMinOffset, 60), $checkInMinOffset % 60);
                            $checkOutTs = strtotime($checkIn) + ($shiftHours * 3600) + rand(0, 10) * 60;
                            $checkOut = date('H:i:00', $checkOutTs);
                        }
                        break;

                    case 'DO':
                        $status = 'DayOff';
                        if ($ot > 0) {
                            // Day Off with OT — only OT hours worked
                            $checkIn = sprintf('%02d:%02d:00', rand(8, 9), rand(0, 15));
                            $checkOutTs = strtotime($checkIn) + ($ot * 3600) + rand(0, 10) * 60;
                            $checkOut = date('H:i:00', $checkOutTs);
                            $totalHours = sprintf('%d:00', $ot);
                            $overtime = sprintf('%02d:00', $ot);
                            $otStatusVal = 'Approved';
                            $note = 'Day off overtime';
                        }
                        break;

                    case 'A':
                        $status = 'Absent';
                        if ($ot > 0) {
                            // Absent but did OT work
                            $checkIn = sprintf('%02d:%02d:00', rand(14, 16), rand(0, 59));
                            $checkOutHour = 16 + $ot;
                            $checkOut = sprintf('%02d:%02d:00', min($checkOutHour, 22), rand(0, 59));
                            $decimalHrs = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                            $totalHours = $toHMM($decimalHrs);
                            $overtime = sprintf('%02d:00', $ot);
                            $otStatusVal = 'Approved';
                            $note = 'Absent from shift, worked OT';
                        }
                        break;

                    case 'UL':
                        $status = 'Absent';
                        $note = 'Unpaid Leave';
                        if ($ot > 0) {
                            // UL but called in for OT
                            $checkIn = sprintf('%02d:%02d:00', rand(14, 16), rand(0, 59));
                            $checkOutHour = 16 + $ot;
                            $checkOut = sprintf('%02d:%02d:00', min($checkOutHour, 22), rand(0, 59));
                            $decimalHrs = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
                            $totalHours = $toHMM($decimalHrs);
                            $overtime = sprintf('%02d:00', $ot);
                            $otStatusVal = 'Approved';
                            $note = 'Unpaid Leave, worked OT';
                        }
                        break;

                    case 'AL':
                        $status = 'FullDayLeave';
                        $note = 'Annual Leave';
                        break;
                }

                DB::table('parent_attendaces')->insert([
                    'resort_id'            => $resortId,
                    'roster_id'            => $rosterId,
                    'Shift_id'             => $shiftId,
                    'Emp_id'               => $empId,
                    'date'                 => $date,
                    'Status'               => $status,
                    'CheckingTime'         => $checkIn,
                    'CheckingOutTime'      => $checkOut,
                    'DayWiseTotalHours'    => $totalHours,
                    'OverTime'             => $overtime,
                    'OTStatus'             => $otStatusVal,
                    'note'                 => $note,
                    'CheckInCheckOut_Type' => 'Manual',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                $count++;
            }
        }

        $this->command->info("Inserted {$count} attendance records for 15 employees (Feb 25 - Mar 24, 2026).");

        // ── Create employee_overtimes records from OT sheet data ──
        $otCount = 0;
        foreach ($otHours as $empCode => $dailyOt) {
            $empId = $empMap[$empCode];

            foreach ($dailyOt as $index => $otHrs) {
                if ($otHrs <= 0) continue;

                $date = $dates[$index];
                $attStatus = $attendance[$empCode][$index];

                // Determine overtime type (enum: before_shift, after_shift, split)
                $overtimeType = 'after_shift';

                // Generate realistic start/end times
                $startHour = ($attStatus === 'DO' || $attStatus === 'A' || $attStatus === 'UL')
                    ? rand(8, 10)
                    : 17; // after shift
                $startMin = rand(0, 30);
                $endHour = $startHour + $otHrs;
                $endMin = $startMin + rand(0, 29);
                if ($endMin >= 60) { $endHour++; $endMin -= 60; }

                $startTime = sprintf('%02d:%02d', $startHour, $startMin);
                $endTime = sprintf('%02d:%02d', min($endHour, 23), $endMin);
                $totalTime = sprintf('%02d:00', $otHrs);

                // Get parent_attendance_id for linking
                $parentId = DB::table('parent_attendaces')
                    ->where('resort_id', $resortId)
                    ->where('Emp_id', $empId)
                    ->where('date', $date)
                    ->value('id');

                DB::table('employee_overtimes')->insert([
                    'resort_id'             => $resortId,
                    'Emp_id'                => $empId,
                    'Shift_id'              => $shiftId,
                    'roster_id'             => $rosterId,
                    'parent_attendance_id'  => $parentId,
                    'date'                  => $date,
                    'start_time'            => $startTime,
                    'end_time'              => $endTime,
                    'total_time'            => $totalTime,
                    'status'                => 'approved',
                    'approved_by'           => 259,
                    'approved_at'           => $now,
                    'overtime_type'         => $overtimeType,
                    'notes'                 => 'Seeded from payroll xlsx',
                    'created_by'            => 259,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);

                $otCount++;
            }
        }

        $this->command->info("Inserted {$otCount} employee_overtimes records.");

        // ── Clean up ALL conflicting leave records ──
        // Delete any leave records where the employee has Present attendance (checked in) on those dates
        $deletedLeaves = 0;
        foreach ($empMap as $empCode => $empId) {
            // Get all dates where this employee has Present attendance with check-in
            $presentDatesForEmp = DB::table('parent_attendaces')
                ->where('Emp_id', $empId)
                ->where('resort_id', $resortId)
                ->whereIn('Status', ['Present', 'On-Time', 'Late'])
                ->whereNotNull('CheckingTime')
                ->where('CheckingTime', '!=', '')
                ->where('CheckingTime', '!=', '00:00:00')
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->toArray();

            if (empty($presentDatesForEmp)) continue;

            // Find leave records that overlap with any present date
            $conflicting = DB::table('employees_leaves')
                ->where('Emp_id', $empId)
                ->where('resort_id', $resortId)
                ->where(function ($q) use ($presentDatesForEmp) {
                    foreach ($presentDatesForEmp as $d) {
                        $q->orWhere(function ($qq) use ($d) {
                            $qq->where('from_date', '<=', $d)
                               ->where('to_date', '>=', $d);
                        });
                    }
                })
                ->pluck('id');

            if ($conflicting->isNotEmpty()) {
                DB::table('employees_leaves_status')->whereIn('leave_request_id', $conflicting)->delete();
                DB::table('employees_leaves')->whereIn('id', $conflicting)->delete();
                $deletedLeaves += $conflicting->count();
            }
        }
        $this->command->info("Deleted {$deletedLeaves} conflicting leave records (employee was present on those dates).");

        // ── Create leave records for AL (Annual Leave) and UL (Unpaid Leave) days ──
        $annualLeaveId = DB::table('leave_categories')
            ->where('resort_id', $resortId)
            ->where('leave_type', 'Annual Leave')
            ->value('id');

        $leaveCount = 0;
        foreach ($attendance as $empCode => $dailyStatuses) {
            $empId = $empMap[$empCode];

            // Group consecutive AL days into single leave records
            $alDates = [];
            foreach ($dailyStatuses as $index => $attStatus) {
                if ($attStatus === 'AL') {
                    $alDates[] = $dates[$index];
                }
            }

            if (empty($alDates)) continue;

            // Group into consecutive ranges
            $ranges = [];
            $rangeStart = $alDates[0];
            $rangePrev = $alDates[0];

            for ($i = 1; $i < count($alDates); $i++) {
                $prev = Carbon::parse($rangePrev);
                $curr = Carbon::parse($alDates[$i]);

                if ($curr->diffInDays($prev) == 1) {
                    $rangePrev = $alDates[$i];
                } else {
                    $ranges[] = ['from' => $rangeStart, 'to' => $rangePrev];
                    $rangeStart = $alDates[$i];
                    $rangePrev = $alDates[$i];
                }
            }
            $ranges[] = ['from' => $rangeStart, 'to' => $rangePrev];

            foreach ($ranges as $range) {
                $fromDate = Carbon::parse($range['from']);
                $toDate = Carbon::parse($range['to']);
                $totalDays = $fromDate->diffInDays($toDate) + 1;

                // Check no existing leave record for this range
                $exists = DB::table('employees_leaves')
                    ->where('Emp_id', $empId)
                    ->where('resort_id', $resortId)
                    ->where('from_date', $range['from'])
                    ->where('to_date', $range['to'])
                    ->exists();

                if (!$exists && $annualLeaveId) {
                    DB::table('employees_leaves')->insert([
                        'Emp_id'             => $empId,
                        'resort_id'          => $resortId,
                        'leave_category_id'  => $annualLeaveId,
                        'from_date'          => $range['from'],
                        'to_date'            => $range['to'],
                        'total_days'         => $totalDays,
                        'status'             => 'Approved',
                        'reason'             => 'Seeded Annual Leave',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                    $leaveCount++;
                }
            }
        }
        $this->command->info("Inserted {$leaveCount} leave records for AL days.");

        // ── Clean up junk records with empty Status (old garbage data) ──
        $junkIds = DB::table('parent_attendaces')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->where(function ($q) {
                $q->whereNull('Status')->orWhere('Status', '');
            })
            ->pluck('id');

        if ($junkIds->isNotEmpty()) {
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $junkIds)->delete();
            DB::table('break_attendaces')->whereIn('Parent_attd_id', $junkIds)->delete();
            DB::table('parent_attendaces')->whereIn('id', $junkIds)->delete();
            $this->command->info("Cleaned up {$junkIds->count()} junk attendance records (empty Status).");
        }

        // ── Also delete old-format attendance for DR-21, DR-22, DR-23 outside seeder range ──
        $extraEmpIds = [191, 192, 194];
        $oldExtraIds = DB::table('parent_attendaces')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $extraEmpIds)
            ->where(function ($q) {
                $q->where('date', '<', '2026-02-25')
                  ->orWhere('date', '>', '2026-03-24');
            })
            ->pluck('id');

        if ($oldExtraIds->isNotEmpty()) {
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $oldExtraIds)->delete();
            DB::table('break_attendaces')->whereIn('Parent_attd_id', $oldExtraIds)->delete();
            DB::table('parent_attendaces')->whereIn('id', $oldExtraIds)->delete();
            $this->command->info("Cleaned up {$oldExtraIds->count()} old-format records for DR-21/22/23.");
        }

        // ── Approve Elena's pending sick leave (seeder consistency) ──
        $elenaEmpId = 182;
        DB::table('employees_leaves')
            ->where('Emp_id', $elenaEmpId)
            ->where('resort_id', $resortId)
            ->where('status', 'Pending')
            ->whereBetween('from_date', ['2026-02-25', '2026-03-24'])
            ->update(['status' => 'Approved']);
        $this->command->info("Auto-approved Elena's pending leaves in seeder range.");

        // ── Clean up 1970 date junk records ──
        $ancientIds = DB::table('duty_roster_entries')
            ->where('resort_id', $resortId)
            ->where('date', '<', '2025-01-01')
            ->pluck('id');
        if ($ancientIds->isNotEmpty()) {
            DB::table('duty_roster_entries')->whereIn('id', $ancientIds)->delete();
            $this->command->info("Cleaned up {$ancientIds->count()} ancient roster entries.");
        }

        // ── Activate all benefit grids with service charge enabled ──
        DB::table('resort_benifit_grid')
            ->where('resort_id', $resortId)
            ->update(['service_charge' => 1, 'status' => 'active']);
        $this->command->info("Activated all benefit grids with service charge enabled.");

        // ── Seed shopkeeper payments for payroll period ──
        $shopkeeperId = DB::table('shopkeepers')->where('resort_id', $resortId)->value('id');
        if ($shopkeeperId) {
            $productId = DB::table('products')->where('shopkeeper_id', $shopkeeperId)->value('id');

            // Delete old seeded payments in this period
            DB::table('payments')
                ->where('shopkeeper_id', $shopkeeperId)
                ->whereBetween('purchased_date', ['2026-02-19', '2026-03-18'])
                ->whereIn('status', ['Consented', 'Partial Paid'])
                ->delete();

            if ($productId) {
                $shopPayments = [
                    ['emp_id' => 170, 'price' => 400, 'date' => '2026-03-01', 'status' => 'Consented', 'qty' => 2, 'cash_paid' => 0],
                    ['emp_id' => 170, 'price' => 200, 'date' => '2026-03-10', 'status' => 'Consented', 'qty' => 1, 'cash_paid' => 0],
                    ['emp_id' => 174, 'price' => 600, 'date' => '2026-02-25', 'status' => 'Consented', 'qty' => 3, 'cash_paid' => 0],
                    ['emp_id' => 177, 'price' => 200, 'date' => '2026-03-05', 'status' => 'Consented', 'qty' => 1, 'cash_paid' => 0],
                    ['emp_id' => 180, 'price' => 400, 'date' => '2026-03-08', 'status' => 'Partial Paid', 'qty' => 2, 'cash_paid' => 150],
                    ['emp_id' => 189, 'price' => 200, 'date' => '2026-02-20', 'status' => 'Consented', 'qty' => 1, 'cash_paid' => 0],
                ];

                foreach ($shopPayments as $p) {
                    DB::table('payments')->insert([
                        'shopkeeper_id' => $shopkeeperId,
                        'order_id' => 'ORD-SEED-' . $p['emp_id'] . '-' . str_replace('-', '', $p['date']),
                        'emp_id' => $p['emp_id'],
                        'product_id' => $productId,
                        'quantity' => $p['qty'],
                        'price' => $p['price'],
                        'status' => $p['status'],
                        'purchased_date' => $p['date'],
                        'cash_paid' => $p['cash_paid'],
                        'payroll_deducted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->command->info("Inserted " . count($shopPayments) . " shopkeeper payments for payroll period.");
            } else {
                $this->command->warn("No products found for shopkeeper — skipped payments.");
            }
        } else {
            $this->command->warn("No shopkeeper found for resort — skipped payments.");
        }
    }
}
