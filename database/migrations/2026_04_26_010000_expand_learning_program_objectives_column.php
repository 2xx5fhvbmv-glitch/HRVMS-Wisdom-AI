<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `learning_programs.objectives` was varchar(191), which truncated programs that
 * needed multiple goals. Bump it to LONGTEXT so the new "Add More" UI can save
 * an unbounded list of bullet-separated objectives.
 *
 * Uses raw SQL because Doctrine isn't always available for ALTER COLUMN, and
 * because this is a simple type change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_programs') || !Schema::hasColumn('learning_programs', 'objectives')) {
            return;
        }
        DB::statement('ALTER TABLE learning_programs MODIFY objectives LONGTEXT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('learning_programs') || !Schema::hasColumn('learning_programs', 'objectives')) {
            return;
        }
        DB::statement('ALTER TABLE learning_programs MODIFY objectives VARCHAR(191) NULL');
    }
};
