<?php
namespace App\Http\Controllers\Admin;

use App\Helpers\Common;
use App\Helpers\Totp;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use F9WebLtd\QrCode\Facades\QrCode;

/**
 * Super-admin MFA (advisory 2026-09-29, decisions A1 + D3).
 * Enrolment is mandatory (AdminSecurity middleware) but happens inside an
 * already-authenticated session, so enforcing it can't lock anyone out.
 * Recovery: single-use recovery codes, or `php artisan admin:2fa-reset`.
 */
class TwoFactorController extends Controller
{
    private const PENDING_TTL = 300;     // seconds between password step and code step
    public const REAUTH_TTL = 600;       // seconds a step-up confirmation stays valid

    /** Login step 2 — password already checked by LoginController::login(). */
    public function challenge(Request $request)
    {
        if (!$this->pendingAdmin($request)) {
            return redirect()->route('admin.loginindex');
        }
        return view('admin.auth.two_factor', ['mode' => 'challenge']);
    }

    public function verify(Request $request)
    {
        $admin = $this->pendingAdmin($request);
        if (!$admin) {
            return redirect()->route('admin.loginindex')->withErrors(['Your sign-in expired. Please start again.']);
        }

        if (Common::isAccountLocked($admin) || !$this->checkSecondFactor($admin, (string) $request->input('code'))) {
            Common::logLoginAttempt('admin', $admin->email, false, $request);
            Common::registerFailedLogin($admin);
            if (Common::isAccountLocked($admin->fresh())) {
                $request->session()->forget('admin_2fa_pending');
                return redirect()->route('admin.loginindex')->withErrors(['Too many failed attempts.']);
            }
            return $this->fail('challenge', 'Invalid authentication code.');
        }

        $request->session()->forget('admin_2fa_pending');
        Common::registerSuccessfulLogin($admin);
        LoginController::completeLogin($request, $admin);

        return redirect()->route($admin->must_change_password ? 'admin.profile' : 'admin.dashboard');
    }

    /** Enrolment page — secret lives in the session until a code proves the app has it. */
    public function setup(Request $request, ?string $error = null)
    {
        $admin = Auth::guard('admin')->user();
        if ($admin->two_factor_confirmed_at) {
            return response()->view('admin.auth.two_factor', ['mode' => 'enrolled', 'admin' => $admin]);
        }

        $secret = $request->session()->get('admin_2fa_setup_secret') ?: Totp::generateSecret();
        $request->session()->put('admin_2fa_setup_secret', $secret);

        return response()->view('admin.auth.two_factor', [
            'mode' => 'setup',
            'secret' => $secret,
            'qr' => QrCode::format('svg')->size(200)->generate(Totp::otpauthUri($secret, $admin->email)),
            'error' => $error,
        ], $error ? 422 : 200);
    }

    public function confirm(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $secret = $request->session()->get('admin_2fa_setup_secret');
        if ($admin->two_factor_confirmed_at || !$secret) {
            return redirect()->route('admin.2fa.setup');
        }
        if (!Totp::verify($secret, (string) $request->input('code'))) {
            return $this->setup($request, 'That code didn\'t match. Check the time on your phone and try again.');
        }

        $codes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5) . '-' . Str::random(5)))->all();
        $admin->two_factor_secret = $secret;
        $admin->two_factor_recovery_codes = array_map(fn ($c) => Hash::make($c), $codes);
        $admin->two_factor_confirmed_at = now();
        $admin->save();

        $request->session()->forget('admin_2fa_setup_secret');
        $request->session()->put('admin_reauth_at', time());
        Common::alertSuperAdmins($admin, 'Super-admin MFA enrolled', $admin->email . ' enrolled an authenticator app from IP ' . $request->ip() . '.');

        return view('admin.auth.two_factor', ['mode' => 'codes', 'codes' => $codes]);
    }

    /** Step-up confirmation (D3) before the most dangerous actions. */
    public function reauthForm()
    {
        return view('admin.auth.two_factor', ['mode' => 'reauth']);
    }

    public function reauth(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $passwordOk = Hash::check((string) $request->input('password'), $admin->password);
        if (!$passwordOk || !$this->checkSecondFactor($admin, (string) $request->input('code'))) {
            Common::logLoginAttempt('admin-reauth', $admin->email, false, $request);
            Common::registerFailedLogin($admin);
            return $this->fail('reauth', 'Password or authentication code is incorrect.');
        }
        $request->session()->put('admin_reauth_at', time());
        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Errors are rendered directly, not flashed: StartSession runs twice
     * (global + web group in Kernel), which ages flash data out before the
     * redirected page reads it.
     */
    private function fail(string $mode, string $error)
    {
        return response()->view('admin.auth.two_factor', ['mode' => $mode, 'error' => $error], 422);
    }

    private function pendingAdmin(Request $request): ?Admin
    {
        $pending = $request->session()->get('admin_2fa_pending');
        if (!$pending || time() - $pending['at'] > self::PENDING_TTL) {
            return null;
        }
        return Admin::where('id', $pending['id'])->where('status', 'active')->whereNotNull('two_factor_confirmed_at')->first();
    }

    /** TOTP (single-use within its window) or a single-use recovery code. */
    private function checkSecondFactor(Admin $admin, string $code): bool
    {
        $code = trim($code);
        if ($admin->two_factor_secret && Totp::verify($admin->two_factor_secret, $code)) {
            return Cache::add('admin_totp_used:' . $admin->id . ':' . $code, 1, 120);
        }

        $remaining = $admin->two_factor_recovery_codes ?? [];
        foreach ($remaining as $i => $hash) {
            if (Hash::check(Str::upper($code), $hash)) {
                unset($remaining[$i]);
                $admin->two_factor_recovery_codes = array_values($remaining);
                $admin->save();
                Common::alertSuperAdmins($admin, 'Super-admin recovery code used', $admin->email . ' used an MFA recovery code from IP ' . request()->ip() . ' (' . count($remaining) . ' left).');
                return true;
            }
        }
        return false;
    }
}
