<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * budget_statuses already gets one new row per stage transition (HR→
 * Finance, Finance→GM, GM approve, or a revise/reject) — reusing that
 * existing per-stage-event row instead of inventing a new parent/child
 * table, per the e-signature spec's own guidance. Frozen at the moment
 * each stage's actor acts (Common::snapshotSignature()) — never
 * re-derived later from the live ResortAdmin.signature_img.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_statuses', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('OtherComments');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('budget_statuses', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
};
