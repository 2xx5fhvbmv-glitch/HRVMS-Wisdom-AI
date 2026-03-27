<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PayrollSupervisorSeeder extends Seeder
{
    public function run()
    {
        $resortId = 26;

        // 1. Create Payroll Supervisor position in HR department
        $hrDept = DB::table('resort_departments')
            ->where('resort_id', $resortId)
            ->whereIn('name', ['Human Resources', 'HR'])
            ->first();

        if (!$hrDept) {
            $this->command->error('HR department not found for resort ' . $resortId);
            return;
        }

        $posId = DB::table('resort_positions')->insertGetId([
            'resort_id' => $resortId,
            'dept_id' => $hrDept->id,
            'position_title' => 'Payroll Supervisor',
            'code' => 'PS_1',
            'short_title' => 'PS',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create resort_admin user
        $adminId = DB::table('resort_admins')->insertGetId([
            'role_id' => 0,
            'resort_id' => $resortId,
            'first_name' => 'Sarah',
            'last_name' => 'Thompson',
            'email' => 'sarah.thompson@resort.com',
            'password' => Hash::make('password123'),
            'type' => 'sub',
            'is_employee' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Get next Emp_id
        $lastEmp = DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('Emp_id', 'like', 'DR-%')
            ->orderByRaw("CAST(SUBSTRING(Emp_id, 4) AS UNSIGNED) DESC")
            ->first();
        $nextNum = $lastEmp ? ((int) substr($lastEmp->Emp_id, 3)) + 1 : 1;
        $empCode = 'DR-' . $nextNum;

        // 4. Create employee with rank 5 (SUP)
        DB::table('employees')->insert([
            'Emp_id' => $empCode,
            'Admin_Parent_id' => $adminId,
            'resort_id' => $resortId,
            'Position_id' => $posId,
            'Dept_id' => $hrDept->id,
            'rank' => 5,
            'status' => 'Active',
            'basic_salary' => 800,
            'basic_salary_currency' => 'USD',
            'nationality' => 'Maldivian',
            'benefit_grid_level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Copy permissions from HR Manager position (or any HOD position with full access)
        $sourcePosition = DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('rank', 2) // HOD
            ->where('Dept_id', $hrDept->id)
            ->first();

        if ($sourcePosition) {
            $perms = DB::table('resort_interal_pages_permissions')
                ->where('position_id', $sourcePosition->Position_id)
                ->get();

            foreach ($perms as $p) {
                DB::table('resort_interal_pages_permissions')->insert([
                    'resort_id' => $p->resort_id,
                    'Dept_id' => $hrDept->id,
                    'position_id' => $posId,
                    'Permission_id' => $p->Permission_id,
                    'page_id' => $p->page_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->command->info("Copied " . count($perms) . " permissions from HR Manager.");
        }

        $this->command->info("Payroll Supervisor created: {$empCode} - Sarah Thompson (sarah.thompson@resort.com / password123)");
    }
}
