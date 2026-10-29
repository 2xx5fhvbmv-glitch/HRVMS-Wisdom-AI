<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accommodation › Configuration, Phase B step 2 (plan §1 rows 1 and 3, §2.1):
 * - building_models.gender M|F|X and room_numbering by_floor|continuous|custom (+ room_number_pattern for custom),
 *   backfilled with the rules the screen used to derive them (accommodation-config.js deriveG / inferNs);
 * - bulidng_and_floor_and_rooms.Room becomes text, so rooms can be numbered G01, F1-2, A101 (existing numbers kept).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('building_models', function (Blueprint $table) {
            $table->char('gender', 1)->default('X');                      // M = male, F = female, X = mixed / not set
            $table->string('room_numbering', 10)->default('by_floor');    // by_floor | continuous | custom
            $table->string('room_number_pattern', 40)->nullable();        // only for custom, e.g. F{F}-{N}
        });
        Schema::table('bulidng_and_floor_and_rooms', function (Blueprint $table) {
            $table->string('Room', 15)->change();
        });

        foreach (DB::table('building_models')->pluck('id') as $id) {
            // Gender: all set-up rooms Male -> M, none Male -> F, otherwise (or no set-up rooms) X.
            $rooms = DB::table('available_accommodation_models')->where('BuildingName', $id)
                ->selectRaw("count(*) total, sum(blockFor = 'Male') male")->first();
            $gender = !$rooms->total ? 'X' : ((int) $rooms->male === (int) $rooms->total ? 'M' : ((int) $rooms->male === 0 ? 'F' : 'X'));

            // Numbering: every room above ground inside its own floor's hundred (floor 2 -> 201..299) -> by_floor.
            $continuous = DB::table('bulidng_and_floor_and_rooms')->where('building_id', $id)->where('Floor', '>', 0)->get(['Floor', 'Room'])
                ->contains(fn ($r) => !(($n = (int) preg_replace('/\D/', '', (string) $r->Room)) > $r->Floor * 100 && $n < $r->Floor * 100 + 100));

            DB::table('building_models')->where('id', $id)->update(['gender' => $gender, 'room_numbering' => $continuous ? 'continuous' : 'by_floor']);
        }
    }

    public function down(): void
    {
        Schema::table('bulidng_and_floor_and_rooms', function (Blueprint $table) {
            $table->integer('Room')->change();   // only safe while every room number is still numeric
        });
        Schema::table('building_models', function (Blueprint $table) {
            $table->dropColumn(['gender', 'room_numbering', 'room_number_pattern']);
        });
    }
};
