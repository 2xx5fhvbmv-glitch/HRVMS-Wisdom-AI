<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreManningResponseParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_manning_response_parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->unsignedBigInteger( 'Budget_id');
            $table->unsignedInteger('Department_id');
            $table->float('Total_Department_budget')->default(0.00);
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign('Resort_id')->references('id')->on('resorts');
            $table->foreign('Budget_id')->references('id')->on('manning_responses');
            $table->foreign('Department_id')->references('id')->on('resort_departments');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_manning_response_parents');
    }
}
