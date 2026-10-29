<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Founder decision: beds read "Bed No 1", "Bed No 2" ... New beds are created that way; this renames the existing
 * "BedNo-1", "Bed No-1", "#1" and bare "1" so a building doesn't mix styles. Any other custom name is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('assing_accommodations')->whereNotNull('BedNo')->orderBy('id')->select('id', 'BedNo')
            ->chunkById(500, function ($beds) {
                foreach ($beds as $bed) {
                    if (preg_match('/^\s*(?:BedNo\s*-\s*|Bed\s*No\s*-\s*|#\s*)?(\d+)\s*$/i', $bed->BedNo, $m) && $bed->BedNo !== 'Bed No ' . (int) $m[1]) {
                        DB::table('assing_accommodations')->where('id', $bed->id)->update(['BedNo' => 'Bed No ' . (int) $m[1]]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Old styles were mixed; there is no single format to go back to.
    }
};
