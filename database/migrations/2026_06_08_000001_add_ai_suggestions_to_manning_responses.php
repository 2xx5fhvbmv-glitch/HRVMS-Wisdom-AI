<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache the AI-generated headcount + salary recommendation for each
 * (department, budget) combination on the Compare Budget page.
 *
 *  ai_suggestions      JSON         — keyed by position_id:
 *      {
 *        "12": {
 *          "suggested_headcount": 1,
 *          "suggested_budget": 1500,
 *          "justification": "Past 3 years averaged 1 manager..."
 *        },
 *        ...
 *      }
 *  ai_suggestions_at   timestamp    — when the cache was populated
 *  ai_suggestions_status varchar    — pending / ready / failed
 *
 * Storing the whole map as JSON (not per-row) keeps the read on
 * CompareBudget to a single column fetch. Regeneration overwrites
 * the whole document.
 *
 * Idempotent: each column is added only if it doesn't already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('manning_responses')) {
            return;
        }
        Schema::table('manning_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('manning_responses', 'ai_suggestions')) {
                $table->json('ai_suggestions')->nullable()->after('total_vacant_positions');
            }
            if (!Schema::hasColumn('manning_responses', 'ai_suggestions_at')) {
                $table->timestamp('ai_suggestions_at')->nullable()->after('ai_suggestions');
            }
            if (!Schema::hasColumn('manning_responses', 'ai_suggestions_status')) {
                $table->string('ai_suggestions_status', 20)->nullable()->after('ai_suggestions_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('manning_responses')) {
            return;
        }
        Schema::table('manning_responses', function (Blueprint $table) {
            foreach (['ai_suggestions_status', 'ai_suggestions_at', 'ai_suggestions'] as $col) {
                if (Schema::hasColumn('manning_responses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
