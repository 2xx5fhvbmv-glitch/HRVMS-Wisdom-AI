<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeave;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportLeaves implements ToModel, WithHeadingRow
{
    protected $resort_id;

    public function __construct($resort_id)
    {
        $this->resort_id = $resort_id;
    }

    public function model(array $row)
    {
        // Extract (EMP123) from "John Doe (EMP123)"
        preg_match('/\((.*?)\)/', $row['employee_id'], $emp_id);

        if (!isset($emp_id[1])) {
            return null; // Invalid format
        }

        // Find leave category
        $leaveCategory = LeaveCategory::where('resort_id', $this->resort_id)
            ->where("leave_type", $row['leave_category'])
            ->first();

            // Find employee
            $employee = Employee::where('resort_id', $this->resort_id)
            ->where("Emp_id", $emp_id[1])
            ->first();
            
            
            // If data is not found, skip row
            if (!$leaveCategory || !$employee) {
                return null;
            }
        // Return new EmployeeLeave record
        return new EmployeeLeave([
            'resort_id'         => $this->resort_id,
            'emp_id'            => $employee->id,
            'leave_category_id' => $leaveCategory->id,
            'from_date'         => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['from_date'])->format('Y-m-d'),
            'to_date'           => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['to_date'])->format('Y-m-d'),
            'reason'            => $row['leave_reason'],
            'total_days'        => $row['total_days'],
            'status'            => $row['status'],
        ]);
    }
}
