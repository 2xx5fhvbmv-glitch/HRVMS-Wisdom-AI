<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record who marked a Work Permit / Slot fee as Paid (the Finance user who
     * renewed it) so the payment-request details page can show "Renewed by …".
     */
    public function up(): void
    {
        foreach (['work_permits', 'quota_slot_renewals'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'paid_by')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('paid_by')->nullable()->after('Status');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['work_permits', 'quota_slot_renewals'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'paid_by')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('paid_by');
                });
            }
        }
    }
};
