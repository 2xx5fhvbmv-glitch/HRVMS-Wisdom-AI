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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Helpers\StorageHelper;

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
    /**
     * Sends a real FCM push to every device token registered against every
     * active employee AT THE CALLER'S OWN RESORT (never cross-resort — this
     * app is multi-tenant) and returns the raw per-device result (FCM
     * credentials missing/invalid, no device token registered, or the
     * actual FCM send result per device) — a single call to confirm push
     * notifications are working end to end, not just that the DB insert
     * (Common::nofitication) succeeded. Hits every logged-in device at the
     * resort, so use deliberately, not as a routine health check.
     */
    public function testPushNotification(Request $request)
    {
        if (!$this->user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json(Common::sendTestPushToResort($this->resort_id));
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
        'GetEmployee.section',
        'GetEmployee.reportingToAdmin',
        // proficiency_level (Native/Fluent/Intermediate) exists on the web
        // Additional Information tab but was left out of this select list,
        // so the column was always silently dropped for mobile.
        'GetEmployee.employeeLanguage:id,employee_id,language,proficiency_level',
        // Web portal's Education/Qualification tab shows this data, but it
        // was never eager-loaded here, so the mobile app's Personal Info
        // screen always got a blank Education field regardless of what HR
        // had actually entered.
        'GetEmployee.education',
        'GetEmployee.experiance',
        // Employment Information tab on web also shows Salary/Allowances/
        // Bank Details — none of these were eager-loaded, so mobile's
        // Employment Information screen was missing them entirely.
        'GetEmployee.allowance.allowanceName:id,particulars',
        'GetEmployee.bankDetails',
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

          // Wisdom AI mobile access architecture: rank + department alone
          // can't be guessed correctly for HR-assigned roles (Clinic
          // Manager, SOS response team, L&D Manager, Security Officer/
          // Manager, Engineering/Housekeeping HOD vs employee) — mobile
          // menus/screens are gated client-side off this payload, not by
          // any new API 403 here.
          $moduleAccessPayload = Common::buildModuleAccessPayload($profile, $profile->GetEmployee);
          $profileArray['get_employee']['department']      = $moduleAccessPayload['department'];
          $profileArray['get_employee']['access_groups']    = $moduleAccessPayload['access_groups'];
          $profileArray['get_employee']['module_access']    = $moduleAccessPayload['module_access'];
          foreach ($moduleAccessPayload['flat'] as $flatKey => $flatValue) {
              $profileArray['get_employee'][$flatKey] = $flatValue;
          }

          // religion is stored as "0"/"1" (see the web Employee create form's
          // <select id="religion">) — mobile only gets the raw code, so add
          // a human-readable companion field the same way rank_type is added
          // for rank above, rather than changing the raw `religion` value
          // and risking breaking whatever already reads it.
          $empReligion = $profileArray['get_employee']['religion'] ?? null;
          $profileArray['get_employee']['religion_type'] = ((string) $empReligion === '1') ? 'Muslim' : 'Non-Muslim';

          // Mobile's GetEmployee.fromJson casts `education`/`experiance` as
          // String? — sending the raw relation array (even an empty [])
          // crashes the whole Profile screen, since a List is never
          // assignable to String?. Collapse each relation down to one
          // human-readable summary string (or null) instead of the array.
          $educationRows = $profileArray['get_employee']['education'] ?? [];
          $profileArray['get_employee']['education'] = collect($educationRows)->map(function ($e) {
              $title = $e['degree'] ?: ($e['education_level'] ?? '');
              $summary = trim($title . (!empty($e['institution_name']) ? ' - ' . $e['institution_name'] : ''));
              return $summary . (!empty($e['attendance_period']) ? ' (' . $e['attendance_period'] . ')' : '');
          })->filter()->implode('; ') ?: null;

          $experianceRows = $profileArray['get_employee']['experiance'] ?? [];
          $profileArray['get_employee']['experiance'] = collect($experianceRows)->map(function ($e) {
              $summary = trim(($e['job_title'] ?? '') . (!empty($e['company_name']) ? ' at ' . $e['company_name'] : ''));
              return $summary . (!empty($e['duration']) ? ' (' . $e['duration'] . ')' : '');
          })->filter()->implode('; ') ?: null;

          // Employment Information tab (web) shows a human-readable Benefit
          // Grid Level label, not the raw grade code — falls back to rank
          // when benefit_grid_level isn't set, same as detail.blade.php.
          $eligibility = config('settings.eligibilty') ?? [];
          $effectiveBgl = $profileArray['get_employee']['benefit_grid_level'] ?? null;
          if (empty($effectiveBgl) && !empty($empRank) && isset($eligibility[$empRank])) {
              $effectiveBgl = $empRank;
          }
          $profileArray['get_employee']['benefit_grid_level_label'] = ($effectiveBgl && isset($eligibility[$effectiveBgl]))
              ? $eligibility[$effectiveBgl]
              : 'N/A';

          // Salary Details tab: total monthly earning (basic + allowances,
          // converted to MVR) and EWT status/indicative deduction — computed
          // in the web controller (EmployeeController::detail), never
          // exposed via API, so mobile only had the raw basic_salary figure.
          $employeeModel = $profile->GetEmployee;
          $conversionRate = optional(\App\Models\ResortSiteSettings::where('resort_id', $this->resort_id)->first())->DollertoMVR ?? 15.42;
          $basicSalary = (float) ($profileArray['get_employee']['basic_salary'] ?? 0);
          $basicMvr = ($profileArray['get_employee']['basic_salary_currency'] ?? null) === 'USD' ? $basicSalary * $conversionRate : $basicSalary;

          $allowanceRows = $employeeModel ? $employeeModel->allowance : collect();
          $totalAllowanceMvr = $allowanceRows->sum(function ($a) use ($conversionRate) {
              $amt = (float) ($a->amount ?? 0);
              return ($a->amount_unit ?? 'USD') === 'USD' ? $amt * $conversionRate : $amt;
          });
          $totalMonthlyEarningMvr = $basicMvr + $totalAllowanceMvr;
          $profileArray['get_employee']['total_monthly_earning_mvr'] = round($totalMonthlyEarningMvr, 2);

          $tin = $profileArray['get_employee']['tin'] ?? null;
          $ewtStatus = $profileArray['get_employee']['ewt_status'] ?? null;
          $profileArray['get_employee']['ewt_status_label'] = $tin
              ? 'Enrolled'
              : ($totalMonthlyEarningMvr >= 30000 ? 'Not Enrolled' : 'Not Required');
          $profileArray['get_employee']['indicative_ewt_deduction_mvr'] = ($ewtStatus === 'yes' && $totalMonthlyEarningMvr > 0)
              ? Common::computeEwtDeduction((float) $totalMonthlyEarningMvr)
              : null;

          // Allowances: flatten the eager-loaded relation down to what the
          // Allowances tab actually displays (particulars/amount/unit)
          // instead of the raw employees_allowance + nested allowance_name
          // relation shape.
          $profileArray['get_employee']['allowances'] = collect($profileArray['get_employee']['allowance'] ?? [])
              ->map(function ($a) {
                  return [
                      'id'          => $a['id'] ?? null,
                      'particulars' => $a['allowance_name']['particulars'] ?? null,
                      'amount'      => $a['amount'] ?? null,
                      'amount_unit' => $a['amount_unit'] ?? null,
                  ];
              })->values();
          unset($profileArray['get_employee']['allowance']);
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
      // middle_name isn't collected by the mobile app's Personal Info
      // screen today — the approval flow (InfoUpdateController) already
      // supports it end-to-end (routes it to ResortAdmin, shows it in the
      // Request Approval diff) if it's ever present in the payload, so
      // accepting it here (once the app starts sending it) needs no
      // further backend change.
      'middle_name'                                  => 'nullable|string|max:255',
      'last_name'                                   => 'required|string|max:255',
      'personal_phone'                              => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }


    try {
      // Only carry the fields this "Personal Information" flow is meant to
      // change into info_payload — never $request->all(). statusChange()
      // in InfoUpdateController blindly writes every payload key onto
      // Employee/ResortAdmin via getFillable(), so an unfiltered payload
      // let a mobile client smuggle in employment/compensation/security
      // fields (rank, Dept_id, basic_salary, status, ...) disguised as a
      // personal-info edit. This allow-list matches exactly the fields the
      // approval side (resortAdminFields + the dob date-normalize branch)
      // and show_details.blade.php already special-case for this request
      // type.
      $personalInfoFields = ['first_name', 'middle_name', 'last_name', 'personal_phone', 'dob', 'address_line_1', 'address_line_2'];

      EmployeeInfoUpdateRequest::create([
        'resort_id'                                   => $this->resort_id,
        'title'                                       => 'Personal Information',
        'employee_id'                                 =>  $this->user->GetEmployee->id,
        'info_payload'                                => $request->only($personalInfoFields)
      ]);

      // Was fully commented out — HR never found out a mobile user had
      // submitted a profile update request at all, and only found one by
      // manually opening the Info Update Requests list. FindResortHR()
      // also only matched HR-department rank 1/2, missing any resort
      // whose HR employee is literally rank 3 — getResortHrEmployeeIds()
      // covers both and notifies every HR employee, not just one.
      $empName = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''));
      $hrIds = Common::getResortHrEmployeeIds($this->resort_id);
      if (!empty($hrIds)) {
        foreach ($hrIds as $hrId) {
          event(new \App\Events\ResortNotificationEvent(Common::nofitication(
            $this->resort_id,
            10,
            'Profile Update Request',
            $empName . ' has submitted a profile update request.',
            0,
            $hrId,
            'People'
          )));
        }
        Common::sendMobileNotification(
          $this->resort_id,
          2,
          null,
          null,
          'Profile Update Request',
          $empName . ' has submitted a profile update request.',
          'People',
          $hrIds,
          null,
          false,
          'info-update-request'
        );
      }

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

      // Security-visibility: let the employee know their password changed,
      // in case it wasn't them.
      if ($employee->GetEmployee) {
        Common::notifyEmployees($this->resort_id, [$employee->GetEmployee->id], 'Password Changed', 'Your password was changed successfully. If this wasn\'t you, please contact HR immediately.', 'Profile');
      }

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
        'profile_image' => 'required|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif',
      ],
      [
        'profile_image.mimes' => 'The image must be a type of:jpg,jpeg,png,gif,svg,webp,heic,heif',
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

          // Self-notify confirmation, same as changePassword() above.
          Common::notifyEmployees($this->resort_id, [$emp->id], 'Profile Photo Updated', 'Your profile photo was updated successfully.', 'Profile');

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

  /**
   * Step 1 of the signature capture flow (mobile + web, same spec): upload
   * the raw photo, get back a background-removed preview. Nothing is
   * saved as the real signature yet — that only happens on
   * signatureConfirm(), after the user has seen the processed result and
   * said it's clear. Near-copy of changeProfileImage()'s upload pattern,
   * with the background-removal step + a short-lived preview instead of
   * saving straight to signature_img.
   */
  public function signaturePreview(Request $request)
  {
    $user = Auth::guard('api')->user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
      'signature_image' => 'required|file|mimes:jpg,jpeg,png,gif,webp,heic,heif',
    ], [
      'signature_image.mimes' => 'The image must be a type of:jpg,jpeg,png,gif,webp,heic,heif',
    ]);
    if ($validator->fails()) {
      return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
    }

    try {
      $resortAdmin = ResortAdmin::find($user->id);
      $emp = Employee::where('Admin_Parent_id', $resortAdmin->id)->first();
      if (!$emp) {
        return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
      }

      $rawContents = file_get_contents($request->file('signature_image')->getRealPath());
      $processed = Common::removeSignatureBackground($rawContents);

      $token = Str::random(40);
      $mainFolder = $resortAdmin->resort->resort_id;
      $tempPath = $mainFolder . '/public/categorized/' . $emp->Emp_id . '/SignaturePreview/' . $token . '.png';
      StorageHelper::put($tempPath, $processed);

      // 10-minute window to confirm — same "temp file, cleaned up whether
      // confirmed or abandoned" shape the spec calls for. Scoped to this
      // resort_admin so a leaked/guessed token can't be confirmed by
      // someone else's session.
      Cache::put('signature_preview_' . $token, [
        'resort_admin_id' => $resortAdmin->id,
        'temp_path' => $tempPath,
      ], now()->addMinutes(10));

      return response()->json([
        'success' => true,
        'preview_url' => StorageHelper::temporaryUrl($tempPath, 15),
        'preview_token' => $token,
      ]);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  /**
   * Step 2 — user tapped "Yes, it's clear". Persists the previewed image
   * as the real signature_img. No reject endpoint exists by design (see
   * the mobile e-signature doc) — on "No" the app just discards the
   * preview and lets the user retry from step 1; the temp file behind an
   * abandoned preview simply expires with its cache entry.
   */
  public function signatureConfirm(Request $request)
  {
    $user = Auth::guard('api')->user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
      'preview_token' => 'required|string',
    ]);
    if ($validator->fails()) {
      return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
    }

    try {
      $resortAdmin = ResortAdmin::find($user->id);
      $cacheKey = 'signature_preview_' . $request->preview_token;
      $preview = Cache::get($cacheKey);

      if (!$preview || (int) $preview['resort_admin_id'] !== (int) $resortAdmin->id) {
        return response()->json(['success' => false, 'message' => 'Preview expired, please retake'], 200);
      }

      $emp = Employee::where('Admin_Parent_id', $resortAdmin->id)->first();
      if (!$emp) {
        return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
      }

      $mainFolder = $resortAdmin->resort->resort_id;
      $finalPath = $mainFolder . '/public/categorized/' . $emp->Emp_id . '/Signature/signature.png';
      StorageHelper::put($finalPath, StorageHelper::get($preview['temp_path']));

      $resortAdmin->signature_img = $finalPath;
      $resortAdmin->save();

      StorageHelper::delete($preview['temp_path']);
      Cache::forget($cacheKey);

      return response()->json([
        'success' => true,
        'message' => 'Signature saved',
        'signature_url' => StorageHelper::temporaryUrl($finalPath, 30),
      ]);
    } catch (\Exception $e) {
      \Log::emergency("File: " . $e->getFile());
      \Log::emergency("Line: " . $e->getLine());
      \Log::error($e->getMessage());
      return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
  }

  /** Current signature, or null if none uploaded yet — same shape the profile screen already uses for the profile photo. */
  public function getSignature(Request $request)
  {
    $user = Auth::guard('api')->user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $resortAdmin = ResortAdmin::find($user->id);
    $signatureUrl = $resortAdmin->signature_img
      ? StorageHelper::temporaryUrl($resortAdmin->signature_img, 30)
      : null;

    return response()->json(['success' => true, 'signature_url' => $signatureUrl]);
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
