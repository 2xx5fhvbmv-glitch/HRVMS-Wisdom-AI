<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The previous backfill (2026_07_07_000001) targeted "GM row Active + no
 * Finance row", which matches every vacancy hit by the original bug —
 * including ones still genuinely awaiting HR approval (e.g. Carpenter).
 * It never checked whether HR had actually approved first, so those got a
 * Finance row inserted too, wrongly surfacing them in Finance's queue
 * before HR had even acted.
 *
 * This removes ONLY the Finance (7) rows inserted by that backfill where
 * the sibling HR (3) row for the same parent is still status='Active'
 * (i.e. HR has not approved). Rows where HR already shows 'Approved'
 * (e.g. Electrician, Commis) are correct and left untouched.
 */
class RemovePrematureFinanceBackfillRows extends Migration
{
    public function up()
    {
        $prematureFinanceIds = DB::table('t_anotification_children as fin')
            ->join('t_anotification_children as hr', function ($join) {
                $join->on('hr.Parent_ta_id', '=', 'fin.Parent_ta_id')
                    ->where('hr.Approved_By', 3);
            })
            ->where('fin.Approved_By', 7)
            ->where('fin.status', 'Active')
            ->where('hr.status', 'Active') // HR hasn't approved yet
            ->pluck('fin.id');

        $deleted = DB::table('t_anotification_children')
            ->whereIn('id', $prematureFinanceIds)
            ->delete();

        \Log::info("RemovePrematureFinanceBackfillRows: deleted {$deleted} premature Finance rows.");
    }

    public function down()
    {
        // Not reversible — see prior migration's down() for the same reasoning.
    }
}
