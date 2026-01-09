<?php

namespace App\Http\Controllers\Resorts;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\ResortAdmin;
use App\Models\ResortAdminPasswordReset;
use App\Models\EmailHistoryLog;
use Illuminate\Support\Facades\Session;
class ResortforgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
    }

    public function checkEmailExists( Request $request )
    {
      try {
        $admin = ResortAdmin::withTrashed()->where( 'email', $request->email )->first();

        if( empty( $admin ) ) {
          echo "false";
        } else {
          echo "true";
        }
      } catch( \Exception $e ) {
        \Log::emergency( "File: ".$e->getFile() );
        \Log::emergency( "Line: ".$e->getLine() );
        \Log::emergency( "Message: ".$e->getMessage() );
      }
    }

    public function requestPassword()
    {
      return view('resorts.auth.request-password');
    }

    public function requestPasswordSubmit(Request $request)
    {

      // dd($request->all());
      $this->validate($request, [
        'email' => 'required'
      ]);
      
        try 
        {  
     
          $this->sendResetLinkEmail($request);
          $admin = ResortAdmin::with('GetEmployee','resort')->where('email', '=', $request->email)->where("status","Active")->where("deleted_at",null)->first();

          if ($admin)
          {
            if ($admin->resort && $admin->resort->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'msg' => 'Resort is inactive. Please contact your administrator.',
                ]);
            }
            if(!$admin->GetEmployee){
                // dd($resort_admin->type);
                if($admin->type == "super" && $admin->is_master_admin == 1)
                {
                    // dd($resort_admin->status);
                    if($admin->status == "inactive"){
                        return response()->json([
                            'success' => false,
                            'msg' => 'Your account is inactive. Please contact your administrator.'
                        ]);
                    }
                }
            }
            if ($admin && isset($admin->GetEmployee) && $admin->GetEmployee->status == "Active") {

                $response['success'] = true;
                $response['msg'] = __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']);
                $response['redirect_url'] = route('resort.password.request');
            } else {
                $response['success'] = false;
                $response['msg'] = 'Your account is inactive. Please contact your administrator.';
                $response['redirect_url'] = ''; // no redirect
            }
          } else {
            $response['success'] = false;
            $response['msg'] = 'User not found';
            $response['redirect_url'] = ''; // no redirect
          }
          return response()->json($response);
        } 
        catch (\Exception $e) 
        {
            $response['success'] = false;
            $response['msg'] = $e->getMessage();
            $response['redirect_url'] = route('resort.password.request');
            return response()->json($response);
        }

    }

    public function broker()
    {
      return Password::broker('resort-admin');
    }

    public function resetPassword($token, Request $request)
    {


        try {
            $ifExist = ResortAdminPasswordReset::where('email','=', $request->email)->first();

            if(!$ifExist) {
              return redirect(route('resort.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
            } else {
              if(!Hash::check($token, $ifExist->token)) {
                return redirect(route('resort.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
              }

            }
            $data['token'] = $token;
            $data['email'] = $request->email;

            return view('resorts.auth.reset-password',$data);
          } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
          }
    }

    public function resetPasswordSubmit(Request $request)
    {
        $this->validate($request, [
          'token' => 'required',
          'email' => 'required|email',
          'password'  => 'required|min:6',
          'password_confirmation'  => 'required|min:6|same:password'
        ]);

        $ifExist = ResortAdminPasswordReset::where('email','=', $request->email)->first();

        if(!$ifExist) {
          $response['success'] = false;
          $response['msg'] = __('messages.invalidRequest');
          return response()->json($response);
        } else {
          if(!Hash::check($request->token, $ifExist->token)) {
            $response['success'] = false;
            $response['msg'] = __('messages.invalidRequest');
            return response()->json($response);
          }



        }

        $adminId = ResortAdmin::where('email','=',$request->email)->first()->id;

        $adminUser = ResortAdmin::find($adminId);
        $adminUser->password = Hash::make($request->password);
        $adminUser->save();

        $adminUser->sendPasswordResetSuccessNotification($adminUser,$request->password);

        return redirect(route('resort.loginindex'))->withErrors(['error' => __('messages.invalidRequest')]);

        // return redirect()->route('resort.loginindex')->with('success', 'Password reset successfully');
        // $response['success'] = true;
        // $response['msg'] = __('messages.passwordResetSuccess');
        // $response['redirect_url'] = route('resort.loginindex');
        // return response()->json($response);
    //   } catch( \Exception $e ) {
    //     \Log::emergency( "File: ".$e->getFile() );
    //     \Log::emergency( "Line: ".$e->getLine() );
    //     \Log::emergency( "Message: ".$e->getMessage() );
    //   }
    }
}
