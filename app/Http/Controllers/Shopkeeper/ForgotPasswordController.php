<?php

namespace App\Http\Controllers\Shopkeeper;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\Shopkeeper;
use App\Models\ShopkeeperPasswordReset;
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
        $shopkeeper = Shopkeeper::withTrashed()->where( 'email', $request->email )->first();

        if( empty( $shopkeeper ) ) {
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
      return view('shopkeeper.auth.request-password');
    }

    public function requestPasswordSubmit(Request $request)
    {


      $this->validate($request, [
        'email' => 'required'
      ]);

      try {
          $this->sendResetLinkEmail($request);
          $admin = Shopkeeper::where('email', '=', $request->email)->first();

          $response['success'] = true;
          $response['msg'] = __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']);
          $response['redirect_url'] = route('shopkeeper.password.request');
          return response()->json($response);
      } catch (\Exception $e) {
          $response['success'] = false;
          $response['msg'] = $e->getMessage();
          $response['redirect_url'] = route('shopkeeper.password.request');
          return response()->json($response);
      }

    }

    public function broker()
    {
      return Password::broker('shopkeeper');
    }

    public function resetPassword($token, Request $request)
    {


        try {
            $ifExist = ShopkeeperPasswordReset::where('email','=', $request->email)->first();

            if(!$ifExist) {
              return redirect(route('shopkeeper.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
            } else {
              if(!Hash::check($token, $ifExist->token)) {
                return redirect(route('shopkeeper.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
              }

            }
            $data['token'] = $token;
            $data['email'] = $request->email;

            return view('shopkeeper.auth.reset-password',$data);
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

        $ifExist = ShopkeeperPasswordReset::where('email','=', $request->email)->first();

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

        $shopkeeperId = Shopkeeper::where('email','=',$request->email)->first()->id;

        $shopkeeper = Shopkeeper::find($shopkeeperId);
        // dd($shopkeeper->password,$request->password);
        $shopkeeper->password = Hash::make($request->password);
        $shopkeeper->save();

        $shopkeeper->sendPasswordResetSuccessNotification($shopkeeper,$request->password);

        return redirect(route('shopkeeper.loginindex'))->withErrors(['error' => __('messages.invalidRequest')]);

    }
}
