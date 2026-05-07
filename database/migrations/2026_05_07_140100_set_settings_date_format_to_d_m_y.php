<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Standardise the resort-side display format on `d M Y` (e.g. "07 May 2026").
 * Matches the format swept across the views/controllers — see the bulk
 * "format('d-m-Y') -> format('d M Y')" change earlier in the session. This
 * row drives Common::getDateFormateFromSettings() and the helper-based
 * formatters that read it.
 */
return new class extends Migration {
    public function up()
    {
        DB::table('settings')->update(['date_format' => 'd M Y']);
    }

    public function down()
    {
        DB::table('settings')->update(['date_format' => 'Y-m-d']);
    }
};
