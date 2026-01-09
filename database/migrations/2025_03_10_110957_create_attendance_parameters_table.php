<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_parameters', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id'); // If parameters vary by resort
            $table->integer('threshold_percentage')->nullable();
            $table->boolean('auto_notifications')->default(false);
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
        Schema::dropIfExists('attendance_parameters');
    }
}
