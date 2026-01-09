<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisaHistoryField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visa_transection_histories', function (Blueprint $table) 
        {
            $table->string('Employee_id')->nullable()->after('from_wallet_realAmt');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visa_transection_histories', function (Blueprint $table) {
            $table->dropColumn('Employee_id')->nullable();


            
            
        });
    }
}
