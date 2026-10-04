<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HRVMS- wisdomAI | Two-factor authentication</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/dist/css/adminlte.min.css') }}">
</head>
<body class="hold-transition login-page">
  <div class="login-box" style="width: 420px; max-width: 100%;">
    <div class="card card-outline card-primary">
      <div class="card-header text-center">
        <span class="h1"><b>HRVMS-</b>wisdomAI</span>
      </div>
      <div class="card-body">
        @if (!empty($error) || $errors->any())
          <div class="alert alert-danger">{{ $error ?? $errors->first() }}</div>
        @endif

        @if ($mode === 'challenge')
          <p class="login-box-msg">Enter the 6-digit code from your authenticator app, or one of your recovery codes.</p>
          <form method="post" action="{{ route('admin.2fa.verify') }}">
            @csrf
            <input type="text" name="code" class="form-control mb-3" inputmode="numeric" autocomplete="one-time-code" autofocus required placeholder="123456">
            <button type="submit" class="btn btn-primary btn-block">Verify</button>
          </form>
          @if (!empty($hasPasskey))
            <div class="text-center text-muted my-2">or</div>
            <button type="button" class="btn btn-outline-primary btn-block" onclick="passkeySignIn()"><i class="fas fa-key"></i> Use passkey / security key</button>
            <div id="passkey-msg" class="small text-danger mt-2"></div>
          @endif
          <p class="mt-3 mb-0"><a href="{{ route('admin.loginindex') }}">Back to sign in</a></p>

        @elseif ($mode === 'setup')
          <p class="login-box-msg">Two-factor authentication is required for the super-admin console. Scan this code with Google Authenticator, Authy or 1Password, then enter the 6-digit code it shows.</p>
          <div class="text-center mb-2">{!! $qr !!}</div>
          <p class="text-center small text-muted">Can't scan? Enter this key manually:<br><code style="word-break: break-all;">{{ trim(chunk_split($secret, 4, ' ')) }}</code></p>
          <form method="post" action="{{ route('admin.2fa.confirm') }}">
            @csrf
            <input type="text" name="code" class="form-control mb-3" inputmode="numeric" autocomplete="one-time-code" autofocus required placeholder="123456">
            <button type="submit" class="btn btn-primary btn-block">Turn on two-factor</button>
          </form>
          <p class="mt-3 mb-0"><a href="{{ route('admin.logout') }}">Sign out</a></p>

        @elseif ($mode === 'codes')
          <p class="login-box-msg">Two-factor is on. Save these recovery codes in your password manager now — each works once if you lose your phone, and they won't be shown again.</p>
          <pre class="text-center" style="font-size: 1.1em;">{{ implode("\n", $codes) }}</pre>
          <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block">I've saved them — continue</a>

        @elseif ($mode === 'enrolled')
          <p class="login-box-msg">Two-factor authentication is on for {{ $admin->email }} since {{ $admin->two_factor_confirmed_at->toDayDateTimeString() }}.
            {{ count($admin->two_factor_recovery_codes ?? []) }} recovery code(s) left.</p>
          <p class="small text-muted">Lost your device or out of codes? Another super-admin (or the server operator) runs <code>php artisan admin:2fa-reset {{ $admin->email }}</code>, and you re-enrol at next sign-in.</p>

          <h6 class="mt-3"><i class="fas fa-key"></i> Passkeys &amp; security keys</h6>
          <p class="small text-muted">Phishing-proof alternative to the 6-digit code at sign-in (YubiKey, Touch ID, Windows Hello, phone passkey). Your authenticator app stays as the fallback.</p>
          <ul class="list-unstyled small">
            @forelse ($passkeys as $key)
              <li class="d-flex justify-content-between align-items-center border-bottom py-1">
                <span>{{ $key->name }}<br><span class="text-muted">added {{ \Carbon\Carbon::parse($key->created_at)->toFormattedDateString() }}{{ $key->last_used_at ? ' · last used ' . \Carbon\Carbon::parse($key->last_used_at)->diffForHumans() : '' }}</span></span>
                <form method="post" action="{{ route('admin.passkey.destroy', $key->id) }}" onsubmit="return confirm('Remove this key?')">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
              </li>
            @empty
              <li class="text-muted">No keys registered yet.</li>
            @endforelse
          </ul>
          <div class="input-group mb-2">
            <input type="text" id="passkey-name" class="form-control" maxlength="100" placeholder="Key name, e.g. YubiKey 5C">
            <div class="input-group-append"><button type="button" class="btn btn-outline-primary" onclick="passkeyRegister()">Add key</button></div>
          </div>
          <div id="passkey-msg" class="small mb-3"></div>
          <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block">Back to dashboard</a>

        @elseif ($mode === 'reauth')
          <p class="login-box-msg">This action needs you to confirm it's you.</p>
          <form method="post" action="{{ route('admin.reauth.submit') }}">
            @csrf
            <input type="password" name="password" class="form-control mb-3" autocomplete="current-password" autofocus required placeholder="Password">
            <input type="text" name="code" class="form-control mb-3" inputmode="numeric" autocomplete="one-time-code" required placeholder="Authentication code">
            <button type="submit" class="btn btn-primary btn-block">Confirm</button>
          </form>
          <p class="mt-3 mb-0"><a href="{{ route('admin.dashboard') }}">Cancel</a></p>
        @endif
      </div>
    </div>
  </div>
  @if (in_array($mode, ['challenge', 'enrolled'], true))
  <script>
    // WebAuthn glue — server options use base64url for every binary field.
    const b64uToBuf = s => Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((s.length + 3) % 4)), c => c.charCodeAt(0)).buffer;
    const bufToB64u = b => btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    const passkeyMsg = (text, ok) => { const el = document.getElementById('passkey-msg'); el.textContent = text; el.className = 'small mb-3 ' + (ok ? 'text-success' : 'text-danger'); };
    async function postJson(url, body) {
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body || {}) });
      const data = await res.json().catch(() => ({}));
      if (data.redirect_url && !res.ok) { window.location.href = data.redirect_url; throw new Error('redirect'); }
      if (!res.ok) throw new Error(data.msg || 'Request failed');
      return data;
    }

    async function passkeySignIn() {
      try {
        const opts = await postJson('{{ route('admin.passkey.login.options') }}');
        opts.publicKey.challenge = b64uToBuf(opts.publicKey.challenge);
        opts.publicKey.allowCredentials.forEach(c => c.id = b64uToBuf(c.id));
        const cred = await navigator.credentials.get(opts);
        const out = await postJson('{{ route('admin.passkey.login') }}', {
          id: bufToB64u(cred.rawId),
          clientDataJSON: bufToB64u(cred.response.clientDataJSON),
          authenticatorData: bufToB64u(cred.response.authenticatorData),
          signature: bufToB64u(cred.response.signature),
        });
        window.location.href = out.redirect_url;
      } catch (e) { if (e.message !== 'redirect') passkeyMsg(e.name === 'NotAllowedError' ? 'Cancelled or timed out.' : e.message); }
    }

    async function passkeyRegister() {
      try {
        const opts = await postJson('{{ route('admin.passkey.register.options') }}');
        opts.publicKey.challenge = b64uToBuf(opts.publicKey.challenge);
        opts.publicKey.user.id = b64uToBuf(opts.publicKey.user.id);
        (opts.publicKey.excludeCredentials || []).forEach(c => c.id = b64uToBuf(c.id));
        const cred = await navigator.credentials.create(opts);
        await postJson('{{ route('admin.passkey.register') }}', {
          name: document.getElementById('passkey-name').value,
          clientDataJSON: bufToB64u(cred.response.clientDataJSON),
          attestationObject: bufToB64u(cred.response.attestationObject),
        });
        window.location.reload();
      } catch (e) { if (e.message !== 'redirect') passkeyMsg(e.name === 'InvalidStateError' ? 'This key is already registered.' : (e.name === 'NotAllowedError' ? 'Cancelled or timed out.' : e.message)); }
    }
  </script>
  @endif
</body>
</html>
