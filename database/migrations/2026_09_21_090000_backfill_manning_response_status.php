<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A2 — manning_responses.status was only ever written by store()/saveDraft()
 * after WP4's fix; rows created before that are '' (or NULL). saveDraft()'s
 * strict `=== 'submitted'` check let a legacy '' row fall through to its
 * updateOrCreate, silently demoting an already-submitted request back to
 * 'draft' and wiping its position_monthly_data on the next tab switch
 * (ManningResponseController::saveDraft() now also hardened to treat
 * anything that isn't explicitly 'draft' as already submitted — this
 * migration is the one-time data fix, that guard is the ongoing one).
 *
 * A row is "submitted" if it has a budget_statuses row with the
 * 'Respond to HR' marker store() writes at submission time; everything
 * else legacy is a genuinely abandoned draft.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('manning_responses')
            ->whereIn('id', function ($q) {
                $q->select('Budget_id')->from('budget_statuses')->where('comments', 'Respond to HR');
            })
            ->where(function ($q) {
                $q->where('status', '')->orWhereNull('status');
            })
            ->update(['status' => 'submitted']);

        DB::table('manning_responses')
            ->where(function ($q) {
                $q->where('status', '')->orWhereNull('status');
            })
            ->update(['status' => 'draft']);
    }

    public function down()
    {
        // No-op — reversing would re-corrupt real submitted/draft state
        // with no way to tell which rows this migration actually touched.
    }
};
