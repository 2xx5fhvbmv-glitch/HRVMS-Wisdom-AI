<?php

namespace App\Services\Wisdom;

/**
 * Validates an AI-generated SQL string before it is allowed anywhere near the
 * database. This is the PRIMARY protection for the ad-hoc `run_read_query`
 * tool (the read-only DB user is just defense-in-depth).
 *
 * The tool is exposed to the full (HR) access tier only — that tier may already
 * see every business record — so the guard's job is NOT to hide business data
 * but to guarantee:
 *   - one single statement only (no stacked `;` queries),
 *   - SELECT / WITH…SELECT only — no writes, DDL, file or lock operations,
 *   - NO system / auth tables (migrations, sessions, oauth, the login user
 *     tables, …) and NO credential columns (password, tokens, secrets),
 *   - the query is scoped to the caller's resort via the bound `:resort_id`
 *     placeholder (we bind the value; the model can never inject a number),
 *   - a row LIMIT and a statement timeout are forced on.
 */
class ReadQueryGuard
{
    /** Hard cap on returned rows. */
    const MAX_ROWS = 200;

    /** Query timeout (ms) injected as a MySQL optimizer hint. */
    const TIMEOUT_MS = 8000;

    /** Exact table names the assistant may never touch (auth / system / queue). */
    const DENY_TABLES = [
        'migrations', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs',
        'password_resets', 'password_reset_tokens', 'personal_access_tokens',
        'users', 'admins', 'shopkeepers',
    ];

    /** Table name prefixes that are always denied. */
    const DENY_TABLE_PREFIXES = ['oauth_', 'telescope_', 'pulse_'];

    /** Columns that must never be selected or returned (case-insensitive substring). */
    const SENSITIVE_COL = '/password|remember_token|access_token|api_token|secret|two_factor|_otp\b|\botp\b|salt/i';

    /**
     * @return array{ok:bool, sql?:string, error?:string}
     */
    public static function validate(string $raw): array
    {
        $sql = trim($raw);
        if ($sql === '') {
            return self::fail('Empty query.');
        }

        // Strip SQL comments (-- …, # …, /* … */) so nothing can hide inside them.
        $sql = preg_replace('/\/\*.*?\*\//s', ' ', $sql);
        $sql = preg_replace('/(--|#)[^\n]*/', ' ', $sql);
        $sql = trim($sql);

        // Single statement only: drop a single trailing semicolon, reject the rest.
        $sql = rtrim($sql, "; \t\n\r");
        if (strpos($sql, ';') !== false) {
            return self::fail('Only a single SQL statement is allowed.');
        }

        // Must be a read query.
        if (!preg_match('/^\s*(select|with)\b/i', $sql)) {
            return self::fail('Only SELECT queries are allowed.');
        }

        // Forbidden keywords / dangerous constructs (word-boundary matched).
        $forbidden = [
            'insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate',
            'replace', 'rename', 'grant', 'revoke', 'merge', 'call', 'do',
            'handler', 'set', 'into', 'load_file', 'outfile', 'dumpfile',
            'sleep', 'benchmark', 'get_lock', 'release_lock', 'lock', 'unlock',
            'information_schema', 'performance_schema', 'mysql', 'sys',
        ];
        foreach ($forbidden as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $sql)) {
                return self::fail("Disallowed keyword in query: \"{$kw}\".");
            }
        }

        // Never let credential columns be referenced.
        if (preg_match(self::SENSITIVE_COL, $sql)) {
            return self::fail('Query references a restricted (credential) column.');
        }

        // Mandatory resort scoping via the bound placeholder.
        if (!preg_match('/:resort_id\b/', $sql)) {
            return self::fail('Query must be scoped to the resort: add a `resort_id = :resort_id` condition (the value is bound automatically).');
        }

        // Table guard: every name after FROM / JOIN must be an allowed business table.
        if (!preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $m)) {
            return self::fail('Could not identify which tables the query reads.');
        }
        foreach (array_unique(array_map('strtolower', $m[1])) as $table) {
            if (self::isDeniedTable($table)) {
                return self::fail("Table \"{$table}\" is restricted and cannot be queried.");
            }
        }

        // Force a row limit if the model didn't add one.
        if (!preg_match('/\blimit\b/i', $sql)) {
            $sql .= ' LIMIT ' . self::MAX_ROWS;
        }

        // Force a server-side statement timeout (SELECT only).
        if (preg_match('/^\s*select\b/i', $sql)) {
            $sql = preg_replace('/^\s*select\b/i', 'SELECT /*+ MAX_EXECUTION_TIME(' . self::TIMEOUT_MS . ') */', $sql, 1);
        }

        return ['ok' => true, 'sql' => $sql];
    }

    /** True if a table is auth/system/sensitive and must never be read. */
    public static function isDeniedTable(string $table): bool
    {
        $t = strtolower(trim($table, " `"));
        if (in_array($t, self::DENY_TABLES, true)) {
            return true;
        }
        foreach (self::DENY_TABLE_PREFIXES as $prefix) {
            if (str_starts_with($t, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /** True if a column name is a credential and must be hidden from results. */
    public static function isSensitiveColumn(string $column): bool
    {
        return (bool) preg_match(self::SENSITIVE_COL, $column);
    }

    private static function fail(string $msg): array
    {
        return ['ok' => false, 'error' => $msg];
    }
}
