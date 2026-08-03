<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FloorNo was switched to string(50) alongside RoomNo in
// 2026_04_10_000001_make_floorno_roomno_nullable_in_maintanace_requests
// (to make both nullable), which broke a mobile client that still expects
// FloorNo as int? ("type 'String' is not a subtype of type 'int?'" on
// maintenance request submit/list/dashboard). Floor numbers are always
// numeric (confirmed against every existing row), unlike RoomNo which can
// legitimately be alphanumeric elsewhere in this schema
// (available_accommodation_models.RoomNo is varchar) — so only FloorNo is
// reverted here, staying nullable.
return new class extends Migration
{
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->integer('FloorNo')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->string('FloorNo', 50)->nullable()->default(null)->change();
        });
    }
};
