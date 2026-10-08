<?php

namespace Database\Seeders\Demo;

use App\Helpers\Common;
use App\Services\ResortDataSetup\ResortDataImporter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Demo ENV slice 2: resort configuration + organisation + 100 employees.
 *
 * Divisions → positions → staff → salary/bank/passport/visa/reporting line →
 * holidays all go through ResortDataImporter::importRecords(), i.e. the exact
 * writes a real client import makes. Configuration (site settings, leave types,
 * shifts, benefit grids, visa settings…) comes from data/config.php.
 */
class DemoOrganisationSeeder
{
    const LEVELS = ['EXCOM' => 1, 'HOD' => 2, 'MGR' => 4, 'SUP' => 5, 'LINE' => 6];

    private int $resortId;
    private int $adminId;
    private array $data;
    private array $people = []; // client id => person built below

    public function __construct(int $resortId, int $adminId)
    {
        $this->resortId = $resortId;
        $this->adminId = $adminId;
        $this->data = require __DIR__ . '/data/people.php';
    }

    public function seed(): array
    {
        $counts = $this->configuration();
        $this->buildPeople();

        $result = (new ResortDataImporter)->importRecords($this->resortId, $this->records(), [
            'email_domain' => config('demo.login_domain'),
            'level_ranks' => self::LEVELS,
            'roles' => ['hr' => 'Human Resources', 'finance' => 'Finance', 'clinic' => ''],
        ]);
        if (!$result['committed']) {
            $first = $result['errors'][0] ?? ['message' => 'unknown'];
            throw new RuntimeException('Demo organisation import failed: ' . $first['message'] . ' (' . count($result['errors']) . ' errors)');
        }
        $this->finishPeople();

        return $counts + [
            'divisions' => DB::table('resort_divisions')->where('resort_id', $this->resortId)->count(),
            'departments' => DB::table('resort_departments')->where('resort_id', $this->resortId)->count(),
            'positions' => DB::table('resort_positions')->where('resort_id', $this->resortId)->count(),
            'employees' => DB::table('employees')->where('resort_id', $this->resortId)->count(),
        ];
    }

    /** data/config.php → this resort, remapping the ids that point at other config rows. */
    private function configuration(): array
    {
        $config = require __DIR__ . '/data/config.php';
        $leaveIds = $gradeIds = [];
        $counts = [];
        foreach ($config as $table => $rows) {
            foreach ($rows as $row) {
                $ref = $row['_ref'] ?? null;
                unset($row['_ref']);
                if ($table === 'resort_benefit_grade_level_ranks') {
                    $row['grade_level_id'] = $gradeIds[$row['grade_level_id']] ?? null;
                    if (!$row['grade_level_id']) {
                        continue;
                    }
                }
                if ($table === 'resort_benifit_grid') {
                    $row['emp_grade'] = (string) $gradeIds[(int) $row['emp_grade']];
                }
                $id = DB::table($table)->insertGetId($row + $this->stamp($table));
                if ($table === 'leave_categories') {
                    $leaveIds[$ref] = $id;
                } elseif ($table === 'resort_benefit_grade_levels') {
                    $gradeIds[$ref] = $id;
                }
            }
            $counts[$table] = count($rows);
        }
        // "Can be combined with" lists other leave categories by id.
        foreach ($config['leave_categories'] as $row) {
            if ($row['leave_category'] !== '' && $row['leave_category'] !== null) {
                $mapped = array_filter(array_map(fn ($id) => $leaveIds[(int) $id] ?? null, explode(',', $row['leave_category'])));
                DB::table('leave_categories')->where('id', $leaveIds[$row['_ref']])->update(['leave_category' => implode(',', $mapped)]);
            }
        }
        return ['leave categories' => $counts['leave_categories'], 'shifts' => $counts['shift_settings']];
    }

    private function stamp(string $table): array
    {
        static $columns = [];
        $columns[$table] ??= Schema::getColumnListing($table);
        $stamp = ['created_at' => now(), 'updated_at' => now()];
        $stamp[in_array('resort_id', $columns[$table], true) ? 'resort_id' : 'Resort_id'] = $this->resortId;
        foreach (['created_by', 'modified_by'] as $c) {
            if (in_array($c, $columns[$table], true)) {
                $stamp[$c] = $this->adminId;
            }
        }
        return $stamp;
    }

