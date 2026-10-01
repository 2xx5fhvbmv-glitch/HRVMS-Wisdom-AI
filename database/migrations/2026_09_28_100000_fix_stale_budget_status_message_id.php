<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * budget_statuses id=69 (Budget_id=62, resort_id=26) was left with the same
 * bogus literal message_id ('5') the 2026_09_21_090100 backfill migration
 * fixed for every OTHER row — that migration could only borrow a valid
 * message_id from a sibling row sharing the same Budget_id, and Budget_id 62
 * has no other row to borrow from. Common::resolveMessageIdForBudget() and
 * every other read site already treat "no message_id" as a normal null/
 * not-found case, so nulling it out is safe (see that helper + every
 * budget_statuses.message_id read site).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('budget_statuses', function (Blueprint $table) {
            $table->string('message_id')->nullable()->change();
        });

        DB::table('budget_statuses')->where('id', 69)->update(['message_id' => null]);
    }

    public function down()
    {
        // The original value ('5') was bogus, not a real message id —
        // nothing meaningful to restore (same stance as the migration
        // that fixed every other row of this kind).
    }
};
