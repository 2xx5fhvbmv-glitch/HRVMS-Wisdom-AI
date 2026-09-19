<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the Casual/Intern bulk-import template's VisaExpiryDate/
 * WorkPermitExpiryDate columns (replacing WorkPermitNumber) — no existing
 * column captures a plain "current expiry" date per employee. Note:
 * CheckVisaExpiryReminders (the expiry-reminder engine) reads
 * visa_renewals/work_permits/etc, not these columns — an import here
 * doesn't feed that pipeline; that'd be a separate change if wanted.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('visa_expiry_date')->nullable()->after('passport_number');
            $table->date('work_permit_expiry_date')->nullable()->after('visa_expiry_date');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['visa_expiry_date', 'work_permit_expiry_date']);
        });
    }
};
