<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Resync every expat's insurance premium to the budget module's dedicated
 * "Xpat Insurance" ("Expat Insurance", details 'Xpat Only') line.
 *
 * Historically some employee_insurances rows were seeded from the generic
 * "Medical Insurance - International" cost ($3,000), inflating the insurance
 * liability. The Insurance COST already pulls the Xpat Insurance line
 * (Common::VisaRenewalCost), so this aligns the stored premiums with it.
 *
 * Scope: all expat employees' insurance records (incl. Paid), per resort.
 * Resorts without a configured Xpat Insurance amount (>0) are left untouched
 * so we never zero out a real premium.
 *
 * Irreversible data migration — the previous per-row premiums are not stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        $resortIds = DB::table('employee_insurances')
            ->whereNotNull('resort_id')->distinct()->pluck('resort_id');

        foreach ($resortIds as $rid) {
            // The dedicated Xpat-only insurance budget line for this resort.
            $row = DB::table('resort_budget_costs')
                ->where('resort_id', $rid)
                ->where('status', 'active')
                ->where('details', 'Xpat Only')
                ->whereRaw("LOWER(TRIM(particulars)) IN ('expat insurance', 'xpat insurance')")
                ->orderByDesc('updated_at')
                ->first(['amount', 'amount_unit']);

            $amount = (float) ($row->amount ?? 0);
            if (!$row || $amount <= 0) {
                continue; // no Xpat Insurance configured — leave records as-is
            }
            $currency = in_array($row->amount_unit, ['$', 'USD'], true) ? '$' : 'MVR';

            // Only expat employees carry visa insurance liabilities.
            $expatIds = DB::table('employees')
                ->where('resort_id', $rid)
                ->whereRaw("LOWER(TRIM(COALESCE(nationality, ''))) <> 'maldivian'")
                ->pluck('id');

            if ($expatIds->isEmpty()) {
                continue;
            }

            DB::table('employee_insurances')
                ->where('resort_id', $rid)
                ->whereIn('employee_id', $expatIds)
                ->update(['Premium' => $amount, 'Currency' => $currency]);
        }
    }

    public function down(): void
    {
        // Irreversible: prior per-record premiums are not retained.
    }
};
