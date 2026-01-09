<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use DB;
use Auth;
use PHPUnit\Framework\MockObject\Stub\ReturnReference;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use Carbon\CarbonInterval;
use App\Models\BuildingModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\InventoryModule;

use App\Models\AccommodationType;
use App\Http\Controllers\Controller;
use App\Models\InventoryCategoryModel;
use App\Models\BulidngAndFloorAndRoom;
use App\Models\AssingAccommodation;
use App\Models\AvailableAccommodationModel;
use App\Models\MaintanaceRequest;
use App\Models\AvailableAccommodationInvItem;
class InventoryController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:3;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function index(Request $request)
    {

        if(Common::checkRouteWisePermission('resort.accommodation.Inventory',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        if($request->ajax())
        {
                $Inv_Cat_id = $request->get('Inv_Cat_id');
                $searchTerm = $request->get('searchTerm');
                $Items_id = $request->get('Items_id');
                $InventoryModule1 = InventoryModule::join('inventory_category_models as t1', 't1.id', '=', 'inventory_modules.Inv_Cat_id')
                                ->where('inventory_modules.resort_id', $this->resort->resort_id)
                                
                                // Apply Inv_Cat_id filter only if it's not "All"
                                ->when($Inv_Cat_id && $Inv_Cat_id !== "All", function ($query) use ($Inv_Cat_id) {
                                    return $query->where('inventory_modules.Inv_Cat_id', $Inv_Cat_id);
                                })

                                // Apply search term filter
                                ->when($searchTerm && $searchTerm !== '', function ($query) use ($searchTerm) {
                                    return $query->where(function ($q) use ($searchTerm) {
                                        $q->where('inventory_modules.ItemName', 'like', '%' . $searchTerm . '%')
                                        ->orWhere('inventory_modules.ItemCode', 'like', '%' . $searchTerm . '%')
                                        ->orWhere('inventory_modules.Occupied', 'like', '%' . $searchTerm . '%')
                                        ->orWhere('inventory_modules.Quantity', 'like', '%' . $searchTerm . '%')
                                        ->orWhere('t1.CategoryName', 'like', '%' . $searchTerm . '%')
                                        ->orWhere('inventory_modules.PurchageDate', 'like', '%' . $searchTerm . '%');
                                    });
                                })

                                // Apply specific item filter only if not "All"
                                ->when($Items_id && $Items_id !== "All", function ($query) use ($Items_id) {
                                    return $query->where('inventory_modules.id', (int) $Items_id);
                                });

                            // Final fetch with ordering and select fields
                            $InventoryModule = $InventoryModule1->orderBy('inventory_modules.id', 'DESC')
                                ->get([
                                    'inventory_modules.id',
                                    'inventory_modules.ItemName',
                                    'inventory_modules.ItemCode',
                                    'inventory_modules.Occupied',
                                    'inventory_modules.Quantity',
                                    'inventory_modules.PurchageDate',
                                    'inventory_modules.created_at',
                                    't1.CategoryName as Category'
                                ]);
            $edit_class = '';
            if(Common::checkRouteWisePermission('resort.accommodation.Inventory',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            return datatables()->of($InventoryModule)
                        ->addColumn('Action', function ($row) use ($edit_class) {
                $id = base64_encode($row->id);
                return '
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                      </div>';
            })
            ->editColumn('ItemName', function ($row) {
                return e($row->ItemName);
            })
            ->editColumn('ItemCode', function ($row) {
                return e($row->ItemCode);
            })
            ->editColumn('Category', function ($row) {
                return e($row->Category);
            })
            ->editColumn('Quantity', function ($row) {
                return e($row->Quantity);
            })
            ->editColumn('PurchageDate', function ($row) {
                return e($row->PurchageDate);
            })
            ->editColumn('Occupied', function ($row) {
                return isset($row->Occupied) ?  $row->Occupied: 0 ;
            })
            ->editColumn('Available', function ($row) {

                    if(isset($row->Occupied))
                    {
                        return $row->Quantity - $row->Occupied;
                    }
                    else
                    {
                        return $row->Quantity;
                    }

            })
            ->rawColumns(['ItemName','ItemCode','Category','Quantity','PurchageDate','Occupied','Available','Action'])

            ->make(true);

        }

        $page_title ="Invenotry category";
        $InventoryItems = InventoryModule::where('resort_id',$this->resort->resort_id)->orderBy('id', 'DESC')->get(['id','ItemName']);
        $InventoryCategory = InventoryCategoryModel::where('resort_id',$this->resort->resort_id)->orderBy('id', 'DESC')->get();

        return view("resorts.Accommodation.inventory.index",compact('page_title','InventoryCategory','InventoryItems'));
    }

    public function HistoricalInventory(Request $request)
    {
        if($request->ajax())
        {
            $searchTerm = $request->searchTerm;
            $Items_id = $request->Items_id;
            $Inv_Cat_id = $request->get('Inv_Cat_id');

            $InventoryModule = MaintanaceRequest::with(['ItemData.InventoryCategory'])
                                ->whereHas('ItemData', function ($query) use ($searchTerm) {
                                    $query->where('resort_id', $this->resort->resort_id);

                                    if (!empty($searchTerm)) {
                                        $query->where(function ($q) use ($searchTerm) {
                                            $q->where('ItemName', 'like', '%' . $searchTerm . '%')
                                                ->orWhere('ItemCode', 'like', '%' . $searchTerm . '%')
                                                ->orWhere('Quantity', 'like', '%' . $searchTerm . '%')
                                                ->orWhere('PurchageDate', 'like', '%' . $searchTerm . '%');
                                        });
                                    }
                                })
                                ->when($Inv_Cat_id && $Inv_Cat_id != 'All', function ($query) use ($Inv_Cat_id) {
                                    $query->whereHas('ItemData.InventoryCategory', function ($q) use ($Inv_Cat_id) {
                                        $q->where('id', $Inv_Cat_id);
                                    });
                                })
                                ->whereNotIn('status', ['Closed'])
                                ->where('resort_id', $this->resort->resort_id)
                                ->when($Items_id && $Items_id != 'All', function ($query) use ($Items_id) {
                                    return $query->where('item_id', $Items_id);
                                })
                                ->orderBy('id', 'DESC')
                                ->get()
                                ->map(function ($i) {
                                    $i->Category = optional($i->ItemData->InventoryCategory)->CategoryName ?? 'N/A';
                                    $i->ItemName = $i->ItemData->ItemName ?? '';
                                    $i->ItemCode = $i->ItemData->ItemCode ?? '';
                                    $i->Quantity = $i->ItemData->Quantity ?? '';
                                    $i->newStatus = 'Broken';
                                    return $i;
                                });


             
          

            return datatables()->of($InventoryModule)
                ->editColumn('ItemName', function ($row) {
                    return e($row->ItemName);
                })
                ->editColumn('ItemCode', function ($row) {
                    return e($row->ItemCode);
                })
                ->editColumn('Category', function ($row) {
                return e($row->Category);
                })
                ->editColumn('Quantity', function ($row) {
                    return e($row->Quantity);
                })
                ->editColumn('PurchageDate', function ($row) {
                    return e($row->PurchageDate);
                })
                ->editColumn('Status', function ($row)
                {
                    return '<span class="badge badge-themeDangerNew">' . $row->newStatus . '</span>';
                })
                ->rawColumns(['ItemName','ItemCode','Category','Quantity','PurchageDate','Status'])
                ->make(true);
        }
    }
    public function StoreInventory(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $Catid = base64_decode($request->CatId);
        $validator = Validator::make($request->all(), [
            'ItemName.*' => 'required|max:50', // Validate each ItemName in the array
            'Quantity.*' => 'required|integer|min:1', // Validate each Quantity in the array
            'PurchageDate.*' => 'required|date', // Validate each PurchageDate in the array
            'ItemCode.*' => [
                'required',
                Rule::unique('inventory_modules', 'ItemCode')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                }),
            ], // Validate uniqueness of each ItemCode in the array
            'MinStock.*' => 'nullable|integer|min:0', // Validate each MinStock in the array
        ], [
            'ItemName.*.required' => 'The Item Name field is required. Please write something.',
            'ItemName.*.max' => 'The maximum allowed length for the Item Name is 50 characters.',
            'Inv_Cat_id.*.required' => 'The Category field is required.',
            'Quantity.*.required' => 'The Quantity field is required.',
            'Quantity.*.integer' => 'The Quantity must be an integer.',
            'Quantity.*.min' => 'The Quantity must be at least 1.',
            'PurchageDate.*.required' => 'The Purchase Date field is required.',
            'PurchageDate.*.date' => 'The Purchase Date must be a valid date.',
            'ItemCode.*.required' => 'The Item Code field is required.',
            'ItemCode.*.unique' => 'The Item Code already exists for this resort.',
            'MinStock.*.integer' => 'The Minimum Stock must be an integer.',
            'MinStock.*.min' => 'The Minimum Stock must be at least 0.',
        ]);


            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

        DB::beginTransaction();

        try
        {
            if(count($request->ItemName)!=0)
            {
                foreach ($request->ItemName as $key => $value)
                {
                    $ItemName = array_key_exists($key, $request->ItemName) ? $request->ItemName[$key]: "";
                    $Inv_Cat_id = array_key_exists($key, $request->Inv_Cat_id) ?  $request->Inv_Cat_id[$key]: "";
                    $Quantity = array_key_exists($key, $request->Quantity) ? $request->Quantity[$key]: "";
                    $PurchageDate =array_key_exists($key, $request->PurchageDate) ? $request->PurchageDate[$key]: "";
                    $ItemCode = array_key_exists($key, $request->ItemCode) ? $request->ItemCode[$key]: "";
                    $MinStock = array_key_exists($key, $request->MinStock) ?  $request->MinStock[$key]: "";
                    InventoryModule::create([
                        'resort_id' => $resort_id,
                        'Inv_Cat_id' => $Inv_Cat_id,
                        'ItemName' => $ItemName,
                        'ItemCode' => $ItemCode,
                        'Quantity' => $Quantity,
                        'PurchageDate' => $PurchageDate,
                        'MinStock' => $MinStock,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' =>true,'message'=>'Item added successfully' ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Save','message'=>'Failed to  added successfully'], 500);
        }
    }
    public function InventoryManagement(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.accommodation.InventoryManagement',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }

        $page_title = "Inventory Management";
      

        if($request->ajax()) 
        { 
            $searchTerm = $request->searchTerm;
            $buildingAvailable = $request->buildingAvailable;
            $availableFloor = $request->AvailableFloor;
            $floorWiseRoom = $request->FloorWiseRoom;
        
            $query = InventoryModule::join('available_accommodation_inv_items as t1', 't1.Item_id', '=', 'inventory_modules.id')
                ->join('available_accommodation_models as t2', 't2.id', '=', 't1.Available_Acc_id')
                ->join('building_models as t3', 't3.id', '=', 't2.BuildingName')
                ->where('inventory_modules.resort_id', $this->resort->resort_id);
            
            // Apply search filters if they exist
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('inventory_modules.ItemName', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('inventory_modules.ItemCode', 'LIKE', "%{$searchTerm}%");
                });
            }
            
            if (!empty($buildingAvailable)) {
                $query->where('t3.id', $buildingAvailable);
            }
            
            if (!empty($availableFloor)) {
                $query->where('t2.Floor', $availableFloor);
            }
            
            if (!empty($floorWiseRoom)) {
                $query->where('t2.RoomNo', $floorWiseRoom);
            }
            $edit_class = '';
            if(Common::checkRouteWisePermission('resort.accommodation.InventoryManagement',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            $inventoryModule = $query->orderBy('inventory_modules.id', 'DESC')
                ->get(['inventory_modules.id', 'ItemName', 'ItemCode', 'inventory_modules.created_at','t3.BuildingName','t2.Floor','t2.RoomNo','t1.Available_Acc_id'])
                ->map(function($ak) use ($edit_class) {
                    $b = BuildingModel::where("resort_id", $this->resort->resort_id)
                         ->where("BuildingName", $ak->BuildingName)
                         ->first();
                                                      
                    $ak->BuildingName = ucfirst($b->BuildingName);
                    $ak->Room = $ak->RoomNo;
                    $ak->Floor = $ak->Floor;
        
                    $AssingAccommodation = AssingAccommodation::where("available_a_id", $ak->Available_Acc_id)
                        ->join('employees as t2', 't2.id', '=', 'assing_accommodations.emp_id')
                        ->join('resort_admins as t3', 't3.id', '=', 't2.Admin_Parent_id')
                        ->get(['t2.id','t3.first_name', 't3.last_name', 't3.id as Parentid'])
                        ->map(function ($row) {
                            $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name);
                            $row->profileImg = Common::getResortUserPicture($row->Parentid);
                            return $row;
                        });
                    
                    $assignAccomdationdata = array();
                    $AssignedStatus = array();
                
                    
                    foreach($AssingAccommodation as $data) 
                    {
                        $effectedDate = Carbon::parse($data->effected_date);
                        $formattedDate = $effectedDate->format('d M y');
                        $yearsDifference = $effectedDate->diffInYears(Carbon::now());
                        $dateDifferent = "{$formattedDate} ({$yearsDifference} year" . ($yearsDifference > 1 ? 's' : '') . ")";
                        $assignAccomdationdata[] = [$data->EmployeeName, $data->profileImg, $dateDifferent]; 
                        $emp[]= base64_encode($data->id);
                    }

                    if($AssingAccommodation->count() ==  0)
                    {
                        $ak->action = '<button type="button" data-flag="assign"  class="btn btn-sm btn-themeSkyblueLight '.$edit_class.'">Please Assign Employee</button>';
                    }
                    else
                    {
                        $ak->action = '<button type="button" data-flag="unassign" data-resort_id="'.base64_encode($this->resort->resort_id).'" data-item="'.base64_encode($ak->id).'"  data-id="' . base64_encode($ak->Available_Acc_id) . '" class="btn btn-sm btn-danger unassign '.$edit_class.'">Unassign</button>';
                    }
                    
                    $ak->AssignedTo = $assignAccomdationdata;
        
                    $maintance_request = MaintanaceRequest::whereNotIn("status", ['Closed'])
                        ->where("item_id", $ak->id)
                        ->where("resort_id", $this->resort->resort_id)
                        ->first();
                        
                    if(isset($maintance_request)) {
                        $ak->ItemStatus = '<span class="badge badge-themeDangerNew">Broken</span>';
                    } else {
                        $ak->ItemStatus = '<span class="badge badge-themeSuccess">Good Condition</span>';
                    }
        
                    return $ak;
                });
        
                return datatables()->of($inventoryModule) // Fixed variable name here
                ->editColumn('ItemName', function ($row) {
                    return e($row->ItemName);
                })
                ->editColumn('ItemCode', function ($row) {
                    return e($row->ItemCode);
                })
                ->editColumn('BuildingName', function ($row) {
                    return e($row->BuildingName);
                })
                ->editColumn('Floor', function ($row) {
                    return e($row->Floor);
                })
                ->editColumn('Room', function ($row) {
                    return e($row->Room);
                })
                ->editColumn('AssignedTo', function ($row) {
                    $string = '';
                    if(!empty($row->AssignedTo)) {
                        foreach($row->AssignedTo as $e) {
                            $string .= '<div class="tableUser-block">
                                <div class="img-circle"><img src="' . $e[1] . '" alt="user">
                                </div>
                                <span>' . $e[0] . '</span>
                            </div>';
                        }
                    }
                    return $string;
                })
                ->editColumn('Date', function ($row) {
                    $string = '';
                    if(!empty($row->AssignedTo)) {
                        foreach($row->AssignedTo as $e) {
                            $string .= $e[2] . '<br>';
                        }
                    }
                    return $string;
                })
                ->editColumn('ItemStatus', function ($row) {
                    return $row->ItemStatus;
                })
                
                ->editColumn('action', function ($row) {
                    return $row->action;
                })
                
                ->rawColumns(['ItemName', 'ItemCode', 'BuildingName', 'Floor', 'Room', 'AssignedTo', 'Date', 'ItemStatus', 'action'])
                ->make(true);
        }
            $BuildingModel = BuildingModel::where('resort_id',$this->resort->resort_id)->orderBy('id', 'ASC')->get();
            $Floors = BulidngAndFloorAndRoom::where('resort_id',$this->resort->resort_id)->groupBy('Floor')->get();
            
        return view("resorts.Accommodation.inventory.InventoryManagement",compact('page_title','BuildingModel','Floors'));

    }

    public function Inventoryupdated(Request $request)
    {
        $id = base64_decode($request->id);
        $qty= $request->qty;
        InventoryModule::where("resort_id",$this->resort->resort_id)->where("id",$id)->update([
            'Quantity' => $qty,
        ]);
        return response()->json(['success' =>true,'message'=>'Invenotry Updated  successfully' ], 200);
    }

    public function UnassignItem(Request $request)
    {
        $availableAccId = base64_decode($request->availableAccId);
        $resort_id = base64_decode($request->resort_id);
        $item = base64_decode($request->item);
        

        $AvailableAccommodationInvItem = AvailableAccommodationInvItem::where("Available_Acc_id", $availableAccId)
            ->where("Item_id", $item)
            ->first();
        
        if ($AvailableAccommodationInvItem)
        {
            $AvailableAccommodationInvItem->delete();
            return response()->json(['success' => true, 'message' => 'In Accommodation Item unassigned successfully'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }
}