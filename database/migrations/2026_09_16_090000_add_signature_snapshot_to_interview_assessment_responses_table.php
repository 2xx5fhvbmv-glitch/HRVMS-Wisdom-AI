<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * interviewer_signature already existed but held a LIVE path straight
 * from ResortAdmin.signature_img (re-read on every render, not frozen) —
 * repurposed to hold the Common::snapshotSignature() frozen copy instead
 * (see InterviewAssessmentController::saveResponse()). signature_name/
 * signed_at added alongside, same naming convention as
 * employee_transfers_approval / employee_promotions_approval /
 * people_salary_increment_status / t_anotification_children.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_assessment_responses', function (Blueprint $table) {
            $table->string('signature_name')->nullable()->after('interviewer_signature');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('interview_assessment_responses', function (Blueprint $table) {
            $table->dropColumn(['signature_name', 'signed_at']);
        });
    }
};
