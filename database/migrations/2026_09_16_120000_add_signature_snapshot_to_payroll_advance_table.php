<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salary Advance/Loan already has a working per-stage approval chain —
 * not a normalized rows-per-step table, but 3 parallel column sets
 * directly on payroll_advance (hr_status/hr_approved_by/hr_action_date,
 * finance_..., gm_...). Adding matching per-stage signature columns
 * (frozen via Common::snapshotSignature() at the moment each stage
 * approves — §6.9 of the e-signature spec) rather than inventing a new
 * table, consistent with that existing convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->string('hr_signature_img')->nullable()->after('hr_action_date');
            $table->string('hr_signature_name')->nullable()->after('hr_signature_img');
            $table->timestamp('hr_signed_at')->nullable()->after('hr_signature_name');

            $table->string('finance_signature_img')->nullable()->after('finance_action_date');
            $table->string('finance_signature_name')->nullable()->after('finance_signature_img');
            $table->timestamp('finance_signed_at')->nullable()->after('finance_signature_name');

            $table->string('gm_signature_img')->nullable()->after('gm_action_date');
            $table->string('gm_signature_name')->nullable()->after('gm_signature_img');
            $table->timestamp('gm_signed_at')->nullable()->after('gm_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->dropColumn([
                'hr_signature_img', 'hr_signature_name', 'hr_signed_at',
                'finance_signature_img', 'finance_signature_name', 'finance_signed_at',
                'gm_signature_img', 'gm_signature_name', 'gm_signed_at',
            ]);
        });
    }
};
