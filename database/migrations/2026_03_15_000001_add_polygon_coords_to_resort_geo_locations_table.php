<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolygonCoordsToResortGeoLocationsTable extends Migration
{
    public function up()
    {
        Schema::table('resort_geo_locations', function (Blueprint $table) {
            $table->text('polygon_coords')->nullable()->after('longitude');
        });
    }

    public function down()
    {
        Schema::table('resort_geo_locations', function (Blueprint $table) {
            $table->dropColumn('polygon_coords');
        });
    }
}
