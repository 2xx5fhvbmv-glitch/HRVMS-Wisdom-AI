<?php

namespace App\Exports;
use DB;
use Auth;
use App\Models\InventoryModule;
use App\Models\AccommodationType;
use App\Models\BulidngAndFloorAndRoom;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Enumerable;

class AvailableAccommodationExport implements FromCollection, WithHeadings, WithEvents
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function headings(): array
    {
        return ['BuildingName', 'Floor','Room','TypeOfAccommodation', 'Capacity', 'RoomType','BedNo','BlockFor','ItemName','CleaningShedule','RoomStatus','Occupancytheresold'];
    }

    public function collection(): Enumerable
    {
        return collect([]); // Empty collection for the Excel template
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $spreadsheet = $worksheet->getParent();
                $resortId = $this->resort->resort_id;

                // Every building / floor / room of this resort (one row per room)
                $rooms = BulidngAndFloorAndRoom::join('building_models as t1', 't1.id', '=', 'bulidng_and_floor_and_rooms.building_id')
                    ->where('t1.resort_id', $resortId)
                    ->orderBy('t1.BuildingName')
                    ->get(['t1.BuildingName', 'bulidng_and_floor_and_rooms.Floor', 'bulidng_and_floor_and_rooms.Room']);

                $buildingNames = $rooms->pluck('BuildingName')->unique()->values()->toArray();
                $floors = $rooms->map(fn($i) => $i->BuildingName . ' - ' . $i->Floor)->unique()->values()->toArray();
                $roomList = $rooms->map(fn($i) => $i->BuildingName . ' / Floor - ' . $i->Floor . ' / Room - ' . $i->Room)->unique()->values()->toArray();
                $types = AccommodationType::where('resort_id', $resortId)->orderBy('id', 'DESC')->pluck('AccommodationName')->toArray();
                $items = InventoryModule::where('resort_id', $resortId)->get()->map(fn($j) => $j->ItemName . '/' . $j->ItemCode)->toArray();

                // Lists live on a hidden sheet: an inline "a,b,c" list is capped at 255 chars by Excel
                $hiddenSheet = $spreadsheet->createSheet();
                $hiddenSheet->setTitle('DropdownData');

                // hidden-sheet column => [named range, values, template column it feeds]
                $lists = [
                    'A' => ['AccBuildingList', $buildingNames, 'A'],
                    'B' => ['AccFloorList', $floors, 'B'],
                    'C' => ['AccRoomList', $roomList, 'C'],
                    'D' => ['AccTypeList', $types, 'D'],
                    'E' => ['AccRoomTypeList', array_values(config('settings.eligibilty')), 'F'],
                    'F' => ['AccBlockForList', ['Male', 'Female'], 'H'],
                    'G' => ['AccItemList', $items, 'I'],
                    'H' => ['AccCleaningList', config('settings.CleaningSchedule'), 'J'],
                    'I' => ['AccStatusList', array_values(array_unique(config('settings.RoomStatus'))), 'K'],
                ];

                $lastRow = 500;
                foreach ($lists as $hCol => [$name, $values, $col]) {
                    if (count($values) === 0) {
                        continue; // e.g. a resort with no inventory items yet: no dropdown, same as an empty list today
                    }
                    foreach (array_values($values) as $i => $val) {
                        $hiddenSheet->setCellValueExplicit($hCol . ($i + 1), (string) $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange($name, $hiddenSheet, '$' . $hCol . '$1:$' . $hCol . '$' . count($values)));

                    // One validation per column range (rows 2..$lastRow), not one object per cell: per-cell
                    // validations for every row ran out of PHP's default 128 MB while writing the file.
                    $worksheet->setDataValidation("{$col}2:{$col}{$lastRow}", (new \PhpOffice\PhpSpreadsheet\Cell\DataValidation)
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('=' . $name));
                }

                $worksheet->setDataValidation("E2:E{$lastRow}", (new \PhpOffice\PhpSpreadsheet\Cell\DataValidation)
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE) // Whole numbers only
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP)
                        ->setAllowBlank(false)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setErrorTitle('Invalid Input')
                        ->setError('Only numeric values are allowed for Capacity.')
                        ->setPromptTitle('Capacity')
                        ->setPrompt('Enter a numeric value (whole number).')
                        ->setFormula1(0) // Minimum value, optional
                        ->setFormula2(9999)); // Maximum value, optional

                $worksheet->setDataValidation("L2:L{$lastRow}", (new \PhpOffice\PhpSpreadsheet\Cell\DataValidation)
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DECIMAL) // Decimal numbers
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP)
                        ->setAllowBlank(false)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setErrorTitle('Invalid Input')
                        ->setError('Only decimal values are allowed for Bed No.')
                        ->setPromptTitle('Occupancy thresold No.')
                        ->setPrompt('Enter a decimal value.')
                        ->setFormula1(0.0) // Minimum value
                        ->setFormula2(9999.9999)); // Maximum value

                foreach (['A' => 25, 'B' => 25, 'C' => 40, 'D' => 20, 'F' => 18, 'H' => 15, 'I' => 25, 'J' => 20, 'K' => 22] as $c => $w) {
                    $worksheet->getColumnDimension($c)->setWidth($w);
                }

                $hiddenSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
