<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssingAccommodationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assing_accommodations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('available_a_id');
            $table->Integer('emp_id');
            $table->date('effected_date')->default(null);
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('available_a_id')->references('id')->on('available_accommodation_models'); // FK to inventory categories
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assing_accommodations');
    }
}
