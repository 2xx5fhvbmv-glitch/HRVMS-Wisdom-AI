<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin hardening (advisory 2026-09-29, decisions A/E/F):
 * - lockout columns mirror the resort_admins S8 pair (same Common helpers);
 * - TOTP secret + recovery codes are stored encrypted (Admin model casts);
 * - admin_audit_logs records every state-changing admin request.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_email', 191)->nullable();
            $table->string('method', 10);
            $table->string('route_name', 191)->nullable();
            $table->string('url', 2048);
            $table->text('payload')->nullable(); // redacted request input, truncated
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'locked_until', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
