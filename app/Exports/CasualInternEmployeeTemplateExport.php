<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use App\Models\Employee;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;

/**
 * Bulk-onboarding template for EXISTING Casual/Intern staff — see
 * CasualInternEmployeeImport for the matching importer and why this is a
 * separate, lighter template rather than reusing the Master (permanent)
 * Employee Import template.
 */
class CasualInternEmployeeTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    protected $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function headings(): array
    {
        return [
            'FirstName',
            'LastName',
            'PassportIdNumber',
            'Nationality',
            'MobileNumber',
            'Department',
            'Position',
            'ReportingManagerEmpId',
            'EmploymentType',
            'Email',
            'Gender',
        ];
    }

    public function collection()
    {
        return collect([[
            'firstname' => '', 'lastname' => '', 'passportidnumber' => '', 'nationality' => '',
            'mobilenumber' => '', 'department' => '', 'position' => '', 'reportingmanagerempid' => '',
            'employmenttype' => '', 'email' => '', 'gender' => '',
        ]]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)
                    ->where('status', 'active')
                    ->pluck('name')->filter()->unique()->values()->toArray();

                $positions = ResortPosition::where('resort_id', $this->resort->resort_id)
                    ->where('status', 'active')
                    ->pluck('position_title')->filter()->unique()->values()->toArray();

                $reportingEmpIds = Employee::where('resort_id', $this->resort->resort_id)
                    ->where('status', 'Active')
                    ->pluck('Emp_id')->filter()->unique()->values()->toArray();

                $employmentTypes = ['Casual', 'Internship'];

                $addListValidation = function (string $range, array $options, string $title, string $prompt) use ($sheet) {
                    if (empty($options)) return;
                    $validation = $sheet->getDataValidation($range);
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Please select a value from the dropdown list.')
                        ->setPromptTitle($title)
                        ->setPrompt($prompt)
                        ->setFormula1('"' . implode(',', $options) . '"');

                    for ($row = 2; $row <= 500; $row++) {
                        $col = substr($range, 0, 1);
                        $cellValidation = clone $validation;
                        $sheet->setDataValidation($col . $row, $cellValidation);
                    }
                };

                $addListValidation('F2', $departments, 'Department', 'Choose a department from the dropdown');
                $addListValidation('G2', $positions, 'Position', 'Choose a position from the dropdown');
                $addListValidation('H2', $reportingEmpIds, 'Reporting Manager', 'Choose the reporting manager\'s Employee ID');
                $addListValidation('I2', $employmentTypes, 'Employment Type', 'Casual or Internship only');
            },
        ];
    }
}
