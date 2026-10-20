<?php

namespace Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared helpers for the module seeders: inserts keep only the columns the table really has (tables here
 * have grown by many small migrations), people are looked up once, and randomness comes from the seeded RNG.
 */
trait SeedsDemoRows
{
    private static array $columnCache = [];
    private ?object $people = null;

    /** Insert one row, keeping only real columns; returns its id. */
    private function row(string $table, array $values): int
    {
        return (int) DB::table($table)->insertGetId($this->known($table, $values));
    }

    /** Insert many rows (chunked), keeping only real columns. */
    private function rows(string $table, array $rows): void
    {
        foreach (array_chunk(array_map(fn ($r) => $this->known($table, $r), $rows), 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function known(string $table, array $values): array
    {
        self::$columnCache[$table] ??= array_flip(Schema::getColumnListing($table));
        return array_intersect_key($values, self::$columnCache[$table]);
    }

    /** Active staff with their login (admin) id, name, rank, department and position. */
    private function people(): object
    {
        return $this->people ??= $this->staff(['Active']);
    }

    /** Staff in these employment statuses, same fields as people(); not cached. */
    private function staff(array $statuses): object
    {
        return DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('e.resort_id', $this->resortId)->whereIn('e.status', $statuses)->whereNull('e.deleted_at')->orderBy('e.id')
            ->get(['e.id', 'e.Emp_id', 'e.status', 'e.Admin_Parent_id as admin', 'e.rank', 'e.Dept_id', 'e.Position_id', 'e.reporting_to',
                'e.employment_type', 'e.nationality', 'e.joining_date', 'a.first_name', 'a.last_name', 'a.email', 'a.gender', 'd.name as dept'])
            ->keyBy('id');
    }

    /** The employee behind a demo login, e.g. 'hr', 'gm', 'hod.kitchen'. */
    private function login(string $local): object
    {
        $p = $this->people()->firstWhere('email', $local . '@' . config('demo.login_domain'));
        if (!$p) {
            throw new \RuntimeException("Demo login {$local}@ not found.");
        }
        return $p;
    }

    private function name(object $p): string
    {
        return trim($p->first_name . ' ' . $p->last_name);
    }

    private function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }
}
