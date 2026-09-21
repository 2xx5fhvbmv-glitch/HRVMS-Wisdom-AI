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
    /**
     * A fixed, valid bcrypt hash of a random string — never a real
     * password. Used as the Hash::check() target when no account exists,
     * so an unknown-email login costs the same CPU time as a real-account
     * wrong-password check (timing-based enumeration fix).
     */
    private const INVALID_CREDENTIALS_HASH = '$2y$10$wJ8k1Qm5X0aG5s3fV1jvbeYyq3H2W1rY7Z9nQxT4uK6oL2mN8pS1e';

    public function logout()
    {
      Auth::guard('shopkeeper')->logout();
      return redirect()->route('shopkeeper.loginindex');
    }

    public function login(Request $request)
    {
        try {
            $shopkeeper = Shopkeeper::where('email', $request->email)->first();

            // Enumeration fix: unknown email and wrong password now return
            // the identical message, and an unknown email runs a dummy
            // hash so response time doesn't distinguish the two cases.
            // Hash::check() must run unconditionally — `!$x || !Hash::check()`
            // short-circuits on null $x, so the dummy hash above was never
            // actually reached and timing kept leaking which branch ran.
            $passwordValid = Hash::check(is_string($request->password) ? $request->password : '', $shopkeeper->password ?? self::INVALID_CREDENTIALS_HASH);
            if (!$shopkeeper || !$passwordValid) {
                Common::logLoginAttempt('shopkeeper', $request->email, false, $request);
                return response()->json([
                    'success' => false,
                    'msg' => 'Invalid email or password.'
                ]);
            }

            // NOTE: the `shopkeepers` table has no status/active column at
            // all (confirmed against the real schema) — there is currently
            // no way to deactivate a shopkeeper account. The commented-out
            // check the security review found was already dead code
            // referencing a column that doesn't exist; reinstating it
            // would be a silent no-op, not a real fix. Adding a genuine
            // inactive-account gate here needs a schema migration + admin
            // UI to toggle it — flagged as separate follow-up work, not
            // done in this pass.

            Auth::guard('shopkeeper')->login( $shopkeeper, isset( $request->remember ) );
            Common::logLoginAttempt('shopkeeper', $request->email, true, $request);

            // Security hardening (S4): a freshly-created shopkeeper account
            // is flagged must_change_password — the credential email sent
            // that password in plaintext, so force a change before the
            // dashboard.
            if ($shopkeeper->must_change_password) {
                return response()->json([
                    'success' => true,
                    'msg' => 'Please set a new password before continuing.',
                    'must_change_password' => true,
                    'redirect_url' => route('shopkeeper.profile')
                ]);
            }

            $response['success'] = true;
            $response['msg'] = 'Logged in Successfully.';
            $response['redirect_url'] = route('shopkeeper.dashboard');
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
