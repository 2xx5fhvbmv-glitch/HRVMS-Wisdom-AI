<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Incidents;
use App\Models\IncidentsWitness;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Validator;
use DB;
use App\Models\IncidentCategory;
use Illuminate\Support\Facades\File;
use App\Models\Employee;
use App\Models\IncidentsMeeting;
use App\Models\IncidentsEmployeeStatements;
use Illuminate\Support\Facades\Storage;

if (!function_exists('parseIds')) {
    function parseIds($value): array
    {
        if (!$value) return [];

        if (is_array($value)) {
            return array_filter(array_map('intval', $value));
        }

        return array_filter(array_map('intval', explode(',', $value)));
    }
}

class IncidentController extends Controller
{
    protected $user;
    protected $resort_id;

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user             =   Auth::guard('api')->user();
            $this->resort_id        =   $this->user->resort_id;
        }
    }

    public function incidentDashboard()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try{
            $employee                                       = $this->user->GetEmployee;
            $emp_id                                         = $employee->id;
            $rank                                           = config('settings.Position_Rank');
            $current_rank                                   = $employee->rank ?? null;
            $available_rank                                 = $rank[$current_rank] ?? '';


            $incidentData                                   =   [];

            $baseQuery = Incidents::where("resort_id", $this->resort_id)->Where('created_by', $this->user->id);
            // ->where(function ($query) use ($emp_id) {
            //     $query->whereRaw("FIND_IN_SET(?, victims)", [$emp_id])
            //           ->orWhereRaw("FIND_IN_SET(?, involved_employees)", [$emp_id])
            //           ->orWhereIn('id', function ($subQuery) use ($emp_id) {
            //               $subQuery->select('incident_id')
            //                        ->from('incidents_witness')
            //                        ->where('witness_id', $emp_id);
            //           })
            //           ->orWhere('created_by', $this->user->id);
            // });
        
            $reportedIncidents              =   Incidents::where('resort_id', $this->resort_id)
                                                    ->where('created_by', $this->user->id)
                                                    ->where('status', 'Reported')
                                                    ->count();

            $acknowledgedIncidents          =   Incidents::where('resort_id', $this->resort_id)
                                                    ->where('created_by', $this->user->id)
                                                    ->where('status', 'Acknowledged')
                                                    ->count();

            $underInvestigationIncidents    =   Incidents::where('resort_id', $this->resort_id)
                                                    ->where('created_by', $this->user->id)
                                                    ->where('status', 'Investigation In Progress')
                                                    ->count();

            $resolvedIncidents              =   Incidents::where('resort_id', $this->resort_id)
                                                    ->where('created_by', $this->user->id)
                                                    ->where('status', 'Resolved')
                                                    ->count();

            $incidents = (clone $baseQuery)
            ->select(['id', 'incident_id', 'incident_name', 'incident_date', 'status'])
            ->orderBy("id","DESC")
            ->get();
        
            $formattedIncidents = $incidents->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'incident_id' => $incident->incident_id,
                    'incident_name' => $incident->incident_name,
                    'incident_date' => date('d M Y', strtotime($incident->incident_date)),
                    'status' => $incident->status,
                ];                                     
            });
        

            $incidentData['total_reported']                 =   $reportedIncidents;
            $incidentData['total_acknowledged']             =   $acknowledgedIncidents;
            $incidentData['total_under_investigation']      =   $underInvestigationIncidents;
            $incidentData['total_resolved']                 =   $resolvedIncidents;
            $incidentData['incidents']                      =   $formattedIncidents;

            $response['status']                             =   true;
            $response['message']                            =   'Incidents ' . $available_rank . ' Dashboard';
            $response['incident_data']                      =   $incidentData;

            return response()->json($response);

        }catch(\Exception $e){
             \Log::emergency("File: " . $e->getFile());
             \Log::emergency("Line: " . $e->getLine());
             \Log::error($e->getMessage());
             return response()->json(['success' => false, 'message' => 'Server error'], 500);
         }
    }

    public function getPreventiveInsights($incidentId)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try{
                $incidentId              =   base64_decode($incidentId);

                $incident               = Incidents::where("resort_id", $this->resort_id)
                                                    ->where('id', $incidentId)
                                                    ->select(['id', 'preventive_measures'])
                                                    ->first();

                if (!$incident) {
                return response()->json(['success' => false, 'message' => 'Incident not found.'], 200);
                }

                return response()->json([
                'success' => true,
                'message' => 'Insights fetched successfully',
                'preventive_insights' => $incident->preventive_measures
                ]);
        }
        catch(\Exception $e){
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getCategories()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try{
            $categories   =  IncidentCategory::select(['id','category_name'])->get();

            return response()->json([
                'message'   => 'Incident categories recieved successfully',
                'data'      => $categories,
            ], 200);
        }catch(\Exception $e){
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::error($e->getMessage());
                return response()->json(['success' => false, 'message' => 'Server error'], 500);
            }
    }

    public function getSubCategories(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
      
        $validator = Validator::make($request->all(), [
            'category_id'                    => 'required|exists:incident_categories,id',   
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try{
            $categoryId = $request->category_id;

            $subcategories=IncidentCategory::join('incident_subcategories as is','incident_categories.id','=','is.category_id')
                                                ->where('is.resort_id',$this->resort_id)
                                                ->where('incident_categories.resort_id',$this->resort_id)
                                                ->where('is.category_id',$categoryId)                               
                                                ->select(['is.id','is.subcategory_name'])->get();

           if($subcategories->isEmpty()){
            return response()->json([
                'message' => 'No sub category found for given category',
                ], 200);     
            }else{
                return response()->json([
                    'message' => 'Incident subcategories recieved successfully',
                    'data' => $subcategories,
                    ], 200);
            }

        }catch(\Exception $e){
                \Log::emergency("File: " . $e->getFile());
                \Log::emergency("Line: " . $e->getLine());
                \Log::error($e->getMessage());
                return response()->json(['success' => false, 'message' => 'Server error'], 500);
            }
    }

    public function AddIncident(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->merge([
            'involved_emp_ids' => json_decode($request->input('involved_emp_ids'), true),
            'victim_ids' => json_decode($request->input('victim_ids'), true),
            'witness_id' => json_decode($request->input('witness_id'), true),
        ]);

        $validator = Validator::make($request->all(), [
            'reporting_for_someone'         => 'required|in:Yes,No',
            'incident_name'                 => 'required|string',
            'victim_ids'                    => 'required_if:reporting_for_someone,Yes|array',   
            'victim_ids.*'                  => 'exists:employees,id',   
            'date'                          => 'required|date_format:Y-m-d',
            'time'                          => 'required|date_format:h:i A',
            'location'                      => 'nullable|string',
            'category_id'                   => 'required|exists:incident_categories,id',
            'subcategory_id'                => 'required|exists:incident_subcategories,id',
            'isWitness'                     => 'required|in:Yes,No',
            'witness_id'                    => 'required_if:isWitness,Yes|array',
            'witness_id.*'                  => 'exists:employees,id',
            'involved_emp_ids'              => 'required|array',
            'involved_emp_ids.*'            => 'exists:employees,id',
            'description'                   => 'nullable|string',
            'attatchements.*'               => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,doc,docx,pdf'
        ]);
            
         if ($validator->fails()) {
               return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
       
        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        DB::beginTransaction();

        try{
            $data = $validator->validated();
            $incidentID = Common::generateIncidentID();
            $imagePaths = [];
            if ($request->hasFile('attatchements')) {

                foreach ($request->file('attatchements') as $file) {
                    $SubFolder="IncidentAttatchements";
                    $status =   Common::AWSEmployeeFileUpload($this->resort_id,$file, $employee->Emp_id,$SubFolder,true);
                    
                    if ($status['status'] == false) {
                        break;
                    } else {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                            $filename = $file->getClientOriginalName();
                            $imagePaths[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                    }
                }
            }
            
            // if ($request->hasFile('attatchements')) {
            //     $incident_doc_path                           =   config('settings.incident_attatchements');
            //     $dynamic_path                               =   $incident_doc_path . '/' . $incidentID;

            //     // Create the directory if it doesn't exist
            //     if (!Storage::exists($dynamic_path)) {
            //         Storage::makeDirectory($dynamic_path);
            //     }


            //     foreach ($request->file('attatchements') as $uploadedFile) {

            //         // Handle file upload if any attachments are provided
            //         $fileName = $uploadedFile->getClientOriginalName();
            //         Common::uploadFile($uploadedFile, $fileName, $dynamic_path);
            //         $file_path= $incident_doc_path . '/'. $incidentID. '/' . $fileName;
            //         $imagePaths[] = $file_path; 
            //     }
            // }

            if(!empty($request->victim_ids)){
                $victimIdsArray = $request->victim_ids;
                $victimIdsCommaSeparated = implode(',', $victimIdsArray); 
            }

            $empIdsArray = $request->involved_emp_ids;
            $empIdsCommaSeparated = implode(',', $empIdsArray); 

            $incident = Incidents::create([
                'resort_id'         => $this->resort_id,
                'incident_id'       => $incidentID,
                'reporter_id'       => $emp_id,
                'incident_name'     => $data['incident_name'],
                'victims'           => $victimIdsCommaSeparated ?? $emp_id,
                'incident_date'     => $data['date'],
                'incident_time'     => $data['time'],
                'location'          => $data['location'] ?? null,
                'category'          => $data['category_id'],
                'subcategory'       => $data['subcategory_id'],
                'isWitness'         => $data['isWitness'],
                'involved_employees'=> $empIdsCommaSeparated,
                'status'            => 'Reported', 
                'description'       => $data['description'] ?? null,
                'attachements'      => json_encode($imagePaths) ?? null,
            ]);
         
            if ($data['isWitness'] === 'Yes') {
                foreach($data['witness_id'] as $witness_id){
                    IncidentsWitness::create([
                        'incident_id' => $incident->id,
                        'witness_id'  =>   $witness_id,
                    ]);
                }
            }
    
            DB::commit();

            return response()->json([
                'message' => 'Incident created successfully',
                'data' => $incident,
            ], 200);

        }catch(\Exception $e){
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function incidentDetails($incidentId)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        try{
            $incidentId                                     =   base64_decode($incidentId);

            $incident = Incidents::where("resort_id", $this->resort_id)
                                    ->where('created_by',$this->user->id)
                                    ->where('id', $incidentId)
                                    ->first();

            if(empty($incident)){
                return response()->json([
                    'message' => 'No such incident found',
                ], 200);
            }

            $incidents =Incidents::where("resort_id", $this->resort_id)
            ->where('id',$incidentId)
            ->where('created_by',$this->user->id)
            ->select(['id','incident_id','incident_name','incident_date','status','priority'])->get();

            $summary =Incidents::where('id',$incidentId)->where('created_by',$this->user->id)->select('description')->get();

            $allEmployeeIds = [];

                $victimIds= parseIds($incident->victims);
                $involvedIds =  parseIds($incident->involved_employees);
                $allEmployeeIds = array_merge($allEmployeeIds, $victimIds,$involvedIds);

                if ($incident->isWitness == 'Yes') {
                    $witnessId = Incidents::join('incidents_witness as iw','iw.incident_id','=','incidents.id')
                                            ->where('resort_id',$this->resort_id)
                                            ->where('incidents.id',$incidentId)
                                            ->select('iw.witness_id')
                                            ->first();
                    $witnessId =  parseIds($witnessId);
                    $allEmployeeIds = array_merge($allEmployeeIds, $witnessId);
                }
            
            $allEmployeeIds = array_unique(array_filter($allEmployeeIds));

            $participants=Employee::whereIn('employees.id', $allEmployeeIds)
                        ->join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                        ->join('resort_positions as t2', "t2.id", "=", "employees.Position_id")
                        ->select(['employees.id',
                                    't1.first_name',
                                    't1.last_name',
                                    't2.code',
                                    't2.position_title',
                                    'employees.Admin_Parent_id as Parentid',
                                ])
                        ->get()
                        ->map(function ($item) {
                            $item->profile_picture = Common::getResortUserPicture($item->Parentid);
                            unset($item->Parentid);
                            return $item;
                    });

            $incidentData['incidents']             =   $incidents;
            $incidentData['summary']               =   $summary;
            $incidentData['participants']          =   $participants;

            $attachments = json_decode($incident->attachements, true);
            if (!empty($attachments)) {
                $attachmentUrls = [];
                foreach ($attachments as $filePath) {
                    $attachmentUrls[] = url($filePath);
                }
                $incidentData['findings']             =   $attachmentUrls;
            }

            $meeting=IncidentsMeeting::where('incident_id', $incidentId)->first();

            if ($meeting) {

                $meetings=IncidentsMeeting::join('incidents_investigation_meetings_participants as t1','t1.meeting_id','=','incidents_investigation_meetings.id')
                                            ->join('employees as e','e.id','=','t1.participant_id')
                                            ->where('incidents_investigation_meetings.incident_id',$incidentId)
                                            ->select(['incidents_investigation_meetings.id as meeting_id',
                                            'incidents_investigation_meetings.meeting_date',
                                            'incidents_investigation_meetings.meeting_time',
                                            'incidents_investigation_meetings.location',
                                            'incidents_investigation_meetings.meeting_type',
                                            'incidents_investigation_meetings.attachments',
                                            'e.Admin_Parent_id as Parentid',])
                                            ->get()
                                            ->groupBy('meeting_id')
                                            ->map(function ($group) {
                                                $first = $group->first();
                                        
                                                $meeting_attachmentUrls = []; 
               
                                                $meeting_attachments = json_decode($first->attachments, true);
                                                if (!empty($meeting_attachments)) {
                                                    foreach ($meeting_attachments as $filePath) {
                                                        $meeting_attachmentUrls[] = url($filePath);
                                                    }
                                                }

                                                return [
                                                    'id' => $first->meeting_id,
                                                    'meeting_date' => date('d M', strtotime($first->meeting_date)),
                                                    'meeting_time' => date('H:i', strtotime($first->meeting_time)),
                                                    'location' => $first->location,
                                                    'meeting_type' => $first->meeting_type,
                                                    'attachments' => $meeting_attachmentUrls,
                                                    'profile_pictures' => $group->pluck('Parentid')
                                                                                ->unique()
                                                                                ->map(fn($id) => Common::getResortUserPicture($id))
                                                                                ->values()
                                                ];
                                            })->values();

                    $incidentData['meetings']             =   $meetings;

                }

            return response()->json([
                'message' => 'Incident fetched successfully',
                'data'    => $incidentData,
            ], 200);

        }catch(\Exception $e){
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function incidentCalender(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'year'                    => 'date_format:Y', 
            'month'                   => 'date_format:m',   
            'filter'                  => 'in:weekly,monthly,daily',
            'category_id'             => 'exists:incident_categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
       
        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;
        $filter                                             =   request('filter');

        try{   
            $data               =           $validator->validated();

            $meetingsQuery      =       IncidentsMeeting::join('incidents', 'incidents.id', '=', 'incidents_investigation_meetings.incident_id');

            if ($request->filled('month') && $request->filled('year')) {
                $meetingsQuery->whereMonth('meeting_date', $data['month'])
                              ->whereYear('meeting_date', $data['year']);
            }

            if ($filter === 'daily') {
                $meetingsQuery->whereDate('meeting_date', now()->toDateString());
            } elseif ($filter === 'weekly') {
                $meetingsQuery->whereBetween('meeting_date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($filter === 'monthly') {
                $meetingsQuery->whereMonth('meeting_date', now()->month)
                            ->whereYear('meeting_date', now()->year);
            }

            // Apply Participant or Creator Filter
            $meetingsQuery->where(function ($q) use ($emp_id) {
                $q->where('incidents_investigation_meetings.created_by', $this->user->id)
                ->orWhereIn('incidents_investigation_meetings.id', function ($sub) use ($emp_id) {
                    $sub->select('meeting_id')
                        ->from('incidents_investigation_meetings_participants')
                        ->where('participant_id', $emp_id);
                });
            });

            if ($request->filled('category_id')) {
                $meetingsQuery->where('incidents.category', $request->category_id);
            }

            // Fetch Data With Participants
            $meetings   =   $meetingsQuery
                            ->leftJoin('incidents_investigation_meetings_participants as t1', 't1.meeting_id', '=', 'incidents_investigation_meetings.id')
                            ->leftJoin('employees as e', 'e.id', '=', 't1.participant_id')
                            ->select([
                                'incidents_investigation_meetings.id as meeting_id',
                                'incidents_investigation_meetings.meeting_date',
                                'incidents_investigation_meetings.meeting_time',
                                'incidents_investigation_meetings.location',
                                'incidents_investigation_meetings.meeting_type',
                                'incidents_investigation_meetings.incident_id',
                                'incidents_investigation_meetings.created_by',
                                'incidents_investigation_meetings.attachments',
                                'e.Admin_Parent_id as Parentid',
                            ])
                            ->get()
                            ->groupBy('meeting_id')
                            ->map(function ($group) {
                                $first = $group->first();

                                $meeting_attachmentUrls = []; 
                        
                                $meeting_attachments = json_decode($first->attachments, true);
                                if (!empty($meeting_attachments)) {
                                    foreach ($meeting_attachments as $filePath) {
                                        $meeting_attachmentUrls[] = url($filePath);
                                    }
                                }

                                $status = 'scheduled';
                                if (strtotime($first->meeting_date) < strtotime(date('Y-m-d'))) {
                                    $status = 'overdue';
                                }

                                return [
                                    'id'                => $first->meeting_id,
                                    'meeting_date'      => date('d M', strtotime($first->meeting_date)),
                                    'meeting_time'      => date('H:i', strtotime($first->meeting_time)),
                                    'location'          => $first->location,
                                    'meeting_type'      => $first->meeting_type,
                                    'attachments'       => $meeting_attachmentUrls,
                                    'status'            => $status,
                                    'profile_pictures'  => $group->pluck('Parentid')->filter()->unique()
                                                                ->map(fn($id)   => Common::getResortUserPicture($id))
                                                                ->values()
                                                        ];
                            })
                            ->values(); 

            if($meetings->isEmpty()){
                return response()->json([
                    'status'   => false,
                    'message' => 'No meetings found',
                ], 200);
            }else{
                return response()->json([
                    'status'   => true,
                    'message'   => 'Incident meetings fetched successfully',
                    'data'      => $meetings,
                ], 200);
            }

        }catch(\Exception $e){
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function provideStatement(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'incident_id'                  => 'required|exists:incidents,id', 
            'statement'                    => 'required', 
            'attatchements.*'              => 'file|mimes:jpeg,png,jpg,mp4,mov,doc,docx,pdf'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user                                               =   Auth::guard('api')->user();
        $employee                                           =   $user->GetEmployee;
        $emp_id                                             =   $employee->id;

        try{
            $data               =           $validator->validated();

            $incident           =       Incidents::where("resort_id", $this->resort_id)
                                                    ->where('id',$data['incident_id'])
                                                    ->select(['id','incident_name','incident_date','involved_employees','incident_id'])
                                                    ->first();

            $witness            =       IncidentsWitness::join('incidents as i','i.id','=','incidents_witness.incident_id')
                                                            ->where('i.resort_id',$this->resort_id)
                                                            ->where('i.id',$data['incident_id'])                          
                                                            ->where('i.isWitness','=','Yes')
                                                            ->select(['incidents_witness.id','incidents_witness.witness_id'])
                                                            ->first();

            $involvedEmployeeIds        = explode(',', $incident->involved_employees);
            $isInvolved                 = in_array($emp_id, $involvedEmployeeIds);
            $isWitness                  = $witness && $witness->witness_id == $emp_id;

            if (!$isInvolved && !$isWitness) {
                return response()->json(['success' => false, 'message' => 'You are not authorized to submit a statement for this incident.'], 200);
            }
             
              $imagePaths = [];         

                if ($request->hasFile('attatchements')) {
                    $incident_doc_path  = config('settings.incident_statements');
                    $absolute_path      =  $incident_doc_path . '/' . $incident->incident_id . '/' . $emp_id;
    
                    if (!file_exists(public_path($absolute_path))) {
                        mkdir(public_path($absolute_path), 0755, true);
                    }
    
                        foreach ($request->file('attatchements') as $uploadedFile) {
                            $fileName = $uploadedFile->getClientOriginalName();
                            Common::uploadFile($uploadedFile, $fileName, $absolute_path);
                            $file_path      = $absolute_path . '/' . $fileName;
                            $imagePaths[]   = $file_path; 
                        }    
                } 

                if ($isWitness) {
                    $witness_statement      =   IncidentsWitness::where('id', $witness->id)
                                                                    ->update([
                                                                        'witness_statements'      => $data['statement'],
                                                                        'witness_statement_file'  =>  json_encode($imagePaths),
                                                                        'witness_status'          => 'Acknowledged',
                                                                    ]);
                    
                        $incidentData['witness_statement']             =   'Witness statement given successfully';
                 }                       
            
            if($isInvolved){
                $employee_statement     =       IncidentsEmployeeStatements::create([
                    'incident_id'       =>$data['incident_id'],
                    'employee_id'       =>$emp_id,
                    'statement'         => $data['statement'],
                    'document_path'     =>  json_encode($imagePaths),
                    'status'            =>'submitted',
                ]);
                $incidentData['employee_statement']             =   'Employee statement given successfully';
             }
             
            $formattedIncident = [
                'id' => $incident->id,
                'incident_name' => $incident->incident_name,
                'incident_date' => date('d M Y', strtotime($incident->incident_date)),  
            ];

            $incidentData['incident']                       =   $formattedIncident;           

            $response['status']                             =   true;
            $response['message']                            =   'Your statement for incident #'. $incident->incident_id .' has been successfully submitted';
            $response['incident_data']                      =   $incidentData;

            return response()->json($response);
        }catch(\Exception $e){
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
