<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityTourImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_tour_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_tour_category_id');
            $table->string('image');
            $table->foreign('facility_tour_category_id')->references('id')->on('facility_tour_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facility_tour_images');
    }
}
