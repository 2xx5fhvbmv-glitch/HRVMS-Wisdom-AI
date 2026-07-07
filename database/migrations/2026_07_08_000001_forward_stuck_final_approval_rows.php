<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Common::TaFinalApproval() returned null for any resort without an explicit
 * job_advertisements.FinalApproval (e.g. resort 26), so in
 * ConfigController@TaApprovedVcanciesNotification the check
 * `$effectiveRank == Common::TaFinalApproval($resort_id)` was always false —
 * even when the approver WAS the resort's actual final rank (GM=8). That
 * routed the final approval into the else-branch, leaving the row at
 * status='Approved' instead of 'ForwardedToNext' and skipping the
 * application_links row that Vacancies-widget / To-Do-List queries require
 * (t3.status='ForwardedToNext' AND t3.Approved_By=<final rank>).
 *
 * TaFinalApproval() now defaults to 8 when unset (code fix, separate
 * commit). This backfills the rows already stuck in the old broken state:
 * any child row at the resort's final rank with status='Approved' and no
 * application_links row yet.
 */
class ForwardStuckFinalApprovalRows extends Migration
{
    public function up()
    {
        $resorts = DB::table('job_advertisements')->select('Resort_id', 'FinalApproval')->get();
        $finalByResort = [];
        foreach ($resorts as $r) {
            $finalByResort[$r->Resort_id] = $r->FinalApproval ?? 8;
        }

        $vacancyResortIds = DB::table('vacancies')->distinct()->pluck('Resort_id');
        foreach ($vacancyResortIds as $resortId) {
            if (!isset($finalByResort[$resortId])) {
                $finalByResort[$resortId] = 8;
            }
        }

        $forwarded = 0;
        foreach ($finalByResort as $resortId => $finalRank) {
            $stuckChildIds = DB::table('t_anotification_children as c')
                ->join('t_anotification_parents as p', 'p.id', '=', 'c.Parent_ta_id')
                ->join('vacancies as v', 'v.id', '=', 'p.V_id')
                ->where('v.Resort_id', $resortId)
                ->where('c.Approved_By', $finalRank)
                ->where('c.status', 'Approved')
                ->leftJoin('application_links as al', 'al.ta_child_id', '=', 'c.id')
                ->whereNull('al.id')
                ->pluck('c.id');

            foreach ($stuckChildIds as $childId) {
                DB::table('t_anotification_children')->where('id', $childId)->update(['status' => 'ForwardedToNext']);
                DB::table('application_links')->updateOrInsert(
                    ['ta_child_id' => $childId, 'Resort_id' => $resortId],
                    ['ta_child_id' => $childId, 'Resort_id' => $resortId, 'Old_ExpiryDate' => now(), 'created_at' => now(), 'updated_at' => now()]
                );
                $forwarded++;
            }
        }

        \Log::info("ForwardStuckFinalApprovalRows: forwarded {$forwarded} stuck final-approval rows.");
    }

    public function down()
    {
        // Not reversible — see prior migrations' down() for the same reasoning.
    }
}
