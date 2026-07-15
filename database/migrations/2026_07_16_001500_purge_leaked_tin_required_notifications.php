<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PurgeLeakedTinRequiredNotifications extends Migration
{
    /**
     * ComplianceController's TIN/salary compliance check was missing a
     * resort_id scope on its Employee::with([...]) query, so every
     * resort's compliance run pulled EVERY employee in the whole
     * database and generated "TIN Required for Employee" notifications
     * naming employees who belong to entirely different resorts. The
     * query itself has since been fixed (resort_id scope added), so no
     * new leaked rows are created — but the rows already written before
     * that fix are still sitting in resort_notifications and still
     * showing up in the mobile app's Notification list.
     *
     * Purges every existing row of this notification type rather than
     * trying to reverse-engineer which ones are cross-resort from
     * unstructured message text — the now-correctly-scoped compliance
     * check will regenerate an accurate one for any employee still
     * genuinely missing a TIN on its next run, so no real signal is
     * lost, just the stale/leaked noise.
     */
    public function up()
    {
        DB::table('resort_notifications')->where('type', 'TIN Required for Employee')->delete();
    }

    public function down()
    {
        // Not reversible — the purged rows named employees from the
        // wrong resort and shouldn't be restored.
    }
}
