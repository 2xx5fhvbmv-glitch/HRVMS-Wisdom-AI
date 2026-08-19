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
            // Tenant-scoped — this is the mobile "who is this person"
            // lookup used right before adding someone as a witness; it had
            // no resort check at all and would return any resort's
            // employee (name/department/position/supervisor) by id.
            $Employee                                       =   Employee::with(['resortAdmin','department','position'])
                                                                    ->where('id',$request->emp_id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
            if (!$Employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
            }
            $Employee->DepartmentName                       =   $Employee->department->name;
            $Employee->PositionName                         =   $Employee->position->position_title;
            $Superviser                                     =   Employee::with(['resortAdmin'])
                                                                    ->where('id',$Employee->reporting_to)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->first();
            $data                                           =   [
                'Employee'                                  =>  $Employee,
                'Superviser'                                =>  $Superviser ? $Superviser->resortAdmin->first_name.' '. $Superviser->resortAdmin->last_name : null
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
            'date'                                          =>  'required|date',
            'Grivance_description'                          =>  'required',
            'Grivance_date_time'                            =>  'required|date_format:Y-m-d H:i:s',
            'location'                                      =>  'required',
            'witness_id'                                    =>  'required',
            'Grivance_Eexplination_description'             =>  'required',
            // Neither box checked ("Neutral" — not confidential, not
            // anonymous) is a legitimate third submission type that already
            // stores/returns correctly as "NotApplicable" below, but marking
            // this field itself required blocked that case with a 400
            // before the fallback branch ever ran. Anonymous is already
            // unvalidated/optional for the same reason.
            'Confidential'                                  =>  'nullable',
            'grievance_informally'                          =>  'required',
        ]);

        if($validator->fails())
        {
            return response()->json(['success' => false,'errors' => $validator->errors()], 400);
        }

        // Any employee id — from any resort — could be stored as a witness
        // with zero validation. Combined with the (now fixed)
        // GetEmployeeDetails lookup above, this was the full leak chain:
        // preview a cross-resort employee, then submit them as a witness.
        $witnessIds = (array) $request->witness_id;
        $validWitnessCount = Employee::whereIn('id', $witnessIds)->where('resort_id', $this->resort_id)->count();
        if ($validWitnessCount !== count(array_unique($witnessIds))) {
            return response()->json(['success' => false, 'message' => 'One or more witnesses are invalid.'], 422);
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
                // The submitter is always the authenticated employee, never
                // client-supplied — a stale/wrong Employee_id in the request
                // body previously attributed the grievance to someone else
                // (e.g. GR-0003 recorded as Priya Sharma when Rani Khan filed it).
                'Employee_id'                                   =>  $this->user->GetEmployee->id,
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
            // Mobile actually posts the files as "attachments" (lowercase) —
            // hasFile()/file() are case-sensitive, so checking only
            // 'Attachments' silently skipped this entire block for every
            // real mobile submission (grievance-store still returned success
            // with nothing to error on), which is why an uploaded file never
            // came back in the detail GET despite the store call succeeding.
            $attachmentFiles = $request->file('Attachments') ?? $request->file('attachments');
            if ($attachmentFiles) {

                foreach ($attachmentFiles as $file) {

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

            // Send mobile notification to all of HR (rank 3) plus the
            // HR department's HOD/EXCOM (rank 1/2) — not just one HR manager.
                $hrEmployeeIds = Common::getResortHrEmployeeIds($this->resort_id);
                if (!empty($hrEmployeeIds)) {
                    Common::sendMobileNotification(
                        $this->resort_id,
                        2,
                        null,
                        null,
                        'Grievance Submission',
                        'A grievance submission has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                        'Employee Grievance',
                        $hrEmployeeIds,
                        $GrivanceSubmission->id,
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
     * Pending "who submitted this?" request from a key-personnel committee
     * member — null once there's nothing awaiting the grievant's response.
     */
    private function resolveIdentityDisclosure($g): ?array
    {
        if ($g->Grivance_Submission_Type !== "Yes" || $g->Request_Identity_Disclosure !== 'Requested') {
            return null;
        }
        $requester = Employee::with('resortAdmin:id,first_name,last_name')->find($g->Identity_Disclosure_Requested_By);
        return [
            'requested_by' => $requester ? trim(optional($requester->resortAdmin)->first_name . ' ' . optional($requester->resortAdmin)->last_name) : null,
        ];
    }

    /**
     * Investigation files / older-style attachments are stored as a plain
     * comma-separated filename list under GrievanceSubmission/{resort_id}
     * (see GrivanceController::InvestigationReportStore on the web side) —
     * not the [{Filename,Child_id}] JSON shape used by grievance-store's
     * own Attachments. Different storage convention, needs its own resolver.
     */
    private function resolvePlainFileUrls(?string $commaSeparated): array
    {
        if (empty($commaSeparated)) {
            return [];
        }
        $basePath = config('settings.GrievanceSubmission') . '/' . $this->resort_id;
        $urls = [];
        foreach (explode(',', $commaSeparated) as $filename) {
            $filename = trim($filename);
            if ($filename === '') {
                continue;
            }
            try {
                $path = $basePath . '/' . $filename;
                if (\App\Helpers\StorageHelper::disk()->exists($path)) {
                    $urls[] = ['filename' => $filename, 'url' => \App\Helpers\StorageHelper::temporaryUrl($path, 30)];
                }
            } catch (\Throwable $e) {
                // skip files that fail to resolve
            }
        }
        return $urls;
    }

    /**
     * GET resort/grievance/my-grievances
     * Every grievance the current user has submitted (created_by, auto-set
     * by the model from the authenticated session, is the only field
     * guaranteed to reflect the actual reporter — GrievanceStore() now
     * hardcodes Employee_id to the authenticated employee's own id too, so
     * on mobile-submitted rows the two are always the same person; this
     * comment used to say Employee_id was still caller-supplied, which is
     * no longer true).
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
                    'identity_disclosure_request' => $this->resolveIdentityDisclosure($g),
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
                    $emp = Employee::with('resortAdmin')->where('resort_id', $this->resort_id)->find($w->Witness_id);
                    return [
                        'employee_id' => $w->Witness_id,
                        'name'        => $emp && $emp->resortAdmin
                            ? trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name)
                            : null,
                        'statement'   => $w->Statement,
                        'status'      => $w->status,
                        'attachments' => $this->resolvePlainFileUrls($w->Attachement),
                    ];
                });

            // Committee investigation updates — entered on the web portal
            // (GrivanceController::InvestigationReportStore), never exposed
            // to mobile before. One GrivanceInvestigationModel row per
            // grievance (start/resolution date), many
            // GrivanceInvestigationChildModel rows underneath — one per
            // committee stage update/follow-up entry.
            $investigation = \App\Models\GrivanceInvestigationModel::where('Grievance_s_id', $g->id)->first();
            $investigationData = null;
            if ($investigation) {
                $timeline = \App\Models\GrivanceInvestigationChildModel::where('investigation_p_id', $investigation->id)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($row) {
                        $member = $row->Committee_member_id ? Employee::with('resortAdmin')->where('resort_id', $this->resort_id)->find($row->Committee_member_id) : null;
                        return [
                            'investigation_stage'   => $row->investigation_stage,
                            'explanation'            => $row->Grivance_Eexplination_description,
                            'recommendations'        => $row->inves_find_recommendations,
                            'committee_member'       => $member && $member->resortAdmin
                                ? trim($member->resortAdmin->first_name . ' ' . $member->resortAdmin->last_name)
                                : null,
                            'follow_up_action'       => $row->follow_up_action,
                            'follow_up_description'  => $row->follow_up_description,
                            'resolution_note'        => $row->resolution_note,
                            'date'                   => $row->created_at,
                        ];
                    });
                $investigationData = [
                    'investigation_start_date'  => $investigation->inves_start_date,
                    'expected_resolution_date'  => $investigation->resolution_date,
                    'investigation_files'       => $this->resolvePlainFileUrls($investigation->investigation_files),
                    'timeline'                  => $timeline,
                ];
            }

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
                'investigation'          => $investigationData,
                // Populated once the review process has completed.
                'outcome_type'           => $g->outcome_type,
                'action_taken'           => $actionTakenName,
                'gm_decision'            => $g->Gm_Decision,
                'gm_reason'              => $g->Gm_Resoan,
                'rejection_reason'       => $g->Rejection_reason,
                'identity_disclosure_request' => $this->resolveIdentityDisclosure($g),
            ];

            return response()->json(['status' => true, 'message' => 'Grievance detail fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST resort/grievance/identity-disclosure-respond
     * The grievant answers a pending "who submitted this?" request. Approve
     * grants that key person permanent access (added to Identity_Disclosed_To);
     * reject just clears the pending request so they can be asked again later.
     */
    public function respondIdentityDisclosure(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id'     => 'required',
            'action' => 'required|in:approve,reject',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $g = GrivanceSubmissionModel::where('resort_id', $this->resort_id)
                ->where('created_by', $this->user->id)
                ->where('id', $request->id)
                ->first();

            if (!$g) {
                return response()->json(['success' => false, 'message' => 'Grievance not found.'], 404);
            }

            if ($g->Request_Identity_Disclosure !== 'Requested') {
                return response()->json(['success' => false, 'message' => 'No pending identity disclosure request.'], 400);
            }

            $requesterId = $g->Identity_Disclosure_Requested_By;
            $approved    = $request->action === 'approve';

            if ($approved) {
                $disclosedTo   = $g->Identity_Disclosed_To ?? [];
                $disclosedTo[] = $requesterId;
                $g->Identity_Disclosed_To = array_values(array_unique($disclosedTo));
            }
            $g->Request_Identity_Disclosure = null;
            $g->Identity_Disclosure_Requested_By = null;
            $g->save();

            if ($requesterId) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Identity Disclosure ' . ($approved ? 'Approved' : 'Declined'),
                    'Your request to view the submitter of grievance ' . $g->Grivance_id . ' was ' . ($approved ? 'approved.' : 'declined.'),
                    'Grievance Identity Disclosure Response',
                    [$requesterId],
                    $g->id,
                );
            }

            return response()->json(['status' => true, 'message' => 'Response recorded successfully.'], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET resort/grievance/witness-statement-request/{grievance_id}
     * Mirrors the Incident module's getStatementRequest() — the fetch step
     * the push notification (RequestForStatement(), web side) had nothing
     * to lead to before this. Caller must be a registered witness on the
     * grievance.
     */
    public function witnessStatementRequest($grievanceId)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $emp_id = $this->user->GetEmployee->id;

        try {
            $grievanceId = base64_decode($grievanceId, true) ?: $grievanceId;

            $g = GrivanceSubmissionModel::with(['category:id,Category_Name'])
                ->where('resort_id', $this->resort_id)
                ->where('id', $grievanceId)
                ->first();

            if (!$g) {
                return response()->json(['success' => false, 'message' => 'Grievance not found.'], 200);
            }

            $witness = GrivanceSubmissionWitness::where('G_S_Parent_id', $g->id)
                ->where('Witness_id', $emp_id)
                ->first();

            if (!$witness) {
                return response()->json(['success' => false, 'message' => 'You are not registered as a witness for this grievance.'], 200);
            }

            $subCategoryName = GrievanceSubcategory::where('id', $g->Grivance_Sub_cat)->value('Sub_Category_Name');

            return response()->json([
                'success' => true,
                'message' => 'Witness statement request fetched successfully',
                'data' => [
                    'grievance' => [
                        'id'            => $g->id,
                        'grievance_id'  => $g->Grivance_id,
                        'category'      => optional($g->category)->Category_Name,
                        'subcategory'   => $subCategoryName,
                        'incident_datetime' => $g->Grivance_date_time,
                        'location'      => $g->location,
                        'description'   => $g->Grivance_description,
                    ],
                    'existing_statement' => $witness->Statement,
                    'already_submitted'  => $witness->status === 'Submitted',
                    'can_submit'         => $witness->status !== 'Submitted',
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST resort/grievance/witness-statement — submit. Text is required;
     * voice recording and file/image attachments both go through the same
     * `attachments[]` field (mimes cover all three), stored the same
     * comma-separated-filename way InvestigationReportStore() already uses
     * for this module, resolved back via resolvePlainFileUrls().
     */
    public function submitWitnessStatement(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'grievance_id'     => 'required|exists:grivance_submission_models,id',
            'statement'        => 'required',
            'attachments.*'    => 'file|mimes:jpeg,png,jpg,heic,heif,mp4,mov,doc,docx,pdf,mp3,m4a,wav,aac,ogg',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $emp_id = $this->user->GetEmployee->id;

        try {
            $witness = GrivanceSubmissionWitness::where('G_S_Parent_id', $request->grievance_id)
                ->where('Witness_id', $emp_id)
                ->first();

            if (!$witness) {
                return response()->json(['success' => false, 'message' => 'You are not registered as a witness for this grievance.'], 200);
            }

            $filenames = [];
            if ($request->hasFile('attachments')) {
                $basePath = config('settings.GrievanceSubmission') . '/' . $this->resort_id;
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    \App\Helpers\StorageHelper::put($basePath . '/' . $filename, file_get_contents($file->getRealPath()));
                    $filenames[] = $filename;
                }
            }

            $witness->Statement   = $request->statement;
            $witness->Attachement = !empty($filenames) ? implode(',', $filenames) : null;
            $witness->status      = 'Submitted';
            $witness->save();

            $g = GrivanceSubmissionModel::find($request->grievance_id);

            return response()->json([
                'status'  => true,
                'message' => 'Your statement for grievance #' . optional($g)->Grivance_id . ' has been successfully submitted',
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
