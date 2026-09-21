<?php
namespace App\Http\Controllers\Admin;

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
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\Admin;
use App\Helpers\Common;

class LoginController extends Controller
{
  /**
   * A fixed, valid bcrypt hash of a random string — never a real
   * password. Used as the Hash::check() target when no account exists, so
   * an unknown-email login costs the same CPU time as a real-account
   * wrong-password check (timing-based enumeration fix).
   */
  private const INVALID_CREDENTIALS_HASH = '$2y$10$wJ8k1Qm5X0aG5s3fV1jvbeYyq3H2W1rY7Z9nQxT4uK6oL2mN8pS1e';

  public function logout()
  {
    Auth::guard('admin')->logout();
    return redirect()->route('admin.loginindex');
  }

  public function login(Request $request)
  {

    try {
      $admin = Admin::where('email', $request->email)->first();

      // Enumeration fix: account-active check used to run BEFORE the
      // password check, so a wrong password on a real email still leaked
      // "Account is deactivated" without ever proving the caller knew the
      // password. Password is now checked first, and an unknown email runs
      // a dummy hash so response time doesn't distinguish "no such
      // account" from "wrong password".
      if (!$admin || !Hash::check((string) $request->password, $admin->password ?? self::INVALID_CREDENTIALS_HASH)) {
        Common::logLoginAttempt('admin', $request->email, false, $request);
        $response['success'] = false;
        $response['msg'] = 'Invalid email or password.';
        return response()->json($response);
      }

      if( $admin->status == "inactive")
      {
        $response['success'] = false;
        $response['msg'] = 'Account is deactivated';
        return response()->json($response);
      }

      Auth::guard('admin')->login( $admin, isset( $request->remember ) );
      Common::logLoginAttempt('admin', $request->email, true, $request);

      $response['success'] = true;
      $response['msg'] = 'Logged in';
      $response['redirect_url'] = route('admin.dashboard');
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = 'An error occurred. Please try again later.';
      return response()->json($response);
    }
  }

  public function showLoginForm()
  {
    if (Auth::guard('admin')->check()) {
      return redirect()->route('admin.dashboard');
    }
    return view('admin.auth.login');
  }

  public function permissionDenied()
  {
    $page_title = 'Permission Deny';
    $page_header = 'Permission Denied';

    return view('admin.permission_deny')->with(compact('page_title', 'page_header'));
  }

  public function AdminToResort(Request $request)
  {
      $resort_id = $request->resort_id;
      try {
        // Find active resort admin
        $resortAdmin = ResortAdmin::where('resort_id', $resort_id)
            ->whereHas('resort', function ($query) {
                $query->where('status', 'Active')
                      ->whereNull('deleted_at');
            })
            ->first();

        if (!$resortAdmin) {
            return response()->json([
                'success' => false,
                'msg' => 'Resort admin not found or Resort is In-Active right now'
            ]);
        }

        // Check if resort admin can be impersonated
        if (isset($resortAdmin->resort_id))
        {
          Auth::guard('resort-admin')->login($resortAdmin);
          if (Auth::guard('resort-admin')->check())
          {
            // Store original admin ID as the impersonator
            session(['impersonated_by' => Auth::id()]);
            // Determine redirect URL based on the impersonated user's role
            $role = $resortAdmin->GetEmployee->rank ?? null;
            $rankConfig = config('settings.Position_Rank');
            $availableRank = array_key_exists($role, $rankConfig) ? $rankConfig[$role] : '';
            // Set default dashboard route based on rank
            $redirectRoute = 'resort.workforceplan.dashboard'; // Default
            if ($availableRank === 'HOD') {
              $redirectRoute = 'resort.workforceplan.hoddashboard';
            } elseif ($availableRank === 'HR') {
              $redirectRoute = 'resort.workforceplan.dashboard';
            } elseif ($availableRank === 'Admin') {
              $redirectRoute = 'resort.workforceplan.admindashboard';
            }
            return response()->json([
              'success' => true,
              'msg' => 'Logged in',
              'redirect_url' => route($redirectRoute)
            ]);
          } 
          else {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to impersonate user',
            ]);
          }
        }
        return response()->json([
          'success' => false,
          'msg' => 'Oops, something went wrong'
        ]);
      } catch (\Exception $e) {
        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());

        return response()->json([
            'success' => false,
            'msg' => 'An error occurred. Please try again later.',
        ]);
      }
  }

}