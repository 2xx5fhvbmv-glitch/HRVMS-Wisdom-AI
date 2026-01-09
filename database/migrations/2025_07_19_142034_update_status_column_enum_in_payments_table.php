<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusColumnEnumInPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            DB::statement("ALTER TABLE payments MODIFY status ENUM(
                'Consented',
                'Pending Consent',
                'Pending',
                'Paid',
                'Partial Paid'
            ) NOT NULL DEFAULT 'Pending Consent'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
              DB::statement("ALTER TABLE payments MODIFY status ENUM(
                'Consent Send',
                'Consented',
                'Pending Consent',
                'Pending',
                'Approved',
                'Paid',
                'Rejected'
            ) NOT NULL DEFAULT 'Pending Consent'"); 
        });
    }
}
