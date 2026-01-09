<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailableAccommodationModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('available_accommodation_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id'); // Changed to BigInteger for consistency
            $table->string('BuildingName')->nullable();
            $table->integer('Floor')->nullable();
            $table->string('RoomNo')->nullable();
            $table->unsignedBigInteger('Accommodation_type_id'); // Already BigInteger
            $table->integer('RoomType')->nullable();
            $table->integer('BedNo')->nullable();
            $table->enum('blockFor', ['Male', 'Female'])->nullable();
            $table->integer('Capacity')->nullable();
            // $table->unsignedBigInteger('Inv_Cat_id'); // Already BigInteger
            $table->enum('CleaningSchedule', ['Daily', 'Weekly','By Weekly','Monthly'])->nullable();
            $table->enum('RoomStatus', ['Available', 'Occupied', 'Under Maintenance', 'Maintenance Required','Under Maintenance','Not in Operation'])->default('Available');
            $table->integer('Occupancytheresold')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Changed to BigInteger for possible referencing
            $table->unsignedBigInteger('modified_by')->nullable(); // Changed to BigInteger for possible referencing
            $table->string('Colour')->nullable();

            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            // $table->foreign('Inv_Cat_id')->references('id')->on('inventory_category_models'); // FK to inventory categories
            $table->foreign('Accommodation_type_id')->references('id')->on('accommodation_types'); // FK to inventory categories

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('available_accommodation_models');
    }
}
