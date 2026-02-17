<?php

namespace App\Http\Controllers\Resorts\GrievanceAndDisciplinery;


use Excel;
use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\OffensesModel;
use App\Models\ActionStore;
use App\Models\SeverityStore;
use App\Models\DisciplinaryCategoriesModel;
use App\Models\CodeOfCounduct;
use Illuminate\Validation\Rule;
use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use App\Models\DisciplinaryApprovalRoles;
use App\Models\DisciplineryLatterTemplete;
use App\Models\DisciplineryAssignCommittee;
use App\Models\DisciplineryCommitteeMembers;
use App\Models\InvestingHearingTempleteModel;
use App\Models\DisciplinaryDelegationRule;
use App\Models\DisciplinaryAppealModel;
use App\Models\RightToBeAccompanied;
use App\Models\GrievanceCategoryAndSubcatModel;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\GrievanceRightToBeAccompanied;
use App\Models\GrievanceDelegationRuleModel;
use App\Models\GrievanceNonRetaliation;
use App\Models\GrievanceAppealDeadlineModel;
use App\Models\GrievanceTempleteModel;
use App\Models\GrivanceResoultionTimeLineModel;
use App\Models\GrivanceEscaltionModel;
use App\Models\GrievanceCommitteeMemberChild;
use App\Models\GrievanceCommitteeMemberParent;
use App\Exports\DisciplineryCodeOfConduct;
use App\Models\GrivanceKeyPerson;
use App\Models\DisciplinaryEmailmodel;
class ConfigurationController extends Controller
{

    public $resort;
    public $reporting_to;
    public $globalUser='';
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        $this->globalUser = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function index()
    {
        $page_title = "Configuration";
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();
        $Offenses =  OffensesModel::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        $SeverityStore = SeverityStore::where('resort_id',$this->resort->resort_id)->get();
        $ApprovalRoles = DisciplinaryApprovalRoles::where('resort_id',$this->resort->resort_id)->first();
        $GrivanceKeys = GrivanceKeyPerson::where('resort_id',$this->resort->resort_id)->get()->pluck('emp_ids')->toArray();
        $CommitteeMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                        ->where("t1.resort_id",$this->resort->resort_id)
                        ->whereIn('employees.rank',[1,2,3,8])
                        ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        $InvestingHearingTempleteModel = InvestingHearingTempleteModel::where('resort_id',$this->resort->resort_id)->first();
        if(isset(        $InvestingHearingTempleteModel))
        {
               $InvestingHearingTempleteModel->Hearing_Temp_Structure = json_decode($InvestingHearingTempleteModel->Hearing_Temp_Structure, true);
        }

        $DisciplinaryAppeal= DisciplinaryAppealModel::where('resort_id',$this->resort->resort_id)->first();

       $Committee='';
       $OtherMembers='';

            $Committee = DisciplineryAssignCommittee::where('resort_id',$this->resort->resort_id)->get();
            $OtherMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                            ->where("t1.resort_id",$this->resort->resort_id)
                            ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);

        $RightToBeAccompanied = RightToBeAccompanied::where('resort_id',$this->resort->resort_id)->first();
        // Greivance
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();
        $GrievanceRightToBeAccompanied = GrievanceRightToBeAccompanied::where("resort_id",$this->resort->resort_id)->first();
        $GrievanceNonRetaliation= GrievanceNonRetaliation::where("resort_id",$this->resort->resort_id)->first();
        $GrievanceAppealDeadlineModel = GrievanceAppealDeadlineModel::where("resort_id",$this->resort->resort_id)->first();
       $GrivanceResoultionTimeLineModel =  GrivanceResoultionTimeLineModel::where("resort_id",$this->resort->resort_id)->first();
        // End of Greivance

        $KeyPerson = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                ->where("t1.resort_id",$this->resort->resort_id)
                ->whereIn('employees.rank',[1,2,3,8])
                ->get(['t1.id as Admin_id','t1.first_name','t1.last_name','t1.profile_picture','employees.*']);

