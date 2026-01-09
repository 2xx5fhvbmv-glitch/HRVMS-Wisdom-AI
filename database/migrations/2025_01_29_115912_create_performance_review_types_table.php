<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceReviewTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_review_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('category_title');
            $table->float('category_weightage');

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
        Schema::dropIfExists('performance_review_types');
    }
}
