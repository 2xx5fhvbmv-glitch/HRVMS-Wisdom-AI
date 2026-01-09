<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyCheckingModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_checking_models', function (Blueprint $table) {
            $table->id();
            $table->string('Checkin_id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('tranining_id')->nullable();
            $table->string('emp_id');
            $table->string('date_discussion');
            $table->string('time_of_discussion');
            $table->string('Meeting_Place');
            $table->string('Area_of_Discussion');
            $table->string('Area_of_Improvement');
            $table->string('Time_Line');
            $table->string('comment');
    
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
        Schema::dropIfExists('monthly_checking_models');
    }
}
