<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `vacancy_id` (FK -> vacancies.id) to the employees table.
 *
 * Purpose: track which vacancy filled each employee slot so we can
 *   1. compute remaining slots per vacancy
 *      (Total_position_required − COUNT(employees.vacancy_id))
 *   2. hide fully-filled vacancies from the "Hire against a vacancy"
 *      picker on /resort/people/employees/create
 *   3. server-side reject a hire if the chosen vacancy has 0 slots left
 *
 * NULL is allowed for backward-compatibility (every pre-existing
 * employee row + any future direct hire that doesn't go through TA).
 * The HR-side requirement that "Hire against a vacancy" is mandatory
 * is enforced in the controller, not at the DB level — Onboarding
 * flows that bypass TA still need to insert employees without a
 * vacancy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('vacancy_id')
                ->nullable()
                ->after('Position_id')
                ->comment('FK to vacancies.id. NULL = direct hire (not TA-driven).');
            $table->index('vacancy_id', 'employees_vacancy_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_vacancy_id_idx');
            $table->dropColumn('vacancy_id');
        });
    }
};
