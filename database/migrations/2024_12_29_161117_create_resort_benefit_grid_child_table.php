<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResortBenefitGridChildTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resort_benefit_grid_child', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('benefit_grid_id');
            $table->unsignedInteger('leave_cat_id');
            $table->integer('rank');
            $table->integer('allocated_days');
            $table->string('eligible_emp_type');
            $table->timestamps();

            $table->foreign('benefit_grid_id')->references('id')->on('resort_benifit_grid');
            $table->foreign('leave_cat_id')->references('id')->on('leave_categories');
            

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resort_benefit_grid_child');
    }
}
