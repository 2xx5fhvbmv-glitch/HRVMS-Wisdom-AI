<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionnairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->unsignedInteger( 'Division_id');
            $table->unsignedInteger( 'Department_id');
            $table->unsignedInteger( 'Position_id');
            $table->enum('video',['Yes','No'])->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();

            $table->timestamps();
            $table->foreign('Resort_id')->references('id')->on('resorts');

            $table->foreign( 'Division_id')->references('id')->on('resort_divisions');
            $table->foreign( 'Department_id')->references('id')->on('resort_departments');
            $table->foreign( 'Position_id')->references('id')->on('resort_positions');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questionnaires');
    }
}
