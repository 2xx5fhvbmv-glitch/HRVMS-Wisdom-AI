<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportEmployeeMainSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(protected int $resortId)
    {
    }

    public function title(): string
    {
        return 'EmployeeTemplate';
    }

    public function array(): array
    {
        return array_fill(0, 250, array_fill(0, count($this->headings()), ''));
    }

    public function headings(): array
    {
        return [
            'Division', 'Department', 'Section', 'Position',
            'FirstName', 'MiddleName', 'LastName',
            'Email', 'Gender', 'PersonalPhoneNo', 'Address1', 'Address2',
            'Country', 'State', 'City', 'Zipcode', 'Nationality',
            // Appended after Nationality — the AfterSheet loop below hardcodes
            // A/B/C/D (division/department/section/position), I (gender), and
            // Q (nationality) as absolute column letters that must match these
            // heading positions, so new columns must not be inserted earlier.
            'DOB', 'JoiningDate', 'EmploymentType', 'MaritalStatus', 'BloodGroup',
            'Religion', 'PassportNumber', 'NID', 'TIN', 'ContractType', 'PaymentMode',
            'BasicSalary', 'BasicSalaryCurrency', 'BenefitGridLevel', 'ReportingManagerEmail',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style the header row
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColumn}1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DDEBF7');

                $dataRows = 250;
                $startRow = 2;

                // Hidden helper columns are placed right after the LAST visible
                // heading (count($this->headings()) + 1..+4), computed instead
                // of hardcoded — so appending new visible columns above never
                // collides with them again.
                $headingCount = count($this->headings());
                $colDivId = Coordinate::stringFromColumnIndex($headingCount + 1);   // was 'T'
                $colDeptId = Coordinate::stringFromColumnIndex($headingCount + 2);  // was 'U'
                $colDivName = Coordinate::stringFromColumnIndex($headingCount + 3); // was 'V'
                $colDeptName = Coordinate::stringFromColumnIndex($headingCount + 4);// was 'W'
                $lastHelperCol = $colDeptName;

                // Add helper columns (hidden) for lookups
                $sheet->setCellValue("{$colDivId}1", 'DivisionID');
                $sheet->setCellValue("{$colDeptId}1", 'DepartmentID');
                $sheet->setCellValue("{$colDivName}1", 'DivisionName');
                $sheet->setCellValue("{$colDeptName}1", 'DepartmentName');
                $sheet->getColumnDimension($colDivId)->setVisible(false);
                $sheet->getColumnDimension($colDeptId)->setVisible(false);
                $sheet->getColumnDimension($colDivName)->setVisible(false);
                $sheet->getColumnDimension($colDeptName)->setVisible(false);

                $lastRow = $dataRows + $startRow - 1;
                for ($row = $startRow; $row <= $lastRow; $row++) {
                    // Hidden helpers: division/department name and id for the dependent dropdowns
                    $sheet->setCellValue("{$colDivName}{$row}", "=IF(A{$row}=\"\",\"\",A{$row})");
                    $sheet->setCellValue("{$colDivId}{$row}", "=IF({$colDivName}{$row}=\"\",\"\",VLOOKUP({$colDivName}{$row},DivisionMap_Name:DivisionMap_ID,2,FALSE))");
                    $sheet->setCellValue("{$colDeptName}{$row}", "=IF(B{$row}=\"\",\"\",B{$row})");
                    $sheet->setCellValue("{$colDeptId}{$row}", "=IF({$colDeptName}{$row}=\"\",\"\",VLOOKUP({$colDeptName}{$row},DepartmentMap_Name:DepartmentMap_ID,2,FALSE))");
                }

                // One validation per column range (formulas are relative to the first row, Excel shifts them per row);
                // per-cell validations made this download take seconds.
                $s = $startRow;
                $this->setDropdown($sheet, "A{$s}:A{$lastRow}", "=Divisions", 'Division', 'Select a division from the dropdown');
                $this->setDropdown($sheet, "B{$s}:B{$lastRow}", "=IF({$colDivId}{$s}=\"\",\"\",INDIRECT({$colDivId}{$s}&\"_depts\"))", 'Department', 'Select a department (filtered by division)');
                $this->setDropdown($sheet, "C{$s}:C{$lastRow}", "=IF({$colDeptId}{$s}=\"\",\"\",IF(ISERROR(INDIRECT({$colDeptId}{$s}&\"_sections\")),\"\",INDIRECT({$colDeptId}{$s}&\"_sections\")))", 'Section', 'Select a section (filtered by department)');
                $this->setDropdown($sheet, "D{$s}:D{$lastRow}", "=IF({$colDeptId}{$s}=\"\",\"\",IF(ISERROR(INDIRECT({$colDeptId}{$s}&\"_positions\")),\"\",INDIRECT({$colDeptId}{$s}&\"_positions\")))", 'Position', 'Select a position (filtered by department)');
                foreach (['I' => 'Genders', 'Q' => 'Nationalities', 'T' => 'EmploymentTypes', 'U' => 'MaritalStatuses', 'V' => 'BloodGroups', 'W' => 'Religions', 'AB' => 'PaymentModes'] as $col => $list) {
                    $this->setDropdown($sheet, "{$col}{$s}:{$col}{$lastRow}", "={$list}");
                }

                // Set fixed width for Division, Department, Position, Section columns to accommodate codes
                $sheet->getColumnDimension('A')->setWidth(25); // Division (name + code)
                $sheet->getColumnDimension('B')->setWidth(25); // Department (name + code)
                $sheet->getColumnDimension('C')->setWidth(25); // Section (name + code)
                $sheet->getColumnDimension('D')->setWidth(25); // Position (name + code)

                // Auto-size remaining visible columns (excluding the fixed-width columns).
                // NOTE: PHP's range() only handles single-character alpha bounds —
                // with 30+ columns the last one is 2 letters (e.g. "AF"), so walk
                // column INDEXES and convert each back to a letter instead.
                for ($i = Coordinate::columnIndexFromString('E'); $i <= $headingCount; $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
                }

                // Protect the sheet but allow selecting unlocked cells
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(false);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setDeleteRows(false);

                // Unlock data entry cells (including hidden helper columns)
                $sheet->getStyle("A{$startRow}:{$lastHelperCol}{$lastRow}")->getProtection()->setLocked(false);
            },
        ];
    }

    private function setDropdown(Worksheet $sheet, string $range, string $formula, string $title = 'Select Value', string $prompt = 'Choose from the available options.'): void
    {
        $sheet->setDataValidation($range, (new DataValidation)
            ->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(true)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setErrorTitle('Invalid Selection')
            ->setError('Please select a value from the dropdown list.')
            ->setPromptTitle($title === 'Select Value' ? $title : $title . ' Selection')
            ->setPrompt($prompt)
            ->setFormula1($formula));
    }
}
