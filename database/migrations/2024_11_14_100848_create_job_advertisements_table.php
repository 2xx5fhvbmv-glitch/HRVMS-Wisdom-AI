<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_advertisements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->string ( 'Jobadvimg');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('Resort_id')->references('id')->on('resorts');
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
        Schema::dropIfExists('job_advertisements');
    }
}
