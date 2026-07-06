<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dummy punch-in/punch-out data for "last week" (Mon-Sun before the current
 * week, computed relative to today — NOT a fixed date range like
 * RealAttendanceSeeder) so the attendance reports always have fresh, recent
 * data to show regardless of when this is run. Idempotent: safe to re-run,
 * clears its own prior rows for the same employees + date range first.
 *
 * Target employees: same resort-26 test set as RealAttendanceSeeder, which
 * includes DR-10 (Fatima Naseer / stayer-banners-3d@icloud.com) — the
 * employee this was requested for.
 */
class PreviousWeekAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $resortId = 26;
        $defaultShiftId = 20; // Morning Shift, 04:00-12:00

        $empIds = [170, 171, 173, 174, 176, 177, 179, 180, 182, 183, 184, 186, 187, 188, 189, 192, 191, 194];

        $weekStart = Carbon::now()->subWeek()->startOfWeek(); // Monday
        $weekEnd   = Carbon::now()->subWeek()->endOfWeek();   // Sunday
        $dates     = [];
        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        $rosterLabel = $weekStart->format('m/d/Y') . ' - ' . $weekEnd->format('m/d/Y');
        $now = now();

        // ── Clear this seeder's own data for these employees/dates (idempotent) ──
        $existingIds = DB::table('parent_attendaces')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->whereBetween('date', [$dates[0], end($dates)])
            ->pluck('id');
        if ($existingIds->isNotEmpty()) {
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $existingIds)->delete();
            DB::table('parent_attendaces')->whereIn('id', $existingIds)->delete();
        }
        DB::table('duty_roster_entries')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->whereBetween('date', [$dates[0], end($dates)])
            ->delete();
        DB::table('duty_rosters')
            ->where('resort_id', $resortId)
            ->whereIn('Emp_id', $empIds)
            ->where('ShiftDate', $rosterLabel)
            ->delete();

        $count = 0;
        foreach ($empIds as $empId) {
            // Reuse the employee's existing shift if they have one, else fall back to Morning Shift.
            $shiftId = DB::table('duty_rosters')->where('Emp_id', $empId)->value('Shift_id') ?? $defaultShiftId;
            $shift = DB::table('shift_settings')->where('id', $shiftId)->first();
            $startHour = $shift ? (int) explode(':', $shift->StartTime)[0] : 4;
            $shiftHours = $shift && $shift->TotalHours ? (int) explode(':', $shift->TotalHours)[0] : 8;

            $rosterId = DB::table('duty_rosters')->insertGetId([
                'resort_id'   => $resortId,
                'Shift_id'    => $shiftId,
                'Emp_id'      => $empId,
                'ShiftDate'   => $rosterLabel,
                'Year'        => $weekStart->format('Y'),
                'created_by'  => 259,
                'modified_by' => 259,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            foreach ($dates as $date) {
                $isWeekend = Carbon::parse($date)->isWeekend();
                $rosterStatus = $isWeekend ? 'DayOff' : 'Working';

                DB::table('duty_roster_entries')->insert([
                    'resort_id'  => $resortId,
                    'roster_id'  => $rosterId,
                    'Emp_id'     => $empId,
                    'Shift_id'   => $shiftId,
                    'date'       => $date,
                    'Status'     => $rosterStatus,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($isWeekend) {
                    continue; // no punch data on a day off
                }

                // ~85% present, ~15% absent, occasional overtime — matches RealAttendanceSeeder's mix.
                $roll = mt_rand(1, 100);
                $status = $roll <= 85 ? 'Present' : 'Absent';
                $overtime = null;
                $checkIn = null;
                $checkOut = null;
                $totalHours = null;

                if ($status === 'Present') {
                    $checkInOffset = mt_rand(0, 15); // 0-15 min late
                    $checkIn = sprintf('%02d:%02d:00', $startHour + intdiv($checkInOffset, 60), $checkInOffset % 60);
                    $ot = mt_rand(1, 100) <= 20 ? mt_rand(1, 3) : 0; // ~20% chance of 1-3h OT
                    $workedMinutes = ($shiftHours + $ot) * 60 + mt_rand(0, 10);
                    $checkOut = date('H:i:00', strtotime($checkIn) + $workedMinutes * 60);
                    $totalHours = sprintf('%d:00', $shiftHours);
                    if ($ot > 0) {
                        $overtime = sprintf('%02d:00', $ot);
                    }
                }

                $parentId = DB::table('parent_attendaces')->insertGetId([
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
                    'CheckInCheckOut_Type' => 'Manual',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                if ($status === 'Present') {
                    // Small jitter around Male', Maldives so points aren't identical.
                    $lat = 4.1730 + mt_rand(-50, 50) / 10000;
                    $lng = 73.5120 + mt_rand(-50, 50) / 10000;
                    $mapUrl = fn($la, $lo) => "https://www.google.com/maps/embed/v1/view?key=AIzaSyB-hYfoNr_5ih_LIrP0kfmfZVNhfdCMNuY&center={$la},{$lo}&zoom=12";

                    DB::table('child_attendaces')->insert([
                        'Parent_attd_id'   => $parentId,
                        'InTime_out'       => $checkIn,
                        'OutTime_out'      => $checkOut,
                        'InTime_Location'  => $mapUrl($lat, $lng),
                        'OutTime_Location' => $mapUrl($lat + 0.0002, $lng + 0.0002),
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }

                $count++;
            }
        }

        $this->command->info("Seeded {$count} attendance records for " . count($empIds) . " employees, {$dates[0]} to " . end($dates) . ".");
    }
}
