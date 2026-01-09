<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceTempleteModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grievance_templete_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->integer('Grievance_Cat_id')->nullable();
            $table->string('Grievance_Temp_name')->nullable();

            $table->longText('Grievance_Temp_Structure')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable(); 
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grievance_templete_models');
    }
}
