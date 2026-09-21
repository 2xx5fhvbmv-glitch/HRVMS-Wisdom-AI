<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security hardening review (S4) — registration emails still send a
 * plaintext generated password (fixing that outright needs a one-time
 * set-password-link redesign across 5 call sites, real feature scope).
 * The review's accepted middle-ground fix: keep the temporary password,
 * but force a change at first login. This column is that flag; no code
 * anywhere checked for one before this.
 */
return new class extends Migration
{
    public function up()
    {
        foreach (['resort_admins', 'admins', 'shopkeepers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('must_change_password')->default(false);
            });
        }
    }

    public function down()
    {
        foreach (['resort_admins', 'admins', 'shopkeepers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('must_change_password');
            });
        }
    }
};
