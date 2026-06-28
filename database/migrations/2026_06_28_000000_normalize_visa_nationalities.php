<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Carry the visa_nationalities country-name -> demonym normalisation to every
 * environment on deploy. The table was seeded with country names ("Bangladesh",
 * "Philippines"), but the config dropdown only matches demonyms ("Bangladeshi",
 * "Filipino"), so editing a row appeared to "rename" it. Runs the dedicated
 * command (idempotent — rows already holding a valid demonym are skipped).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visa_nationalities')) {
            return;
        }
        Artisan::call('visa:normalize-nationalities');
    }

    public function down(): void
    {
        // Data normalisation — not reversible.
    }
};
