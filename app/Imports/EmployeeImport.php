<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Auth;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use Carbon\Carbon;
use App\Helpers\Common;
use Hash;
use DB;
use App\Models\ResortDepartment;
use App\Models\Position;

class EmployeeImport implements ToModel, WithHeadingRow
{
    // Master Import cap. Larger files should be split — this keeps the
    // synchronous upload request bounded instead of timing out mid-file.
    const MAX_ROWS = 500;

    const EMPLOYMENT_TYPES = ['Full-Time', 'Part-Time', 'Contract', 'Casual', 'Probationary', 'Internship', 'Temporary'];
    const MARITAL_STATUSES = ['Single', 'Married', 'Divorced', 'Widowed'];
    const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    const PAYMENT_MODES = ['Cash', 'Bank'];
    // employees.religion is a literal enum('0','1') — 0=non-muslim, 1=muslim
    // (see the manual Add Employee form's <select>, values 0/1). Accept the
    // same human-readable labels the manual UI shows, not raw digits only.
    const RELIGION_MAP = ['non-muslim' => '0', 'muslim' => '1', '0' => '0', '1' => '1'];

    protected $resort;
    protected $rowCapReported = false;

    // Public so the queued job (ImportEmployeesJob) can read final counts
    // and per-row errors straight off the import instance after
    // Excel::import() returns, without any session() involvement.
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
        return 2; // Assuming row 1 is headers
    }

    public function model(array $row)
    {
        $this->rowNumber++;
        $excelRowNumber = $this->rowNumber + $this->startRow() - 1;

        // Silently skip completely empty rows (template padding rows)
        if ($this->isRowEmpty($row)) {
            return null;
        }

        if ($this->rowNumber > self::MAX_ROWS) {
            if (!$this->rowCapReported) {
                $this->addError($excelRowNumber, $row, 'Import capped at ' . self::MAX_ROWS . ' rows. Remaining rows were skipped — split the file and upload the rest separately.');
                $this->rowCapReported = true;
            }
            return null;
        }

        // Validate required fields
        $validationErrors = $this->validateRequiredFields($row, $excelRowNumber);
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $error) {
                $this->errors[] = $error;
            }
            return null;
        }

        $division = $row['division'] ?? '';
        $divisionname = $this->stripCode($division);

        $division = ResortDivision::where('name', $divisionname)
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$division) {
            $this->addError($excelRowNumber, $row, "Division '{$divisionname}' does not match with the internal link.");
            return null;
        }

        $department = ResortDepartment::where('name', $this->stripCode($row['department'] ?? ''))
            ->where('status', 'active')
            ->where('division_id', $division->id)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$department) {
            $this->addError($excelRowNumber, $row, "Department '" . ($row['department'] ?? 'N/A') . "' does not match with the internal link.");
            return null;
        }

        // NOTE: the export template's Position/Section dropdowns show
        // "Title (code)" — the previous version of this import only
        // stripped the "(code)" suffix for division/department, so any
        // row using the dropdown-selected position/section value always
        // failed to resolve. Strip it here too.
        $position = ResortPosition::where('position_title', $this->stripCode($row['position'] ?? ''))
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->where('dept_id', $department->id)
            ->first();

        if (!$position) {
            $this->addError($excelRowNumber, $row, "Position '" . ($row['position'] ?? 'N/A') . "' does not match with the internal link.");
            return null;
        }

        $Rank = Common::GetResortPositionWiseRank($position->id, $position->Rank, $this->resort->resort_id);

        $section = ResortSection::where('name', $this->stripCode($row['section'] ?? ''))
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        // Optional enum fields — validate the value when given, otherwise
        // fall back to the same defaults employees.* columns already carry.
        $employmentType = trim((string) ($row['employmenttype'] ?? ''));
        if ($employmentType !== '' && !in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $this->addError($excelRowNumber, $row, "EmploymentType '{$employmentType}' is invalid. Allowed: " . implode(', ', self::EMPLOYMENT_TYPES));
            return null;
        }
        $employmentType = $employmentType !== '' ? $employmentType : 'Full-Time';

        $maritalStatus = trim((string) ($row['maritalstatus'] ?? ''));
        if ($maritalStatus !== '' && !in_array($maritalStatus, self::MARITAL_STATUSES, true)) {
            $this->addError($excelRowNumber, $row, "MaritalStatus '{$maritalStatus}' is invalid. Allowed: " . implode(', ', self::MARITAL_STATUSES));
            return null;
        }
        $maritalStatus = $maritalStatus !== '' ? $maritalStatus : 'Single';

        $bloodGroup = trim((string) ($row['bloodgroup'] ?? ''));
        if ($bloodGroup !== '' && !in_array($bloodGroup, self::BLOOD_GROUPS, true)) {
            $this->addError($excelRowNumber, $row, "BloodGroup '{$bloodGroup}' is invalid. Allowed: " . implode(', ', self::BLOOD_GROUPS));
            return null;
        }
        $bloodGroup = $bloodGroup !== '' ? $bloodGroup : null;

        $nationality = ucfirst($row['nationality'] ?? '');
        if (!in_array($nationality, config('settings.nationalities') ?? [], true)) {
            $this->addError($excelRowNumber, $row, "Nationality '{$nationality}' is not a recognized nationality.");
            return null;
        }

        // employees.payment_mode is enum('Cash','Bank') NOT NULL DEFAULT
        // 'Cash' — omit the key entirely when blank so the DB applies its
        // default instead of inserting an explicit NULL (which violates the
        // NOT NULL constraint even though the column itself has a default).
        $paymentMode = trim((string) ($row['paymentmode'] ?? ''));
        if ($paymentMode !== '' && !in_array($paymentMode, self::PAYMENT_MODES, true)) {
            $this->addError($excelRowNumber, $row, "PaymentMode '{$paymentMode}' is invalid. Allowed: " . implode(', ', self::PAYMENT_MODES));
            return null;
        }

        $religion = null;
        $religionRaw = strtolower(trim((string) ($row['religion'] ?? '')));
        if ($religionRaw !== '') {
            if (!isset(self::RELIGION_MAP[$religionRaw])) {
                $this->addError($excelRowNumber, $row, "Religion '{$row['religion']}' is invalid. Allowed: Muslim, Non-Muslim.");
                return null;
            }
            $religion = self::RELIGION_MAP[$religionRaw];
        }

        // Reporting manager is optional — resolved by email to an existing
        // employee in this resort (self-relation employees.reporting_to).
        $reportingToId = null;
        $reportingEmail = trim((string) ($row['reportingmanageremail'] ?? ''));
        if ($reportingEmail !== '') {
            $reportingAdmin = ResortAdmin::where('email', $reportingEmail)
                ->where('resort_id', $this->resort->resort_id)
                ->first();
            $reportingEmployee = $reportingAdmin
                ? Employee::where('Admin_Parent_id', $reportingAdmin->id)->first()
                : null;
            if (!$reportingEmployee) {
                $this->addError($excelRowNumber, $row, "ReportingManagerEmail '{$reportingEmail}' does not match an existing employee in this resort.");
                return null;
            }
            $reportingToId = $reportingEmployee->id;
        }

        $dob = $this->parseDate($row['dob'] ?? null);
        if ($dob === false) {
            $this->addError($excelRowNumber, $row, "DOB '" . ($row['dob'] ?? '') . "' is not a valid date.");
            return null;
        }

        $joiningDate = $this->parseDate($row['joiningdate'] ?? null);
        if ($joiningDate === false) {
            $this->addError($excelRowNumber, $row, "JoiningDate '" . ($row['joiningdate'] ?? '') . "' is not a valid date.");
            return null;
        }

        // Same probation derivation as the manual Add Employee wizard
        // (People\Employee\EmployeeController::store()): only Probationary
        // hires get a probation window, everyone else is Confirmed.
        $isProbationary = ($employmentType === 'Probationary');
        $probationEndDate = $isProbationary
            ? Carbon::parse($joiningDate)->addMonths(3)->format('Y-m-d')
            : null;
        $probationStatus = $isProbationary ? 'Active' : 'Confirmed';

        $gender = strtolower(trim((string) ($row['gender'] ?? '')));
        $title = $gender === 'male' ? 'Mr.' : 'Ms.';

        $presentAddress = collect([
            $row['address1'] ?? null,
            $row['address2'] ?? null,
            $row['city'] ?? null,
            $row['state'] ?? null,
            $row['zipcode'] ?? null,
            $row['country'] ?? null,
        ])->filter(fn($v) => !is_null($v) && trim((string) $v) !== '')->implode(', ');

        $existingResortAdmin = ResortAdmin::where('email', $row['email'])->first();

        // License cap — resorts.no_of_users. Only rows that would CREATE
        // a new employee count; re-imports updating an existing employee
        // pass through. Checked before the ResortAdmin is created so no
        // login record or welcome email leaks for a blocked row.
        $hasEmployee = $existingResortAdmin
            && Employee::where('Admin_Parent_id', $existingResortAdmin->id)->exists();
        if (!$hasEmployee) {
            $limitError = Common::employeeLimitError($this->resort->resort_id);
            if ($limitError) {
                $this->addError($excelRowNumber, $row, $limitError);
                return null;
            }
        }

        $Access_position = Position::where('status', 'Active')->where('id', $position->Position_access)->first();
        if (isset($Access_position) && in_array($Access_position->position_title, ['Director Of Human Resources', 'Human Resources Manager'])) {
            $Access_position = $Access_position->id;
        } else {
            $Access_position = null;
        }

        $ResortAdmindata = [
            'first_name' => $row['firstname'],
            'last_name' => $row['lastname'],
            'middle_name' => $row['middlename'] ?? null,
            'email' => $row['email'],
            'gender' => $gender,
            'type' => 'sub',
            'role_id' => 0,
            'is_master_admin' => 0,
            'is_employee' => 1,
            'address_line_1' => $row['address1'] ?? null,
            'address_line_2' => $row['address2'] ?? null,
            'country' => $row['country'] ?? null,
            'state' => $row['state'] ?? null,
            'city' => $row['city'] ?? null,
            'zip' => $row['zipcode'] ?? null,
            'profile_picture' => 0,
            'status' => 'Active',
            'personal_phone' => $row['personalphoneno'] ?? null,
            'Position_access' => $Access_position,
        ];

        $employeeData = [
            'division_id' => $division->id,
            'Dept_id' => $department->id,
            'Position_id' => $position->id,
            'Section_id' => $section->id ?? null,
            'resort_id' => $this->resort->resort_id,
            'nationality' => $nationality,
            'is_employee' => 1,
            'rank' => $position->Rank,
            'main_rank' => $Rank,
            'status' => 'Active',
            'title' => $title,
            'dob' => $dob,
            'joining_date' => $joiningDate,
            'employment_type' => $employmentType,
            'marital_status' => $maritalStatus,
            'blood_group' => $bloodGroup,
            'religion' => $religion,
            'passport_number' => $row['passportnumber'] ?? null,
            'nid' => $row['nid'] ?? null,
            'present_address' => $presentAddress,
            'tin' => $row['tin'] ?? null,
            'contract_type' => $row['contracttype'] ?? null,
            'basic_salary' => $row['basicsalary'] ?? null,
            'basic_salary_currency' => $row['basicsalarycurrency'] ?? 'USD',
            'benefit_grid_level' => $row['benefitgridlevel'] ?? null,
            'probation_end_date' => $probationEndDate,
            'probation_status' => $probationStatus,
        ];
        if ($paymentMode !== '') {
            $employeeData['payment_mode'] = $paymentMode;
        }
        // employees.reporting_to is NOT NULL DEFAULT 0 — same trap as
        // payment_mode: omit the key when blank so the DB default applies
        // instead of an explicit NULL violating the constraint.
        if ($reportingToId !== null) {
            $employeeData['reporting_to'] = $reportingToId;
        }

        // Password/welcome-email only apply the first time this email is
        // seen — decided here (not inside the shared helper) since that's
        // the caller's upsert POLICY, not the mechanical persist step.
        $password = null;
        if (!$existingResortAdmin) {
            $password = Common::generateUniquePassword(8);
            $ResortAdmindata['password'] = Hash::make($password);
        }

        DB::transaction(function () use ($existingResortAdmin, $ResortAdmindata, $employeeData, $password) {
            // Shared with the manual Add Employee wizard
            // (People\Employee\EmployeeController::store()) — see
            // Common::persistEmployeeProfile() for the single write path.
            $profile = Common::persistEmployeeProfile($ResortAdmindata, $employeeData, $this->resort->resort_id, $existingResortAdmin);

            if ($profile['employeeCreated']) {
                $this->created++;
            } else {
                $this->updated++;
            }

            if (!$existingResortAdmin) {
                $profile['resortAdmin']->sendResortemployee($this->resort->resort, $profile['resortAdmin'], $password);
            }
        });

        return null;
    }

    private function stripCode($value): string
    {
        return trim(preg_replace('/\s*\(.*?\)/', '', (string) $value));
    }

    /**
     * Accepts an Excel date serial number, a d/m/Y string, or anything else
     * Carbon can parse. Returns a Y-m-d string, null when blank, or false
     * when the value is present but unparseable (caller reports the error).
     */
    private function parseDate($value)
    {
        if (is_null($value) || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return false;
            }
        }

        $value = trim((string) $value);
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    private function addError(int $excelRowNumber, array $row, string $error): void
    {
        $this->errors[] = [
            'row' => $excelRowNumber,
            'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: 'N/A',
            'email' => $row['email'] ?? 'N/A',
            'department' => $row['department'] ?? 'N/A',
            'position' => $row['position'] ?? 'N/A',
            'error' => $error,
        ];
    }

    /**
     * Validate required fields and return specific error messages
     */
    private function validateRequiredFields(array $row, int $excelRowNumber): array
    {
        $errors = [];
        $requiredFields = [
            'division' => 'Division',
            'department' => 'Department',
            'position' => 'Position',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'nationality' => 'Nationality',
            'dob' => 'DOB',
            'joiningdate' => 'JoiningDate',
            'gender' => 'Gender',
        ];

        $missingFields = [];

        foreach ($requiredFields as $field => $fieldName) {
            $value = $row[$field] ?? null;
            if (is_null($value) || trim((string) $value) === '') {
                $missingFields[] = $fieldName;
            }
        }

        if (!empty($missingFields)) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: 'N/A',
                'email' => $row['email'] ?? 'N/A',
                'department' => $row['department'] ?? 'N/A',
                'position' => $row['position'] ?? 'N/A',
                'error' => 'Missing required fields: ' . implode(', ', $missingFields),
            ];
        }

        // Additional validation for email format
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: 'N/A',
                'email' => $row['email'],
                'department' => $row['department'] ?? 'N/A',
                'position' => $row['position'] ?? 'N/A',
                'error' => 'Invalid email format: ' . $row['email'],
            ];
        }

        return $errors;
    }

    /**
     * Check if a row is empty based on required fields
     */
    private function isRowEmpty(array $row): bool
    {
        $requiredFields = ['division', 'department', 'position', 'firstname', 'lastname', 'email'];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;
            if (!is_null($value) && trim((string) $value) !== '') {
                return false; // Row has data
            }
        }

        return true; // Row is empty
    }
}
