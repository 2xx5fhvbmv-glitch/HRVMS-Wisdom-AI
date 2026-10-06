<?php

namespace App\Services\ResortDataSetup;

use App\Models\ResortDataImport;
use Illuminate\Support\Facades\DB;

/**
 * What one import did, row by row: created / changed (with old and new
 * values) / removed, plus the source file and row. Collected while the
 * import runs — the dry run turns it into the "what would change" list —
 * saved with a committed import, and replayed backwards to undo it.
 */
class ImportLedger
{
    private array $entries = [];
    private ?string $file = null;
    private ?int $row = null;

    /** Source file/row for the entries that follow. */
    public function at(?string $file, ?int $row): void
    {
        $this->file = $file;
        $this->row = $row;
    }

    public function created(string $kind, string $table, int $id, string $label): void
    {
        $this->add($kind, $table, $id, 'created', null, $label);
    }

    /** Rows inserted in bulk, identified by a where clause (array value = whereIn). */
    public function createdWhere(string $kind, string $table, array $where, string $label): void
    {
        $this->add($kind, $table, null, 'created_where', $where, $label);
    }

    /** @param array $changes field => [old, new] */
    public function updated(string $kind, string $table, int $id, array $changes, string $label): void
    {
        $this->add($kind, $table, $id, 'updated', $changes, $label);
    }

    /** The full row, so undo can put it back. */
    public function deleted(string $kind, string $table, array $row, string $label): void
    {
        $this->add($kind, $table, $row['id'] ?? null, 'deleted', $row, $label);
    }

    private function add(string $kind, string $table, ?int $id, string $action, ?array $data, string $label): void
    {
        $this->entries[] = ['kind' => $kind, 'table' => $table, 'id' => $id, 'action' => $action, 'data' => $data,
            'label' => $label, 'file' => $this->file, 'row' => $this->row];
    }

    public function entries(): array
    {
        return $this->entries;
    }

    public function save(int $importId): void
    {
        $now = now();
        foreach (array_chunk($this->entries, 500) as $chunk) {
            DB::table('resort_data_import_records')->insert(array_map(fn ($e) => [
                'import_id' => $importId, 'kind' => $e['kind'], 'table_name' => $e['table'], 'record_id' => $e['id'],
                'action' => $e['action'], 'data' => $e['data'] === null ? null : json_encode($e['data']),
                'label' => mb_substr($e['label'], 0, 255), 'source_file' => $e['file'], 'source_row' => $e['row'], 'created_at' => $now,
            ], $chunk));
        }
    }

    /** null and '' are the same; numbers by value ("1500.00" = 1500); everything else as text (DB returns strings). */
    public static function same($a, $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }
        return (string) ($a ?? '') === (string) ($b ?? '');
    }

    /**
     * Reverse an import. Refused when the imported staff have started using
     * the system (that data would be orphaned); a field changed after the
     * import is kept as it is now and reported, never overwritten.
     */
    public static function undo(ResortDataImport $import): array
    {
        $entries = DB::table('resort_data_import_records')->where('import_id', $import->id)->orderByDesc('id')->get();
        if ($blockers = self::blockers($import, $entries)) {
            return ['undone' => false, 'blockers' => $blockers];
        }

        $rows = 0;
        $conflicts = [];
        DB::transaction(function () use ($entries, &$rows, &$conflicts) {
            foreach ($entries as $e) {
                $data = $e->data === null ? null : json_decode($e->data, true);
                switch ($e->action) {
                    case 'created':
                        $rows += DB::table($e->table_name)->where('id', $e->record_id)->delete();
                        break;
                    case 'created_where':
                        $q = DB::table($e->table_name);
                        foreach ($data as $column => $value) {
                            is_array($value) ? $q->whereIn($column, $value) : $q->where($column, $value);
                        }
                        $rows += $q->delete();
                        break;
                    case 'updated':
                        $current = (array) DB::table($e->table_name)->where('id', $e->record_id)->first(array_keys($data));
                        $restore = [];
                        foreach ($data as $field => [$old, $new]) {
                            if ($current && self::same($current[$field] ?? null, $new)) {
                                $restore[$field] = $old;
                            } else {
                                $conflicts[] = "{$e->label}: {$field} was changed after the import — kept as it is now.";
                            }
                        }
                        if ($restore) {
                            DB::table($e->table_name)->where('id', $e->record_id)->update($restore);
                            $rows++;
                        }
                        break;
                    case 'deleted':
                        if (!DB::table($e->table_name)->where('id', $data['id'])->exists()) {
                            DB::table($e->table_name)->insert($data);
                            $rows++;
                        }
                        break;
                }
            }
        });

        return ['undone' => true, 'rows' => $rows, 'conflicts' => array_values(array_unique($conflicts))];
    }

    /** Signs the imported staff are already in use — undoing would orphan real data. */
    private static function blockers(ResortDataImport $import, $entries): array
    {
        $employeeIds = $entries->where('table_name', 'employees')->where('action', 'created')->pluck('record_id')->all();
        $adminIds = $entries->where('table_name', 'resort_admins')->where('action', 'created')->pluck('record_id')->all();
        if (!$employeeIds) {
            return [];
        }
        $since = $import->imported_at;
        $blockers = [];
        if ($n = DB::table('resort_admins')->whereIn('id', $adminIds)->where('must_change_password', 0)->count()) {
            $blockers[] = "{$n} imported staff have already logged in and set their own password.";
        }
        if ($n = DB::table('employees_leaves')->whereIn('emp_id', $employeeIds)->where('created_at', '>', $since)->count()) {
            $blockers[] = "{$n} leave record(s) were added for imported staff after the import.";
        }
        if ($n = DB::table('parent_attendaces')->whereIn('Emp_id', $employeeIds)->where('created_at', '>', $since)->count()) {
            $blockers[] = "{$n} attendance day(s) were recorded for imported staff after the import.";
        }
        return $blockers;
    }
}
