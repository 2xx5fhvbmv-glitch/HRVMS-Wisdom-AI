<?php

namespace App\Support\Demo;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Deletes every row that belongs to one resort — and nothing else — so the demo
 * resort can be rebuilt inside the normal database.
 *
 * Which rows belong to the resort is worked out from the live schema plus
 * config/demo_purge.php (see that file). It fails closed: a table it cannot
 * classify stops the purge, and so does a selected row whose own resort column
 * points at another resort.
 */
class DemoPurger
{
    private array $columns = [];  // table => [column => data type]
    private array $rules = [];    // table => [rule, …]
    private array $selected = []; // table => [id => true]
    private ?array $employeeIds = null;
    private ?array $adminIds = null;

    private const INT_TYPES = ['int', 'bigint', 'mediumint', 'smallint', 'tinyint'];

    public function __construct(private int $resortId)
    {
        $this->loadSchema();
    }

    /** Problems that stop a purge: unclassified tables, rows without an id column, children of unpurged parents. */
    public function audit(): array
    {
        $global = array_flip(config('demo_purge.global'));
        $problems = [];
        foreach (array_keys($this->columns) as $table) {
            if (isset($global[$table])) {
                continue;
            }
            if (empty($this->rules[$table])) {
                $problems[] = "{$table}: not classified — add it to config/demo_purge.php (children, admin_columns or global).";
                continue;
            }
            if (!isset($this->columns[$table]['id']) && $this->referencedBy($table)) {
                $problems[] = "{$table}: has no id column but other tables point at it.";
            }
            foreach ($this->rules[$table] as $rule) {
                if ($rule['type'] === 'child' && isset($global[$rule['parent']])) {
                    $problems[] = "{$table}.{$rule['col']}: parent {$rule['parent']} is global.";
                }
                if ($rule['type'] === 'child' && !isset($this->columns[$rule['parent']])) {
                    $problems[] = "{$table}.{$rule['col']}: parent table {$rule['parent']} does not exist.";
                }
            }
        }
        foreach (config('demo_purge.children') + config('demo_purge.admin_columns') as $table => $_) {
            if (!isset($this->columns[$table])) {
                $problems[] = "{$table}: listed in config/demo_purge.php but does not exist.";
            }
        }
        return $problems;
    }

    /** table => number of rows a purge would delete. Deletes nothing. */
    public function plan(): array
    {
        if ($problems = $this->audit()) {
            throw new RuntimeException("Purge refused — schema not fully classified:\n" . implode("\n", $problems));
        }
        $this->select();
        $this->crossCheck();
        $counts = array_map('count', array_filter($this->selected));
        foreach ($this->idlessTables() as $table) {
            if ($n = $this->idlessQuery($table)?->count()) {
                $counts[$table] = $n;
            }
        }
        ksort($counts);
        return $counts;
    }

    /** Deletes the planned rows, children before parents, in one transaction. table => rows deleted. */
    public function purge(): array
    {
        $this->plan();
        $deleted = [];
        DB::transaction(function () use (&$deleted) {
            // Pivot tables without an id are leaves: delete them first, by their conditions.
            foreach ($this->idlessTables() as $table) {
                if (($query = $this->idlessQuery($table)) && ($n = $query->delete())) {
                    $deleted[$table] = $n;
                }
            }
            foreach ($this->deleteOrder() as $table) {
                $n = 0;
                foreach (array_chunk(array_keys($this->selected[$table]), 1000) as $ids) {
                    $n += DB::table($table)->whereIn('id', $ids)->delete();
                }
                $deleted[$table] = $n;
            }
        });
        ksort($deleted);
        return $deleted;
    }

