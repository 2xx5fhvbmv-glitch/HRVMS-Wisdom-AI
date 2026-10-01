<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix, not schema: SOS events 448, 450, 451, 453 are July drills that
 * never got a Drill-Completed transition — there is no API path that sets
 * that status (completeSOSUpdateStatus only ever writes 'Completed'), so
 * they've sat as Drill-Active for months, making the live-incident card pick
 * whichever one initiates. Closing them out mirrors what
 * completeSOSUpdateStatus() does for a real completion (status flip + the
 * same two child_sos_history_status timeline rows) so dashboard views that
 * read that timeline stay consistent.
 */
class CloseStaleDrillActiveSosEvents extends Migration
{
    private array $sosIds = [448, 450, 451, 453];

    public function up()
    {
        $ids = DB::table('sos_history')
            ->whereIn('id', $this->sosIds)
            ->where('status', 'Drill-Active')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('sos_history')
            ->whereIn('id', $ids)
            ->update(['status' => 'Drill-Completed', 'updated_at' => now()]);

        $now = now();
        $childRows = [];
        foreach ($ids as $id) {
            $childRows[] = ['sos_history_id' => $id, 'sos_status' => 'situation_was_marked_as_under_control', 'created_at' => $now, 'updated_at' => $now];
            $childRows[] = ['sos_history_id' => $id, 'sos_status' => 'sos_completed', 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('child_sos_history_status')->insert($childRows);
    }

    public function down()
    {
        // Data fix only — not reversible to the prior (stuck) state.
    }
}
