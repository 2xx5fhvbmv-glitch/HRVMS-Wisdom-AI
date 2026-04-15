<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalWorkflowToMonthlyCheckingModels extends Migration
{
    public function up()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_checking_models', 'approval_status')) {
                $table->string('approval_status', 20)->default('pending')->after('status');
            }
            if (!Schema::hasColumn('monthly_checking_models', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('monthly_checking_models', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('monthly_checking_models', 'employee_rejection_reason')) {
                $table->text('employee_rejection_reason')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('monthly_checking_models', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('employee_rejection_reason');
            }
        });

        // Stage-2 fields must allow NULL so a stage-1 request can be stored before approval.
        // Use raw SQL since Doctrine DBAL may not be installed.
        $nullableCols = ['Area_of_Discussion', 'Area_of_Improvement', 'Time_Line', 'comment', 'tranining_id'];
        foreach ($nullableCols as $col) {
            if (Schema::hasColumn('monthly_checking_models', $col)) {
                try {
                    $type = $col === 'tranining_id' ? 'BIGINT UNSIGNED NULL' : 'TEXT NULL';
                    \DB::statement("ALTER TABLE monthly_checking_models MODIFY `{$col}` {$type}");
                } catch (\Exception $e) {
                    // ignore — existing column definition may already allow nulls
                }
            }
        }
    }

    public function down()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
            $cols = ['approval_status', 'approved_at', 'rejected_at', 'employee_rejection_reason', 'finalized_at'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('monthly_checking_models', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
}
