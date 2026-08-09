<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * grivance_submission_witnesses.status/Statement/Attachement were added
 * directly to the database at some point, never through a migration — so
 * any environment that didn't happen to get that manual patch (production)
 * has no such columns at all. The very next migration
 * (2026_08_08_231346_add_submitted_to_grivance_submission_witnesses_status)
 * only MODIFYs the status enum, assuming it already exists, and fails with
 * "Column not found" anywhere this base column was never added. This
 * migration adds what's missing (guarded, so it's a no-op anywhere the
 * columns already exist, e.g. local dev).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('grivance_submission_witnesses', function (Blueprint $table) {
            if (!Schema::hasColumn('grivance_submission_witnesses', 'status')) {
                $table->enum('status', ['Requested', 'Approved', 'NoAction'])
                    ->default('NoAction')
                    ->after('Wintness_Status');
            }
            if (!Schema::hasColumn('grivance_submission_witnesses', 'Statement')) {
                $table->string('Statement')->nullable()->after('status');
            }
            if (!Schema::hasColumn('grivance_submission_witnesses', 'Attachement')) {
                $table->string('Attachement')->nullable()->after('Statement');
            }
        });
    }

    public function down()
    {
        Schema::table('grivance_submission_witnesses', function (Blueprint $table) {
            foreach (['status', 'Statement', 'Attachement'] as $col) {
                if (Schema::hasColumn('grivance_submission_witnesses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
