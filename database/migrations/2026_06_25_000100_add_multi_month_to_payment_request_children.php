<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-month Work Permit / Slot fee payments. A payment request can now
     * cover several upcoming months at once: we record how many months were
     * selected and the exact WorkPermit / QuotaSlotRenewal row ids included, so
     * fulfillment can mark all of them Paid in a single action.
     */
    public function up(): void
    {
        Schema::table('payment_request_children', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_request_children', 'WorkPermitMonths')) {
                $table->unsignedInteger('WorkPermitMonths')->default(1)->after('WorkPermitAmt');
            }
            if (!Schema::hasColumn('payment_request_children', 'WorkPermitIds')) {
                $table->text('WorkPermitIds')->nullable()->after('WorkPermitMonths');
            }
            if (!Schema::hasColumn('payment_request_children', 'QuotaslotMonths')) {
                $table->unsignedInteger('QuotaslotMonths')->default(1)->after('QuotaslotAmt');
            }
            if (!Schema::hasColumn('payment_request_children', 'QuotaslotIds')) {
                $table->text('QuotaslotIds')->nullable()->after('QuotaslotMonths');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_children', function (Blueprint $table) {
            foreach (['WorkPermitMonths', 'WorkPermitIds', 'QuotaslotMonths', 'QuotaslotIds'] as $col) {
                if (Schema::hasColumn('payment_request_children', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
