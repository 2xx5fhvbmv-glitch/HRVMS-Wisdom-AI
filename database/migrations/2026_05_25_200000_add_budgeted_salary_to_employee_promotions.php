<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot of the budgeted salary for the target position at the moment
 * the promotion was submitted. Used by the approval / detail views to show
 * the same "exceeds budget" warning that HR saw on the initiate form, even
 * if the underlying budget rows change later.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_promotions', 'budgeted_salary')) {
                $table->decimal('budgeted_salary', 12, 2)->nullable()->after('new_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            if (Schema::hasColumn('employee_promotions', 'budgeted_salary')) {
                $table->dropColumn('budgeted_salary');
            }
        });
    }
};
