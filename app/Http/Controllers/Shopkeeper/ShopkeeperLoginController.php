<?php
namespace App\Http\Controllers\Shopkeeper;
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
use App\Models\Shopkeeper;

class ShopkeeperLoginController extends Controller
{
    public function logout()
    {
      Auth::guard('shopkeeper')->logout();
      return redirect()->route('shopkeeper.loginindex');
    }

    public function login(Request $request)
    {
        try {
            $shopkeeper = Shopkeeper::where('email', $request->email)->first();

            if (!$shopkeeper) {
                return response()->json([
                    'success' => false,
                    'msg' => 'There is no account with this email address'
                ]);
            }

            // if ($shopkeeper->status == "inactive") {
            //     return response()->json([
            //         'success' => false,
            //         'msg' => 'Account is deactivated'
            //     ]);
            // }
            
            if( Hash::check( $request->password, $shopkeeper->password ) ) {
                Auth::guard('shopkeeper')->login( $shopkeeper, isset( $request->remember ) );
        
                $response['success'] = true;
                $response['msg'] = 'Logged in Successfully.';
                $response['redirect_url'] = route('shopkeeper.dashboard');
                return response()->json($response);
              }
        
              $response['success'] = false;
              $response['msg'] = 'Please enter a valid password';
              return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'An error occurred. Please try again later.'
            ]);
        }
    }

    public function showLoginForm()
    {
      if (Auth::guard('shopkeeper')->check())
      {
        return redirect()->route('shopkeeper.dashboard');
      }
       return view('shopkeeper.auth.login');
    }

    public function permissionDenied()
    {
      $page_title = 'Permission Deny';
      $page_header = 'Permission Denied';

      return view('admin.permission_deny')->with(compact('page_title', 'page_header'));
    }

    public function ResortProfile()
    {
        $profile =  Auth::guard('resort-admin')->user();
        return view('resorts.workforce_planning.profile',compact('profile'));
    }

    public function UpdateResortProfile(Request $request)
    {
        // dd($request);
        $path_profile_image = config('settings.ResortProfile_folder');
        $path_signature_image = config('settings.Resortsignature_folder');
        // DB::beginTransaction();
        // try
        // {
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
            $resortAdmin->zip = $request->pincode;
            if(isset($request->password))
            {
                $resortAdmin->password = Hash::make($request->password);
            }

            if ($request->file('profile_picture'))
            {
                $fileName = $request->profile_picture->getClientOriginalName();
                Common::uploadFile($request->profile_picture, $fileName, $path_profile_image);
                if (File::exists(public_path($path_profile_image.'/'.$resortAdmin->profile_picture)))
                {
                    File::delete(public_path($path_profile_image.'/'.$resortAdmin->profile_picture));
                }
                $resortAdmin->profile_picture = $fileName;
            }
            if ($request->file('signature_img'))
            {
                $fileName1 = $request->signature_img->getClientOriginalName();
                Common::uploadFile($request->signature_img, $fileName1, $path_signature_image);
                if (File::exists(public_path($path_signature_image.'/'.$resortAdmin->signature_img)))
                {
                    File::delete(public_path($path_signature_image.'/'.$resortAdmin->signature_img));
                }
                $resortAdmin->signature_img = $fileName1;
            }

            $saveResortAdmin = $resortAdmin->save();
            DB::commit();
            $response['success'] = true;
            $response['html']= '' ;
            $response['msg'] = __('Profile Updated successfully');
            return response()->json($response);

        // }
        // catch (\Exception $e)
        // {
        // DB::rollBack();
        //     $response['success'] = false;
        //     $response['msg'] = __('Somthing Wrong ', ['name' => 'Wrong']);
        //     return response()->json($response);
        // }

    }
    public function checkRankWiseRoute($Rank)
    {
        $redirectRoute = null;
        switch($Rank)
        {
            case 'HOD':
                $redirectRoute = 'resort.workforceplan.hoddashboard';
                break;

            case 'MGR':
            case 'GM':
            case 'Finance':
            case 'HR':
                $redirectRoute = 'resort.workforceplan.dashboard';
                break;

            default:

                break;
        }
        return  $redirectRoute;


    }
}
