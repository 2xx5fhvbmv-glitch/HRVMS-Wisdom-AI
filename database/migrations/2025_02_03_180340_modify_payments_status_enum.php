<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyPaymentsStatusEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('Consent Send','Consented','Pending Consent','Pending','Approved','Paid','Partial Paid') NOT NULL DEFAULT 'Pending'");
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
            DB::statement("ALTER TABLE payments MODIFY status ENUM('Consent Send','Consented','Pending Consent','Pending','Approved','Paid') NOT NULL DEFAULT 'Pending'");
        });
    }
}
