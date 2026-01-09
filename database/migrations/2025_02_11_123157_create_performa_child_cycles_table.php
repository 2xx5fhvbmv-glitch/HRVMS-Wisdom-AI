<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformaChildCyclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performa_child_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_cycle_id');
            $table->string('Emp_main_id');
            $table->date('Self_review_date')->nullable();
            $table->date('Manager_review_date')->nullable();
            $table->integer('Manager_id')->nullable();
            $table->timestamps();
            $table->foreign('Parent_cycle_id')->references('id')->on('performance_cycles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('performa_child_cycles');
    }
}
