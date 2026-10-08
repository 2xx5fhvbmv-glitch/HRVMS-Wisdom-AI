@extends('admin.layouts.app')
@section('page_tab_title', 'Demo ENV')

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header"><h1 class="mb-0">Demo ENV</h1></div>
        <div class="card-body">
          <p class="mb-1">A demo resort with ~100 employees and 12 months of linked data in every module, generated relative to today.
            Clients can use it freely — <strong>nothing is wiped except by the reset below.</strong></p>
          <p class="text-muted mb-0">Demo logins use the <code>{{ '@' . config('demo.login_domain') }}</code> addresses and the password from <code>DEMO_PASSWORD</code>.
            Every email the demo sends goes only to <code>{{ config('demo.mail_to') }}</code>, marked with who it was meant for.</p>
        </div>
      </div>

      @foreach($notices as [$type, $text])
        <div class="alert alert-{{ $type }}">{{ $text }}</div>
      @endforeach

      @if($status)
        <div class="alert alert-{{ ['queued' => 'info', 'running' => 'info', 'done' => 'success', 'failed' => 'danger'][$status['state']] ?? 'secondary' }}">
          @if($busy)<i class="fas fa-spinner fa-spin"></i>@endif
          <strong>{{ ucfirst($status['state']) }}</strong> — {{ $status['message'] }}
          <small class="text-muted ml-2">{{ $status['updated_at'] }}</small>
          @if($status['state'] === 'queued' && \Illuminate\Support\Carbon::parse($status['updated_at'])->lt(now()->subMinutes(2)))
            <br><strong>Still waiting for a queue worker — make sure <code>php artisan queue:work</code> is running.</strong>
          @endif
          @if($busy)<br><small>This page refreshes itself.</small>@endif
        </div>
      @endif

      <div class="row">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Demo resort</h3></div>
            <div class="card-body">
              @if($resort)
                <dl class="row mb-0">
                  <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $resort->resort_name }} <small class="text-muted">(rename / logo: Resorts → Edit)</small></dd>
                  <dt class="col-sm-4">Code</dt><dd class="col-sm-8"><code>{{ $resort->resort_id }}</code></dd>
                  <dt class="col-sm-4">Employees</dt><dd class="col-sm-8">{{ $counts['employees'] }}</dd>
                  <dt class="col-sm-4">Logins</dt><dd class="col-sm-8">{{ $counts['logins'] }}</dd>
                </dl>
              @else
                <p class="mb-0">Not created yet — the first reset creates it.</p>
              @endif
            </div>
          </div>

          <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-undo"></i> Reset Demo ENV</h3></div>
            <div class="card-body">
              <p>Deletes <strong>all</strong> Demo ENV data — including everything clients changed — and builds it again with fresh dates.
                Only the demo resort is touched. You will be asked to confirm your password and authentication code.</p>
              <form method="post" action="{{ route('admin.demo_env.reset') }}" onsubmit="return confirm('Wipe and rebuild Demo ENV now?');">
                @csrf
                <div class="form-group">
                  <label>Type <code>{{ $phrase }}</code> to confirm</label>
                  <input type="text" name="confirm" class="form-control" autocomplete="off" required>
                </div>
                <button type="submit" class="btn btn-danger" @disabled($busy)><i class="fas fa-undo"></i> Reset Demo ENV</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title">Last reset</h3></div>
            <div class="card-body">
              @if($last)
                <dl class="row">
                  <dt class="col-sm-4">Finished</dt><dd class="col-sm-8">{{ $last['finished_at'] ?? '—' }} ({{ $last['seconds'] ?? '?' }}s)</dd>
                  <dt class="col-sm-4">Seed</dt><dd class="col-sm-8"><code>{{ $last['seed'] }}</code> <small class="text-muted">(rebuild the same data: <code>php artisan demo:reset --seed={{ $last['seed'] }}</code>)</small></dd>
                  <dt class="col-sm-4">Rows removed</dt><dd class="col-sm-8">{{ number_format($last['deleted'] ?? 0) }}</dd>
                </dl>
                <h6>Checks</h6>
                <ul class="list-unstyled mb-0">
                  @foreach($last['verify']['checks'] ?? [] as $c)
                    <li>
                      @if($c['ok'])<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-danger"></i>@endif
                      {{ $c['name'] }} @if($c['detail'])<small class="text-danger">— {{ $c['detail'] }}</small>@endif
                    </li>
                  @endforeach
                </ul>
              @else
                <p class="mb-0 text-muted">No reset has run yet.</p>
              @endif
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>
@endsection

@section('import-scripts')
@if($busy)
<script>setTimeout(function () { window.location.href = @json(route('admin.demo_env.index')); }, 5000);</script>
@endif
@endsection
