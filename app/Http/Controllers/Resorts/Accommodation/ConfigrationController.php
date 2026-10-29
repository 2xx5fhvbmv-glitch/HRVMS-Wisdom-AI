<?php

namespace App\Http\Controllers\Resorts\Accommodation;


use DB;
use Auth;
use Excel;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Models\BuildingModel;
use App\Models\EscalationDay;
use Illuminate\Validation\Rule;
use App\Models\InventoryModule;
use App\Models\AccommodationType;
use App\Models\AssingAccommodation;
use App\Http\Controllers\Controller;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\InventoryCategoryModel;
use App\Models\AvailableAccommodationModel;
use App\Models\AvailableAccommodationInvItem;
use App\Exports\AvailableAccommodationExport;
use App\Exports\QuickAssignmentTempleteExport;
use App\Models\OccupancyLevelsHitACriticalThreshold;
use App\Imports\ImportAvailableAccommodation;
use App\Imports\ImportQuickAssignment;
class ConfigrationController extends Controller
{

   protected $resort; 
   protected $globalUser;
    protected $underEmp_id=[];
    /** Room numbers are text (plan §2.1): G01, F1-2, A101 ... */
    private const ROOM_NO_RULES = ['required', 'max:15', 'regex:/^[A-Za-z0-9 .\/-]+$/'];

    /** Optional building fields (plan §1 rows 1 and 3, §1b): who it's for and how its rooms are numbered. */
    private function buildingFieldRules(): array
    {
        return [
            'gender' => ['nullable', 'in:M,F,X'],
            'room_numbering' => ['nullable', 'in:by_floor,continuous,custom'],
            // Custom pattern: {F} floor, {N} / {NN} counter on the floor, {C} building-wide counter; must contain a counter.
            'room_number_pattern' => ['nullable', 'required_if:room_numbering,custom', 'max:40',
                'regex:/^([A-Za-z0-9 .\/-]|\{(F|N|NN|C)\})+$/', 'regex:/\{(N|NN|C)\}/'],
        ];
    }

