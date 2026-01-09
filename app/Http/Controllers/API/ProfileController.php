<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\Country;
use App\Models\EmployeeInfoUpdateRequest;
use App\Models\EmployeeLanguage;
use App\Models\ResortNotification;
use App\Models\VisaEmployeeExpiryData;
use Illuminate\Support\Facades\Session;
use DB;
use File;
use App\Helpers\Common;
use DateTime;
use DateTimeZone;
use Validator;
use Storage;

class ProfileController extends Controller
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
  public function getProfile(Request $request)
  {
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    try {
      // Get the authenticated user's details
      $user = Auth::guard('api')->user();

      // Fetch the user's complete profile from the ResortAdmin model
      $profile = ResortAdmin::with([
        'GetEmployee',
        'GetEmployee.reportingTo',
        'GetEmployee.resort_divisions',
        'GetEmployee.resort_positions',
        'GetEmployee.department',
        'GetEmployee.reportingToAdmin',
        'GetEmployee.employeeLanguage:id,employee_id,language',
      ])->find($user->id);

      if ($profile) {
        $profileArray     = $profile->toArray(); // Convert the Eloquent model to an array
        $profileArray['profile_picture'] = Common::getResortUserPicture($profileArray['id']);
        // Add rank_type to get_employee
        if (isset($profileArray['get_employee'])) {
          $empRank      = $profileArray['get_employee']['rank'] ?? null; // Employee's rank
          $rankConfig   = config('settings.Position_Rank'); // Fetch rank config
          $rankType     = array_key_exists($empRank, $rankConfig) ? $rankConfig[$empRank] : null;

          // Assign rank_type to the get_employee array
          $profileArray['get_employee']['rank_type'] = $rankType;
        }

        return response()->json(['success' => true, 'profile' => $profileArray,]);
      }

      if (!$profile) {
        return response()->json(['success' => false, 'message' => 'Profile not found'], 200);
      }
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  public function profilePersonalUpdate(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'first_name'                                  => 'required|string|max:255',
      'last_name'                                   => 'required|string|max:255',
      'personal_phone'                              => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }


    try {
      EmployeeInfoUpdateRequest::create([
        'resort_id'                                   => $this->resort_id,
        'title'                                       => 'Personal Information',
        'employee_id'                                 =>  $this->user->GetEmployee->id,
        'info_payload'                                => $request->all()
      ]);

      // Send mobile notification to HR employee
      $hrEmployee = Common::FindResortHR($this->user);
    //   Common::sendMobileNotification(
    //         $this->resort_id,
    //         2,
    //         null,
    //         null,
    //         'Profile Update Request',
    //         'A profile update request has been submitted by ' . $this->user->first_name . ' ' . $this->user->last_name . '.',
    //         'People',
    //         [$hrEmployee->id],
    //         null,
    //     );

      $response['status']      = true;
      $response['message']     = 'Profile Updated Request Sent to HR Successfully';

      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['status' => false, 'message' => $e->getMessage()]);
    }
  }

  public function profileEmployeeUpdate(Request $request)
  {
    try {
      $user = Auth::guard('api')->user();

      $resortAdmin = ResortAdmin::with('GetEmployee')->find($user->id);

      if (!$resortAdmin) {
        return response()->json(['status' => false, 'message' => 'ResortAdmin not found'], 200);
      }
      // Update fields in the related Employee table
      $employee                                   = $resortAdmin->GetEmployee;
      if ($employee) {
        $skill                                    = $request->skill;
        $skillArray                               = is_array($skill) ? $skill : json_decode($skill, true) ?? [];
        $skillArray                               = array_map('strtolower', $skillArray);
        foreach ($skillArray as $key => $value) {
          EmployeeLanguage::create([
            'resort_id'                             =>  $this->resort_id,
            'employee_id'                           =>  $this->user->GetEmployee->id,
            'language'                              =>  $value,
          ]);
        }
      }
      $response['status']                         =   true;
      $response['message']                        =   'Profile Request updated';

      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['status' => false, 'message' => $e->getMessage()]);
    }
  }

  public function changePassword(Request $request)
  {
    try {
      if ($request->password == '') {
        $response['status']   = false;
        $response['message']  = 'Password is required';
        return response()->json($response);
      }

      if ($request->confirm_password == '') {
        $response['status']   = false;
        $response['message']  = 'Confirm password is required';
        return response()->json($response);
      }

      if ($request->password != $request->confirm_password) {
        $response['status']   = false;
        $response['message']  = 'Confirm password does not match';
        return response()->json($response);
      }

      if (strlen($request->password) != 6) {
        $response['status']   = false;
        $response['message']  = 'Password must be 6 digit number';
        return response()->json($response);
      }

      $employee           = Auth::guard('api')->user();
      $password           = bcrypt($request->password);

      $employee->password = $password;
      $employee->save();

      $accessToken        = $employee->token();
      $accessToken->revoke();

      Auth::guard('employee')->logout();

      $response['status']   = true;
      $response['message']  = 'Password updated';
      return response()->json($response);
    } catch (\Exception $e) {
      $response['status']   = false;
      $response['data']     = [];
      $response['message']  = $e->getMessage();
      return response()->json($response);
    }
  }

  public function getnationality()
  {
    $employee           = Auth::guard('api')->user();
    if (!$employee) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    try {
      $nationality = Country::select('id', 'name')->get();

      $response['status']                             =   true;
      $response['message']                            =   'Successfully fetch countries';
      $response['accomodation_data']                  =   $nationality;
      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  public function changeProfileImage(Request $request)
  {
    $user           = Auth::guard('api')->user();

    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }


      $validator = Validator::make($request->all(),
      [
        'profile_image' => 'required|file|mimes:jpg,jpeg,png',
      ],
      [
        'profile_image.mimes' => 'The image must be a type of:jpg,jpeg,png',
      ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
    }

    try {
      $profile    = $request->file('profile_image');

      if ($profile) {
          $resortAdmin        = ResortAdmin::find($user->id);

          $emp = Employee::where('Admin_Parent_id', $resortAdmin->id)->first();
          if (!$emp) {
              return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
          }
          $main_folder = $resortAdmin->resort->resort_id;
          $basePath = 'app/'.$main_folder . '/public/categorized/' .$emp->Emp_id.'/Profile';
          $path     = Common::UploadProfileAwsPic($basePath,$request->profile_image);

          if($path['status'] == false)
          {
              return response()->json(['success' => false, 'msg' => $path['msg']]);
          }
          $resortAdmin->profile_picture =$path['path'];
          $saveResortAdmin                  = $resortAdmin->save();

          $response['status']   = true;
          $response['message']  = 'Profile image uploaded successfully';
      } else {
          $response['status']   = false;
          $response['message']  = 'No profile image uploaded';
      }
      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  public function getVisaCategory()
  {
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    try {
      $visaCategories                                   = config('settings.VisaCategory');
      $response['status']                               = true;
      $response['message']                              = 'Visa categories fetched successfully';
      $response['visa_category']                        = $visaCategories;

      return response()->json($response);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  public function getVisaData($categoryType)
  {
    if (!Auth::guard('api')->check()) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    try {
      $user                                             = Auth::guard('api')->user();
      $VisaEmployeeExpiryData                           = VisaEmployeeExpiryData::where('employee_id', $user->GetEmployee->id)
                                                              ->where('DocumentName', $categoryType)
                                                              ->first();
      if (!$VisaEmployeeExpiryData) {
        return response()->json(['success' => false, 'message' => 'No visa data found for this category'], 200);
      }

      $VisaEmployeeExpiryData->file_url                 = Common::GetAWSFile($VisaEmployeeExpiryData->File_child_id,$user->GetEmployee->resort_id);

      // Decode Ai_extracted_data JSON string to array
      if (!empty($VisaEmployeeExpiryData->Ai_extracted_data)) {
          $VisaEmployeeExpiryData->Ai_extracted_data    = json_decode($VisaEmployeeExpiryData->Ai_extracted_data, true);

          // Transform extracted_fields from key-value to array of Title-Value objects
            if (isset($VisaEmployeeExpiryData->Ai_extracted_data['extracted_fields']) &&
                is_array($VisaEmployeeExpiryData->Ai_extracted_data['extracted_fields'])) {

                $transformedFields = [];
                foreach ($VisaEmployeeExpiryData->Ai_extracted_data['extracted_fields'] as $key => $value) {
                    $transformedFields[] = [
                        'Title' => $key,
                        'Value' => $value
                    ];
                }

                // Set the transformed fields in the array
                $extractedData['extracted_fields'] = $transformedFields;

                // Replace the entire Ai_extracted_data property
                $VisaEmployeeExpiryData->Ai_extracted_data = $extractedData;
                // Replace the original extracted_fields with the transformed array
                // $VisaEmployeeExpiryData->Ai_extracted_data['extracted_fields'] = $transformedFields;
            }
      }
      return response()->json([
                'success'                               => true,
                'message'                               => 'Visa data fetched successfully',
                'visa_data'                             => $VisaEmployeeExpiryData
            ]);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

}
