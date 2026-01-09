<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountUnitFieldsToFinalSettlementEarningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('final_settlement_earnings', function (Blueprint $table) {
            $table->enum('amount_unit', ['MVR', 'USD'])->default('MVR')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('final_settlement_earnings', function (Blueprint $table) {
            $table->dropColumn('amount_unit');
        });
    }
}
