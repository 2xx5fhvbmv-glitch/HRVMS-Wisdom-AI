<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache the AI-generated explanation, remediation, and severity for every
 * compliance breach so HR sees actionable context instead of a single
 * hardcoded sentence.
 *
 *   description_ai   TEXT          — richer "why this matters" narrative
 *   remediation_ai   TEXT          — concrete suggested remediation
 *   severity_ai      varchar(20)   — Critical / High / Medium / Low / Info
 *   ai_status        varchar(20)   — pending / ready / failed
 *   ai_generated_at  timestamp
 *
 * Both ai_status='ready' and the absence of description_ai are valid: the
 * view falls back to the hardcoded description column whenever
 * description_ai is empty, so existing rows render correctly and a failed
 * AI call simply doesn't change what the user sees.
 *
 * Idempotent: each column is added only if missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compliances')) {
            return;
        }
        Schema::table('compliances', function (Blueprint $table) {
            if (!Schema::hasColumn('compliances', 'description_ai')) {
                $table->text('description_ai')->nullable()->after('description');
            }
            if (!Schema::hasColumn('compliances', 'remediation_ai')) {
                $table->text('remediation_ai')->nullable()->after('description_ai');
            }
            if (!Schema::hasColumn('compliances', 'severity_ai')) {
                $table->string('severity_ai', 20)->nullable()->after('remediation_ai');
            }
            if (!Schema::hasColumn('compliances', 'ai_status')) {
                $table->string('ai_status', 20)->nullable()->after('severity_ai');
            }
            if (!Schema::hasColumn('compliances', 'ai_generated_at')) {
                $table->timestamp('ai_generated_at')->nullable()->after('ai_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('compliances')) {
            return;
        }
        Schema::table('compliances', function (Blueprint $table) {
            foreach (['ai_generated_at', 'ai_status', 'severity_ai', 'remediation_ai', 'description_ai'] as $col) {
                if (Schema::hasColumn('compliances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
