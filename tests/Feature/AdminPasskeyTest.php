<?php

namespace Tests\Feature;

use App\Helpers\Totp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Super-admin passkeys (A2) against a software authenticator: real P-256
 * keys and CBOR, so the library's crypto path runs end to end.
 */
class AdminPasskeyTest extends TestCase
{
    use DatabaseTransactions;

    private string $email = 'passkey-test@wisdom.test';
    private string $secret;
    private $key;
    private string $credId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $this->secret = Totp::generateSecret();
        DB::table('admins')->insert(['first_name' => 'P', 'last_name' => 'K', 'email' => $this->email, 'password' => Hash::make('Secret#123'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'created_at' => now(), 'updated_at' => now(),
            'two_factor_secret' => encrypt($this->secret, false), 'two_factor_confirmed_at' => now()]);
        $this->key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->credId = random_bytes(32);
    }

    // --- minimal CBOR encoder (uint, negint, bytes, text, map) ---
    private function cborHead(int $major, int $n): string
    {
        if ($n < 24) return chr($major << 5 | $n);
        if ($n < 256) return chr($major << 5 | 24) . chr($n);
        return chr($major << 5 | 25) . pack('n', $n);
    }
    private function cbor($v, bool $bytes = false): string
    {
        if (is_int($v)) return $v >= 0 ? $this->cborHead(0, $v) : $this->cborHead(1, -1 - $v);
        if (is_string($v)) return $bytes ? $this->cborHead(2, strlen($v)) . $v : $this->cborHead(3, strlen($v)) . $v;
        $out = $this->cborHead(5, count($v));
        foreach ($v as [$k, $val, $isBytes]) {
            $out .= $this->cbor($k) . (is_array($val) ? $this->cbor($val) : $this->cbor($val, $isBytes));
        }
        return $out;
    }
    private function b64u(string $bin): string { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
    private function clientData(string $type, string $challengeB64u, string $origin = 'https://thewisdom.io'): string
    {
        return json_encode(['type' => $type, 'challenge' => $challengeB64u, 'origin' => $origin]);
    }

    private function registration(string $challenge, string $origin = 'https://thewisdom.io'): array
    {
        $ec = openssl_pkey_get_details($this->key)['ec'];
        $cose = $this->cbor([[1, 2, false], [3, -7, false], [-1, 1, false],
            [-2, str_pad($ec['x'], 32, "\0", STR_PAD_LEFT), true], [-3, str_pad($ec['y'], 32, "\0", STR_PAD_LEFT), true]]);
        $authData = hash('sha256', 'thewisdom.io', true) . chr(0x41) . pack('N', 0)
            . str_repeat("\0", 16) . pack('n', strlen($this->credId)) . $this->credId . $cose;
        $att = $this->cbor([['fmt', 'none', false], ['attStmt', [], false], ['authData', $authData, true]]);
        return ['name' => 'Test key', 'clientDataJSON' => $this->b64u($this->clientData('webauthn.create', $challenge, $origin)), 'attestationObject' => $this->b64u($att)];
    }

    private function assertion(string $challenge, int $counter, string $origin = 'https://thewisdom.io'): array
    {
        $authData = hash('sha256', 'thewisdom.io', true) . chr(0x01) . pack('N', $counter);
        $cd = $this->clientData('webauthn.get', $challenge, $origin);
        openssl_sign($authData . hash('sha256', $cd, true), $sig, $this->key, OPENSSL_ALGO_SHA256);
        return ['id' => $this->b64u($this->credId), 'clientDataJSON' => $this->b64u($cd), 'authenticatorData' => $this->b64u($authData), 'signature' => $this->b64u($sig)];
    }

    private function passwordStep() { return $this->postJson('/admin/do-login', ['email' => $this->email, 'password' => 'Secret#123']); }

    public function test_register_sign_in_and_remove()
    {
        // signed in with password + TOTP
        $this->passwordStep();
        $this->post('/admin/two-factor', ['code' => Totp::code($this->secret, intdiv(time(), 30))])->assertRedirect(route('admin.dashboard'));
        $this->get('/admin/two-factor/setup')->assertStatus(200)->assertSee('No keys registered yet');

        // registration needs step-up re-auth
        session()->forget('admin_reauth_at');
        $this->postJson('/admin/passkeys/options')->assertStatus(403)->assertJson(['reauth_required' => true]);
        session(['admin_reauth_at' => time()]);

        // wrong origin is refused
        $opts = $this->postJson('/admin/passkeys/options')->assertStatus(200)->json();
        $this->postJson('/admin/passkeys', $this->registration($opts['publicKey']['challenge'], 'https://evilthewisdom.io'))->assertStatus(422);

        $opts = $this->postJson('/admin/passkeys/options')->json();
        $this->assertSame('thewisdom.io', $opts['publicKey']['rp']['id']);
        $this->postJson('/admin/passkeys', $this->registration($opts['publicKey']['challenge']))->assertStatus(200)->assertJson(['success' => true]);
        $row = DB::table('admin_passkeys')->where('credential_id', $this->b64u($this->credId))->first();
        $this->assertNotNull($row);
        $this->get('/admin/two-factor/setup')->assertSee('Test key');

        // sign in with password + passkey
        $this->get('/admin/logout');
        $this->passwordStep()->assertJson(['redirect_url' => route('admin.2fa.challenge')]);
        $this->get('/admin/two-factor')->assertSee('Use passkey');
        $opts = $this->postJson('/admin/two-factor/passkey/options')->assertStatus(200)->json();
        $this->assertSame($this->b64u($this->credId), $opts['publicKey']['allowCredentials'][0]['id']);
        $good = $this->assertion($opts['publicKey']['challenge'], 1);
        $this->postJson('/admin/two-factor/passkey', $good)->assertStatus(200)->assertJson(['success' => true, 'redirect_url' => route('admin.dashboard')]);
        $this->assertAuthenticated('admin');
        $this->assertSame(1, (int) DB::table('admin_passkeys')->where('id', $row->id)->value('sign_count'));

        // replay (stale challenge) and counter rollback are both refused
        $this->get('/admin/logout');
        $this->passwordStep();
        $this->postJson('/admin/two-factor/passkey', $good)->assertStatus(422);
        $opts = $this->postJson('/admin/two-factor/passkey/options')->json();
        $this->postJson('/admin/two-factor/passkey', $this->assertion($opts['publicKey']['challenge'], 1))->assertStatus(422);
        $this->assertGuest('admin');

        // signature from another key is refused
        $opts = $this->postJson('/admin/two-factor/passkey/options')->json();
        $this->key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->postJson('/admin/two-factor/passkey', $this->assertion($opts['publicKey']['challenge'], 5))->assertStatus(422);
        $this->assertGuest('admin');

        // TOTP still works as fallback; then remove the key
        $this->post('/admin/two-factor', ['code' => Totp::code($this->secret, intdiv(time(), 30) + 1)])->assertRedirect(route('admin.dashboard'));
        session(['admin_reauth_at' => time()]);
        $this->post('/admin/passkeys/' . $row->id . '/delete')->assertRedirect(route('admin.2fa.setup'));
        $this->assertFalse(DB::table('admin_passkeys')->where('id', $row->id)->exists());
    }

    public function test_passkey_endpoints_need_pending_password_step()
    {
        $this->postJson('/admin/two-factor/passkey/options')->assertStatus(401);
        $this->postJson('/admin/two-factor/passkey', ['id' => 'x'])->assertStatus(401);
        $this->postJson('/admin/passkeys/options')->assertStatus(401);
    }
}
