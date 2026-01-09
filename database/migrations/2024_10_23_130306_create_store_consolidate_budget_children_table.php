<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreConsolidateBudgetChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_consolidate_budget_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger( 'Parent_SCB_id');
            $table->json('header')->nullable();
            $table->json('Data')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign( 'Parent_SCB_id')->references('id')->on('store_consolidate_budget_parents');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_consolidate_budget_children');
    }
}
