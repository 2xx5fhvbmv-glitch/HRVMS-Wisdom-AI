<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('resort_id');
            $table->text('description')->nullable();
            $table->json('query_params');
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
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
        Schema::dropIfExists('resort_reports');
    }
}
