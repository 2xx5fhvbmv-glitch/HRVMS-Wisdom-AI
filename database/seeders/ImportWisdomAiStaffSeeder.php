<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Imports the 300+ staff from "Test Wisdom Ai.xls" into resort_admins + employees
 * for the resort owned by Mohamed Shad (painter_retreat05@icloud.com — resort_id 28).
 *
 * - Password: 3ed5f4be (bcrypt hashed)
 * - Dummy emails: testemp.<slug>.<n>@wisdomai.test
 * - "Verified": status=Active (there is no email_verified_at column on resort_admins)
 *
 * Run:
 *   php artisan db:seed --class=ImportWisdomAiStaffSeeder
 */
class ImportWisdomAiStaffSeeder extends Seeder
{
    public function run()
    {
        $excelPath = base_path('Test Wisdom Ai.xls');
        if (!file_exists($excelPath)) {
            $this->command->error("Excel file not found at: {$excelPath}");
            return;
        }

        $rows = Excel::toArray([], $excelPath);
        $sheet = $rows[0] ?? [];
        if (empty($sheet)) {
            $this->command->error('Empty sheet.');
            return;
        }

        // ---- Resort / defaults ----
        $resortAdmin = DB::table('resort_admins')->where('email', 'painter_retreat05@icloud.com')->first();
        if (!$resortAdmin) {
            $this->command->error('Resort admin painter_retreat05@icloud.com not found.');
            return;
        }
        $resortId       = $resortAdmin->resort_id;
        $createdBy      = $resortAdmin->id;
        $defaultDivision = DB::table('resort_divisions')->where('resort_id', $resortId)->value('id');
        $rawPassword    = '3ed5f4be';
        $hashedPassword = Hash::make($rawPassword);

        // ---- Level code -> rank mapping (config/settings.php Position_Rank) ----
        // EXCOM=1, HOD=2, HR=3, MGR=4, SUP=5, LINE=6, Finance=7, GM=8
        $levelToRank = function (?string $level, ?string $position) {
            $level = strtoupper(trim((string) $level));
            $position = strtoupper(trim((string) $position));
            if (str_starts_with($level, 'EX')) {
                return str_contains($position, 'GENERAL MANAGER') ? 8 : 1;
            }
            if (str_starts_with($level, 'HOD')) return 2;
            if (str_starts_with($level, 'MGR')) return 4;
            if (str_starts_with($level, 'SUP')) return 5;
            if (str_starts_with($level, 'LS'))  return 6;
            return 6;
        };

        // ---- Find header row (contains "Name" + "Position" + "Level") ----
        $headerIdx = null;
        foreach ($sheet as $i => $row) {
            $joined = implode('|', array_map(fn($v) => (string) $v, $row));
            if (stripos($joined, 'Name') !== false && stripos($joined, 'Position') !== false && stripos($joined, 'Level') !== false) {
                $headerIdx = $i;
                break;
            }
        }
        if ($headerIdx === null) {
            $this->command->error('Could not locate header row.');
            return;
        }
        $this->command->info("Header row: {$headerIdx}");

        // Column indices (based on inspection: # | Name | | | ID No. | Hire Date | Position | Level | Gender | | Country | | Religion | ID/Passport)
        $COL_NUM      = 0;
        $COL_NAME     = 1;
        $COL_ID       = 4;
        $COL_HIRE     = 5;
        $COL_POSITION = 6;
        $COL_LEVEL    = 7;
        $COL_GENDER   = 8;
        $COL_COUNTRY  = 10;
        $COL_RELIGION = 12;
        $COL_PASSPORT = 13;

        // ---- Walk rows. Department rows have Name set but no # and no Position ----
        $currentDept = null;
        $deptCache     = []; // name -> dept_id
        $positionCache = []; // "dept|position" -> position_id
        $imported = 0; $skipped = 0;

        DB::beginTransaction();
        try {
            for ($r = $headerIdx + 1; $r < count($sheet); $r++) {
                $row = $sheet[$r] ?? [];
                $num = $row[$COL_NUM] ?? null;
                $name = trim((string) ($row[$COL_NAME] ?? ''));

                // Department marker
                if ($num === null && $name !== '' && empty($row[$COL_POSITION])) {
                    $currentDept = $name;
                    continue;
                }

                // Skip blank rows
                if (empty($name) || empty($row[$COL_POSITION])) continue;
                if (!$currentDept) continue;

                // ---- Resolve / create department ----
                if (!isset($deptCache[$currentDept])) {
                    $dept = DB::table('resort_departments')
                        ->where('resort_id', $resortId)
                        ->whereRaw('LOWER(name) = ?', [strtolower($currentDept)])
                        ->first();
                    if (!$dept) {
                        $deptId = DB::table('resort_departments')->insertGetId([
                            'resort_id'   => $resortId,
                            'division_id' => $defaultDivision,
                            'name'        => $currentDept,
                            'code'        => strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $currentDept), 0, 4)) ?: 'DEPT',
                            'short_name'  => Str::substr($currentDept, 0, 20),
                            'status'      => 'active',
                            'created_by'  => $createdBy,
                            'modified_by' => $createdBy,
                            'slug'        => Str::slug($currentDept).'-'.Str::random(4),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    } else {
                        $deptId = $dept->id;
                    }
                    $deptCache[$currentDept] = $deptId;
                }
                $deptId = $deptCache[$currentDept];

                // ---- Resolve / create position ----
                $positionTitle = trim((string) $row[$COL_POSITION]);
                $posKey = $deptId.'|'.strtolower($positionTitle);
                if (!isset($positionCache[$posKey])) {
                    $pos = DB::table('resort_positions')
                        ->where('resort_id', $resortId)
                        ->where('Dept_id', $deptId)
                        ->whereRaw('LOWER(position_title) = ?', [strtolower($positionTitle)])
                        ->first();
                    if (!$pos) {
                        $positionId = DB::table('resort_positions')->insertGetId([
                            'resort_id'      => $resortId,
                            'Dept_id'        => $deptId,
                            'position_title' => $positionTitle,
                            'status'         => 'active',
                            'created_by'     => $createdBy,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    } else {
                        $positionId = $pos->id;
                    }
                    $positionCache[$posKey] = $positionId;
                }
                $positionId = $positionCache[$posKey];

                // ---- Name split ----
                $parts = preg_split('/\s+/', $name, 2);
                $first = $parts[0] ?? $name;
                $last  = $parts[1] ?? '';

                $gender = strtolower(trim((string) ($row[$COL_GENDER] ?? 'male')));
                $nationality = ucwords(strtolower(trim((string) ($row[$COL_COUNTRY] ?? ''))));
                $religionRaw = strtoupper(trim((string) ($row[$COL_RELIGION] ?? '')));
                $religion = $religionRaw === 'MUSLIM' ? 1 : 0;
                $empIdExternal = trim((string) ($row[$COL_ID] ?? ''));
                $passport = trim((string) ($row[$COL_PASSPORT] ?? '')) ?: null;
                $hireVal = $row[$COL_HIRE] ?? null;
                $hireDate = null;
                if (is_numeric($hireVal)) {
                    try { $hireDate = Carbon::createFromDate(1899, 12, 30)->addDays((int)$hireVal)->format('Y-m-d'); }
                    catch (\Exception $e) { $hireDate = null; }
                } elseif (!empty($hireVal)) {
                    try { $hireDate = Carbon::parse($hireVal)->format('Y-m-d'); }
                    catch (\Exception $e) { $hireDate = null; }
                }

                $rank = $levelToRank($row[$COL_LEVEL] ?? null, $positionTitle);

                // Generate unique dummy email
                $emailBase = Str::slug(strtolower($first.'.'.$last), '.') ?: 'emp';
                $emailBase = preg_replace('/[^a-z0-9\.]/', '', $emailBase) ?: 'emp';
                $email = 'testemp.'.$emailBase.'.'.$num.'@wisdomai.test';
                // Ensure uniqueness
                if (DB::table('resort_admins')->where('email', $email)->exists()) {
                    $email = 'testemp.'.$emailBase.'.'.$num.'.'.Str::random(4).'@wisdomai.test';
                }

                // Skip if an employee with this external Emp_id already exists in this resort
                if ($empIdExternal && DB::table('employees')->where('resort_id', $resortId)->where('Emp_id', $empIdExternal)->exists()) {
                    $skipped++;
                    continue;
                }

                // ---- Insert resort_admins ----
                $adminId = DB::table('resort_admins')->insertGetId([
                    'role_id'         => 0,
                    'resort_id'       => $resortId,
                    'Is_It'           => 'no',
                    'first_name'      => $first,
                    'middle_name'     => null,
                    'last_name'       => $last,
                    'email'           => $email,
                    'password'        => $hashedPassword,
                    'type'            => 'sub',
                    'is_master_admin' => 0,
                    'is_employee'     => 1,
                    'country'         => 'Maldives',
                    'menu_type'       => 'horizontal',
                    'gender'          => $gender,
                    'status'          => 'Active',
                    'created_by'      => $createdBy,
                    'modified_by'     => $createdBy,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // ---- Insert employees ----
                DB::table('employees')->insert([
                    'Admin_Parent_id' => $adminId,
                    'resort_id'       => $resortId,
                    'Emp_id'          => $empIdExternal ?: ('WAI-'.$num),
                    'division_id'     => $defaultDivision,
                    'Dept_id'         => $deptId,
                    'Position_id'     => $positionId,
                    'is_employee'     => 1,
                    'rank'            => $rank,
                    'main_rank'       => $rank,
                    'nationality'     => $nationality,
                    'location'        => $nationality === 'Maldives' ? 'Malé' : 'Resorts',
                    'joining_date'    => $hireDate,
                    'passport_number' => $passport,
                    'religion'        => $religion,
                    'status'          => 'Active',
                    'created_by'      => $createdBy,
                    'modified_by'     => $createdBy,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $imported++;
            }

            DB::commit();
            $this->command->info("Imported: {$imported} employees. Skipped duplicates: {$skipped}.");
            $this->command->info("Password for all imported users: {$rawPassword}");
            $this->command->info("Resort ID: {$resortId}");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: '.$e->getMessage().' at line '.$e->getLine().' in '.$e->getFile());
            throw $e;
        }
    }
}
