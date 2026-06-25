<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a "snooze until" date to employee_resignation so HR can defer a
     * pending deposit refund (the "No" action on the deposit-request page).
     * While snooze_until is in the future the row is hidden from the
     * deposit-request list and skipped by the reminder command; once it passes
     * the row reappears and reminders resume.
     */
    public function up(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_resignation', 'deposit_refund_snooze_until')) {
                $table->date('deposit_refund_snooze_until')->nullable()->after('Deposit_Amt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            if (Schema::hasColumn('employee_resignation', 'deposit_refund_snooze_until')) {
                $table->dropColumn('deposit_refund_snooze_until');
            }
        });
    }
};
