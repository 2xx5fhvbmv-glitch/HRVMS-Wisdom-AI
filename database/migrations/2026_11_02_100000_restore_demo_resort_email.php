<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Demo ENV: the demo resort's email was edited (2026-11-02), so demo:reset / demo:verify refuse it
 * (Demo::assertSafeToReset matches code AND email). Put the email back; name and logo stay as branded.
 * Touches only the resort carrying the demo code — a no-op on servers without one.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('resorts')->where('resort_id', config('demo.resort_code'))
            ->where('resort_email', '<>', config('demo.resort_email'))
            ->update(['resort_email' => config('demo.resort_email'), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo: the edited email was never meant to be on the demo resort.
    }
};
