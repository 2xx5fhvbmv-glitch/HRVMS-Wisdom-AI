<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track who created a meeting so we can notify them when invitees accept or decline.
 * Stores the ResortAdmin id (matching `created_by` on most other tables in this app).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peformance_meetings')) return;
        Schema::table('peformance_meetings', function (Blueprint $t) {
            if (!Schema::hasColumn('peformance_meetings', 'created_by')) {
                $t->unsignedBigInteger('created_by')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('peformance_meetings')) return;
        Schema::table('peformance_meetings', function (Blueprint $t) {
            if (Schema::hasColumn('peformance_meetings', 'created_by')) {
                $t->dropColumn('created_by');
            }
        });
    }
};
