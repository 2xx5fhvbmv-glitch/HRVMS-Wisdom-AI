<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\LeaveCategory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
class LeaveSheet implements FromCollection, WithHeadings, WithEvents
{
    public $resort_id, $division, $department, $section, $position;

    public function __construct($resort_id, $division, $department, $section, $position)
    {
        $this->resort_id = $resort_id;
        $this->division = $division;
        $this->department = $department;
        $this->section = $section;
        $this->position = $position;
    }

    public function collection()
    {
        return collect([]); // Empty rows, ready for data input
    }

    public function headings(): array
    {
        return ['Employee ID', 'Leave Category', 'Leave Reason', 'From Date', 'To Date', 'Total Days', 'Status'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $employees = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                    ->where('ra.resort_id', $this->resort_id)
                    ->where('employees.division_id', $this->division)
                    ->where('employees.Dept_id', $this->department)
                    ->where('employees.Position_id', $this->position)
                    ->selectRaw("CONCAT(ra.first_name, ' ', ra.last_name, ' (', employees.Emp_id, ')') as employee_info")
                    ->get()
                    ->pluck('employee_info')
                    ->toArray();
                    $employeesList = implode(",", $employees);
                // Dropdown for Employee ID from second sheet
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("A$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"'.$employeesList.'"');
                    $sheet->getCell("A$row")->setDataValidation($validation);
                }

                // Leave Categories
                $categories = LeaveCategory::where('resort_id', $this->resort_id)->pluck('leave_type')->toArray();
                $categoriesList = implode(",", $categories);

                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("B$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1('"' . $categoriesList . '"');
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(true);
                    $sheet->getCell("B$row")->setDataValidation($validation);
                }

                // Status Dropdown (Approved, Rejected, Pending)
                $statusList = '"Approved,Rejected,Pending"';
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("G$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($statusList);
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(true);
                    $sheet->getCell("G$row")->setDataValidation($validation);
                }
            }
        ];
    }
}