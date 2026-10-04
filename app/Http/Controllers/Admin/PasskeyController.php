<?php
namespace App\Http\Controllers\Admin;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;

/**
 * Super-admin passkeys / hardware keys (advisory decision A2) — a
 * phishing-proof alternative to the TOTP code at sign-in step 2. TOTP stays
 * enrolled underneath as the fallback, so losing a key never locks anyone out.
 *
 * Bound to the current host: moving the console to another hostname (C2)
 * means re-registering keys; TOTP keeps working meanwhile.
 */
class PasskeyController extends Controller
{
    /** Registration options — only from a step-up-confirmed session (admin.reauth). */
    public function registerOptions(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $webAuthn = $this->webAuthn($request);
        $existing = DB::table('admin_passkeys')->where('admin_id', $admin->id)->pluck('credential_id')
            ->map(fn ($id) => ByteBuffer::fromBase64Url($id))->all();

        $args = $webAuthn->getCreateArgs(
            (string) $admin->id, $admin->email, trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email,
            60, false, 'preferred', null, $existing
        );
        $request->session()->put('admin_webauthn_challenge', $webAuthn->getChallenge()->getBinaryString());

        return response()->json($args);
    }

    public function register(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $challenge = $request->session()->pull('admin_webauthn_challenge');
        $name = trim((string) $request->input('name')) ?: 'Security key';

        try {
            if (!$challenge || !$this->originMatchesHost($request)) {
                throw new \RuntimeException('challenge/origin');
            }
            $data = $this->webAuthn($request)->processCreate(
                $this->b64($request->input('clientDataJSON')),
                $this->b64($request->input('attestationObject')),
                $challenge
            );
        } catch (\Throwable $e) {
            \Log::warning('[admin-security] passkey registration failed for ' . $admin->email . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Could not register this key. Please try again.'], 422);
        }

        DB::table('admin_passkeys')->insert([
            'admin_id'      => $admin->id,
            'name'          => mb_substr($name, 0, 100),
            'credential_id' => rtrim(strtr(base64_encode($data->credentialId), '+/', '-_'), '='),
            'public_key'    => $data->credentialPublicKey,
            'rp_id'         => $request->getHost(),
            'sign_count'    => (int) $data->signatureCounter,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        Common::alertSuperAdmins($admin, 'Super-admin passkey added', $admin->email . ' registered a passkey/security key "' . $name . '" from IP ' . $request->ip() . '.');

        return response()->json(['success' => true, 'msg' => 'Key registered.']);
    }

    public function destroy(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();
        $key = DB::table('admin_passkeys')->where('id', $id)->where('admin_id', $admin->id)->first();
        if ($key) {
            DB::table('admin_passkeys')->where('id', $key->id)->delete();
            Common::alertSuperAdmins($admin, 'Super-admin passkey removed', $admin->email . ' removed the passkey/security key "' . $key->name . '" from IP ' . $request->ip() . '.');
        }
        return redirect()->route('admin.2fa.setup');
    }

    /** Sign-in step 2 options — password already verified (admin_2fa_pending). */
    public function loginOptions(Request $request)
    {
        $admin = TwoFactorController::pendingAdmin($request);
        if (!$admin) {
            return response()->json(['success' => false, 'msg' => 'Your sign-in expired. Please start again.', 'redirect_url' => route('admin.loginindex')], 401);
        }
        $ids = DB::table('admin_passkeys')->where('admin_id', $admin->id)->pluck('credential_id')
            ->map(fn ($id) => ByteBuffer::fromBase64Url($id))->all();
        if (!$ids) {
            return response()->json(['success' => false, 'msg' => 'No passkey registered for this account.'], 422);
        }

        $webAuthn = $this->webAuthn($request);
        $args = $webAuthn->getGetArgs($ids, 60, true, true, true, true, true, 'preferred');
        $request->session()->put('admin_webauthn_challenge', $webAuthn->getChallenge()->getBinaryString());

        return response()->json($args);
    }

    public function login(Request $request)
    {
        $admin = TwoFactorController::pendingAdmin($request);
        if (!$admin) {
            return response()->json(['success' => false, 'msg' => 'Your sign-in expired. Please start again.', 'redirect_url' => route('admin.loginindex')], 401);
        }
        $challenge = $request->session()->pull('admin_webauthn_challenge');
        $key = DB::table('admin_passkeys')->where('admin_id', $admin->id)->where('credential_id', (string) $request->input('id'))->first();

        try {
            if (Common::isAccountLocked($admin) || !$challenge || !$key || !$this->originMatchesHost($request)) {
                throw new \RuntimeException('locked/challenge/key/origin');
            }
            $webAuthn = $this->webAuthn($request);
            $webAuthn->processGet(
                $this->b64($request->input('clientDataJSON')),
                $this->b64($request->input('authenticatorData')),
                $this->b64($request->input('signature')),
                $key->public_key,
                $challenge,
                (int) $key->sign_count
            );
        } catch (\Throwable $e) {
            \Log::warning('[admin-security] passkey sign-in failed for ' . $admin->email . ': ' . $e->getMessage());
            Common::logLoginAttempt('admin', $admin->email, false, $request);
            Common::registerFailedLogin($admin);
            return response()->json(['success' => false, 'msg' => 'That key could not be verified.'], 422);
        }

        DB::table('admin_passkeys')->where('id', $key->id)->update([
            'sign_count' => (int) $webAuthn->getSignatureCounter(), 'last_used_at' => now(), 'updated_at' => now(),
        ]);
        $request->session()->forget('admin_2fa_pending');
        Common::registerSuccessfulLogin($admin);
        LoginController::completeLogin($request, $admin);

        return response()->json(['success' => true, 'redirect_url' => route($admin->must_change_password ? 'admin.profile' : 'admin.dashboard')]);
    }

    private function webAuthn(Request $request): WebAuthn
    {
        // useBase64UrlEncoding: binary fields in the options JSON are base64url strings.
        return new WebAuthn('Wisdom Admin', $request->getHost(), null, true);
    }

    /**
     * The library only suffix-matches the origin host against the rpId;
     * require the exact host this request came in on.
     */
    private function originMatchesHost(Request $request): bool
    {
        $clientData = json_decode($this->b64($request->input('clientDataJSON')));
        return is_object($clientData) && isset($clientData->origin)
            && strcasecmp((string) parse_url($clientData->origin, PHP_URL_HOST), $request->getHost()) === 0;
    }

    private function b64($value): string
    {
        return (string) base64_decode(strtr((string) $value, '-_', '+/'), true);
    }
}
