<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAirportsTable extends Migration
{
    public function up()
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            // IATA is 3 letters and (almost) globally unique. Mark unique
            // so the seeder can `upsert` against this column without dupes.
            $table->string('iata_code', 3)->unique();
            $table->string('icao_code', 4)->nullable()->index();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            // 'national' vs 'international' for display grouping. The
            // current Maldives-resort install treats Maldivian airports
            // as national; everything else international.
            $table->enum('type', ['national', 'international'])->default('international');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Search index — composite covers our typical lookups.
            $table->index(['is_active', 'type']);
            $table->index('city');
            $table->index('country');
        });
    }

    public function down()
    {
        Schema::dropIfExists('airports');
    }
}
