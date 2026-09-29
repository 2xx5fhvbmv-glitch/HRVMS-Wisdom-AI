<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S8 — account lockout after repeated failed logins. Both the web portal
 * (ResortLoginController::login()) and the mobile API (Logincontroller::
 * apiLogin()) authenticate against this same resort_admins row (see
 * config/auth.php: 'resort-admin' and 'api' guards share the
 * 'resort-admins' provider), so one pair of columns here covers both.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('resort_admins', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('must_change_password');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    public function down()
    {
        Schema::table('resort_admins', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'locked_until']);
        });
    }
};
