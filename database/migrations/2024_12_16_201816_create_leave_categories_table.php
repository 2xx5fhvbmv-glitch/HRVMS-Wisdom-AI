<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->string('leave_type');
            $table->integer('number_of_days');
            $table->boolean('carry_forward')->default(false);
            $table->integer('carry_max')->nullable();
            $table->boolean('earned_leave')->default(false);
            $table->integer('earned_max')->nullable();
            $table->string('eligibility');
            $table->string('frequency');
            $table->string('number_of_times');
            $table->string('color');
            $table->string('leave_category');
            $table->boolean('combine_with_other')->default(false);
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
        Schema::dropIfExists('leave_categories');
    }
}
