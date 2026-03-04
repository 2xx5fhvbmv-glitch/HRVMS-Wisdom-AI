<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveSheet implements FromCollection, WithHeadings, WithEvents
{
    public $resort_id, $division, $department, $section, $position, $start_date, $end_date;

    public function __construct($resort_id, $division, $department, $section, $position, $start_date = null, $end_date = null)
    {
        $this->resort_id = $resort_id;
        $this->division = $division;
        $this->department = $department;
        $this->section = $section;
        $this->position = $position;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function collection()
    {
        // Template download: add one placeholder row so user sees date format and can fill data
        if (!$this->start_date || !$this->end_date) {
            $firstEmployee = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                ->where('ra.resort_id', $this->resort_id)
                ->selectRaw("CONCAT(ra.first_name, ' ', ra.last_name, ' (', employees.Emp_id, ')') as employee_info")
                ->first();
            $firstCategory = LeaveCategory::where('resort_id', $this->resort_id)->value('leave_type');
            $emp = $firstEmployee ? $firstEmployee->employee_info : 'Select from dropdown';
            $cat = $firstCategory ?: 'Select from dropdown';
            return collect([
                [$emp, $cat, 'Enter leave reason', 'dd-mm-yyyy', 'dd-mm-yyyy', '', 'Pending'],
            ]);
        }

        $query = EmployeeLeave::query()
            ->where('employees_leaves.resort_id', $this->resort_id)
            ->where(function ($q) {
                $q->whereBetween('employees_leaves.from_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('employees_leaves.to_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($q2) {
                        $q2->where('employees_leaves.from_date', '<=', $this->start_date)
                            ->where('employees_leaves.to_date', '>=', $this->end_date);
                    });
            })
            ->join('employees', 'employees.id', '=', 'employees_leaves.emp_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
            ->leftJoin('leave_categories as lc', 'lc.id', '=', 'employees_leaves.leave_category_id')
            ->select(
                \DB::raw("CONCAT(COALESCE(ra.first_name,''), ' ', COALESCE(ra.last_name,''), ' (', employees.Emp_id, ')') as employee_id"),
                'lc.leave_type as leave_category',
                'employees_leaves.reason as leave_reason',
                'employees_leaves.from_date as from_date',
                'employees_leaves.to_date as to_date',
                'employees_leaves.total_days as total_days',
                'employees_leaves.status as status'
            )
            ->orderBy('employees_leaves.from_date');

        if ($this->division) {
            $query->where('employees.division_id', $this->division);
        }
        if ($this->department) {
            $query->where('employees.Dept_id', $this->department);
        }
        if ($this->section) {
            $query->where('employees.Section_id', $this->section);
        }
        if ($this->position) {
            $query->where('employees.Position_id', $this->position);
        }

        $rows = $query->get();

        return $rows->map(function ($row) {
            return [
                $row->employee_id,
                $row->leave_category ?? '',
                $row->leave_reason ?? '',
                $row->from_date,
                $row->to_date,
                $row->total_days ?? '',
                $row->status ?? '',
            ];
        });
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
                $spreadsheet = $sheet->getParent();

                // Only add dropdowns and hidden sheet for template (no date range). Data export stays single-sheet.
                if ($this->start_date || $this->end_date) {
                    $spreadsheet->setActiveSheetIndex(0);
                    return;
                }

                // Build employee list for template dropdowns
                $empQuery = Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                    ->where('ra.resort_id', $this->resort_id);
                if ($this->division) {
                    $empQuery->where('employees.division_id', $this->division);
                }
                if ($this->department) {
                    $empQuery->where('employees.Dept_id', $this->department);
                }
                if ($this->section) {
                    $empQuery->where('employees.Section_id', $this->section);
                }
                if ($this->position) {
                    $empQuery->where('employees.Position_id', $this->position);
                }
                $employees = $empQuery
                    ->selectRaw("CONCAT(ra.first_name, ' ', ra.last_name, ' (', employees.Emp_id, ')') as employee_info")
                    ->get()
                    ->pluck('employee_info')
                    ->values()
                    ->all();

                $categories = LeaveCategory::where('resort_id', $this->resort_id)->pluck('leave_type')->values()->all();
                $statuses = ['Approved', 'Rejected', 'Pending'];

                // Create hidden sheet for dropdown source data (A=Employees, B=Leave categories, C=Status)
                $hiddenSheet = $spreadsheet->createSheet();
                $hiddenSheet->setTitle('LeaveLists');

                foreach ($employees as $i => $val) {
                    $hiddenSheet->setCellValue('A' . ($i + 1), $val);
                }
                foreach ($categories as $i => $val) {
                    $hiddenSheet->setCellValue('B' . ($i + 1), $val);
                }
                foreach ($statuses as $i => $val) {
                    $hiddenSheet->setCellValue('C' . ($i + 1), $val);
                }

                $empCount = count($employees);
                $catCount = count($categories);

                // Use direct sheet references so each column gets the correct list (avoids named-range mix-up)
                $sheetName = "'LeaveLists'";
                $empFormula   = $empCount > 0 ? "={$sheetName}!\$A\$1:\$A\${$empCount}" : '"Select from dropdown"';
                $catFormula   = $catCount > 0 ? "={$sheetName}!\$B\$1:\$B\${$catCount}" : '"Select from dropdown"';
                $statusFormula = "={$sheetName}!\$C\$1:\$C\$3";

                // Dropdown for Employee ID
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("A$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($empFormula);
                    $validation->setPromptTitle('Employee ID');
                    $validation->setPrompt('Select employee from dropdown (Name (EMP001))');
                    $sheet->getCell("A$row")->setDataValidation($validation);
                }

                // Dropdown for Leave Category
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("B$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($catFormula);
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('Leave Category');
                    $validation->setPrompt('Select leave type from dropdown');
                    $sheet->getCell("B$row")->setDataValidation($validation);
                }

                // From Date – placeholder for date format
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("D$row")->getDataValidation();
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('From Date');
                    $validation->setPrompt('Use date format: dd-mm-yyyy (e.g. 31-12-2025)');
                    $sheet->getCell("D$row")->setDataValidation($validation);
                }

                // To Date – placeholder for date format
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("E$row")->getDataValidation();
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('To Date');
                    $validation->setPrompt('Use date format: dd-mm-yyyy (e.g. 31-12-2025)');
                    $sheet->getCell("E$row")->setDataValidation($validation);
                }

                // Total Days – placeholder prompt
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("F$row")->getDataValidation();
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('Total Days');
                    $validation->setPrompt('Enter number of leave days');
                    $sheet->getCell("F$row")->setDataValidation($validation);
                }

                // Dropdown for Status
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell("G$row")->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setFormula1($statusFormula);
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setPromptTitle('Status');
                    $validation->setPrompt('Select from dropdown: Approved, Rejected, or Pending');
                    $sheet->getCell("G$row")->setDataValidation($validation);
                }

                // Hide the dropdown sheet and ensure LeaveData is the active sheet
                $hiddenSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                $spreadsheet->setActiveSheetIndex(0);
            }
        ];
    }
}