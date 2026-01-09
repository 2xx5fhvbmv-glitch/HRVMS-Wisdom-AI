<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResginationField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('employee_resignation', function (Blueprint $table) 
        {
            $table->enum('Deposit_withdraw',['Yes','No'])->nullable()->after('withdraw_reason');
            $table->decimal('Deposit_Amt', 10, 2)->nullable()->after('Deposit_withdraw');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_resignation', function (Blueprint $table) 
        {
            $table->dropColumn('Deposit_withdraw');
            $table->dropColumn('Deposit_Amt');
        });
    }
}
