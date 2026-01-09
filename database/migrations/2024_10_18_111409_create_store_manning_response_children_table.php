<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreManningResponseChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_manning_response_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_SMRP_id');
            $table->unsignedInteger('Emp_id');
            $table->float('Current_Basic_salary');
            $table->float('Proposed_Basic_salary');
            $table->json('Months')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign('Parent_SMRP_id')->references('id')->on('store_manning_response_parents');
            $table->foreign('Emp_id')->references('id')->on('employees');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_manning_response_children');
    }
}
