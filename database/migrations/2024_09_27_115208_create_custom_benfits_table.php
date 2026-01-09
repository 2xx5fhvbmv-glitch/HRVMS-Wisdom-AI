<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomBenfitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_benfits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('benefit_grid_id');
            $table->string('benefit_name');
            $table->string('benefit_value');
            $table->timestamps();

            // Foreign key constraint to benefit_grid table
            $table->foreign('benefit_grid_id')->references('id')->on('resort_benifit_grid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_benfits');
    }
}
