<?php
namespace App\Http\Controllers\Resorts;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use DB;
use BrowserDetect;
use Route;
use File;
use Illuminate\Support\Facades\Session;
use App\Helpers\Common;
use App\Models\Resort;
use App\Models\Settings;
use App\Models\ResortAdmin;
use App\Models\ResortPagewisePermission;
use App\Models\ResortInteralPagesPermission;
use App\Models\Position; 
use App\Models\ResortPosition; 
use App\Models\Employee;
use Storage;
class ResortLoginController extends Controller
{
    public function logout()
    {
      Auth::guard('resort-admin')->logout();
      return redirect()->route('resort.loginindex');
    }

    public function login(Request $request)
    {
       
            $resort_admin = ResortAdmin::with(['GetEmployee', 'resort'])
                ->where('email', $request->email)
                ->whereNull('deleted_at')
                ->first();

            // 1. Account not found
            if (!$resort_admin) {
                return response()->json([
                    'success' => false,
                    'msg' => 'There is no account with this email address'
                ]);
            }
            // dd($resort_admin->resort->status);
            // 2. Resort is inactive
            if ($resort_admin->resort && $resort_admin->resort->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'msg' => 'Resort is inactive so you cannot login'
                ]);
            }
            if(!$resort_admin->GetEmployee){
                // dd($resort_admin->type);
                if($resort_admin->type == "super" && $resort_admin->is_master_admin == 1)
                {
                    // dd($resort_admin->status);
                    if($resort_admin->status == "inactive"){
                        return response()->json([
                            'success' => false,
                            'msg' => 'Your account is inactive. Please contact your administrator.'
                        ]);
                    }
                }
            }
            // 3. Employee account is inactive
            if ($resort_admin->GetEmployee && $resort_admin->GetEmployee->status === 'Inactive') {
                return response()->json([
                    'success' => false,
                    'msg' => 'Your account is inactive. Please contact your administrator.'
                ]);
            }

            // 4. Admin account is deactivated
            if ($resort_admin->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'msg' => 'Your account is inactive. Please contact your administrator.'
                ]);
            }

            // 5. Password check
            if (!Hash::check($request->password, $resort_admin->password)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Please enter a valid password'
                ]);
            }

            // 6. Successful login
            Auth::guard('resort-admin')->login($resort_admin, $request->remember);

            // 7. Redirect based on type & rank
            if ($resort_admin->type === 'sub' && $resort_admin->is_employee === 1) {
                $employee = DB::table('employees')
                    ->where('Admin_Parent_id', $resort_admin->id)
                    ->first();

                if (!$employee) {
                    return response()->json([
                        'success' => false,
                        'msg' => 'No employee record found for this staff member'
                    ]);
                }

                $Rank = config('settings.Position_Rank');
                $AvailableRank = $Rank[$employee->rank] ?? '';
                $redirectRoute = $this->checkRankWiseRoute($resort_admin);

                if($redirectRoute) 
                {
                    return response()->json([
                        'success' => true,
                        'msg' => 'Logged in',
                        'redirect_url' => route($redirectRoute)
                    ]);
                }
                else
                {
                        $this->logout();

                      return response()->json([
                            'success' => false,
                            'msg' => 'No access found please contact your administrator'
                        ]);
                }
                   $this->logout();
                return response()->json([
                    'success' => false,
                    'msg' => 'No route mapped for this employee rank'
                ]);
            }

            // 8. Default route for super admins
            $defaultRedirect = ($resort_admin->type === 'super' && $resort_admin->is_employee === 0)
                ? 'resort.master.admin_dashboard'
                : 'resort.master.hr_dashboard';

            return response()->json([
                'success' => true,
                'msg' => 'Logged in',
                'redirect_url' => route($defaultRedirect)
            ]);

        try { } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'An error occurred. Please try again later.'
            ]);
        }
    }


    // public function GetAuthPermissions($Resort_id,$department_id,$Position_id)
    // {
    //       $accessible = ResortPagewisePermission::join('resort_interal_pages_permissions as t1', 't1.resort_id', '=', 'resort_pagewise_permissions.resort_id')
    //             ->where('resort_pagewise_permissions.resort_id', $Resort_id)
    //             ->where('t1.Dept_id', $department_id)
    //             ->where('t1.position_id', $Position_id)
    //             ->get(['resort_pagewise_permissions.Module_id','t1.page_id','t1.Permission_id','resort_pagewise_permissions.page_permission_id']);


    //     $permissions=array();
    //     if($accessible->isNotEmpty())
    //     {

    //         foreach ($accessible as $value) {
    //             if (!isset($permissions[$value->Module_id]))
    //             {
    //                  $permissions[$value->Module_id] = [];
    //             }


    //             if( !in_array($value->page_id, $permissions[$value->Module_id]))
    //             {
    //                 if( $value->page_permission_id == $value->page_id)
    //                 {
    //                     dd($value->getPagePermission);
    //                     $permissions[$value->Module_id][] = $value->getPagePermission->page_name;
    //                 }
    //             }

    //         }

    //     }
    //     return   $permissions;
    // //     //  $Permissions = ResortInteralPagesPermission::where("resort_id",$Resort_id)
    // //     //  ->whereHas('resort_internal_pages', function($q) use($Permission_id, $Position_id) {
    // //     //     $q->where('permission_id', $Permission_id)
    // //     //     ->where('position_id', $Position_id);// Poistion id to specific
    // //     // })
    // //     //     ->where('Dept_id', $department_id)
    // //     //     ->where('position_id', $Position_id)
    // //     //     ->get(['resort_id','Dept_id','position_id','page_id','Permission_id'])->toArray();
    // //     // return   $Permissions;

    // //     // return $accessible = ResortPagewisePermission::whereResortId($Resort_id)->get(['resort_id','Module_id'])->toArray();
    // }

    public function showLoginForm()
    {
      if (Auth::guard('resort-admin')->check())
      {
        return redirect()->route('resort.workforceplan.dashboard');
      }
      $settings = Settings::first();
       return view('resorts.auth.login', compact('settings'));
    }

    public function permissionDenied()
    {
      $page_title = 'Permission Deny';
      $page_header = 'Permission Denied';

      return view('admin.permission_deny')->with(compact('page_title', 'page_header'));
    }

    public function ResortProfile()
    {
        $page_title = 'Profile';
        $profile =  Auth::guard('resort-admin')->user();
        return view('resorts.workforce_planning.profile',compact('profile','page_title'));
    }

    public function UpdateResortProfile(Request $request)
    {
        // dd($request);
        $path_profile_image = config('settings.ResortProfile_folder');
        $path_signature_image = config('settings.Resortsignature_folder');
       
            $resortAdmin =ResortAdmin::find($request->id);
            $resortAdmin->first_name = $request->first_name;
            $resortAdmin->middle_name = $request->middle_name;
            $resortAdmin->last_name = $request->last_name;
            $resortAdmin->email = $request->email;
            $resortAdmin->personal_phone = $request->personal_phone;
            $resortAdmin->address_line_1 = $request->address_line_1;
            $resortAdmin->address_line_2 = $request->address_line_2;
            $resortAdmin->city = $request->city;
            $resortAdmin->state = $request->state;
            $resortAdmin->country = $request->country;
            $resortAdmin->zip = $request->zip;
            if ($request->file('profile_picture'))
            {

                if($resortAdmin->is_master_admin == 1)
                {
                    $response['success'] = false;
                    $response['html']= '' ;
                    $response['msg'] = __('Sorry Super Admin can not update profile picture');
                    return response()->json($response);
                }
                    $emp = Employee::where('Admin_Parent_id', $resortAdmin->id)->first();
            
                    $fileName = $request->profile_picture->getClientOriginalName();
                    $main_folder = $resortAdmin->resort->resort_id;
                    $s3 = Storage::disk('s3');
                    $newFileName = $request->profile_picture->getClientOriginalName();
                    $mimeType = $request->profile_picture->getClientMimeType();
                    $basePath = $main_folder . '/public/categorized/' .$emp->Emp_id.'/Profile';
                    $path     = Common::UploadProfileAwsPic($basePath,$request->profile_picture);
        
                    if($path['status'] == false)
                    {
                        return response()->json(['success' => false, 'msg' => $path['msg']]);
                    }
                    $resortAdmin->profile_picture =$path['path'];
            }
            if ($request->file('signature_img'))
            {
                  $emp = Employee::where('Admin_Parent_id', $resortAdmin->id)->first();
                $main_folder = $resortAdmin->resort->resort_id;
                $s3          = Storage::disk('s3');
                $newFileName = $request->signature_img->getClientOriginalName();
                $mimeType    = $request->signature_img->getClientMimeType();
                $basePath    = $main_folder . '/public/categorized/' .$emp->Emp_id.'/Signature';
                $path        = Common::UploadProfileAwsPic($basePath,$request->signature_img);
                if($path['status'] == false)
                {
                    return response()->json(['success' => false]);
                }
                $resortAdmin->signature_img =$path['path'];
            }

            $saveResortAdmin = $resortAdmin->save();
            DB::commit();
            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Profile Updated successfully');
            return response()->json($response);

        DB::beginTransaction();
        try
        { }
        catch (\Exception $e)
        {
            DB::rollBack();
            $response['success'] = false;
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
            return response()->json($response);
        }

    }
    public function checkRankWiseRoute($admin)
    {
        $redirectRoute = null;
        
        // Initialize default route
        $defaultRoute = 'resort.master.hod_dashboard';
        
        // Check if admin is master admin
        switch (true) {
            case ($admin->is_master_admin == 1):
            $redirectRoute = 'resort.master.hr_dashboard';
            break;
            
            case ($admin->is_master_admin == 0):
            $position_name = $admin->GetEmployee->position->position_title ?? null;
            $resort_access = Resort::where('id', $admin->resort_id)->first('Position_access');
            
            if (!$resort_access) {
                $redirectRoute = $defaultRoute;
                break;
            }
            
            $Access_position = Position::where('status', 'Active')
                ->where('id', $resort_access->Position_access)
                ->first();
                
            if ($Access_position && $Access_position->position_title == $position_name) {
                $redirectRoute = 'resort.master.hr_dashboard';
            } else {
                $redirectRoute = $defaultRoute;
            }
            break;
            
            default:
            $redirectRoute = $defaultRoute;
            break;
        }
        
        // Check position access if not already determined
        if (!$redirectRoute) {
            $Access_position = Position::where('status', 'Active')
            ->where('id', $admin->Position_access)
            ->first();
            
            switch (true) {
            case (!$Access_position):
                $redirectRoute = $defaultRoute;
                break;
                
            case (in_array($Access_position->position_title, [
                'Director Of Human Resources', 
                'Human Resources Manager',
                'General Manager',
                'Director Of Finance'
            ])):
                $redirectRoute = 'resort.master.hr_dashboard';
                break;
                
            default:
                $redirectRoute = $defaultRoute;
                break;
            }
        }
        
        
        // switch($Rank)

        // {
        //     case 'HOD':
        //         $redirectRoute = 'resort.master.hod_dashboard';
        //         break;

        //     case 'GM':
        //         $redirectRoute = 'resort.master.gm_dashboard';
        //         break;
           
        //     case 'HR':
        //         $redirectRoute = 'resort.master.hr_dashboard';
        //         break;
            
        //     case 'MGR':
        //         $redirectRoute = 'resort.master.hod_dashboard';
        //         break;

        //     case 'EXCOM':
        //         $redirectRoute = 'resort.master.hod_dashboard';
        //         break;
        //     case 'EDHOD':    // Engineering Department HOD Only
        //         $redirectRoute = 'resort.accommodation.hoddashboard';
        //         break;
        //     default:
        //         break;
        // }

        return  $redirectRoute;


    }

    public function changePassword(Request $request)
    {
        $user = auth()->guard('resort-admin')->user(); // Adjust guard if needed

        $validator = \Validator::make($request->all(), [
            'old_password' => ['required'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'different:old_password', // ✅ Ensure new password ≠ old password
                'regex:/[a-z]/',           // at least one lowercase
                'regex:/[A-Z]/',           // at least one uppercase
                'regex:/[0-9]/',           // at least one number
                'regex:/[@$!%*#?&]/',      // at least one special character
                'not_in:password,password123,123456,admin123' // disallowed common passwords
            ],
            'confirmpassword' => ['required', 'same:password']
        ], [
            'password.different' => 'New password must be different from old password.',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            'password.not_in' => 'Please choose a stronger password, not a common one.',
            'confirmpassword.same' => 'Confirm password must match new password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if old password matches
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'msg' => 'Old password is incorrect.'
            ]);
        }

        // ✅ Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'msg' => 'Password changed successfully.'
        ]);
    }

    public function AccessDeined()
    {
        return view('notaccess');
    }
}
