<?php

namespace App\Imports;

use DB;
use Auth;
use App\Helpers\Common;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Employee;
use App\Models\InventoryModule;
use App\Models\BuildingModel;
use App\Models\AccommodationType;
use App\Models\AssingAccommodation;
use App\Models\AvailableAccommodationModel;
use App\Models\AvailableAccommodationInvItem;
class ImportQuickAssignment implements  ToModel, WithHeadingRow
{
    protected $resort;
    public $existingRows = []; // To store messages for existing rows

    public $errorMessages=[];
    public $successMessages=[];
    public function __construct()
    {
        $this->resort= Auth::guard('resort-admin')->user();
    }

    // public function model(array $row)
    // {
    //     $buildingname = $row['buildingname'] ?? null;
    //     $emp1= explode('/',$row['employee']);
    //     $employee = explode("(",$emp1[1]);
    //     $emp_id = str_replace(' ', '', $employee[0]);

    //     $roomsAndB= explode('/',$buildingname);
    //     $floor = explode("-",$roomsAndB[1]);
    //     $bedno = $row['bedno'];
    //     $Newbedno= explode('/',$bedno);
    //     $BedNo =   str_replace(' ', '', $Newbedno[3]);
    //     $floorNo= str_replace(' ', '', $floor[1]);

    //     $rooms =explode("-",$roomsAndB[2]);
    //     $RoomNo = explode("(",$rooms[1]);
    //     $RoomNo = str_replace(' ', '', $RoomNo[0]);
    //     $buildingname = $roomsAndB[0];

    //     $BuildingModel = BuildingModel::where('BuildingName', $buildingname)->first();
    //     $employee = Employee::where('Emp_id', $emp_id)->first();
    //     DB::beginTransaction();
    //     try {
    //             $AvailableAccommodationModel = AvailableAccommodationModel::where('BuildingName', $BuildingModel->id)
    //                                         ->where('Floor', $floorNo)
    //                                         ->where('RoomNo', $RoomNo)
    //                                         ->where('resort_id', $this->resort->resort_id)
    //                                         ->where('roomstatus', 'Available')
    //                                         ->first();
    //             $AvailableAccommodationModel = $AvailableAccommodationModel->id;

    //             $AssingAccommodation = AssingAccommodation::where('resort_id',$this->resort->resort_id)
    //                 ->where('available_a_id', $AvailableAccommodationModel)
    //                 ->where('emp_id', $employee->id)
    //                 ->where("BedNo", $BedNo)
    //                 ->exists();

    //             if(!$AssingAccommodation)
    //             {
    //         AssingAccommodation::where('resort_id',$this->resort->resort_id)
    //                                     ->where('available_a_id', $AvailableAccommodationModel)
    //                                     ->where("BedNo", $BedNo)
    //                                     ->update(['emp_id' => $employee->id,'effected_date'=>date('Y-m-d')]);
    //                 $this->successMessages[] = "The Room {$RoomNo}, Bed No {$BedNo} has been successfully assigned to Employee .";

    //             }
    //             else
    //             {

    //                 $this->errorMessages[] = "Error: Unable to assign Room {$RoomNo}, Bed No {$BedNo}. Please try again.";

    //             }
    //             DB::commit();
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             \Log::emergency("File: " . $e->getFile());
    //             \Log::emergency("Line: " . $e->getLine());
    //             \Log::emergency("Message: " . $e->getMessage());
    //             $this->errorMessages[] = "Error: Unable to assign Room {$RoomNo}, Bed No {$BedNo}. Please try again.";

    //         }


    // }
    public function model(array $row)
    {
        $buildingname = isset($row['buildingname']) ? $row['buildingname'] : null;
        $bedno = isset($row['bedno']) ? $row['bedno'] : null;

        if (empty($buildingname) || empty($bedno)) {
            // $this->errorMessages[] = "Missing data for building or bed number.";
            return;
        }

        $emp1 = isset($row['employee']) ? explode('/', $row['employee']) : [];
        $employee = isset($emp1[1]) ? explode("(", $emp1[1]) : [];
        $emp_id = isset($employee[0]) ? str_replace(' ', '', $employee[0]) : null;

        if (empty($emp_id)) {
            $this->errorMessages[] = "Missing employee ID.";
            return;
        }

        $roomsAndB = explode('/', $buildingname);
        $floor = array_key_exists(1, $roomsAndB) ? explode("-", $roomsAndB[1]) : '';
        $floorNo = isset($floor[1]) ? str_replace(' ', '', $floor[1]) : null;

        $Newbedno = explode('/', $bedno);
        $BedNo = isset($Newbedno[3]) ? str_replace(' ', '', $Newbedno[3]) : null;

        $RoomNo = isset($roomsAndB[2]) ? explode("-", $roomsAndB[2])[1] : null;
        $RoomNo = isset($RoomNo) ? explode("(", $RoomNo)[0] : null;
        $RoomNo = str_replace(' ', '', $RoomNo);

        // Log building name and room info
        \Log::info('Building Name: ' . $roomsAndB[0]);

        // Find building
        $BuildingModel = BuildingModel::where('BuildingName', $roomsAndB[0])->first();
        if (!$BuildingModel) {
            $this->errorMessages[] = "Building {$roomsAndB[0]} not found.";
            return;
        }

        // Find employee
        $employee = Employee::where('Emp_id', $emp_id)->first();
        if (!$employee) {
            $this->errorMessages[] = "Employee with ID {$emp_id} not found.";
            return;
        }

        // Check available accommodation
        $AvailableAccommodationModel = AvailableAccommodationModel::where('BuildingName', $BuildingModel->id)
            ->where('Floor', $floorNo)
            ->where('RoomNo', $RoomNo)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$AvailableAccommodationModel) {
            $this->errorMessages[] = "Available accommodation not found for Room {$RoomNo},  {$BedNo}.";
            return;
        }

        // Check if accommodation already assigned
        $AssingAccommodation = AssingAccommodation::where('resort_id', $this->resort->resort_id)
            ->where('available_a_id', $AvailableAccommodationModel->id)
            ->where('emp_id', $employee->id)
            ->where("BedNo", $BedNo)
            ->exists();

        if (!$AssingAccommodation) {
            AssingAccommodation::where('resort_id', $this->resort->resort_id)
                ->where('available_a_id', $AvailableAccommodationModel->id)
                ->where("BedNo", $BedNo)
                ->update(['emp_id' => $employee->id, 'effected_date' => date('Y-m-d')]);

            $this->successMessages[] = "The Room {$RoomNo}, Bed No {$BedNo} has been successfully assigned to Employee.";
        } else {
            $this->errorMessages[] = "Unable to assign Room {$RoomNo}, Bed No {$BedNo}. Already assigned.";
        }
    }

}
