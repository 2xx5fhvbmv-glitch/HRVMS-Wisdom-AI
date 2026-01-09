<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessionalformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professionalforms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('FormName')->nullable();
            $table->longtext('form_structure')->nullable();
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
        Schema::dropIfExists('professionalforms');
    }
}
