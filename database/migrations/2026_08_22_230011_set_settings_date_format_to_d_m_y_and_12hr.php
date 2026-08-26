<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Standardise the display format to "dd-mmm-yy" (e.g. "28-Jul-26") with
 * 12-hour AM/PM time. Supersedes the earlier "d M Y" sweep — the settings
 * row had drifted to date_format="d/m/Y" and, critically, time_format
 * had drifted to the literal string "H:i" rather than the "12"/"24" token
 * Common::getTimeFromSettings() actually compares against (`== '12'`),
 * which meant every one of the ~141 callers of that helper was silently
 * stuck on 24-hour time regardless of intent.
 */
return new class extends Migration {
    public function up()
    {
        DB::table('settings')->update([
            'date_format' => 'd-M-y',
            'time_format' => '12',
        ]);
    }

    public function down()
    {
        DB::table('settings')->update([
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ]);
    }
};
