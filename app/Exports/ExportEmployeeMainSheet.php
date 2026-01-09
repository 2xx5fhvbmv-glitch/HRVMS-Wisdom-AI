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

                // Add helper columns (hidden) for lookups
                $sheet->setCellValue('T1', 'DivisionID');
                $sheet->setCellValue('U1', 'DepartmentID');
                $sheet->setCellValue('V1', 'DivisionName');
                $sheet->setCellValue('W1', 'DepartmentName');
                $sheet->getColumnDimension('T')->setVisible(false);
                $sheet->getColumnDimension('U')->setVisible(false);
                $sheet->getColumnDimension('V')->setVisible(false);
                $sheet->getColumnDimension('W')->setVisible(false);

                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    // A: Division dropdown (shows name and code)
                    $this->setDropdown($sheet, "A{$row}", "=Divisions");

                    // V: Extract Division Name from display (helper column)
                    $divNameFormula = "=IF(A{$row}=\"\",\"\",A{$row})";
                    $sheet->setCellValue("V{$row}", $divNameFormula);

                    // T: Division ID lookup (hidden column)
                    $divLookup = "=IF(V{$row}=\"\",\"\",VLOOKUP(V{$row},DivisionMap_Name:DivisionMap_ID,2,FALSE))";
                    $sheet->setCellValue("T{$row}", $divLookup);

                    // B: Department dropdown based on Division ID (shows name and code)
                    $deptFormula = "=IF(T{$row}=\"\",\"\",INDIRECT(T{$row}&\"_depts\"))";
                    $this->setDropdown($sheet, "B{$row}", $deptFormula);

                    // W: Extract Department Name from display (helper column)
                    $deptNameFormula = "=IF(B{$row}=\"\",\"\",B{$row})";
                    $sheet->setCellValue("W{$row}", $deptNameFormula);

                    // U: Department ID lookup (hidden column)
                    $deptLookup = "=IF(W{$row}=\"\",\"\",VLOOKUP(W{$row},DepartmentMap_Name:DepartmentMap_ID,2,FALSE))";
                    $sheet->setCellValue("U{$row}", $deptLookup);

                    // C: Section dropdown based on Department ID (shows name and code)
                    $sectionFormula = "=IF(U{$row}=\"\",\"\",IF(ISERROR(INDIRECT(U{$row}&\"_sections\")),\"\",INDIRECT(U{$row}&\"_sections\")))";
                    $this->setDropdown($sheet, "C{$row}", $sectionFormula);

                    // D: Position dropdown based on Department ID (shows name and code)
                    $positionFormula = "=IF(U{$row}=\"\",\"\",IF(ISERROR(INDIRECT(U{$row}&\"_positions\")),\"\",INDIRECT(U{$row}&\"_positions\")))";
                    $this->setDropdown($sheet, "D{$row}", $positionFormula);

                    // I: Gender dropdown
                    $this->setDropdown($sheet, "I{$row}", "=Genders");

                    // Q: Nationality dropdown
                    $this->setDropdown($sheet, "Q{$row}", "=Nationalities");
                }

                // Set fixed width for Division, Department, Position, Section columns to accommodate codes
                $sheet->getColumnDimension('A')->setWidth(25); // Division (name + code)
                $sheet->getColumnDimension('B')->setWidth(25); // Department (name + code)
                $sheet->getColumnDimension('C')->setWidth(25); // Section (name + code)
                $sheet->getColumnDimension('D')->setWidth(25); // Position (name + code)

                // Auto-size remaining visible columns (excluding the fixed-width columns)
                foreach (range('E', 'S') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                // Protect the sheet but allow selecting unlocked cells
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(false);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setDeleteRows(false);
                
                // Unlock data entry cells (including hidden helper columns)
                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    foreach (range('A', 'W') as $col) {
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