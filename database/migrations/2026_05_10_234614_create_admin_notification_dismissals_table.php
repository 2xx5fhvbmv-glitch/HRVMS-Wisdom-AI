<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user "I've dismissed this banner" record. The admin broadcast
 * notification banner reads from `notifications` (the global record) and
 * filters out anything the current user has already crossed out via this
 * pivot, so the banner re-appears on a different account but stays gone
 * for the user who closed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedInteger('resort_admin_id');
            $table->timestamp('dismissed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['notification_id', 'resort_admin_id'], 'idx_admin_notif_dismiss_unique');
            $table->index('resort_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_dismissals');
    }
};
