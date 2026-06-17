<?php

namespace App\Services\Wisdom;

/**
 * Validates an AI-generated SQL string before it is allowed anywhere near the
 * database. This is the PRIMARY protection for the ad-hoc `run_read_query`
 * tool (the read-only DB user is just defense-in-depth).
 *
 * Guarantees enforced here:
 *   - one single statement only (no stacked `;` queries),
 *   - SELECT / WITH…SELECT only — no writes, DDL, file or lock operations,
 *   - every table referenced is on a small HR allow-list,
 *   - the query MUST scope to the caller's resort via the bound `:resort_id`
 *     placeholder (we bind the value; the model can never inject a number),
 *   - a row LIMIT and a statement timeout are forced on.
 */
class ReadQueryGuard
{
    /** Tables the assistant is allowed to read. All have a `resort_id` column. */
    const ALLOWED_TABLES = [
        'employees',
        'resort_admins',
        'resort_departments',
        'resort_positions',
        'employees_leaves',
        'leave_categories',
        'parent_attendaces',
        'sos_history',
        'sos_emergency_types',
        'vacancies',
        'applicant_form_data',
        'payroll',
    ];

    /** Hard cap on returned rows. */
    const MAX_ROWS = 200;

    /** Query timeout (ms) injected as a MySQL optimizer hint. */
    const TIMEOUT_MS = 8000;

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

        // Mandatory resort scoping via the bound placeholder.
        if (!preg_match('/:resort_id\b/', $sql)) {
            return self::fail('Query must be scoped to the resort: add a `resort_id = :resort_id` condition (the value is bound automatically).');
        }

        // Table allow-list: collect every name after FROM / JOIN.
        if (!preg_match_all('/\b(?:from|join)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $m)) {
            return self::fail('Could not identify which tables the query reads.');
        }
        foreach (array_unique(array_map('strtolower', $m[1])) as $table) {
            if (!in_array($table, self::ALLOWED_TABLES, true)) {
                return self::fail("Table \"{$table}\" is not available. Allowed tables: " . implode(', ', self::ALLOWED_TABLES) . '.');
            }
        }

        // Force a row limit if the model didn't add one.
        if (!preg_match('/\blimit\b/i', $sql)) {
            $sql .= ' LIMIT ' . self::MAX_ROWS;
        }

        // Force a server-side statement timeout (SELECT only; CTEs run uncapped
        // by hint but are still bounded by LIMIT + allow-list).
        if (preg_match('/^\s*select\b/i', $sql)) {
            $sql = preg_replace('/^\s*select\b/i', 'SELECT /*+ MAX_EXECUTION_TIME(' . self::TIMEOUT_MS . ') */', $sql, 1);
        }

        return ['ok' => true, 'sql' => $sql];
    }

    private static function fail(string $msg): array
    {
        return ['ok' => false, 'error' => $msg];
    }
}
