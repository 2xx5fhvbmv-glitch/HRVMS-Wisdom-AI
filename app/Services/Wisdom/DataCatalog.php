<?php

namespace App\Services\Wisdom;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WAI's generic data access: `list_datasets` + `query_data` over config/wisdom_catalog.php.
 *
 * The AI sends a structured request (dataset, fields, filters, group_by, metrics, sort, limit). Every name is looked
 * up in the catalog; anything unknown is refused. SQL is built only from catalog text (columns, joins, expressions)
 * plus bound values, so a request can never inject SQL. The resort filter is applied by this class on the base table
 * and on every join, and a scoped user's department filter likewise — neither ever comes from the request.
 */
class DataCatalog
{
    private const OPS = ['=', '!=', '>', '>=', '<', '<=', 'in', 'not_in', 'contains', 'starts_with', 'is_null', 'not_null', 'between'];
    private const METRIC_FNS = ['count', 'count_distinct', 'sum', 'avg', 'min', 'max'];

    /** Datasets this user may query, with only the fields their tier may see. */
    public static function datasets(array $ctx): array
    {
        $tier = $ctx['tier'] ?? null;
        $allowed = config('wisdom_catalog.sensitivity', []);
        $out = [];
        foreach (config('wisdom_catalog.datasets', []) as $name => $ds) {
            if (isset($ds['tiers']) && !in_array($tier, $ds['tiers'], true)) {
                continue;
            }
            $only = config("wisdom_catalog.tier_datasets.{$tier}");
            if ($only !== null && !in_array($name, $only, true)) {
                continue;
            }
            // A scoped user (department ids in ctx) can only use datasets that can be scoped to a department.
            if (isset($ctx['dept_ids']) && empty($ds['scope']['department'])) {
                continue;
            }
            $ds['fields'] = array_filter($ds['fields'], fn ($f) => in_array($tier, $allowed[$f['sensitivity'] ?? 'general'] ?? [], true));
            if ($ds['fields']) {
                $out[$name] = $ds;
            }
        }
        return $out;
    }

    /** `list_datasets`: what the AI may ask for, without any SQL. */
    public static function describe(array $ctx, ?string $module = null): array
    {
        $list = [];
        foreach (self::datasets($ctx) as $name => $ds) {
            if ($module && stripos($ds['module'], $module) === false) {
                continue;
            }
            $list[] = [
                'dataset'     => $name,
                'module'      => $ds['module'],
                'description' => $ds['description'],
                'fields'      => array_map(fn ($f) => array_filter([
                    'type'   => $f['type'],
                    'label'  => $f['label'] ?? null,
                    'values' => $f['values'] ?? null,
                ]), $ds['fields']),
            ];
        }
        return [
            'datasets' => $list,
            'how_to'   => 'Call query_data with one dataset. List rows with `fields`, or summarise with `group_by` + `metrics` '
                . '(count, count_distinct, sum, avg, min, max). Filters: {field, op, value}; ops ' . implode(', ', self::OPS)
                . '. Dates are YYYY-MM-DD. Results are already limited to your resort and access.',
        ];
    }

    /** `query_data`: validate the request against the catalog, build it, run it read-only. */
    public static function query(array $args, array $ctx): array
    {
        $name = (string) ($args['dataset'] ?? '');
        $computed = self::datasets($ctx)[$name] ?? null;
        if ($computed && !empty($computed['source'])) {
            try {
                return self::queryComputed($name, $computed, $args, $ctx);
            } catch (\InvalidArgumentException $e) {
                return ['error' => $e->getMessage(), 'hint' => 'Call list_datasets to see the datasets and fields you can use.'];
            }
        }
        try {
            [$q, $meta] = self::build($args, $ctx);
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage(), 'hint' => 'Call list_datasets to see the datasets and fields you can use.'];
        }

