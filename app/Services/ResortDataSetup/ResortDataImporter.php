<?php

namespace App\Services\ResortDataSetup;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\ResortDataImport;
use App\Models\ShiftSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Writes a resort's parsed client files into the live tables, in dependency
 * order: divisions → departments → sections → positions → staff → employee
 * details → holidays → attendance.
 *
 * One code path for both modes: a dry run performs every write inside a
 * transaction and rolls it back, so "Validate" reports exactly what
 * "Import" will do. Import commits only with zero errors — all or nothing.
 *
 * Re-runs are safe: rows are matched (case-insensitive name within the
 * resort, Emp_id for staff, employee + date for attendance); only fields
 * that differ are written. Every write goes through the ImportLedger, which
 * gives the dry run its "what would change" list and makes an import undoable.
 *
 * Master-data tables are written with DB::table on purpose: the
 * ResortDivision/Department/Section/Position/DutyRoster saving hooks read
 * Auth::guard('resort-admin')->user()->id unguarded, which is null in the
 * super-admin console.
 */
class ResortDataImporter
{
    const ORDER = ['divisions', 'departments', 'sections', 'positions', 'staff', 'employee_details', 'holidays', 'attendance'];
    const LEAVE_REASON = 'Migrated from client attendance sheet';
    const PRESENT_HOURS = 8; // a P / blank day counts as a full 8-hour duty
    const BLANK = '(blank)'; // option key for an empty attendance cell

    // Default level → Position_Rank by level-name prefix; overridable per level.
    const LEVEL_PREFIX_RANK = ['HOD' => 2, 'MGR' => 4, 'SUP' => 5, 'EX' => 1, 'LS' => 6];
    // EXCOM / HOD / MGR in a role department get that department's main_rank.
    const ROLE_LEVEL_RANKS = [1, 2, 4];
    const ROLE_MAIN_RANK = ['hr' => 3, 'finance' => 7, 'clinic' => 12];
    const ROLE_KEYWORDS = ['hr' => ['human resource'], 'finance' => ['finance', 'account'], 'clinic' => ['clinic', 'medical']];
    // Confirmed meaning of the client's attendance codes → default action.
    const CODE_HINTS = [self::BLANK => 'present', 'P' => 'present', 'BT' => 'present', 'DO' => 'dayoff',
        'AL' => 'annual', 'SL' => 'sick', 'EL' => 'emergency', 'PL' => 'paternity', 'ML' => 'maternity',
        'CL' => 'circumcision', 'BD' => 'birthday', 'RR' => 'rest', 'NP' => 'unpaid', 'UL' => 'unpaid', 'PH' => 'public holiday'];

    private int $resortId;
    private ?int $createdBy;
    private bool $commit;
    private array $opt;
    private array $errors = [];
    private array $warnings = []; // message => occurrences
    private array $counts = [];
    private array $credentials = [];

    // Lowercase-name lookups for this resort, kept current as rows are written.
    private array $divisions = [];   // name => id
    private array $departments = []; // name => ['id', 'division_id', 'name']
    private array $sections = [];    // "deptId|name" => id
    private array $positions = [];   // "deptId|title" => id
    private array $newPositions = []; // id => title, created from the positions file, rank not known yet
    private array $levelledPositions = []; // id => true, rank given by the positions file's Level column
    private ?string $prefix = null;   // resort prefix for Employee IDs; '' once found invalid
    private ImportLedger $ledger;
    private array $missingStaff = []; // employees in the resort but not in the staff file

    /** Saved options merged over defaults derived from the uploaded files. */
    public static function options(ResortDataImport $import): array
    {
        $saved = $import->options ?? [];
        $files = $import->files ?? [];
        $facet = fn ($key) => array_values(array_unique(array_merge([], ...array_map(fn ($f) => (array) ($f['facets'][$key] ?? []), $files))));
        $resort = Resort::find($import->resort_id);

        $levels = [];
        foreach ($facet('levels') as $level) {
            $levels[$level] = $saved['level_ranks'][$level] ?? self::levelRank($level);
        }

        $departments = array_values(array_unique(array_merge(
            $facet('departments'),
            DB::table('resort_departments')->where('resort_id', $import->resort_id)->pluck('name')->all()
        )));
        $roles = [];
        foreach (self::ROLE_KEYWORDS as $role => $words) {
            $roles[$role] = $saved['roles'][$role] ?? (collect($departments)->first(
                fn ($d) => collect($words)->contains(fn ($w) => str_contains(strtolower($d), $w))
            ) ?? '');
        }

        $categories = LeaveCategory::where('resort_id', $import->resort_id)->pluck('leave_type', 'id');
        $codes = [];
        foreach ($facet('codes') as $code) {
            $code = $code === '' ? self::BLANK : $code;
            $codes[$code] = $saved['codes'][$code] ?? self::codeDefault($code, $categories);
        }

        $periodGuess = collect($files)->pluck('facets.period_guess')->filter()->first();

        return [
            'email_domain' => $saved['email_domain'] ?? Str::slug($resort->resort_name ?? 'resort') . '-' . $import->resort_id . '.wisdom.local',
            'level_ranks'  => $levels,
            'departments'  => $departments,
            'roles'        => $roles,
            'codes'        => $codes,
            'period_start' => $saved['period_start'] ?? $periodGuess ?? '',
            'shift_id'     => $saved['shift_id'] ?? '',
        ];
    }

    public static function levelRank(string $level): string
    {
        $level = strtoupper(trim($level));
        foreach (self::LEVEL_PREFIX_RANK as $prefix => $rank) {
            if (str_starts_with($level, $prefix)) {
                return (string) $rank;
            }
        }
        return '';
    }

    private static function codeDefault(string $code, $categories): string
    {
        $hint = self::CODE_HINTS[$code] ?? null;
        if ($hint === null || in_array($hint, ['present', 'dayoff'], true)) {
            return (string) $hint;
        }
        foreach ($categories as $id => $type) {
            if (str_contains(strtolower($type), $hint)) {
                return 'leave:' . $id;
            }
        }
        return '';
    }

    public function run(ResortDataImport $import, bool $commit): array
    {
        $this->opt = self::options($import);
        $byType = [];
        foreach ($import->files ?? [] as $file) {
            if (empty($file['mapping'])) {
                $this->error($file['name'], null, 'File has no column mapping yet.');
                continue;
            }
            try {
                $parsed = SheetMapper::parse(SheetMapper::readRows($file['path']), $file['mapping']);
            } catch (\Throwable $e) {
                $this->error($file['name'], null, 'Could not read file: ' . $e->getMessage());
                continue;
            }
            if ($parsed['error']) {
                $this->error($file['name'], null, $parsed['error']);
                continue;
            }
            $byType[$file['mapping']['type']][] = ['name' => $file['name'], 'parsed' => $parsed];
        }
        return $this->execute((int) $import->resort_id, $byType, $commit, $import->id);
    }

