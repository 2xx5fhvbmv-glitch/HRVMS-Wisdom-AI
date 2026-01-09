<?php

namespace App\Exports;

use App\Models\ResortDepartment;
use App\Models\ResortDivision;
use App\Models\ResortPosition;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Auth;
use App\Models\ResortBudgetCost;

class ConslidateBudgetData  implements FromCollection, WithHeadings, WithEvents
{
    protected $resortid;
    protected $counts;

public function collection()
{
 
    return $this->getActionItems(); // Directly return the action items
}


public function headings(): array
{

    $resort_id = Auth::guard('resort-admin')->user()->resort_id;
    $tableData = ResortBudgetCost::where('resort_id', $resort_id)->get()->pluck('particulars')->toArray();

    $Newfilds = [
                    'Division', // Added Division heading
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
public function styles(Worksheet $sheet)
{

    // Set header row styling
    $headerCells = 'A1:G'.$this->counts; // Adjust range based on your headers
    $sheet->getStyle($headerCells)->getFont()->setBold(true);
    $sheet->getStyle($headerCells)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet->getStyle($headerCells)->getFill()->getStartColor()->setARGB(Color::COLOR_RED); // Set header background color
    $sheet->getStyle($headerCells)->getFont()->getColor()->setARGB(Color::COLOR_WHITE); // Set header text color
}


public function registerEvents(): array
{
    $data = $this->getActionItems();
    $row_count = count($data) + 50;

    return [
        AfterSheet::class => function(AfterSheet $event) use ($row_count) {

            $divisionColumn = 'A';
            $departmentColumn = 'B';
            $positionColumn = 'C';
            $rankColumn = 'D';

            $divisions = ResortDivision::where('resort_id', $this->resortid)->where('slug',"!=",null)->pluck('slug')->toArray();
            $departments = ResortDepartment::where('resort_id', $this->resortid)->where('slug',"!=",null)->where('status', 'active')->pluck('slug')->toArray();
            $positions = ResortPosition::where('resort_id', $this->resortid)->where('slug',"!=",null)->where('status', 'active')->pluck('slug')->toArray();
            $Rank = config('settings.Position_Rank');

            if (!empty($divisions)) {
                $divisionDropdown = sprintf('"%s"', implode(',', $divisions));
                for ($i = 2; $i <= $row_count; $i++) {
                    $event->sheet->getCell("{$divisionColumn}{$i}")->setValue('');

                    $divisionValidation = $event->sheet->getCell("{$divisionColumn}{$i}")->getDataValidation();
                    $divisionValidation->setType(DataValidation::TYPE_LIST);
                    $divisionValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $divisionValidation->setShowDropDown(true);
                    $divisionValidation->setFormula1($divisionDropdown);
                    $divisionValidation->setErrorTitle('Input error');
                    $divisionValidation->setError('Value is not in the list.');
                    $divisionValidation->setPromptTitle('Select Division');
                    $divisionValidation->setPrompt('Please pick a value from the drop-down list.');

                    $event->sheet->getCell("{$divisionColumn}{$i}")->setDataValidation(clone $divisionValidation);
                }
            }



            if (!empty($departments)) {
                $departmentDropdown = sprintf('"%s"', implode(',', $departments));

                for ($i = 2; $i <= $row_count; $i++) {
                    $event->sheet->getCell("{$departmentColumn}{$i}")->setValue('');

                    $departmentValidation = $event->sheet->getCell("{$departmentColumn}{$i}")->getDataValidation();
                    $departmentValidation->setType(DataValidation::TYPE_LIST);
                    $departmentValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $departmentValidation->setShowDropDown(true);
                    $departmentValidation->setFormula1($departmentDropdown);
                    $departmentValidation->setErrorTitle('Input error');
                    $departmentValidation->setError('Value is not in the list.');
                    $departmentValidation->setPromptTitle('Select Department');
                    $departmentValidation->setPrompt('Please pick a value from the drop-down list.');

                    $event->sheet->getCell("{$departmentColumn}{$i}")->setDataValidation(clone $departmentValidation);
                }
            }

            if (!empty($positions)) {
                $positionDropdown = sprintf('"%s"', implode(',', $positions));

                for ($i = 2; $i <= $row_count; $i++) {
                    $event->sheet->getCell("{$positionColumn}{$i}")->setValue('');

                    $positionValidation = $event->sheet->getCell("{$positionColumn}{$i}")->getDataValidation();
                    $positionValidation->setType(DataValidation::TYPE_LIST);
                    $positionValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $positionValidation->setShowDropDown(true);
                    $positionValidation->setFormula1($positionDropdown);
                    $positionValidation->setErrorTitle('Input error');
                    $positionValidation->setError('Value is not in the list.');
                    $positionValidation->setPromptTitle('Select Position');
                    $positionValidation->setPrompt('Please pick a value from the drop-down list.');

                    $event->sheet->getCell("{$positionColumn}{$i}")->setDataValidation(clone $positionValidation);
                }
            }
            if (!empty($Rank)) {
                $RankDropdown = sprintf('"%s"', implode(',', $Rank));

                for ($i = 2; $i <= $row_count; $i++) {
                    $event->sheet->getCell("{$rankColumn}{$i}")->setValue('');

                    $rankValidation = $event->sheet->getCell("{$rankColumn}{$i}")->getDataValidation();
                    $rankValidation->setType(DataValidation::TYPE_LIST);
                    $rankValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $rankValidation->setShowDropDown(true);
                    $rankValidation->setFormula1($RankDropdown);
                    $rankValidation->setErrorTitle('Input error');
                    $rankValidation->setError('Value is not in the list.');
                    $rankValidation->setPromptTitle('Select Rank');
                    $rankValidation->setPrompt('Please pick a value from the drop-down list.');

                    $event->sheet->getCell("{$rankColumn}{$i}")->setDataValidation(clone $rankValidation);
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $column = Coordinate::stringFromColumnIndex($i);
                $event->sheet->getColumnDimension($column)->setAutoSize(true);
            }
        },
    ];
}

private function getActionItems()
{
    $this->resortid = Auth::guard('resort-admin')->user()->resort_id;

    $ResortDivision = ResortDivision::where('resort_id', $this->resortid)->get();

    $departments = ResortDepartment::where('resort_id', $this->resortid)->where('status', 'active')->get();

    // Initialize data array
    $data = [];

    foreach ($ResortDivision as $division) {
        $data[] =
        [
            'Division' => $division->slug,
            'Department' => '',
            'Position' => '',
              'Rank' => ''
        ];

        foreach ($departments as $d) {
            $data[] = [
                'Division' => '',
                'Department' => $d->slug,
                'Position' => '',
                'Rank' => ''
            ];
            foreach (config('settings.Position_Rank') as $p) {
                $data[] = [
                    'Division' => '',
                    'Department' => '',
                    'Position' => $p,
                    'Rank' => ''
                ];
            }
        }
    }

    return collect($data);
}
}
