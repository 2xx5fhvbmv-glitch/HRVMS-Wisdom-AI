<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frozen at the moment the new hire acknowledges each document
 * (Common::snapshotSignature()) — never re-derived later from the live
 * ResortAdmin.signature_img. Same naming convention as
 * employee_transfers_approval / employee_promotions_approval /
 * interview_assessment_responses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_onboarding_acknowledgements', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('acknowledged_date');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('employee_onboarding_acknowledgements', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
};
