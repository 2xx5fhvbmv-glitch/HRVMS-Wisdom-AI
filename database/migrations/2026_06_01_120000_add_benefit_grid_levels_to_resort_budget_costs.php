<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds `benefit_grid_levels` to resort_budget_costs so cost templates can
 * be scoped to specific employee benefit-grade levels (EXCOM, GM, etc.)
 * instead of the all-or-nothing Locals/Xpat/Muslim/Both filter on `details`.
 *
 * Format: comma-separated grade integers matching config/settings.php
 * `eligibilty` map keys:
 *   1 = EXCOM, 2 = HOD, 4 = MGR, 5 = SUP, 6 = LINE WORKERS, 8 = GM
 *
 * Semantics:
 *   NULL or ''        → no grade filter (template applies to every grade)
 *   "1,8"             → only EXCOM and GM employees
 *   "1,2,8"           → EXCOM, HOD and GM
 *
 * The filter intersects with the existing `details` filter (Locals/Xpat/
 * Muslim/Both). An employee must pass BOTH filters to receive the cost.
 *
 * Existing rows are left with NULL on purpose — backward-compatible. HR
 * opens the Cost Templates page and explicitly tightens templates like
 * "Medical Insurance International" to EXCOM+GM as needed; nothing breaks
 * until they do.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resort_budget_costs', function (Blueprint $table) {
            // Comma-separated list of benefit_grid_level ids the template
            // applies to. 50 chars is more than enough — six possible
            // grades, 1-digit each, plus separators.
            $table->string('benefit_grid_levels', 50)
                ->nullable()
                ->after('details')
                ->comment('Comma-separated benefit-grade levels (e.g. "1,8"). NULL = all grades.');
        });
    }

    public function down(): void
    {
        Schema::table('resort_budget_costs', function (Blueprint $table) {
            $table->dropColumn('benefit_grid_levels');
        });
    }
};
