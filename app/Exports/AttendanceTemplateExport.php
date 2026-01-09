<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;
use Auth;
use App\Models\Employee;
use App\Models\ShiftSettings;
use App\Models\LeaveCategory;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    protected $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function headings(): array
    {
        return [
            'Employee Id',
            'Date',
            'Shift',
            'Check In Time',
            'Check Out Time',
            'Overtime',
            'Status',
            'Leave Type'
        ];
    }

    public function collection()
    {
        $result = collect();

        // Add empty rows for data entry
        $result->push([
            'employee_id' => '',
            'date' => '',
            'shift' => '',
            'check_in_time' => '',
            'check_out_time' => '',
            'overtime' => '',
            'status' => '',
            'leavetype' => '',
        ]);

        return $result;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get employees for the resort
                $employees = Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                    ->where('t1.resort_id', $this->resort->resort_id)
                    ->select('employees.Emp_id')
                    ->get()
                    ->pluck('Emp_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                // Get shifts for the resort
                $shifts = ShiftSettings::where('resort_id', $this->resort->resort_id)
                    ->select('ShiftName')
                    ->get()
                    ->pluck('ShiftName')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                // Status options
                $statusOptions = ['Present', 'Absent', 'DayOff', 'Late'];

                // Fetch leave types from database
                $leaveTypes = LeaveCategory::where('resort_id', $this->resort->resort_id)
                    ->select('leave_type')
                    ->get()
                    ->pluck('leave_type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                // Create dropdown validation for Employee Id (Column A)
                if (!empty($employees)) {
                    $validation = $sheet->getDataValidation('A2:A1000');
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select an employee from the list')
                        ->setPromptTitle('Employee Id')
                        ->setPrompt('Choose an employee from the dropdown')
                        ->setFormula1('"' . implode(',', $employees) . '"');
                }

                // Create dropdown validation for Shift (Column C)
                if (!empty($shifts)) {
                    $validation = $sheet->getDataValidation('C2:C1000');
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a shift from the list')
                        ->setPromptTitle('Shift')
                        ->setPrompt('Choose a shift from the dropdown')
                        ->setFormula1('"' . implode(',', $shifts) . '"');
                }

                // Create dropdown validation for Status (Column G)
                if (!empty($statusOptions)) {
                    $validation = $sheet->getDataValidation('G2:G1000');
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a status from the list')
                        ->setPromptTitle('Status')
                        ->setPrompt('Choose a status from the dropdown')
                        ->setFormula1('"' . implode(',', $statusOptions) . '"');
                }

                // Create dropdown validation for Leave Type (Column H)
                if (!empty($leaveTypes)) {
                    $validation = $sheet->getDataValidation('H2:H1000');
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a leave type from the list')
                        ->setPromptTitle('Leave Type')
                        ->setPrompt('Choose a leave type from the dropdown')
                        ->setFormula1('"' . implode(',', $leaveTypes) . '"');
                }

                // Auto-size columns
                foreach (range('A', 'H') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
        ];
    }
}

