<?php

namespace App\Services;

use App\Helpers\Common;
use Illuminate\Support\Facades\DB;

/**
 * Tells HR when a room or a building reaches the occupancy level set for it on Accommodation › Configuration
 * (percent FULL: occupied beds / beds × 100 >= level). Room level: available_accommodation_models.Occupancytheresold
 * (default 90); building level: building_models.occupancy_alert (optional).
 * Once per crossing: occupancy_alert_notified_at is set when HR is told and cleared when it drops back below.
 * Runs on every bed assign/release (AssingAccommodation model events) and nightly for everything as a safety net.
 */
class RoomOccupancyAlert
{
    private const DEFAULT_LEVEL = 90; // the Configuration screen's default for rooms

    public static function check($roomId): void
    {
        try {
            $room = DB::table('available_accommodation_models as a')
                ->leftJoin('building_models as b', 'b.id', '=', 'a.BuildingName')
                ->where('a.id', $roomId)
                ->first(['a.id', 'a.resort_id', 'a.RoomNo', 'a.Occupancytheresold', 'a.occupancy_alert_notified_at', 'b.id as building_id', 'b.BuildingName as building']);
            if (!$room) {
                return;
            }
            $beds = DB::table('assing_accommodations')->where('available_a_id', $room->id)
                ->selectRaw('count(*) beds, sum(emp_id is not null and emp_id <> 0) occupied')->first();
            self::cross('available_accommodation_models', $room->id, $room->resort_id, $beds, (int) ($room->Occupancytheresold ?: self::DEFAULT_LEVEL),
                $room->occupancy_alert_notified_at, 'Room ' . $room->RoomNo . ($room->building ? " in {$room->building}" : ''), 'accommodation-occupancy-alert');

            if ($room->building_id) {
                self::checkBuilding($room->building_id);
            }
        } catch (\Throwable $e) {
            \Log::warning('Room occupancy alert failed for room ' . $roomId . ': ' . $e->getMessage()); // never blocks the bed change
        }
    }

    public static function checkBuilding($buildingId): void
    {
        try {
            self::buildingCheck($buildingId);
        } catch (\Throwable $e) {
            \Log::warning('Building occupancy alert failed for building ' . $buildingId . ': ' . $e->getMessage());
        }
    }

    private static function buildingCheck($buildingId): void
    {
        $b = DB::table('building_models')->where('id', $buildingId)->first(['id', 'resort_id', 'BuildingName', 'occupancy_alert', 'occupancy_alert_notified_at']);
        if (!$b || (!$b->occupancy_alert && !$b->occupancy_alert_notified_at)) {
            return;
        }
        $beds = DB::table('assing_accommodations as s')->join('available_accommodation_models as a', 'a.id', '=', 's.available_a_id')
            ->where('a.BuildingName', $b->id)->selectRaw('count(*) beds, sum(s.emp_id is not null and s.emp_id <> 0) occupied')->first();
        self::cross('building_models', $b->id, $b->resort_id, $beds, (int) $b->occupancy_alert, $b->occupancy_alert_notified_at,
            $b->BuildingName, 'accommodation-building-occupancy-alert');
    }

    /** Every room with beds, and every building with a level (nightly safety net for bed changes made without model events). */
    public static function checkAll(): void
    {
        DB::table('assing_accommodations')->distinct()->orderBy('available_a_id')->pluck('available_a_id')->each(fn ($id) => self::check($id));
        DB::table('building_models')->where(fn ($q) => $q->whereNotNull('occupancy_alert')->orWhereNotNull('occupancy_alert_notified_at'))
            ->pluck('id')->each(fn ($id) => self::checkBuilding($id));
    }

    /** Notify HR the first time $level is reached; clear the flag once it drops below (or the level is removed). */
    private static function cross(string $table, $id, $resortId, $beds, int $level, $notifiedAt, string $label, string $pageId): void
    {
        $percent = $beds->beds ? round($beds->occupied / $beds->beds * 100) : 0;
        $reached = $level > 0 && $beds->beds > 0 && $percent >= $level;
        if ($reached === (bool) $notifiedAt) {
            return; // already told, or nothing to tell
        }
        DB::table($table)->where('id', $id)->update(['occupancy_alert_notified_at' => $reached ? now() : null]);

        if ($reached) {
            Common::notifyEmployees(
                $resortId,
                Common::getResortHrEmployeeIds($resortId),
                $table === 'building_models' ? 'Building occupancy alert' : 'Room occupancy alert',
                "{$label} is {$percent}% full ({$beds->occupied} of {$beds->beds} beds), at or above its {$level}% alert level.",
                'Accommodation',
                $id,
                $pageId
            );
        }
    }
}
