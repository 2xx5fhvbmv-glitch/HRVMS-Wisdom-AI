<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When HR was told a room reached its occupancy alert level; cleared once it drops below (App\Services\RoomOccupancyAlert). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('available_accommodation_models', function (Blueprint $table) {
            $table->timestamp('occupancy_alert_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('available_accommodation_models', function (Blueprint $table) {
            $table->dropColumn('occupancy_alert_notified_at');
        });
    }
};
