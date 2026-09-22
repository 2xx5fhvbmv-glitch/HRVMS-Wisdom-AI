<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoldUntilToMaintanaceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->date('hold_until')->nullable()->after('ReasonOnHold');
            // Idempotency marker for the hold-expiry cron, same pattern as
            // escalation_notified_at above it — cleared whenever a fresh
            // On-Hold is set so the case can fire again on its next expiry.
            $table->timestamp('hold_expiry_notified_at')->nullable()->after('hold_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintanace_requests', function (Blueprint $table) {
            $table->dropColumn(['hold_until', 'hold_expiry_notified_at']);
        });
    }
}
