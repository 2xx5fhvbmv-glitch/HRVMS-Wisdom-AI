<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrects probation letter templates saved with a blank `type`.
 *
 * The Probation Details page's "Send Probation Unsuccessful Letter" button
 * looks up a probation_letter_templates row with type = 'failed'
 * (ProbationController@sendProbationLetter). A template intended as the
 * unsuccessful-probation letter was saved with an empty `type`, so the
 * lookup returned nothing and the endpoint responded 404
 * "Template not found for this resort and type."
 *
 * For each resort that has a blank-type template but NO 'failed' template,
 * the blank one is set to 'failed'. `type` is unique per resort, so only the
 * first blank row per resort is converted (guards against duplicates).
 */
class FixBlankProbationLetterTemplateType extends Migration
{
    public function up()
    {
        $blankTemplates = DB::table('probation_letter_templates')
            ->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($blankTemplates as $template) {
            $resortHasFailed = DB::table('probation_letter_templates')
                ->where('resort_id', $template->resort_id)
                ->where('type', 'failed')
                ->exists();

            if (!$resortHasFailed) {
                DB::table('probation_letter_templates')
                    ->where('id', $template->id)
                    ->update(['type' => 'failed']);
            }
        }
    }

    public function down()
    {
        // No-op — the original (blank) type is not recorded, and reverting a
        // 'failed' template to blank would re-break the unsuccessful-letter
        // lookup.
    }
}
