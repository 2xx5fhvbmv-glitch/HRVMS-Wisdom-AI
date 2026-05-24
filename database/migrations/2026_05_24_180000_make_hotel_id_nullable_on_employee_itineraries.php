<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The onboarding itinerary form does not collect a hotel_id — it captures
 * a free-text hotel_name. The original table created hotel_id as NOT NULL,
 * which causes "Column 'hotel_id' cannot be null" on submit. Relax it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_itineraries', function (Blueprint $table) {
            $table->string('hotel_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op — re-tightening would fail on existing null rows.
    }
};
