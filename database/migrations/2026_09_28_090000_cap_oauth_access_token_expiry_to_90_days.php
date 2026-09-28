<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S4-04: personalAccessTokensExpireIn() only governs tokens issued from
 * now on — existing tokens were minted under Passport's 1-year default and
 * keep that expiry unless capped here. Non-destructive: only ever shortens
 * expires_at, never extends it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE oauth_access_tokens SET expires_at = LEAST(expires_at, DATE_ADD(created_at, INTERVAL 90 DAY)) WHERE revoked = 0");
    }

    public function down(): void
    {
        // Not reversible — the original expiry value isn't retained.
    }
};