    /**
     * Imports records already in SheetMapper::parse() shape, without files or an
     * undo ledger — Demo ENV builds its organisation and people this way, so they
     * go through exactly the writes a real client import does.
     * $byType: type => [['name' => label, 'parsed' => ['records' => [['row', 'v', 'group']]]]].
     */
    public function importRecords(int $resortId, array $byType, array $options): array
    {
        $this->opt = $options + ['email_domain' => 'resort-' . $resortId . '.wisdom.local', 'level_ranks' => [], 'roles' => [], 'codes' => [], 'period_start' => '', 'shift_id' => ''];
        return $this->execute($resortId, $byType, true, null);
    }

    private function execute(int $resortId, array $byType, bool $commit, ?int $importId): array
    {
        @set_time_limit(900);
        $this->commit = $commit;
        $this->ledger = new ImportLedger;
        $this->resortId = $resortId;
        $this->createdBy = ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->orderBy('id')->value('id');

        DB::beginTransaction();
        try {
            $this->loadLookups();
            foreach (self::ORDER as $type) {
                foreach ($byType[$type] ?? [] as $file) {
                    match ($type) {
                        'divisions'   => $this->importDivisions($file['name'], $file['parsed']),
                        'departments' => $this->importDepartments($file['name'], $file['parsed']),
                        'sections'    => $this->importSections($file['name'], $file['parsed']),
                        'positions'   => $this->importPositions($file['name'], $file['parsed']),
                        'staff'       => $this->importStaff($file['name'], $file['parsed']),
                        'employee_details' => $this->importEmployeeDetails($file['name'], $file['parsed']),
                        'holidays'    => $this->importHolidays($file['name'], $file['parsed']),
                        'attendance'  => $this->importAttendance($file['name'], $file['parsed']),
                    };
                }
            }
        } catch (\Throwable $e) {
            Log::error('Resort data setup failed', ['resort_id' => $this->resortId, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            $this->error(null, null, 'Unexpected error: ' . $e->getMessage());
        }

        $committed = $commit && !$this->errors;
        if ($committed) {
            if ($importId) {
                $this->ledger->save($importId);
            }
            DB::commit();
        } else {
            DB::rollBack();
        }

        // What changed on records that already existed (attendance days are only counted).
        $changes = [];
        foreach ($this->ledger->entries() as $e) {
            if ($e['action'] === 'updated' && $e['kind'] !== 'attendance' && count($changes) < 300) {
                $changes[] = ['kind' => $e['kind'], 'label' => $e['label'], 'changes' => $e['data']];
            }
        }

        return [
            'mode'        => $commit ? 'import' : 'dry-run',
            'committed'   => $committed,
            'at'          => now()->toDateTimeString(),
            'counts'      => $this->counts,
            'errors'      => $this->errors,
            'warnings'    => $this->warnings,
            'changes'     => $changes,
            'missing'     => $this->missingStaff,
            'credentials' => $committed ? $this->credentials : [],
        ];
    }

    private function loadLookups(): void
    {
        $q = fn ($table) => DB::table($table)->where('resort_id', $this->resortId);
        foreach ($q('resort_divisions')->get(['id', 'name']) as $d) {
            $this->divisions[self::key($d->name)] = $d->id;
        }
        foreach ($q('resort_departments')->get(['id', 'division_id', 'name']) as $d) {
            $this->departments[self::key($d->name)] = ['id' => $d->id, 'division_id' => $d->division_id, 'name' => $d->name];
        }
        foreach ($q('resort_sections')->get(['id', 'dept_id', 'name']) as $s) {
            $this->sections[$s->dept_id . '|' . self::key($s->name)] = $s->id;
        }
        foreach ($q('resort_positions')->get(['id', 'dept_id', 'position_title', 'employee_category']) as $p) {
            $this->positions[$p->dept_id . '|' . self::key($p->position_title) . ($p->employee_category ? '|' . $p->employee_category : '')] = $p->id;
        }
    }

    private function importDivisions(string $file, array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $this->ledger->at($file, $rec['row']);
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['short_name' => $short, 'status' => self::status($v['status'] ?? '')];
            $label = "Division {$v['name']}";
            if ($id = $this->divisions[self::key($v['name'])] ?? null) {
                $this->count('divisions', $this->updateRow('divisions', 'resort_divisions', $id, $data, $label) ? 'updated' : 'unchanged');
                continue;
            }
            $this->divisions[self::key($v['name'])] = $this->insertRow('divisions', 'resort_divisions', $data + $this->newRow([
                'name' => $v['name'], 'code' => $short, 'slug' => Str::slug($v['name']),
            ]), $label);
            $this->count('divisions', 'created');
        }
    }

    private function importDepartments(string $file, array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $divisionId = $this->divisions[self::key($v['division'])] ?? null;
            if (!$divisionId) {
                $this->error($file, $rec['row'], "Division '{$v['division']}' not found for department '{$v['name']}'.");
                continue;
            }
            $this->ledger->at($file, $rec['row']);
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['division_id' => $divisionId, 'short_name' => $short, 'status' => self::status($v['status'] ?? '')];
            $label = "Department {$v['name']}";
            if ($existing = $this->departments[self::key($v['name'])] ?? null) {
                $this->count('departments', $this->updateRow('departments', 'resort_departments', $existing['id'], $data, $label) ? 'updated' : 'unchanged');
                $this->departments[self::key($v['name'])]['division_id'] = $divisionId;
                continue;
            }
            $id = $this->insertRow('departments', 'resort_departments', $data + $this->newRow([
                'name' => $v['name'], 'code' => $short, 'slug' => Str::slug($v['name']),
            ]), $label);
            $this->departments[self::key($v['name'])] = ['id' => $id, 'division_id' => $divisionId, 'name' => $v['name']];
            $this->count('departments', 'created');
        }
    }

