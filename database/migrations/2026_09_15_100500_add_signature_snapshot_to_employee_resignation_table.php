<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frozen at the moment HR marks Exit Clearance complete
 * (Common::snapshotSignature()) — never re-derived later from the live
 * ResortAdmin.signature_img, so the Experience Certificate keeps showing
 * the exact signature used at the time. Same naming convention as
 * employees_leaves_status / employee_transfers_approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('certificate_issue');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
};
