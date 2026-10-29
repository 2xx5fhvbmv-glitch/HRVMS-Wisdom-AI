<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Accommodation › Configuration: the default occupancy alert level for new rooms, shared by the resort's HR users (was per browser). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('escalation_days', 'default_occupancy_alert')) {
            Schema::table('escalation_days', function (Blueprint $table) {
                $table->unsignedTinyInteger('default_occupancy_alert')->nullable()->after('EscalationDay');   // percent full, 50-100
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('escalation_days', 'default_occupancy_alert')) {
            Schema::table('escalation_days', fn (Blueprint $table) => $table->dropColumn('default_occupancy_alert'));
        }
    }
};
