<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incident_configuration', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('setting_key'); // e.g., 'meeting_reminder'
            $table->text('setting_value'); // JSON data
            $table->integer('created_by');
            $table->integer('modified_by');
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
        Schema::dropIfExists('incident_configuration');
    }
}
