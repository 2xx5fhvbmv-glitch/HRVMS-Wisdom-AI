<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinalSettlementDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('final_settlement_deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('final_settlement_id');
            $table->unsignedInteger('deduction_id');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('final_settlement_id')->references('id')->on('final_settlements')->onDelete('cascade');
            $table->foreign('deduction_id')->references('id')->on('resort_deductions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('final_settlement_deductions');
    }
}
