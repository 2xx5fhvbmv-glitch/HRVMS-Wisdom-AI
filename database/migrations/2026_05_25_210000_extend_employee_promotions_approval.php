<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Two corrections to employee_promotions_approval:
 *
 *   1) approval_rank was ENUM('Finance','GM') — but the controller now also
 *      writes 'HOD' as the first stage in the approval chain. MySQL silently
 *      drops the value with that constrained enum, so HOD rows never
 *      persist correctly. Widen the enum to include 'HOD' (and 'HR' for
 *      forward-compat with an HR final-approver step).
 *
 *   2) Add approved_at — handlePromotionApproval calls
 *      $currentApproval->update(['approved_at' => now(), ...]) but the
 *      column doesn't exist, so the audit trail had no "when did they act"
 *      timestamp. Backfill existing non-Pending rows from updated_at.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Widen the enum to include HOD + HR.
        DB::statement("ALTER TABLE employee_promotions_approval
            MODIFY approval_rank ENUM('HOD','Finance','GM','HR') NOT NULL");

        // 2) Add approved_at timestamp + backfill.
        Schema::table('employee_promotions_approval', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_promotions_approval', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('remarks');
            }
        });

        DB::statement("UPDATE employee_promotions_approval
            SET approved_at = updated_at
            WHERE approved_at IS NULL AND status <> 'Pending'");
    }

    public function down(): void
    {
        Schema::table('employee_promotions_approval', function (Blueprint $table) {
            if (Schema::hasColumn('employee_promotions_approval', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        DB::statement("ALTER TABLE employee_promotions_approval
            MODIFY approval_rank ENUM('Finance','GM') NOT NULL");
    }
};
