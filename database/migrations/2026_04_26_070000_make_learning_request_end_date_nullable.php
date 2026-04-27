<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the NOT NULL constraint on learning_requests.end_date so requests can
 * be created with only an expected start date. The form-side END DATE input
 * has been commented out for now; this migration just makes the column tolerate
 * nulls so future submits don't 500.
 *
 * Idempotent — re-running on a column that's already NULL is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_requests') || !Schema::hasColumn('learning_requests', 'end_date')) {
            return;
        }
        DB::statement('ALTER TABLE learning_requests MODIFY end_date DATE NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('learning_requests') || !Schema::hasColumn('learning_requests', 'end_date')) {
            return;
        }
        // Backfill nulls before tightening to avoid integrity errors.
        DB::statement('UPDATE learning_requests SET end_date = start_date WHERE end_date IS NULL');
        DB::statement('ALTER TABLE learning_requests MODIFY end_date DATE NOT NULL');
    }
};
