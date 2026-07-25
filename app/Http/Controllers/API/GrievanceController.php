<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GrivanceSubmissionModel;
use App\Models\GrivanceSubmissionWitness;
use App\Models\Employee;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
use App\Models\ActionStore;
use App\Models\GrievanceInformalResolution;
use Illuminate\Support\Facades\Auth;
use Validator;
use DB;
use App\Helpers\Common;

class GrievanceController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
        }
    }

    public function GetEmployeeDetails(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'emp_id'                                        =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        try
        {
            $Employee                                       =   Employee::with(['resortAdmin','department','position'])->where('id',$request->emp_id)->first();
            $Employee->DepartmentName                       =   $Employee->department->name;
            $Employee->PositionName                         =   $Employee->position->position_title;
            $Superviser                                     =   Employee::with(['resortAdmin'])->where('id',$Employee->reporting_to)->first();
            $Superviser->Main_Name                          =   $Superviser->resortAdmin->first_name.' '. $Superviser->resortAdmin->last_name;
            $data                                           =   [
                'Employee'                                  =>  $Employee,
                'Superviser'                                =>  $Superviser->Main_Name
            ];

            $response['status']                             =   true;
            $response['message']                            =   'Monthly Check in Stored successfully';
            $response['data']                               =  $data;
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Find Employee Details'], 500);
        }
    }

    public function GetGrievanceCat()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try
        {
            $GrievanceCategory                              =   GrievanceCategory::where("resort_id",$this->resort_id)->get();
            $response['status']                             =   true;
            $response['message']                            =   'Fetching the Grievance category successfully';
            $response['data']                               =   $GrievanceCategory;
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Find Employee Details'], 500);
        }
    }

    public function GetGrievanceSubCat(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'Grievance_Cat_id'                              =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        try
        {
            $GrievanceSubcategory                           =   GrievanceSubcategory::where("Grievance_Cat_id",$request->Grievance_Cat_id)->where('resort_id',$this->resort_id)->get(['id','Sub_Category_Name']);
            $response['status']                             =   true;
            $response['message']                            =   'Fetching the Grievance Sub category successfully';
            $response['data']                               =   $GrievanceSubcategory;
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Find Employee Details'], 500);
        }
    }

    public function GrievanceStore(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'Grivance_Cat_id'                               =>  'required',
            'Grivance_Sub_cat'                              =>  'required',
            'Employee_id'                                   =>  'required',
            'date'                                          =>  'required|date',
            'Grivance_description'                          =>  'required',
            'Grivance_date_time'                            =>  'required|date_format:Y-m-d H:i:s',
            'location'                                      =>  'required',
            'witness_id'                                    =>  'required',
            'Grivance_Eexplination_description'             =>  'required',
            'Confidential'                                  =>  'required',
            'grievance_informally'                          =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            if($request->Confidential =="option1")
            {
                $Grivance_Submission_Type   =  "Yes";

            } else if($request->Anonymous =="option2") {

                $Grivance_Submission_Type   =  "No";

            }  else {
                $Grivance_Submission_Type   =  "NotApplicable";
            }
            // dd($request->all());
            $GrivanceSubmission                                 =   GrivanceSubmissionModel::create([
                'Grivance_id'                                   =>  Common::getGriveanceID(),
                'Grivance_Cat_id'                               =>  $request->Grivance_Cat_id,
                'Grivance_Sub_cat'                              =>  $request->Grivance_Sub_cat,
                'Employee_id'                                   =>  $request->Employee_id,
                'status'                                        =>  'pending',
                'date'                                          =>  date('Y-m-d',strtotime($request->date)),
                'Grivance_description'                          =>  $request->Grivance_description,
                'Grivance_date_time'                            =>  $request->Grivance_date_time,
                'location'                                      =>  $request->location,
                'Grivance_Eexplination_description'             =>  $request->Grivance_Eexplination_description,
                'Grivance_Submission_Type'                      =>  $Grivance_Submission_Type,
                'grievance_informally'                          =>  $request->grievance_informally,
                'resort_id'                                     =>  $this->resort_id,
            ]);

            $imagePaths = [];
            if ($request->hasFile('Attachments')) {

                foreach ($request->file('Attachments') as $file) {

                    $SubFolder      =   "GrivanceAttachments";
                    $status         =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $this->user->GetEmployee->Emp_id,$SubFolder,true);

                    if ($status['status'] == false) {
                        break;
                    } else {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                            $filename = $file->getClientOriginalName();
                            $imagePaths[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                    }
                }

                GrivanceSubmissionModel::where('Grivance_id', $GrivanceSubmission->Grivance_id)
                                        ->update(['Attachements' => json_encode($imagePaths)]);
            }

            foreach($request->witness_id as $v)
            {
                GrivanceSubmissionWitness::create(["Witness_id" => $v,"G_S_Parent_id" => $GrivanceSubmission->id,'Wintness_Status'=>'Active']);
            }

            // Send mobile notification to HR employee
                $hrEmployee = Common::FindResortHR($this->user);
                if ($hrEmployee) {
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Grievance Submission',
                        'A grievance submission has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                        'Employee Grievance',
                        [$hrEmployee->id],
                        null,
                        false,
                        'grievance-submission',
                    );
                }

            DB::commit();
            $response['status']                             =   true;
            $response['message']                            =   'Grievance Created Successfully';
            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Mobile pre-check dialog shown before an employee starts a formal
     * grievance: "did you try to resolve this informally first?" Logged
     * standalone (not folded into GrievanceStore) so HR sees informal
     * attempts even when the employee answers "Yes" and never files a
     * formal grievance at all.
     */
    public function InformalResolution(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'resolved_informally' => 'required|in:Yes,No',
            'description'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $employee = $this->user->GetEmployee;
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee record not found.'], 404);
            }

            $record = GrievanceInformalResolution::create([
                'resort_id'            => $this->resort_id,
                'employee_id'          => $employee->id,
                'resolved_informally'  => $request->resolved_informally,
                'description'         => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Response recorded successfully.',
                'data'    => $record,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Employee_id on a grievance row is the SUBJECT of the complaint (the
     * person it's about), not the submitter — resolved the same way
     * GetEmployeeDetails() does, plus their supervisor via reporting_to.
     * Shared by the listing and detail endpoints so both surface it.
     */
    private function resolveSubjectAndSupervisor($employeeId): array
    {
        if (!$employeeId) {
            return ['employee' => null, 'supervisor' => null];
        }
        $subject = Employee::with(['resortAdmin:id,first_name,last_name', 'department:id,name', 'position:id,position_title'])->find($employeeId);
        if (!$subject) {
            return ['employee' => null, 'supervisor' => null];
        }
        $supervisor = $subject->reporting_to
            ? Employee::with('resortAdmin:id,first_name,last_name')->find($subject->reporting_to)
            : null;

        return [
            'employee' => [
                'id'         => $subject->id,
                'name'       => trim(optional($subject->resortAdmin)->first_name . ' ' . optional($subject->resortAdmin)->last_name),
                'department' => optional($subject->department)->name,
                'position'   => optional($subject->position)->position_title,
            ],
            'supervisor' => $supervisor ? [
                'id'   => $supervisor->id,
                'name' => trim(optional($supervisor->resortAdmin)->first_name . ' ' . optional($supervisor->resortAdmin)->last_name),
            ] : null,
        ];
    }

    /**
     * GET resort/grievance/my-grievances
     * Every grievance the current user has submitted (created_by is the
     * only field guaranteed to reflect the actual reporter — Employee_id
     * on the row is set from a caller-supplied value and isn't reliable
     * for "who filed this").
     */
    public function myGrievances(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $grievances = GrivanceSubmissionModel::with(['category:id,Category_Name'])
                ->where('resort_id', $this->resort_id)
                ->where('created_by', $this->user->id)
                ->orderByDesc('id')
                ->get();

            $subCatIds = $grievances->pluck('Grivance_Sub_cat')->filter()->unique()->values();
            $subCatNames = GrievanceSubcategory::whereIn('id', $subCatIds)->pluck('Sub_Category_Name', 'id');

            $witnessCounts = GrivanceSubmissionWitness::whereIn('G_S_Parent_id', $grievances->pluck('id'))
                ->get()
                ->groupBy('G_S_Parent_id')
                ->map(fn ($rows) => $rows->count());

            $data = $grievances->map(function ($g) use ($subCatNames, $witnessCounts) {
                $subjectAndSupervisor = $this->resolveSubjectAndSupervisor($g->Employee_id);
                $hasAttachments = !empty(json_decode((string) $g->Attachements, true));

                return [
                    'id'                  => $g->id,
                    'grievance_id'        => $g->Grivance_id,
                    'category'            => optional($g->category)->Category_Name,
                    // "Offense" in older UI copy and "subcategory" are the
                    // same underlying field — Grivance_offence_id was
                    // renamed to Grivance_Sub_cat (migration
                    // 2025_04_17_164241_add_grivance_field.php) and the web
                    // create form's leftover "SELECT OFFENSE" label targets
                    // a DOM id nothing binds to anymore. There's no second,
                    // separate offense value to expose here.
                    'subcategory'         => $subCatNames[$g->Grivance_Sub_cat] ?? null,
                    'employee'            => $subjectAndSupervisor['employee'],
                    'supervisor'          => $subjectAndSupervisor['supervisor'],
                    'submitted_on'        => $g->date,
                    'status'              => $g->status,
                    'location'            => $g->location,
                    'description'         => $g->Grivance_description,
                    'confidential'        => $g->Grivance_Submission_Type,
                    'resolved_informally' => $g->grievance_informally,
                    'witness_count'       => $witnessCounts[$g->id] ?? 0,
                    'has_attachments'     => $hasAttachments,
                ];
            });

            return response()->json(['status' => true, 'message' => 'Grievances fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET resort/grievance/{id}
     * Full detail for one of the current user's own submitted grievances,
     * including the final outcome once the review process has completed.
     */
    public function grievanceDetail(Request $request, $id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $g = GrivanceSubmissionModel::with(['category:id,Category_Name'])
                ->where('resort_id', $this->resort_id)
                ->where('created_by', $this->user->id)
                ->where('id', $id)
                ->first();

            if (!$g) {
                return response()->json(['success' => false, 'message' => 'Grievance not found.'], 404);
            }

            $subCategoryName = GrievanceSubcategory::where('id', $g->Grivance_Sub_cat)->value('Sub_Category_Name');

            $witnesses = GrivanceSubmissionWitness::where('G_S_Parent_id', $g->id)
                ->get()
                ->map(function ($w) {
                    $emp = Employee::with('resortAdmin')->find($w->Witness_id);
                    return [
                        'employee_id' => $w->Witness_id,
                        'name'        => $emp && $emp->resortAdmin
                            ? trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name)
                            : null,
                        'statement'   => $w->Statement,
                    ];
                });

            $attachments = [];
            $decoded = json_decode((string) $g->Attachements, true);
            if (is_array($decoded)) {
                foreach ($decoded as $file) {
                    $childId = $file['Child_id'] ?? null;
                    $url = null;
                    if ($childId) {
                        try {
                            $aws = Common::GetAWSFile($childId, $this->resort_id);
                            if (!empty($aws['success'])) $url = $aws['NewURLshow'];
                        } catch (\Throwable $e) {
                            // leave url null for attachments that fail to resolve
                        }
                    }
                    $attachments[] = ['filename' => $file['Filename'] ?? null, 'url' => $url];
                }
            }

            $actionTakenName = $g->action_taken ? ActionStore::where('id', $g->action_taken)->value('ActionName') : null;
            $subjectAndSupervisor = $this->resolveSubjectAndSupervisor($g->Employee_id);

            $data = [
                'id'                     => $g->id,
                'grievance_id'           => $g->Grivance_id,
                'category'               => optional($g->category)->Category_Name,
                // Same field as the legacy "Offense" label — see the note
                // in myGrievances(); no separate offense value exists.
                'subcategory'            => $subCategoryName,
                'employee'               => $subjectAndSupervisor['employee'],
                'supervisor'             => $subjectAndSupervisor['supervisor'],
                'submitted_on'           => $g->date,
                'status'                 => $g->status,
                'incident_datetime'      => $g->Grivance_date_time,
                'location'               => $g->location,
                'description'            => $g->Grivance_description,
                'explanation'            => $g->Grivance_Eexplination_description,
                'confidential'           => $g->Grivance_Submission_Type,
                'resolved_informally'    => $g->grievance_informally,
                'witnesses'              => $witnesses,
                'attachments'            => $attachments,
                'has_attachments'        => count($attachments) > 0,
                // Populated once the review process has completed.
                'outcome_type'           => $g->outcome_type,
                'action_taken'           => $actionTakenName,
                'gm_decision'            => $g->Gm_Decision,
                'gm_reason'              => $g->Gm_Resoan,
                'rejection_reason'       => $g->Rejection_reason,
            ];

            return response()->json(['status' => true, 'message' => 'Grievance detail fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
