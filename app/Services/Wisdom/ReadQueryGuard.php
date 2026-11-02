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
        'users', 'admins', 'shopkeepers', 'resort_admins',
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
        // The same query with its string literals blanked out: words inside quotes are data, so they can't count as
        // SQL below (e.g. a literal 'resort_id' padding the per-table scope count).
        $bare = preg_replace('/\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"/s', "''", $sql);

        if (strpos($bare, ';') !== false) {
            return self::fail('Only a single SQL statement is allowed.');
        }

        // Must be a read query.
        if (!preg_match('/^\s*(select|with)\b/i', $sql)) {
            return self::fail('Only SELECT queries are allowed.');
        }

        // Forbidden keywords / dangerous constructs (word-boundary matched).
        // WA-01: `union` was missing here — `SELECT ... WHERE resort_id = :resort_id
        // UNION SELECT ... WHERE resort_id != :resort_id` passed every other check
        // (single statement, starts with SELECT, no other forbidden keyword, the
        // literal substring `:resort_id` present) and returned every other resort's
        // rows. A single-statement read tool has no legitimate need for UNION.
        $forbidden = [
            'insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate',
            'replace', 'rename', 'grant', 'revoke', 'merge', 'call', 'do',
            'handler', 'set', 'into', 'load_file', 'outfile', 'dumpfile',
            'sleep', 'benchmark', 'get_lock', 'release_lock', 'lock', 'unlock',
            'information_schema', 'performance_schema', 'mysql', 'sys',
            'union', 'straight_join',   // STRAIGHT_JOIN hides its table from the FROM/JOIN scan below
        ];
        foreach ($forbidden as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $bare)) {
                return self::fail("Disallowed keyword in query: \"{$kw}\".");
            }
        }

        // Never let credential columns be referenced.
        if (preg_match(self::SENSITIVE_COL, $sql)) {
            return self::fail('Query references a restricted (credential) column.');
        }

        // OR / XOR / || can widen the resort filter (`resort_id = :resort_id OR 1=1` returns every resort).
        if (preg_match('/\b(or|xor)\b|\|\|/i', $bare)) {
            return self::fail('OR is not allowed (it could widen the resort filter); use IN (...) or run separate queries.');
        }
        // Tables must be named plainly and joined with JOIN ... ON: a schema-qualified name (db.table) or a comma
        // join (FROM a, b) slips past the table and per-table scope checks below.
        if (preg_match('/\b(?:from|join)\s+`?\w+`?\s*\.\s*`?\w/i', $bare)) {
            return self::fail('Name tables without a database prefix.');
        }
        if (self::hasCommaJoin($bare)) {
            return self::fail('Join tables with JOIN ... ON (each scoped by its own resort_id), not with commas.');
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

        // Every table (or its alias) needs its own `alias.resort_id = :resort_id`, and :resort_id may appear only in
        // that form: `resort_id IN (:resort_id, 27)` or `resort_id <> :resort_id` would read other resorts. With OR
        // refused above, these AND-ed equalities can only narrow the result, so each table is held to this resort.
        // (Was WA-01's count of resort_id mentions, which repeating one table's predicate could pad.)
        if ($error = self::scopeError($bare)) {
            return self::fail($error);
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

    private const CLAUSE_WORDS = 'where|join|inner|left|right|cross|natural|on|using|group|order|limit|having';

    /** "FROM a, b" anywhere (subqueries included): commas left in a FROM clause once ON conditions and brackets are gone. */
    private static function hasCommaJoin(string $sql): bool
    {
        $check = function (string $part): bool {
            if (!preg_match_all('/\bfrom\b(.*?)(?=\bwhere\b|\bgroup\s+by\b|\border\s+by\b|\bhaving\b|\blimit\b|$)/is', $part, $m)) {
                return false;
            }
            foreach ($m[1] as $clause) {
                // An ON condition ends at the next join or at a comma (its own commas were inside brackets, already replaced).
                $clause = preg_replace('/\bon\b[^,]*?(?=\b(?:inner|left|right|cross|natural|join)\b|,|$)/is', ' ', $clause);
                if (str_contains($clause, ',')) {
                    return true;
                }
            }
            return false;
        };
        // Innermost brackets first (subqueries, IN lists, function calls), each replaced by a placeholder once checked.
        while (preg_match('/\(([^()]*)\)/', $sql, $g)) {
            if ($check($g[1])) {
                return true;
            }
            $sql = preg_replace('/\([^()]*\)/', ' _sub_ ', $sql, 1);
        }
        return $check($sql);
    }

    /** Null when every FROM/JOIN table is scoped by its own `name.resort_id = :resort_id` (unqualified only for one table). */
    private static function scopeError(string $sql): ?string
    {
        preg_match_all('/\b(?:from|join)\s+`?(\w+)`?(?:\s+(?:as\s+)?(?!(?:' . self::CLAUSE_WORDS . ')\b)`?(\w+)`?)?/i', $sql, $refs, PREG_SET_ORDER);
        $names = array_map(fn ($r) => strtolower(($r[2] ?? '') !== '' ? $r[2] : $r[1]), $refs);
        preg_match_all('/(?:`?(\w+)`?\s*\.\s*)?`?resort_id`?\s*=\s*:resort_id\b/i', $sql, $preds, PREG_SET_ORDER);
        if (count($preds) !== preg_match_all('/:resort_id\b/', $sql)) {
            return 'Use :resort_id only as `table.resort_id = :resort_id`.';
        }
        $scoped = array_map(fn ($p) => strtolower($p[1] ?? ''), $preds);
        if (count($names) === 1 && $scoped) {
            return in_array('', $scoped, true) || in_array($names[0], $scoped, true) ? null : 'Scope the table by its own resort_id = :resort_id.';
        }
        $missing = array_diff(array_unique($names), $scoped);
        return $missing ? 'Every table must be scoped by its own resort_id = :resort_id; missing for: ' . implode(', ', $missing) . '.' : null;
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
