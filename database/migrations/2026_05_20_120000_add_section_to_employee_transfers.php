<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section is a sub-grouping of a department (resort_sections.dept_id), and
 * the initiate-transfer form needs to capture a target section in addition
 * to the target department/position so HR can move an employee within /
 * across sections. The Employee table already tracks Section_id; mirror it
 * here so we can update the employee's section on final approval and keep
 * a snapshot of where they were transferred from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_transfers', 'current_section_id')) {
                $table->unsignedInteger('current_section_id')->nullable()->after('current_department_id');
            }
            if (!Schema::hasColumn('employee_transfers', 'target_section_id')) {
                $table->unsignedInteger('target_section_id')->nullable()->after('target_department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('employee_transfers', 'current_section_id')) {
                $table->dropColumn('current_section_id');
            }
            if (Schema::hasColumn('employee_transfers', 'target_section_id')) {
                $table->dropColumn('target_section_id');
            }
        });
    }
};
