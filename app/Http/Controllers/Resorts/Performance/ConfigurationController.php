<?php

namespace App\Http\Controllers\Resorts\Performance;

use DB;
use Auth;
use Validator;
use Carbon\Carbon;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Models\ResortDivision;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
 use App\Models\PerformanceReviewType;
 use App\Models\NintyDayPeformanceForm;
 use App\Models\PerformanceTemplateForm;
use App\Models\PerformanceMeetingContent;
use App\Models\Professionalform;
class ConfigurationController extends Controller
{

    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $reporting_to = $this->globalUser->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }
    public function index(Request $request)
    {
        $page_title  ='Configuration';
        if($request->ajax())
        {
                    $PerformanceReviewType = PerformanceReviewType::where('resort_id',$this->resort->resort_id)->get();
                    return datatables()->of($PerformanceReviewType)
                    ->addColumn('action', function ($row)
                    {
                        // $id =base64_decode($row)
                        return '<a hef="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="'.e($row->id).'"><img src="'. asset('resorts_assets/images/edit.svg').'" alt="" class="img-fluid" /></a>
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="'.e($row->id).'"><img src="'. asset("resorts_assets/images/trash-red.svg").'" alt="" class="img-fluid" /></a>
                        ';
                     })
                    ->editColumn('category_title', function ($row) {
                      return   ucfirst($row->category_title);

                    })
                    ->editColumn('category_weightage', function ($row) {
                        return e($row->category_weightage);
                    })


                    ->rawColumns(['category_title','category_weightage','action'])
                    ->make(true);

        }
        $resort_divisions = ResortDivision::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
        $PerformanceMeetingContent =  PerformanceMeetingContent::where('resort_id',$this->resort->resort_id)->first();
        return view('resorts.Performance.configuration.index',compact('page_title','resort_divisions','PerformanceMeetingContent'));
    }

    public function ReviewTypes(Request $request)
    {
        $category_title = $request->category_title;
        $category_weightage = $request->category_weightage;



        $validator = Validator::make($request->all(), [
            'category_title' => 'required|array|min:1', // Ensure it's an array
            'category_title.*' => [
                'required',
                'max:50',
                Rule::unique('performance_review_types', 'category_title')->where(function ($query) use ($request) {
                    return $query->where('resort_id', $this->resort->resort_id);
                }),
            ],
            'category_weightage' => 'required|array|min:1', // Ensure it's an array
            'category_weightage.*' => 'required|numeric|min:1|max:100',
        ], [
            'category_title.required' => 'The Category Name field is required. Please write something.',
            'category_title.array' => 'Invalid Category Name format.',
            'category_title.*.required' => 'Each Category Name is required.',
            'category_title.*.unique' => 'The Category Name ":input" already exists for this resort.',
            'category_title.*.max' => 'The maximum allowed length for the Category Name is 50 characters.',

            'category_weightage.required' => 'The Category Weightage field is required.',
            'category_weightage.array' => 'Invalid Category Weightage format.',
            'category_weightage.*.required' => 'Each Category Weightage is required.',
            'category_weightage.*.numeric' => 'Category Weightage must be a number.',
            'category_weightage.*.min' => 'Category Weightage must be at least 1.',
            'category_weightage.*.max' => 'Category Weightage cannot be more than 100.',
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
            foreach($category_title as $k=>$v){
                PerformanceReviewType::Create([
                    'resort_id'=>$this->resort->resort_id,
                    'category_title'=>$v,
                    'category_weightage'=>$category_weightage[$k]

                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Review Type  Added Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to add  Review Type'], 500);
        }

    }
    public function inlineReviewTypesUpdate(Request $request,$id)
    {

        $category_title = $request->category_title;
        $category_weightage = $request->category_weightage;



        $validator = Validator::make($request->all(), [
            'category_title' => [
                'required',
                'max:50',
                Rule::unique('performance_review_types', 'category_title')->where(function ($query) use ($request) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })->ignore($id),
            ],
            'category_weightage' => 'required|min:1', // Ensure it's an array
        ], [
            'category_title.required' => 'The Category Name field is required. Please write something.',

            'category_title.unique' => 'The Category Name  already exists for this resort.',
            'category_title.max' => 'The maximum allowed length for the Category Name is 50 characters.',

            'category_weightage.required' => 'The Category Weightage field is required.',
            'category_weightage.numeric' => 'Category Weightage must be a number.',
            'category_weightage.min' => 'Category Weightage must be at least 1.',
            'category_weightage.max' => 'Category Weightage cannot be more than 100.',
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
            PerformanceReviewType::where('id',$id)->update([
                'category_title'=>$request->category_title,
                'category_weightage'=>$request->category_weightage
            ]);

             DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Review Type  Updated Successfully',
                ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Review Type'], 500);
        }
    }

    public function DestroyReviewTypes($id)
    {
        DB::beginTransaction();
        try
        {
            $PerformanceReviewType = PerformanceReviewType::where('id',$id)->delete();
           DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Review Type  Deleted Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete  Review Type'], 500);
        }


    }

    public function PerformanceTemplateFormStore(Request $request)
    {
            $Division_id = $request->Division_id;
            $Department_id= $request->Department_id;
            $Section_id= $request->Section_id;
            $Position_id= $request->Position_id;
            $FormName = $request->FormName;
            $form_data = json_encode($request->form_structure, true);

            $validator = Validator::make($request->all(), [
                'FormName' => [
                    'required',
                    'max:50',
                    Rule::unique('performance_template_forms')->where(function ($query) {
                        return $query->where('resort_id', $this->resort->resort_id);
                    }),
                ],
            ], [
                'FormName.required' => 'The Form Name field is required. Please write something.',
                'FormName.unique' => 'The Form Name already exists for this resort.',
                'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
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
                $PerformanceTemplateForm = PerformanceTemplateForm::updateOrCreate([
                        'resort_id'=>$this->resort->resort_id,
                        'Division_id'=>$Division_id,
                        'Department_id'=>$Department_id,
                        'Section_id'=>$Section_id,
                        'Position_id'=>$Position_id],
                        [
                                    'resort_id'=>$this->resort->resort_id,
                                    'Division_id'=>$Division_id,
                                    'Department_id'=>$Department_id,
                                    'Section_id'=>$Section_id,
                                    'Position_id'=>$Position_id,
                                    'form_structure'=>$form_data,
                                    'FormName'=>$FormName

                        ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Peformance Template Created Successfully',
                ], 200);
            }
            catch (\Exception $e)
            {
                DB::rollBack();
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::emergency("Message: " . $e->getMessage());
                return response()->json(['error' => 'Failed to Peformance Template  Review Type'], 500);
            }
    }

    public function PerformanceTemplateFormList(Request $request)
    {

        $PerformanceReviewType = PerformanceTemplateForm::leftJoin('resort_divisions as t1','t1.id',"=",'performance_template_forms.Division_id')
                                                        ->leftJoin('resort_departments as t2','t2.id',"=",'performance_template_forms.Department_id')
                                                        ->leftJoin('resort_positions as t3','t3.id',"=",'performance_template_forms.Position_id')
                                                        ->leftJoin('resort_sections as t4','t4.id',"=",'performance_template_forms.Section_id')
                                                        ->where('performance_template_forms.resort_id',$this->resort->resort_id)
                                                        ->get([
                                                            't1.name as ResortDivison',
                                                            't2.name as DepartmentName',
                                                            't3.position_title as ResortPositionName',
                                                            't4.name as SectionName',
                                                            'performance_template_forms.*',
                                                        ]);


        if($request->ajax())
        {


            return datatables()->of($PerformanceReviewType)
            ->addColumn('action', function ($row)
             {
                return '<a hef="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-name="'.$row->FormName.'" data-id="'.e($row->id).'"><img src="'. asset('resorts_assets/images/edit.svg').'" alt="" class="img-fluid" /></a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="'.e(base64_encode($row->id)).'"><img src="'. asset("resorts_assets/images/trash-red.svg").'" alt="" class="img-fluid" /></a>';
             })
            ->editColumn('FormBuilderName', function ($row) {
              return   ucfirst($row->FormName);

            })
            ->editColumn('Division', function ($row) {
                return e($row->ResortDivison);
            })
            ->editColumn('Department', function ($row) {
                return e($row->DepartmentName);
            })

            ->editColumn('Position', function ($row)
            {
                return e($row->ResortPositionName);
            })
            ->rawColumns(['Division','Department','Position','FormBuilderName','action'])
            ->make(true);
        }

        $page_title="Performance Template Form List";
        return view('resorts.Performance.configuration.PerformanceTemplateFormList',compact('page_title'));
    }

    public function GetPerformanceTemplateForm($id)
    {
        $PerformanceReviewType = PerformanceTemplateForm::where('resort_id',$this->resort->resort_id)->where('id',$id)->first();
       $form_structure = json_decode($PerformanceReviewType->form_structure, true);

       $data=[$form_structure,$PerformanceReviewType->FormName,$PerformanceReviewType->id];
                return response()->json([
                    'success' => true,
                    'data' =>$data,
                ], 200);

    }

    public function PerformanceTemplateFormUpdate(Request $request)
    {
        $Form_id = $request->Form_id;
        $FormName = $request->FormName;
        $form_structure = json_encode($request->form_structure);
        $validator = Validator::make($request->all(), [
            'FormName' => [
                'required',
                'max:50',
                Rule::unique('performance_template_forms')->where(function ($query) use($Form_id) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })->ignore($Form_id),
            ],
        ], [
            'FormName.required' => 'The Form Name field is required. Please write something.',
            'FormName.unique' => 'The Form Name already exists for this resort.',
            'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
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
            $PerformanceTemplateForm = PerformanceTemplateForm::updateOrCreate(['resort_id'=>$this->resort->resort_id,'id'=>$Form_id],
                                                                                [
                                                                                    'form_structure'=>$form_structure,
                                                                                    'FormName'=>$FormName
                                                                                ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Peformance Template  Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated  Peformance Template '], 500);
        }
    }

    public function DestroyPerformanceTemplateForm($id)
    {
        $id =  base64_decode($id);
        DB::beginTransaction();
        try
        {
            PerformanceTemplateForm::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Performance Template Form Deleted Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Performance Template Form'], 500);
        }
    }
    public function NintyDayPeformanceFormStore(Request $request)
    {
        $FormName = $request->FormName;
        $form_structure = json_encode($request->form_structure);

        $validator = Validator::make($request->all(), [
            'FormName' => [
                'required',
                'max:50',
                Rule::unique('ninty_day_peformance_forms')->where(function ($query) {
                    return $query->where('resort_id', $this->resort->resort_id);
                }),
            ],
        ], [
            'FormName.required' => 'The Form Name field is required. Please write something.',
            'FormName.unique' => 'The Form Name already exists for this resort.',
            'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
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
             NintyDayPeformanceForm::create([
                    'resort_id'=>$this->resort->resort_id,
                    'FormName'=>$FormName,
                    'form_structure'=>$form_structure
             ]);
             DB::commit();
             return response()->json([
                 'success' => true,
                 'message' => 'Ninty Day Peformance Form Create Successfully',
             ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Ninty Day Peformance Form  '], 500);
        }
    }
    public function NitnyPerformanceFormList(Request $request)
    {


        $NintyDayPeformanceForm = NintyDayPeformanceForm::where('resort_id',$this->resort->resort_id)->get();

        if($request->ajax())
        {
            return datatables()->of($NintyDayPeformanceForm)
            ->addColumn('action', function ($row)
            {
                return '<a hef="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-name="'.$row->FormName.'" data-id="'.e($row->id).'"><img src="'. asset('resorts_assets/images/edit.svg').'" alt="" class="img-fluid" /></a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="'.e(base64_encode($row->id)).'"><img src="'. asset("resorts_assets/images/trash-red.svg").'" alt="" class="img-fluid" /></a>';
             })
            ->editColumn('FormName', function ($row)
            {
              return   ucfirst($row->FormName);
            })
            ->rawColumns(['FormName','action'])
            ->make(true);
        }
        $page_title="90 Day Peformance Form List";

        return view('resorts.Performance.configuration.NintyPerformanceFormList',compact('page_title'));
    }

    public function GetNintyPerformanceForm($id)
    {


        $NintyDayPeformanceForm = NintyDayPeformanceForm::where('resort_id',$this->resort->resort_id)->where('id',$id)->first();
        $form_structure = json_decode($NintyDayPeformanceForm->form_structure, true);
        $data=[$form_structure,$NintyDayPeformanceForm->FormName,$NintyDayPeformanceForm->id];
                return response()->json([
                    'success' => true,
                    'data' =>$data,
                ], 200);
    }
    public function NintyDayPerformanceFormUpdate(Request $request)
    {
        $Form_id = $request->Form_id;

        $FormName = $request->FormName;
        $form_structure = json_encode($request->form_structure);

        $validator = Validator::make($request->all(), [
            'FormName' => [
                'required',
                'max:50',
                Rule::unique('ninty_day_peformance_forms')->where(function ($query) use($Form_id) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })->ignore($Form_id),
            ],
        ], [
            'FormName.required' => 'The Form Name field is required. Please write something.',
            'FormName.unique' => 'The Form Name already exists for this resort.',
            'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
        ]);

        DB::beginTransaction();
        try
        {
            NintyDayPeformanceForm::where("resort_id",$this->resort->resort_id)->where("id",$Form_id)->update([
                'resort_id'=>$this->resort->resort_id,
                'FormName'=>$FormName,
                'form_structure'=>$form_structure
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Ninty Day Peformance Form Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated Ninty Day Peformance Form  '], 500);
        }
    }

    public function DestroyNintyDayPerformanceForm($id)
    {
        $id =  base64_decode($id);
        DB::beginTransaction();
        try
        {
            NintyDayPeformanceForm::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Nity Day Performance  Form Deleted Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Nity Day Performance  Form'], 500);
        }
    }

    public function ScheduleMeetingEmail(Request $request)
    {
        DB::beginTransaction();
        try
        {
            PerformanceMeetingContent::updateOrCreate(
                ['resort_id' => $this->resort->resort_id], // Condition to check existing record
                [
                    'resort_id' => $this->resort->resort_id, // Ensure resort_id is set
                    'content' => $request->ScheduleMeetingEmail
                ]
            );
                        DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Performance Meeting content set Successfully',
                ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create / Update Performance Meeting content set '], 500);
        }
    }
    public function ProfessionalFormList(Request $request)
    {
        $Professionalform = Professionalform::where('resort_id',$this->resort->resort_id)->get();
        if($request->ajax())
        {
            return datatables()->of($Professionalform)
            ->addColumn('action', function ($row)
            {
                return '<a hef="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-name="'.$row->FormName.'" data-id="'.e($row->id).'"><img src="'. asset('resorts_assets/images/edit.svg').'" alt="" class="img-fluid" /></a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="'.e(base64_encode($row->id)).'"><img src="'. asset("resorts_assets/images/trash-red.svg").'" alt="" class="img-fluid" /></a>';
             })
            ->editColumn('FormBuilderName', function ($row)
            {
              return   ucfirst($row->FormName);
            })
            ->rawColumns(['FormBuilderName','action'])
            ->make(true);
        }
        $page_title="Professional Form List";

        return view('resorts.Performance.configuration.ProfessionalFormList',compact('page_title'));
    }

    public function ProfessionalFormStore(Request $request)
    {
        $FormName = $request->FormName;
        $form_structure = json_encode($request->form_structure);

        $validator = Validator::make($request->all(), [
            'FormName' => [
                'required',
                'max:50',
                Rule::unique('professionalforms')->where(function ($query) {
                    return $query->where('resort_id', $this->resort->resort_id);
                }),
            ],
        ], [
            'FormName.required' => 'The Form Name field is required. Please write something.',
            'FormName.unique' => 'The Form Name already exists for this resort.',
            'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
        ]);

        DB::beginTransaction();
        try
        {
            Professionalform::create([
                    'resort_id'=>$this->resort->resort_id,
                    'FormName'=>$FormName,
                    'form_structure'=>$form_structure
             ]);
             DB::commit();
             return response()->json([
                 'success' => true,
                 'message' => 'Professional form Create Successfully',
             ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Create Professional  Form '], 500);
        }
    }

    public function  GetProfessionalForm($id)
    {


        $Professionalform = Professionalform::where('resort_id',$this->resort->resort_id)->where('id',$id)->first();
        $form_structure = json_decode($Professionalform->form_structure, true);
        $data=[$form_structure,$Professionalform->FormName,$Professionalform->id];
                return response()->json([
                    'success' => true,
                    'data' =>$data,
                ], 200);
    }

    public function ProfessionalFormUpdate(Request $request)
    {
        $Form_id = $request->Form_id;

        $FormName = $request->FormName;
        $form_structure = json_encode($request->form_structure);

        $validator = Validator::make($request->all(), [
            'FormName' => [
                'required',
                'max:50',
                Rule::unique('professionalforms')->where(function ($query) use($Form_id) {
                    return $query->where('resort_id', $this->resort->resort_id);
                })->ignore($Form_id),
            ],
        ], [
            'FormName.required' => 'The Form Name field is required. Please write something.',
            'FormName.unique' => 'The Form Name already exists for this resort.',
            'FormName.max' => 'The maximum allowed length for the Form Name is 50 characters.',
        ]);

        DB::beginTransaction();
        try
        {
            Professionalform::where("resort_id",$this->resort->resort_id)->where("id",$Form_id)->update([
                'resort_id'=>$this->resort->resort_id,
                'FormName'=>$FormName,
                'form_structure'=>$form_structure
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Professional Form Updated Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Updated Professional form '], 500);
        }
    }
    public function DestroyProfessionalForm($id)
    {

        $id =  base64_decode($id);
        DB::beginTransaction();
        try
        {
            Professionalform::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Professional Form Deleted Successfully',
            ], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Delete Professional Form'], 500);
        }
    }
}
