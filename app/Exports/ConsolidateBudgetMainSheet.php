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
use Auth;
use App\Models\ResortBudgetCost;


class ConsolidateBudgetMainSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(protected int $resortId)
    {
    }

    public function title(): string
    {
        return 'ConsolidatedBudgetTemplate';
    }

    public function array(): array
    {
        // Creates 300 empty rows for data entry
        return array_fill(0, 300, array_fill(0, count($this->headings()), ''));
    }

    public function headings(): array
    {
        
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $tableData = ResortBudgetCost::where('resort_id', $resort_id)->get()->pluck('particulars')->toArray();

        $Newfilds = [
                    'Division',
                    'Department', 
                    'Position',
                    'Rank',
                    'NATION',
                    'NoOfPosition',
                    'Current Salary',
                    'Proposed Salary',
                ];

        $this->counts  =  count(array_merge($Newfilds, $tableData));

        return array_merge($Newfilds, $tableData);
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

                $dataRows = 300;
                $startRow = 2;

                // Add helper columns (hidden) for lookups
                $sheet->setCellValue('J1', 'DivisionID');
                $sheet->setCellValue('K1', 'DepartmentID');
                $sheet->setCellValue('L1', 'DivisionName');
                $sheet->setCellValue('M1', 'DepartmentName');
                $sheet->getColumnDimension('J')->setVisible(false);
                $sheet->getColumnDimension('K')->setVisible(false);
                $sheet->getColumnDimension('L')->setVisible(false);
                $sheet->getColumnDimension('M')->setVisible(false);

                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    // A: Division dropdown (shows name and code)
                    $this->setDropdown($sheet, "A{$row}", "=Divisions");

                    // L: Extract Division Name from display (helper column)
                    $divNameFormula = "=IF(A{$row}=\"\",\"\",A{$row})";
                    $sheet->setCellValue("L{$row}", $divNameFormula);

                    // J: Division ID lookup (hidden column)
                    $divLookup = "=IF(L{$row}=\"\",\"\",VLOOKUP(L{$row},DivisionMap_Name:DivisionMap_ID,2,FALSE))";
                    $sheet->setCellValue("J{$row}", $divLookup);

                    // B: Department dropdown based on Division ID (shows name and code)
                    $deptFormula = "=IF(J{$row}=\"\",\"\",INDIRECT(J{$row}&\"_depts\"))";
                    $this->setDropdown($sheet, "B{$row}", $deptFormula);

                    // M: Extract Department Name from display (helper column)
                    $deptNameFormula = "=IF(B{$row}=\"\",\"\",B{$row})";
                    $sheet->setCellValue("M{$row}", $deptNameFormula);

                    // K: Department ID lookup (hidden column)
                    $deptLookup = "=IF(M{$row}=\"\",\"\",VLOOKUP(M{$row},DepartmentMap_Name:DepartmentMap_ID,2,FALSE))";
                    $sheet->setCellValue("K{$row}", $deptLookup);

                    // C: Position dropdown based on Department ID (shows name and code)
                    $positionFormula = "=IF(K{$row}=\"\",\"\",IF(ISERROR(INDIRECT(K{$row}&\"_positions\")),\"\",INDIRECT(K{$row}&\"_positions\")))";
                    $this->setDropdown($sheet, "C{$row}", $positionFormula);

                    // E: Nationality dropdown
                    $this->setDropdown($sheet, "E{$row}", "=Nationalities");
                }

                // Set fixed width for Division, Department, Position columns to accommodate codes
                $sheet->getColumnDimension('A')->setWidth(25); // Division (name + code)
                $sheet->getColumnDimension('B')->setWidth(25); // Department (name + code)
                $sheet->getColumnDimension('C')->setWidth(25); // Position (name + code)
                $sheet->getColumnDimension('D')->setWidth(15); // Rank
                $sheet->getColumnDimension('E')->setWidth(15); // NATION
                $sheet->getColumnDimension('F')->setWidth(15); // NoOfPosition
                $sheet->getColumnDimension('G')->setWidth(15); // Current Salary
                $sheet->getColumnDimension('H')->setWidth(15); // Proposed Salary

                // Protect the sheet but allow selecting unlocked cells
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(false);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setDeleteRows(false);
                
                // Unlock data entry cells (including hidden helper columns)
                for ($row = $startRow; $row <= $dataRows + $startRow - 1; $row++) {
                    foreach (range('A', 'M') as $col) {
                        $sheet->getStyle("{$col}{$row}")->getProtection()->setLocked(false);
                    }
                }

                // Add data validation messages for better user experience
                $this->addValidationMessage($sheet, 'A', 'Division', 'Select a division from the dropdown');
                $this->addValidationMessage($sheet, 'B', 'Department', 'Select a department (filtered by division)');
                $this->addValidationMessage($sheet, 'C', 'Position', 'Select a position (filtered by department)');
                $this->addValidationMessage($sheet, 'E', 'Nationality', 'Select a nationality from the dropdown');
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
        $dataRows = 300;
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