    private function loadSchema(): void
    {
        $db = DB::getDatabaseName();
        foreach (DB::select('SELECT TABLE_NAME t, COLUMN_NAME c, DATA_TYPE d FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?', [$db]) as $c) {
            $this->columns[$c->t][$c->c] = $c->d;
        }
        $global = array_flip(config('demo_purge.global'));
        $employeeColumns = array_map('strtolower', config('demo_purge.employee_columns'));

        foreach ($this->columns as $table => $cols) {
            if (isset($global[$table])) {
                continue;
            }
            foreach ($cols as $col => $type) {
                $int = in_array($type, self::INT_TYPES, true);
                if ($int && strtolower($col) === 'resort_id') {
                    $this->rules[$table][] = ['type' => 'resort', 'col' => $col];
                } elseif ($int && in_array(strtolower($col), $employeeColumns, true) && !$this->hasResortColumn($table)) {
                    $this->rules[$table][] = ['type' => 'employee', 'col' => $col];
                }
            }
        }
        foreach (config('demo_purge.text_resort_columns') as $table => $col) {
            $this->rules[$table][] = ['type' => 'text_resort', 'col' => $col];
        }
        foreach (config('demo_purge.admin_columns') as $table => $spec) {
            $this->rules[$table][] = ['type' => 'admin', 'col' => $spec[0], 'where' => $spec[1] ?? null];
        }
        foreach (config('demo_purge.children') as $table => $links) {
            foreach ($links as [$col, $parent, $parentCol]) {
                $this->rules[$table][] = ['type' => 'child', 'col' => $col, 'parent' => $parent, 'parentCol' => $parentCol];
            }
        }
        $fks = DB::select('SELECT TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc
            FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL', [$db]);
        foreach ($fks as $fk) {
            if (!isset($global[$fk->t]) && !isset($global[$fk->rt]) && $fk->t !== $fk->rt) {
                $this->rules[$fk->t][] = ['type' => 'child', 'col' => $fk->c, 'parent' => $fk->rt, 'parentCol' => $fk->rc];
            }
        }
    }

    private function hasResortColumn(string $table): bool
    {
        foreach ($this->columns[$table] as $col => $type) {
            if (strtolower($col) === 'resort_id' && in_array($type, self::INT_TYPES, true)) {
                return true;
            }
        }
        return false;
    }

    /** Repeat until no table gains rows: a child can only be found once its parent rows are known. */
    private function select(): void
    {
        $this->selected = [];
        $this->employeeIds = DB::table('employees')->where('resort_id', $this->resortId)->pluck('id')->all();
        $this->adminIds = DB::table('resort_admins')->where('resort_id', $this->resortId)->pluck('id')->all();

        for ($pass = 0; $pass < 25; $pass++) {
            $changed = false;
            foreach ($this->rules as $table => $rules) {
                if (!isset($this->columns[$table]['id'])) {
                    continue; // pivot without id — handled by idlessQuery()
                }
                $conditions = $this->conditions($table);
                if (!$conditions) {
                    continue;
                }
                $ids = DB::table($table)->where(function ($q) use ($conditions) {
                    foreach ($conditions as $condition) {
                        $q->orWhere($condition);
                    }
                })->pluck('id')->all();
                foreach ($ids as $id) {
                    if (!isset($this->selected[$table][$id])) {
                        $this->selected[$table][$id] = true;
                        $changed = true;
                    }
                }
            }
            if (!$changed) {
                return;
            }
        }
        throw new RuntimeException('Purge selection did not settle after 25 passes.');
    }

    /** Tables with rules but no id column (pivots). */
    private function idlessTables(): array
    {
        return array_values(array_filter(array_keys($this->rules), fn ($t) => isset($this->columns[$t]) && !isset($this->columns[$t]['id'])));
    }

    private function idlessQuery(string $table)
    {
        $conditions = $this->conditions($table);
        if (!$conditions) {
            return null;
        }
        return DB::table($table)->where(function ($q) use ($conditions) {
            foreach ($conditions as $condition) {
                $q->orWhere($condition);
            }
        });
    }

    private function referencedBy(string $table): bool
    {
        foreach ($this->rules as $rules) {
            foreach ($rules as $rule) {
                if ($rule['type'] === 'child' && $rule['parent'] === $table) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * A table's own resort column decides: links (child / employee / login) may
     * only add rows whose resort column is empty. Another resort's row pointing
     * at a purged parent is left alone — its foreign key then blocks the delete
     * and the whole purge rolls back, instead of deleting another resort's data.
     */
    private function conditions(string $table): array
    {
        $resortCol = null;
        foreach ($this->rules[$table] as $rule) {
            if ($rule['type'] === 'resort') {
                $resortCol = $rule['col'];
            }
        }
        $conditions = [];
        foreach ($this->rules[$table] as $rule) {
            $condition = $this->condition($rule);
            if (!$condition) {
                continue;
            }
            $conditions[] = ($resortCol && $rule['type'] !== 'resort')
                ? fn ($q) => $q->whereNull($resortCol)->where($condition)
                : $condition;
        }
        return $conditions;
    }

    private function condition(array $rule): ?\Closure
    {
        switch ($rule['type']) {
            case 'resort':
                return fn ($q) => $q->where($rule['col'], $this->resortId);
            case 'text_resort':
                return fn ($q) => $q->where($rule['col'], (string) $this->resortId);
            case 'employee':
                return $this->employeeIds ? fn ($q) => $q->whereIn($rule['col'], $this->employeeIds) : null;
            case 'admin':
                if (!$this->adminIds) {
                    return null;
                }
                return function ($q) use ($rule) {
                    $q->whereIn($rule['col'], $this->adminIds);
                    if ($rule['where']) {
                        $q->where($rule['where'][0], $rule['where'][1]);
                    }
                };
            case 'child':
                $values = $this->parentValues($rule['parent'], $rule['parentCol']);
                return $values ? fn ($q) => $q->whereIn($rule['col'], $values) : null;
        }
        return null;
    }

    private function parentValues(string $parent, string $parentCol): array
    {
        $ids = array_keys($this->selected[$parent] ?? []);
        if (!$ids || $parentCol === 'id') {
            return $ids;
        }
        $values = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            array_push($values, ...DB::table($parent)->whereIn('id', $chunk)->whereNotNull($parentCol)->pluck($parentCol)->all());
        }
        return array_values(array_unique($values));
    }

    /** A row reached through a child/employee link but stamped with another resort means a wrong rule — stop. */
    private function crossCheck(): void
    {
        foreach ($this->selected as $table => $ids) {
            foreach ($this->rules[$table] ?? [] as $rule) {
                if ($rule['type'] !== 'resort') {
                    continue;
                }
                foreach (array_chunk(array_keys($ids), 1000) as $chunk) {
                    $foreign = DB::table($table)->whereIn('id', $chunk)->whereNotNull($rule['col'])
                        ->where($rule['col'], '!=', $this->resortId)->count();
                    if ($foreign) {
                        throw new RuntimeException("Purge refused — {$foreign} selected row(s) in {$table} belong to another resort.");
                    }
                }
            }
        }
    }

    /** Children before parents. */
    private function deleteOrder(): array
    {
        $remaining = array_flip(array_keys(array_filter($this->selected)));
        $childrenOf = [];
        foreach ($this->rules as $table => $rules) {
            foreach ($rules as $rule) {
                if ($rule['type'] === 'child' && isset($remaining[$table]) && $rule['parent'] !== $table) {
                    $childrenOf[$rule['parent']][$table] = true;
                }
            }
        }
        $order = [];
        while ($remaining) {
            $ready = array_filter(array_keys($remaining), fn ($t) => !array_intersect_key($childrenOf[$t] ?? [], $remaining));
            if (!$ready) {
                $ready = [array_key_first($remaining)]; // a cycle — FK checks will still protect the delete
            }
            foreach ($ready as $table) {
                $order[] = $table;
                unset($remaining[$table]);
            }
        }
        return $order;
    }
}
