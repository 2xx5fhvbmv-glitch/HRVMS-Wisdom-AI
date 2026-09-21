<?php

namespace App\Http\Controllers\Resorts;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\ResortAdmin;
class ResortforgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
    }

    public function requestPassword()
    {
      return view('resorts.auth.request-password');
    }

    public function requestPasswordSubmit(Request $request)
    {
      $this->validate($request, [
        'email' => 'required|email'
      ]);

      try {
        $this->sendResetLinkEmail($request);
      } catch (\Exception $e) {
        \Log::error('ResortforgotPasswordController::requestPasswordSubmit failed: ' . $e->getMessage());
      }

      // Always the same response regardless of whether the account exists,
      // is active, or belongs to an active resort — Laravel's own broker
      // already skips sending when it shouldn't; the caller never learns
      // which branch it took.
      return response()->json([
        'success' => true,
        'msg' => __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']),
        'redirect_url' => route('resort.password.request'),
      ]);
    }

    public function broker()
    {
      return Password::broker('resort-admin');
    }

    public function resetPassword($token, Request $request)
    {
        try {
            $user = ResortAdmin::where('email', $request->email)->first();

            // tokenExists() is Laravel's own check (age via config's
            // 'expire' => 60 minutes, plus hash match) — replaces the old
            // hand-rolled Hash::check() with no expiry at all.
            if (!$user || !$this->broker()->tokenExists($user, $token)) {
              return redirect(route('resort.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
            }

            return view('resorts.auth.reset-password', ['token' => $token, 'email' => $request->email]);
          } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
            return redirect(route('resort.password.request'))->withErrors(['error' => __('messages.invalidRequest')]);
          }
    }

    public function resetPasswordSubmit(Request $request)
    {
        $this->validate($request, [
          'token' => 'required',
          'email' => 'required|email',
          'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->uncompromised()],
        ]);

        // Password::broker()->reset() is Laravel's real implementation —
        // it validates token age + hash (the same tokenExists() check
        // above) AND deletes the token row on success, so it can't be
        // replayed. The old code hand-rolled only the Hash::check() half
        // and never deleted the row, so a used/expired token kept working.
        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // No plaintext password in this email anymore (S4) — just a
                // confirmation. Revoke the account's mobile API tokens so a
                // stolen token can't survive a password reset (S3).
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
                'redirect_url' => route('resort.loginindex'),
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => __('messages.invalidRequest'),
        ]);
    }
}
