<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Helpers\Common;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Validator;
use Illuminate\Support\Facades\Password;


class Logincontroller extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * A fixed, valid bcrypt hash of a random string — never a real
     * password. Used as the Hash::check() target when no real user record
     * exists, so an unknown-identifier login costs the same CPU time as a
     * real-account wrong-password check (timing-based enumeration fix).
     */
    private const INVALID_CREDENTIALS_HASH = '$2y$10$wJ8k1Qm5X0aG5s3fV1jvbeYyq3H2W1rY7Z9nQxT4uK6oL2mN8pS1e';

    /** Security hardening (S10): max concurrent active Passport tokens per account. */
    private const MAX_ACTIVE_TOKENS = 5;

    public function apiLogin(Request $request)
    {
        
        $validator  = Validator::make($request->all(), [
            'emp_id'                                => 'required',
            'password'                              => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {

            // Find the Employee by Emp_id
            $employee                               =   Employee::where('Emp_id', $request->emp_id)->first();
            $resortAdmin                            =   $employee ? ResortAdmin::where('id', $employee->Admin_Parent_id)->first() : null;

            // S8 — account lockout: same shared resort_admins state the web
            // portal login checks (Common::isAccountLocked()), checked
            // before the password hash so a locked account can't be
            // brute-forced during its lockout window.
            if ($resortAdmin && Common::isAccountLocked($resortAdmin)) {
                Common::logLoginAttempt('mobile', $request->emp_id, false, $request);
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Too many failed login attempts. Please try again in a few minutes.'
                ], 200);
            }

            // Enumeration fix: account-state checks (inactive employee,
            // non-permanent employment type, inactive resort admin) used to
            // run BEFORE the password check, so a wrong password on a real
            // Emp_id still leaked "Account is deactivated" etc. without ever
            // proving the caller knew the password. Password is now checked
            // first, and unknown-emp_id runs a dummy hash so the response
            // time doesn't distinguish "no such employee" from "wrong
            // password" (self::INVALID_CREDENTIALS_HASH is a fixed bcrypt
            // hash of a random string, never a real password).
            // Hash::check() must run unconditionally — `!$x || !Hash::check()`
            // short-circuits on null $x, so the dummy hash above was never
            // actually reached and timing kept leaking which branch ran.
            $passwordValid = Hash::check(is_string($request->password) ? $request->password : '', $resortAdmin->password ?? self::INVALID_CREDENTIALS_HASH);
            if (!$resortAdmin || !$passwordValid) {
                Common::logLoginAttempt('mobile', $request->emp_id, false, $request);
                if ($resortAdmin) {
                    Common::registerFailedLogin($resortAdmin);
                }
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Invalid Employee ID or password. Please try again'
                ],200);
            }

            // Allow-list, not a block-list (S4-02): Terminated/Resigned/
            // Suspended must also be blocked, and any future status is
            // blocked by default until added to Employee::LOGIN_ALLOWED_STATUSES.
            if (!in_array($employee->status, Employee::LOGIN_ALLOWED_STATUSES, true)) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Account is deactivated'
                ],200);
            }

            // Casual/Intern staff get no mobile app access at all — their
            // reporting manager marks attendance/leave on their behalf
            // (Common::sendResortemployee() already skips issuing them
            // credentials, but this is checked again here too in case a
            // password was set some other way — e.g. a manual reset).
            if (Common::manningCategory($employee->employment_type) !== 'Permanent') {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Mobile app access is not available for this account type'
                ],200);
            }

            // Case-sensitive `== "Inactive"` never matched — the super-admin
            // form saves resort_admins.status as lowercase 'active'/'inactive'
            // (S4-02). Compare case-insensitively and allow only 'active'.
            if (strtolower($resortAdmin->status) !== 'active') {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Account is deactivated'
                ],200);
            }

            // Mobile login had no resort-status check at all (S4-02) — a
            // client resort switched off (contract ended) could still use
            // the app. Mirror the web login's check.
            $resort = \App\Models\Resort::find($employee->resort_id);
            if (!$resort || strtolower($resort->status) !== 'active') {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Account is deactivated'
                ],200);
            }
            // Check if the user already has an active token
            // $existingToken                          =   $resortAdmin->tokens()->where('revoked', false)->first();

            // if ($existingToken) {
            //     return response()->json([
            //         'success'                       =>  false,
            //         'message'                       =>  'User is already logged in',
            //     ], 200);
            // }

            // Security hardening (S10): an unbounded number of live tokens
            // per account means a stolen/never-logged-out token from years
            // ago is still valid forever. Cap concurrent sessions — revoke
            // the oldest active tokens beyond the limit before issuing a
            // new one.
            $activeTokens = $resortAdmin->tokens()->where('revoked', false)->orderBy('created_at', 'desc')->get();
            if ($activeTokens->count() >= self::MAX_ACTIVE_TOKENS) {
                foreach ($activeTokens->slice(self::MAX_ACTIVE_TOKENS - 1) as $staleToken) {
                    $staleToken->revoke();
                }
            }

            // Generate a new token
            $tokenResult                            =   $resortAdmin->createToken('ResortAdminToken');
            $token                                  =   $tokenResult->accessToken;
            Common::logLoginAttempt('mobile', $request->emp_id, true, $request);
            Common::registerSuccessfulLogin($resortAdmin);

            // Was never captured at login at all — the app had to remember
            // to call the separate add-device-token endpoint afterward, and
            // if it didn't (or that call failed), push notifications had
            // nothing to send to. Optional here since some callers may still
            // follow up with add-device-token separately; appends rather
            // than overwrites so a second device logging in doesn't kill
            // push to the first.
            if ($request->filled('device_token')) {
                Common::addDeviceToken($employee, $request->device_token);
            }

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'User Login Successfully',
                'token'                             =>  $token,
                'redirect_url'                      =>  route('resort.workforceplan.dashboard'),
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error. Please try again later.'], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            // Get the currently authenticated user
            $resort_admin                           = Auth::guard('api')->user();

           
            // Check if the user is authenticated
            if (!$resort_admin) {
                return response()->json(['success'  => false, 'message' => 'No authenticated user'], 401);
            }

            // Get the token from the request
            $token = $request->user()->token();
            if (!$token) {
                return response()->json(['success'  => false, 'message' => 'No valid token found'], 400);
            }

            // Revoke the token
            $token->revoke(); // Passport-specific method to revoke the token


             $employee                               =   Employee::where('Admin_Parent_id', $resort_admin->id)->first();
             if($employee) {
                        // Used to null the whole column — with multiple
                        // devices now supported, that would also kill push
                        // to every OTHER device this employee is still
                        // logged into. Only remove the token for the
                        // specific device logging out.
                        if ($request->filled('device_token')) {
                            Common::removeDeviceToken($employee, $request->device_token);
                        }
             }
            return response()->json(['success'      => true, 'message' => 'User Logout Successfully'], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success'      => false, 'message' => 'Server error. Please try again later.'], 500);
        }
    }

    public function apiForgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            // Always the same response regardless of $status — Laravel's own
            // Password::RESET_LINK_SENT vs 'passwords.user' translation
            // strings otherwise confirm whether the email is registered.
            // The actual send-or-not decision still happens inside the
            // broker; the caller just never learns which branch it took.
            Password::broker('resort-admin')->sendResetLink($request->only('email'));

            return response()->json([
                'success' => true,
                'msg'     => __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']),
            ]);
        } catch (\Exception $e) {
            \Log::error('apiForgotPassword failed: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'msg'     => __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']),
            ]);
        }
    }

    public function addDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token'                          =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            // A TemporaryClinicDoctor calls this same route right after
            // login (every account does), but this route sat inside the
            // outer auth:api-only group and this method only ever checked
            // Auth::guard('api') — so a doctor's own valid token got a 401
            // here immediately post-login, which the app surfaces as a
            // forced logout even though the login itself succeeded. A
            // doctor has no Employee row at all (see EnsureClinicManagerAccess),
            // so there's nowhere to persist a device token for one yet —
            // acknowledge and no-op rather than crash or 401, until doctor
            // push support is actually built.
            if (Auth::guard('temp-clinic-doctor')->check()) {
                return response()->json([
                    'success'                       =>  true,
                    'message'                       =>  'Device token registration not yet supported for this account type.',
                ], 200);
            }

            // Was looking the employee up by a client-supplied emp_id in the
            // request body instead of the authenticated user this Bearer
            // token actually belongs to (this route already sits behind
            // auth:api) — any logged-in user could register a device token
            // against ANY other employee's account just by passing a
            // different emp_id, and would then receive that employee's push
            // notifications. Identity now comes from the token, not the body.
            $resortAdmin                            =   Auth::guard('api')->user();
            $employee                               =   Employee::where('Admin_Parent_id', $resortAdmin->id)->first();

            if (!$employee) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Employee not found',
                ], 404);
            }

            // Was a raw overwrite — logging in on a second device silently
            // wiped the first device's token and killed push to it. Appends
            // instead (Common::addDeviceToken saves the employee itself).
            Common::addDeviceToken($employee, $request->device_token);
            $employee->latitude = $request->latitude ?? null; // Set latitude if provided, otherwise null
            $employee->longitude = $request->longitude ?? null; // Set longitude if provided, otherwise null
            $employee->save();

            return response()->json([
                'success'                       =>  true,
                'message'                       => 'Device token registered successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error. Please try again later.'], 500);
        }
    }

    /**
     * Deregisters one device's FCM token (e.g. on logout, or when the app
     * gets a new token from Firebase and wants the old one gone) without
     * requiring a full logout — apiLogout only removes a token as a
     * side-effect of revoking the session token entirely.
     */
    public function removeDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token'                          =>  'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            $resortAdmin                            =   Auth::guard('api')->user();
            $employee                               =   Employee::where('Admin_Parent_id', $resortAdmin->id)->first();

            if (!$employee) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Employee not found',
                ], 404);
            }

            Common::removeDeviceToken($employee, $request->device_token);

            return response()->json([
                'success'                       =>  true,
                'message'                       => 'Device token deregistered successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error. Please try again later.'], 500);
        }
    }
}
