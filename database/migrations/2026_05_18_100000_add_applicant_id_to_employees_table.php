<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds employees.applicant_id so that an Employee auto-created from a Talent
 * Acquisition applicant (during onboarding Step 1) can be linked back to its
 * source applicant_form_data row. This is what makes the "auto-create on
 * select" flow idempotent: if the same accepted applicant is selected again
 * we reuse the existing Employee instead of creating a duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'applicant_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('applicant_id')->nullable()->after('Emp_id');
                $table->index('applicant_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'applicant_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['applicant_id']);
                $table->dropColumn('applicant_id');
            });
        }
    }
};
