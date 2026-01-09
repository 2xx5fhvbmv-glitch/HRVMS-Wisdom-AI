<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodeOfCounductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('code_of_counducts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->integer('Deciplinery_cat_id')->nullable();
            $table->integer('Offenses_id')->nullable();
            $table->integer('Action_id')->nullable();
            $table->integer('Severity_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts');
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
        Schema::dropIfExists('code_of_counducts');
    }
}
