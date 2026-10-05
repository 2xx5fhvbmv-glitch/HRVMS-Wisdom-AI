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
 * order: divisions → departments → sections → positions → staff → attendance.
 *
 * One code path for both modes: a dry run performs every write inside a
 * transaction and rolls it back, so "Validate" reports exactly what
 * "Import" will do. Import commits only with zero errors — all or nothing.
 *
 * Re-runs are safe: rows are matched (case-insensitive name within the
 * resort, Emp_id for staff, employee + date for attendance) and updated
 * instead of duplicated.
 *
 * Master-data tables are written with DB::table on purpose: the
 * ResortDivision/Department/Section/Position/DutyRoster saving hooks read
 * Auth::guard('resort-admin')->user()->id unguarded, which is null in the
 * super-admin console.
 */
class ResortDataImporter
{
    const ORDER = ['divisions', 'departments', 'sections', 'positions', 'staff', 'attendance'];
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
        @set_time_limit(900);
        $this->commit = $commit;
        $this->resortId = (int) $import->resort_id;
        $this->opt = self::options($import);
        $this->createdBy = ResortAdmin::where('resort_id', $this->resortId)->where('is_master_admin', 1)->orderBy('id')->value('id');

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

        DB::beginTransaction();
        try {
            $this->loadLookups();
            foreach (self::ORDER as $type) {
                foreach ($byType[$type] ?? [] as $file) {
                    match ($type) {
                        'divisions'   => $this->importDivisions($file['parsed']),
                        'departments' => $this->importDepartments($file['name'], $file['parsed']),
                        'sections'    => $this->importSections($file['name'], $file['parsed']),
                        'positions'   => $this->importPositions($file['name'], $file['parsed']),
                        'staff'       => $this->importStaff($file['name'], $file['parsed']),
                        'attendance'  => $this->importAttendance($file['name'], $file['parsed']),
                    };
                }
            }
        } catch (\Throwable $e) {
            Log::error('Resort data setup failed', ['resort_id' => $this->resortId, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            $this->error(null, null, 'Unexpected error: ' . $e->getMessage());
        }

        $committed = $commit && !$this->errors;
        $committed ? DB::commit() : DB::rollBack();

        return [
            'mode'        => $commit ? 'import' : 'dry-run',
            'committed'   => $committed,
            'at'          => now()->toDateTimeString(),
            'counts'      => $this->counts,
            'errors'      => $this->errors,
            'warnings'    => $this->warnings,
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
        foreach ($q('resort_positions')->get(['id', 'dept_id', 'position_title']) as $p) {
            $this->positions[$p->dept_id . '|' . self::key($p->position_title)] = $p->id;
        }
    }

    private function importDivisions(array $parsed): void
    {
        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['short_name' => $short, 'status' => self::status($v['status'] ?? ''), 'updated_at' => now()];
            if ($id = $this->divisions[self::key($v['name'])] ?? null) {
                DB::table('resort_divisions')->where('id', $id)->update($data);
                $this->count('divisions', 'updated');
                continue;
            }
            $this->divisions[self::key($v['name'])] = DB::table('resort_divisions')->insertGetId($data + $this->newRow([
                'name' => $v['name'], 'code' => $short, 'slug' => Str::slug($v['name']),
            ]));
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
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['division_id' => $divisionId, 'short_name' => $short, 'status' => self::status($v['status'] ?? ''), 'updated_at' => now()];
            if ($existing = $this->departments[self::key($v['name'])] ?? null) {
                DB::table('resort_departments')->where('id', $existing['id'])->update($data);
                $this->departments[self::key($v['name'])]['division_id'] = $divisionId;
                $this->count('departments', 'updated');
                continue;
            }
            $id = DB::table('resort_departments')->insertGetId($data + $this->newRow([
                'name' => $v['name'], 'code' => $short, 'slug' => Str::slug($v['name']),
            ]));
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
            $short = ($v['short_name'] ?? '') ?: $v['name'];
            $data = ['short_name' => $short, 'status' => self::status($v['status'] ?? ''), 'updated_at' => now()];
            $key = $dept['id'] . '|' . self::key($v['name']);
            if ($id = $this->sections[$key] ?? null) {
                DB::table('resort_sections')->where('id', $id)->update($data);
                $this->count('sections', 'updated');
                continue;
            }
            $this->sections[$key] = DB::table('resort_sections')->insertGetId($data + $this->newRow([
                'dept_id' => $dept['id'], 'name' => $v['name'], 'code' => $short,
            ]));
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
                DB::table('resort_positions')->where('id', $id)->update(array_filter(['section_id' => $sectionId, 'Rank' => $rank]) + ['updated_at' => now()]);
                if ($rank) {
                    $this->levelledPositions[$id] = true;
                }
                $this->count('positions', 'updated');
                continue;
            }
            $rank = $this->positionLevelRank($file, $rec['row'], $v);
            if ($rank === false) {
                continue;
            }
            $id = $this->createPosition($dept['id'], $v['title'], $sectionId);
            if ($rank) {
                DB::table('resort_positions')->where('id', $id)->update(['Rank' => $rank]);
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

    private function createPosition(int $deptId, string $title, ?int $sectionId): int
    {
        // Rank 6 is provisional; importStaff() sets it from the people who hold the position.
        $id = DB::table('resort_positions')->insertGetId($this->newRow([
            'dept_id' => $deptId, 'section_id' => $sectionId, 'position_title' => $title, 'Rank' => 6,
            'status' => 'active', 'is_reserved' => 'No', 'slug' => Str::slug($title),
        ]));
        $this->positions[$deptId . '|' . self::key($title)] = $id;
        return $id;
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
        $positionRanks = [];
        $titleRanks = [];
        $legacy = 0;
        $roleDepts = array_filter(array_map([self::class, 'key'], $this->opt['roles']));

        foreach ($parsed['records'] as $rec) {
            $v = $rec['v'];
            $row = $rec['row'];
            $empId = $this->empId($v['emp_id']);

            if (isset($seen[$empId])) {
                $this->error($file, $row, "Employee ID {$empId} appears twice (rows {$seen[$empId]} and {$row}).");
                continue;
            }
            $seen[$empId] = $row;

            $rank = (int) ($this->opt['level_ranks'][$v['level']] ?? 0);
            if (!$rank) {
                $this->error($file, $row, "Level '{$v['level']}' has no rank — set it under Options.");
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

            $rank = self::gmRank($rank, $v['position']);
            $mainRank = $rank;
            if (in_array($rank, self::ROLE_LEVEL_RANKS, true)) {
                $role = array_search(self::key($dept['name']), $roleDepts, true);
                if ($role !== false) {
                    $mainRank = self::ROLE_MAIN_RANK[$role];
                }
            }

            $sectionId = $this->sectionId($dept['id'], $v['section'] ?? '');
            $positionId = $this->positions[$dept['id'] . '|' . self::key($v['position'])] ?? null;
            if (!$positionId) {
                $positionId = $this->createPosition($dept['id'], $v['position'], $sectionId);
                $this->warn("Position '{$v['position']}' ({$dept['name']}) was not in the positions file — created from the staff list.");
            }
            $positionRanks[$positionId][] = $rank;
            $titleRanks[self::key($v['position'])][] = $rank;

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
            }

            $profile = Common::persistEmployeeProfile($adminData, $employeeData, $this->resortId, $existingAdmin);
            if ($profile['employeeCreated']) {
                $this->count('staff', 'created');
                $this->credentials[] = ['emp_id' => $empId, 'name' => $v['name'], 'email' => $adminData['email'], 'password' => $password];
            } else {
                $this->count('staff', 'updated');
            }
        }

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
        foreach ($positionRanks as $positionId => $ranks) {
            if (!isset($this->levelledPositions[$positionId])) {
                DB::table('resort_positions')->where('id', $positionId)->update(['Rank' => $mode($ranks), 'updated_at' => now()]);
            }
            unset($this->newPositions[$positionId]);
        }
        foreach ($this->newPositions as $positionId => $title) {
            if ($ranks = $titleRanks[self::key($title)] ?? null) {
                DB::table('resort_positions')->where('id', $positionId)->update(['Rank' => $mode($ranks), 'updated_at' => now()]);
                unset($this->newPositions[$positionId]);
                $this->warn('Vacant position took its rank from the same title in another department.');
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

            $roster = ['resort_id' => $this->resortId, 'Emp_id' => $empId, 'ShiftDate' => $shiftDate];
            $rosterId = DB::table('duty_rosters')->where($roster)->value('id');
            if ($rosterId) {
                DB::table('duty_rosters')->where('id', $rosterId)->update(['Shift_id' => $shift->id, 'updated_at' => now()]);
            } else {
                $rosterId = DB::table('duty_rosters')->insertGetId($roster + [
                    'Shift_id' => $shift->id, 'Year' => Carbon::parse($start)->format('Y'),
                    'created_by' => $this->createdBy, 'created_at' => now(), 'updated_at' => now(),
                ]);
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

            $this->upsertDays('duty_roster_entries', $empId, $rows);
            $this->upsertDays('parent_attendaces', $empId, $rows);

            // Punch pair for present days only, matching the manual mark-present path.
            $parents = DB::table('parent_attendaces')->where('Emp_id', $empId)->whereIn('date', array_keys($rows))->pluck('id', 'date');
            $presentIds = [];
            foreach ($rows as $date => $r) {
                if ($r['Status'] === 'Present') {
                    $presentIds[] = $parents[$date];
                }
            }
            DB::table('child_attendaces')->whereIn('Parent_attd_id', $parents->values())->delete();
            DB::table('child_attendaces')->insert(array_map(fn ($id) => [
                'Parent_attd_id' => $id, 'InTime_out' => $in, 'OutTime_out' => $out, 'created_at' => now(), 'updated_at' => now(),
            ], $presentIds));

            // Leave days need an approved employees_leaves row too: payroll and the
            // register take the leave type and paid/unpaid from it, not from the
            // attendance row. Earlier migrated rows for this period are replaced.
            DB::table('employees_leaves')->where('resort_id', $this->resortId)->where('emp_id', $empId)
                ->where('reason', self::LEAVE_REASON)->whereBetween('from_date', [$start, $end])->delete();
            foreach ($leaveDays as $categoryId => $days) {
                foreach (self::runs($days) as [$from, $to, $total]) {
                    DB::table('employees_leaves')->insert([
                        'resort_id' => $this->resortId, 'emp_id' => $empId, 'leave_category_id' => $categoryId,
                        'from_date' => $from, 'to_date' => $to, 'total_days' => $total, 'duration' => '',
                        'reason' => self::LEAVE_REASON, 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $this->count('attendance', 'leave records');
                }
            }
            $this->count('attendance', 'employees');
        }

        if ($missing) {
            $this->warn(count($missing) . ' attendance row(s) skipped — Employee ID not found in this resort: ' . implode(', ', array_slice($missing, 0, 20)));
        }
    }

    /** Insert or update one row per date for this employee. */
    private function upsertDays(string $table, int $empId, array $rows): void
    {
        $existing = DB::table($table)->where('Emp_id', $empId)->whereIn('date', array_keys($rows))->pluck('id', 'date');
        $inserts = [];
        foreach ($rows as $date => $row) {
            if (isset($existing[$date])) {
                DB::table($table)->where('id', $existing[$date])->update($row + ['updated_at' => now()]);
            } else {
                $inserts[] = $row + ['created_by' => $this->createdBy, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table($table)->insert($chunk);
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
