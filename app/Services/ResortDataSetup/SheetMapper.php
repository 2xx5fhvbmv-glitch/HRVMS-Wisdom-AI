<?php

namespace App\Services\ResortDataSetup;

use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Turns a client's export from their previous HR system (report-style
 * xls/xlsx/csv: title rows, merged cells, department group rows, page
 * headers, totals) into plain records.
 *
 * Token budget: the AI never sees data rows. It only gets the first ~16
 * non-empty rows of a file whose header layout has never been seen, and
 * answers which row is the header and which column holds which field.
 * That answer is stored by header fingerprint, so every later file with
 * the same layout (same HR system, any resort, any month) costs 0 tokens.
 * A mapping is stored by header LABEL, not column index, so it survives
 * columns shifting between exports.
 */
class SheetMapper
{
    /** type => [field => required] */
    const FIELDS = [
        'divisions'   => ['name' => true, 'short_name' => false, 'status' => false],
        'departments' => ['name' => true, 'division' => true, 'short_name' => false, 'status' => false],
        'sections'    => ['name' => true, 'department' => false, 'division_department' => false, 'short_name' => false, 'status' => false],
        'positions'   => ['title' => true, 'department' => true, 'division' => false, 'section' => false, 'level' => false],
        'levels'      => ['name' => true],
        'staff'       => ['emp_id' => true, 'name' => true, 'position' => true, 'level' => true, 'department' => false, 'section' => false,
                          'hire_date' => false, 'gender' => false, 'nationality' => false, 'religion' => false, 'email' => false],
        'attendance'  => ['emp_id' => true, 'name' => false],
    ];

    const MAX_ROWS = 5000;
    const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** First worksheet as a list of rows of cleaned strings. */
    public static function readRows(string $storedPath): array
    {
        // IOFactory picks the reader from the extension, so the temp copy keeps it.
        $base = tempnam(sys_get_temp_dir(), 'rds');
        $tmp = $base . '.' . pathinfo($storedPath, PATHINFO_EXTENSION);
        file_put_contents($tmp, StorageHelper::get($storedPath));
        @unlink($base);
        $book = null;
        try {
            $book = IOFactory::load($tmp);
            // ponytail: first sheet only — HR report exports are single-sheet.
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > self::MAX_ROWS) {
                throw new \RuntimeException('File has more than ' . self::MAX_ROWS . ' rows — split it and upload the parts.');
            }
            // Formatted values (keeps "0433" as text), formulas not evaluated.
            $rows = $sheet->toArray(null, false, true, false);
        } finally {
            // Cells hold circular references; without this every file read
            // in one request stays in memory (7 files > 128M).
            $book?->disconnectWorksheets();
            unset($book, $sheet);
            @unlink($tmp);
        }

