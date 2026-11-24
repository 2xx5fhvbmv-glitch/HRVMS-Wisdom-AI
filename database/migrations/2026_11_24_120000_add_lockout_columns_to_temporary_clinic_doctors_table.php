<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-account lockout for the temporary clinic doctor mobile login
 * (TemporaryClinicDoctorAuthController::login), same columns and types as
 * resort_admins so Common::isAccountLocked/registerFailedLogin work on it.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('temporary_clinic_doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('temporary_clinic_doctors', 'failed_login_attempts')) {
                $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            }
            if (!Schema::hasColumn('temporary_clinic_doctors', 'locked_until')) {
                $table->timestamp('locked_until')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('temporary_clinic_doctors', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'locked_until']);
        });
    }
};
