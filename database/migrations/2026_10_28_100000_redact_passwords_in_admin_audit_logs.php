<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Security audit W1-08: AdminSecurity used to write old_pass / password_confirm (any field whose name contains
 * pass, secret or token) into admin_audit_logs.payload in clear. Strip them from the rows already stored, using
 * the same key rule the middleware now applies. Unreadable (truncated) payloads that mention one are emptied.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_audit_logs')
            ->where(fn ($q) => $q->where('payload', 'like', '%pass%')->orWhere('payload', 'like', '%secret%')->orWhere('payload', 'like', '%token%'))
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $data = json_decode($row->payload, true);
                    $clean = is_array($data) ? array_filter($data, fn ($k) => !preg_match('/pass|secret|token/i', $k), ARRAY_FILTER_USE_KEY) : [];
                    if (!is_array($data) || $clean !== $data) {
                        DB::table('admin_audit_logs')->where('id', $row->id)->update(['payload' => json_encode((object) $clean)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Redacted values can't be restored.
    }
};
