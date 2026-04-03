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
use App\Models\AvailableAccommodationModel;
use App\Models\Employee;
use App\Helpers\Common;

class QuickAssignmentTempleteExport implements FromCollection, WithHeadings, WithEvents
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function headings(): array
    {
        return ['Employee', 'Building', 'Floor', 'Room', 'Bed No'];
    }

    public function collection()
    {
        return collect([]);
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

                $resortId = $this->resort->resort_id;

                // Fetch Employee Names - format: "Name / EmpID"
                $employees = Employee::with('resortAdmin')
                    ->where('resort_id', $resortId)
                    ->where('status', 'Active')
                    ->get()
                    ->map(function ($e) {
                        return $e->resortAdmin->first_name . ' ' . $e->resortAdmin->last_name . ' / ' . $e->Emp_id;
                    })
                    ->values()
                    ->toArray();

                // Fetch Buildings
                $buildings = BuildingModel::where('resort_id', $resortId)
                    ->pluck('BuildingName')
                    ->toArray();

                // Fetch available beds (unassigned)
                $beds = AssingAccommodation::join('available_accommodation_models as aa', 'aa.id', '=', 'assing_accommodations.available_a_id')
                    ->join('building_models as b', 'b.id', '=', 'aa.BuildingName')
                    ->where('b.resort_id', $resortId)
                    ->where(function ($q) {
                        $q->whereNull('assing_accommodations.emp_id')
                          ->orWhere('assing_accommodations.emp_id', 0);
                    })
                    ->select('assing_accommodations.BedNo')
                    ->distinct()
                    ->pluck('BedNo')
                    ->toArray();

                // Fetch floors
                $floors = AvailableAccommodationModel::where('resort_id', $resortId)
                    ->select('Floor')
                    ->distinct()
                    ->pluck('Floor')
                    ->map(fn($f) => (string) $f)
                    ->toArray();

                // Fetch rooms
                $rooms = AvailableAccommodationModel::where('resort_id', $resortId)
                    ->select('RoomNo')
                    ->distinct()
                    ->pluck('RoomNo')
                    ->map(fn($r) => (string) $r)
                    ->toArray();

                // Write dropdown data to hidden sheet
                foreach ($employees as $i => $val) {
                    $hiddenSheet->setCellValue('A' . ($i + 1), $val);
                }
                foreach ($buildings as $i => $val) {
                    $hiddenSheet->setCellValue('B' . ($i + 1), $val);
                }
                foreach ($floors as $i => $val) {
                    $hiddenSheet->setCellValue('C' . ($i + 1), $val);
                }
                foreach ($rooms as $i => $val) {
                    $hiddenSheet->setCellValue('D' . ($i + 1), $val);
                }
                foreach ($beds as $i => $val) {
                    $hiddenSheet->setCellValue('E' . ($i + 1), $val);
                }

                // Create named ranges
                if (count($employees) > 0) {
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('EmployeeList', $hiddenSheet, 'A1:A' . count($employees)));
                }
                if (count($buildings) > 0) {
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('BuildingList', $hiddenSheet, 'B1:B' . count($buildings)));
                }
                if (count($floors) > 0) {
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('FloorList', $hiddenSheet, 'C1:C' . count($floors)));
                }
                if (count($rooms) > 0) {
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('RoomList', $hiddenSheet, 'D1:D' . count($rooms)));
                }
                if (count($beds) > 0) {
                    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('BedList', $hiddenSheet, 'E1:E' . count($beds)));
                }

                // Set column widths
                $worksheet->getColumnDimension('A')->setWidth(35);
                $worksheet->getColumnDimension('B')->setWidth(20);
                $worksheet->getColumnDimension('C')->setWidth(10);
                $worksheet->getColumnDimension('D')->setWidth(10);
                $worksheet->getColumnDimension('E')->setWidth(15);

                // Apply dropdowns for rows 2-100
                for ($row = 2; $row <= 100; $row++) {
                    // Employee dropdown
                    if (count($employees) > 0) {
                        $worksheet->getCell('A' . $row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setFormula1('=EmployeeList');
                    }

                    // Building dropdown
                    if (count($buildings) > 0) {
                        $worksheet->getCell('B' . $row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setFormula1('=BuildingList');
                    }

                    // Floor dropdown
                    if (count($floors) > 0) {
                        $worksheet->getCell('C' . $row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setFormula1('=FloorList');
                    }

                    // Room dropdown
                    if (count($rooms) > 0) {
                        $worksheet->getCell('D' . $row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setFormula1('=RoomList');
                    }

                    // Bed dropdown
                    if (count($beds) > 0) {
                        $worksheet->getCell('E' . $row)->getDataValidation()
                            ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                            ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION)
                            ->setAllowBlank(true)
                            ->setShowDropDown(true)
                            ->setFormula1('=BedList');
                    }
                }

                // Hide the dropdown data sheet
                $spreadsheet->setActiveSheetIndex(0);
                $hiddenSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            }
        ];
    }
}
