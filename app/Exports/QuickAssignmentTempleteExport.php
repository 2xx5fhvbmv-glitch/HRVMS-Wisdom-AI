<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use DB;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\AccommodationType;
use App\Models\BuildingModel;
use App\Models\AssingAccommodation;

use App\Models\Employee;
use App\Helpers\Common;
use App\Models\InventoryModule;class QuickAssignmentTempleteExport implements FromCollection, WithHeadings, WithEvents
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();

    }

    public function headings(): array
    {
        return ['Employee', 'BuildingName','BedNo'];
    }

    public function collection()
    {
        return collect([]); // Empty collection for the Excel template
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $spreadsheet = $event->sheet->getDelegate()->getParent();
                $worksheet = $event->sheet->getDelegate();

                // Add a hidden sheet for dropdown data
                $hiddenSheet = $spreadsheet->createSheet();
                $hiddenSheet->setTitle('DropdownData');

                // Fetch Employee Names
                $Employee = Employee::with('resortAdmin')
                    ->where('resort_id', $this->resort->resort_id)
                    ->get()
                    ->map(function ($e) {
                        $ranks = Common::getEmpGrade($e->rank);
                        $emp_grade = config('settings.eligibilty');
                        return $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name . ' / ' . $e->Emp_id . ' (' . $emp_grade[$ranks] . ')';
                    })
                    ->toArray();

                // Fetch Floors and Rooms
                $available_id = []; // Initialize array

                $floors = BulidngAndFloorAndRoom::join('building_models as t1', 't1.id', '=', 'bulidng_and_floor_and_rooms.building_id')
                    ->join('available_accommodation_models as t2', 't2.BuildingName', '=', 't1.id')
                    ->where('t1.resort_id', $this->resort->resort_id)
                    ->groupBy('t2.id')
                    ->get(['t1.BuildingName','t2.RoomNo','t2.Floor as AvailableFloor','t2.id as AvailableId', 't2.RoomType', 'bulidng_and_floor_and_rooms.Floor', 'bulidng_and_floor_and_rooms.Room'])
                    ->map(function ($i) use (&$available_id) {  // Pass by reference
                        $roomtypeConfig = config('settings.eligibilty');
                        $room_Type = $roomtypeConfig[$i->RoomType] ?? '';
                        $i->BuildingName = $i->BuildingName . ' / Floor - ' . $i->AvailableFloor . ' / Room - ' . $i->RoomNo . ' (' . $room_Type . ')';
                        if(!in_array($i->AvailableId, $available_id))
                        {
                            $available_id[] = $i->AvailableId;
                        }
                        return $i;
                    })
                    ->toArray();

                foreach ($Employee as $index => $value) {
                    $hiddenSheet->setCellValue('A' . ($index + 1), $value);
                }

                $AssingAccommodation  = AssingAccommodation::join('available_accommodation_models as t1','t1.id','=','assing_accommodations.available_a_id')
                                                            ->join('building_models as t2','t2.id','=','t1.BuildingName')
                                                            ->where('t2.resort_id',$this->resort->resort_id)
                                                            ->whereIn('assing_accommodations.available_a_id', $available_id)
                                                            ->get(['t2.BuildingName as Bname','t1.RoomType','t1.Floor','t1.RoomNo','t1.id as AvailableId','assing_accommodations.BedNo'])
                                                            ->map(function ($i) {
                                                                $roomtypeConfig = config('settings.eligibilty');
                                                                $room_Type = $roomtypeConfig[$i->RoomType] ?? '';
                                                                    $i->BuildingName = $i->Bname . ' / F - ' . $i->Floor . ' / R - ' . $i->RoomNo .' / '.$i->BedNo .' / '.$room_Type;

                                                                return $i;
                                                            })
                                                            ->pluck('BuildingName')->toArray();
                    // Convert bed numbers array to string with proper formatting
                    $BedNODropdown = array_map(function($bed) {
                        return strval($bed);
                    }, $AssingAccommodation);
                    $BedNODropdown = implode(',', $BedNODropdown);
                    $BuildingDropDown = array_map(function($building) {
                        return str_replace(',', ' ', $building['BuildingName']); // Remove commas that could break dropdown
                    }, $floors);
                $BuildingDropDown = implode(',', $BuildingDropDown);
                $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('EmployeeList', $hiddenSheet, 'A1:A' . count($Employee)));

                for ($row = 2; $row <= 100; $row++)
                {
                    // Employee Dropdown
                    $worksheet->getCell('A' . $row)->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                        ->setAllowBlank(false)
                        ->setShowDropDown(true)
                        ->setFormula1('=EmployeeList');

                    $worksheet->getColumnDimension('A')->setWidth(30);

                    // Building Dropdown
                    $worksheet->getCell('B'.$row)->getDataValidation()
                    ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('"' . $BuildingDropDown . '"');
                    $worksheet->getColumnDimension('B')->setWidth(30);

                    $worksheet->getCell('C'.$row)->getDataValidation()
                    ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('"' . $BedNODropdown . '"');
                    $worksheet->getColumnDimension('C')->setWidth(30);

                }

                // Hide the dropdown data sheet
                $spreadsheet->setActiveSheetIndex(0);
                $spreadsheet->getSheetByName('DropdownData')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            }
        ];
    }
}
