<?php

namespace App\Imports;
use DB;
use Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\InventoryModule;
use App\Models\BuildingModel;
use App\Models\AccommodationType;
use App\Models\AssingAccommodation;
use App\Models\AvailableAccommodationModel;
use App\Models\AvailableAccommodationInvItem;
class ImportAvailableAccommodation implements  ToModel, WithHeadingRow
{

    protected $resort;
    public $existingRows = []; // To store messages for existing rows

    public function __construct()
    {
        $this->resort= Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {
        $buildingname = $row['buildingname'] ?? null;
        $floor = $row['floor'] ?? null;
        $room = $row['room'] ?? null;
        $typeofaccommodation = $row['typeofaccommodation'] ?? null;
        $capacity = $row['capacity'] ?? null;
        $roomtype = $row['roomtype'] ?? null;
        $bedno = $row['bedno'] ?? null;
        $blockfor = $row['blockfor'] ?? null;
        $roomstatus = $row['roomstatus'] ?? null;
        $cleaningshedule = $row['cleaningshedule'] ?? null;
        $itemname = $row['itemname'] ?? null;
        $occupancytheresold = $row['occupancytheresold'] ?? null;

        // Split values safely to avoid undefined indexes
        $item = explode("/", $itemname);
        $floorParts = explode('-', $floor);
        $roomParts = explode('/', $room);

        $roomSubParts = isset($roomParts[2]) ? explode('-', $roomParts[2]) : [];

        // Assign values safely with default empty string if not found
        $itemname = $item[0] ?? '';
        $ItemCode = $item[1] ?? '';
        $floor = isset($floorParts[1]) ? str_replace(' ', '', $floorParts[1]) : '';
        $room = isset($roomSubParts[1]) ? str_replace(' ', '', $roomSubParts[1]) : '';

        // Get Room Type from config
        $roomtypeConfig = config('settings.eligibilty');
        $RoomType = array_search($roomtype , $roomtypeConfig) ?  array_search($roomtype , $roomtypeConfig) : '';
        $buildingModel = BuildingModel::where('BuildingName', $buildingname)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        $buildingId = optional($buildingModel)->id;

        $inventoryModel = InventoryModule::where('ItemName', $itemname)
            ->where('ItemCode', $ItemCode)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        $item_id = optional($inventoryModel)->id;

        $accommodationTypeModel = AccommodationType::where('AccommodationName', $typeofaccommodation)
            ->where('resort_id', $this->resort->resort_id)
            ->first();
        $AccommodationType = optional($accommodationTypeModel)->id;

        // Validate if accommodation already exists
        $exists = DB::table('available_accommodation_models')
            ->where('BuildingName', $buildingId)
            ->where('Floor', $floor)
            ->where('RoomNo', $room)
            ->where('resort_id', $this->resort->resort_id)
            ->exists();

        if (!$exists) {
            DB::beginTransaction();
            try {
                $parent = AvailableAccommodationModel::create([
                    "BuildingName" => $buildingId,
                    "Floor" => $floor,
                    "RoomNo" => $room,
                    "Accommodation_type_id" => $AccommodationType,
                    "Capacity" => $capacity,
                    "RoomType" => $RoomType,
                    "BedNo" => $bedno,
                    "blockFor" => $blockfor,
                    "CleaningSchedule" => $cleaningshedule,
                    "RoomStatus" => $roomstatus,
                    "Occupancytheresold" => $occupancytheresold,
                    "resort_id" => $this->resort->resort_id,
                ]);

                if (isset($parent->id)) {
                    AvailableAccommodationInvItem::create([
                        'Available_Acc_id' => $parent->id,
                        'Item_id' => $item_id,
                    ]);

                    for ($i = 0; $i < $capacity; $i++) {
                        AssingAccommodation::create([
                            "resort_id" => $this->resort->resort_id,
                            'available_a_id' => $parent->id,
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
            }
        } else {
            // Store existing record messages for later response
            $this->existingRows[] = "The RoomNo {$room} already exists in Building {$buildingname}, Floor {$floor} for this resort.";
        }
    }

}

