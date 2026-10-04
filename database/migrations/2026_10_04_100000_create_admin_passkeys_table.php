<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Super-admin passkeys / hardware security keys (advisory decision A2).
 * A registered key is an alternative second factor to TOTP at sign-in.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_passkeys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('name', 100);
            $table->string('credential_id', 512)->unique(); // base64url
            $table->text('public_key');                     // PEM
            $table->string('rp_id', 191);
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_passkeys');
    }
};
