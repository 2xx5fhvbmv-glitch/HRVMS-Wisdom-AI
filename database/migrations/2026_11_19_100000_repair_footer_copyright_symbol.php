<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mobile QA #2: a footer saved through a non-UTF-8 path stored "Copyright ?? 2026" (the two bytes of © became "??"),
 * which every PDF then printed. Only rows that literally contain "Copyright ??" change.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('resort_site_settings')
            ->where('Footer', 'like', '%Copyright ??%')
            ->update(['Footer' => DB::raw("REPLACE(Footer, 'Copyright ??', 'Copyright ©')")]);
    }

    public function down(): void
    {
        // Not reversible: the broken text carried no information.
    }
};