    /** Who works here: name, nationality, religion, dates, pay — all from the seeded RNG. */
    private function buildPeople(): void
    {
        $maldivianShare = mt_rand(40, 60) / 100;
        $n = 0;
        $recent = 0;
        foreach ($this->data['positions'] as [$dept, $title, $level, $section, $headcount, $login, $fixedNat]) {
            for ($i = 0; $i < $headcount; $i++) {
                $n++;
                $nat = $fixedNat ?? ($this->chance($maldivianShare) ? 'Maldivian' : $this->weighted($this->data['expat_weights']));
                [$muslimShare, $male, $female, $last, $passport] = $this->data['names'][$nat];
                $gender = $this->chance(in_array($dept, ['Engineering', 'Kitchen'], true) ? 0.1 : 0.35) ? 'female' : 'male';
                $senior = in_array($level, ['EXCOM', 'HOD'], true);
                // A handful of recent line hires are still on probation.
                $hired = !$senior && $recent < 6 && $this->chance(0.12)
                    ? Carbon::today()->subDays(mt_rand(10, 75)) : Carbon::today()->subDays(mt_rand(150, 6 * 365));
                $recent += $hired->gt(Carbon::today()->subDays(80)) ? 1 : 0;
                $payLevel = $title === 'General Manager' ? 'GM' : $level;
                $usd = round(mt_rand(...$this->data['salary'][$payLevel]) / 10) * 10;

                $this->people[sprintf('%04d', $n)] = [
                    'dept' => $dept, 'title' => $title, 'level' => $level, 'section' => $section, 'login' => $i === 0 ? $login : null,
                    'nationality' => $nat, 'gender' => $gender, 'muslim' => $this->chance($muslimShare),
                    'name' => $this->pick($gender === 'male' ? $male : $female) . ' ' . $this->pick($last),
                    'hired' => $hired, 'dob' => Carbon::today()->subYears(mt_rand($senior ? 35 : 21, $senior ? 56 : 45))->subDays(mt_rand(0, 364)),
                    'salary' => $nat === 'Maldivian' ? round($usd * 15.42 / 50) * 50 : $usd, 'currency' => $nat === 'Maldivian' ? 'MVR' : 'USD',
                    'passport' => $passport . mt_rand(1000000, 9999999),
                ];
            }
        }
        // One birthday today, so the dashboard's birthday card has someone (spec §5).
        $birthday = array_rand($this->people);
        $this->people[$birthday]['dob'] = Carbon::today()->subYears(mt_rand(24, 40));
    }

    /** The people and structure in the importer's parsed-file shape. */
    private function records(): array
    {
        $file = fn (string $name, array $rows) => [['name' => "Demo ENV {$name}", 'parsed' => ['error' => null,
            'records' => array_map(fn ($v, $i) => ['row' => $i + 2, 'v' => $v, 'group' => null], $rows, array_keys($rows))]]];

        $divisions = $departments = $sections = $positions = $staff = $details = [];
        foreach ($this->data['divisions'] as $division => $depts) {
            $divisions[] = ['name' => $division];
            foreach ($depts as $dept) {
                $departments[] = ['name' => $dept, 'division' => $division];
            }
        }
        foreach ($this->data['sections'] as $dept => $names) {
            foreach ($names as $name) {
                $sections[] = ['name' => $name, 'department' => $dept];
            }
        }
        foreach ($this->data['positions'] as [$dept, $title, $level, $section]) {
            $positions[] = ['department' => $dept, 'title' => $title, 'level' => $level, 'section' => $section];
        }

        $managers = $this->reportingLines();
        $today = Carbon::today();
        foreach ($this->people as $id => $p) {
            $staff[] = [
                'emp_id' => $id, 'name' => $p['name'], 'department' => $p['dept'], 'section' => $p['section'], 'position' => $p['title'],
                'level' => $p['level'], 'gender' => $p['gender'], 'nationality' => $p['nationality'], 'religion' => $p['muslim'] ? 'Muslim' : '',
                'hire_date' => $p['hired']->format('d/m/Y'), 'employment_type' => 'Full-Time',
                'email' => $p['login'] ? $p['login'] . '@' . config('demo.login_domain') : '',
            ];
            $expat = $p['nationality'] !== 'Maldivian';
            // Expat visas: mostly valid, some due soon, a few already lapsed — so the Visa screens have work.
            $visaDays = $this->chance(0.12) ? mt_rand(1, 30) : ($this->chance(0.05) ? -mt_rand(3, 25) : mt_rand(45, 400));
            $details[] = [
                'emp_id' => $id, 'dob' => $p['dob']->format('d/m/Y'), 'basic_salary' => (string) $p['salary'], 'salary_currency' => $p['currency'],
                'payment_mode' => 'Bank', 'reporting_manager_id' => $managers[$id] ?? '', 'phone' => ($expat ? '+960 9' : '+960 7') . mt_rand(100000, 999999),
                'passport_number' => $p['passport'], 'visa_expiry' => $expat ? $today->copy()->addDays($visaDays)->format('d/m/Y') : '',
                'work_permit_expiry' => $expat ? $today->copy()->addDays($visaDays)->format('d/m/Y') : '',
                'visa_number' => $expat ? 'WV' . mt_rand(1000000, 9999999) : '', 'work_permit_number' => $expat ? 'WP' . mt_rand(100000, 999999) : '',
                'bank_name' => $this->chance(0.7) ? 'Bank of Maldives' : 'Maldives Islamic Bank', 'bank_currency' => $p['currency'],
                'account_no' => '77' . mt_rand(10000000, 99999999) . mt_rand(100, 999),
            ];
        }

        $holidays = [];
        foreach ([$today->year - 1, $today->year, $today->year + 1] as $year) {
            foreach ($this->data['holidays_fixed'] as $md => $name) {
                $holidays[] = ['date' => Carbon::parse("{$year}-{$md}")->format('d/m/Y'), 'name' => $name];
            }
            foreach ($this->data['holidays_islamic'][$year] ?? [] as $date => $name) {
                $holidays[] = ['date' => Carbon::parse($date)->format('d/m/Y'), 'name' => $name];
            }
        }

        return [
            'divisions' => $file('divisions', $divisions), 'departments' => $file('departments', $departments),
            'sections' => $file('sections', $sections), 'positions' => $file('positions', $positions),
            'staff' => $file('staff', $staff), 'employee_details' => $file('employee details', $details),
            'holidays' => $file('holidays', $holidays),
        ];
    }

