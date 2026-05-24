<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline-hire wizard backing table. One row per offline interview record;
 * HR drives a candidate through 5 stages (Applicant Info / Hiring Requisition
 * / Documents / Interview Rounds / Selection + Offer) and the row holds:
 *
 *   - Link to the applicant_form_data row created in Step 1
 *   - Step 2 hiring requisition snapshot (mirrors Add Vacancy)
 *   - Step 4 round outcomes + comments
 *   - Step 5 selection + offer letter + the Employee created on finalize
 *
 * Wizard progress is tracked via `wizard_status` so HR can Save As Draft and
 * resume later. `created_employee_id` is set when Step 5 converts the
 * candidate (mirrors convertApplicant from the online flow).
 */
return new class extends Migration
{
    public function up(): void
    {
        // An earlier stub migration (2024_12_09_170147_create_offline_interviews)
        // created an empty offline_interviews table with only id/timestamps.
        // Drop it (and any FK-dependent child) before recreating with the real schema.
        Schema::dropIfExists('offline_interview_documents');
        Schema::dropIfExists('offline_interviews');

        Schema::create('offline_interviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('applicant_form_data_id')->nullable()
                ->comment('Set after Step 1 — the applicant record holds personal/contact/CV');

            // Wizard progress
            $table->enum('wizard_status', ['Draft','In Progress','Selected','Rejected','Withdrawn'])
                ->default('Draft');
            $table->unsignedTinyInteger('current_step')->default(1)
                ->comment('1..5 — the highest step HR reached');

            // ── Step 2 — Hiring Requisition snapshot ─────────────────────
            $table->enum('budgeted_or_out_of_budget', ['Budgeted','Out of Budget'])->nullable();
            $table->unsignedInteger('division_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('section_id')->nullable();
            $table->unsignedInteger('position_id')->nullable();
            $table->unsignedInteger('reporting_to')->nullable();
            $table->string('position_title')->nullable();
            $table->integer('rank')->nullable();
            $table->date('required_starting_date')->nullable();
            $table->enum('employee_type', ['Permanant','Casual/Agency','Trainee / Intern','Replacement','Temporary / Project'])
                ->nullable();

            // Casual/Agency / temporary block
            $table->string('service_provider_name')->nullable();
            $table->string('salary')->nullable();
            $table->string('food')->nullable();
            $table->string('accommodation')->nullable();
            $table->string('transportation')->nullable();

            // Budget, Funding & Benefits
            $table->decimal('budget_salary', 15, 2)->nullable();
            $table->decimal('proposed_salary', 15, 2)->nullable();
            $table->string('allowances')->nullable();
            $table->string('medical')->nullable();
            $table->string('insurance')->nullable();
            $table->string('pension')->nullable();
            $table->enum('service_charge', ['Yes','No'])->nullable();
            $table->enum('uniform', ['Yes','No'])->nullable();
            $table->string('benefit_accommodation')->nullable();

            // Recruitment — multi-select stored as JSON ["online_posting","recruiter","agency"]
            $table->json('recruitment_methods')->nullable();

            // ── Step 4 — Interview Rounds outcomes ───────────────────────
            $table->boolean('shortlisted_by_ai')->default(false);
            $table->boolean('hr_shortlisted')->default(false);
            $table->enum('hr_round_status', ['Pending','Passed','Failed','Skipped'])->default('Pending');
            $table->enum('hod_round_status', ['Pending','Passed','Failed','Skipped'])->default('Pending');
            $table->enum('gm_round_status', ['Pending','Passed','Failed','Skipped'])->default('Pending');
            $table->text('round_comments')->nullable();

            // ── Step 5 — Selection & Offer ───────────────────────────────
            $table->enum('is_selected', ['Yes','No'])->nullable();
            $table->string('offer_letter_path')->nullable();
            $table->unsignedInteger('created_employee_id')->nullable()
                ->comment('Set after finalize — links to the Employee row created via convertApplicant');

            // Audit
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->timestamps();

            $table->index(['resort_id', 'wizard_status']);
            $table->index('applicant_form_data_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_interviews');
    }
};
