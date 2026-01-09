<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailableAccommodationInvItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('available_accommodation_inv_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Available_Acc_id');
            $table->unsignedBigInteger('Item_id')->nullable();
            $table->timestamps();
            $table->foreign('Item_id')->references('id')->on('inventory_modules');
            $table->foreign('Available_Acc_id')->references('id')->on('available_accommodation_models');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('available_accommodation_inv_items');
    }
}