        return view('resorts.GrievanceAndDisciplinery.configuration.index',compact('GrivanceResoultionTimeLineModel','GrivanceKeys','GrievanceAppealDeadlineModel','GrievanceNonRetaliation','GrievanceRightToBeAccompanied','GrievanceCategory','RightToBeAccompanied','Committee','OtherMembers','DisciplinaryAppeal','InvestingHearingTempleteModel','ApprovalRoles','page_title','DisciplinaryCategories','Offenses','ActionStore','SeverityStore','CommitteeMembers','KeyPerson'));
    }
    public function IndexDisciplineryCategory(Request $request)
    {

        $page_title="Discriplinery Category ";
        if($request->ajax())
        {
            $DisciplinaryCategoriesModel= DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($DisciplinaryCategoriesModel)
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
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.Disciplineryindex',compact('page_title'));
    }
    public function StoreDisciplineryCategory(Request $request)
    {

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'DisciplinaryCategoryName' => [
                'required',
                'max:50',
                Rule::unique('disciplinary_categories_models')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
        ], [
            'DisciplinaryCategoryName.required' => 'The Category Name field is required. Please write something.',
            'DisciplinaryCategoryName.unique' => 'The Category Name already exists for this resort.',
            'DisciplinaryCategoryName.max' => 'The maximum allowed length for the Category Name is 50 characters.',
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
            DisciplinaryCategoriesModel::create(['resort_id'=>$this->resort->resort_id,"DisciplinaryCategoryName"=>$request->DisciplinaryCategoryName,"description"=>$request->description]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create  Category'], 500);
        }
    }
    public function DisciplineryCategoryinlineUpdate(Request $request,$id)
    {

        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'DisciplinaryCategoryName' => [
                'required',
                'max:50',
                Rule::unique('disciplinary_categories_models')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'DisciplinaryCategoryName.required' => 'The Category Name field is required. Please write something.',
            'DisciplinaryCategoryName.unique' => 'The Category Name already exists for this resort.',
            'DisciplinaryCategoryName.max' => 'The maximum allowed length for the Category Name is 50 characters.',
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
            DisciplinaryCategoriesModel::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'DisciplinaryCategoryName' => $request->DisciplinaryCategoryName,
                'description' => $request->description
            ]);

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
    public function DisciplineryCategoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            DisciplinaryCategoriesModel::where("id",$id)->delete();

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
    public function IndexOffenses(Request $request)
    {

        $page_title="Discriplinery Offenses";
        if($request->ajax())
        {
            $DisciplinaryCategoriesModel= OffensesModel::with('disciplinaryCategory')->where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($DisciplinaryCategoriesModel)
            ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-disciplineryid="'.$row->disciplinary_cat_id.'" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('disciplinary_cat_id', function ($row) {

                return $row->disciplinaryCategory->DisciplinaryCategoryName ?? '';
            })

            ->rawColumns(['action'])
            ->make(true);
        }
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();

        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexOffenses',compact('DisciplinaryCategories','page_title'));
    }
    public function StoreOffenses(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'OffensesName' => [
                'required',
                'max:50',
                Rule::unique('offenses_models')->where(function ($query) use ($resort_id, $request) {
                    return $query->where('resort_id', $resort_id)
                                ->where('disciplinary_cat_id', $request->disciplinary_cat_id);
                })
            ],
            'disciplinary_cat_id' => 'required'  // Added validation for disciplinary_cate
        ], [
            'disciplinary_cat_id.required' => 'Please Select Disciplinary Category.',
            'OffensesName.required' => 'The Offenses Name field is required. Please write something.',
            'OffensesName.unique' => 'The Offenses Name already exists for this resort.',
            'OffensesName.max' => 'The maximum allowed length for the Offenses Name is 50 characters.',
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
            OffensesModel::create(['disciplinary_cat_id'=>$request->disciplinary_cat_id,'resort_id'=>$this->resort->resort_id,"OffensesName"=>$request->OffensesName,"offensesdescription"=>$request->offensesdescription]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Offenses Created Successfully',
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
    public function OffensesinlineUpdate(Request $request,$id)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;


        $validator = Validator::make($request->all(), [
            'OffensesName' => [
                'required',
                'max:50',
                Rule::unique('offenses_models')->where(function ($query) use ($resort_id, $Main_id,$request) {
                    return $query->where('resort_id', $resort_id)
                                ->where('disciplinary_cat_id', $request->disciplinary_cat_id);
                })->ignore($Main_id),
            ],
            'disciplinary_cat_id' => 'required'  // Added validation for disciplinary_cate
        ], [
            'disciplinary_cat_id.required' => 'Please Select Disciplinary Category.',
            'OffensesName.required' => 'The Offenses Name field is required. Please write something.',
            'OffensesName.unique' => 'The Offenses Name already exists for this resort.',
            'OffensesName.max' => 'The maximum allowed length for the Offenses Name is 50 characters.',
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
            OffensesModel::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'disciplinary_cat_id'=>$request->disciplinary_cat_id,
                'OffensesName' => $request->OffensesName,
                'offensesdescription' => $request->offensesdescription
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Offenses Updated Successfully',
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
    public function OffensesDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            OffensesModel::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Offenses Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Offenses'], 500);
        }
    }

    // ActionStore
    public function IndexAction(Request $request)
    {

        $page_title="Action";
        if($request->ajax())
        {
            $ActionStore= ActionStore::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($ActionStore)
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
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.Actionindex',compact('page_title'));
    }
    public function ActionStore(Request $request)
    {

        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'ActionName' => [
                'required',
                'max:50',
                Rule::unique('action_stores')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
        ], [
            'ActionName.required' => 'The Action Name field is required. Please write something.',
            'ActionName.unique' => 'The Action Name already exists for this resort.',
            'ActionName.max' => 'The maximum allowed length for the Action Name is 50 characters.',
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
            ActionStore::create(['resort_id'=>$this->resort->resort_id,"ActionName"=>$request->ActionName,"description"=>$request->description]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Action Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Action'], 500);
        }
    }

    public function ActioninlineUpdate(Request $request,$id)
    {

        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'ActionName' => [
                'required',
                'max:50',
                Rule::unique('action_stores')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'ActionName.required' => 'The Action Name field is required. Please write something.',
            'ActionName.unique' => 'The Action Name already exists for this resort.',
            'ActionName.max' => 'The maximum allowed length for the Action Name is 50 characters.',
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
            ActionStore::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'ActionName' => $request->ActionName,
                'description' => $request->description
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Action Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Action'], 500);
        }
    }
    public function ActionDestory($id)
    {


        $id = base64_decode($id);


        DB::beginTransaction();
        try
        {
            ActionStore::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Action Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Offenses'], 500);
        }
    }

    public function IndexSeverity(Request $request)
    {

        $page_title="Severity";
        if($request->ajax())
        {
            $ActionStore= SeverityStore::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($ActionStore)
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
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexSeverity',compact('page_title'));
    }
    public function SeverityStore(Request $request)
    {

        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'SeverityName' => [
                'required',
                'max:50',
                Rule::unique('severity_stores')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
        ], [
            'SeverityName.required' => 'The Severity Name field is required. Please write something.',
            'SeverityName.unique' => 'The Severity Name already exists for this resort.',
            'SeverityName.max' => 'The maximum allowed length for the Severity Name is 50 characters.',
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
            SeverityStore::create(['resort_id'=>$this->resort->resort_id,"SeverityName"=>$request->SeverityName,"description"=>$request->description]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Severity Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Severity'], 500);
        }
    }
    public function SeverityInlineUpdate(Request $request,$id)
    {
        $Main_id = (int) base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'SeverityName' => [
                'required',
                'max:50',
                Rule::unique('severity_stores')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'SeverityName.required' => 'The Severity Name field is required. Please write something.',
            'SeverityName.unique' => 'The Severity Name already exists for this resort.',
            'SeverityName.max' => 'The maximum allowed length for the Severity Name is 50 characters.',
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
            SeverityStore::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'SeverityName' => $request->SeverityName,
                'description' => $request->description
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Severity Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Action'], 500);
        }
    }
    public function SeverityDestory($id)
    {


        $id = base64_decode($id);


        DB::beginTransaction();
        try
        {
            SeverityStore::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Severity Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Severity'], 500);
        }
    }
    public function GetCategoryOffenses(Request $request)
    {
        try
        {
            $id = base64_decode($request->id);
            $OffensesModel = OffensesModel::where("disciplinary_cat_id",$id)
                                                            ->where('resort_id',$this->resort->resort_id)
                                                            ->get();
            return response()->json([
                'success' => true,
                'data' => $OffensesModel,
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
        }
    }

    public function CodeOfCounduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Deciplinery_cat_id' => 'required|array|min:1',
            'Deciplinery_cat_id.*' => 'required|string',
            'Offenses_id' => 'required|array|min:1',
            'Offenses_id.*' => 'required|string',
            'Action_id' => 'required|array|min:1',
            'Action_id.*' => 'required|string',
            'Severity_id' => 'required|array|min:1',
            'Severity_id.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $resort_id = $this->resort->resort_id;
        foreach ($request->Deciplinery_cat_id as $key => $Deciplinery_cat_id)
        {
            $Deciplinery_cat_id = base64_decode($Deciplinery_cat_id) ?? null;
            $offense = $request->Offenses_id[$key] ?? null;
            $action = base64_decode($request->Action_id[$key]) ?? null;
            $Severity_id = base64_decode($request->Severity_id[$key]) ?? null;

            $exists = CodeOfCounduct::where('resort_id', $resort_id)
                                    ->where('Deciplinery_cat_id', $Deciplinery_cat_id)
                                    ->where('Offenses_id', $offense)
                                    ->where('Action_id', $action)
                                    ->where('Severity_id', $Severity_id)
                                    ->exists();



            if ($exists)
            {
                return response()->json([
                    'success' => false,
                    'error' => "The combination already exists for this resort.",
                    'failed_data' => [
                        'Deciplinery_cat_id' => $Deciplinery_cat_id,
                        'Offenses_id' => $request->Offenses_id[$key],
                        'Action_id' => $request->Action_id[$key],
                        'Severity_id' => $request->Severity_id[$key],
                    ]
                ], 422);
            }
        }
        DB::beginTransaction();
        try
        {
            foreach ($request->Deciplinery_cat_id as $key => $Deciplinery_cat_id)
            {
                CodeOfCounduct::create([
                    'resort_id' => $resort_id,
                    'Deciplinery_cat_id' => base64_decode($Deciplinery_cat_id),
                    'Offenses_id' => $request->Offenses_id[$key],
                    'Action_id' => base64_decode($request->Action_id[$key]),
                    'Severity_id' => base64_decode($request->Severity_id[$key]),
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Code of Counduct Create Successfully',
            ], 200);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
        }
    }
    public function IndexCodeOfCounduct(Request $request)
    {

        $page_title="Code Of Counduct";

        if($request->ajax())
        {
            $ActionStore = CodeOfCounduct::join('disciplinary_categories_models','disciplinary_categories_models.id','code_of_counducts.Deciplinery_cat_id')
            ->join('offenses_models','offenses_models.id','code_of_counducts.Offenses_id')
            ->join('action_stores','action_stores.id','code_of_counducts.Action_id')
            ->join('severity_stores','severity_stores.id','code_of_counducts.Severity_id')
            ->where('code_of_counducts.resort_id',$this->resort->resort_id)
            ->get([
                            'code_of_counducts.*',
                            'disciplinary_categories_models.DisciplinaryCategoryName as DisciplinaryCategoryName',
                            'offenses_models.OffensesName as OffensesCategoryName',
                            'action_stores.ActionName as ActionCategoryName',
                            'severity_stores.SeverityName as SeverityCategoryName'
                           ]);

            return datatables()->of($ActionStore)
                    ->addColumn('action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                data-Disciplinery_cat_id = "' . base64_encode($row->Deciplinery_cat_id) . '"
                                data-Offenses_id = "' . $row->Offenses_id . '"
                                data-Action_id = "' . base64_encode($row->Action_id) . '"
                                data-Severity_id = "' . base64_encode($row->Severity_id) . '"
                                data-Self_id = "' . $id . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-Self_id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('DisciplinaryCategoryName', function ($row) {
                return $row->DisciplinaryCategoryName;
            })
            ->addColumn('OffensesCategoryName', function ($row) {
                return $row->OffensesCategoryName;
            })
            ->addColumn('ActionName', function ($row) {
                return $row->ActionCategoryName;
            })
            ->addColumn('SeverityName', function ($row) {
                return $row->SeverityCategoryName;
            })
            ->rawColumns(['DisciplinaryCategoryName','OffensesCategoryName','ActionName','SeverityName','action'])
            ->make(true);
        }
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        $SeverityStore = SeverityStore::where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexCodeOfCounduct',compact('DisciplinaryCategories','ActionStore','SeverityStore','page_title'));
    }
    public function CodeOfCounductUpdate(Request $request)
    {
        $Self_id = base64_decode($request->Self_id);
        $validator = Validator::make($request->all(), [
            'Deciplinery_cat_id' => 'required|array|min:1',
            'Deciplinery_cat_id.*' => 'required|string',
            'Offenses_id' => 'required|array|min:1',
            'Offenses_id.*' => 'required|string',
            'Action_id' => 'required|array|min:1',
            'Action_id.*' => 'required|string',
            'Severity_id' => 'required|array|min:1',
            'Severity_id.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $resort_id = $this->resort->resort_id;

        foreach ($request->Deciplinery_cat_id as $key => $Deciplinery_cat_id)
        {
            $Deciplinery_cat_id = base64_decode($Deciplinery_cat_id) ?? null;
            $offense = $request->Offenses_id[$key] ?? null;
            $action = base64_decode($request->Action_id[$key]) ?? null;
            $Severity_id = base64_decode($request->Severity_id[$key]) ?? null;
            $exists = CodeOfCounduct::where('resort_id', $resort_id)
                                    ->where('Deciplinery_cat_id', $Deciplinery_cat_id)
                                    ->where('Offenses_id', $offense)
                                    ->where('Action_id', $action)
                                    ->where('Severity_id', $Severity_id)
                                    ->where('id', '!=', $Self_id) // Ignore the current record
                                    ->exists();
                                    if ($exists)
            {
                return response()->json([
                    'success' => false,
                    'error' => "The combination already exists for this resort.",
                    'failed_data' => [
                        'Deciplinery_cat_id' => $Deciplinery_cat_id,
                        'Offenses_id' => $request->Offenses_id[$key],
                        'Action_id' => $request->Action_id[$key],
                        'Severity_id' => $request->Severity_id[$key],
                    ]
                ], 422);
            }
        }
        DB::beginTransaction();
        try
        {
            foreach ($request->Deciplinery_cat_id as $key => $Deciplinery_cat_id)
            {

                CodeOfCounduct::where('id', $Self_id)
                ->where('resort_id', $resort_id)
                ->update([
                    'resort_id' => $resort_id,
                    'Deciplinery_cat_id' => base64_decode($Deciplinery_cat_id),
                    'Offenses_id' => $request->Offenses_id[$key],
                    'Action_id' => base64_decode($request->Action_id[$key]),
                    'Severity_id' => base64_decode($request->Severity_id[$key]),
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Code of Counduct Updated Successfully',
            ], 200);

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
        }
    }

    public function CodeOfConductDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            CodeOfCounduct::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Code Of Counduct Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Severity'], 500);
        }
    }

    // Disciplinary Approval Role
    public function DiscriplineryApprovalRole(Request $request)
    {


        DB::beginTransaction();
        try
        {
            DisciplinaryApprovalRoles::updateOrCreate(["resort_id"=>$this->resort->resort_id],['resort_id'=>$this->resort->resort_id,'Approval_role_id'=>$request->Approval_role_id]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ' Disciplinary Approval Role Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Disciplinary Approval Role Updated'], 500);
        }
    }

    public function LatterTemletestore(Request $request)
    {

        if($request->Templete_id == 0)
        {
            $validator = Validator::make($request->all(), [
                'Latter_Temp_name' => [
                    'required',
                    'max:50',
                    Rule::unique('disciplinery_latter_templetes')->where(function ($query) {
                        return $query->where('resort_id', $this->resort->resort_id);
                    }),
                ],
            ], [
                'Latter_Temp_name.required' => 'The Templete Name field is required. Please write something.',
                'Latter_Temp_name.unique' => 'The Templete Name already exists for this resort.',
                'Latter_Temp_name.max' => 'The Templetes allowed length for the Form Name is 50 characters.',
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
                DisciplineryLatterTemplete::create(['resort_id'=>$this->resort->resort_id,'Latter_Structure'=>json_encode($request->Latter_Structure),'Latter_Temp_name'=>$request->Latter_Temp_name]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Latter Templete Created Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Add Latter Templete  Updated'], 500);
            }
        }
        else
        {

            $validator = Validator::make($request->all(), [
                'Latter_Temp_name' => [
                    'required',
                    'max:50',
                    Rule::unique('disciplinery_latter_templetes')->where(function ($query) {
                        return $query->where('resort_id', $this->resort->resort_id);
                    })->ignore($request->Templete_id),
                ],
            ], [
                'Latter_Temp_name.required' => 'The Templete Name field is required. Please write something.',
                'Latter_Temp_name.unique' => 'The Templete Name already exists for this resort.',
                'Latter_Temp_name.max' => 'The Templetes allowed length for the Form Name is 50 characters.',
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
                DisciplineryLatterTemplete::where('id',$request->Templete_id)
                                            ->update(['resort_id'=>$this->resort->resort_id,
                                                    'Latter_Structure'=>json_encode($request->Latter_Structure),
                                                    'Latter_Temp_name'=>$request->Latter_Temp_name]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Latter Templete Templete Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to update Latter Templete'], 500);
            }
        }

    }
    public function IndexLatterTemplete(Request $request)
    {

        $page_title="Code Of Counduct";

        if($request->ajax())
        {
            $DisciplineryLatterTemplete = DisciplineryLatterTemplete::where('resort_id',$this->resort->resort_id)->get();

            return datatables()->of($DisciplineryLatterTemplete)
                    ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                            return '

                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-Self_id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>';

            })
            ->addColumn('LatterName', function ($row) {
                return $row->Latter_Temp_name;
            })
            ->addColumn('EditTemplete', function ($row) {
                $id = base64_encode($row->id);
                return '<a href="javascript:void(0)" class="a-linkTheme letterTemplates-edit" data-flag="edit" data-id="'. e($id).'" data-flag="edit" > Edit Latter Template</a>';
            })
            ->addColumn('CreateNewTemplate', function ($row) {
                return '<a href="javascript:void(0)" class="a-link letterTemplates-add" data-type="Latter" data-flag="new"> Create New Template</a>';
            })

            ->rawColumns(['CreateNewTemplate','EditTemplete','Action'])
            ->make(true);
        }
        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();
        $ActionStore = ActionStore::where('resort_id',$this->resort->resort_id)->get();
        $SeverityStore = SeverityStore::where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexCodeOfCounduct',compact('DisciplinaryCategories','ActionStore','SeverityStore','page_title'));
    }
    public function LatterTempleteEdit($id)
    {
        $id = (int)base64_decode($id);
        $d = DisciplineryLatterTemplete::find($id);
        $form_structure = json_decode($d->Latter_Structure, true);
        $data=[$form_structure,$d->Latter_Temp_name,$d->id];
        return response()->json([
            'success' => true,
            'data' =>$data,
        ], 200);

    }

    public function LatterTempleteDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            DisciplineryLatterTemplete::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Latter Templete Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Latter Templete'], 500);
        }
    }

    // Disciplinary Committees and Assigned Members
    public function DisciplinaryCommittees(Request $request)
    {
        // $request->merge([
        //     'MemberIds' => collect($request->MemberIds)->flatten()->toArray()
        // ]);
        $resortId = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'CommitteeName' => ['required', 'array', 'min:1'],
            'CommitteeName.*' => ['required', 'array', 'min:1'],
            'CommitteeName.*.*' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    preg_match('/CommitteeName\.(\d+)/', $attribute, $matches);
                    if (!empty($matches))
                    {
                        $index = $matches[1];
                        $exists = DisciplineryAssignCommittee::where('resort_id', $this->resort->resort_id)
                            ->where('CommitteeName', $value)
                            ->exists();
                        if ($exists) {
                            $fail("The committee name '$value' already exists in this resort.");
                        }
                    }
                }
            ],
        ], [
            'CommitteeName.required' => 'Please provide at least one committee name.',
            'CommitteeName.*.required' => 'Each committee name is required.',
            'CommitteeName.*.*.max' => 'Each committee name must not exceed 50 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ],
            422);
        }
            DB::beginTransaction();
            try
            {
                $currentDate= date('Y-m-d');
                $count=1;
                foreach ($request->CommitteeName as $key => $committee)
                {
                    $newCommittee = DisciplineryAssignCommittee::create([
                                        'resort_id' => $this->resort->resort_id,
                                        'CommitteeName' => $committee[0],
                                        'date'=>$currentDate,
                                    ]);
                    foreach ($request->MemberId[$count] as $member) {
                                DisciplineryCommitteeMembers::create([
                                    'Parent_committee_id' => $newCommittee->id,
                                    'MemberId' => $member,
                                ]);
                            }


                            $count++;
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Committee Created Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Create Committee'], 500);
            }
    }

    public function IndexDisciplinaryCommittees(Request $request)
    {

        $page_title="Disciplinary Committees";

        if($request->ajax())
        {
            $DisciplineryAssignCommittee = DisciplineryAssignCommittee::where('resort_id',$this->resort->resort_id)->get();

            return datatables()->of($DisciplineryAssignCommittee)
                    ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                $child = DisciplineryCommitteeMembers::where('Parent_committee_id',$row->id)->get('MemberId');
                $members=[];
                foreach($child as $c)
                {
                    $members[]=base64_encode($c->MemberId);
                }

                return '
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-AssignCommittee"
                            data
                            data-date= "'.date('d-m-Y',strtotime($row->date)).'"  data-committeename="'.$row->CommitteeName.'" data-members="'.implode(",",$members).'"data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-Self_id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>';

            })
            ->addColumn('CommitteeName', function ($row) {
                return ucfirst($row->CommitteeName);
            })

            ->addColumn('CommiteeMembers', function ($row) {

                $child = DisciplineryCommitteeMembers::join('employees as t2' ,'t2.id',"=","disciplinery_committee_members.MemberId")
                        ->join('resort_admins as t1',"t1.id","=","t2.Admin_Parent_id")
                        ->where("t1.resort_id",$this->resort->resort_id)
                        ->where('disciplinery_committee_members.Parent_committee_id',$row->id)
                        ->get(['t1.first_name','t1.last_name','t1.profile_picture']);
                $names='';
                foreach($child as $c)
                {
                    $names.= $c->first_name.'  ' .$c->last_name. "<br>";
                }
                return $names;
            })
            ->addColumn('date', function ($row) {
                return date('d-m-Y',strtotime($row->date));
            })
            ->rawColumns(['CreateNewTemplate','date','CommiteeMembers','Action'])
            ->make(true);
        }
        $CommitteeMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
        ->where("t1.resort_id",$this->resort->resort_id)
        ->whereIn('employees.rank',[1,2,3,8])
        ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);

        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexDisciplinaryCommittees',compact('CommitteeMembers','page_title'));

    }

    public function CommitteeinlineUpdate(Request $request)
    {
        $id = base64_decode($request->Main_id);
        $CommitteeName = $request->CommitteeName;
        $assign_members= $request->assign_members;
        $validator = Validator::make($request->all(), [
            'CommitteeName' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $exists = DisciplineryAssignCommittee::where('resort_id', $this->resort->resort_id)
                        ->where('CommitteeName', $value)
                        ->where('id', '!=', $id) // Exclude the current record from the uniqueness check
                        ->exists();

                    if ($exists) {
                        $fail("The committee name '$value' already exists in this resort.");
                    }
                }
            ],
        ], [
            'CommitteeName.required' => 'Please provide at least one committee name.',
            'CommitteeName.max' => 'Committee name must not exceed 50 characters.',
        ]);
        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ],
            422);
        }
        DB::beginTransaction();
            try
            {

                    $newCommittee = DisciplineryAssignCommittee::where('id',$id)->update([
                                    'CommitteeName' => $CommitteeName
                                ]);

                if( count($assign_members)> 0)
                {

                    DisciplineryCommitteeMembers::where("Parent_committee_id",$id)->delete();

                    foreach($assign_members as $m)
                    DisciplineryCommitteeMembers::create([
                        'Parent_committee_id' =>  $id,
                        'MemberId' => $m,
                    ]);
                }
            DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Committee Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Updated Committee'], 500);
            }

    }
    public function DisciplinaryCommitteesDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            DisciplineryCommitteeMembers::where("Parent_committee_id",$id)->delete();
            DisciplineryAssignCommittee::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ' Disciplinary Committee Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Disciplinary Committee'], 500);
        }
    }

    public function InvestingHearingTempleteStore(Request $request)
    {

        $Hearing_Temp_name = $request->Hearing_Temp_name;
        $Hearing_Temp_Structure = $request->Hearing_Temp_Structure;
        $HearingIdtemplete = $request->HearingIdtemplete;
        $validator = Validator::make($request->all(), [
            'Hearing_Temp_name' => [
                'required',
                'max:50',
                Rule::unique('investing_hearing_templete_models')->where(function ($query)use($HearingIdtemplete ) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })->ignore($HearingIdtemplete ),
            ],
        ], [
            'Hearing_Temp_name.required' => 'The Templete Name field is required. Please write something.',
            'Hearing_Temp_name.unique' => 'The Templete Name already exists for this resort.',
            'Hearing_Temp_name.max' => 'The Templetes allowed length for the Form Name is 50 characters.',
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


            if($HearingIdtemplete ==0)
            {
               $investigationTemplteModel = new InvestingHearingTempleteModel;
               $investigationTemplteModel->resort_id =$this->resort->resort_id;
               $msg = 'Created';
            }
            else
            {
               $msg = 'Update';
            $investigationTemplteModel =  InvestingHearingTempleteModel::where('id',$HearingIdtemplete)->first();
            }
            $investigationTemplteModel->Hearing_Temp_name = $Hearing_Temp_name;
            $investigationTemplteModel->Hearing_Temp_Structure = $Hearing_Temp_Structure;
            $investigationTemplteModel->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '  Investing Hearing Templete '.$msg.'  Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Investing Hearing '], 500);
        }

    }

    public function DelegationRulesStore(Request $request)
    {


        $validator = Validator::make($request->all(), [
            // Validate `Del_cat_id[]` (must be an array with at least one value)
            'Del_cat_id'   => ['required', 'array', 'min:1'],
            'Del_cat_id.*' => [
                'required',
                'string', // Assuming IDs are stored as Base64 strings
                function ($attribute, $value, $fail) use ($request) {
                    $decodedValue = base64_decode($value, true); // Decode Base64 safely
                    if (!$decodedValue) {
                        $fail("Invalid category ID format.");
                    }

                    // Extract index from `Del_cat_id.*`
                    preg_match('/Del_cat_id\.(\d+)/', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    // Ensure `Del_Rule` has the same index
                    $Del_Rule = $request->Del_Rule[$index] ?? null;

                    if (!$Del_Rule) {
                        $fail("A corresponding rule is required for category ID '$decodedValue'.");
                    }

                    // Check if combination of Del_cat_id and Del_Rule already exists
                    $exists = DisciplinaryDelegationRule::where('resort_id', $this->resort->resort_id)
                        ->where('Del_cat_id', $decodedValue)
                        ->where('Del_Rule', $Del_Rule)
                        ->exists();

                    if ($exists) {
                        $fail("The category ID '$decodedValue' with rule '$Del_Rule' already exists for this resort.");
                    }
                }
            ],
            'Del_Rule'   => ['required', 'array', 'min:1'],
            'Del_Rule.*' => [
                'required',
                'string',
                'max:50'
            ]
        ], [
            'Del_cat_id.required' => 'At least one Category ID is required.',
            'Del_cat_id.*.required' => 'Each Category ID must be provided.',
            'Del_Rule.required' => 'At least one Rule Name is required.',
            'Del_Rule.*.required' => 'Each Rule Name must be provided.',
            'Del_Rule.*.max' => 'The maximum allowed length for a Rule Name is 50 characters.',
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
            foreach($request->Del_cat_id as $ak=>$d)
            {
                $Del_Rule = array_key_exists($ak,$request->Del_Rule) ? $request->Del_Rule[$ak]:'';
                DisciplinaryDelegationRule::create([
                    'resort_id' => $this->resort->resort_id,
                    'Del_cat_id' => base64_decode($d),
                    'Del_Rule' => $Del_Rule,
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Disciplinary Delegation Rules Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Investing Hearing '], 500);
        }


    }


    public function KeyPersonnel(Request $request)
    {

            if(!empty($request->KeyPersonnel))
            {
                GrivanceKeyPerson::where("resort_id",$this->resort->resort_id)->delete();
                foreach($request->KeyPersonnel as $k)
                {

                    GrivanceKeyPerson::create([
                        "resort_id"=>$this->resort->resort_id,
                        "emp_ids"=>$k
                    ]);
                }

            }
            DB::beginTransaction();
            try
            {    DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Key Personnel Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Investing Hearing '], 500);
        }
    }
    // DisciplinaryDelegationRule
    public function IndexDisciplinaryDelegationRule(Request $request)
    {

        $page_title="Disciplinary Delegation Rule";

        if($request->ajax())
        {
            $DisciplineryAssignCommittee = DisciplinaryDelegationRule::join('disciplinary_categories_models as t1',"t1.id","=","disciplinary_delegation_rules.Del_cat_id")
                                                                        ->where('disciplinary_delegation_rules.resort_id',$this->resort->resort_id)
                                                                        ->get(['t1.DisciplinaryCategoryName','disciplinary_delegation_rules.*']);
            return datatables()->of($DisciplineryAssignCommittee)
                    ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);

                return ' <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                            data
                            data-date= "'.date('d-m-Y',strtotime($row->date)).'" data-del_cat_id="'.base64_encode($row->Del_cat_id).'" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-Self_id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>';
            })
            ->addColumn('CategoryName', function ($row) {
                return $row->DisciplinaryCategoryName;
            })
            ->addColumn('RuleName', function ($row) {
                return $row->Del_Rule;
            })
            ->rawColumns(['CategoryName','RuleName','Action'])
            ->make(true);
        }

        $DisciplinaryCategories = DisciplinaryCategoriesModel::where('resort_id',$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configuration.disciplinery.IndexDisciplinaryDelegationRule',compact('DisciplinaryCategories','page_title'));

    }

    public function DisciplineryDeletgationRuleinlineUpdate(Request $request,$id)
    {

        $Main_id = (int) base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'Del_cat_id' => [
                'required',
                'string', // Assuming it's stored as a Base64 encoded string
                function ($attribute, $value, $fail) use ($request, $Main_id) {
                    $decodedValue = base64_decode($value, true); // Decode Base64 safely
                    if (!$decodedValue) {
                        $fail("Invalid category ID format.");
                    }

                    $Del_Rule = $request->Del_Rule ?? null;

                    if (!$Del_Rule) {
                        $fail("A corresponding rule is required for the category.");
                    }

                    // Ensure the record does not already exist except for the current ID being updated
                    $exists = DisciplinaryDelegationRule::where('resort_id', $this->resort->resort_id)
                        ->where('Del_cat_id', $decodedValue)
                        ->where('Del_Rule', $Del_Rule)
                        ->where('id', '!=', $Main_id) // Ignore the current record
                        ->exists();

                    if ($exists) {
                        $DisciplinaryCategories = DisciplinaryCategoriesModel::where("id",$decodedValue)->where('resort_id',$this->resort->resort_id)->first();
                        $fail("The category ID '$DisciplinaryCategories->DisciplinaryCategoryName' with rule '$Del_Rule' already exists for this resort.");
                    }
                }
            ],
            'Del_Rule' => [
                'required',
                'string',
                'max:50'
            ]
        ], [
            'Del_cat_id.required' => 'The Category ID is required.',
            'Del_Rule.required' => 'The Rule Name is required.',
            'Del_Rule.max' => 'The maximum allowed length for the Rule Name is 50 characters.',
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
            DisciplinaryDelegationRule::where('resort_id', $this->resort->resort_id)
            ->where('id', $Main_id)
            ->update([
                'Del_cat_id' => base64_decode($request->Del_cat_id),
                'Del_Rule' => $request->Del_Rule
            ]);


            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Delegation Rule Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Delegation Rule'], 500);
        }
    }

    public function DisciplineryDeletegationRuleDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            DisciplinaryDelegationRule::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Delegation Rule Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Latter Templete'], 500);
        }
    }
    // DisciplinaryAppealStore

    public function DisciplinaryAppealStore(Request $request)
    {
       $AppealDeadLine          = $request->AppealDeadLine;
       $Appeal_Type             = $request->Appeal_Type;
       $MemberId_or_CommitteeId = $request->MemberId_or_CommitteeId;

       $validator = Validator::make($request->all(), [
            'AppealDeadLine' => 'required',
            'Appeal_Type' => 'required',
            'MemberId_or_CommitteeId' => 'required',
        ], [
            'AppealDeadLine.required' => 'The Appeal DeadLine field is required. Please write something.',
            'Appeal_Type.required' => 'The Appeal Type field is required. Please write something.',
            'MemberId_or_CommitteeId.required' => 'The MemberId or CommitteeId field is required. Please write something.',
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
            DisciplinaryAppealModel::updateOrCreate(
        ["resort_id"=>$this->resort->resort_id],
            ["resort_id",$this->resort->resort_id,
                    "AppealDeadLine"=>$AppealDeadLine,
                    "Appeal_Type"=>$Appeal_Type,
                    "MemberId_or_CommitteeId"=>$MemberId_or_CommitteeId]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Disciplinary Appeal Created Successfully',
            ],200);
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Disciplinary Appeal'], 500);
        }

    }
    public function DisciplineryAppealTypeWiseData(Request $request)
    {
        $Committee='';
        $OtherMembers='';

        if($request->Appeal_type == "Committee")
        {
            $Committee = DisciplineryAssignCommittee::where('resort_id',$this->resort->resort_id)->get();
        }
        else
        {
            $OtherMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                            ->where("t1.resort_id",$this->resort->resort_id)
                            ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'Committee' => $Committee,
                'OtherMembers' => $OtherMembers,
                'Type'=>$request->Appeal_type,
            ]
        ], 200);
    }

    public function RightToBeAccompanied(Request $request)
    {
       DB::beginTransaction();
       try
       {
            RightToBeAccompanied::updateOrCreate(["resort_id"=>$this->resort->resort_id],['resort_id'=>$this->resort->resort_id,'RightToBeAccompanied'=>$request->RightToBeAccompanied]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Right To Be Accompanied Created or Updated Successfully',
            ],200);
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Created or Updated Right To Be Accompanied'], 500);
        }
    }

    // End Disciplinary Configuration
    // Start Grievance Configuration
    public function IndexGrievanceCategory(Request $request)
    {
        $page_title="Grievance Category";
        if($request->ajax())
        {
            $GrievanceCategory= GrievanceCategory::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceCategory)
            ->addColumn('Action', function ($row) {
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
            ->rawColumns(['Action'])
            ->make(true);
        }
        return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrievanceCategory',compact('page_title'));
    }

    public function GrievanceCategoryStore(Request $request)
    {

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'Category_Name' => [
                'required',
                'max:50',
                Rule::unique('grievance_categories')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })
            ],
        ], [
            'Category_Name.required' => 'The Category Name field is required. Please write something.',
            'Category_Name.unique' => 'The Category Name already exists for this resort.',
            'Category_Name.max' => 'The maximum allowed length for the Category Name is 50 characters.',
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
            GrievanceCategory::create(['resort_id'=>$this->resort->resort_id,"Category_Name"=>$request->Category_Name,"Category_Description"=>$request->Category_Description]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create  Category'], 500);
        }
    }
    public function GrievanceCategorinlineUpdate(Request $request)
    {
        $Main_id = (int) base64_decode($request->Main_id);

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'Category_Name' => [
                'required',
                'max:50',
                Rule::unique('grievance_categories')->where(function ($query) use ($resort_id,$Main_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore( $Main_id),
            ],
        ], [
            'Category_Name.required' => 'The Category Name field is required. Please write something.',
            'Category_Name.unique' => 'The Category Name already exists for this resort.',
            'Category_Name.max' => 'The maximum allowed length for the Category Name is 50 characters.',
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
                GrievanceCategory::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'Category_Name' => $request->Category_Name,
                    'Category_Description' => $request->Category_Description
                ]);

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
                return response()->json(['error' => 'Failed to Updated  Category '], 500);
            }
    }
    public function GrievanceCategoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            GrievanceCategory::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Category Rule Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Grievance Category'], 500);
        }
    }


    public function GrievanceSubCategoryStore(Request $request)
    {

        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'Sub_Category_Name' => [
                'required',
                'max:50',
                Rule::unique('grievance_subcategories')->where(function ($query) use ($resort_id, $request) {
                    return $query->where('resort_id', $resort_id)
                                ->where('Grievance_Cat_id', $request->Grievance_Cat_id);
                })
            ],
            'Grievance_Cat_id' => 'required'
        ], [
            'Grievance_Cat_id.required' => 'Please Select Grievance Category.',
            'Sub_Category_Name.required' => 'The Sub Category Name field is required. Please write something.',
            'Sub_Category_Name.unique' => 'The Sub Category Name already exists for this resort.',
            'Sub_Category_Name.max' => 'The maximum allowed length for the Sub Category Name is 50 characters.',
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
            GrievanceSubcategory::create(['resort_id'=>$this->resort->resort_id,"Grievance_Cat_id"=>$request->Grievance_Cat_id,"Sub_Category_Name"=>$request->Sub_Category_Name]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sub Category Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create  Sub Category'], 500);
        }
    }

    public function IndexGrievanceSubCategory(Request $request)
    {
        $page_title="Grievance Sub Category";
        if($request->ajax())
        {
            $GrievanceSubcategory= GrievanceSubcategory::with('category')->where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceSubcategory)
            ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                data-Grievance_Cat_id ="'.$row->Grievance_Cat_id.'"
                                data-cat-id="' . e($id) . '"
                                data-Sub_Category_Name="'.$row->Sub_Category_Name.'"
                                >
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('Grievance_Cat_id', function ($row) {

                            return $row->category->Category_Name;
            })
            ->rawColumns(['Action'])
            ->make(true);
        }
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();

        return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrievanceSubCategory',compact('GrievanceCategory','page_title'));
    }


    public function GrievanceSubCategorinlineUpdate(Request $request)
    {

        $Main_id = (int) base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'Sub_Category_Name' => [
                'required',
                'max:50',
                Rule::unique('grievance_subcategories')->where(function ($query) use ($resort_id, $Main_id,$request) {
                    return $query->where('resort_id', $resort_id)
                                ->where('Grievance_Cat_id', $request->Grievance_Cat_id);
                })->ignore($Main_id),
            ],
            'Grievance_Cat_id' => 'required'  // Added validation for disciplinary_cate
        ], [
            'Grievance_Cat_id.required' => 'Please Select Grievance Category.',
            'Sub_Category_Name.required' => 'The Sub Category  Name field is required. Please write something.',
            'Sub_Category_Name.unique' => 'The Sub Category  Name already exists for this resort.',
            'Sub_Category_Name.max' => 'The maximum allowed length for the Sub Category Name is 50 characters.',
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
                GrievanceSubcategory::where('resort_id', $this->resort->resort_id)
                ->where('id', $Main_id)
                ->update([
                    'Grievance_Cat_id' => $request->Grievance_Cat_id,
                    'Sub_Category_Name' => $request->Sub_Category_Name,
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Sub Category Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Updated  Sub Category '], 500);
            }
    }
    public function GrievanceSubCategoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            GrievanceSubcategory::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Sub Category Rule Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  GrievanceSub Category'], 500);
        }
    }
    public function GrievanceCategoryWiseSubCategoryData(Request $request)
    {
        $Grievance_Cat_idMain  = $request->Grievance_Cat_idMain;

        $GrievanceSubcategory  = GrievanceSubcategory::where("Grievance_Cat_id",$Grievance_Cat_idMain)->get();
        return response()->json([
            'success' => true,
            'data' => [
                'GrievanceSubcategory' => $GrievanceSubcategory,
            ]
        ], 200);

    }
    public function GrievanceCatAndSubCategoryStore(Request $request)
    {


        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id' => ['required', 'array', 'min:1'],
            'Grievance_Cat_id.*' => ['required', 'distinct'],

            'Gri_Sub_cat_id' => ['required', 'array', 'min:1'],
            'Gri_Sub_cat_id.*' => ['required', 'distinct'],

            'priority_level' => ['required', 'array', 'min:1'],
            'priority_level.*' => ['required', 'string', 'in:High,Medium,Low'],

            // Custom Rule to ensure uniqueness for each Category-Subcategory pair
        ], [
            'Grievance_Cat_id.required' => 'Please select at least one Grievance Category.',
            'Grievance_Cat_id.*.required' => 'Each Grievance Category is required.',
            'Grievance_Cat_id.*.distinct' => 'Duplicate Grievance Categories are not allowed.',

            'Gri_Sub_cat_id.required' => 'Please select at least one Grievance Subcategory.',
            'Gri_Sub_cat_id.*.required' => 'Each Grievance Subcategory is required.',
            'Gri_Sub_cat_id.*.distinct' => 'Duplicate Grievance Subcategories are not allowed.',

            'priority_level.required' => 'Please select at least one Priority Level.',
            'priority_level.*.required' => 'Each Priority Level is required.',
            'priority_level.*.in' => 'Priority Level must be High, Medium, or Low.',
        ]);

        $validator->after(function ($validator) use ($request, $resort_id) {
            foreach ($request->Grievance_Cat_id as $index => $categoryId) {
                $subcategoryId = $request->Gri_Sub_cat_id[$index] ?? null;

                if ($subcategoryId) {
                    $exists = DB::table('grievance_category_and_subcat_models')
                        ->where('resort_id', $resort_id)
                        ->where('Grievance_Cat_id', $categoryId)
                        ->where('Gri_Sub_cat_id', $subcategoryId)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add("Grievance_Cat_id.$index", "This category and subcategory combination already exists for this resort.");
                    }
                }
            }
        });


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            foreach($request->Grievance_Cat_id as $ak=>$g)
                {

                    GrievanceCategoryAndSubcatModel::create(
                        [
                            'resort_id'=>$resort_id,
                            'Grievance_Cat_id'=>$g,
                            'Gri_Sub_cat_id'=>$request->Gri_Sub_cat_id[$ak],
                            'priority_level'=>$request->priority_level[$ak]
                        ]
                    );
                }



            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Category And Sub Category Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Grievance Category And Sub Category'], 500);
        }
    }
    public function IndexGrievanceCatAndSubCategory(Request $request)
    {
        $page_title="Grievance Category And Sub Category";
        if($request->ajax())
        {
            $GrievanceSubcategory= GrievanceCategoryAndSubcatModel::with(['category', 'subcategory'])->where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceSubcategory)
            ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                                return '
                                <div  class="d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                    data-Grievance_Cat_id ="'.$row->Grievance_Cat_id.'"
                                    data-cat-id="' . e($id) . '"
                                    data-Gri_Sub_cat_id="'.$row->Gri_Sub_cat_id.'"
                                     data-Priority_Level="'.$row->Priority_Level.'"

                                    >
                                        <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                    </a>
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                        <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                    </a>
                                </div>';
                })
                ->addColumn('Grievance_Cat_id', function ($row)
                {
                                return $row->category->Category_Name;
                })
                ->addColumn('Gri_Sub_cat_id', function ($row)
                {
                                return $row->subcategory->Sub_Category_Name;
                })
                ->rawColumns(['Action'])
                ->make(true);
        }
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();
        $GrievanceSubcategory = GrievanceSubcategory::where("resort_id",$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrievanceCatAndSubCategory',compact('GrievanceCategory','GrievanceSubcategory','GrievanceCategory','page_title'));
    }
    public function GrievanceCatAndSubCategoryinlineUpdate(Request $request)
    {
        $Main_id           = base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id' => ['required', 'integer', 'exists:grievance_categories,id'],
            'Gri_Sub_cat_id' => ['required', 'integer', 'exists:grievance_subcategories,id'],
            'Priority_Level' => ['required', 'string', 'in:High,Medium,Low'],
        ], [
            'Grievance_Cat_id.required' => 'Please select a Grievance Category.',
            'Grievance_Cat_id.exists' => 'Invalid Grievance Category selected.',
            'Gri_Sub_cat_id.required' => 'Please select a Grievance Subcategory.',
            'Gri_Sub_cat_id.exists' => 'Invalid Grievance Subcategory selected.',
            'Priority_Level.required' => 'Please select a Priority Level.',
            'Priority_Level.in' => 'Priority Level must be High, Medium, or Low.',
        ]);
        $validator->after(function ($validator) use ($request, $resort_id) {
            $exists = DB::table('grievance_category_and_subcat_models')
                ->where('resort_id', $resort_id)
                ->where('Grievance_Cat_id', $request->Grievance_Cat_id)
                ->where('Gri_Sub_cat_id', $request->Gri_Sub_cat_id)
                ->where('id', '!=', base64_decode($request->Main_id)) // Exclude the current record from check
                ->exists();
            if($exists)
            {
                $validator->errors()->add('Grievance_Cat_id', "This category and subcategory combination already exists for this resort.");
            }
        });
        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        try
        {
                   GrievanceCategoryAndSubcatModel::where('resort_id',$resort_id)
                                        ->where('id',base64_decode($request->Main_id))
                                        ->update([
                                                            'resort_id'=>$resort_id,
                                                            'Grievance_Cat_id'=>$request->Grievance_Cat_id,
                                                            'Gri_Sub_cat_id'=>$request->Gri_Sub_cat_id,
                                                            'priority_level'=>$request->Priority_Level
                                                        ]);


        DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Category And Sub Category Update Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Update Grievance Category And Sub Category'], 500);
        }

    }
    public function GrievanceCatAndSubCategoryDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            GrievanceCategoryAndSubcatModel::where("id",$id)->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Category And Sub Category Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Grievance Category And Sub Category'], 500);
        }
    }
    public function GrieDelegationRuleStore(Request $request)
    {

        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id' => ['required', 'array', 'min:1'],
            'Grievance_Cat_id.*' => ['required', 'distinct'],

            'delegation_rule' => ['required', 'array', 'min:1'],
            'delegation_rule.*' => ['required', 'distinct'],



            // Custom Rule to ensure uniqueness for each Category-Subcategory pair
        ], [
            'delegation_rule.required' => 'Please select at least one Grievance Rule.',
            'delegation_rule.*.required' => 'Each Grievance Rule is required.',
            'delegation_rule.*.distinct' => 'Duplicate Grievance Rule are not allowed.',
            'priority_level.required' => 'Please select at least one Priority Level.',
            'priority_level.*.required' => 'Each Priority Level is required.',
            'priority_level.*.in' => 'Priority Level must be High, Medium, or Low.',
        ]);

        $validator->after(function ($validator) use ($request, $resort_id) {
            foreach ($request->Grievance_Cat_id as $index => $categoryId) {
                $delegation_rule = $request->delegation_rule[$index] ?? null;

                if ($delegation_rule) {
                    $exists = DB::table('grievance_delegation_rule_models')
                        ->where('resort_id', $resort_id)
                        ->where('Grievance_Cat_id', $categoryId)
                        ->where('delegation_rule', $delegation_rule)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add("Grievance_Cat_id.$index","This Category with delegation Rule already exists for this resort.");
                    }
                }
            }
        });


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {
            foreach($request->Grievance_Cat_id as $ak=>$g)
            {
                GrievanceDelegationRuleModel::create(
                    [
                        'resort_id'=>$resort_id,
                        'Grievance_Cat_id'=>$g,
                        'delegation_rule'=>$request->delegation_rule[$ak]
                    ]
                );
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Rule Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Grievance Rule'], 500);
        }
    }
    public function IndexGrievanceDelegationRule(Request $request)
    {
        $page_title="Grievance Delegation Rule";
        if($request->ajax())
        {
            $GrievanceDelegationRuleModel= GrievanceDelegationRuleModel::with(['category'])->where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceDelegationRuleModel)
            ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                                return '
                                <div  class="d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                    data-Grievance_Cat_id ="'.$row->Grievance_Cat_id.'"
                                    data-cat-id="' . e($id) . '"
                                    data-Gri_Sub_cat_id="'.$row->Gri_Sub_cat_id.'"
                                     data-Priority_Level="'.$row->Priority_Level.'"

                                    >
                                        <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                    </a>
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                        <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                    </a>
                                </div>';
                })
                ->addColumn('Grievance_Cat_id', function ($row)
                {
                                return $row->category->Category_Name;
                })
                ->addColumn('delegation_rule', function ($row)
                {
                                return $row->delegation_rule;
                })
                ->rawColumns(['Action'])
                ->make(true);
        }
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configurationGIndexGrievanceDelegationRule',compact('GrievanceCategory','page_title'));
    }
    public function GrievanceDelegeationRuleinlineUpdate(Request $request)
    {


        $Main_id = base64_decode($request->Main_id);
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id' => ['required', 'min:1'],
            'delegation_rule' => ['required', 'min:1'],
        ], [
            'delegation_rule.required' => 'Please select at least one Grievance Rule.',
            'priority_level.required' => 'Please select at least one Priority Level.',

        ]);

        $validator->after(function ($validator) use ($request, $resort_id, $Main_id) {

                $delegation_rule = $request->delegation_rule ?? null;

                if ($delegation_rule) {
                    $exists = DB::table('grievance_delegation_rule_models')
                        ->where('resort_id', $resort_id)
                        ->where('Grievance_Cat_id', $request->Grievance_Cat_id)
                        ->where('delegation_rule', $delegation_rule)
                        ->where("id","!=",$Main_id)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add("Grievance_Cat_id","This Category with delegation Rule already exists for this resort.");
                    }
                }

        });


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try
        {

                GrievanceDelegationRuleModel::where("id",$Main_id)->update(
                    [
                        'resort_id'=>$resort_id,
                        'Grievance_Cat_id'=>$request->Grievance_Cat_id,
                        'delegation_rule'=>$request->delegation_rule
                    ]
                );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Rule Create Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Grievance Rule'], 500);
        }
    }

    public function GrievanceDelegeationRuleDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            GrievanceDelegationRuleModel::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Delegation Rule Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Grievance Delegation Rule'], 500);
        }
    }
    public function GrievanceRightToBeAccompanied(Request $request)
    {

        $grievanceRightToBeAccompanied = isset($request->grievanceRightToBeAccompanied) && $request->grievanceRightToBeAccompanied == "on" ? $grievanceRightToBeAccompanied = 'yes' : $grievanceRightToBeAccompanied = 'no';

        DB::beginTransaction();
        try
        {

            GrievanceRightToBeAccompanied::updateOrCreate(['resort_id'=>$this->resort->resort_id],['resort_id'=>$this->resort->resort_id,'Right_to_be_accompanied'=>$grievanceRightToBeAccompanied]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Right To Be Accompanied Created or Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Grievance Rule'], 500);
        }

    }


    public function GrievanceNonRetaliation(Request $request)
    {

        //
        DB::beginTransaction();
        try
        {

            $timeframe_submission =  $request->timeframe_submission;
            $Reminder_Frequency =  $request->Reminder_Frequency;
            $reminder_default_time = $request->reminder_default_time;
            $NonRetaliationFeedback =  ($request->NonRetaliationFeedback == "on") ? 'yes' : 'no';
            $ReminderCompleteFeedback =  ($request->ReminderCompleteFeedback == "on") ? 'yes' : 'no';

            GrievanceNonRetaliation::updateOrCreate(['resort_id'=>$this->resort->resort_id],
            ['resort_id'=>$this->resort->resort_id,
                    'timeframe_submission'=>$timeframe_submission,
                    'reminder_frequency'=>$Reminder_Frequency,
                    'reminder_default_time'=>$reminder_default_time,
                    'NonRetaliationFeedback'=>$NonRetaliationFeedback,
                    'ReminderCompleteFeedback'=>$ReminderCompleteFeedback]);
            DB::Commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Grievance Non-Retaliation Created or Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Create Grievance Non-Retaliation'], 500);
            }
    }
    public function GrievanceAppealStore(Request $request)
    {

        $Grievance_Enable_Appeal = (isset($request->Grievance_Enable_Appeal)) ? 'on':'off';
        $AppealDeadLine = $request->AppealDeadLine;
        $MemberId_or_CommitteeId = $request->MemberId_or_CommitteeId;
        $Appeal_Type             = $request->Appeal_Type;


        $validator = Validator::make($request->all(), [
             'AppealDeadLine' => 'required',
             'Appeal_Type' => 'required',
             'MemberId_or_CommitteeId' => 'required',
         ], [
             'AppealDeadLine.required' => 'The Appeal DeadLine field is required. Please write something.',
             'Appeal_Type.required' => 'The Appeal Type field is required. Please write something.',
             'MemberId_or_CommitteeId.required' => 'The MemberId or CommitteeId field is required. Please write something.',
         ]);
         $validator->after(function ($validator) use ($Grievance_Enable_Appeal)
        {   if ($Grievance_Enable_Appeal === 'off') {
                $validator->errors()->add('Grievance_Enable_Appeal', 'Please enable the Grievance Enable Appeal.');
            }
        });


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
            GrievanceAppealDeadlineModel::updateOrCreate(
         ["resort_id"=>$this->resort->resort_id],
             ["resort_id"=>$this->resort->resort_id,
                     "Proccess"=>$Grievance_Enable_Appeal,
                     "Appeal_Type"=>$Appeal_Type,
                     "AppealDeadLine"=>$AppealDeadLine,
                     "date"=> date('Y-m-d'),
                    'MemberId_or_CommitteeId'=>$MemberId_or_CommitteeId]);

             DB::commit();
             return response()->json([
                 'success' => true,
                 'message' => 'Grievance Appeal DeadLine Created Successfully',
             ],200);
         }
         catch(\Exception $e)
         {
             DB::rollBack();
             \Log::emergency("File: " . $e->getFile());
             \Log::emergency("Line: " . $e->getLine());
             \Log::emergency("Message: " . $e->getMessage());
             return response()->json(['error' => 'Failed to Create Grievance Appeal DeadLine'], 500);
         }

    }
    public function GrievanceTempleteStore(Request $request)
    {

        if($request->GrievanectempleteFlag =="EditMode")
        {
            $edit_id = $request->GrievanecInvestingationTemplete_id;

            $validator = Validator::make($request->all(), [
                'Grievance_Temp_name' => [
                    'required',
                    Rule::unique('grievance_templete_models')->where(function ($query) use($edit_id) {
                        return $query->where('resort_id', $this->resort->resort_id);
                    })->ignore($edit_id),
                ],
            ], [
                'Grievance_Temp_name.required' => 'Please Enter Grievance Templete Name.',
                'Grievance_Temp_name.unique' => 'The Grievance Templete Name already exists for this resort.',
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
                GrievanceTempleteModel::updateOrCreate(
                        ["resort_id"=>$this->resort->resort_id,'id'=>$edit_id],
                        ['Grievance_Temp_name'=>$request->Grievance_Temp_name,
                                'resort_id'=>$this->resort->resort_id,
                                'Grievance_Temp_Structure'=>json_encode($request->Grievance_Temp_Structure),
                                'Grievance_Cat_id'=>$request->Grievance_Cat_id]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Grievance Templete Update Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Add Grievance Templete  Updated'], 500);
            }
        }
        else
        {



            $validator = Validator::make($request->all(), [
                'Grievance_Temp_name' => [
                    'required',
                    Rule::unique('grievance_templete_models')->where(function ($query) {
                        return $query->where('resort_id', $this->resort->resort_id);
                    }),
                ],
            ], [
                'Grievance_Temp_name.required' => 'Please Enter Grievance Templete Name.',
                'Grievance_Temp_name.unique' => 'The Grievance Templete Name already exists for this resort.',
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
                GrievanceTempleteModel::create(['Grievance_Temp_name'=>$request->Grievance_Temp_name,'resort_id'=>$this->resort->resort_id,'Grievance_Temp_Structure'=>json_encode($request->Grievance_Temp_Structure),'Grievance_Cat_id'=>$request->Grievance_Cat_id]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Grievance Templete Created Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Add Grievance Templete  Created'], 500);
            }
        }
    }
    public function IndexGrievanceTemplete(Request $request)
    {
        $page_title="Grievance Category";
        if($request->ajax())
        {
            // GrievanceCategory
            $GrievanceCategory= GrievanceTempleteModel::with(['category'])->where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceCategory)
            ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                            return '
                            <div  class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-grivanceTemplate" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-grivanceTemplete" data-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>
                            </div>';
            })
            ->addColumn('CategoryName', function ($row) {

                return $row->category->Category_Name;

            })
            ->addColumn('GrievaneTempleteName', function ($row) {
                return $row->Grievance_Temp_name;
            })
            ->rawColumns(['CategoryName','GrievaneTempleteName','Action'])
            ->make(true);
        }
    }

    public function GrievanceTempleteEdit(Request $request)
    {
        $id = base64_decode($request->id);

        $d = GrievanceTempleteModel::find($id);

        $form_structure = json_decode($d->Grievance_Temp_Structure, true);
        $data=[$form_structure,$d->Grievance_Temp_name,$d->Grievance_Cat_id,$d->id];
        return response()->json([
            'success' => true,
            'data' =>$data,
        ], 200);


    }

    public function GrivanceTempleteDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            GrievanceTempleteModel::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Templete Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Grievance Templete'], 500);
        }
    }
    public function GrivanceResoultionTimeLineStore(Request $request)
    {
        $Grivance_high_priority = $request->Grivance_high_priority;
        $Grivance_medium_priority = $request->Grivance_medium_priority;
        $Grivance_low_priority = $request->Grivance_low_priority;

        DB::beginTransaction();
        try
        {

            GrivanceResoultionTimeLineModel::updateOrCreate(['resort_id'=>$this->resort->resort_id],
            ['HighPriority'=> $Grivance_high_priority,
            'MediumPriority'=> $Grivance_medium_priority,
            'LowPriority'=> $Grivance_low_priority]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Resoultion Time Line Update Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Resoultion Time Line Update'], 500);
        }

    }

    public function GrivanceEscaltionStore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id'   => ['required', 'array', 'min:1'],
            'Grievance_Cat_id.*' => [
                'required',
                'integer', // Assuming it's stored as an integer
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1] ?? null; // Get index from "Grievance_Cat_id.X"
                    $resolvedDuration = $request->resolved_duration[$index] ?? null;

                    if (!$resolvedDuration) {
                        $fail("A corresponding resolved duration is required for category ID '$value'.");
                    }

                    // Get the resort ID from the authenticated user
                    $resortId = auth()->guard('resort-admin')->user()->resort_id;

                    // Check if a record already exists
                    $exists = GrivanceEscaltionModel::where('resort_id', $resortId)
                        ->where('Grievance_Cat_id', $value)
                        ->where('resolved_duration', $resolvedDuration)
                        ->exists();

                    if ($exists) {
                        $fail("The category ID  with resolved duration $resolvedDuration already exists for this resort.");
                    }
                }
            ],
            'resolved_duration'   => ['required', 'array', 'min:1'],
            'resolved_duration.*' => ['required', 'integer'],
        ], [
            'Grievance_Cat_id.required' => 'At least one Category ID is required.',
            'Grievance_Cat_id.*.required' => 'Each Category ID must be provided.',
            'resolved_duration.required' => 'At least one Resolved Duration is required.',
            'resolved_duration.*.required' => 'Each Resolved Duration must be provided.',
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
            foreach($request->Grievance_Cat_id as $ak=>$d)
            {
                $resolved_duration = array_key_exists($ak,$request->resolved_duration) ? $request->resolved_duration[$ak]:'';
                GrivanceEscaltionModel::create([
                    'resort_id' => $this->resort->resort_id,
                    'Grievance_Cat_id' => $d,
                    'resolved_duration' => $resolved_duration,
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grivane Escaltion Created Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add Escalation '], 500);
        }
    }

    public function IndexGrievanceEscaltion(Request $request)
    {

        $page_title="Grievance Ecalation";
        if($request->ajax())
        {
            $IndexGrievanceEscaltion = GrivanceEscaltionModel::with(['category'])->where('resort_id',$this->resort->resort_id)->get();
                    return datatables()->of($IndexGrievanceEscaltion)
                    ->addColumn('Action', function ($row) {
                    $id = base64_encode($row->id);
                                return '
                                <div  class="d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                    data-Grievance_Cat_id ="'.$row->Grievance_Cat_id.'"
                                    data-cat-id="' . e($id) . '"
                                    data-resolved_duration="'.$row->resolved_duration.'"
                                    >
                                        <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                    </a>
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-cat-id="' . e($id) . '">
                                        <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                    </a>
                                </div>';
                })
                ->addColumn('Grievance_Cat_id', function ($row)
                {
                    return $row->category->Category_Name;
                })
                ->addColumn('Resolved_Duration', function ($row)
                {
                    return $row->resolved_duration;
                })
                ->rawColumns(['Grievance_Cat_id','Resolved_Duration','Action'])
                ->make(true);
        }
        $GrievanceCategory = GrievanceCategory::where("resort_id",$this->resort->resort_id)->get();
        return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrievanceEscaltion',compact('GrievanceCategory','page_title'));
    }

    public function GrievanceEscalationinlineUpdate(Request $request)
    {
        $mainId = base64_decode($request->Main_id); // Decode Main ID
        $validator = Validator::make($request->all(), [
            'Main_id'            => ['required', 'string'], // Assuming it's Base64 encoded
            'Grievance_Cat_id'   => ['required', 'integer'],
            'resolved_duration'  => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $mainId = $request->Main_id; // Decode Main ID
                    $resortId = auth()->guard('resort-admin')->user()->resort_id;

                    // Check if a record already exists, excluding the current one
                    $exists = GrivanceEscaltionModel::where('resort_id', $resortId)
                        ->where('Grievance_Cat_id', $request->Grievance_Cat_id)
                        ->where('resolved_duration', $value)
                        ->where('id', '!=', $mainId) // Exclude current record
                        ->exists();

                    if ($exists) {
                        $fail("The selected grievance category with resolved duration {$value} already exists for this resort.");
                    }
                }
            ],
        ], [
            'Main_id.required'            => 'The Main ID is required.',
            'Grievance_Cat_id.required'   => 'The Grievance Category ID is required.',
            'resolved_duration.required'  => 'The Resolved Duration is required.',
        ]);

        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }


        DB::beginTransaction();
        try
        {
            // Proceed with update

                $update = GrivanceEscaltionModel::where('id', $mainId)
                ->update([
                    'Grievance_Cat_id'  => $request->Grievance_Cat_id,
                    'resolved_duration' => $request->resolved_duration,
                ]);
                    DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grivane Escaltion Updated Successfully',
            ], 200);



              }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated Escalation '], 500);
        }
    }
    public function GrievanceEscalationDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {
            GrivanceEscaltionModel::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Escalation Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Escalation'], 500);
        }
    }
    public function GrievanceCommitteeStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Grivance_CommitteeName'  => [
                'required',
                'string',
                Rule::unique('grievance_committee_member_parents', 'Grivance_CommitteeName')->where(function ($query) {
                    return $query->where('resort_id',$this->resort->resort_id);
                })
            ],
            'GrieanceCommitteeMembers'   => ['required',  'min:1'],
            'GrieanceCommitteeMembers.*' => ['required', 'integer'], // Validate each member ID

        ], [
            'Grivance_CommitteeName.required'  => 'The Grievance Committee Name is required.',
            'Grivance_CommitteeName.unique'    => 'The Grievance Committee Name already exists for this resort. Please choose another name.',
            'GrieanceCommitteeMembers.required' => 'At least one Committee Member is required.',
            'GrieanceCommitteeMembers.*.required' => 'Each Committee Member must be provided.',
            'GrieanceCommitteeMembers.*.integer'  => 'Each Committee Member must be a valid integer ID.',
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
            $parent_id = GrievanceCommitteeMemberParent::create(['Grivance_CommitteeName'=>$request->Grivance_CommitteeName,
                                                                'resort_id'=>$this->resort->resort_id,
                                                                'date'=>date('Y-m-d')
                                                            ]);
            if($parent_id)
            {
                foreach($request->GrieanceCommitteeMembers as $g)
                {
                    GrievanceCommitteeMemberChild::create(['Parent_id'=>$parent_id->id,'resort_id'=>$this->resort->resort_id,"Committee_Member_Id"=>$g]);

                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Grievance Committee Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Grievance Committee'], 500);
        }
    }

    public function GrivanceCommitteeIndex(Request $request)
    {
        $page_title="Grievance Committees";

        if($request->ajax())
        {

            $GrievanceCommitteeMemberParent = GrievanceCommitteeMemberParent::where('resort_id',$this->resort->resort_id)->get();
            return datatables()->of($GrievanceCommitteeMemberParent)
                    ->addColumn('Action', function ($row) {
                $id = base64_encode($row->id);
                $child = GrievanceCommitteeMemberChild::where('Parent_id',$row->id)->get('Committee_Member_Id');
                $members=[];
                foreach($child as $c)
                {
                    $members[]=base64_encode($c->Committee_Member_Id);
                }

                return ' <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-AssignCommittee"
                            data
                            data-date= "'.date('d-m-Y',strtotime($row->date)).'"  data-committeename="'.$row->CommitteeName.'" data-members="'.implode(",",$members).'"data-cat-id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/edit.svg") . '" alt="Edit" class="img-fluid">
                                </a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-Self_id="' . e($id) . '">
                                    <img src="' . asset("resorts_assets/images/trash-red.svg") . '" alt="Delete" class="img-fluid">
                                </a>';

            })
            ->addColumn('CommitteeName', function ($row) {
                return ucfirst($row->Grivance_CommitteeName);
            })

            ->addColumn('CommiteeMembers', function ($row) {

                $child = GrievanceCommitteeMemberChild::join('employees as t2' ,'t2.id',"=","grievance_committee_member_children.Committee_Member_Id")
                        ->join('resort_admins as t1',"t1.id","=","t2.Admin_Parent_id")
                        ->where("t1.resort_id",$this->resort->resort_id)
                        ->where('grievance_committee_member_children.Parent_id',$row->id)
                        ->get(['t1.first_name','t1.last_name','t1.profile_picture']);
                $names='';
                foreach($child as $c)
                {
                    $names.= $c->first_name.'  ' .$c->last_name. "<br>";
                }
                return $names;
            })
            ->addColumn('date', function ($row) {
                return date('d-m-Y',strtotime($row->date));
            })
            ->rawColumns(['CreateNewTemplate','date','CommiteeMembers','Action'])
            ->make(true);
        }
        $CommitteeMembers = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                                    ->where("t1.resort_id",$this->resort->resort_id)
                                    ->whereIn('employees.rank',[1,2,3,8])
                                    ->get(['t1.first_name','t1.last_name','t1.profile_picture','employees.*']);
        return view('resorts.GrievanceAndDisciplinery.configuration.Grievance.IndexGrivanceCommittees',compact('CommitteeMembers','page_title'));

    }
    public function GrivanceCommitteeinlineUpdate(Request $request)
    {
        $id = base64_decode($request->Main_id);
        $CommitteeName = $request->Grivance_CommitteeName;
        $assign_members= $request->Committee_Member_Id;
        $validator = Validator::make($request->all(), [
            'Grivance_CommitteeName' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request, $id) {
                    $exists = GrievanceCommitteeMemberParent::where('resort_id', $this->resort->resort_id)
                        ->where('Grivance_CommitteeName', $value)
                        ->where('id', '!=', $id) // Exclude the current record from the uniqueness check
                        ->exists();

                    if ($exists) {
                        $fail("The committee name '$value' already exists in this resort.");
                    }
                }
            ],
        ], [
            'Grivance_CommitteeName.required' => 'Please provide at least one committee name.',
            'Grivance_CommitteeName.max' => 'Committee name must not exceed 50 characters.',
        ]);
        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ],
            422);
        }
        DB::beginTransaction();
            try
            {

                    $newCommittee = GrievanceCommitteeMemberParent::where('id',$id)->update([
                                    'Grivance_CommitteeName' => $CommitteeName
                                ]);

                if( count($assign_members)> 0)
                {

                    GrievanceCommitteeMemberChild::where("Parent_id",$id)->delete();

                    foreach($assign_members as $m)
                    GrievanceCommitteeMemberChild::create([
                        'Parent_id' =>  $id,
                        'Committee_Member_Id' => $m,
                    ]);
                }
            DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => ' Grievance Committee Updated Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Grievance Updated Committee'], 500);
            }

    }
    public function GrivevanceCommitteesDestory($id)
    {
        $id = base64_decode($id);
        DB::beginTransaction();
        try
        {

            GrievanceCommitteeMemberChild::where("Parent_id",$id)->delete();
            GrievanceCommitteeMemberParent::where("id",$id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ' Grivevance Committee Delete Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Grivevance Committee'], 500);
        }
    }

    public function CodeOfConduct()
    {
        return Excel::download(new DisciplineryCodeOfConduct, 'ResortCodeOfConductList.xlsx');

    }

    public function DisciplineryEmailTamplate(Request $request)
    {
        $MailTemplete  = $request->content;
        $MailSubject  = $request->subject;
        $Action_id  = base64_decode($request->Action_id);
        $id  = $request->MailTemplete;
        $placeholders = DisciplinaryEmailmodel::extractPlaceholders($request->content) ?? [];
        $resort_id = $this->resort->resort_id;

        $decodedActionId = base64_decode($request->Action_id); // Decode it

        DB::beginTransaction();
        try
        {
            if($request->Mode !="edit")
            {

                $validator = Validator::make([
                    'Action_id' => $decodedActionId, // use decoded value
                    'subject' => $request->subject,
                    'content' => $request->content,
                ], [
                    'Action_id' => [
                        'required',
                        Rule::unique('disciplinary_emailmodels', 'Action_id')
                            ->where(function ($query) use ($resort_id) {
                                return $query->where('resort_id', $resort_id);
                            }),
                    ],
                    'subject' => 'required',
                    'content' => 'required',
                ], [
                    'Action_id.required' => 'The Action Name field is required.',
                    'Action_id.unique' => 'The Action Name already exists for this resort.',
                    'subject.required' => 'The Subject is required.',
                    'content.required' => 'The Content is required.',
                ]);
                if($validator->fails())
                {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                DisciplinaryEmailmodel::create([
                    "resort_id"=>$this->resort->resort_id,
                    'Action_id'=>$Action_id,
                    'subject'=>$MailSubject,
                    'content'=>$MailTemplete,
                    'Placeholders'=>$placeholders,
                ]);
                $msg = 'Disciplinary Email Template Created Successfully';
            }
            else
            {


                    $validator = Validator::make([
                        'Action_id' => $decodedActionId, // use decoded value
                        'subject' => $request->subject,
                        'content' => $request->content,
                    ], [
                        'Action_id' => [
                            'required',
                            Rule::unique('disciplinary_emailmodels', 'Action_id')
                                ->where(function ($query) use ($resort_id) {
                                    return $query->where('resort_id', $resort_id);
                                })
                                ->ignore($request->id),
                        ],
                        'subject' => 'required',
                        'content' => 'required',
                    ], [
                        'Action_id.required' => 'The Action Name field is required.',
                        'Action_id.unique' => 'The Action Name already exists for this resort.',
                        'subject.required' => 'The Subject is required.',
                        'content.required' => 'The Content is required.',
                    ]);
                if($validator->fails())
                {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }

                DisciplinaryEmailmodel::where("resort_id",$this->resort->resort_id)
                                        ->where("id",$request->id)
                                        ->update([
                                            "resort_id"=>$this->resort->resort_id,
                                            'Action_id'=>$Action_id,
                                            'subject'=>$MailSubject,
                                            'content'=>$MailTemplete,
                                            'Placeholders'=>$placeholders,
                                        ]);
                $msg = 'Disciplinary Email Template Updated Successfully';
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $msg,
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Add  Disciplinary Email Template '], 500);
        }

    }

    public function DisciplineryEmailTamplateIndex(Request $request)
    {
        if($request->ajax())
        {
            $DisciplinaryEmailmodel= DisciplinaryEmailmodel::join("action_stores as t1","t1.id","=","disciplinary_emailmodels.Action_id")
                                                            ->where('disciplinary_emailmodels.resort_id',$this->resort->resort_id)
                                                            ->get(['disciplinary_emailmodels.*','t1.ActionName']);



            return datatables()->of($DisciplinaryEmailmodel)
            ->addColumn('ActionName', function ($row)
            {
                return $row->ActionName;
            })
            ->addColumn('action', function ($row)
            {
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
            ->rawColumns(['ActionName','action'])
            ->make(true);
        }

    }
    public function GetEmailTamplate(Request $request)
    {
        $id=  base64_decode($request->id);
        $DisciplinaryEmailmodel= DisciplinaryEmailmodel::join("action_stores as t1","t1.id","=","disciplinary_emailmodels.Action_id")
                                                            ->where('disciplinary_emailmodels.resort_id',$this->resort->resort_id)
                                                            ->where('disciplinary_emailmodels.id',$id)
                                                            ->first(['disciplinary_emailmodels.*','t1.id as A_ID','t1.ActionName']);

        $data = ['Action_id'=> base64_encode($DisciplinaryEmailmodel->A_ID),'id'=>$DisciplinaryEmailmodel->id,'flag'=>"edit","subject"=>$DisciplinaryEmailmodel->subject,'content'=>$DisciplinaryEmailmodel->content];

         return response()->json([
                'success' => true,
                'message' => 'Disciplinary Email Template Created Successfully',
                'data'=>$data
            ], 200);
    }
}

