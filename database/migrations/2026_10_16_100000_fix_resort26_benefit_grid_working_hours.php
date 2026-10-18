<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Founder-confirmed typo (2026-10-15): Resort 26's benefit grid stores
// working_hrs_per_week = 6 (days, not hours); the weekly limit is 48 hours.
// Only rows holding exactly 6 change — any other value is left for review.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('resort_benifit_grid')->where('resort_id', 26)->where('working_hrs_per_week', 6)
            ->update(['working_hrs_per_week' => 48, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible: 6 was a typo, not a value to restore.
    }
};