    public function __construct()
    {
        // AC-03: Accommodation settings (buildings/floors/rooms/beds/
        // accommodation types/inventory categories/thresholds/escalation
        // days/bulk import) are HR-only — no separate Accommodation-manager
        // role exists (product decision). Was completely ungated.
        $this->middleware('accommodation.hr');

        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id : null;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    public function index()
    {
        $page_title ="Configuration";
        $AccommodationType= AccommodationType::where('resort_id',$this->resort->resort_id)
                                            ->orderBy("id","DESC")
                                            ->get();
        $InventoryModule= InventoryModule::where('resort_id',$this->resort->resort_id)
                                            ->whereRaw('(Quantity - COALESCE(Occupied, 0)) > 0')
                                            ->orderBy("id","DESC")
                                            ->get();

        $BuildingData= BuildingModel::where('resort_id',$this->resort->resort_id)
                                        ->orderBy("id","DESC")
                                        ->get();
        // $BulidngAndFloorAndRoom = BulidngAndFloorAndRoom::where('resort_id',$this->resort->resort_id)
        //                                                 ->orderBy("id","DESC")
        //                                                 ->get();

        //                                                 dd($BulidngAndFloorAndRoom);
        $EscalationDay = EscalationDay::where('resort_id',$this->resort->resort_id)->first();

        return view('resorts.Accommodation.configration.index',compact('page_title','EscalationDay','AccommodationType','InventoryModule','BuildingData'));
    }

    // Start of invenotry Category
    public function InvenptoryCategoryStore(Request $request)
    {
        $CategoryName = $request->CategoryName;

        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'CategoryName' => [
                'required',
                'max:50',
                Rule::unique('inventory_category_models')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                }),
            ],
        ], [
            'CategoryName.required' => 'The Category Name field is required. Please write something.',
            'CategoryName.unique' => 'The Category Name already exists for this resort.',
            'CategoryName.max' => 'The maximum allowed length for the Category Name is 50 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
        $created = InventoryCategoryModel::create(["resort_id"=>$this->resort->resort_id,"CategoryName"=>ucfirst($CategoryName)]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Added Successfully',
                'id' => $created->id,
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to add  Category'], 500);
        }
    }
    public function InvenotryCatIndex(Request $request)
    {

        if($request->ajax())
        {
            $InventoryCategoryModel= InventoryCategoryModel::where('resort_id',$this->resort->resort_id)->get();

            return datatables()->of($InventoryCategoryModel)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);

                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>';

            })


            ->rawColumns(['action'])
            ->make(true);
        }

         $page_title ="Invenotry category";
        return view('resorts.Accommodation.configration.InvenotryCatIndex',compact('page_title'));
    }
    public function CategoryUpdate(Request $request)
    {
        $Catid = base64_decode($request->CatId);

        $CategoryName = $request->CategoryName;

        $resort_id = $this->resort->resort_id;
        $Catid = base64_decode($request->CatId);

        $validator = Validator::make($request->all(), [
            'CategoryName' => [
                'required',
                'max:50',
                Rule::unique('inventory_category_models')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($Catid),
            ],
        ], [
            'CategoryName.required' => 'The Category Name field is required. Please write something.',
            'CategoryName.unique' => 'The Category Name already exists for this resort.',
            'CategoryName.max' => 'The maximum allowed length for the Category Name is 50 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            InventoryCategoryModel::where("id",$Catid)->where('resort_id', $resort_id)->update(["CategoryName"=>ucfirst($CategoryName)]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Category'], 500);
        }

    }

    public function Catdestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
        InventoryCategoryModel::where("id",$id)->where('resort_id', $this->resort->resort_id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Category'], 500);
        }
    }
    //end of  Invenotry category
    // Accommodation Start
    public function AccommodationTypeStore(Request $request)
    {
        $AccommodationName = $request->AccommodationName;
        // dd( $this->resort->);
        $validator = Validator::make($request->all(), [
            'AccommodationName' => 'required|unique:accommodation_types|max:50',
        ], [
            'AccommodationName.required' => 'The Accommodation Name field is required. Please write something.',
            'AccommodationName.unique' => 'The Accommodation Name already exists.',
            'AccommodationName.max' => 'The maximum allowed length for the Accommodation Name is 50 characters.',
        ]);

        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'AccommodationName' => [
                'required',
                'max:50',
                Rule::unique('accommodation_types')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                }),
            ],
        ], [
            'AccommodationName.required' => 'The accommodation Name field is required. Please write something.',
            'AccommodationName.unique' => 'The accommodation Name already exists for this resort.',
            'AccommodationName.max' => 'The maximum allowed length for the accommodation Name is 50 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }


        DB::beginTransaction();
        try
        {
            $created = AccommodationType::create(["resort_id"=>$this->resort->resort_id,"AccommodationName"=>ucfirst($AccommodationName),'Color'=>$request->Color ?? '#014653']);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Accommodation Added Successfully',
                'id' => $created->id,
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to add  Accommodation'], 500);
        }
    }


    public function AccommodationIndex(Request $request)
    {
        if($request->ajax())
        {

            $AccommodationType= AccommodationType::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($AccommodationType)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);

                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>';

            })


            ->rawColumns(['action'])
            ->make(true);
        }

        $page_title ="Accommodation Type List";
        return view('resorts.Accommodation.configration.AccommodationIndex',compact('page_title'));
    }

    public function AccommodationUpdate(Request $request)
    {
        $AccommodationName = $request->AccommodationName;

        $resort_id = $this->resort->resort_id;
        $Catid = base64_decode($request->CatId);

        $validator = Validator::make($request->all(), [
            'AccommodationName' => [
                'required',
                'max:50',
                Rule::unique('accommodation_types')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($Catid),
            ],
        ], [
            'AccommodationName.required' => 'The Accommodation Name field is required. Please write something.',
            'AccommodationName.unique' => 'The Accommodation Name already exists for this resort.',
            'AccommodationName.max' => 'The maximum allowed length for the Accommodation Name is 50 characters.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            AccommodationType::where("id",$Catid)->where('resort_id', $resort_id)->update(["AccommodationName"=>ucfirst($AccommodationName),'Color'=>$request->Color]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Accommodation Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Accommodation  Category'], 500);
        }

    }

    public function Accommodationdestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            AccommodationType::where("id",$id)->where('resort_id', $this->resort->resort_id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Category'], 500);
        }
    }


    public function AvailableAccommodationIndex(Request $request)
    {
        $page_title = 'Listing Accommodation';
        $resort_id = $this->resort->resort_id;

        if ($request->ajax()) {
            // Beds and occupied beds for every room in one grouped query (was two count queries per room).
            $bedCounts = \App\Models\AssingAccommodation::where('resort_id', $resort_id)
                ->selectRaw('available_a_id, count(*) beds, sum(emp_id is not null and emp_id <> 0) occupied')
                ->groupBy('available_a_id')->get()->keyBy('available_a_id');
            $data = AvailableAccommodationModel::join('building_models as b', 'b.id', '=', 'available_accommodation_models.BuildingName')
                ->leftJoin('accommodation_types as at', 'at.id', '=', 'available_accommodation_models.Accommodation_type_id')
                ->where('available_accommodation_models.resort_id', $resort_id)
                ->select(
                    'available_accommodation_models.id',
                    'b.BuildingName',
                    'available_accommodation_models.BuildingName as building_id', // match by id, not by name (names can repeat)
                    'available_accommodation_models.Floor',
                    'available_accommodation_models.RoomNo',
                    'available_accommodation_models.Capacity',
                    'available_accommodation_models.RoomType',
                    'available_accommodation_models.blockFor',
                    'available_accommodation_models.CleaningSchedule',
                    'available_accommodation_models.RoomStatus',
                    'available_accommodation_models.Occupancytheresold',
                    'available_accommodation_models.Accommodation_type_id',
                    'at.AccommodationName',
                    'available_accommodation_models.created_at'
                )
                ->orderBy('available_accommodation_models.id', 'DESC')
                ->get()
                ->map(function ($row) use ($bedCounts) {
                    $rankMap = config('settings.eligibilty');
                    $row->RoomTypeName = $rankMap[$row->RoomType] ?? 'Unknown';
                    $c = $bedCounts[$row->id] ?? null;
                    $row->Beds = (int) ($c->occupied ?? 0) . '/' . (int) ($c->beds ?? 0);
                    return $row;
                });

            return datatables()->of($data)
                ->editColumn('BuildingName', fn($row) => e($row->BuildingName))
                ->editColumn('Floor', fn($row) => e($row->Floor))
                ->editColumn('RoomNo', fn($row) => e($row->RoomNo))
                ->addColumn('action', function ($row) {
                    $id = $row->id;
                    return '<div class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-accommodation-btn" data-id="' . $id . '"
                                    data-building="' . e($row->BuildingName) . '"
                                    data-floor="' . e($row->Floor) . '"
                                    data-room="' . e($row->RoomNo) . '"
                                    data-capacity="' . e($row->Capacity) . '"
                                    data-roomtype="' . e($row->RoomType) . '"
                                    data-blockfor="' . e($row->blockFor) . '"
                                    data-cleaning="' . e($row->CleaningSchedule) . '"
                                    data-roomstatus="' . e($row->RoomStatus) . '"
                                    data-threshold="' . e($row->Occupancytheresold) . '"
                                    data-accommodationtype="' . e($row->Accommodation_type_id) . '">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-accommodation-btn" data-id="' . $id . '">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('resorts.Accommodation.configration.available-accommodation-list', compact('page_title'));
    }

    public function AvailableAccommodationDestroy($id)
    {
        $resort_id = $this->resort->resort_id;
        $accommodation = AvailableAccommodationModel::where('id', $id)->where('resort_id', $resort_id)->first();
        if (!$accommodation) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        DB::beginTransaction();
        try {
            $this->releaseRoomItems($id, $resort_id);
            \App\Models\AssingAccommodation::where('available_a_id', $id)->where('resort_id', $resort_id)->delete();
            $accommodation->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Accommodation deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    public function recalculateInventory()
    {
        $resort_id = $this->resort->resort_id;
        $items = InventoryModule::where('resort_id', $resort_id)->get();
        $updated = 0;

        foreach ($items as $item) {
            $totalOccupied = AvailableAccommodationInvItem::join('available_accommodation_models as a', 'a.id', '=', 'available_accommodation_inv_items.Available_Acc_id')
                ->where('a.resort_id', $resort_id)
                ->where('available_accommodation_inv_items.Item_id', $item->id)
                ->sum('available_accommodation_inv_items.quantity');

            if ($item->Occupied != $totalOccupied) {
                $item->Occupied = $totalOccupied;
                $item->save();
                $updated++;
            }
        }

        return response()->json(['success' => true, 'message' => "Recalculated {$updated} inventory items"]);
    }

    public function getBeds($id)
    {
        $beds = \App\Models\AssingAccommodation::where('available_a_id', $id)
            ->where('resort_id', $this->resort->resort_id)
            ->select('id', 'BedNo', 'emp_id')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'beds' => $beds]);
    }

    /**
     * A building with all its floors and rooms in one request and one transaction (plan §2.6): nothing is saved if any
     * room is invalid, so a half-built building can't happen. Returns the building id and each room's id.
     */
    public function StoreBuildingWithFloors(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $v = Validator::make($request->all(), [
            'BuildingName' => ['required', 'max:50', Rule::unique('building_models')->where(fn ($q) => $q->where('resort_id', $resort_id))],
            'floors' => ['required', 'array', 'min:1', 'max:60'],
            'floors.*.floor' => ['required', 'integer', 'min:0', 'max:200', 'distinct'],
            'floors.*.rooms' => ['required', 'array', 'min:1', 'max:60'],
            'floors.*.rooms.*' => [...self::ROOM_NO_RULES, 'distinct'],   // one number once per building
        ] + $this->buildingFieldRules(), [
            'BuildingName.unique' => 'The Building Name already exists for this resort.',
            'floors.*.rooms.*.distinct' => 'Room :input appears twice in this building.',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        return DB::transaction(function () use ($request, $resort_id) {
            $building = BuildingModel::create(['resort_id' => $resort_id, 'BuildingName' => $request->BuildingName]
                + array_filter($request->only(['gender', 'room_numbering', 'room_number_pattern']), fn ($x) => $x !== null));
            $rooms = [];
            foreach ($request->floors as $f) {
                foreach ($f['rooms'] as $no) {
                    $rooms[] = ['floor' => (int) $f['floor'], 'room' => (string) $no, 'id' => BulidngAndFloorAndRoom::create(
                        ['resort_id' => $resort_id, 'building_id' => $building->id, 'Floor' => (int) $f['floor'], 'Room' => (string) $no])->id];
                }
            }
            return response()->json(['success' => true, 'message' => 'Building added successfully', 'id' => $building->id, 'rooms' => $rooms]);
        });
    }

    /**
     * One setup applied to a list of rooms in one transaction (plan §2.6): creates the setup where a room has none and
     * updates it (beds, items) where it has one. All or nothing: rooms that fail come back in `failed` with the reason
     * and nothing is saved, so the screen can fix them and resend.
     */
    public function SetupRoomsBatch(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $v = Validator::make($request->all(), [
            'rooms' => ['required', 'array', 'min:1', 'max:200'],
            'rooms.*.building_id' => ['required', Rule::exists('building_models', 'id')->where('resort_id', $resort_id)],
            'rooms.*.floor' => ['required', 'integer', 'min:0'],
            'rooms.*.room' => self::ROOM_NO_RULES,
            'setup.Accommodation_type_id' => ['required', Rule::exists('accommodation_types', 'id')->where('resort_id', $resort_id)],
            'setup.Capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'setup.RoomType' => ['required', Rule::in(array_keys(config('settings.eligibilty')))],
            'setup.blockFor' => ['required', Rule::in(['Male', 'Female'])],
            'setup.CleaningSchedule' => ['required', Rule::in(config('settings.CleaningSchedule'))],
            'setup.RoomStatus' => ['nullable', Rule::in(config('settings.RoomStatus'))],
            'setup.Occupancytheresold' => ['required', 'integer', 'min:50', 'max:100'],
            'setup.items' => ['sometimes', 'array'],
            'setup.items.*' => ['integer', 'min:1', 'max:999'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        DB::beginTransaction();
        $done = $failed = [];
        foreach ($request->rooms as $i => $r) {
            $result = $this->saveRoomSetup($resort_id, $r, $request->setup);
            is_string($result) ? $failed[] = ['index' => $i, 'room' => $r['room'], 'message' => $result] : $done[] = ['index' => $i, 'id' => $result];
        }
        if ($failed) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => count($failed) . ' of ' . count($request->rooms) . ' rooms could not be set up; nothing was saved.', 'failed' => $failed], 422);
        }
        DB::commit();
        return response()->json(['success' => true, 'message' => 'Rooms set up successfully', 'rooms' => $done]);
    }

    /** Create or update one room's setup; returns its id, or the reason it can't be saved. */
    private function saveRoomSetup($resortId, array $r, array $setup)
    {
        $where = ['resort_id' => $resortId, 'building_id' => $r['building_id'], 'Floor' => $r['floor'], 'Room' => $r['room']];
        if (!BulidngAndFloorAndRoom::where($where)->exists()) {
            return "Room {$r['room']} is not on floor {$r['floor']} of this building.";
        }
        $fields = ['Accommodation_type_id' => $setup['Accommodation_type_id'], 'Capacity' => (int) $setup['Capacity'], 'RoomType' => $setup['RoomType'],
            'blockFor' => $setup['blockFor'], 'CleaningSchedule' => $setup['CleaningSchedule'], 'RoomStatus' => $setup['RoomStatus'] ?? 'Available',
            'Occupancytheresold' => (int) $setup['Occupancytheresold'], 'BedNo' => (int) $setup['Capacity']];
        $room = AvailableAccommodationModel::where('resort_id', $resortId)->where('BuildingName', $r['building_id'])
            ->where('Floor', $r['floor'])->where('RoomNo', $r['room'])->first();

        if (!$room) {
            $room = AvailableAccommodationModel::create($fields + ['resort_id' => $resortId, 'BuildingName' => $r['building_id'], 'Floor' => $r['floor'], 'RoomNo' => $r['room']]);
            for ($b = 1; $b <= $fields['Capacity']; $b++) {
                AssingAccommodation::create(['resort_id' => $resortId, 'available_a_id' => $room->id, 'BedNo' => 'BedNo-' . $b]);
            }
        } else {
            $beds = AssingAccommodation::where('available_a_id', $room->id)->where('resort_id', $resortId)->orderBy('id')->get();
            $occupied = $beds->filter(fn ($b) => !empty($b->emp_id))->count();
            if ($fields['Capacity'] < $occupied) {
                return "Room {$r['room']} has {$occupied} staff assigned, so it needs at least {$occupied} beds.";
            }
            $room->update($fields);
            for ($b = $beds->count() + 1; $b <= $fields['Capacity']; $b++) {
                AssingAccommodation::create(['resort_id' => $resortId, 'available_a_id' => $room->id, 'BedNo' => 'BedNo-' . $b]);
            }
            $beds->filter(fn ($b) => empty($b->emp_id))->sortByDesc('id')->take(max(0, $beds->count() - $fields['Capacity']))->each->delete();
        }

        if (array_key_exists('items', $setup)) {
            $this->releaseRoomItems($room->id, $resortId);
            if ($short = $this->reserveRoomItems($room->id, (array) $setup['items'], $resortId)) {
                return "Room {$r['room']}: insufficient inventory: " . implode(', ', $short);
            }
        }
        return $room->id;
    }

    /** Give a room's linked items back to stock and unlink them (room deleted or its items replaced). */
    private function releaseRoomItems($roomId, $resortId): void
    {
        foreach (AvailableAccommodationInvItem::where('Available_Acc_id', $roomId)->get() as $link) {
            InventoryModule::where('id', $link->Item_id)->where('resort_id', $resortId)
                ->update(['Occupied' => DB::raw('GREATEST(COALESCE(Occupied, 0) - ' . (int) ($link->quantity ?? 1) . ', 0)')]);
        }
        AvailableAccommodationInvItem::where('Available_Acc_id', $roomId)->delete();
    }

    /**
     * Reserve [item id => quantity] for a room and link them. Check and reserve are one conditional UPDATE per item,
     * so two saves at once can't both take the last units. Returns the items short of stock; the caller rolls back.
     */
    private function reserveRoomItems($roomId, array $qtyByItem, $resortId): array
    {
        $outOfStock = [];
        foreach ($qtyByItem as $item => $qty) {
            $qty = max(1, (int) $qty);
            $reserved = InventoryModule::where('id', $item)->where('resort_id', $resortId)
                ->whereRaw('COALESCE(Quantity, 0) - COALESCE(Occupied, 0) >= ?', [$qty])
                ->update(['Occupied' => DB::raw('COALESCE(Occupied, 0) + ' . $qty)]);
            if (!$reserved && ($inv = InventoryModule::where('id', $item)->where('resort_id', $resortId)->first())) {
                $outOfStock[] = $inv->ItemName . ' (Available: ' . (($inv->Quantity ?? 0) - ($inv->Occupied ?? 0)) . ', Requested: ' . $qty . ')';
                continue;
            }
            AvailableAccommodationInvItem::create(['Available_Acc_id' => $roomId, 'Item_id' => $item, 'quantity' => $qty]);
        }
        return $outOfStock;
    }

    /** A set-up room's items (plan §2.2): the drawer shows and edits them, "Same as the floor below" copies them. */
    public function AvailableAccommodationItems($id)
    {
        $resort_id = $this->resort->resort_id;
        if (!AvailableAccommodationModel::where('id', $id)->where('resort_id', $resort_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $items = AvailableAccommodationInvItem::join('inventory_modules as i', 'i.id', '=', 'available_accommodation_inv_items.Item_id')
            ->leftJoin('inventory_category_models as c', 'c.id', '=', 'i.Inv_Cat_id')
            ->where('available_accommodation_inv_items.Available_Acc_id', $id)->where('i.resort_id', $resort_id)
            ->get(['i.id', 'i.ItemName as name', 'i.ItemCode as code', 'c.CategoryName as category', 'available_accommodation_inv_items.quantity as qty',
                DB::raw('GREATEST(COALESCE(i.Quantity, 0) - COALESCE(i.Occupied, 0), 0) as available')]);
        return response()->json(['success' => true, 'items' => $items]);
    }

    public function AvailableAccommodationUpdate(Request $request, $id)
    {
        $resort_id = $this->resort->resort_id;
        $accommodation = AvailableAccommodationModel::where('id', $id)->where('resort_id', $resort_id)->first();
        if (!$accommodation) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        // Optional items section, {item id: quantity}; when sent it replaces the room's items (absent = items untouched).
        if ($request->has('items')) {
            $v = Validator::make($request->all(), ['items' => ['present', 'array'], 'items.*' => ['integer', 'min:1', 'max:999']]);
            if ($v->fails()) {
                return response()->json(['success' => false, 'errors' => $v->errors()], 422);
            }
        }
        DB::beginTransaction();
        if ($request->has('items')) {
            $this->releaseRoomItems($id, $resort_id);
            if ($short = $this->reserveRoomItems($id, $request->input('items', []), $resort_id)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Insufficient inventory: ' . implode(', ', $short)], 422);
            }
        }
        $accommodation->update([
            'Accommodation_type_id' => $request->Accommodation_type_id,
            'Capacity' => $request->Capacity,
            'RoomType' => $request->RoomType,
            'blockFor' => $request->blockFor,
            'CleaningSchedule' => $request->CleaningSchedule,
            'RoomStatus' => $request->RoomStatus ?? 'Available',
            'Occupancytheresold' => $request->Occupancytheresold,
        ]);

        // Update existing bed names
        if ($request->has('bedNames') && is_array($request->bedNames)) {
            foreach ($request->bedNames as $bedId => $bedName) {
                \App\Models\AssingAccommodation::where('id', $bedId)
                    ->where('resort_id', $resort_id)
                    ->update(['BedNo' => $bedName]);
            }
        }

        // Create new beds if capacity increased
        if ($request->has('newBeds') && is_array($request->newBeds)) {
            foreach ($request->newBeds as $bedName) {
                \App\Models\AssingAccommodation::create([
                    'resort_id' => $resort_id,
                    'available_a_id' => $id,
                    'BedNo' => $bedName,
                ]);
            }
        }

        // Remove unoccupied excess beds if capacity decreased
        $currentBedCount = \App\Models\AssingAccommodation::where('available_a_id', $id)->where('resort_id', $resort_id)->count();
        $newCapacity = intval($request->Capacity);
        if ($newCapacity < $currentBedCount) {
            $excessCount = $currentBedCount - $newCapacity;
            $emptyBeds = \App\Models\AssingAccommodation::where('available_a_id', $id)
                ->where('resort_id', $resort_id)
                ->where('emp_id', 0)
                ->orderBy('id', 'desc')
                ->limit($excessCount)
                ->pluck('id');
            \App\Models\AssingAccommodation::whereIn('id', $emptyBeds)->get()->each->delete(); // as models: the occupancy alert re-checks the room
        }
        DB::commit();
        return response()->json(['success' => true, 'message' => 'Accommodation updated successfully']);
    }

    public function AvailableAccommodationStore(Request $request)
    {
        $BuildingName = $request->BuildingName;
        $Floor = $request->Floor;
        $RoomNo = $request->RoomNo;
        $Accommodation_type_id = $request->Accommodation_type_id;
        $Capacity = $request->Capacity;
        $RoomType = $request->RoomType;
        $BedNo = $request->BedNo;
        $blockFors = $request->blockFor;
        $Inv_Cat_id = $request->Inv_Cat_id;
        $CleaningSchedule = $request->CleaningSchedule;
        $RoomStatus = $request->RoomStatus;
        $Occupancytheresold = $request->Occupancytheresold;

        $resort_id = $this->resort->resort_id;


        // $validator = Validator::make($request->all(), [
        //     'BuildingName.*' => [
        //         'required',
        //     ],
        //     'Floor.*' => [
        //         'required',
        //     ],
        //     'RoomNo.*' => [
        //         'required',
        //         function ($attribute, $value, $fail) use ($request, $resort_id) {
        //             $index = str_replace('RoomNo.', '', $attribute);
        //             $buildingName = $request->BuildingName[$index] ?? null;
        //             $floor = $request->Floor[$index] ?? null;

        //             // Check if RoomNo exists for the same BuildingName, Floor, and resort_id
        //             $exists = DB::table('available_accommodation_models')
        //                 ->where('BuildingName', $buildingName)
        //                 ->where('Floor', $floor)
        //                 ->where('RoomNo', $value)
        //                 ->where('resort_id', $resort_id)
        //                 ->exists();

        //             if ($exists) {
        //                 $fail("The RoomNo {$value} already exists in Building {$buildingName}, Floor {$floor} for this resort.");
        //             }
        //         },
        //     ],
        //     // Other validation rules
        //     'Accommodation_type_id.*' => 'required|integer',
        //     'Capacity.*' => 'required|string|max:255',
        //     'RoomType.*' => 'required|string|max:255',
        //     'BedNo.*' => 'required|integer|min:1',
        //     'blockFor.*' => 'required|in:Male,Female',
        //     'Inv_Cat_id.*' => 'required',
        //     'CleaningSchedule.*' => 'required|string|in:Daily,Weekly,Monthly,Yearly',
        //     'RoomStatus.*' => 'required|string',
        //     'Occupancytheresold.*' => 'required|integer|min:1|max:100',
        //     'AvailableCount' => 'required|integer|min:1',
        // ], [
        //     'BuildingName.*.required' => 'The Building field is required. Please select a building.',
        //     'Floor.*.required' => 'The Floor field is required. Please specify the floor number.',
        //     'RoomNo.*.required' => 'The RoomNo field is required. Please specify the room number.',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }
        DB::beginTransaction();

        try
        {

        for($i=1;$i<= $request->AvailableCount;$i++)
        {

            $capacity =   array_key_exists($i,$request->Capacity)?$request->Capacity[$i]:"";

            $Floor=array_key_exists($i,$request->Floor)?$request->Floor[$i]:"";
            $RoomNo=array_key_exists($i,$request->RoomNo)?$request->RoomNo[$i]:"";
            $Accommodation_type_id = array_key_exists($i,$request->Accommodation_type_id)?$request->Accommodation_type_id[$i]:"";
            $buildingId = array_key_exists($i,$request->BuildingName)?$request->BuildingName[$i]:"";

            // Check for duplicate room
            $exists = AvailableAccommodationModel::where('resort_id', $resort_id)
                ->where('BuildingName', $buildingId)
                ->where('Floor', $Floor)
                ->where('RoomNo', $RoomNo)
                ->exists();

            if ($exists) {
                DB::rollBack();
                $buildingName = \App\Models\BuildingModel::where('id', $buildingId)->where('resort_id', $resort_id)->value('BuildingName') ?? $buildingId;
                return response()->json([
                    'success' => false,
                    'message' => "Room {$RoomNo} on Floor {$Floor} in {$buildingName} already exists."
                ], 422);
            }

            $parent_id =  AvailableAccommodationModel::create([
                            "BuildingName"=>$buildingId,
                            "Floor"=>$Floor,
                            "RoomNo"=>$RoomNo,
                            "Accommodation_type_id"=>$Accommodation_type_id,
                            "Capacity"=>$capacity ,
                            "RoomType"=>array_key_exists($i,$request->RoomType)?$request->RoomType[$i]:"",
                            "BedNo"=>array_key_exists($i,$request->BedNo)?$request->BedNo[$i]:"",
                            "blockFor"=>array_key_exists($i,$request->blockFor)?$request->blockFor[$i]:"",
                            // "Inv_Cat_id"=>array_key_exists($i,$request->Inv_Cat_id)?$request->Inv_Cat_id[$i]:"",
                            "CleaningSchedule"=>array_key_exists($i,$request->CleaningSchedule)?$request->CleaningSchedule[$i]:"",
                            "RoomStatus"=>($request->RoomStatus && array_key_exists($i,$request->RoomStatus))?$request->RoomStatus[$i]:"Available",
                            "Occupancytheresold"=>array_key_exists($i,$request->Occupancytheresold)?$request->Occupancytheresold[$i]:"",
                            "resort_id"=>$resort_id,
                        ]);

                if($request->Inv_Cat_id && array_key_exists($i,$request->Inv_Cat_id))
                {
                    $invQtyMap = ($request->InvQty && array_key_exists($i, $request->InvQty)) ? $request->InvQty[$i] : [];

                    $qtyByItem = [];
                    foreach ($request->Inv_Cat_id[$i] as $item) {
                        $qtyByItem[$item] = isset($invQtyMap[$item]) ? intval($invQtyMap[$item]) : 1;
                    }
                    $outOfStock = $this->reserveRoomItems($parent_id->id, $qtyByItem, $resort_id);

                    if (!empty($outOfStock)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient inventory: ' . implode(', ', $outOfStock)
                        ], 422);
                    }
                }

                    $bedCount = intval($capacity);
                    $bedNames = ($request->BedNames && array_key_exists($i, $request->BedNames)) ? $request->BedNames[$i] : [];
                    for ($b = 0; $b < $bedCount; $b++)
                    {
                        AssingAccommodation::create([
                            "resort_id" => $resort_id,
                            'available_a_id' => $parent_id->id,
                            'BedNo' => $bedNames[$b] ?? 'BedNo-' . ($b + 1),
                        ]);
                    }


                //
            }
            DB::commit();

            return response()->json(['success' =>true,'message'=>'Available Accommodation added successfully' ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' =>false,'error' => 'Failed to Save','message'=>'Failed to  added Available Accommodation  '], 500);
        }
    }
    //Bulidng
    public function StoreBuilding(Request $request)
    {
            $resort_id = $this->resort->resort_id;
            $Catid = base64_decode($request->CatId);

            $validator = Validator::make($request->all(), [
                'BuildingName' => [
                    'required',
                    'max:50',
                    Rule::unique('building_models')->where(function ($query) use ($resort_id)
                    {
                        return $query->where('resort_id', $resort_id);
                    }),
                ],
            ] + $this->buildingFieldRules(), [
                'room_number_pattern.regex' => 'Use letters, digits, space, - / . and {F} {N} {NN} {C}, with at least one of {N}, {NN} or {C}.',
                'BuildingName.required' => 'The Building Name field is required. Please write something.',
                'BuildingName.unique' => 'The Building Name already exists for this resort.',
                'BuildingName.max' => 'The maximum allowed length for the Building Name is 50 characters.',
            ]);

            if($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
        DB::beginTransaction();

        try
        {
            $created = BuildingModel::create(["resort_id"=>$resort_id,"BuildingName"=>$request->BuildingName]
                + array_filter($request->only(['gender', 'room_numbering', 'room_number_pattern']), fn ($v) => $v !== null));
            DB::commit();
            return response()->json(['success' =>true,'message'=>'Building added successfully', 'id' => $created->id], 200);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' =>false,'error' => 'Failed to Save','message'=>'Failed to  added Available Accommodation  '], 500);
        }
    }
    public function BuildingIndex(Request $request)
    {

        if($request->ajax())
        {

            $BuildingModel= BuildingModel::where('resort_id',$this->resort->resort_id)->get();

            return datatables()->of($BuildingModel)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);

                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>';

            })


            ->rawColumns(['action'])
            ->make(true);
        }
        $page_title="Building Name";
        return view('resorts.Accommodation.configration.BuildingIndex',compact('page_title'));

    }

    public function BuildingUpdate(Request $request)
    {
        $AccommodationName = $request->AccommodationName;

        $resort_id = $this->resort->resort_id;
        $b_id = base64_decode($request->CatId);

        $validator = Validator::make($request->all(), [
            'BuildingName' => [
                'required',
                'max:50',
                Rule::unique('building_models')->where(function ($query) use ($resort_id,$b_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($b_id),
            ],
        ] + $this->buildingFieldRules(), [
            'room_number_pattern.regex' => 'Use letters, digits, space, - / . and {F} {N} {NN} {C}, with at least one of {N}, {NN} or {C}.',
            'BuildingName.required' => 'The Building Name field is required. Please write something.',
            'BuildingName.unique' => 'The Building Name already exists for this resort.',
            'BuildingName.max' => 'The maximum allowed length for the Building Name is 50 characters.',
        ]);


        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            // Was unscoped — the update payload reassigns resort_id to the
            // caller's own, so without a where() filter this let one resort
            // "adopt" another resort's building record by id.
            BuildingModel::where("id",$b_id)->where('resort_id', $resort_id)->update(["resort_id"=>$resort_id,"BuildingName"=>$request->BuildingName]
                + array_filter($request->only(['gender', 'room_numbering', 'room_number_pattern']), fn ($v) => $v !== null));

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Building Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Building  Name'], 500);
        }
    }
    public function BuildingDestory($id)
    {
        $id = base64_decode($id);

        DB::beginTransaction();
        try
        {
            BuildingModel::where("id",$id)->where('resort_id', $this->resort->resort_id)->delete();
            BulidngAndFloorAndRoom::where("building_id",$id)->where('resort_id', $this->resort->resort_id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Building  and internal Floor and Room  Delete Successfully ',
            ], 200);
           
              
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Building Name'], 500);
        }
    }
    public function StoreFloorandroom(Request $request)
    {

        $resort_id = $this->resort->resort_id;
        $building_id = $request->building_id;
        $validator = Validator::make($request->all(), [
            'building_id' => [
                'required',
            ],
            'Floor' => [
                'present', 'numeric', 'min:0',
            ],
            'Room' => [
                ...self::ROOM_NO_RULES,
                Rule::unique('bulidng_and_floor_and_rooms')->where(function ($query) use ($request, $resort_id, $building_id) {
                    return $query->where('Floor', $request->Floor)
                                 ->where('building_id', $building_id)
                                 ->where('resort_id', $resort_id);
                }),
            ],
        ], [
            'building_id.required' => 'The Building field is required. Please select a building.',
            'Floor.required' => 'The Floor field is required. Please specify the floor number.',
            'Floor.unique' => 'The Floor already exists in the selected building.',
            'Room.required' => 'The Room field is required. Please specify the room number.',
            'Room.unique' => 'The Room already exists on the selected floor in the selected building.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            $resort_id = $this->resort->resort_id;
            $created = BulidngAndFloorAndRoom::create(["resort_id"=>$resort_id,"Floor"=>$request->Floor,"Room"=>$request->Room,"building_id"=>$request->building_id]);
            DB::commit();
            return response()->json([ 'success' => true,'message' => 'Floor And Room Updated Successfully', 'id' => $created->id], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Floor And Room Name'], 500);
        }
    }

    public function FloorRoomIndex(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $room = BulidngAndFloorAndRoom::with('building')->where("resort_id",$resort_id)->get();

        if($request->ajax())
        {


            return datatables()->of($room)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);

                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-buildingId="'.e($row->building_id).'" data-cat-id="' . e($id) . '">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-cat-id="' . e($id) . '">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>';

            })
            ->addColumn('BuildingName',function($r){

                return $r->building->BuildingName ?? ' ';
            })
            ->rawColumns(['action'])
            ->make(true);
        }
        $BuildingData= BuildingModel::where('resort_id',$this->resort->resort_id)
        ->orderBy("id","DESC")
        ->get();
        $page_title ="Floor And Rooms ";
        return view('resorts.Accommodation.configration.FloorRoomIndex',compact('page_title','BuildingData'));

    }
    public function FloorAndRoomUpdate(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $Main_id = base64_decode($request->Main_id);
        $validator = Validator::make($request->all(), [
            'building_id' => ['required', Rule::exists('building_models', 'id')->where('resort_id', $resort_id)],
            'Floor' => ['present', 'numeric', 'min:0'],
            'Room' => [...self::ROOM_NO_RULES, Rule::unique('bulidng_and_floor_and_rooms')->where(fn ($q) => $q
                ->where('Floor', $request->Floor)->where('building_id', $request->building_id)->where('resort_id', $resort_id))->ignore($Main_id)],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try
        {

            $building_id = $request->building_id;
            $Floor = $request->Floor;
            $Room = $request->Room;
            $Main_id = base64_decode($request->Main_id);
            $resort_id = $this->resort->resort_id;
            // Same hijack pattern as BuildingUpdate — scope the where().
            BulidngAndFloorAndRoom::where("id",$Main_id)->where('resort_id', $resort_id)->update(["resort_id"=>$resort_id,"Floor"=>$Floor,"Room"=>$Room,"building_id"=>$building_id]);
            DB::commit();
                return response()->json(['success' =>true,'message'=>'Floor and Room Updated successfully' ], 200);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' =>false,'error' => 'Failed to Save','message'=>'Failed to  Update Floor and Room'], 500);
        }

    }

    public function FloorAndRoomDestroy($id)
    {
        $id = base64_decode($id);

        DB::beginTransaction();
        try {
            BulidngAndFloorAndRoom::where("id", $id)->where('resort_id', $this->resort->resort_id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Floor and Room deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete Floor and Room'], 500);
        }
    }

    public function GetBuildingWiseFloor(Request $request)
    {
        $building_id = $request->buildingId;
        $BulidngAndFloorAndRoom = BulidngAndFloorAndRoom::where('resort_id', $this->resort->resort_id)
                                                        ->where("building_id",$building_id)
                                                        ->groupBy('Floor')
                                                        ->pluck('Floor');
        return response()->json(['success' =>true,'data'=>$BulidngAndFloorAndRoom ], 200);

    }
    public function GetFloorWiseRooms(Request $request)
    {
        $AvailableFloor = $request->AvailableFloor;
        $building_id = $request->building_id;

        $BulidngAndFloorAndRoom = BulidngAndFloorAndRoom::where('resort_id', $this->resort->resort_id)
                                                        ->where("building_id",$building_id)
                                                        ->where("Floor",$AvailableFloor)
                                                        ->pluck('Room');
         return response()->json(['success' =>true,'data'=>$BulidngAndFloorAndRoom ], 200);
    }
    public function OccupancyThreshold(Request $request)
    {

        $building_id = $request->building_id;
        $Floor = $request->Floor;
        $RoomNo = $request->RoomNo;
        $ThresSoldLevel = $request->ThresSoldLevel;
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'building_id.*' => [
                'required',
            ],
            'Floor.*' => [
                'required',
            ],
            'RoomNo.*' => [
                'required',
                function ($attribute, $value, $fail) use ($request, $resort_id) {
                    $index = str_replace('RoomNo.', '', $attribute);
                    $building_id = $request->building_id[$index] ?? null;
                    $floor = $request->Floor[$index] ?? null;

                    // Validate RoomNo uniqueness for the same Building, Floor, and Resort
                    $exists = DB::table('occupancy_levels_hit_a_critical_thresholds')
                        ->where('building_id', $building_id)
                        ->where('Floor', $floor)
                        ->where('RoomNo', $value)
                        ->where('resort_id', $resort_id)
                        ->exists();

                    if ($exists) {
                        $fail("The RoomNo {$value} already exists in Building {$building_id}, Floor {$floor} for this resort.");
                    }
                },
            ],

        ], [
            'building_id.*.required' => 'The Building field is required. Please select a building.',
            'Floor.*.required' => 'The Floor field is required. Please specify the floor number.',
            'RoomNo.*.required' => 'The RoomNo field is required. Please specify the room number.',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            $i=0;
            $AvailablethreshodCount = $request->AvailablethreshodCount;
            for ($i = 1; $i <= $AvailablethreshodCount; $i++)
            {


                OccupancyLevelsHitACriticalThreshold::create([
                    'resort_id'=> $resort_id,
                    'building_id'=>$building_id[$i],
                    'Floor'=>$Floor[$i],
                    'RoomNo'=>$RoomNo[$i],
                    'ThresSoldLevel'=>$ThresSoldLevel[$i],
                ]);

            }

            DB::commit();
            return response()->json([ 'success' => true,'message' => 'Occupancy levels Critical thresholds Create Successfully'], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Occupancy levels Critical thresholds'], 500);
        }


    }

    public function EscalationDay(Request $request)
    {
        $EscalationDay = $request->EscalationDay;
        $resort_id = $this->resort->resort_id;
            // One row per resort (updateOrCreate below), read as a number of days by the maintenance
            // escalation check. The old unique rule ignored the row whose *id* equalled the resort id,
            // so re-saving the current value failed with "already taken".
            $validator = Validator::make($request->all(), [
                'EscalationDay' => ['required', 'integer', 'min:1', 'max:30'],
            ], [
                'EscalationDay.required' => 'Enter the number of days.',
                'EscalationDay.integer' => 'Escalation day must be a whole number of days from 1 to 30.',
                'EscalationDay.min' => 'Escalation day must be a whole number of days from 1 to 30.',
                'EscalationDay.max' => 'Escalation day must be a whole number of days from 1 to 30.',
            ]);

            if($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            DB::beginTransaction();

            try
            {
                EscalationDay::updateOrCreate(["resort_id"=>$resort_id],["resort_id"=>$resort_id,"EscalationDay"=>$request->EscalationDay]);
                DB::commit();
                return response()->json(['success' =>true,'message'=>'EscalationDay added successfully' ], 200);

            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['success' =>false,'error' => 'Failed to Save','message'=>'Failed to  added EscalationDay '], 500);
            }
    }
    public function AccommodationTemplete()
    {
        return Excel::download(new AvailableAccommodationExport, 'AvailableAccommodationTempleteExport.xlsx');
    }

    public function AccommodationAvailableFileStore(Request $request)
    {
        $File = $request->AvailableAccommodationFile;
        $resort_id = $this->resort->resort_id;
        // $request->validate([
        //                             'AvailableAccommodationFile' => 'required|file|mimes:csv,xls,xlsx|max:2048', // 2MB max file size
        //                         ]);

            $file = $request->file('AvailableAccommodationFile');

            $import = new ImportAvailableAccommodation();
            Excel::import($import, $request->file('AvailableAccommodationFile'));


            if (!empty($import->existingRows)) {
                return response()->json([
                    'success' => false,
                    'errors' => $import->existingRows,
                    'message' => 'Some rooms already exist in the database.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Available accommodations imported successfully.',
            ], 200);


            try
            {

            } catch (\Exception $e)
            {
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
    }

    public function QuickAssignmentTemplete()
    {
        return Excel::download(new QuickAssignmentTempleteExport, 'QuickAssignmentTempleteExport.xlsx');
    }


    public function QuickAssignmentFileStore(Request $request)
    {
        $File = $request->QuickAssignmentFile;
        $resort_id = $this->resort->resort_id;
        // $request->validate([
        //                             'AvailableAccommodationFile' => 'required|file|mimes:csv,xls,xlsx|max:2048', // 2MB max file size
        //                         ]);


            $import = new ImportQuickAssignment();
            Excel::import($import, $request->file('QuickAssignmentFile'));

            $hasErrors = !empty($import->errorMessages);
            $hasSuccess = !empty($import->successMessages);

            if ($hasErrors && !$hasSuccess) {
                return response()->json([
                    'success' => false,
                    'errors' => $import->errorMessages,
                    'message' => 'Import failed. Please check the errors.',
                ], 422);
            }

            if ($hasErrors && $hasSuccess) {
                return response()->json([
                    'success' => true,
                    'errors' => $import->errorMessages,
                    'successMessages' => $import->successMessages,
                    'message' => count($import->successMessages) . ' assigned successfully. ' . count($import->errorMessages) . ' failed.',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'successMessages' => $import->successMessages ?? [],
                'message' => 'All assignments imported successfully!',
            ], 200);



    }
}

