<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\Admin;
use App\Models\AdminPasswordReset;
use App\Models\EmailHistoryLog;
use Illuminate\Support\Facades\Session;

class ForgotPasswordController extends Controller
{
  use SendsPasswordResetEmails;

  public function __construct()
  {
  }

  public function checkEmailExists( Request $request )
  {
    try {
      $admin = Admin::withTrashed()->where( 'email', $request->email )->first();

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
    return view('admin.auth.request-password');
  }

  public function requestPasswordSubmit(Request $request)
  {
    $this->validate($request, [
      'email' => 'required'
    ]);

    try {
        $this->sendResetLinkEmail($request);
        $admin = Admin::where('email', '=', $request->email)->first();
        $response['success'] = true;
        $response['msg'] = __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']);
        $response['redirect_url'] = route('admin.password.request');
        return response()->json($response);
        // return redirect()->route('admin.password.request')->with('success', 'Password reset link sent!');
    } catch (\Exception $e) {
        $response['success'] = false;
        $response['msg'] = $e->getMessage();
        $response['redirect_url'] = route('admin.password.request');
        return response()->json($response);
        // return redirect()->route('admin.password.request')->with('fail', 'Server error occurred.');
    }

  }

  public function broker()
  {
    return Password::broker('admins');
  }

  public function resetPassword($token, Request $request)
  {
    try {
      $ifExist = AdminPasswordReset::where('email','=', $request->email)->first();

      if(!$ifExist) {
        return redirect(route('admin.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
      } else {
        if(!Hash::check($token, $ifExist->token)) {
          return redirect(route('admin.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
        }
      }
      $data['token'] = $token;
      $data['email'] = $request->email;
      return view('admin.auth.reset-password',$data);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function resetPasswordSubmit(Request $request)
  {
    try {
      $this->validate($request, [
        'token' => 'required',
        'email' => 'required|email',
        'password'  => 'required|min:6',
        'password_confirmation'  => 'required|min:6|same:password'
      ]);

      $ifExist = AdminPasswordReset::where('email','=', $request->email)->first();

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

      $adminId = Admin::where('email','=',$request->email)->first()->id;
      $adminUser = Admin::find($adminId);
      $adminUser->password = Hash::make($request->password);
      $adminUser->save();

      $adminUser->sendPasswordResetSuccessNotification($adminUser,$request->password);

      $response['success'] = true;
      $response['msg'] = __('messages.passwordResetSuccess');
      $response['redirect_url'] = route('admin.loginindex');
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }
}

