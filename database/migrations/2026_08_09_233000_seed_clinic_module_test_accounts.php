<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Helpers\Common;

/**
 * Two dedicated test accounts for QA to verify the Clinic module role split:
 * - clinic-employee-test@example.test (rank 6, Line Worker) — Employee
 *   Clinic APIs only (book appointment, own history, etc).
 * - clinic-manager-test@example.test (rank 12, CLINIC_STAFF) — also gets
 *   the Clinic Manager APIs (dashboard, treatment records, medical
 *   certificates, leave sign-off for the resort).
 *
 * Brand-new accounts, not a repurposed real employee — the Clinic Manager
 * role has real read access to other employees' medical/health data, so
 * this avoids handing that out on an existing person's login. Anchored off
 * employee DR-5's resort/department/position (a real, already-known
 * employee) rather than a hardcoded resort_id, since resort ids can differ
 * across environments.
 */
return new class extends Migration
{
    public function up()
    {
        $anchor = Employee::where('Emp_id', 'DR-5')->first();
        if (!$anchor) {
            // Anchor employee not present in this environment — nothing to
            // key off safely, skip rather than guess a resort.
            return;
        }

        $resortId   = $anchor->resort_id;
        $deptId     = $anchor->Dept_id;
        $positionId = $anchor->Position_id;

        DB::transaction(function () use ($resortId, $deptId, $positionId) {
            $this->createTestAccount(
                $resortId, $deptId, $positionId,
                'Clinic', 'TestEmployee', 'clinic-employee-test@example.test', 6
            );

            $this->createTestAccount(
                $resortId, $deptId, $positionId,
                'Clinic', 'TestManager', 'clinic-manager-test@example.test', 12
            );
        });
    }

    private function createTestAccount($resortId, $deptId, $positionId, $firstName, $lastName, $email, $rank)
    {
        if (ResortAdmin::where('email', $email)->exists()) {
            return;
        }

        $admin = ResortAdmin::create([
            'resort_id'     => $resortId,
            'role_id'       => 0,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'email'         => $email,
            'password'      => Hash::make('ClinicQA#2026!'),
            'type'          => 'sub',
            'gender'        => 'male',
            'is_employee'   => 1,
            'status'        => 'Active',
            'signature_img' => '',
        ]);

        Employee::create([
            'resort_id'       => $resortId,
            'Emp_id'          => Common::nextEmployeeId($resortId),
            'Admin_Parent_id' => $admin->id,
            'title'           => 'Mr',
            'Dept_id'         => $deptId,
            'Position_id'     => $positionId,
            'division_id'     => 0,
            'rank'            => $rank,
            'is_employee'     => 1,
            'status'          => 'Active',
            'joining_date'    => now()->toDateString(),
        ]);
    }

    public function down()
    {
        $emails = ['clinic-employee-test@example.test', 'clinic-manager-test@example.test'];

        $admins = ResortAdmin::whereIn('email', $emails)->get();
        foreach ($admins as $admin) {
            Employee::where('Admin_Parent_id', $admin->id)->forceDelete();
            $admin->delete();
        }
    }
};