    private function importSections(string $file, array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $deptName = ($v['department'] ?? '') ?: $this->departmentFromCombined($v['division_department'] ?? '');
            $dept = $this->departments[self::key($deptName)] ?? null;
            if (!$dept) {
                $this->error($file, $rec['row'], "Department '{$deptName}' not found for section '{$v['name']}'.");
                continue;
            }
            $this->ledger->at($file, $rec['row']);
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['short_name' => $short, 'status' => self::status($v['status'] ?? '')];
            $label = "Section {$v['name']} ({$dept['name']})";
            $key = $dept['id'] . '|' . self::key($v['name']);
            if ($id = $this->sections[$key] ?? null) {
                $this->count('sections', $this->updateRow('sections', 'resort_sections', $id, $data, $label) ? 'updated' : 'unchanged');
                continue;
            }
            $this->sections[$key] = $this->insertRow('sections', 'resort_sections', $data + $this->newRow([
                'dept_id' => $dept['id'], 'name' => $v['name'], 'code' => $short,
            ]), $label);
            $this->count('sections', 'created');
        }
    }

    /** "FINANCE - INFORMATION AND TECHNOLOGY" → department part, split after a known division name. */
    private function departmentFromCombined(string $value): string
    {
        $best = '';
        foreach (array_keys($this->divisions) as $division) {
            if (str_starts_with(self::key($value), $division . ' - ') && strlen($division) > strlen($best)) {
                $best = $division;
            }
        }
        if ($best !== '') {
            return trim(substr($value, strlen($best) + 3));
        }
        return trim(explode(' - ', $value, 2)[1] ?? $value);
    }

    private function importPositions(string $file, array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $dept = $this->departments[self::key($v['department'])] ?? null;
            if (!$dept) {
                $this->error($file, $rec['row'], "Department '{$v['department']}' not found for position '{$v['title']}'.");
                continue;
            }
            $this->ledger->at($file, $rec['row']);
            $sectionId = $this->sectionId($dept['id'], $v['section'] ?? '');
            $key = $dept['id'] . '|' . self::key($v['title']);
            if ($id = $this->positions[$key] ?? null) {
                // One position per title per department: the client lists a title
                // once per section, those rows merge into the first one.
                if (isset($this->newPositions[$id])) {
                    $this->count('positions', 'same title in another section (merged)');
                    continue;
                }
                $rank = $this->positionLevelRank($file, $rec['row'], $v);
                if ($rank === false) {
                    continue;
                }
                $changed = $this->updateRow('positions', 'resort_positions', $id, array_filter(['section_id' => $sectionId, 'Rank' => $rank]), self::positionLabel($v['title'], $dept['name']));
                if ($rank) {
                    $this->levelledPositions[$id] = true;
                }
                $this->count('positions', $changed ? 'updated' : 'unchanged');
                continue;
            }
            $rank = $this->positionLevelRank($file, $rec['row'], $v);
            if ($rank === false) {
                continue;
            }
            $id = $this->createPosition($dept['id'], $dept['name'], $v['title'], $sectionId, $rank ?: 6);
            if ($rank) {
                $this->levelledPositions[$id] = true;
            } else {
                $this->newPositions[$id] = $v['title'];
            }
            $this->count('positions', 'created');
        }
    }

    /** Rank from the positions file's own Level column: null when not given, false (error reported) when unmapped. */
    private function positionLevelRank(string $file, int $row, array $v)
    {
        if (($v['level'] ?? '') === '') {
            return null;
        }
        $rank = (int) ($this->opt['level_ranks'][$v['level']] ?? 0);
        if (!$rank) {
            $this->error($file, $row, "Level '{$v['level']}' has no rank — set it under Options.");
            return false;
        }
        return self::gmRank($rank, $v['title']);
    }

    /** GM: an EXCOM-level "General Manager" (not "Assistant General Manager"). */
    private static function gmRank(int $rank, string $title): int
    {
        return $rank === 1 && preg_match('/^general manager\b/i', $title) ? 8 : $rank;
    }

    private function createPosition(int $deptId, string $deptName, string $title, ?int $sectionId, int $rank = 6, ?string $category = null): int
    {
        // Rank 6 is provisional unless the file gave a level; importStaff() sets it from the people who hold the position.
        // Casual/Intern positions carry employee_category and rank 0, like PositionConfigController makes them.
        $id = $this->insertRow('positions', 'resort_positions', $this->newRow([
            'dept_id' => $deptId, 'section_id' => $sectionId, 'position_title' => $title, 'Rank' => $category ? 0 : $rank,
            'employee_category' => $category, 'status' => 'active', 'is_reserved' => 'No', 'slug' => Str::slug($title),
        ]), self::positionLabel($title . ($category ? " ({$category})" : ''), $deptName));
        $this->positions[$deptId . '|' . self::key($title) . ($category ? '|' . $category : '')] = $id;
        return $id;
    }

    private static function positionLabel(string $title, string $deptName): string
    {
        return "Position {$title} ({$deptName})";
    }

    private function sectionId(int $deptId, string $name): ?int
    {
        if (in_array(strtoupper($name), ['', 'N/A', 'NA', '-'], true)) {
            return null;
        }
        $id = $this->sections[$deptId . '|' . self::key($name)] ?? null;
        if (!$id) {
            $this->warn("Section '{$name}' not found — left empty.");
        }
        return $id;
    }

    private function importStaff(string $file, array $parsed): void
    {
        if (!$this->checkPrefix($file)) {
            return;
        }
        $seen = [];
        $touched = [];
        $positionRanks = [];
        $titleRanks = [];
        $legacy = 0;
        $roleDepts = array_filter(array_map([self::class, 'key'], $this->opt['roles']));

        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $row = $rec['row'];
            $empId = $this->empId($v['emp_id']);
            $this->ledger->at($file, $row);

            if (isset($seen[$empId])) {
                $this->error($file, $row, "Employee ID {$empId} appears twice (rows {$seen[$empId]} and {$row}).");
                continue;
            }
            $seen[$empId] = $row;

            $employmentType = self::employmentType($v['employment_type'] ?? '');
            if ($employmentType === false) {
                $this->error($file, $row, "Employment type '{$v['employment_type']}' not recognised for {$empId} — use Full-Time, Part-Time, Contract, Probationary, Temporary, Casual or Internship.");
                continue;
            }
            // Casual/Intern: own position category, rank 0, no app login (same rules as the Casual/Intern importer).
            $category = Common::manningCategory($employmentType ?? 'Full-Time');
            $category = $category === 'Permanent' ? null : $category;
            $rank = $category ? 0 : (int) ($this->opt['level_ranks'][$v['level'] ?? ''] ?? 0);
            if (!$category && !$rank) {
                $this->error($file, $row, ($v['level'] ?? '') === '' ? "Level missing for {$empId}." : "Level '{$v['level']}' has no rank — set it under Options.");
                continue;
            }
            $deptName = ($v['department'] ?? '') ?: (string) $rec['group'];
            $dept = $this->departments[self::key($deptName)] ?? null;
            if (!$dept) {
                $this->error($file, $row, "Department '{$deptName}' not found for {$empId}.");
                continue;
            }
            $gender = match (strtolower($v['gender'] ?? '')) {
                'male', 'm' => 'male',
                'female', 'f' => 'female',
                '' => 'other',
                default => null,
            };
            if (!$gender) {
                $this->error($file, $row, "Gender '{$v['gender']}' not recognised for {$empId}.");
                continue;
            }
            $hireDate = self::parseDate($v['hire_date'] ?? '');
            if ($hireDate === false) {
                $this->error($file, $row, "Hire date '{$v['hire_date']}' is not a valid dd/mm/yyyy date for {$empId}.");
                continue;
            }

            $rank = $category ? 0 : self::gmRank($rank, $v['position']);
            $mainRank = $rank;
            if (in_array($rank, self::ROLE_LEVEL_RANKS, true)) {
                $role = array_search(self::key($dept['name']), $roleDepts, true);
                if ($role !== false) {
                    $mainRank = self::ROLE_MAIN_RANK[$role];
                }
            }

            $sectionId = $this->sectionId($dept['id'], $v['section'] ?? '');
            $positionId = $this->positions[$dept['id'] . '|' . self::key($v['position']) . ($category ? '|' . $category : '')] ?? null;
            if (!$positionId) {
                $positionId = $this->createPosition($dept['id'], $dept['name'], $v['position'], $sectionId, 6, $category);
                if (!$category) {
                    $this->warn("Position '{$v['position']}' ({$dept['name']}) was not in the positions file — created from the staff list.");
                }
            }
            if (!$category) {
                $positionRanks[$positionId][] = $rank;
                $titleRanks[self::key($v['position'])][] = $rank;
            }

            if (($v['gender'] ?? '') === '') {
                $this->warn('Gender missing — stored as "other".');
            }
            $nationality = $this->nationality($v['nationality'] ?? '');
            [$first, $last] = array_pad(explode(' ', $v['name'], 2), 2, '');

            $adminData = ['first_name' => $first, 'last_name' => $last, 'gender' => $gender];
            $employeeData = [
                'resort_id' => $this->resortId, 'Emp_id' => $empId, 'division_id' => $dept['division_id'], 'Dept_id' => $dept['id'],
                'Position_id' => $positionId, 'Section_id' => $sectionId,
                'rank' => $rank, 'main_rank' => $mainRank, 'title' => $gender === 'female' ? 'Miss' : 'Mr',
                'nationality' => $nationality, 'joining_date' => $hireDate, 'religion' => self::religion($v['religion'] ?? ''), 'is_employee' => 1,
            ];
            if ($employmentType) {
                $employeeData['employment_type'] = $employmentType;
            }

            $existing = Employee::where('resort_id', $this->resortId)->where('Emp_id', $empId)->first();
            if (!$existing && $empId !== $v['emp_id']) {
                // Loaded earlier without the prefix (e.g. ImportWisdomAiStaffSeeder): update in place, ID kept.
                $existing = Employee::where('resort_id', $this->resortId)->where('Emp_id', $v['emp_id'])->first();
                $legacy += $existing ? 1 : 0;
            }
            $existingAdmin = null;
            $password = null;
            if ($existing) {
                // Re-import: login email and password are left alone (HR may
                // already have replaced the placeholder with a real email).
                $existingAdmin = ResortAdmin::find($existing->Admin_Parent_id);
                if (!$existingAdmin) {
                    $this->error($file, $row, "Employee {$empId} exists but has no login record.");
                    continue;
                }
            } else {
                $email = filter_var($v['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: $this->placeholderEmail($empId);
                if (!$email) {
                    $this->error($file, $row, "Employee ID '{$empId}' cannot be turned into a login email — use letters, digits, '.', '-' or '_'.");
                    continue;
                }
                if (ResortAdmin::withTrashed()->where('email', $email)->exists()) {
                    $this->error($file, $row, "Login email {$email} is already used by another account.");
                    continue;
                }
                if ($limit = Common::employeeLimitError($this->resortId)) {
                    $this->error($file, $row, $limit);
                    continue;
                }
                // The mobile app logs in by Employee ID alone: it must be unique across resorts.
                if (Employee::withTrashed()->where('Emp_id', $empId)->where('resort_id', '!=', $this->resortId)->exists()) {
                    $this->error($file, $row, "Employee ID {$empId} already exists in another resort.");
                    continue;
                }
                $password = Common::generateUniquePassword(8);
                // Dry run is rolled back: skip the slow bcrypt for every row.
                $adminData += ['email' => $email, 'password' => $this->commit ? Hash::make($password) : '-',
                    'type' => 'sub', 'role_id' => 0, 'is_master_admin' => 0, 'is_employee' => 1, 'status' => 'Active'];
                $employeeData += ['status' => 'Active', 'employment_type' => 'Full-Time', 'probation_status' => 'Confirmed'];
                if ($category) {
                    $password = null; // Casual/Intern have no app access: no credentials to hand out.
                }
            }

            $label = "{$empId} {$v['name']}";
            if ($existing) {
                $touched[] = $existing->id;
                $changed = $this->updateModel('staff', $existingAdmin, $adminData, $label);
                $changed = $this->updateModel('staff', $existing, $employeeData, $label) || $changed;
                $this->count('staff', $changed ? 'updated' : 'unchanged');
                continue;
            }

            $profile = Common::persistEmployeeProfile($adminData, $employeeData, $this->resortId);
            $touched[] = $profile['employee']->id;
            $this->ledger->created('staff', 'resort_admins', $profile['resortAdmin']->id, $label);
            $this->ledger->created('staff', 'employees', $profile['employee']->id, $label);
            // The categorized folder row the Employee::created hook just made.
            if ($folderId = DB::table('filemangement_systems')->where('resort_id', $this->resortId)->where('Folder_Name', $empId)->where('Folder_Type', 'categorized')->value('id')) {
                $this->ledger->created('staff', 'filemangement_systems', $folderId, $label);
            }
            $this->count('staff', $category ? "created ({$category})" : 'created');
            if ($password) {
                $this->credentials[] = ['emp_id' => $empId, 'name' => $v['name'], 'email' => $adminData['email'], 'password' => $password];
            }
        }

        // In the resort but not in this staff file: reported, never changed (resignations are handled in the portal).
        $this->missingStaff = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $this->resortId)->whereNull('e.deleted_at')->whereNotIn('e.id', $touched)
            ->whereNotIn('e.status', ['Terminated', 'Resigned', 'Inactive'])
            ->limit(200)->get(['e.Emp_id', 'a.first_name', 'a.last_name'])
            ->map(fn ($m) => trim("{$m->Emp_id} {$m->first_name} {$m->last_name}"))->all();

        if ($legacy) {
            $this->warn("{$legacy} employee(s) already existed with an un-prefixed Employee ID — updated, ID kept. Their mobile login may clash with other resorts.");
        }

        // A position's rank = the most common rank among the people holding it,
        // unless the positions file gave it a level. A vacant position borrows
        // the rank of the same title held in another department.
        $mode = function (array $ranks) {
            $counts = array_count_values($ranks);
            arsort($counts);
            return array_key_first($counts);
        };
        $this->ledger->at($file, null);
        foreach ($positionRanks as $positionId => $ranks) {
            if (!isset($this->levelledPositions[$positionId])) {
                $this->updateRow('positions', 'resort_positions', $positionId, ['Rank' => $mode($ranks)], $this->positionTitle($positionId));
            }
            unset($this->newPositions[$positionId]);
        }
        foreach ($this->newPositions as $positionId => $title) {
            if ($ranks = $titleRanks[self::key($title)] ?? null) {
                $this->updateRow('positions', 'resort_positions', $positionId, ['Rank' => $mode($ranks)], $this->positionTitle($positionId));
                unset($this->newPositions[$positionId]);
                $this->warn('Vacant position took its rank from the same title in another department.');
            }
        }
        // The HR head (EXCOM position in the HR department) gets full page
        // access by default, so someone can work in every module from day one
        // and hand out access to the other positions.
        if ($hrDept = $this->departments[self::key($this->opt['roles']['hr'] ?? '')] ?? null) {
            $heads = DB::table('resort_positions')->where('resort_id', $this->resortId)->where('dept_id', $hrDept['id'])->where('Rank', 1)->whereNull('employee_category')->pluck('id');
            foreach ($heads as $positionId) {
                if (Common::grantDefaultPageAccess($this->resortId, $positionId)) {
                    $this->ledger->createdWhere('access', 'resort_interal_pages_permissions', ['resort_id' => $this->resortId, 'position_id' => $positionId],
                        'Full page access for ' . $this->positionTitle($positionId));
                    $this->count('positions', 'HR head given full page access');
                }
            }
        }

        if ($this->newPositions) {
            $this->warn(count($this->newPositions) . ' vacant position(s): nobody in the staff list holds them, so their rank defaulted to Line Worker — the resort sets the right rank in Manning → Positions before hiring into them: '
                . implode(', ', array_slice($this->newPositions, 0, 15)) . (count($this->newPositions) > 15 ? ', …' : ''));
        }
    }

    /**
     * Imported Employee IDs are "<resort prefix>-<client ID>" (e.g. HAY-0433):
     * the mobile app logs in by Employee ID alone, so the prefix must exist
     * and belong to this resort only.
     */
    private function checkPrefix(string $file): bool
    {
        if ($this->prefix === null) {
            $prefix = strtoupper(trim((string) DB::table('resorts')->where('id', $this->resortId)->value('resort_prefix')));
            $this->prefix = '';
            if (!preg_match('/^[A-Z0-9]+$/', $prefix)) {
                $this->error($file, null, 'Set a Resort Prefix (letters and digits, e.g. HAY) on the resort\'s edit page — Employee IDs are imported as PREFIX-<client ID>.');
            } elseif ($other = DB::table('resorts')->where('id', '!=', $this->resortId)->where('resort_prefix', $prefix)->value('resort_name')) {
                $this->error($file, null, "Resort Prefix {$prefix} is also used by {$other} — give this resort a unique prefix on its edit page.");
            } else {
                $this->prefix = $prefix;
            }
        }
        return $this->prefix !== '';
    }

    private function empId(string $clientId): string
    {
        return str_starts_with(strtoupper($clientId), $this->prefix . '-') ? $clientId : $this->prefix . '-' . $clientId;
    }

    /** Salary, bank, documents, reporting manager… for employees that already exist (staff file or earlier import). */
    private function importEmployeeDetails(string $file, array $parsed): void
    {
        if (!$this->checkPrefix($file)) {
            return;
        }
        $missing = [];
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $row = $rec['row'];
            $this->ledger->at($file, $row);
            $employee = $this->findEmployee($v['emp_id']);
            if (!$employee) {
                $missing[] = $v['emp_id'];
                continue;
            }
            $admin = ResortAdmin::find($employee->Admin_Parent_id);
            $name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
            $label = "{$employee->Emp_id} {$name}";
            $fail = fn ($msg) => $this->error($file, $row, "{$employee->Emp_id}: {$msg}");

            $data = [];
            $dates = ['dob' => 'dob', 'visa_expiry' => 'visa_expiry_date', 'work_permit_expiry' => 'work_permit_expiry_date'];
            foreach ($dates as $field => $column) {
                $date = self::parseDate($v[$field] ?? '');
                if ($date === false) {
                    $fail("{$field} '{$v[$field]}' is not a valid dd/mm/yyyy date.");
                    continue 2;
                }
                if ($date) {
                    $data[$column] = $date;
                }
            }
            if (($v['passport_number'] ?? '') !== '') {
                $data['passport_number'] = $v['passport_number'];
            }
            if (($v['employment_type'] ?? '') !== '') {
                $type = self::employmentType($v['employment_type']);
                if ($type === false) {
                    $fail("employment type '{$v['employment_type']}' not recognised.");
                    continue;
                }
                if (Common::manningCategory($type) !== Common::manningCategory($employee->employment_type ?? 'Full-Time')) {
                    $fail('moving between permanent and Casual/Intern changes the position — put the employment type in the staff file instead.');
                    continue;
                }
                $data['employment_type'] = $type;
            }
            if (($v['reporting_manager_id'] ?? '') !== '') {
                $manager = $this->findEmployee($v['reporting_manager_id']);
                if (!$manager || $manager->id === $employee->id) {
                    $fail("reporting manager '{$v['reporting_manager_id']}' " . ($manager ? 'is the employee themself.' : 'not found in this resort.'));
                    continue;
                }
                $data['reporting_to'] = $manager->id;
            }
            if (($v['basic_salary'] ?? '') !== '') {
                $salary = str_replace([',', ' '], '', $v['basic_salary']);
                if (!is_numeric($salary) || $salary < 0) {
                    $fail("basic salary '{$v['basic_salary']}' is not a number.");
                    continue;
                }
                $data['basic_salary'] = round((float) $salary, 2);
            }
            if (($v['salary_currency'] ?? '') !== '') {
                if (!$currency = self::currency($v['salary_currency'])) {
                    $fail("salary currency '{$v['salary_currency']}' must be USD or MVR.");
                    continue;
                }
                // Stored as entered, in the currency it was entered in (see CLAUDE.md, Money).
                $data['basic_salary_currency'] = $currency;
            }
            if (($v['payment_mode'] ?? '') !== '') {
                $mode = ucfirst(strtolower($v['payment_mode']));
                if (!in_array($mode, ['Cash', 'Bank'], true)) {
                    $fail("payment mode '{$v['payment_mode']}' must be Cash or Bank.");
                    continue;
                }
                $data['payment_mode'] = $mode;
            }

            $changed = $this->updateModel('details', $employee, $data, $label);
            if (($v['phone'] ?? '') !== '' && $admin) {
                $changed = $this->updateModel('details', $admin, ['personal_phone' => $v['phone']], $label) || $changed;
            }
            $changed = $this->importBank($employee, $name, $v, $label, $fail) || $changed;
            $changed = $this->importVisa($employee, $v, $data, $label) || $changed;
            $this->count('employee_details', $changed ? 'updated' : 'unchanged');
        }
        if ($missing) {
            $this->warn(count($missing) . ' employee-details row(s) skipped — Employee ID not found in this resort: ' . implode(', ', array_slice($missing, 0, 20)));
        }
    }

    /** One bank row per account number (an employee can have several). */
    private function importBank(Employee $employee, string $name, array $v, string $label, \Closure $fail): bool
    {
        if (($v['account_no'] ?? '') === '' && ($v['iban'] ?? '') === '' && ($v['bank_name'] ?? '') === '') {
            return false;
        }
        $currency = ($v['bank_currency'] ?? '') === '' ? 'USD' : self::currency($v['bank_currency']);
        if (!$currency) {
            $fail("bank currency '{$v['bank_currency']}' must be USD or MVR.");
            return false;
        }
        $bank = array_filter([
            'bank_name' => $v['bank_name'] ?? '', 'bank_branch' => $v['bank_branch'] ?? '', 'account_no' => $v['account_no'] ?? '',
            'IBAN' => $v['iban'] ?? '', 'IFSC_BIC' => $v['swift'] ?? '',
        ], fn ($x) => $x !== '') + ['account_holder_name' => ($v['account_holder'] ?? '') ?: $name, 'currency' => $currency];
        $existing = DB::table('employee_bank_details')->where('employee_id', $employee->id)
            ->when(($v['account_no'] ?? '') !== '', fn ($q) => $q->where('account_no', $v['account_no']))->orderBy('id')->value('id');
        if ($existing) {
            return $this->updateRow('details', 'employee_bank_details', $existing, $bank, $label);
        }
        $this->insertRow('details', 'employee_bank_details', $bank + ['employee_id' => $employee->id, 'created_at' => now(), 'updated_at' => now()], $label);
        return true;
    }

    /** The Visa module's expiry screens read visa_renewals, not the employees columns. */
    private function importVisa(Employee $employee, array $v, array $data, string $label): bool
    {
        if (empty($data['visa_expiry_date'])) {
            return false;
        }
        $end = $data['visa_expiry_date'];
        $start = self::parseDate($v['visa_start'] ?? '') ?: Carbon::parse($end)->subYear()->addDay()->format('Y-m-d'); // work visas run a year
        $visa = array_filter(['Visa_Number' => $v['visa_number'] ?? '', 'WP_No' => $v['work_permit_number'] ?? ''], fn ($x) => $x !== '')
            + ['start_date' => $start, 'end_date' => $end];
        $existing = DB::table('visa_renewals')->where('resort_id', $this->resortId)->where('employee_id', $employee->id)
            ->where(fn ($q) => $q->where('end_date', $end)->when(($v['visa_number'] ?? '') !== '', fn ($q2) => $q2->orWhere('Visa_Number', $v['visa_number'])))
            ->orderByDesc('id')->value('id');
        if ($existing) {
            return $this->updateRow('details', 'visa_renewals', $existing, $visa, $label);
        }
        $this->insertRow('details', 'visa_renewals', $visa + ['resort_id' => $this->resortId, 'employee_id' => $employee->id,
            'Amt' => 0, 'Status' => 'Paid', 'created_at' => now(), 'updated_at' => now()], $label);
        return true;
    }

    private function importHolidays(string $file, array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $this->ledger->at($file, $rec['row']);
            $date = self::parseDate($v['date']);
            if (!$date) {
                $this->error($file, $rec['row'], "Holiday date '{$v['date']}' is not a valid dd/mm/yyyy date.");
                continue;
            }
            $label = "Holiday {$date} {$v['name']}";
            $existing = DB::table('resortholidays')->where('resort_id', $this->resortId)->whereDate('PublicHolidaydate', $date)->value('id');
            if ($existing) {
                $this->count('holidays', $this->updateRow('holidays', 'resortholidays', $existing, ['PublicHolidayName' => $v['name']], $label) ? 'updated' : 'unchanged');
                continue;
            }
            $this->insertRow('holidays', 'resortholidays', $this->newRow(['PublicHolidaydate' => $date, 'PublicHolidayName' => $v['name']]), $label);
            $this->count('holidays', 'created');
        }
    }

    private function findEmployee(string $clientId): ?Employee
    {
        return Employee::where('resort_id', $this->resortId)->where('Emp_id', $this->empId($clientId))->first()
            ?? Employee::where('resort_id', $this->resortId)->where('Emp_id', $clientId)->first();
    }

    /** '' → null, a known employment type → its employees.employment_type value, else false. */
    private static function employmentType(string $value)
    {
        $k = strtolower(trim($value));
        return match (true) {
            $k === '' => null,
            in_array($k, ['full-time', 'full time', 'fulltime', 'permanent', 'regular'], true) => 'Full-Time',
            in_array($k, ['part-time', 'part time', 'parttime'], true) => 'Part-Time',
            $k === 'contract' => 'Contract',
            in_array($k, ['probation', 'probationary'], true) => 'Probationary',
            in_array($k, ['temporary', 'temp'], true) => 'Temporary',
            $k === 'casual' => 'Casual',
            in_array($k, ['intern', 'internship', 'trainee'], true) => 'Internship',
            default => false,
        };
    }

    private static function currency(string $value): ?string
    {
        $k = strtoupper(trim($value));
        return match (true) {
            in_array($k, ['USD', 'US$', '$', 'US DOLLAR', 'DOLLAR'], true) => 'USD',
            in_array($k, ['MVR', 'RF', 'MRF', 'RUFIYAA'], true) => 'MVR',
            default => null,
        };
    }

    private function placeholderEmail(string $empId): ?string
    {
        $local = strtolower($empId);
        if (!preg_match('/^[a-z0-9][a-z0-9._-]*$/', $local)) {
            return null;
        }
        return filter_var($local . '@' . $this->opt['email_domain'], FILTER_VALIDATE_EMAIL) ?: null;
    }

    private function importAttendance(string $file, array $parsed): void
    {
        if (!$this->checkPrefix($file)) {
            return;
        }
        $start = self::parseDate($this->opt['period_start']);
        if (!$start) {
            $this->error($file, null, 'Set the attendance period start date under Options.');
            return;
        }
        $shift = ShiftSettings::where('resort_id', $this->resortId)->where('id', $this->opt['shift_id'])->first();
        if (!$shift) {
            $this->error($file, null, 'Choose the shift to attach to migrated attendance under Options.');
            return;
        }

        // Column → date; the sheet's own day numbers must agree with the period start.
        $dates = [];
        $cursor = Carbon::parse($start);
        foreach ($parsed['days'] as $col => $day) {
            if ($cursor->day !== $day) {
                $this->error($file, null, "Period start {$start} does not line up with the sheet's day columns (expected day {$day}, got {$cursor->day}).");
                return;
            }
            $dates[$col] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }
        $end = end($dates);

        $categories = LeaveCategory::where('resort_id', $this->resortId)->pluck('id')->all();
        $actions = [];
        foreach ($this->opt['codes'] as $code => $action) {
            $actions[$code === self::BLANK ? '' : $code] = $action;
        }
        $unmapped = [];
        foreach ($parsed['records'] as $rec) {
            foreach ($rec['codes'] as $code) {
                $action = $actions[$code] ?? '';
                $categoryId = str_starts_with($action, 'leave:') ? (int) substr($action, 6) : null;
                if ($action === '' || ($categoryId !== null && !in_array($categoryId, $categories))) {
                    $unmapped[$code === '' ? self::BLANK : $code] = true;
                }
            }
        }
        foreach (array_keys($unmapped) as $code) {
            $this->error($file, null, "Attendance code '{$code}' has no action — set it under Options.");
        }
        if ($unmapped) {
            return;
        }

        $in = substr($shift->StartTime ?: '00:00', 0, 5);
        $out = Carbon::createFromFormat('H:i', $in)->addHours(self::PRESENT_HOURS)->format('H:i');
        $hours = sprintf('%02d:00', self::PRESENT_HOURS);
        $shiftDate = Carbon::parse($start)->format('m/d/Y') . ' - ' . Carbon::parse($end)->format('m/d/Y');
        $employees = DB::table('employees')->where('resort_id', $this->resortId)->whereNull('deleted_at')
            ->get(['id', 'Emp_id', 'joining_date'])->keyBy('Emp_id');
        $missing = [];

        foreach ($parsed['records'] as $rec) {
            $employee = $employees[$this->empId($rec['v']['emp_id'])] ?? $employees[$rec['v']['emp_id']] ?? null;
            if (!$employee) {
                $missing[] = $rec['v']['emp_id'];
                continue;
            }
            $empId = $employee->id;

            $label = "{$employee->Emp_id} attendance";
            $this->ledger->at($file, $rec['row']);
            $roster = ['resort_id' => $this->resortId, 'Emp_id' => $empId, 'ShiftDate' => $shiftDate];
            $rosterId = DB::table('duty_rosters')->where($roster)->value('id');
            if ($rosterId) {
                $this->updateRow('attendance', 'duty_rosters', $rosterId, ['Shift_id' => $shift->id], $label);
            } else {
                $rosterId = $this->insertRow('attendance', 'duty_rosters', $roster + [
                    'Shift_id' => $shift->id, 'Year' => Carbon::parse($start)->format('Y'),
                    'created_by' => $this->createdBy, 'created_at' => now(), 'updated_at' => now(),
                ], $label);
            }

            $rows = [];
            $leaveDays = [];
            foreach ($dates as $col => $date) {
                $action = $actions[$rec['codes'][$col]];
                // Before the hire date the sheet is blank, and the client's own P
                // total does not count those days: not employed yet, not present.
                if ($action === 'skip' || ($employee->joining_date && $date < $employee->joining_date)) {
                    continue;
                }
                $present = $action === 'present';
                $rows[$date] = [
                    'roster_id' => $rosterId, 'resort_id' => $this->resortId, 'Shift_id' => $shift->id, 'Emp_id' => $empId, 'date' => $date,
                    'Status' => $present ? 'Present' : ($action === 'dayoff' ? 'DayOff' : 'FullDayLeave'),
                    'CheckingTime' => $present ? $in : null, 'CheckingOutTime' => $present ? $out : null,
                    'DayWiseTotalHours' => $present ? $hours : '00:00', 'CheckInCheckOut_Type' => 'Manual',
                ];
                if (str_starts_with($action, 'leave:')) {
                    $leaveDays[(int) substr($action, 6)][] = $date;
                }
                $this->count('attendance', $rows[$date]['Status']);
            }

            // Days already punched in the app (geofence / biometric) are real data — never overwrite them.
            $protected = DB::table('parent_attendaces')->where('Emp_id', $empId)->whereBetween('date', [$start, $end])
                ->whereIn('CheckInCheckOut_Type', ['Geofencing', 'Biometric'])->pluck('date')->all();
            foreach ($protected as $date) {
                unset($rows[$date]);
                $this->warn('Day already recorded by app punch-in — kept, not overwritten.');
            }

            $this->upsertDays('duty_roster_entries', $empId, $rows, $label);
            $this->upsertDays('parent_attendaces', $empId, $rows, $label);

            // Punch pair for present days only, matching the manual mark-present path.
            $parents = DB::table('parent_attendaces')->where('Emp_id', $empId)->whereIn('date', array_keys($rows))->pluck('id', 'date');
            $children = DB::table('child_attendaces')->whereIn('Parent_attd_id', $parents->values())->get()->keyBy('Parent_attd_id');
            $newChildren = [];
            foreach ($rows as $date => $r) {
                $child = $children[$parents[$date]] ?? null;
                if ($r['Status'] === 'Present') {
                    if (!$child) {
                        $newChildren[] = ['Parent_attd_id' => $parents[$date], 'InTime_out' => $in, 'OutTime_out' => $out, 'created_at' => now(), 'updated_at' => now()];
                    } else {
                        $this->updateRow('attendance', 'child_attendaces', $child->id, ['InTime_out' => $in, 'OutTime_out' => $out], $label);
                    }
                } elseif ($child) {
                    DB::table('child_attendaces')->where('id', $child->id)->delete();
                    $this->ledger->deleted('attendance', 'child_attendaces', (array) $child, $label);
                }
            }
            if ($newChildren) {
                DB::table('child_attendaces')->insert($newChildren);
                $this->ledger->createdWhere('attendance', 'child_attendaces', ['Parent_attd_id' => array_column($newChildren, 'Parent_attd_id')], $label);
            }

            // Leave days need an approved employees_leaves row too: payroll and the
            // register take the leave type and paid/unpaid from it, not from the
            // attendance row. Earlier migrated rows for this period that no longer
            // match the sheet are replaced; matching ones are left alone.
            $wanted = [];
            foreach ($leaveDays as $categoryId => $days) {
                foreach (self::runs($days) as [$from, $to, $total]) {
                    $wanted["{$categoryId}|{$from}|{$to}"] = [$categoryId, $from, $to, $total];
                }
            }
            $existingLeaves = DB::table('employees_leaves')->where('resort_id', $this->resortId)->where('emp_id', $empId)
                ->where('reason', self::LEAVE_REASON)->whereBetween('from_date', [$start, $end])->get();
            foreach ($existingLeaves as $leave) {
                $key = "{$leave->leave_category_id}|{$leave->from_date}|{$leave->to_date}";
                if (isset($wanted[$key])) {
                    unset($wanted[$key]);
                    continue;
                }
                DB::table('employees_leaves')->where('id', $leave->id)->delete();
                $this->ledger->deleted('attendance', 'employees_leaves', (array) $leave, $label);
            }
            foreach ($wanted as [$categoryId, $from, $to, $total]) {
                $this->insertRow('attendance', 'employees_leaves', [
                    'resort_id' => $this->resortId, 'emp_id' => $empId, 'leave_category_id' => $categoryId,
                    'from_date' => $from, 'to_date' => $to, 'total_days' => $total, 'duration' => '',
                    'reason' => self::LEAVE_REASON, 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now(),
                ], $label);
                $this->count('attendance', 'leave records');
            }
            $this->count('attendance', 'employees');
        }

        if ($missing) {
            $this->warn(count($missing) . ' attendance row(s) skipped — Employee ID not found in this resort: ' . implode(', ', array_slice($missing, 0, 20)));
        }
    }

    /** One row per date for this employee: insert the missing ones, change only days that differ. */
    private function upsertDays(string $table, int $empId, array $rows, string $label): void
    {
        $existing = DB::table($table)->where('Emp_id', $empId)->whereIn('date', array_keys($rows))->pluck('id', 'date');
        $inserts = [];
        foreach ($rows as $date => $row) {
            if (isset($existing[$date])) {
                if ($this->updateRow('attendance', $table, $existing[$date], $row, $label) && $table === 'parent_attendaces') {
                    $this->count('attendance', 'days changed');
                }
            } else {
                $inserts[] = $row + ['created_by' => $this->createdBy, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }
        if ($inserts) {
            $this->ledger->createdWhere('attendance', $table, ['Emp_id' => $empId, 'date' => array_column($inserts, 'date')], $label);
        }
    }

    /** Sorted Y-m-d dates → [[from, to, days], ...] for each unbroken run. */
    private static function runs(array $dates): array
    {
        sort($dates);
        $runs = [];
        foreach ($dates as $date) {
            $last = count($runs) - 1;
            if ($last >= 0 && Carbon::parse($runs[$last][1])->addDay()->format('Y-m-d') === $date) {
                $runs[$last][1] = $date;
                $runs[$last][2]++;
            } else {
                $runs[] = [$date, $date, 1];
            }
        }
        return $runs;
    }

    /** '' → null, valid dd/mm/yyyy (time and anything after ignored), yyyy-mm-dd or Excel serial → Y-m-d, else false. */
    public static function parseDate(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        $token = strtok($value, ' ');
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $token, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $token, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $token;
        }
        return false;
    }

    private function nationality(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        static $map = null;
        if ($map === null) {
            // Same country → demonym map the nationality normalisation migration uses.
            require_once database_path('migrations/2026_04_22_010000_normalize_employee_nationalities.php');
            $map = \NormalizeEmployeeNationalities::countryToDemonymMap();
        }
        $name = ucwords(strtolower($value));
        if (in_array($name, config('settings.nationalities') ?? [], true)) {
            return $name;
        }
        if (isset($map[$name])) {
            return $map[$name];
        }
        $this->warn("Nationality '{$value}' not recognised — left empty.");
        return null;
    }

    private static function religion(string $value): string
    {
        // employees.religion enum('0','1'): 0 = non-muslim, 1 = muslim.
        return strtolower(trim($value)) === 'muslim' ? '1' : '0';
    }

    private static function status(string $value): string
    {
        return in_array(strtolower($value), ['inactive', 'n', 'no', '0', 'disabled'], true) ? 'inactive' : 'active';
    }

    private static function key(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function insertRow(string $kind, string $table, array $data, string $label): int
    {
        $id = DB::table($table)->insertGetId($data);
        $this->ledger->created($kind, $table, $id, $label);
        return $id;
    }

    /** Writes only the fields that differ; true when something changed. */
    private function updateRow(string $kind, string $table, int $id, array $data, string $label): bool
    {
        if (!$data) {
            return false;
        }
        $current = (array) DB::table($table)->where('id', $id)->first(array_keys($data));
        $changes = [];
        foreach ($data as $field => $value) {
            if (!ImportLedger::same($current[$field] ?? null, $value)) {
                $changes[$field] = [$current[$field] ?? null, $value];
            }
        }
        if (!$changes) {
            return false;
        }
        DB::table($table)->where('id', $id)->update(array_map(fn ($c) => $c[1], $changes) + ['updated_at' => now()]);
        $this->ledger->updated($kind, $table, $id, $changes, $label);
        return true;
    }

    /** Same, through the model, so Employee's audit observer still logs position/department changes. */
    private function updateModel(string $kind, $model, array $data, string $label): bool
    {
        $changes = [];
        foreach ($data as $field => $value) {
            if (!ImportLedger::same($model->getRawOriginal($field), $value)) {
                $changes[$field] = [$model->getRawOriginal($field), $value];
            }
        }
        if (!$changes) {
            return false;
        }
        $model->update(array_map(fn ($c) => $c[1], $changes));
        $this->ledger->updated($kind, $model->getTable(), $model->id, $changes, $label);
        return true;
    }

    private function positionTitle(int $positionId): string
    {
        $p = DB::table('resort_positions as p')->join('resort_departments as d', 'd.id', '=', 'p.dept_id')->where('p.id', $positionId)->first(['p.position_title', 'd.name']);
        return $p ? self::positionLabel($p->position_title, $p->name) : "Position #{$positionId}";
    }

    private function newRow(array $data): array
    {
        return $data + ['resort_id' => $this->resortId, 'created_by' => $this->createdBy, 'created_at' => now(), 'updated_at' => now()];
    }

    private function count(string $type, string $what): void
    {
        $this->counts[$type][$what] = ($this->counts[$type][$what] ?? 0) + 1;
    }

    private function error(?string $file, ?int $row, string $message): void
    {
        $this->errors[] = ['file' => $file, 'row' => $row, 'message' => $message];
    }

    private function warn(string $message): void
    {
        $this->warnings[$message] = ($this->warnings[$message] ?? 0) + 1;
    }
}
