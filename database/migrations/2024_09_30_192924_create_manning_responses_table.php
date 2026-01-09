<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManningResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manning_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('dept_id');
            $table->year('year'); // To store the year (e.g., 2025)
            $table->string('status');
            $table->unsignedInteger('total_headcount')->default(0); // Add total headcount
            $table->unsignedInteger('total_filled_positions')->default(0); // Add filled positions
            $table->unsignedInteger('total_vacant_positions')->default(0); // Add vacant positions
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
        Schema::dropIfExists('manning_responses');
    }
}
