<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_languages', function (Blueprint $table) {
            $table->id();
            $table->string('name',50);
            $table->string('sort_name',25);
            $table->string('native',50);
            $table->string('country_code',50);
            $table->string('flag_image',250);
            $table->string('flag_image_svg',250);
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
        Schema::dropIfExists('resort_languages');
    }
}
