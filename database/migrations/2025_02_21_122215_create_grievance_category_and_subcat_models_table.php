<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceCategoryAndSubcatModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grievance_category_and_subcat_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Grievance_Cat_id');
            $table->unsignedBigInteger('Gri_Sub_cat_id');
            $table->enum('Priority_Level',['High','Low','Medium'])->default('Medium');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable(); 
            $table->timestamps();   
            $table->foreign('Grievance_Cat_id')->references('id')->on('grievance_categories');
            $table->foreign('Gri_Sub_cat_id')->references('id')->on('grievance_subcategories');
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
        Schema::dropIfExists('grievance_category_and_subcat_models');
    }
}
