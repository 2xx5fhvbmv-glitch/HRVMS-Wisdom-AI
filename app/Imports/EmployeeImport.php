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
use Illuminate\Support\Str;
use App\Helpers\Common;
use Hash;
use DB;
use App\Models\ResortDepartment;
use App\Models\Position;


class EmployeeImport implements ToModel, WithHeadingRow
{
    protected $departmentId;
    protected $positionId;
    protected $resort;
    protected $rowNumber = 0;

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
        
        // Keep track of errors
        static $errors = [];

        // Validate required fields first
        $validationErrors = $this->validateRequiredFields($row, $excelRowNumber);
        if (!empty($validationErrors)) {
            $errors = array_merge($errors, $validationErrors);
            session(['import_errors' => $errors]);
            return null;
        }

        // Check if row is empty - skip if all required fields are empty
        if ($this->isRowEmpty($row)) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => "Row is empty",
                'email' => "N/A",
                'department' => "N/A",
                'position' => "N/A",
                'error' => "Empty row - all required fields are missing."
            ];
            session(['import_errors' => $errors]);
            return null;
        }

        $division = $row['division'] ?? '';
        $divisionname = preg_replace("/\s*\(.*?\)/", "", $division);

        // Get the department
        $division = ResortDivision::where('name', $divisionname)
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->first();
            
        if (!$division) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'email' => $row['email'] ?? 'N/A',
                'department' => $row['department'] ?? 'N/A',
                'position' => $row['position'] ?? 'N/A',
                'error' => "Division '{$divisionname}' does not match with the internal link."
            ];
            session(['import_errors' => $errors]);
            return null;
        }

        $department = ResortDepartment::where('name', preg_replace("/\s*\(.*?\)/", "", $row['department'] ?? ''))
            ->where('status', 'active')
            ->where('division_id', $division->id)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        
        if (!$department) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'email' => $row['email'] ?? 'N/A',
                'department' => $row['department'] ?? 'N/A',
                'position' => $row['position'] ?? 'N/A',
                'error' => "Department '" . ($row['department'] ?? 'N/A') . "' does not match with the internal link."
            ];
            session(['import_errors' => $errors]);
            return null;
        }

        $position = ResortPosition::where('position_title', $row['position'] ?? '')
            ->where('status', 'active') 
            ->where('resort_id', $this->resort->resort_id)
            ->where('dept_id', $department->id)
            ->first();

        if (!$position) {
            $errors[] = [
                'row' => $excelRowNumber,
                'name' => trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')),
                'email' => $row['email'] ?? 'N/A',
                'department' => $row['department'] ?? 'N/A',
                'position' => $row['position'] ?? 'N/A',
                'error' => "Position '" . ($row['position'] ?? 'N/A') . "' does not match with the internal link."
            ];
            session(['import_errors' => $errors]);
            return null;
        } else {
            $Rank = Common::GetResortPositionWiseRank($position->id, $position->Rank, $this->resort->resort_id);
        }

        $section = ResortSection::where('name', $row['section'] ?? '')
            ->where('status', 'active')
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        // Process the employee data
        if (!empty($row['firstname']) && !empty($row['lastname']) && !empty($row['email'])) {
            $parts = explode(' ', $this->resort->resort->resort_name);
            $initials = $this->resort->resort->resort_prefix;
            $existingResortAdmin = ResortAdmin::where('email', $row['email'])->first();
            $password = Common::generateUniquePassword(8);
            $hashedPassword = Hash::make($password);

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
                'gender' => $row['gender'] ?? null,
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
                'Position_access' => $Access_position
            ];
               
            if ($existingResortAdmin) {
                $existingResortAdmin->update($ResortAdmindata);
                $ResortAdmin = $existingResortAdmin;
            } else {
                $ResortAdmindata['resort_id'] = $this->resort->resort_id;
                $ResortAdmindata['password'] = $hashedPassword;
                $ResortAdmin = ResortAdmin::create($ResortAdmindata);
                $ResortAdmin->sendResortemployee($this->resort->resort, $ResortAdmin, $password);
            }

            $employeeData = [
                'Admin_Parent_id' => $ResortAdmin->id,
                'name' => $row['firstname'],
                'division_id' => $division->id,
                'Dept_id' => $department->id,
                'Position_id' => $position->id ?? null,
                'Section_id' => $section->id ?? null, 
                'resort_id' => $this->resort->resort_id,
                'personal_phone' => $row['personalphoneno'] ?? null,
                'nationality' => ucfirst($row['nationality'] ?? ''),
                'is_employee' => 1,
                'rank' => $position->Rank,
                'main_rank' => $Rank,
                'status' => 'Active',
            ];
            
            $Employee = Employee::where('Admin_Parent_id', $ResortAdmin->id)->first();
            if ($Employee) {
                $Employee->update($employeeData);
            } else {
                $employeeData['Emp_id'] = $initials . '-' . Common::GetLastEmpId($this->resort->resort_id);
                $Employee = Employee::create($employeeData);
                $folderName = Common::CreateFirstTimeEmployeeFolders($this->resort->resort_id, $this->resort->resort->resort_id, $Employee->Emp_id);
            }
            
            DB::commit();
        }
        
        return null;
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
            'email' => 'Email'
        ];

        $missingFields = [];
        
        foreach ($requiredFields as $field => $fieldName) {
            $value = $row[$field] ?? null;
            if (is_null($value) || trim((string)$value) === '') {
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
                'error' => 'Missing required fields: ' . implode(', ', $missingFields)
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
                'error' => 'Invalid email format: ' . $row['email']
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
            if (!is_null($value) && trim((string)$value) !== '') {
                return false; // Row has data
            }
        }
        
        return true; // Row is empty
    }
}