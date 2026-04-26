<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the "Recurring" frequency option to "Monthly" and add a day-of-month
 * column that the new UI uses for one-time / monthly / quarterly programs.
 *
 *  - frequency_day : 1..30 (the day of the month to run on). NULL for annually.
 *  - existing rows with frequency='recurring' are remapped to 'monthly'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        Schema::table('learning_programs', function (Blueprint $t) {
            if (!Schema::hasColumn('learning_programs', 'frequency_day')) {
                $t->unsignedTinyInteger('frequency_day')->nullable()->after('frequency');
            }
        });

        // Backfill the renamed value so existing programs survive the rename.
        DB::table('learning_programs')
            ->where('frequency', 'recurring')
            ->update(['frequency' => 'monthly']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('learning_programs')) return;

        DB::table('learning_programs')
            ->where('frequency', 'monthly')
            ->update(['frequency' => 'recurring']);

        Schema::table('learning_programs', function (Blueprint $t) {
            if (Schema::hasColumn('learning_programs', 'frequency_day')) {
                $t->dropColumn('frequency_day');
            }
        });
    }
};
