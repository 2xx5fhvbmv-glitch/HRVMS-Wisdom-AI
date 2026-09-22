<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Housekeeping/Housekeeping Request combine (Trello: "Housekeeping –
 * Combine Housekeeping and Housekeeping Request"). The unified web-portal
 * flow needs a Date/Time step that housekeeping_requests never had (mobile
 * only ever collected free-text remarks). Also widens employee_id to
 * nullable — the unified flow allows HR to raise a request against a
 * Building/Room with no employee selected at all.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('remarks');
            $table->time('scheduled_time')->nullable()->after('scheduled_date');
        });

        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->dropColumn(['scheduled_date', 'scheduled_time']);
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });
    }
};
