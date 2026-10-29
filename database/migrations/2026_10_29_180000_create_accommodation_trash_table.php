<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accommodation › Configuration: restore a deleted building, floor/room or room setup on the same rows.
 * A delete keeps its rows here (by table) and removes them as before; a restore puts them back with their old ids.
 * Not soft deletes: many pages join these tables directly and would keep showing a soft-deleted room.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accommodation_trash')) {
            return;
        }
        Schema::create('accommodation_trash', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id')->index();
            $table->string('kind', 20);                 // building | floor_room | room_setup
            $table->unsignedBigInteger('record_id');
            $table->longText('payload');                // {table: [rows]}
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_trash');
    }
};
