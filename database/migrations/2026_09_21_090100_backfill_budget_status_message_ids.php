<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WP5 — rows written before the message_id fix (see
 * ResortAllNotificationController::ReviseBudget()/SendToFinance()) hold a
 * bogus literal from the old config('settings.Notifications') array (e.g.
 * "5") instead of a real message id (always <2 uppercase letters><digits>,
 * e.g. "DR620587438"). Those rows never joined back to
 * resorts_parent_notifications, so the HOD's dashboard silently showed
 * nothing for that rejection. Backfills each bad row from its own
 * Budget_id's real "Respond to HR" message_id, same lookup
 * Common::resolveMessageIdForBudget() does at request time.
 */
return new class extends Migration
{
    public function up()
    {
        $badRows = DB::table('budget_statuses')
            ->whereRaw("message_id NOT REGEXP '^[A-Z]{2}[0-9]+$'")
            ->whereNotNull('message_id')
            ->where('message_id', '!=', '')
            ->get(['id', 'Budget_id']);

        foreach ($badRows as $row) {
            $realMessageId = DB::table('budget_statuses')
                ->where('Budget_id', $row->Budget_id)
                ->whereNotNull('message_id')
                ->where('message_id', '!=', '')
                ->orderBy('id')
                ->value('message_id');

            // The oldest row for this Budget_id might itself be the bad
            // one being fixed — walk forward to the first row whose
            // message_id actually matches the real pattern.
            if (!$realMessageId || !preg_match('/^[A-Z]{2}[0-9]+$/', $realMessageId)) {
                $realMessageId = DB::table('budget_statuses')
                    ->where('Budget_id', $row->Budget_id)
                    ->whereRaw("message_id REGEXP '^[A-Z]{2}[0-9]+$'")
                    ->orderBy('id')
                    ->value('message_id');
            }

            if ($realMessageId) {
                DB::table('budget_statuses')->where('id', $row->id)->update(['message_id' => $realMessageId]);
            }
        }
    }

    public function down()
    {
        // No-op — the original values were bogus; nothing meaningful to restore.
    }
};
