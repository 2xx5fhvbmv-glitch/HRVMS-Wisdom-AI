<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('housekeeping_service_catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('resort_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('housekeeping_service_catalog');
    }
};
