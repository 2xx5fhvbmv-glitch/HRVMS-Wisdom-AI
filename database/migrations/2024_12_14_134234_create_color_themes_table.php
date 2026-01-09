<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColorThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('color_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('name');  // Name of the theme (e.g., 'On Time', 'Late', 'Overtime')
            $table->string('color'); // Hex code of the color selected
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('color_themes');
    }
}
