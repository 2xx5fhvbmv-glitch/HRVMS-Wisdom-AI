<?php

namespace App\Http\Controllers\Resorts\Visa;
use DB;
use Auth;
use Excel;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use  App\Models\VisaNationality;
use  App\Models\VisaFeeAmount;
use  App\Models\ResortBudgetCost;
use  App\Models\DepositRefound;
use App\Exports\VisaNationalityExport;
use App\Imports\VisaNationalityImport;
use App\Models\VisaConfigReminder;
use App\Models\VisaDocumentType;
use App\Models\VisaDocumentSegmentation;
use App\Models\ResortSiteSettings;
use App\Models\VisaWallets;
use App\Models\VisaXpactAmounts;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function index()
    {
        $page_title="Configuration Index";
        $nationality = config('settings.nationalities');
        $ResortSiteSettings =  ResortSiteSettings::where('resort_id',$this->resort->resort_id)->first(['MVRtoDoller','DollertoMVR']);
        $ResortBudgetCost = ResortBudgetCost::whereIn("details",["Xpat Only"])->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->get()
        ->map(function($i)use($ResortSiteSettings)
        {

            $i->New_Amount  =  Common::RateConversion("DollerToMVR",$i->amount,$this->resort->resort_id);
            return  $i;
        });
    
        $DepositRefound = DepositRefound::where('resort_id',$this->resort->resort_id)->first();
        $visaReminder = VisaConfigReminder::where('resort_id',$this->resort->resort_id)->first();
        
        $VisaDocumentType = VisaDocumentType::where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.Visa.config.index',compact('page_title','nationality','ResortBudgetCost','DepositRefound','visaReminder','VisaDocumentType'));
    }
    public function NationalityStore(Request $request)
    {
        $resortId = $this->resort->resort_id;
        $rules = [
            'nationality'   => ['required', 'array', 'min:1'],
            'nationality.*' => [
                'required',
                Rule::unique('visa_nationalities', 'nationality')
                    ->where(function ($query) use ($resortId) {
                        return $query->where('resort_id', $resortId);
                    }),
            ],
            'amt'           => ['required', 'array', 'min:1'],
            'amt.*'         => ['required', 'numeric', 'min:0'],
        ];
        
        $messages = [
            'nationality.*.unique' => 'The selected nationality already exists for this resort.',
            'nationality.*.required' => 'Please select a nationality.',
            'amt.*.required' => 'Please enter an amount.',
            'amt.*.numeric' => 'Amount must be a number.',
        ];
        
        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Process and save the data
            $nationalities = $request->nationality;
            $amounts = $request->amt;
            
            foreach ($nationalities as $key => $nationality) {
                    VisaNationality::create([
                    'resort_id' => $resortId,
                    'nationality' => $nationality,
                    'amt' => $amounts[$key],
                ]);
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'msg' => 'Nationality added successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to add nationality',
            ], 500);
        }
    }
    public function NationalityIndex(Request $request)
    {
        $nationalitydata = VisaNationality::where('resort_id', $this->resort->resort_id)->orderBy('id','desc')->get();

        if ($request->ajax()) 
        {
            return datatables()->of($nationalitydata)
                ->editColumn('nationality', function ($row) 
                {
                    return $row->nationality;
                })
                ->editColumn('Amount', function ($row) {
                    return $row->amt;
                })
                ->editColumn('Action', function ($row) 
                {
                    $id = base64_encode($row->id);
                    return '
                    <div  class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-del_cat_id="'.$row->nationality.'" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
                })      
                ->rawColumns(['nationality','Amount','Action'])
                ->make(true);
        }      
        $page_title="Configuration";
        $nationality = config('settings.nationalities');

        return view('resorts.Visa.config.natioanlityindex', compact('page_title','nationality'));
    }
    public function NationalityUpdate(request $request,$id)
    {
        $Main_id  =  base64_decode($request->Main_id);
        $Nationality  =  $request->Nationality;
        $Amount  =  $request->Amount;
        
        
        $resortId = $this->resort->resort_id;
        
        $rules = [
            'nationality' => [
                'required',
                Rule::unique('visa_nationalities', 'nationality')
                    ->where(function ($query) use ($resortId) {
                        return $query->where('resort_id', $resortId);
                    })
                    ->ignore($Main_id), // exclude current record from unique check
            ],
            'amt' => ['required', 'numeric', 'min:0'],
        ];
        $messages = [
            'nationality.unique' => 'The selected nationality already exists for this resort.',
            'nationality.required' => 'Please select a nationality.',
            'amt.required' => 'Please enter an amount.',
            'amt.numeric' => 'Amount must be a number.',
            'amt.min' => 'Amount cannot be negative.',
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $visa = VisaNationality::findOrFail($Main_id);
        $visa->update([
            'nationality' => $request->nationality,
            'amt' => $request->amt,
        ]);
    
        return response()->json([
            'success' => true,
            'msg' => 'Visa nationality updated successfully.',
        ]);

    }
    public function Destroy($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            VisaNationality::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Nationality Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Nationality'], 500);
        }
    }
    public function Nationalityexport(Request $request) // Get method call
    {
        return Excel::download(new \App\Exports\VisaNationalityExport(), 'visa_nationalities.xlsx');

    }
    public function NationalityImport(Request $request) // Get method call
    {
      
        $validator = Validator::make($request->all(), [
            'nationality' => 'required|file|mimes:xls,xlsx',
        ], [
            'nationality.mimes' => 'The Benifit Grid file must be a type of: xls, xlsx.',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        session()->forget('import_errors');
        $filePath = $request->file('nationality')->store('imports');
        
        // Step 4: Process the import synchronously
        // try {
       
            Excel::import(new VisaNationalityImport(), $filePath);
            $importErrors = session('import_errors');
            if (!empty($importErrors)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Some rows could not be imported',
                    'errors' => $importErrors
                ], 422);
            }
            
            return response()->json([
                'success' => true,
                'msg' => "Visa Nationality Import Stored successfully"
            ]);
    }
    public function VisaAmtForm(Request $request)
    {

        $resortId = $this->resort->resort_id;

        $rules = [
            'amount'           => ['required', 'array', 'min:1'],
            'amount.*'         => ['required', 'numeric', 'min:0'],
        ];
        
        $messages = [
            'amount.*.required' => 'Please enter an amount.',
            'amount.*.numeric' => 'Amount must be a number.',
        ];
        
        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
                       

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ids = $request->id;
        $amount = $request->amount;
        $nationality = $request->nationality;
        $AmountbeforExp = $request->AmountbeforExp;
        $AmountafterExp = $request->AmountafterExp;

        foreach($ids as $k=>$id)
        {
        
            $amount1 = array_key_exists($k,$amount) ?  $amount[$k]:null;

            if(isset($amount1))
            {
                ResortBudgetCost::where('id',$id)->update(['amount'=>$amount1,'amount_unit'=>'ރ']);
            }
        }
        DB::beginTransaction();
        try 
        {
            $data = ["resort_id"=>$this->resort->resort_id,'nationality'=>$request->nationality,'AmountbeforExp'=>$request->AmountbeforExp,'AmountafterExp'=>$request->AmountafterExp];
            VisaFeeAmount::updateOrCreate(["resort_id"=>$this->resort->resort_id,'nationality'=>$request->nationality],$data);
                DB::commit();
            return response()->json([
                'success' => true,
                'msg' => 'Visa Fees  Create or updated  successfully.',
            ]);


        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Update or Create Visa Fees'], 500);
        }

    }
    public function DepositRefundStore(Request $request)
    {

        $resortId = $this->resort->resort_id;
        $rules = [
                'initial_reminder'   => ['required', 'numeric'],
                'followup_reminder'   => ['required', 'numeric'],
            ];
        
        $messages = [
            'initial_reminder.required' => 'Please Select Initial Reminder.',
            'followup_reminder.required' => 'Please Select nationality.',
        ];
        
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        try 
        {

                DepositRefound::updateorCreate(['resort_id' =>$this->resort->resort_id],
                                    [
                                        'resort_id'         =>$this->resort->resort_id,
                                        "initial_reminder"  =>$request->initial_reminder,
                                        "followup_reminder" =>$request->followup_reminder
                                    ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'msg' => 'Deposit Refound Updated Successfully.',
                ]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Update Deposit Refound'], 500);
        }
    }
    public function Reminderalert(Request $request)
    {
        DB::beginTransaction();
        try 
        {
                $visaReminder = VisaConfigReminder::where('resort_id',$this->resort->resort_id)->first();
                if(!isset( $visaReminder))
                {
                    $visaReminder = new VisaConfigReminder();
                }
                $visaReminder->resort_id =  $this->resort->resort_id;
                if($request->Work_Permit_Fee_reminder =="on")
                {
                    $visaReminder->Work_Permit_Fee =  $request->Work_Permit_Fee;
                    $visaReminder->Work_Permit_Fee_reminder =   "Active";
                }
                else
                {
                    $visaReminder->Work_Permit_Fee_reminder =  "InActive";
                }
                if($request->Slot_Fee_reminder =="on")
                {
                    $visaReminder->Slot_Fee =  $request->Slot_Fee;
                    $visaReminder->Slot_Fee_reminder = "Active";
                }
                else
                {
                    $visaReminder->Slot_Fee_reminder =  "InActive";
                }
                if($request->Insurance_reminder =="on")
                {
                    $visaReminder->Insurance =  $request->Insurance;
                    $visaReminder->Insurance_reminder =    "Active";
                }
                else
                {
                    $visaReminder->Insurance_reminder =  "InActive";
                }
                if($request->Medical_reminder =="on")
                {
                    $visaReminder->Medical =  $request->Medical;
                    $visaReminder->Medical_reminder =   "Active";
                }
                else
                {
                    $visaReminder->Medical_reminder =  "InActive";
                }
                if($request->Visa_reminder =="on")
                {
                    $visaReminder->Visa =  $request->Visa;
                    $visaReminder->Visa_reminder =   "Active";
                }
                else
                {
                    $visaReminder->Visa_reminder =  "InActive";
                }
                if($request->Passport_reminder =="on")
                {
                    $visaReminder->Passport =  $request->Passport;
                    $visaReminder->Passport_reminder =  "Active";
                }
                else
                {
                    $visaReminder->Passport_reminder =  "InActive";
                }
                $visaReminder->save();
                DB::commit();
                return response()->json([
                    'success' => true,
                    'msg' => 'Expiry Date Updated Successfully.',
                ]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Update Expiry Date'], 500);
        }    
    }
    public function DocumentTypeIndex(Request $request)
    {
        $visaDocument = VisaDocumentType::where('resort_id', $this->resort->resort_id);
        if ($request->ajax()) 
        {
            return datatables()->of($visaDocument)
                    ->orderColumn('id', 'id $1') 
                    ->editColumn('DocumentName', function ($row) 
                    {
                        return $row->documentname;
                    })
                    ->editColumn('Action', function ($row) {
                        $id = base64_encode($row->id);
                        return '
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-del_cat_id="' . $row->documentname . '" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
                    })
                    ->rawColumns(['Action'])
                    ->make(true);
        }      
    }
    public function DocumentType(Request $request)
    {
        $rules = [
            'documentname' => [
                'required',
                Rule::unique('visa_document_types')->where(function ($query) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })
            ]
        ];
        
        $messages = [
            'documentname.required' => 'Plase enter document name.',
            'documentname.unique' => 'document name already exists for this resort.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        $documentname =  $request->documentname;
        if ($validator->fails()) 
        {
            return response()->json(['success' => false,'msg' => 'Validation failed','errors' => $validator->errors()], 422);
        }
        DB::beginTransaction();
        try 
        {
            VisaDocumentType::create([
                                        'resort_id'         =>$this->resort->resort_id,
                                        "documentname" =>$request->documentname
                                    ]);
                                            DB::commit();
                return response()->json([
                                            'success' => true,
                                            'msg' => 'Docuemt Type Created Successfully.',
                                        ]);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Docuemt Type'], 500);
        }
    }
    public function DocumentTypeUpdate(Request $request,$id)
    {
        $Main_id  =  base64_decode($request->Main_id);
        $documentname  =  $request->documentname;
        
        
        $resortId = $this->resort->resort_id;
        
        $rules = [
            'documentname' => [
                'required',
                Rule::unique('visa_document_types', 'documentname')
                    ->where(function ($query) use ($resortId) {
                        return $query->where('resort_id', $resortId);
                    })
                    ->ignore($Main_id), // exclude current record from unique check
            ],
        ];
        $messages = [
            'documentname.unique' => 'The selected Document Name already exists for this resort.',
            'documentname.required' => 'Please Enter Document name',
           
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $visa = VisaDocumentType::findOrFail($Main_id);
        $visa->update([
            'documentname' => $request->documentname,
        ]);
    
        return response()->json([
            'success' => true,
            'msg' => 'Document Type  updated successfully.',
        ]);

    }
    public function DocumentTypeDelete($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            VisaDocumentType::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Document Type Type Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Wallet Type'], 500);
        }
    }
    public function DocumentSegmentationStore(Request $request)
    {
        $resortId = $this->resort->resort_id;
        $rules = [
            'document_id'   => ['required', 'array', 'min:1'],
            'DocumentName.*' => [
                'required',
                Rule::unique('visa_document_segmentations', 'DocumentName')
                    ->where(function ($query) use ($resortId) {
                        return $query->where('resort_id', $resortId);
                    }),
            ],
            'document_id'           => ['required', 'array', 'min:1'],
            'document_id.*'         => ['required', 'numeric', 'min:0'],
        ];
        $messages = [
            'document_id.*.unique' => 'The selected nationality already exists for this resort.',
            'document_id.*.required' => 'Please select a nationality.',
            'DocumentName.*.required' => 'Please enter an amount.',
        ];
        
        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Process and save the data
            $VisaDocumentType = $request->document_id;
            $DocumentName = $request->DocumentName;
            
            foreach ($VisaDocumentType as $key => $type) {
                VisaDocumentSegmentation::create([
                    'resort_id' => $resortId,
                    'DocumentName' => $DocumentName[$key],
                    'document_id' => $type,
                ]);
            }
            
            DB::commit();
            return response()->json([
                'success' => true,
                'msg' => 'Document Segmentiation added successfully',
            ], 200);
        } catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to add Document Segmentiation',
            ], 500);
        }
    }
    public function VisaWalletsStore(Request $request) 
    {

        

          $rules = [
            'WalletName' => [
                'required',
                Rule::unique('visa_wallets')->where(function ($query) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })
            ]
        ];
        
        $messages = [
            'WalletName.required' => 'Plase enter Wallet name.',
            'Amt.required' => 'Plase enter Wallet Amount.',
            'WalletName.unique' => 'Wallet name already exists for this resort.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        $WalletName =  $request->WalletName;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        try 
        {
                VisaWallets::create(['resort_id' =>$this->resort->resort_id,"WalletName" =>ucfirst($request->WalletName),"Amt"=>$request->Amt]);
                VisaXpactAmounts::updateOrCreate(['resort_id' => $this->resort->resort_id, 'Xpact_WalletName' => ucfirst($request->WalletName)]);
                DB::commit();
                return response()->json(['success' => true,'msg' => 'Wallet Type Created Successfully.', ]);
         }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Wallet Type'], 500);
        }
    }
    public function WalletIndex(Request $request)
    {
        $visaWallets = VisaWallets::where('resort_id', $this->resort->resort_id);

        if ($request->ajax()) {
            return datatables()->of($visaWallets)
                ->editColumn('WalletName', function ($row) {
                    return $row->WalletName;
                })
                ->editColumn('Amount', function ($row) {
                    // Return as-is for numeric sorting; or format if sorting is disabled
                    return $row->Amt;
                })
                ->editColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                    return '
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-del_cat_id="'.$row->WalletName.'" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                            <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
                })
                ->rawColumns(['WalletName', 'Amount', 'Action'])
                ->make(true);
        }
    }
    public function UpdateWallet(Request $request,$id)
    {
       
        $Main_id  =  base64_decode($request->Main_id);
        $WalletName  =  $request->WalletName;
        $resortId = $this->resort->resort_id;
        $rules = [
            'WalletName' => [
                'required',
                Rule::unique('visa_wallets', 'WalletName')
                    ->where(function ($query) use ($resortId) {
                        return $query->where('resort_id', $resortId);
                    })
                    ->ignore($Main_id), // exclude current record from unique check
            ],
        ];
        $messages = [
            'WalletName.unique' => ' Wallet Name already exists for this resort.',
            'WalletName.required' => 'Please Enter Wallet name',
            'Amt.required' => 'Please Enter Wallet Amount',
           
        ];
    
        $validator = Validator::make($request->all(), $rules, $messages);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $visa = VisaWallets::findOrFail($Main_id);
        $visa->update([
            'WalletName' => ucfirst($request->WalletName),
            'Amt' =>$request->Amt,
        ]);
    
        return response()->json([
            'success' => true,
            'msg' => 'Wallet Type  updated successfully.',
        ]);

    }
    public function WalletDestroy($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            VisaWallets::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Wallet Type Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Wallet Type'], 500);
        }
    }
}
