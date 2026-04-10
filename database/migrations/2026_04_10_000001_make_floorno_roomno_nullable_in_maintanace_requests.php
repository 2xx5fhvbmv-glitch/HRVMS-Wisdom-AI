<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->string('FloorNo', 50)->nullable()->default(null)->change();
            $table->string('RoomNo', 50)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->integer('FloorNo')->nullable(false)->change();
            $table->integer('RoomNo')->nullable(false)->change();
        });
    }
};
