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
</body>
</html>
