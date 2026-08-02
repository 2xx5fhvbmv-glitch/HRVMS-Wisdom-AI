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

                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    // A: Division dropdown (shows name and code)
                    $this->setDropdown($sheet, "A{$row}", "=Divisions");

                    // Extract Division Name from display (helper column)
                    $divNameFormula = "=IF(A{$row}=\"\",\"\",A{$row})";
                    $sheet->setCellValue("{$colDivName}{$row}", $divNameFormula);

                    // Division ID lookup (hidden column)
                    $divLookup = "=IF({$colDivName}{$row}=\"\",\"\",VLOOKUP({$colDivName}{$row},DivisionMap_Name:DivisionMap_ID,2,FALSE))";
                    $sheet->setCellValue("{$colDivId}{$row}", $divLookup);

                    // B: Department dropdown based on Division ID (shows name and code)
                    $deptFormula = "=IF({$colDivId}{$row}=\"\",\"\",INDIRECT({$colDivId}{$row}&\"_depts\"))";
                    $this->setDropdown($sheet, "B{$row}", $deptFormula);

                    // Extract Department Name from display (helper column)
                    $deptNameFormula = "=IF(B{$row}=\"\",\"\",B{$row})";
                    $sheet->setCellValue("{$colDeptName}{$row}", $deptNameFormula);

                    // Department ID lookup (hidden column)
                    $deptLookup = "=IF({$colDeptName}{$row}=\"\",\"\",VLOOKUP({$colDeptName}{$row},DepartmentMap_Name:DepartmentMap_ID,2,FALSE))";
                    $sheet->setCellValue("{$colDeptId}{$row}", $deptLookup);

                    // C: Section dropdown based on Department ID (shows name and code)
                    $sectionFormula = "=IF({$colDeptId}{$row}=\"\",\"\",IF(ISERROR(INDIRECT({$colDeptId}{$row}&\"_sections\")),\"\",INDIRECT({$colDeptId}{$row}&\"_sections\")))";
                    $this->setDropdown($sheet, "C{$row}", $sectionFormula);

                    // D: Position dropdown based on Department ID (shows name and code)
                    $positionFormula = "=IF({$colDeptId}{$row}=\"\",\"\",IF(ISERROR(INDIRECT({$colDeptId}{$row}&\"_positions\")),\"\",INDIRECT({$colDeptId}{$row}&\"_positions\")))";
                    $this->setDropdown($sheet, "D{$row}", $positionFormula);

                    // I: Gender dropdown
                    $this->setDropdown($sheet, "I{$row}", "=Genders");

                    // Q: Nationality dropdown
                    $this->setDropdown($sheet, "Q{$row}", "=Nationalities");

                    // T: EmploymentType dropdown
                    $this->setDropdown($sheet, "T{$row}", "=EmploymentTypes");

                    // U: MaritalStatus dropdown
                    $this->setDropdown($sheet, "U{$row}", "=MaritalStatuses");

                    // V: BloodGroup dropdown
                    $this->setDropdown($sheet, "V{$row}", "=BloodGroups");
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
                $lastHelperColIndex = Coordinate::columnIndexFromString($lastHelperCol);
                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    for ($i = Coordinate::columnIndexFromString('A'); $i <= $lastHelperColIndex; $i++) {
                        $col = Coordinate::stringFromColumnIndex($i);
                        $sheet->getStyle("{$col}{$row}")->getProtection()->setLocked(false);
                    }
                }

                // Add data validation messages for better user experience
                $this->addValidationMessage($sheet, 'A', 'Division', 'Select a division from the dropdown');
                $this->addValidationMessage($sheet, 'B', 'Department', 'Select a department (filtered by division)');
                $this->addValidationMessage($sheet, 'C', 'Section', 'Select a section (filtered by department)');
                $this->addValidationMessage($sheet, 'D', 'Position', 'Select a position (filtered by department)');
            },
        ];
    }

    private function setDropdown(Worksheet $sheet, string $cell, string $formula): void
    {
        try {
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                ->setAllowBlank(true)
                ->setShowInputMessage(true)
                ->setShowErrorMessage(true)
                ->setShowDropDown(true)
                ->setErrorTitle('Invalid Selection')
                ->setError('Please select a value from the dropdown list.')
                ->setPromptTitle('Select Value')
                ->setPrompt('Choose from the available options.')
                ->setFormula1($formula);
        } catch (\Exception $e) {
            \Log::warning("Could not set dropdown for cell '{$cell}': " . $e->getMessage());
        }
    }

    private function addValidationMessage(Worksheet $sheet, string $column, string $fieldName, string $message): void
    {
        $dataRows = 250;
        $startRow = 2;
        
        for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
            $cell = $column . $row;
            try {
                $validation = $sheet->getCell($cell)->getDataValidation();
                $validation->setPromptTitle($fieldName . ' Selection')
                    ->setPrompt($message);
            } catch (\Exception $e) {
                \Log::warning("Could not set validation message for cell '{$cell}': " . $e->getMessage());
            }
        }
    }
}