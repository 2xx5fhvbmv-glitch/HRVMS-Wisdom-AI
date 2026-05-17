<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds columns to `employee_transfers` to support:
 *  - Item 2: budgeted_salary (snapshot of the target position's budgeted
 *            basic salary at initiation time) + proposed_salary (the salary
 *            the initiator proposes for the transferred employee).
 *  - Item 3: temporary_from / temporary_to — the period of a Temporary
 *            transfer. NULL for Permanent transfers.
 *
 * All columns are nullable so existing rows are unaffected.
 */
class AddSalaryAndTemporaryPeriodToEmployeeTransfers extends Migration
{
    public function up()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_transfers', 'budgeted_salary')) {
                $table->decimal('budgeted_salary', 15, 2)->nullable()->after('reporting_manager');
            }
            if (!Schema::hasColumn('employee_transfers', 'proposed_salary')) {
                $table->decimal('proposed_salary', 15, 2)->nullable()->after('budgeted_salary');
            }
            if (!Schema::hasColumn('employee_transfers', 'temporary_from')) {
                $table->date('temporary_from')->nullable()->after('proposed_salary');
            }
            if (!Schema::hasColumn('employee_transfers', 'temporary_to')) {
                $table->date('temporary_to')->nullable()->after('temporary_from');
            }
        });
    }

    public function down()
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            foreach (['budgeted_salary', 'proposed_salary', 'temporary_from', 'temporary_to'] as $col) {
                if (Schema::hasColumn('employee_transfers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
