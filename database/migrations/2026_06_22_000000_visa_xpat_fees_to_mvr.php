<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Visa (Xpat Only) cost-config fees are kept in MVR permanently. Existing rows
 * stored as USD/$ are converted to their MVR equivalent (amount x the resort's
 * DollertoMVR rate) with amount_unit set to MVR. This is VALUE-PRESERVING — the
 * budget engine reads amount_unit, so the real USD value is unchanged; only the
 * stored currency representation changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('resort_budget_costs')
            ->where('details', 'Xpat Only')
            ->whereIn('amount_unit', ['$', 'USD'])
            ->get(['id', 'resort_id', 'amount']);

        foreach ($rows as $row) {
            $rate = (float) (DB::table('resort_site_settings')
                ->where('resort_id', $row->resort_id)->value('DollertoMVR') ?: 15.42);
            if ($rate <= 0) {
                $rate = 15.42;
            }
            DB::table('resort_budget_costs')->where('id', $row->id)->update([
                'amount'      => round(((float) $row->amount) * $rate, 2),
                'amount_unit' => 'MVR',
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible: we no longer know which rows were originally USD, and the
        // stored values remain numerically valid as MVR. No-op.
    }
};