        try {
            $rows = self::withTimeout($q);
        } catch (\Throwable $e) {
            Log::warning('Wisdom AI query_data failed', ['dataset' => $meta['dataset'], 'error' => $e->getMessage()]);
            return ['error' => 'The query could not be run. Try fewer fields or filters.'];
        }

        $limit = $meta['limit'];
        return [
            'dataset'   => $meta['dataset'],
            'as_of'     => now()->toDateTimeString(),
            'row_count' => min(count($rows), $limit),
            'truncated' => count($rows) > $limit,
            'rows'      => array_slice(array_map(fn ($r) => (array) $r, $rows), 0, $limit),
        ];
    }

    /**
     * Build the query. Public for tests (they inspect the SQL and bindings). Throws InvalidArgumentException with a
     * message meant for the AI on any unknown name, bad operator or bad value.
     *
     * @return array{0: Builder, 1: array}
     */
    public static function build(array $args, array $ctx): array
    {
        $rid = (int) ($ctx['resort_id'] ?? 0);
        if ($rid <= 0) {
            throw new \InvalidArgumentException('No resort in context.');
        }
        $name = (string) ($args['dataset'] ?? '');
        $ds = self::datasets($ctx)[$name] ?? null;
        if (!$ds) {
            throw new \InvalidArgumentException("Unknown or unavailable dataset '{$name}'.");
        }
        $limits = config('wisdom_catalog.limits');
        $fields = $ds['fields'];
        $field = function ($f) use ($fields, $name) {
            if (!is_string($f) || !isset($fields[$f])) {
                throw new \InvalidArgumentException("Unknown field '" . (is_scalar($f) ? $f : '?') . "' in dataset '{$name}'.");
            }
            return $fields[$f];
        };
        $sql = fn (array $f) => $f['expr'] ?? $f['col'];

        $select   = self::list($args['fields'] ?? [], 30, 'fields');
        $groupBy  = self::list($args['group_by'] ?? [], $limits['group_by'], 'group_by');
        $metrics  = self::list($args['metrics'] ?? [], $limits['metrics'], 'metrics');
        $filters  = self::list($args['filters'] ?? [], $limits['filters'], 'filters');
        $sort     = self::list($args['sort'] ?? [], 3, 'sort');
        $limit    = max(1, min((int) ($args['limit'] ?? 50), $limits['rows']));
        $aggregate = $groupBy || $metrics;
        if (!$aggregate && !$select) {
            $select = array_keys($fields);   // nothing asked for: list every field the user may see
        }

        // Joins needed by every field used (plus the department scope), with their own dependencies.
        $needs = [];
        $used = array_merge($aggregate ? $groupBy : $select, array_column(array_filter($filters, 'is_array'), 'field'),
            array_filter(array_column(array_filter($metrics, 'is_array'), 'field')));
        foreach ($used as $f) {
            if (isset($field($f)['join'])) {
                $needs[] = $field($f)['join'];
            }
        }
        if (isset($ctx['dept_ids']) && !empty($ds['scope']['needs'])) {
            $needs[] = $ds['scope']['needs'];
        }
        // A table without its own resort column is reached only through a required INNER join that carries it.
        $required = $ds['required'] ?? [];
        $anchored = !empty($ds['resort']) || (bool) array_filter($required,
            fn ($j) => ($ds['joins'][$j]['type'] ?? '') === 'inner' && !empty($ds['joins'][$j]['resort']));
        if (!$anchored) {
            throw new \LogicException("Catalog dataset '{$name}' has no resort filter.");
        }

        $q = DB::connection('mysql_readonly')->table($ds['table'] . ' as ' . $ds['alias']);
        if (!empty($ds['resort'])) {
            $q->where($ds['resort'], $rid);
        }
        foreach ($ds['where_null'] ?? [] as $col) {
            $q->whereNull($col);
        }
        foreach ($ds['where'] ?? [] as [$col, $op, $val]) {
            $q->where($col, $op, $val);
        }
        foreach (self::joinOrder($ds['joins'] ?? [], array_unique(array_merge($required, $needs))) as $j) {
            $join = $ds['joins'][$j];
            $on = function ($on) use ($join, $rid) {
                $on->on($join['on'][0], '=', $join['on'][1])->where($join['resort'], '=', $rid);
            };
            ($join['type'] ?? 'left') === 'inner'
                ? $q->join($join['table'] . ' as ' . $join['alias'], $on)
                : $q->leftJoin($join['table'] . ' as ' . $join['alias'], $on);
        }
        if (isset($ctx['dept_ids'])) {
            $q->whereIn($ds['scope']['department'], array_map('intval', (array) $ctx['dept_ids']) ?: [0]);
        }

        // Select / aggregate
        $aliases = [];
        if ($aggregate) {
            foreach ($groupBy as $g) {
                $q->addSelect(DB::raw($sql($field($g)) . ' as `' . $g . '`'))->groupBy(DB::raw($sql($field($g))));
                $aliases[] = $g;
            }
            foreach ($metrics as $m) {
                $fn = is_array($m) ? strtolower((string) ($m['fn'] ?? '')) : '';
                if (!in_array($fn, self::METRIC_FNS, true)) {
                    throw new \InvalidArgumentException("Metric fn must be one of: " . implode(', ', self::METRIC_FNS) . '.');
                }
                if ($fn === 'count' && empty($m['field'])) {
                    $expr = 'COUNT(*)';
                    $alias = 'count';
                } else {
                    $f = $field($m['field'] ?? null);
                    if (in_array($fn, ['sum', 'avg'], true) && $f['type'] !== 'number') {
                        throw new \InvalidArgumentException("{$fn} needs a number field; '{$m['field']}' is {$f['type']}.");
                    }
                    $expr = $fn === 'count_distinct' ? 'COUNT(DISTINCT ' . $sql($f) . ')' : strtoupper($fn) . '(' . $sql($f) . ')';
                    $alias = $fn . '_' . $m['field'];
                }
                $q->addSelect(DB::raw($expr . ' as `' . $alias . '`'));
                $aliases[] = $alias;
            }
            if (!$metrics) {
                $q->addSelect(DB::raw('COUNT(*) as `count`'));
                $aliases[] = 'count';
            }
        } else {
            foreach ($select as $s) {
                $q->addSelect(DB::raw($sql($field($s)) . ' as `' . $s . '`'));
                $aliases[] = $s;
            }
        }

        foreach ($filters as $flt) {
            if (!is_array($flt)) {
                throw new \InvalidArgumentException('Each filter is {field, op, value}.');
            }
            self::applyFilter($q, $field($flt['field'] ?? null), $sql, (string) ($flt['op'] ?? '='), $flt['value'] ?? null);
        }

        foreach ($sort as $s) {
            $by = is_array($s) ? ($s['by'] ?? null) : $s;
            if (!in_array($by, $aliases, true)) {
                throw new \InvalidArgumentException("Sort by one of the returned columns: " . implode(', ', $aliases) . '.');
            }
            $q->orderBy(DB::raw('`' . $by . '`'), strtolower((string) ($s['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc');
        }
        $q->limit($limit + 1);   // one extra row tells us the result was cut

        return [$q, ['dataset' => $name, 'limit' => $limit]];
    }

    /**
     * A computed dataset: rows come from a PHP engine the app already uses (e.g. the leave-balance engine), named in
     * the catalog as 'source' => [Class, method]. The method receives the resort id and the department scope and
     * must return only that resort's (and those departments') rows, keyed by the catalog field names. The request
     * is validated exactly like a table dataset; filters / group_by / metrics / sort are applied here in PHP.
     */
    private static function queryComputed(string $name, array $ds, array $args, array $ctx): array
    {
        $rid = (int) ($ctx['resort_id'] ?? 0);
        if ($rid <= 0) {
            throw new \InvalidArgumentException('No resort in context.');
        }
        $limits = config('wisdom_catalog.limits');
        $fields = $ds['fields'];
        $field = function ($f) use ($fields, $name) {
            if (!is_string($f) || !isset($fields[$f])) {
                throw new \InvalidArgumentException("Unknown field '" . (is_scalar($f) ? $f : '?') . "' in dataset '{$name}'.");
            }
            return $fields[$f];
        };
        $select  = self::list($args['fields'] ?? [], 30, 'fields');
        $groupBy = self::list($args['group_by'] ?? [], $limits['group_by'], 'group_by');
        $metrics = self::list($args['metrics'] ?? [], $limits['metrics'], 'metrics');
        $filters = self::list($args['filters'] ?? [], $limits['filters'], 'filters');
        $sort    = self::list($args['sort'] ?? [], 3, 'sort');
        $limit   = max(1, min((int) ($args['limit'] ?? 50), $limits['rows']));
        $aggregate = $groupBy || $metrics;
        if (!$aggregate && !$select) {
            $select = array_keys($fields);   // nothing asked for: list every field the user may see
        }
        array_map($field, $aggregate ? $groupBy : $select);

        // Filters, validated like SQL ones, then matched in PHP (strings case-insensitive, like MySQL).
        $tests = [];
        foreach ($filters as $flt) {
            if (!is_array($flt)) {
                throw new \InvalidArgumentException('Each filter is {field, op, value}.');
            }
            $f = $field($flt['field'] ?? null);
            $op = strtolower(trim((string) ($flt['op'] ?? '=')));
            if (!in_array($op, self::OPS, true)) {
                throw new \InvalidArgumentException("Unknown filter op '{$op}'. Use: " . implode(', ', self::OPS) . '.');
            }
            $raw = $flt['value'] ?? null;
            $val = match ($op) {
                'is_null', 'not_null' => null,
                'in', 'not_in' => array_map(fn ($v) => self::value($f, $v), self::list($raw, $limits['in_values'], 'in value')),
                'between' => (is_array($raw) && count($raw) === 2) ? array_map(fn ($v) => self::value($f, $v), array_values($raw))
                    : throw new \InvalidArgumentException('between needs [from, to].'),
                'contains', 'starts_with' => mb_strtolower((string) self::value(['type' => 'string'], $raw)),
                default => self::value($f, $raw),
            };
            $tests[] = [$flt['field'], $op, $val, $f['type']];
        }
        $cmp = function ($a, $b, string $type): int {
            if ($type === 'number') {
                return (float) $a <=> (float) $b;
            }
            return strcmp(mb_strtolower((string) $a), mb_strtolower((string) $b));
        };
        $matches = function (array $r) use ($tests, $cmp): bool {
            foreach ($tests as [$k, $op, $v, $type]) {
                $x = $r[$k] ?? null;
                $empty = $x === null || $x === '';
                $ok = match ($op) {
                    'is_null'  => $empty,
                    'not_null' => !$empty,
                    'in'       => !$empty && (bool) array_filter($v, fn ($y) => $cmp($x, $y, $type) === 0),
                    'not_in'   => $empty || !array_filter($v, fn ($y) => $cmp($x, $y, $type) === 0),
                    'between'  => !$empty && $cmp($x, $v[0], $type) >= 0 && $cmp($x, $v[1], $type) <= 0,
                    'contains' => !$empty && str_contains(mb_strtolower((string) $x), $v),
                    'starts_with' => !$empty && str_starts_with(mb_strtolower((string) $x), $v),
                    '='  => !$empty && $cmp($x, $v, $type) === 0,
                    '!=' => $empty || $cmp($x, $v, $type) !== 0,
                    '>'  => !$empty && $cmp($x, $v, $type) > 0,
                    '>=' => !$empty && $cmp($x, $v, $type) >= 0,
                    '<'  => !$empty && $cmp($x, $v, $type) < 0,
                    '<=' => !$empty && $cmp($x, $v, $type) <= 0,
                };
                if (!$ok) {
                    return false;
                }
            }
            return true;
        };

        [$class, $method] = $ds['source'];
        $rows = array_values(array_filter($class::$method($rid, $ctx['dept_ids'] ?? null), $matches));

        if ($aggregate) {
            $groups = [];
            foreach ($rows as $r) {
                $key = json_encode(array_map(fn ($g) => $r[$g] ?? null, $groupBy));
                $groups[$key][] = $r;
            }
            $out = [];
            foreach ($groups as $members) {
                $row = [];
                foreach ($groupBy as $g) {
                    $row[$g] = $members[0][$g] ?? null;
                }
                foreach ($metrics ?: [['fn' => 'count']] as $m) {
                    $fn = is_array($m) ? strtolower((string) ($m['fn'] ?? '')) : '';
                    if (!in_array($fn, self::METRIC_FNS, true)) {
                        throw new \InvalidArgumentException('Metric fn must be one of: ' . implode(', ', self::METRIC_FNS) . '.');
                    }
                    if ($fn === 'count' && empty($m['field'])) {
                        $row['count'] = count($members);
                        continue;
                    }
                    $f = $field($m['field'] ?? null);
                    if (in_array($fn, ['sum', 'avg'], true) && $f['type'] !== 'number') {
                        throw new \InvalidArgumentException("{$fn} needs a number field; '{$m['field']}' is {$f['type']}.");
                    }
                    $vals = array_values(array_filter(array_column($members, $m['field']), fn ($v) => $v !== null && $v !== ''));
                    $row[$fn . '_' . $m['field']] = match ($fn) {
                        'count'          => count($vals),
                        'count_distinct' => count(array_unique($vals)),
                        'sum'            => round(array_sum($vals), 2),
                        'avg'            => $vals ? round(array_sum($vals) / count($vals), 2) : null,
                        'min'            => $vals ? min($vals) : null,
                        'max'            => $vals ? max($vals) : null,
                    };
                }
                $out[] = $row;
            }
            $rows = $out;
        } else {
            $rows = array_map(fn ($r) => array_intersect_key($r, array_flip($select)) + array_fill_keys($select, null), $rows);
        }

        $columns = array_keys($rows[0] ?? []);
        foreach (array_reverse($sort) as $s) {
            $by = is_array($s) ? ($s['by'] ?? null) : $s;
            if ($rows && !in_array($by, $columns, true)) {
                throw new \InvalidArgumentException('Sort by one of the returned columns: ' . implode(', ', $columns) . '.');
            }
            $desc = is_array($s) && strtolower((string) ($s['dir'] ?? 'asc')) === 'desc';
            usort($rows, function ($a, $b) use ($by, $desc) {
                $x = $a[$by] ?? null;
                $y = $b[$by] ?? null;
                $c = is_numeric($x) && is_numeric($y) ? $x <=> $y : strcmp((string) $x, (string) $y);
                return $desc ? -$c : $c;
            });
        }

        return [
            'dataset'   => $name,
            'as_of'     => now()->toDateTimeString(),
            'basis'     => $ds['basis'] ?? null,
            'row_count' => min(count($rows), $limit),
            'truncated' => count($rows) > $limit,
            'rows'      => array_slice($rows, 0, $limit),
        ];
    }

    private static function applyFilter(Builder $q, array $f, \Closure $sql, string $op, $value): void
    {
        $op = strtolower(trim($op));
        if (!in_array($op, self::OPS, true)) {
            throw new \InvalidArgumentException("Unknown filter op '{$op}'. Use: " . implode(', ', self::OPS) . '.');
        }
        $col = DB::raw($sql($f));
        if ($op === 'is_null' || $op === 'not_null') {
            $op === 'is_null' ? $q->whereNull($col) : $q->whereNotNull($col);
            return;
        }
        if ($op === 'in' || $op === 'not_in') {
            $vals = array_map(fn ($v) => self::value($f, $v), self::list($value, config('wisdom_catalog.limits.in_values'), 'in value'));
            $op === 'in' ? $q->whereIn($col, $vals ?: [null]) : $q->whereNotIn($col, $vals);
            return;
        }
        if ($op === 'between') {
            $pair = is_array($value) ? array_values($value) : [];
            if (count($pair) !== 2) {
                throw new \InvalidArgumentException('between needs [from, to].');
            }
            $q->where($col, '>=', self::value($f, $pair[0]))->where($col, '<=', self::value($f, $pair[1]));
            return;
        }
        if ($op === 'contains' || $op === 'starts_with') {
            $v = addcslashes((string) self::value(['type' => 'string'], $value), '%_\\');
            $q->where($col, 'like', ($op === 'contains' ? '%' : '') . $v . '%');
            return;
        }
        $q->where($col, $op === '!=' ? '<>' : $op, self::value($f, $value));
    }

    /** A filter value, checked against the field's type. */
    private static function value(array $f, $v)
    {
        if (is_bool($v)) {
            $v = $f['type'] === 'number' ? (int) $v : ($v ? 'yes' : 'no');   // yes/no and 1/0 fields
        }
        if (!is_scalar($v)) {
            throw new \InvalidArgumentException('Filter values must be plain values.');
        }
        switch ($f['type']) {
            case 'number':
                if (!is_numeric($v)) {
                    throw new \InvalidArgumentException("'{$v}' is not a number.");
                }
                return $v + 0;
            case 'date':
            case 'datetime':
                $v = strtolower(trim((string) $v)) === 'today' ? now()->toDateString() : trim((string) $v);
                foreach (['Y-m-d', 'Y-m-d H:i:s'] as $format) {
                    $d = \DateTime::createFromFormat($format, $v);
                    if ($d && $d->format($format) === $v) {
                        return $f['type'] === 'date' ? $d->format('Y-m-d') : $d->format('Y-m-d H:i:s');
                    }
                }
                throw new \InvalidArgumentException("'{$v}' is not a date (YYYY-MM-DD).");
            default:
                $v = (string) $v;
                if (mb_strlen($v) > 200) {
                    throw new \InvalidArgumentException('Filter value too long.');
                }
                return $v;
        }
    }

    private static function list($v, int $max, string $what): array
    {
        if ($v === null || $v === '') {
            return [];
        }
        if (!is_array($v)) {
            $v = [$v];
        }
        if (count($v) > $max) {
            throw new \InvalidArgumentException("At most {$max} {$what}.");
        }
        return array_values($v);
    }

    /** Joins in dependency order ('needs' = a join that must come first). */
    private static function joinOrder(array $joins, array $wanted): array
    {
        $order = [];
        $add = function ($j) use (&$add, &$order, $joins) {
            if (in_array($j, $order, true) || !isset($joins[$j])) {
                return;
            }
            if (!empty($joins[$j]['needs'])) {
                $add($joins[$j]['needs']);
            }
            $order[] = $j;
        };
        foreach ($wanted as $j) {
            $add($j);
        }
        return $order;
    }

    /** Run with a statement time limit (MySQL max_execution_time / MariaDB max_statement_time). */
    private static function withTimeout(Builder $q): array
    {
        $ms = (int) config('wisdom_catalog.limits.timeout_ms', 8000);
        $conn = $q->getConnection();
        try {
            $conn->statement('SET SESSION max_execution_time = ' . $ms);
        } catch (\Throwable $e) {
            try {
                $conn->statement('SET SESSION max_statement_time = ' . ($ms / 1000));
            } catch (\Throwable $e) {
                // neither variable exists: run without a server-side limit
            }
        }
        return $q->get()->all();
    }
}
