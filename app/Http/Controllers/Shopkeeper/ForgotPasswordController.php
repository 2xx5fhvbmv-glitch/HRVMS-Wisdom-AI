<?php

namespace App\Http\Controllers\Shopkeeper;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\Shopkeeper;
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
    }

    public function requestPassword()
    {
      return view('shopkeeper.auth.request-password');
    }

    public function requestPasswordSubmit(Request $request)
    {
      $this->validate($request, [
        'email' => 'required|email'
      ]);

      try {
          $this->sendResetLinkEmail($request);
      } catch (\Exception $e) {
          \Log::error('Shopkeeper ForgotPasswordController::requestPasswordSubmit failed: ' . $e->getMessage());
      }

      // Always the same response regardless of whether the account exists —
      // Laravel's own broker already skips sending when it shouldn't.
      $response['success'] = true;
      $response['msg'] = __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']);
      $response['redirect_url'] = route('shopkeeper.password.request');
      return response()->json($response);
    }

    public function broker()
    {
      return Password::broker('shopkeeper');
    }

    public function resetPassword($token, Request $request)
    {
        try {
            $user = Shopkeeper::where('email', $request->email)->first();

            // tokenExists() is Laravel's own check (age via config's
            // 'expire' minutes, plus hash match) — replaces the old
            // hand-rolled Hash::check() with no expiry at all.
            if (!$user || !$this->broker()->tokenExists($user, $token)) {
              return redirect(route('shopkeeper.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
            }

            return view('shopkeeper.auth.reset-password', ['token' => $token, 'email' => $request->email]);
          } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
            return redirect(route('shopkeeper.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
          }
    }

    public function resetPasswordSubmit(Request $request)
    {
        $this->validate($request, [
          'token' => 'required',
          'email' => 'required|email',
          'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->uncompromised()],
        ]);

        // Password::broker()->reset() validates token age + hash AND
        // deletes the token row on success, so it can't be replayed — the
        // old code hand-rolled only the Hash::check() half and never
        // deleted the row, and its success path returned a redirect
        // carrying an "invalidRequest" error message even on success.
        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // No plaintext password in this email anymore — confirmation only.
                $user->sendPasswordResetSuccessNotification($user, null);
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'msg' => __('messages.passwordResetSuccess'),
                'redirect_url' => route('shopkeeper.loginindex'),
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => __('messages.invalidRequest'),
        ]);
    }
}
