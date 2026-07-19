<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * countries.flag_url has always been empty for every row — the
     * Applicant's O'clock flag in the interview time-slot picker
     * (resources/views/resorts/renderfiles/TimezoneModel.blade.php) has
     * never had a real image to show. Backfill from shortname (ISO-2
     * code, present on all 58 rows) via flagcdn.com's stable public URL
     * convention.
     */
    public function up(): void
    {
        $countries = DB::table('countries')
            ->whereNotNull('shortname')
            ->where('shortname', '!=', '')
            ->orderBy('id')
            ->get(['id', 'shortname']);

        foreach ($countries as $country) {
            DB::table('countries')
                ->where('id', $country->id)
                ->update([
                    'flag_url' => 'https://flagcdn.com/w40/' . strtolower($country->shortname) . '.png',
                ]);
        }
    }

    public function down(): void
    {
        DB::table('countries')->update(['flag_url' => '']);
    }
};
