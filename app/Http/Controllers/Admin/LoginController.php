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

class LoginController extends Controller
{
  public function logout()
  {
    Auth::guard('admin')->logout();
    return redirect()->route('admin.loginindex');
  }

  public function login(Request $request)
  {

    try {
      $admin = Admin::where('email', $request->email)->first();

      if (!$admin) {
        $response['success'] = false;
        $response['msg'] = 'There is no account with this email address';
        return response()->json($response);
      }

      if( $admin->status == "inactive")
      {
        $response['success'] = false;
        $response['msg'] = 'Account is deactivated';
        return response()->json($response);
      }

      if( Hash::check( $request->password, $admin->password ) ) {
        Auth::guard('admin')->login( $admin, isset( $request->remember ) );

        $response['success'] = true;
        $response['msg'] = 'Logged in';
        $response['redirect_url'] = route('admin.dashboard');
        return response()->json($response);
      }

      $response['success'] = false;
      $response['msg'] = 'Please enter a valid password';
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
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
            'msg' => $e->getMessage(),
        ]);
      }
  }

}