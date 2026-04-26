<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make `hours` and `days` nullable on learning_programs. The save() endpoint
 * now treats them as mutually-optional (at least one required), but the schema
 * was still NOT NULL — submissions with only one of the two threw a 1048
 * integrity-constraint violation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        DB::statement('ALTER TABLE learning_programs MODIFY hours DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE learning_programs MODIFY days INT(11) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        // Backfill any nulls before re-tightening, otherwise the ALTER fails.
        DB::table('learning_programs')->whereNull('hours')->update(['hours' => 0]);
        DB::table('learning_programs')->whereNull('days')->update(['days' => 0]);

        DB::statement('ALTER TABLE learning_programs MODIFY hours DOUBLE(8,2) NOT NULL');
        DB::statement('ALTER TABLE learning_programs MODIFY days INT(11) NOT NULL');
    }
};
