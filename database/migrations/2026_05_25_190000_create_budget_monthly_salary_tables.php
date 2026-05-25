<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-month salary overrides for the Budget → View Budget page.
 *
 * Until now the page read salaries from a single field on the employee
 * (or the vacant_budget_costs row) and rendered the same value for all 12
 * months. The per-month edit modal therefore appeared to support per-month
 * salaries but was actually overwriting one shared value — every refresh
 * propagated the latest edit to every month.
 *
 * These tables store an optional per-month override. The read path falls
 * back to the shared employee/vacant salary when no override exists, so
 * existing data continues to render correctly.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('resort_employee_monthly_salaries')) {
            Schema::create('resort_employee_monthly_salaries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('employee_id');
                $table->unsignedInteger('resort_id');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month'); // 1-12
                $table->decimal('current_salary', 12, 2)->nullable();
                $table->decimal('proposed_salary', 12, 2)->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'resort_id', 'year', 'month'], 'emp_monthly_salary_unique');
                $table->index(['resort_id', 'year', 'month']);
            });
        }

        if (!Schema::hasTable('resort_vacant_monthly_salaries')) {
            Schema::create('resort_vacant_monthly_salaries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('resort_id');
                $table->unsignedInteger('position_id');
                $table->unsignedInteger('department_id');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('vacant_index');
                $table->unsignedTinyInteger('month'); // 1-12
                $table->decimal('current_salary', 12, 2)->nullable();
                $table->decimal('proposed_salary', 12, 2)->nullable();
                $table->timestamps();

                $table->unique(
                    ['resort_id', 'position_id', 'department_id', 'year', 'vacant_index', 'month'],
                    'vac_monthly_salary_unique'
                );
                $table->index(['resort_id', 'year', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resort_vacant_monthly_salaries');
        Schema::dropIfExists('resort_employee_monthly_salaries');
    }
};
