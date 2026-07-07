<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VacancyController's approval-chain creation used `key < FinalApproval` plus
 * a hardcoded {"3":"HR","8":"GM"} union, which always force-added a GM row
 * and never added a Finance row — so any resort configured with Finance (7)
 * as its final-approval rank got {HR, GM} instead of {HR, Finance} for every
 * vacancy, silently skipping Finance approval entirely (code fix landed
 * separately in VacancyController).
 *
 * Backfills ONLY the exact bug signature: an untouched GM (8) row still
 * sitting at status='Active' (never approved/forwarded) with no Finance (7)
 * row at all for that parent, on a resort whose configured FinalApproval is
 * 7 (Finance), 8 (GM), or unset (null — e.g. resort 26, which never
 * configured this and must default to the full chain, same as the
 * VacancyController fix). Deliberately narrow — does NOT touch parents that
 * are already Approved/Rejected/ForwardedToNext, so already-closed vacancies
 * are never reopened.
 */
class BackfillMissingApprovalChainStages extends Migration
{
    public function up()
    {
        $resorts = DB::table('job_advertisements')
            ->select('Resort_id', 'FinalApproval')
            ->where(function ($q) {
                $q->whereIn('FinalApproval', [7, 8])->orWhereNull('FinalApproval');
            })
            ->get();

        $inserted = 0;
        foreach ($resorts as $resort) {
            $parentIds = DB::table('t_anotification_parents')
                ->where('Resort_id', $resort->Resort_id)
                ->pluck('id');

            if ($parentIds->isEmpty()) {
                continue;
            }

            // Parents with an untouched GM row (the bug's tell-tale sign)
            // and no Finance row at all.
            $affectedParentIds = DB::table('t_anotification_children')
                ->whereIn('Parent_ta_id', $parentIds)
                ->where('Approved_By', 8)
                ->where('status', 'Active')
                ->pluck('Parent_ta_id')
                ->diff(
                    DB::table('t_anotification_children')
                        ->whereIn('Parent_ta_id', $parentIds)
                        ->where('Approved_By', 7)
                        ->pluck('Parent_ta_id')
                );

            foreach ($affectedParentIds as $parentId) {
                DB::table('t_anotification_children')->insert([
                    'Parent_ta_id' => $parentId,
                    'status'       => 'Active',
                    'Approved_By'  => 7,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $inserted++;
            }
        }

        \Log::info("BackfillMissingApprovalChainStages: inserted {$inserted} missing Finance approval rows.");
    }

    public function down()
    {
        // Not reversible — we don't record which rows this migration
        // inserted vs already existed. Re-running up() is a no-op once the
        // gap is closed (idempotent), so there is no data-loss risk in
        // leaving down() as a no-op.
    }
}