    /**
     * Line → a supervisor of the same department (else manager/HOD), SUP → MGR/HOD,
     * MGR → HOD, HOD → Resident Manager, EXCOM → GM. Mirrors the leave approval chain.
     */
    private function reportingLines(): array
    {
        $byDeptLevel = [];
        foreach ($this->people as $id => $p) {
            $byDeptLevel[$p['dept']][$p['level']][] = $id;
        }
        $find = fn ($title) => array_search($title, array_column($this->people, 'title'), true);
        $ids = array_keys($this->people);
        $gm = $ids[$find('General Manager')];
        $rm = $ids[$find('Resident Manager')];

        $lines = [];
        foreach ($this->people as $id => $p) {
            $up = match ($p['level']) {
                'LINE' => ['SUP', 'MGR', 'HOD', 'EXCOM'],
                'SUP' => ['MGR', 'HOD', 'EXCOM'],
                'MGR' => ['HOD', 'EXCOM'],
                default => [],
            };
            $manager = null;
            foreach ($up as $level) {
                if ($candidates = $byDeptLevel[$p['dept']][$level] ?? null) {
                    $manager = $candidates[crc32($id) % count($candidates)];
                    break;
                }
            }
            $manager ??= match (true) {
                $id === $gm => null,
                $p['level'] === 'HOD' => $rm,
                default => $gm,
            };
            if ($manager && $manager !== $id) {
                $lines[$id] = $manager;
            }
        }
        return $lines;
    }

    /** What the staff/details files can't carry: profile fields, probation, one password, the demo logins' full menu. */
    private function finishPeople(): void
    {
        $employees = DB::table('employees')->where('resort_id', $this->resortId)->get(['id', 'Emp_id']);
        $prefix = config('demo.prefix') . '-';
        $relations = ['Mother', 'Father', 'Spouse', 'Brother', 'Sister'];
        foreach ($employees as $e) {
            $p = $this->people[substr($e->Emp_id, strlen($prefix))] ?? null;
            if (!$p) {
                continue;
            }
            $married = $this->chance($p['level'] === 'LINE' ? 0.35 : 0.7);
            $probation = $p['hired']->gt(Carbon::today()->subMonths(3));
            $expat = $p['nationality'] !== 'Maldivian';
            DB::table('employees')->where('id', $e->id)->update([
                'title' => $p['gender'] === 'female' ? ($married ? 'Mrs' : 'Miss') : 'Mr',
                'marital_status' => $married ? 'Married' : 'Single', 'contract_type' => $married ? 'Married' : 'Single',
                'blood_group' => $this->pick(['A+', 'B+', 'O+', 'AB+', 'A-', 'O-']), 'location' => 'Resorts',
                'entitled_overtime' => in_array($p['level'], ['LINE', 'SUP'], true) ? 'yes' : 'no',
                'entitled_service_charge' => 'yes', 'entitled_public_holiday' => 'yes',
                'entitled_annual_leave_ticket' => $expat ? 'yes' : 'no', 'ewt_status' => 'no',
                'proposed_salary_unit' => $p['currency'],
                'probation_status' => $probation ? 'Active' : 'Confirmed',
                'probation_end_date' => $probation ? $p['hired']->copy()->addMonths(3)->format('Y-m-d') : null,
                'emg_cont_first_name' => $this->pick($this->data['names'][$p['nationality']][2]) . ' ' . explode(' ', $p['name'], 2)[1],
                'emg_cont_relationship' => $this->pick($relations), 'emg_cont_no' => '+960 7' . mt_rand(100000, 999999),
            ]);
        }

        // Every demo login uses DEMO_PASSWORD and goes straight in (no forced change).
        DB::table('resort_admins')->where('resort_id', $this->resortId)->where('is_master_admin', 0)
            ->update(['password' => Hash::make(config('demo.password')), 'must_change_password' => 0]);

        // Full menu for every named demo login's position (user decision: data still role-scoped).
        $loginPositions = DB::table('employees as e')->join('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', $this->resortId)->where('a.email', 'not like', 'demo-%')->distinct()->pluck('e.Position_id');
        foreach ($loginPositions as $positionId) {
            Common::grantDefaultPageAccess($this->resortId, (int) $positionId);
        }
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }

    private function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    private function weighted(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return $key;
            }
        }
        return array_key_first($weights);
    }
}
