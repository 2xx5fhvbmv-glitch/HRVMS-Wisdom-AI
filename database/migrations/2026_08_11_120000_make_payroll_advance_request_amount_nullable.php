<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RequestController::RequestStore() only requires (and only ever sends)
 * request_amount for 'Payroll Advance' requests — every other request_type
 * (Employment Verification Letter, etc.) explicitly inserts null for it,
 * matching the validator's own required_if:request_type,Payroll Advance
 * rule. But the column was NOT NULL with no default, so every single
 * non-monetary request type crashed on the very first insert with
 * "Column 'request_amount' cannot be null" — before any attachment
 * handling ever ran. Not a Wasabi/upload issue at all; the request never
 * got created in the first place.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->decimal('request_amount', 12, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->decimal('request_amount', 12, 2)->nullable(false)->change();
        });
    }
};
