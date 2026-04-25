<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Some performance form rows were saved with the form_structure column
 * double-encoded (json_encode(json_encode($arr))). After one json_decode they
 * are still a JSON string, so the front-end's `Array.isArray(formStructure)`
 * check silently bails and the form renders empty.
 *
 * Re-save any double-encoded rows as single-encoded so a single json_decode
 * yields a proper array. Idempotent: rows already in good shape are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'ninty_day_peformance_forms' => 'form_structure',
            'professionalforms'          => 'form_structure',
            'performance_template_forms' => 'form_structure',
            'training_feedback_forms'    => 'form_structure',
            'training_evaluation_forms'  => 'form_structure',
        ];

        foreach ($tables as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $rows = DB::table($table)->select('id', $column)->get();
            foreach ($rows as $row) {
                $raw = $row->{$column};
                if (empty($raw)) continue;

                $first = json_decode($raw, true);
                // Already a proper array on single decode? — leave it alone.
                if (is_array($first)) continue;

                // Otherwise try a second decode; if that gives us an array,
                // re-save as single-encoded.
                if (is_string($first)) {
                    $second = json_decode($first, true);
                    if (is_array($second)) {
                        DB::table($table)->where('id', $row->id)->update([
                            $column => json_encode($second),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No-op — we won't re-introduce double encoding.
    }
};
