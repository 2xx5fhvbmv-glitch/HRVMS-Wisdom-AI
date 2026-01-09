<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTAnotificationParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_anotification_parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->unsignedInteger('V_id')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('Resort_id')->references('id')->on('resorts');
            $table->foreign('V_id')->references('id')->on('vacancies');
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
        Schema::dropIfExists('t_anotification_parents');
    }
}
