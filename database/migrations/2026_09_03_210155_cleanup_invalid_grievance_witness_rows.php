<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Both grievance-witness creation paths (mobile API GrievanceController and
 * web portal Resorts\...\GrivanceController) now validate that a witness id
 * belongs to the same resort as the grievance itself, but that check didn't
 * always exist — a handful of witness rows created before the fix reference
 * an employee from a DIFFERENT resort, or an employee id that doesn't exist
 * at all. Those can never resolve to a real person, so
 * API\GrievanceController::grievanceDetail() correctly returns null
 * name/photo for them ("witness name and photo are not coming in API").
 * Deletes exactly those already-broken rows rather than guessing a
 * replacement identity — condition-based (not a hardcoded id list) so it's
 * safe to run unchanged on any environment.
 */
class CleanupInvalidGrievanceWitnessRows extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('grivance_submission_witnesses') || !Schema::hasTable('grivance_submission_models')) {
            return;
        }

        DB::table('grivance_submission_witnesses as w')
            ->join('grivance_submission_models as g', 'g.id', '=', 'w.G_S_Parent_id')
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('employees as e')
                    ->whereColumn('e.id', 'w.Witness_id')
                    ->whereColumn('e.resort_id', 'g.resort_id')
                    ->whereNull('e.deleted_at');
            })
            ->delete();
    }

    /**
     * Irreversible — the deleted rows referenced employees that were never
     * valid for their grievance's resort, so there's no correct state to
     * restore to.
     */
    public function down()
    {
        //
    }
}
