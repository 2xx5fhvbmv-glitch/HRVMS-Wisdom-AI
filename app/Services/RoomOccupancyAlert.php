<?php

namespace App\Services;

use App\Helpers\Common;
use Illuminate\Support\Facades\DB;

/**
 * Tells HR when a room reaches the occupancy level set for it on Accommodation › Configuration
 * (available_accommodation_models.Occupancytheresold, percent FULL: occupied beds / beds × 100 >= level).
 * Once per crossing: occupancy_alert_notified_at is set when HR is told and cleared when the room drops back below.
 * Runs on every bed assign/release (AssingAccommodation model events) and nightly for all rooms as a safety net.
 */
class RoomOccupancyAlert
{
    private const DEFAULT_LEVEL = 90; // the Configuration screen's default

    public static function check($roomId): void
    {
        try {
            $room = DB::table('available_accommodation_models as a')
                ->leftJoin('building_models as b', 'b.id', '=', 'a.BuildingName')
                ->where('a.id', $roomId)
                ->first(['a.id', 'a.resort_id', 'a.RoomNo', 'a.Occupancytheresold', 'a.occupancy_alert_notified_at', 'b.BuildingName as building']);
            if (!$room) {
                return;
            }

            $beds = DB::table('assing_accommodations')->where('available_a_id', $room->id)
                ->selectRaw('count(*) beds, sum(emp_id is not null and emp_id <> 0) occupied')->first();
            $level = (int) ($room->Occupancytheresold ?: self::DEFAULT_LEVEL);
            $percent = $beds->beds ? round($beds->occupied / $beds->beds * 100) : 0;
            $reached = $beds->beds > 0 && $percent >= $level;

            if ($reached === (bool) $room->occupancy_alert_notified_at) {
                return; // already told, or nothing to tell
            }
            DB::table('available_accommodation_models')->where('id', $room->id)->update(['occupancy_alert_notified_at' => $reached ? now() : null]);

            if ($reached) {
                Common::notifyEmployees(
                    $room->resort_id,
                    Common::getResortHrEmployeeIds($room->resort_id),
                    'Room occupancy alert',
                    "Room {$room->RoomNo}" . ($room->building ? " in {$room->building}" : '') . " is {$percent}% full ({$beds->occupied} of {$beds->beds} beds), at or above its {$level}% alert level.",
                    'Accommodation',
                    $room->id,
                    'accommodation-occupancy-alert'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Room occupancy alert failed for room ' . $roomId . ': ' . $e->getMessage()); // never blocks the bed change
        }
    }

    /** Every room that has beds (nightly safety net for bed changes made without model events). */
    public static function checkAll(): void
    {
        DB::table('assing_accommodations')->distinct()->orderBy('available_a_id')->pluck('available_a_id')->each(fn ($id) => self::check($id));
    }
}
