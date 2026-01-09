<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityTourCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_tour_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('thumbnail_image')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();

            $table->foreign('resort_id')->references('id')->on('resort_admins')->onDelete('cascade');
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
        Schema::dropIfExists('facility_tour_categories');
    }
}
