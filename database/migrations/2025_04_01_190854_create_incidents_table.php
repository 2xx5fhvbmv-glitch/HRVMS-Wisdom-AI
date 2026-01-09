<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('incident_id');
            $table->string('incident_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('reporter_id')->nullable();
            $table->string('victims');
            $table->date('incident_date');
            $table->time('incident_time');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('category');
            $table->unsignedBigInteger('subcategory');
            $table->enum('isWitness',['Yes','No'])->default('No');
            $table->string('involved_employees');
            $table->string('attachements')->nullable();
            $table->enum('priority',['Low','Medium','High'])->default('Low');
            $table->json('assigned_to')->nullable();
            $table->text('comments')->nullable();
            $table->string('severity')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('category')->references('id')->on('incident_categories')->onDelete('cascade');
            $table->foreign('subcategory')->references('id')->on('incident_subcategories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incidents');
    }
}
