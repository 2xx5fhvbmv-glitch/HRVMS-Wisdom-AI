<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix, not schema: follow-up to 2026_09_29_130000. Resort 26 still has
 * July-Aug SOS events left open (447, 452, 454-458 Drill-Active; 449 Real-Active locally,
 * already closed on prod — status guard makes it a no-op there),
 * and Common::activeSosForResort() surfaces the newest one as the live SOS,
 * blocking new ones. Close them the same way completeSOSUpdateStatus() does
 * (status flip + the two child_sos_history_status timeline rows).
 */
class CloseRemainingStaleSosEvents extends Migration
{
    private array $drillIds = [447, 452, 454, 455, 456, 457, 458];
    private array $realIds = [449];

    public function up()
    {
        $drill = DB::table('sos_history')->whereIn('id', $this->drillIds)->where('status', 'Drill-Active')->pluck('id');
        $real = DB::table('sos_history')->whereIn('id', $this->realIds)->where('status', 'Real-Active')->pluck('id');

        if ($drill->isNotEmpty()) {
            DB::table('sos_history')->whereIn('id', $drill)->update(['status' => 'Drill-Completed', 'updated_at' => now()]);
        }
        if ($real->isNotEmpty()) {
            DB::table('sos_history')->whereIn('id', $real)->update(['status' => 'Completed', 'updated_at' => now()]);
        }

        $now = now();
        $childRows = [];
        foreach ($drill->merge($real) as $id) {
            $childRows[] = ['sos_history_id' => $id, 'sos_status' => 'situation_was_marked_as_under_control', 'created_at' => $now, 'updated_at' => $now];
            $childRows[] = ['sos_history_id' => $id, 'sos_status' => 'sos_completed', 'created_at' => $now, 'updated_at' => $now];
        }
        if ($childRows) {
            DB::table('child_sos_history_status')->insert($childRows);
        }
    }

    public function down()
    {
        // Data fix only — not reversible to the prior (stuck) state.
    }
}
