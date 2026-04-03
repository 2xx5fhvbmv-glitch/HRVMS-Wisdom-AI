<?php

namespace App\Imports;

use DB;
use Auth;
use App\Helpers\Common;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Employee;
use App\Models\BuildingModel;
use App\Models\AssingAccommodation;
use App\Models\AvailableAccommodationModel;

class ImportQuickAssignment implements ToModel, WithHeadingRow
{
    protected $resort;
    public $errorMessages = [];
    public $successMessages = [];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {
        // Headings: Employee, Building, Floor, Room, Bed No
        $employeeRaw = trim($row['employee'] ?? '');
        $buildingName = trim($row['building'] ?? '');
        $floor = trim($row['floor'] ?? '');
        $roomNo = trim($row['room'] ?? '');
        $bedNo = trim($row['bed_no'] ?? '');

        if (empty($employeeRaw) || empty($buildingName) || empty($floor) || empty($roomNo) || empty($bedNo)) {
            return null; // Skip empty rows
        }

        // Parse employee ID from "Name / EmpID"
        $empParts = explode('/', $employeeRaw);
        $empId = isset($empParts[1]) ? trim($empParts[1]) : null;

        if (empty($empId)) {
            $this->errorMessages[] = "Invalid employee format: {$employeeRaw}. Expected 'Name / EmpID'.";
            return null;
        }

        // Find building
        $building = BuildingModel::where('BuildingName', $buildingName)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$building) {
            $this->errorMessages[] = "Building '{$buildingName}' not found.";
            return null;
        }

        // Find employee
        $employee = Employee::where('Emp_id', $empId)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$employee) {
            $this->errorMessages[] = "Employee '{$empId}' not found.";
            return null;
        }

        // Find available accommodation
        $accommodation = AvailableAccommodationModel::where('BuildingName', $building->id)
            ->where('Floor', $floor)
            ->where('RoomNo', $roomNo)
            ->where('resort_id', $this->resort->resort_id)
            ->first();

        if (!$accommodation) {
            $this->errorMessages[] = "No accommodation found for Building '{$buildingName}', Floor {$floor}, Room {$roomNo}.";
            return null;
        }

        // Check if bed already assigned to someone
        $existingAssignment = AssingAccommodation::where('resort_id', $this->resort->resort_id)
            ->where('available_a_id', $accommodation->id)
            ->where('BedNo', $bedNo)
            ->first();

        if (!$existingAssignment) {
            $this->errorMessages[] = "Bed '{$bedNo}' not found in Room {$roomNo}, Floor {$floor}, Building '{$buildingName}'.";
            return null;
        }

        if ($existingAssignment->emp_id && $existingAssignment->emp_id != 0) {
            $existingEmp = Employee::find($existingAssignment->emp_id);
            $existingName = $existingEmp ? $existingEmp->Emp_id : 'Unknown';
            $this->errorMessages[] = "Bed '{$bedNo}' in Room {$roomNo} is already assigned to {$existingName}.";
            return null;
        }

        // Assign employee to bed
        $existingAssignment->update([
            'emp_id' => $employee->id,
            'effected_date' => date('Y-m-d')
        ]);

        $this->successMessages[] = "Employee {$empId} assigned to Building '{$buildingName}', Floor {$floor}, Room {$roomNo}, Bed {$bedNo}.";
        return null;
    }
}
