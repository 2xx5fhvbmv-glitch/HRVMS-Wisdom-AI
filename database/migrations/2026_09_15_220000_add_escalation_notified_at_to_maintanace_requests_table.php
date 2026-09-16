<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks whether the escalation-overdue reminder has already fired
        // for this request, so the daily cron notifies once instead of
        // re-notifying every day the request stays open past the threshold.
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->timestamp('escalation_notified_at')->nullable()->after('Status');
        });
    }

    public function down(): void
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->dropColumn('escalation_notified_at');
        });
    }
};
