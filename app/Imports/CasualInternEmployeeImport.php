<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Auth;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Helpers\Common;
use DB;

/**
 * Bulk-import EXISTING Casual/Intern staff a resort already has on-site
 * when first onboarding onto the system — mirrors EmployeeImport.php's
 * pattern (ToModel/WithHeadingRow, Common::persistEmployeeProfile() for
 * the actual write) but deliberately does NOT reuse that class/template.
 * EmployeeImport's required-field list (division/email/DOB/address/...)
 * is the full permanent-employee shape; Casual/Intern only need name,
 * passport/ID, nationality, mobile number, department/position, and a
 * reporting manager — forcing that data through the heavier template
 * would mean either padding every row with placeholder permanent-hire
 * fields or overloading EmployeeImport with conditional required-field
 * logic. A second, lighter importer is the smaller, more honest fit.
 */
class CasualInternEmployeeImport implements ToModel, WithHeadingRow
{
    const MAX_ROWS = 500;
    const EMPLOYMENT_TYPES = ['Casual', 'Internship'];

    protected $resort;
    public $rowNumber = 0;
    public $errors = [];
    public $created = 0;
    public $updated = 0;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        $excelRowNumber = $this->rowNumber + $this->startRow() - 1;

        if ($this->isRowEmpty($row)) {
            return null;
        }

        if ($this->rowNumber > self::MAX_ROWS) {
            $this->addError($excelRowNumber, $row, 'Import capped at ' . self::MAX_ROWS . ' rows. Remaining rows were skipped — split the file and upload the rest separately.');
            return null;
        }

        $firstName = trim((string) ($row['firstname'] ?? ''));
        $lastName = trim((string) ($row['lastname'] ?? ''));
        $idNumber = trim((string) ($row['passportidnumber'] ?? ''));
        $nationality = ucfirst(trim((string) ($row['nationality'] ?? '')));
        $mobile = trim((string) ($row['mobilenumber'] ?? ''));
        $departmentName = trim((string) ($row['department'] ?? ''));
        $positionName = trim((string) ($row['position'] ?? ''));
        $reportingEmpCode = trim((string) ($row['reportingmanagerempid'] ?? ''));
        $employmentType = trim((string) ($row['employmenttype'] ?? ''));

        $missing = [];
        if ($firstName === '') $missing[] = 'FirstName';
        if ($lastName === '') $missing[] = 'LastName';
        if ($idNumber === '') $missing[] = 'PassportIdNumber';
        if ($nationality === '') $missing[] = 'Nationality';
        if ($mobile === '') $missing[] = 'MobileNumber';
        if ($departmentName === '') $missing[] = 'Department';
        if ($positionName === '') $missing[] = 'Position';
        if ($reportingEmpCode === '') $missing[] = 'ReportingManagerEmpId';
        if ($employmentType === '') $missing[] = 'EmploymentType';
        if (!empty($missing)) {
            $this->addError($excelRowNumber, $row, 'Missing required field(s): ' . implode(', ', $missing) . '.');
            return null;
        }

        if (!in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $this->addError($excelRowNumber, $row, "EmploymentType '{$employmentType}' is invalid. Allowed: " . implode(', ', self::EMPLOYMENT_TYPES) . '.');
            return null;
        }

        $department = ResortDepartment::where('name', $departmentName)
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        if (!$department) {
            $this->addError($excelRowNumber, $row, "Department '{$departmentName}' does not match an existing department.");
            return null;
        }

        $position = ResortPosition::where('position_title', $positionName)
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->where('dept_id', $department->id)
            ->first();
        if (!$position) {
            $this->addError($excelRowNumber, $row, "Position '{$positionName}' does not match an existing position in {$departmentName}.");
            return null;
        }

        // Mandatory — this is who marks the imported employee's daily
        // attendance/leave (Phase 5), so a row with no valid reporting_to
        // fails validation instead of silently importing with a null
        // supervisor.
        $reportingEmployee = Employee::where('Emp_id', $reportingEmpCode)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        if (!$reportingEmployee) {
            $this->addError($excelRowNumber, $row, "ReportingManagerEmpId '{$reportingEmpCode}' does not match an existing employee in this resort.");
            return null;
        }

        $rank = Common::GetResortPositionWiseRank($position->id, $position->Rank, $this->resort->resort_id);

        // resort_admins.email is NOT NULL + UNIQUE, but Casual/Agency
        // workers often have no company (or even personal) email on
        // file — a placeholder, unique per row, satisfies the constraint
        // without forcing HR to invent real addresses for every row.
        // Never used to send anything: Phase 4's mobile-access gate
        // already skips credential email for Casual/Intern regardless.
        $emailRaw = trim((string) ($row['email'] ?? ''));
        $email = $emailRaw !== ''
            ? $emailRaw
            : strtolower($employmentType) . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $idNumber) . '-' . $this->resort->resort_id . '@placeholder.internal';

        if (ResortAdmin::where('email', $email)->exists()) {
            $this->addError($excelRowNumber, $row, "Email '{$email}' is already in use.");
            return null;
        }

        $ResortAdmindata = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'gender' => strtolower(trim((string) ($row['gender'] ?? ''))) ?: null,
            'type' => 'sub',
            'role_id' => 0,
            'is_master_admin' => 0,
            'is_employee' => 1,
            'personal_phone' => $mobile,
            'profile_picture' => 0,
            'status' => 'Active',
        ];

        $employeeData = [
            'resort_id' => $this->resort->resort_id,
            'division_id' => $department->division_id,
            'Dept_id' => $department->id,
            'Position_id' => $position->id,
            'reporting_to' => $reportingEmployee->id,
            'nationality' => $nationality,
            'is_employee' => 1,
            'rank' => $position->Rank,
            'main_rank' => $rank,
            'status' => 'Active',
            'title' => 'Mr',
            'joining_date' => now()->format('Y-m-d'),
            'employment_type' => $employmentType,
            'passport_number' => $idNumber,
        ];

        DB::transaction(function () use ($ResortAdmindata, $employeeData) {
            $profile = Common::persistEmployeeProfile($ResortAdmindata, $employeeData, $this->resort->resort_id);
            if ($profile['employeeCreated']) {
                $this->created++;
            } else {
                $this->updated++;
            }
        });

        // Same mobile-access gate as EmployeeController::sendCredentials()
        // / EmployeeImport.php's bulk path — Casual/Intern get no mobile
        // app access, so no credential email fires for this import
        // either. (Common::manningCategory() will always be non-Permanent
        // here since EMPLOYMENT_TYPES only allows Casual/Internship, but
        // resolved explicitly rather than assumed, matching the other two
        // gate call sites.)
        // Intentionally no sendResortemployee() call at all — this import
        // never issues credentials, unlike EmployeeImport.php's Permanent
        // path.

        return null;
    }

    private function addError(int $excelRowNumber, array $row, string $error): void
    {
        // Same structured shape as EmployeeImport::addError() — the
        // upload page's error table (reused as-is) expects
        // {row, name, email, department, position, error}.
        $this->errors[] = [
            'row' => $excelRowNumber,
            'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: 'N/A',
            'email' => $row['email'] ?? 'N/A',
            'department' => $row['department'] ?? 'N/A',
            'position' => $row['position'] ?? 'N/A',
            'error' => $error,
        ];
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}
