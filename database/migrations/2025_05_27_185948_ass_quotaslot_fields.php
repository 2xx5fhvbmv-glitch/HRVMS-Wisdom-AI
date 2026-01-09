<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AssQuotaslotFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('quota_slot_renewals', function (Blueprint $table) {
            $table->enum('PaymentType',['Lumpsum','Installment'])->default('Installment')->after("Reciept_file");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quota_slot_renewals', function (Blueprint $table) {
            $table->dropColumn('PaymentType');
        });
    }
}
