<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Building-level occupancy alert (plan §2.3): HR is told when a building is at least this % full (App\Services\RoomOccupancyAlert).
 * Replaces occupancy_levels_hit_a_critical_thresholds, whose level meant "alert when FREE beds are at or below L%";
 * each building's latest old level is carried over as the same trigger in percent full: 100 - L. The old table is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_models', function (Blueprint $table) {
            $table->unsignedTinyInteger('occupancy_alert')->nullable();     // percent full; null = no building alert
            $table->timestamp('occupancy_alert_notified_at')->nullable();
        });

        DB::table('occupancy_levels_hit_a_critical_thresholds')->whereNotNull('building_id')->orderBy('id')->get(['building_id', 'ThresSoldLevel'])
            ->keyBy('building_id')   // the latest row per building wins
            ->each(fn ($row) => DB::table('building_models')->where('id', $row->building_id)
                ->update(['occupancy_alert' => max(1, min(100, 100 - (int) $row->ThresSoldLevel))]));
    }

    public function down(): void
    {
        Schema::table('building_models', function (Blueprint $table) {
            $table->dropColumn(['occupancy_alert', 'occupancy_alert_notified_at']);
        });
    }
};
