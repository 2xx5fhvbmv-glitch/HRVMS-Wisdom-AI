<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Withdrawing a resignation after both HOD (or XCOM) and HR accepted it —
 * the employee is then Offboarding — needs HOD then HR approval before the
 * employee goes back to Active (founder, 2026-10-05). NULL = no such
 * withdrawal request; withdrawals before acceptance keep the old direct path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->timestamp('withdraw_requested_at')->nullable()->after('withdraw_reason');
            $table->enum('withdraw_hod_status', ['Pending', 'Approved', 'Rejected'])->nullable()->after('withdraw_requested_at');
            $table->unsignedInteger('withdraw_hod_by')->nullable()->after('withdraw_hod_status');
            $table->timestamp('withdraw_hod_at')->nullable()->after('withdraw_hod_by');
            $table->enum('withdraw_hr_status', ['Pending', 'Approved', 'Rejected'])->nullable()->after('withdraw_hod_at');
            $table->unsignedInteger('withdraw_hr_by')->nullable()->after('withdraw_hr_status');
            $table->timestamp('withdraw_hr_at')->nullable()->after('withdraw_hr_by');
            $table->string('withdraw_rejected_reason')->nullable()->after('withdraw_hr_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn([
                'withdraw_requested_at', 'withdraw_hod_status', 'withdraw_hod_by', 'withdraw_hod_at',
                'withdraw_hr_status', 'withdraw_hr_by', 'withdraw_hr_at', 'withdraw_rejected_reason',
            ]);
        });
    }
};
