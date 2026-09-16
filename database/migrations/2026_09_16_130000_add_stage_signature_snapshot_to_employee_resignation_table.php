<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The generic signature_img/signature_name/signed_at columns already on
 * this table (migration 2026_09_15_100500_...) are used by
 * ExitClearanceController::markAsComplete() for the LATER exit-clearance
 * completion stage. This migration adds SEPARATE per-stage columns for
 * the EARLIER resignation-approval stage (HOD → HR, EmployeeResignation
 * Controller::updateStatus()) — §6.10 of the e-signature spec — so the
 * two lifecycle stages' frozen signatures don't collide/overwrite each
 * other on the same row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->string('hod_signature_img')->nullable()->after('hod_comments');
            $table->string('hod_signature_name')->nullable()->after('hod_signature_img');
            $table->timestamp('hod_signed_at')->nullable()->after('hod_signature_name');

            $table->string('hr_signature_img')->nullable()->after('hr_comments');
            $table->string('hr_signature_name')->nullable()->after('hr_signature_img');
            $table->timestamp('hr_signed_at')->nullable()->after('hr_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn([
                'hod_signature_img', 'hod_signature_name', 'hod_signed_at',
                'hr_signature_img', 'hr_signature_name', 'hr_signed_at',
            ]);
        });
    }
};
