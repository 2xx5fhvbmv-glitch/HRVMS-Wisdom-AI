<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaFeeAmountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_fee_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('nationality')->default(null);
            $table->string('AmountbeforExp')->default(null);
            $table->string('AmountafterExp')->default(null);
            $table->foreign('resort_id')->references('id')->on('resorts');

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
        Schema::dropIfExists('visa_fee_amounts');
    }
}
