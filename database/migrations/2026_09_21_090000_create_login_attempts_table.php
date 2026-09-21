<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security hardening review (S10): no failed-login audit trail existed
 * anywhere — a burst of failed attempts against any of the four login
 * surfaces (resort/admin/shopkeeper web, mobile) was invisible after the
 * fact. This table is written to (success and failure both) from each
 * login controller; nothing reads it automatically yet — alerting on
 * bursts is a separate follow-up (Sentry is already integrated).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('portal', 20); // resort | admin | shopkeeper | mobile
            $table->string('identifier', 191); // email or emp_id, as submitted — not validated/normalized
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['portal', 'identifier', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_attempts');
    }
};
