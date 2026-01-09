<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GrivanceSubmissionModel;
use App\Models\GrivanceSubmissionWitness;
use App\Models\Employee;
use App\Models\GrievanceCategory;
use App\Models\GrievanceSubcategory;
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

            $Path                                           =   $path."/".$this->user->resort->resort_id."/".$GrivanceSubmission->Grivance_id;

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
                GrivanceSubmissionWitness::create(["Witness_id" => base64_decode($v),"G_S_Parent_id" => $GrivanceSubmission->id,'Wintness_Status'=>'Active']);
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
}
