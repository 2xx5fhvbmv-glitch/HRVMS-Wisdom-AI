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
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
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
        InventoryCategoryModel::create(["resort_id"=>$this->resort->resort_id,"CategoryName"=>ucfirst($CategoryName)]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Added Successfully',
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
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
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
            InventoryCategoryModel::where("id",$Catid)->update(["CategoryName"=>ucfirst($CategoryName)]);

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
        InventoryCategoryModel::where("id",$id)->delete();

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
            AccommodationType::create(["resort_id"=>$this->resort->resort_id,"AccommodationName"=>ucfirst($AccommodationName),'Color'=>$request->Color]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Accommodation Added Successfully',
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
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
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
            AccommodationType::where("id",$Catid)->update(["AccommodationName"=>ucfirst($AccommodationName),'Color'=>$request->Color]);

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
            AccommodationType::where("id",$id)->delete();

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
            $parent_id =  AvailableAccommodationModel::create([
                            "BuildingName"=>array_key_exists($i,$request->BuildingName)?$request->BuildingName[$i]:"",
                            "Floor"=>$Floor,
                            "RoomNo"=>$RoomNo,
                            "Accommodation_type_id"=>$Accommodation_type_id,
                            "Capacity"=>$capacity ,
                            "RoomType"=>array_key_exists($i,$request->RoomType)?$request->RoomType[$i]:"",
                            "BedNo"=>array_key_exists($i,$request->BedNo)?$request->BedNo[$i]:"",
                            "blockFor"=>array_key_exists($i,$request->blockFor)?$request->blockFor[$i]:"",
                            // "Inv_Cat_id"=>array_key_exists($i,$request->Inv_Cat_id)?$request->Inv_Cat_id[$i]:"",
                            "CleaningSchedule"=>array_key_exists($i,$request->CleaningSchedule)?$request->CleaningSchedule[$i]:"",
                            "RoomStatus"=>array_key_exists($i,$request->RoomStatus)?$request->RoomStatus[$i]:"",
                            "Occupancytheresold"=>array_key_exists($i,$request->Occupancytheresold)?$request->Occupancytheresold[$i]:"",
                            "resort_id"=>$resort_id,
                        ]);

                if(array_key_exists($i,$request->Inv_Cat_id))
                {

                    foreach( $request->Inv_Cat_id[$i] as $item)
                    {
                        AvailableAccommodationInvItem::create([ 'Available_Acc_id'=>$parent_id->id,'Item_id'=>$item]);
                    }


                    for ($i = 0; $i < $capacity; $i++)
                    {
                        AssingAccommodation::create([
                            "resort_id"=>$resort_id,
                            'available_a_id'=> $parent_id->id
                        ]);
                    }

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
            ], [
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
            BuildingModel::create(["resort_id"=>$resort_id,"BuildingName"=>$request->BuildingName]);
            DB::commit();
            return response()->json(['success' =>true,'message'=>'Building added successfully' ], 200);

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
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
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
        ], [
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
            BuildingModel::where("id",$b_id)->update(["resort_id"=>$resort_id,"BuildingName"=>$request->BuildingName]);

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
            BuildingModel::where("id",$id)->delete();
            BulidngAndFloorAndRoom::where("building_id",$id)->delete();
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
                'required',
            ],
            'Room' => [
                'required',
                Rule::unique('bulidng_and_floor_and_rooms')->where(function ($query) use ($request,$resort_id) {
                    return $query->where('Floor', $request->Floor)
                                 ->where('resort_id',$resort_id);
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
            BulidngAndFloorAndRoom::create(["resort_id"=>$resort_id,"Floor"=>$request->Floor,"Room"=>$request->Room,"building_id"=>$request->building_id]);
            DB::commit();
            return response()->json([ 'success' => true,'message' => 'Floor And Room Updated Successfully',], 200);
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
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-buildingId="'.e($row->building_id).'" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
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

        DB::beginTransaction();

        try
        {

            $building_id = $request->building_id;
            $Floor = $request->Floor;
            $Room = $request->Room;
            $Main_id = base64_decode($request->Main_id);
            $resort_id = $this->resort->resort_id;
            BulidngAndFloorAndRoom::where("id",$Main_id)->update(["resort_id"=>$resort_id,"Floor"=>$Floor,"Room"=>$Room,"building_id"=>$building_id]);
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
            $validator = Validator::make($request->all(), [
                'EscalationDay' => [
                    'required',
                    'max:15',
                    Rule::unique('escalation_days')->where(function ($query) use ($resort_id)
                    {
                        return $query->where('resort_id', $resort_id);
                    })->ignore($resort_id),
                ],
            ], [
                'EscalationDay.required' => 'The Escalation Day field is required. Please write something.',
                'EscalationDay.max' => 'The maximum allowed length for the Building Name is 50 characters.',
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


            if (!empty($import->errorMessages)) {
                return response()->json([
                    'success' => false,
                    'errors' => $import->errorMessages,
                    'message' => 'Some rooms already exist in the database.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import successful!',
            ], 200);



    }
}

