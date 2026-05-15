<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds Status + paid_date columns to the three visa-document renewal tables
 * that previously had no notion of Paid/Unpaid:
 *   - employee_insurances
 *   - work_permit_medical_renewals
 *   - visa_renewals
 *
 * Rationale: the Visa HR Dashboard tab widget computes "Total Paid" per
 * document type, but for these three tables there was no Status filter
 * because the columns didn't exist. work_permits / quota_slot_renewals
 * already have Status enum + Payment_Date, so this migration brings the
 * other three tables to parity.
 *
 * Existing rows are backfilled with Status='Paid' on the assumption that
 * any record currently in these tables represents a completed transaction
 * (an active policy / a conducted medical test / an issued visa).
 * paid_date stays nullable; if you want it set, run a follow-up backfill.
 */
class AddStatusToVisaRenewalTables extends Migration
{
    public function up()
    {
        // Use enum to mirror work_permits.Status (string with two values).
        // Default Paid → existing rows immediately reflect their de-facto
        // state without a separate UPDATE.
        Schema::table('employee_insurances', function (Blueprint $table) {
            $table->enum('Status', ['Pending', 'Paid'])->default('Paid')->after('Premium');
            $table->date('paid_date')->nullable()->after('Status');
        });

        Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
            $table->enum('Status', ['Pending', 'Paid'])->default('Paid')->after('Amt');
            $table->date('paid_date')->nullable()->after('Status');
        });

        Schema::table('visa_renewals', function (Blueprint $table) {
            $table->enum('Status', ['Pending', 'Paid'])->default('Paid')->after('Amt');
            $table->date('paid_date')->nullable()->after('Status');
        });

        // Belt-and-suspenders backfill: any pre-existing rows still showing
        // the column NULL get coerced to Paid. The DEFAULT clause should
        // already handle this on most engines, but older MySQL versions
        // skip the default when the column was added via ->after().
        DB::table('employee_insurances')->whereNull('Status')->update(['Status' => 'Paid']);
        DB::table('work_permit_medical_renewals')->whereNull('Status')->update(['Status' => 'Paid']);
        DB::table('visa_renewals')->whereNull('Status')->update(['Status' => 'Paid']);
    }

    public function down()
    {
        Schema::table('employee_insurances', function (Blueprint $table) {
            $table->dropColumn(['Status', 'paid_date']);
        });

        Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
            $table->dropColumn(['Status', 'paid_date']);
        });

        Schema::table('visa_renewals', function (Blueprint $table) {
            $table->dropColumn(['Status', 'paid_date']);
        });
    }
}
