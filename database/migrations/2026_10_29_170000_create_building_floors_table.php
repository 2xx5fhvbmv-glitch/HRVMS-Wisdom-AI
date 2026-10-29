<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accommodation › Configuration: an optional gender per floor (M, F or X = mixed), like the building's own setting.
 * Floors aren't rows anywhere else (a floor is the Floor number on each room row), so only floors with a gender set
 * have a row here; no row = the building's setting applies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('building_floors')) {
            return;
        }
        Schema::create('building_floors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->index();
            $table->unsignedBigInteger('building_id');
            $table->integer('floor');
            $table->char('gender', 1)->nullable();
            $table->timestamps();
            $table->unique(['building_id', 'floor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_floors');
    }
};