        return array_map(fn ($row) => array_map([self::class, 'clean'], $row), $rows);
    }

    public static function clean($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private static function label($value): string
    {
        return strtolower(self::clean($value));
    }

    /**
     * Identity of a header row: its text labels in order. Weekday names and
     * bare numbers are left out so an attendance sheet keeps one identity
     * whichever weekday the month starts on.
     */
    public static function fingerprint(array $row): ?string
    {
        $labels = [];
        foreach ($row as $cell) {
            $l = self::label($cell);
            if ($l !== '' && !ctype_digit($l) && !in_array($l, self::WEEKDAYS, true)) {
                $labels[] = $l;
            }
        }
        return count($labels) >= 2 ? sha1(implode('|', $labels)) : null;
    }

    /** Cached mapping first, AI only for a never-seen layout. */
    public static function guess(array $rows, string $fileName): array
    {
        $prints = array_filter(array_map([self::class, 'fingerprint'], array_slice($rows, 0, 40)));
        if ($prints) {
            $hit = DB::table('resort_data_import_mappings')->whereIn('fingerprint', $prints)->first();
            if ($hit) {
                return ['mapping' => json_decode($hit->mapping, true), 'source' => 'cache', 'tokens' => 0, 'error' => null];
            }
        }

        return self::askAi($rows, $fileName);
    }

    public static function remember(array $mapping, string $source): void
    {
        DB::table('resort_data_import_mappings')->updateOrInsert(
            ['fingerprint' => $mapping['fingerprint']],
            ['mapping' => json_encode($mapping), 'source' => $source, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Build a mapping from column indexes (AI answer or admin's selects).
     * Returns [mapping|null, error|null].
     */
    public static function fromIndexes(array $rows, string $type, int $headerIdx, array $columns, $groupCol): array
    {
        if (!isset(self::FIELDS[$type])) {
            return [null, "Unknown file type '{$type}'."];
        }
        $header = $rows[$headerIdx] ?? null;
        $print = $header ? self::fingerprint($header) : null;
        if (!$print) {
            return [null, 'The chosen header row has no column titles.'];
        }

        $mapping = ['type' => $type, 'fingerprint' => $print, 'header_row' => $headerIdx, 'columns' => [], 'group' => null];
        foreach ($columns as $field => $idx) {
            if ($idx === null || $idx === '' || !isset(self::FIELDS[$type][$field])) {
                continue;
            }
            $label = self::label($header[(int) $idx] ?? '');
            if ($label === '') {
                return [null, "Column {$idx} chosen for '{$field}' has no title in the header row."];
            }
            $mapping['columns'][$field] = $label;
        }
        if ($groupCol !== null && $groupCol !== '') {
            $mapping['group'] = self::label($header[(int) $groupCol] ?? '') ?: null;
        }

        $missing = self::missingFields($mapping);
        return $missing ? [null, 'Missing required column(s): ' . implode(', ', $missing)] : [$mapping, null];
    }

    public static function missingFields(array $mapping): array
    {
        $cols = $mapping['columns'] ?? [];
        $missing = [];
        foreach (self::FIELDS[$mapping['type']] ?? [] as $field => $required) {
            if ($required && !isset($cols[$field])) {
                $missing[] = $field;
            }
        }
        if ($mapping['type'] === 'sections' && !isset($cols['department']) && !isset($cols['division_department'])) {
            $missing[] = 'department (or division_department)';
        }
        if ($mapping['type'] === 'staff' && !isset($cols['department']) && empty($mapping['group'])) {
            $missing[] = 'department (column or group rows)';
        }
        return $missing;
    }

    /**
     * Apply a mapping to every row. Returns
     * ['header' => idx, 'columns' => [field => idx], 'group_column' => ?idx, 'records' => [...],
     *  'days' => [col => dayNo], 'period_guess' => Y-m-d|null, 'error' => ?string]
     */
    public static function parse(array $rows, array $mapping): array
    {
        $out = ['header' => null, 'columns' => [], 'group_column' => null, 'records' => [], 'days' => null, 'period_guess' => null, 'error' => null];

        $h = null;
        foreach (array_slice($rows, 0, 40, true) as $i => $row) {
            if (self::fingerprint($row) === $mapping['fingerprint']) {
                $h = $i;
                break;
            }
        }
        if ($h === null) {
            $out['error'] = 'Header row not found — this file does not match its mapping.';
            return $out;
        }
        $out['header'] = $h;

        $labels = array_map([self::class, 'label'], $rows[$h]);
        $idx = [];
        foreach ($mapping['columns'] as $field => $label) {
            $pos = array_search($label, $labels, true);
            if ($pos === false) {
                $out['error'] = "Column '{$label}' ({$field}) not found in the header row.";
                return $out;
            }
            $idx[$field] = $pos;
        }
        $groupIdx = $mapping['group'] ? array_search($mapping['group'], $labels, true) : false;
        $out['columns'] = $idx;
        $out['group_column'] = $groupIdx === false ? null : $groupIdx;
        $required = array_keys(array_filter(self::FIELDS[$mapping['type']]));

        if ($mapping['type'] === 'attendance') {
            $out['days'] = self::dayColumns($rows, $h, max($idx));
            if (!$out['days']) {
                $out['error'] = 'Could not find the day-of-month columns (1..31) in this attendance sheet.';
                return $out;
            }
            $out['period_guess'] = self::periodGuess($rows, $h, reset($out['days']));
        }

        $group = null;
        for ($r = $h + 1, $n = count($rows); $r < $n; $r++) {
            $row = $rows[$r];
            if (self::fingerprint($row) === $mapping['fingerprint']) {
                continue; // header repeated on a later page
            }
            $values = [];
            foreach ($idx as $field => $pos) {
                $values[$field] = $row[$pos] ?? '';
            }

            $complete = true;
            foreach ($required as $field) {
                if (($values[$field] ?? '') === '') {
                    $complete = false;
                    break;
                }
            }

            if ($complete) {
                $record = ['row' => $r + 1, 'v' => $values, 'group' => $group];
                if ($out['days']) {
                    foreach (array_keys($out['days']) as $col) {
                        $record['codes'][$col] = strtoupper($row[$col] ?? '');
                    }
                }
                $out['records'][] = $record;
            } elseif ($groupIdx !== false && ($row[$groupIdx] ?? '') !== ''
                && count(array_filter($row, fn ($c) => $c !== '')) === 1) {
                $group = $row[$groupIdx];
            }
        }

        return $out;
    }

    /** Record count, facets and a small preview (for manual mapping) of a file. */
    public static function describe(array $entry, array $rows): array
    {
        $entry['preview'] = array_map(
            fn ($row) => array_map(fn ($c) => mb_substr($c, 0, 40), $row),
            array_slice($rows, 0, 25)
        );
        $entry['count'] = null;
        $entry['facets'] = [];
        if (!empty($entry['mapping'])) {
            $parsed = self::parse($rows, $entry['mapping']);
            $entry['note'] = $parsed['error'] ?? ($entry['note'] ?? null);
            $entry['header_row'] = $parsed['header'];
            $entry['columns_idx'] = $parsed['columns'];
            $entry['group_idx'] = $parsed['group_column'];
            $entry['count'] = count($parsed['records']);
            $entry['facets'] = $parsed['error'] ? [] : self::facets($entry['mapping']['type'], $parsed);
        }
        return $entry;
    }

    /** Distinct values the options form needs, computed once per mapping. */
    public static function facets(string $type, array $parsed): array
    {
        $col = fn ($field) => array_values(array_unique(array_filter(array_map(fn ($r) => $r['v'][$field] ?? '', $parsed['records']))));

        return match ($type) {
            'staff'      => ['levels' => $col('level'),
                             'departments' => array_values(array_unique(array_filter(array_map(fn ($r) => $r['v']['department'] ?? $r['group'], $parsed['records']))))],
            'levels'     => ['levels' => $col('name')],
            'positions'  => ['levels' => $col('level')],
            'departments'=> ['departments' => $col('name')],
            'attendance' => ['codes' => array_values(array_unique(array_merge(...array_map(fn ($r) => array_values($r['codes']), $parsed['records']) ?: [[]]))),
                             'period_guess' => $parsed['period_guess'],
                             'days' => count($parsed['days'] ?? [])],
            default      => [],
        };
    }

    /**
     * The longest run of consecutive day numbers (wrapping 28..31 -> 1) in
     * the header row or the two rows under it, right of the mapped columns.
     */
    private static function dayColumns(array $rows, int $h, int $after): ?array
    {
        $best = [];
        for ($r = $h; $r <= $h + 2; $r++) {
            $run = [];
            $prev = null;
            foreach ($rows[$r] ?? [] as $c => $cell) {
                if ($c <= $after) {
                    continue;
                }
                $day = ctype_digit($cell) ? (int) $cell : 0;
                $next = $day >= 1 && $day <= 31 && ($prev === null || $day === $prev + 1 || ($day === 1 && $prev >= 28));
                if (!$next) {
                    $run = $day >= 1 && $day <= 31 ? [$c => $day] : [];
                    $prev = $run ? $day : null;
                    continue;
                }
                $run[$c] = $day;
                $prev = $day;
                if (count($run) > count($best)) {
                    $best = $run;
                }
            }
        }
        return count($best) >= 28 ? $best : null;
    }

    /** A dd/mm/yyyy above the header whose day equals the first day column ("Period : 26/05/2026 to ..."). */
    private static function periodGuess(array $rows, int $h, int $firstDay): ?string
    {
        for ($r = 0; $r < $h; $r++) {
            foreach ($rows[$r] as $cell) {
                if (preg_match_all('#\b(\d{1,2})/(\d{1,2})/(\d{4})\b#', $cell, $m, PREG_SET_ORDER)) {
                    foreach ($m as [, $d, $mo, $y]) {
                        if ((int) $d === $firstDay && checkdate((int) $mo, (int) $d, (int) $y)) {
                            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
                        }
                    }
                }
            }
        }
        return null;
    }

    private static function askAi(array $rows, string $fileName): array
    {
        $fail = fn ($msg) => ['mapping' => null, 'source' => null, 'tokens' => 0, 'error' => $msg];
        $key = config('services.openrouter.key');
        if (empty($key)) {
            return $fail('AI is not configured (OPENROUTER_API_KEY) — map the columns manually below.');
        }

        $lines = [];
        foreach ($rows as $i => $row) {
            $cells = [];
            foreach ($row as $c => $v) {
                if ($v !== '') {
                    $cells[] = $c . '=' . mb_substr($v, 0, 30);
                }
            }
            if ($cells) {
                $lines[] = "r{$i}: " . implode(' | ', $cells);
            }
            if (count($lines) >= 16) {
                break;
            }
        }

        $system = <<<'TXT'
You identify the layout of a spreadsheet exported from an HR system. Reply with ONLY a JSON object:
{"type":T,"header_row":N,"columns":{"field":col},"group_column":col or null}
N and col are the r and column numbers shown in the input. header_row is the row holding the column titles.
group_column: only when label-only rows (e.g. a department name alone on a row) group the data rows below them; the column that label sits in.
Types and fields (* = required):
divisions: name*, short_name, status
departments: name*, division*, short_name, status
sections: name*, short_name, status, department, division_department (one combined "Division - Department" column)
positions: title*, department*, division, section, level
levels: name* (a list of grades/levels)
staff: emp_id*, name*, position*, level*, department, section, hire_date, gender, nationality (country), religion, email
attendance: emp_id*, name (one row per employee, one column per day of the month)
unknown: nothing fits; columns {}.
Map only columns that clearly match. Never invent columns.
TXT;

        try {
            $resp = Http::withToken($key)
                ->withHeaders(['HTTP-Referer' => config('app.url'), 'X-Title' => 'HRVMS Resort Data Setup'])
                ->timeout(60)
                ->post(rtrim(config('services.openrouter.base_url'), '/') . '/chat/completions', [
                    'model'       => config('services.openrouter.model'),
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => "File: {$fileName}\n" . implode("\n", $lines)],
                    ],
                    'temperature' => 0,
                    // Reasoning models spend max_tokens on thinking too; keep it low.
                    'reasoning'   => ['effort' => 'low'],
                    'max_tokens'  => 2000,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Resort data setup: AI request failed', ['error' => $e->getMessage()]);
            return $fail('Could not reach the AI service — map the columns manually below.');
        }

        if (!$resp->successful()) {
            Log::warning('Resort data setup: AI non-200', ['status' => $resp->status(), 'body' => mb_substr($resp->body(), 0, 500)]);
            return $fail('AI service returned an error (HTTP ' . $resp->status() . ') — map the columns manually below.');
        }

        $tokens = (int) $resp->json('usage.total_tokens', 0);
        $content = (string) $resp->json('choices.0.message.content', '');
        $answer = preg_match('/\{.*\}/s', $content, $m) ? json_decode($m[0], true) : null;
        if (!is_array($answer) || !isset($answer['type'], $answer['header_row']) || !is_array($answer['columns'] ?? null)) {
            return ['mapping' => null, 'source' => null, 'tokens' => $tokens, 'error' => 'AI answer could not be read — map the columns manually below.'];
        }
        if ($answer['type'] === 'unknown') {
            return ['mapping' => null, 'source' => null, 'tokens' => $tokens, 'error' => 'AI could not recognise this file — choose its type and columns manually below.'];
        }

        $columns = array_filter($answer['columns'], fn ($v) => is_int($v) || ctype_digit((string) $v));
        $group = isset($answer['group_column']) && (is_int($answer['group_column']) || ctype_digit((string) $answer['group_column'])) ? $answer['group_column'] : null;
        [$mapping, $error] = self::fromIndexes($rows, (string) $answer['type'], (int) $answer['header_row'], $columns, $group);
        if (!$mapping) {
            return ['mapping' => null, 'source' => null, 'tokens' => $tokens, 'error' => "AI mapping rejected ({$error}) — map the columns manually below."];
        }

        self::remember($mapping, 'ai');
        return ['mapping' => $mapping, 'source' => 'ai', 'tokens' => $tokens, 'error' => null];
    }
}
