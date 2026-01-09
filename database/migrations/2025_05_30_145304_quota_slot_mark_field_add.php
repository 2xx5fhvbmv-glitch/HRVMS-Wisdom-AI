<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuotaSlotMarkFieldAdd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      
        Schema::table('quota_slot_renewals', function (Blueprint $table) {
            $table->string('ReceiptNumber')->nullable()->after('Reciept_file');
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
            $table->dropColumn('ReceiptNumber');
        });
    }
}
