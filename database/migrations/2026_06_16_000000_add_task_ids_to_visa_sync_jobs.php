<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Xpat-sync extraction now runs asynchronously on the AI server: the
 * controller starts two background extractions and stores their task ids here,
 * then the page polls until both are ready. Adds the task-id columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_sync_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('visa_sync_jobs', 'xpat_task_id')) {
                $table->string('xpat_task_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('visa_sync_jobs', 'quota_task_id')) {
                $table->string('quota_task_id')->nullable()->after('xpat_task_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visa_sync_jobs', function (Blueprint $table) {
            $table->dropColumn(['xpat_task_id', 'quota_task_id']);
        });
    }
};
