<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRightToBeAccompaniedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('right_to_be_accompanieds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->enum('RightToBeAccompanied',['Allow','Denied'])->default('Allow');
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
        Schema::dropIfExists('right_to_be_accompanieds');
    }
}
