@extends('admin.layouts.app')
@section('page_tab_title', 'Resort Data Setup')

@php
  $files = $import->files ?? [];
  $types = collect($files)->pluck('mapping.type')->filter()->unique()->all();
  $report = $shown->report;
  $busy = $shown->busy();
  $jobLabels = ['analyse' => 'Reading the files and detecting their layout', 'validate' => 'Validating (dry run)', 'import' => 'Importing'];
  $typeLabels = ['divisions' => 'Divisions', 'departments' => 'Departments', 'sections' => 'Sections', 'positions' => 'Positions',
                 'levels' => 'Levels (reference)', 'staff' => 'Staff list', 'attendance' => 'Attendance'];
@endphp

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <h1 class="mb-0">{{ $resort->resort_name }} — Data Setup</h1>
          <a href="{{ route('admin.resort_data_setup.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All resorts</a>
        </div>
        <div class="card-body">
          <p class="mb-1">In this resort now: <strong>{{ $existing['divisions'] }}</strong> divisions, <strong>{{ $existing['departments'] }}</strong> departments,
            <strong>{{ $existing['positions'] }}</strong> positions, <strong>{{ $existing['employees'] }}</strong> employees.</p>
          <p class="text-muted mb-0">1. Upload the export files &rarr; 2. Check each file's type and columns &rarr; 3. Set the options &rarr; 4. Validate (full dry run, nothing saved) &rarr; 5. Import.
            Existing records are matched and updated, never duplicated, so the same files can be imported again.</p>
        </div>
      </div>

      @foreach($notices as [$type, $text])
        <div class="alert alert-{{ $type }}">{{ $text }}</div>
      @endforeach

      @if($busy)
        <div class="alert alert-info">
          <i class="fas fa-spinner fa-spin"></i> {{ $jobLabels[$shown->job_action] ?? 'Working' }}…
          {{ $shown->job_status === 'queued' ? 'queued' : 'started ' . optional($shown->job_started_at)->diffForHumans() }}. This page refreshes itself.
          @if($shown->job_status === 'queued' && $shown->updated_at?->lt(now()->subMinutes(2)))
            <br><strong>Still waiting for a queue worker — make sure <code>php artisan queue:work</code> is running on the server.</strong>
          @endif
        </div>
      @elseif($shown->job_message)
        <div class="alert alert-{{ $shown->job_status === 'failed' ? 'danger' : 'secondary' }}">{!! nl2br(e($shown->job_message)) !!}</div>
      @endif

      @if($credentials)
        <div class="card card-outline card-success">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title mb-0">New logins ({{ count($credentials) }})</h3>
            <div>
              <button type="button" class="btn btn-success btn-sm" id="download-credentials"><i class="fas fa-download"></i> Download CSV</button>
              <form method="post" action="{{ route('admin.resort_data_setup.clear_credentials', $resort->id) }}" class="d-inline"
                    onsubmit="return confirm('Clear the temporary passwords? Download them first — they cannot be shown again.');">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">I have saved them — clear</button>
              </form>
            </div>
          </div>
          <div class="card-body">
            <p>Stored encrypted only until you clear them. Every account must change its password at first login. Placeholder emails are not real mailboxes: no email was sent, and "forgot password" works only after HR sets a real email.</p>
            <div class="table-responsive" style="max-height: 320px;">
              <table class="table table-sm table-bordered">
                <thead><tr><th>Employee ID</th><th>Name</th><th>Login email</th><th>Temporary password</th></tr></thead>
                <tbody>
                  @foreach($credentials as $c)
                    <tr><td>{{ $c['emp_id'] }}</td><td>{{ $c['name'] }}</td><td>{{ $c['email'] }}</td><td><code>{{ $c['password'] }}</code></td></tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      {{-- 1. Upload --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title">1. Upload files</h3></div>
        <div class="card-body">
          @if($shown->status === 'imported')
            <p class="text-success">Last import finished {{ optional($shown->imported_at)->format('d M Y H:i') }}.
              Uploading again starts a new batch with the same options; existing records are updated, not duplicated.</p>
          @endif
          <form method="post" action="{{ route('admin.resort_data_setup.upload', $resort->id) }}" enctype="multipart/form-data" class="form-inline flex-wrap">
            @csrf
            <input type="file" name="files[]" multiple required accept=".xls,.xlsx,.csv" class="form-control-file mr-2 mb-2" style="max-width: 100%;">
            <button type="submit" class="btn btn-primary mb-2" @disabled($busy)><i class="fas fa-upload"></i> Upload &amp; detect</button>
          </form>
          <small class="text-muted">.xls, .xlsx or .csv, up to 20 MB each, 5,000 rows per file. Files are read on the queue. The AI only reads the top rows of a file whose layout it has not seen before;
            a known layout is reused at no AI cost. Uploading a file with the same name replaces it.</small>
        </div>
      </div>

      {{-- 2. Files --}}
      @if($files)
        <div class="card">
          <div class="card-header"><h3 class="card-title">2. Files and column mapping</h3></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead><tr><th>File</th><th>Type</th><th>Records</th><th>Detected by</th><th>Status</th><th style="width: 170px;">Action</th></tr></thead>
                <tbody>
                  @foreach($files as $file)
                    @php($mapping = $file['mapping'] ?? null)
                    <tr>
                      <td>{{ $file['name'] }}</td>
                      <td>{{ $mapping ? ($typeLabels[$mapping['type']] ?? $mapping['type']) : '—' }}</td>
                      <td>{{ $file['count'] ?? '—' }}</td>
                      <td>
                        @switch($file['source'] ?? null)
                          @case('ai') <span class="badge badge-info">AI · {{ $file['tokens'] }} tokens</span> @break
                          @case('cache') <span class="badge badge-success">Saved layout · 0 tokens</span> @break
                          @case('manual') <span class="badge badge-primary">Manual</span> @break
                          @default <span class="badge badge-secondary">Not mapped</span>
                        @endswitch
                      </td>
                      <td>
                        @if(!empty($file['pending']))
                          <span class="text-info"><i class="fas fa-spinner fa-spin"></i> Reading…</span>
                        @elseif(!empty($file['note']))
                          <span class="text-danger">{{ $file['note'] }}</span>
                        @elseif($mapping)
                          <span class="text-success">Ready</span>
                        @endif
                      </td>
                      <td>
                        <button type="button" class="btn btn-info btn-sm" data-toggle="collapse" data-target="#map-{{ $file['id'] }}"><i class="fas fa-columns"></i> Mapping</button>
                        <form method="post" action="{{ route('admin.resort_data_setup.remove', [$resort->id, $file['id']]) }}" class="d-inline" onsubmit="return confirm('Remove this file?');">
                          @csrf
                          <button type="submit" class="btn btn-danger btn-sm" title="Remove" @disabled($busy)><i class="fas fa-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                    <tr class="collapse {{ $mapping ? '' : 'show' }}" id="map-{{ $file['id'] }}">
                      <td colspan="6" class="bg-light">
                        <form method="post" action="{{ route('admin.resort_data_setup.mapping', [$resort->id, $file['id']]) }}" class="js-mapping"
                              data-preview="{{ json_encode($file['preview'] ?? []) }}">
                          @csrf
                          <div class="form-row">
                            <div class="form-group col-md-3">
                              <label>File type</label>
                              <select name="type" class="form-control js-type">
                                <option value="">Choose…</option>
                                @foreach($typeLabels as $value => $label)
                                  <option value="{{ $value }}" @selected(($mapping['type'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group col-md-5">
                              <label>Header row (the row with the column titles)</label>
                              <select name="header_row" class="form-control js-header">
                                @foreach($file['preview'] ?? [] as $r => $cells)
                                  @php($text = collect($cells)->filter(fn ($c) => $c !== '')->take(5)->implode(' | '))
                                  @if($text !== '')
                                    <option value="{{ $r }}" @selected(($file['header_row'] ?? null) === $r)>Row {{ $r + 1 }}: {{ \Illuminate\Support\Str::limit($text, 80) }}</option>
                                  @endif
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group col-md-4">
                              <label>Group label column <small class="text-muted">(rows that only name a department)</small></label>
                              <select name="group_column" class="form-control js-col" data-selected="{{ $file['group_idx'] ?? '' }}"></select>
                            </div>
                          </div>
                          @foreach($fields as $type => $typeFields)
                            <div class="form-row js-fields" data-type="{{ $type }}" style="display: none;">
                              @foreach($typeFields as $field => $required)
                                <div class="form-group col-md-3">
                                  <label>{{ str_replace('_', ' ', ucfirst($field)) }} @if($required)<span class="red-mark">*</span>@endif</label>
                                  <select name="columns[{{ $type }}][{{ $field }}]" class="form-control js-col"
                                          data-selected="{{ ($mapping['type'] ?? '') === $type ? ($file['columns_idx'][$field] ?? '') : '' }}"></select>
                                </div>
                              @endforeach
                            </div>
                          @endforeach
                          <button type="submit" class="btn btn-primary btn-sm" @disabled($busy)>Save mapping</button>
                          <small class="text-muted ml-2">Saved mappings are reused for every later file with the same column titles.</small>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- 3. Options --}}
        <form method="post" action="{{ route('admin.resort_data_setup.options', $resort->id) }}">
          @csrf
          <div class="card">
            <div class="card-header"><h3 class="card-title">3. Options</h3></div>
            <div class="card-body">
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Placeholder login email domain</label>
                  <input type="text" name="email_domain" class="form-control" value="{{ $options['email_domain'] }}" required>
                  <small class="text-muted">New staff without an email get <code>&lt;Employee ID&gt;@{{ $options['email_domain'] }}</code>. Must be unique to this resort.</small>
                </div>
              </div>

              @if($options['level_ranks'])
                <h5 class="mt-2">Level &rarr; rank</h5>
                <p class="text-muted mb-2">An EXCOM-level employee whose position is "General Manager" becomes GM automatically.</p>
                <div class="form-row">
                  @foreach($options['level_ranks'] as $level => $rank)
                    <div class="form-group col-md-3">
                      <label>{{ $level }}</label>
                      <select name="level_ranks[{{ $level }}]" class="form-control">
                        <option value="">Choose…</option>
                        @foreach($ranks as $id => $name)
                          <option value="{{ $id }}" @selected((string) $rank === (string) $id)>{{ $name }}</option>
                        @endforeach
                      </select>
                    </div>
                  @endforeach
                </div>
              @endif

              @if(in_array('staff', $types, true))
                <h5 class="mt-2">Role departments</h5>
                <p class="text-muted mb-2">EXCOM, HOD and Manager level staff in these departments get the HR / Finance / Clinic role. Everyone else keeps their level's rank.</p>
                <div class="form-row">
                  @foreach(['hr' => 'HR', 'finance' => 'Finance', 'clinic' => 'Clinic / Medical'] as $role => $label)
                    <div class="form-group col-md-4">
                      <label>{{ $label }} department</label>
                      <select name="roles[{{ $role }}]" class="form-control">
                        <option value="">None</option>
                        @foreach($options['departments'] as $dept)
                          <option value="{{ $dept }}" @selected($options['roles'][$role] === $dept)>{{ $dept }}</option>
                        @endforeach
                      </select>
                    </div>
                  @endforeach
                </div>
              @endif

              @if(in_array('attendance', $types, true))
                <h5 class="mt-2">Attendance</h5>
                <div class="form-row">
                  <div class="form-group col-md-3">
                    <label>Period start (first day column)</label>
                    <input type="date" name="period_start" class="form-control" value="{{ $options['period_start'] }}">
                  </div>
                  <div class="form-group col-md-5">
                    <label>Shift for migrated days</label>
                    <select name="shift_id" class="form-control">
                      <option value="">Choose…</option>
                      @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected((string) $options['shift_id'] === (string) $shift->id)>{{ $shift->ShiftName }} ({{ $shift->StartTime }}–{{ $shift->EndTime }})</option>
                      @endforeach
                    </select>
                    @if($shifts->isEmpty())
                      <small class="text-danger">This resort has no shifts yet — create one in Time &amp; Attendance first.</small>
                    @else
                      <small class="text-muted">Present days are stored as 8 hours from the shift start.</small>
                    @endif
                  </div>
                </div>
                <div class="form-row">
                  @foreach($options['codes'] as $code => $action)
                    <div class="form-group col-md-3">
                      <label>Code <strong>{{ $code }}</strong></label>
                      <select name="codes[{{ $code }}]" class="form-control">
                        <option value="">Choose…</option>
                        <option value="present" @selected($action === 'present')>Present (8 h)</option>
                        <option value="dayoff" @selected($action === 'dayoff')>Day off</option>
                        @foreach($categories as $id => $name)
                          <option value="leave:{{ $id }}" @selected($action === 'leave:'.$id)>Leave: {{ $name }}</option>
                        @endforeach
                        <option value="skip" @selected($action === 'skip')>Skip (do not import)</option>
                      </select>
                    </div>
                  @endforeach
                </div>
                @if($categories->isEmpty())
                  <small class="text-danger">This resort has no leave categories yet — create them in Leave configuration before mapping leave codes.</small>
                @else
                  <small class="text-muted">Leave codes become approved leave records (they count against leave balances), matching how payroll reads leave.</small>
                @endif
              @endif
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" @disabled($busy)>Save options</button></div>
          </div>
        </form>

        {{-- 4 / 5 --}}
        <div class="card">
          <div class="card-header"><h3 class="card-title">4. Validate &amp; 5. Import</h3></div>
          <div class="card-body">
            <form method="post" action="{{ route('admin.resort_data_setup.validate', $resort->id) }}" class="d-inline js-busy">
              @csrf
              <button type="submit" class="btn btn-info" @disabled($busy)><i class="fas fa-check-double"></i> Validate (dry run)</button>
            </form>
            <form method="post" action="{{ route('admin.resort_data_setup.import', $resort->id) }}" class="d-inline js-busy"
                  onsubmit="return confirm('Import into {{ addslashes($resort->resort_name) }}? This writes to the live tables.');">
              @csrf
              <button type="submit" class="btn btn-success" @disabled(!$canImport)><i class="fas fa-file-import"></i> Import</button>
            </form>
            <p class="text-muted mt-2 mb-0">
              @if($canImport)
                Validation passed. Import repeats the same run on the queue and commits it — all or nothing.
              @else
                Import unlocks after a validation with no errors. Changing files, mappings or options needs a new validation.
              @endif
            </p>
          </div>
        </div>
      @endif

      {{-- Report --}}
      @if($report)
        <div class="card card-outline {{ empty($report['errors']) ? 'card-success' : 'card-danger' }}">
          <div class="card-header">
            <h3 class="card-title">
              {{ $report['mode'] === 'import' ? ($report['committed'] ? 'Import result' : 'Import failed — nothing saved') : 'Validation result (nothing saved)' }}
              <small class="text-muted">{{ $report['at'] }}</small>
            </h3>
          </div>
          <div class="card-body">
            <div class="row">
              @foreach($report['counts'] as $type => $counts)
                <div class="col-md-4 col-lg-2 mb-2">
                  <strong>{{ $typeLabels[$type] ?? ucfirst($type) }}</strong>
                  <ul class="list-unstyled mb-0 small">
                    @foreach($counts as $what => $n)
                      <li>{{ $what }}: {{ $n }}</li>
                    @endforeach
                  </ul>
                </div>
              @endforeach
            </div>

            @if($report['errors'])
              <h5 class="text-danger mt-3">Errors ({{ count($report['errors']) }}) — must be fixed before importing</h5>
              <div class="table-responsive" style="max-height: 360px;">
                <table class="table table-sm table-bordered">
                  <thead><tr><th>File</th><th>Row</th><th>Problem</th></tr></thead>
                  <tbody>
                    @foreach(array_slice($report['errors'], 0, 300) as $e)
                      <tr><td>{{ $e['file'] ?? '—' }}</td><td>{{ $e['row'] ?? '—' }}</td><td>{{ $e['message'] }}</td></tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif

            @if($report['warnings'])
              <h5 class="text-warning mt-3">Warnings — imported anyway, review afterwards</h5>
              <ul class="small">
                @foreach($report['warnings'] as $message => $n)
                  <li>{{ $message }} @if($n > 1)<span class="badge badge-secondary">× {{ $n }}</span>@endif</li>
                @endforeach
              </ul>
            @endif
          </div>
        </div>
      @endif

    </div>
  </section>
</div>
@endsection

@section('import-scripts')
<script>
  $(function () {
    // Column selects list the titles of the chosen header row.
    $('.js-mapping').each(function () {
      var $form = $(this), preview = $form.data('preview') || [];
      function fillColumns() {
        var row = preview[$form.find('.js-header').val()] || [];
        $form.find('.js-col').each(function () {
          var $s = $(this), keep = $s.val() !== null && $s.val() !== '' ? $s.val() : String($s.data('selected'));
          $s.empty().append($('<option>').val('').text('—'));
          row.forEach(function (title, i) {
            if (title !== '') { $s.append($('<option>').val(i).text((i + 1) + ': ' + title)); }
          });
          $s.val(keep);
          if ($s.val() === null) { $s.val(''); }
        });
      }
      function showFields() {
        var type = $form.find('.js-type').val();
        $form.find('.js-fields').each(function () {
          var on = $(this).data('type') === type;
          $(this).toggle(on).find('select').prop('disabled', !on);
        });
      }
      $form.find('.js-header').on('change', fillColumns);
      $form.find('.js-type').on('change', showFields);
      fillColumns();
      showFields();
    });

    @if($busy)
      setTimeout(function () { window.location.href = @json(route('admin.resort_data_setup.show', $resort->id)); }, 4000);
    @endif

    $('.js-busy').on('submit', function (e) {
      if (!e.isDefaultPrevented()) { $(this).find('button').prop('disabled', true).append(' …'); }
    });

    var credentials = @json($credentials);
    $('#download-credentials').on('click', function () {
      var esc = function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; };
      var csv = ['Employee ID,Name,Login email,Temporary password'].concat(credentials.map(function (c) {
        return [c.emp_id, c.name, c.email, c.password].map(esc).join(',');
      })).join('\r\n');
      var a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
      a.download = @json(\Illuminate\Support\Str::slug($resort->resort_name) . '-logins.csv');
      a.click();
    });
  });
</script>
@endsection
