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

    public function collection()
    {
        return collect([]); // Empty collection for the Excel template
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();

                // Fetch buildings and floors using joins
                $data = DB::table('building_models')
                    ->join('bulidng_and_floor_and_rooms', 'building_models.id', '=', 'bulidng_and_floor_and_rooms.building_id')
                    ->where('building_models.resort_id', $this->resort->resort_id)
                    ->select('building_models.BuildingName', 'bulidng_and_floor_and_rooms.Floor')
                    ->groupBy('building_models.BuildingName', 'bulidng_and_floor_and_rooms.Floor')
                    ->get();

                // Prepare building names and floors
                $buildingNames = $data->pluck('BuildingName')->unique()->toArray();
                for ($row = 2; $row <=2; $row++)
                {

                        // Create dropdown for BuildingName
                        $buildingDropdown = implode(',', $buildingNames);
                        $worksheet->getCell('A'.$row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowDropDown(true)
                            ->setFormula1('"' . $buildingDropdown . '"');

                        $floors = BulidngAndFloorAndRoom::join('building_models as t1',"t1.id","=","bulidng_and_floor_and_rooms.building_id")
                                                            ->where('t1.resort_id', $this->resort->resort_id)
                                                            ->groupBy('bulidng_and_floor_and_rooms.building_id')
                                                            ->get(['t1.BuildingName', 'bulidng_and_floor_and_rooms.Floor','bulidng_and_floor_and_rooms.Room'])
                                                            ->map(function($i)
                                                            {
                                                                            $i->NewBuildingName  = $i->BuildingName.' - '.$i->Floor;
                                                                            $i->NewRooms = $i->BuildingName.' / Floor - '.$i->Floor.' / Room - '.$i->Room;
                                                                            return $i;
                                                            });
                        $Newfloors = $floors->pluck('NewBuildingName')->toArray();
                        $floorsDropdown = implode(',', $Newfloors);

                        $worksheet->getCell('B'.$row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowDropDown(true)
                            ->setFormula1('"' . $floorsDropdown . '"');
                        $RoomsAndFloor = $floors->pluck('NewRooms')->toArray();
                        $worksheet->getCell('C'.$row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowDropDown(true)
                            ->setFormula1('"' . implode(',', $RoomsAndFloor) . '"');
                        $worksheet->getColumnDimension('C')->setWidth(30); // Set the width for column C to 30


                        $AccommodationType= AccommodationType::where('resort_id',$this->resort->resort_id)
                        ->orderBy("id","DESC")
                        ->get()->pluck('AccommodationName')->toArray();
                        $worksheet->getCell('D'.$row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(false)
                            ->setShowDropDown(true)
                            ->setFormula1('"' . implode(',', $AccommodationType) . '"');
                        $worksheet->getColumnDimension('D')->setWidth(15); // Set the width for column C to 30


                        $ROOM = config('settings.eligibilty');

                        $worksheet->getCell('F'.$row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('"' . implode(',', $ROOM) . '"');
                        $worksheet->getColumnDimension('D')->setWidth(30); // Set the width for column C to 30

                        $BlockFor =['Male','Female'];


                        $worksheet->getCell('H'.$row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('"' . implode(',', $BlockFor) . '"');
                        $worksheet->getColumnDimension('H')->setWidth(20); // Set the width for column C to 30
                        $InventoryModule = InventoryModule::where('resort_id',$this->resort->resort_id)->get()->map(function($j){$j->item= $j->ItemName.'/'.$j->ItemCode;  return $j;})->pluck('item')->toArray();

                        $worksheet->getCell('I'.$row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('"' . implode(',', $InventoryModule) . '"');
                        $worksheet->getColumnDimension('I')->setWidth(20); // Set the width for column C to 30

                        $CleaningSchedule = config('settings.CleaningSchedule');
                        $worksheet->getCell('J'.$row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('"' . implode(',', $CleaningSchedule) . '"');
                        $worksheet->getColumnDimension('J')->setWidth(20); // Set the width for column C to 30

                        $worksheet->getCell('K'.$row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('"' . implode(',', config('settings.RoomStatus')) . '"');
                        $worksheet->getColumnDimension('K')->setWidth(20); // Set the width for column C to 30



                            $worksheet->getCell("E".$row)->getDataValidation()
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
                                ->setFormula2(9999); // Maximum value, optional

                                $worksheet->getCell("L".$row)->getDataValidation()
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
                                        ->setFormula2(9999.9999); // Maximum value
                }
            }


        ];
    }
}
