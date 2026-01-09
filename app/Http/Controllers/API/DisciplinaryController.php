<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\disciplinarySubmit;
use Illuminate\Support\Facades\Auth;
use Validator;
use DB;
use App\Helpers\Common;
use Illuminate\Support\Facades\URL;

class DisciplinaryController extends Controller
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

    public function disciplinaryDashboard()
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try
        { 
            $employee_id                                        =   $this->user->GetEmployee->id;
            $start                                              =   now()->startOfMonth();
            $end                                                =   now()->endOfMonth();
            
            $activeDisciplinaryCount                            =   disciplinarySubmit::with(['offence'])
                                                                        ->where('resort_id',$this->resort_id) //show all and history of all the committe members
                                                                        ->where('Employee_id',$employee_id)
                                                                        ->whereBetween('Expiry_date', [$start, $end])       
                                                                        ->where('status', 'In_Review')
                                                                        ->count();

            $pastDisciplinaryCount                              =   disciplinarySubmit::with(['offence'])
                                                                        ->where('resort_id',$this->resort_id) //show all and history of all the committe members
                                                                        ->where('Employee_id',$employee_id)
                                                                        ->whereBetween('Expiry_date', [$start, $end])       
                                                                        ->where('status', 'resolved')
                                                                        ->count();
                                                                        
            $DisciplinarySubmissionModel                        =   disciplinarySubmit::with(['offence'])
                                                                        ->where('resort_id', $this->resort_id) //show all and history of all the committe members
                                                                        ->whereBetween('Expiry_date', [$start, $end])       
                                                                        ->where('Employee_id',$employee_id)
                                                                        ->get();

            $response['status']                                 =   true;
            $response['message']                                =   'Disciplinary Dashboard Retrieved successfully';
            $response['data']                                   =   [
                'active_disciplinary_count'                     =>  $activeDisciplinaryCount,
                'past_disciplinary_count'                       =>  $pastDisciplinaryCount,
                'recent_disciplinary'                           =>  $DisciplinarySubmissionModel,
            ];
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch disciplinary dashboard'], 500);
        }
    }

    public function disciplinaryDetails($disciplinary_id)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $disciplinary_id = base64_decode($disciplinary_id);
        try
        { 
            $employee_id                                        =   $this->user->GetEmployee->id;
            $DisciplinaryModel                                  =   disciplinarySubmit::with(['offence',
                                                                        'action:id,ActionName',
                                                                        'GetEmployee:id,Admin_Parent_id,Position_id,Emp_id',
                                                                        'GetEmployee.resortAdmin:id,first_name,last_name,profile_picture',
                                                                       ])
                                                                        ->where('resort_id', $this->resort_id) //show all and history of all the committe members
                                                                        ->where('Employee_id',$employee_id)
                                                                        ->where('id',$disciplinary_id)
                                                                        ->first();

            $DisciplinaryModel->GetEmployee->resortAdmin->profile_picture =   Common::getResortUserPicture( $DisciplinaryModel->GetEmployee->resortAdmin->id);


            $path = config('settings.DisciplinaryAttachments');
            $Path = $path."/".$this->user->resort->resort_id."/".$DisciplinaryModel->Disciplinary_id;

            $DisciplinaryModel->Attachements =  URL::asset($Path . "/" . $DisciplinaryModel->Attachements);
            $DisciplinaryModel->upload_signed_document =  URL::asset($Path . "/" . $DisciplinaryModel->upload_signed_document);

            $response['status']                                 =   true;
            $response['message']                                =   'Disciplinary Details Retrieved successfully';
            $response['disciplinary_details']                   =   $DisciplinaryModel;

            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch disciplinary dashboard'], 500);
        }
    }

    public function AcknowledgmentSubmit(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

         $validator = Validator::make($request->all(), [
            'disciplinary_id'                                   => 'required', 
            'Acknowledgment_description'                        => 'required',   
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try
        {
            $employee_id                                        =   $this->user->GetEmployee->id;
            $DisciplinaryModel                                  =   disciplinarySubmit::find($request->disciplinary_id);

            if (!$DisciplinaryModel) {
                return response()->json([
                    'success'                                   =>  false,
                    'message'                                   =>  'Disciplinary record not found'
                ], 200);
            }
            $DisciplinaryModel->Acknowledgment_description      =   $request->Acknowledgment_description;
            $DisciplinaryModel->save();
            
            // Send mobile notification to HR employee
            $hrEmployee = Common::FindResortHR($this->user);
            if ($hrEmployee) {
                Common::sendMobileNotification(
                    $this->resort_id,
                    2,
                    null,
                    null,
                    'Employee Disciplinary Acknowledgment',
                    'A disciplinary acknowledgment has been sent by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
                    'Employee Disciplinary',
                    [$hrEmployee->id],
                    null,
                );
            }
            $response['status']                                 =   true;
            $response['message']                                =   'Acknowledgment Disciplinary Submitted Successfully';
        
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch disciplinary dashboard'], 500);
        }
    }
}
