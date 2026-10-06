@extends('admin.layouts.app')
@section('page_tab_title', 'Resort Data Setup')

@php
  $files = $import->files ?? [];
  $types = collect($files)->pluck('mapping.type')->filter()->unique()->all();
  $report = $shown->report;
  $jobLabels = ['analyse' => 'Reading the files and detecting their layout', 'validate' => 'Validating (dry run)', 'import' => 'Importing', 'undo' => 'Undoing the import'];
  $typeLabels = ['divisions' => 'Divisions', 'departments' => 'Departments', 'sections' => 'Sections', 'positions' => 'Positions',
                 'levels' => 'Levels (reference)', 'staff' => 'Staff list', 'employee_details' => 'Employee details',
                 'holidays' => 'Public holidays', 'attendance' => 'Attendance'];
  $kindLabels = $typeLabels + ['details' => 'Employee details', 'access' => 'Page access'];
  $fieldLabels = ['Position_id' => 'Position', 'Dept_id' => 'Department', 'division_id' => 'Division', 'Section_id' => 'Section', 'section_id' => 'Section',
                  'reporting_to' => 'Reporting manager', 'main_rank' => 'Role rank', 'Rank' => 'Rank', 'rank' => 'Rank', 'joining_date' => 'Hire date',
                  'basic_salary' => 'Basic salary', 'basic_salary_currency' => 'Salary currency', 'personal_phone' => 'Phone', 'dob' => 'Date of birth',
                  'visa_expiry_date' => 'Visa expiry', 'work_permit_expiry_date' => 'Work permit expiry', 'end_date' => 'Visa expiry', 'start_date' => 'Visa start',
                  'Visa_Number' => 'Visa number', 'WP_No' => 'Work permit number', 'PublicHolidayName' => 'Holiday name', 'short_name' => 'Short name'];
  $ranksByValue = $ranks;
  $showValue = function ($field, $value) use ($names, $ranksByValue) {
      if ($value === null || $value === '') { return '—'; }
      if (isset($names[$field][$value])) { return $names[$field][$value]; }
      if (in_array($field, ['rank', 'Rank', 'main_rank'], true) && isset($ranksByValue[$value])) { return $ranksByValue[$value]; }
      return $value;
  };

  // What still needs a decision before validation can pass.
  $todo = [];
  if (collect($files)->contains(fn ($f) => !empty($f['note']) || empty($f['mapping'])) && !collect($files)->contains('pending', true)) {
      $todo[] = 'Fix the column mapping of the files marked in red (step 2).';
  }
  if ($blankLevels = array_keys(array_filter($options['level_ranks'], fn ($r) => $r === ''))) {
      $todo[] = 'Choose a rank for level(s): ' . implode(', ', $blankLevels) . '.';
  }
  if (in_array('attendance', $types, true)) {
      if (!$options['shift_id']) { $todo[] = $shifts->isEmpty() ? 'Create a shift in the resort portal (Time & Attendance), then choose it here.' : 'Choose the shift for migrated attendance.'; }
      if ($blankCodes = array_keys(array_filter($options['codes'], fn ($a) => $a === ''))) {
          $todo[] = ($categories->isEmpty() ? 'Create leave categories in the resort portal (Leave), then map' : 'Map') . ' attendance code(s): ' . implode(', ', $blankCodes) . '.';
      }
      if (!$options['period_start']) { $todo[] = 'Set the attendance period start date.'; }
  }

  $mappedOk = $files && !collect($files)->contains(fn ($f) => !empty($f['pending']) || !empty($f['note']) || empty($f['mapping']));
  $validated = $report && ($report['mode'] ?? '') === 'dry-run' && empty($report['errors']) && $import->exists && $import->status === 'draft';
  // Right after an import (no new files yet) every step is complete.
  $finished = $shown->status === 'imported' && !$files;
  $steps = [
      ['Upload', $files || $finished],
      ['Check columns', $mappedOk || $finished],
      ['Options', ($files && !$todo) || $finished],
      ['Validate', $validated || $shown->status === 'imported'],
      ['Import', $shown->status === 'imported'],
  ];
  $current = collect($steps)->search(fn ($s) => !$s[1]);

  $templates = [
      'employee_details' => ['Employee ID', 'Date of birth', 'Phone', 'Employment type', 'Reporting manager ID', 'Basic salary', 'Salary currency', 'Payment mode',
                             'Bank name', 'Bank branch', 'Account holder', 'Account no', 'IBAN', 'SWIFT', 'Bank currency',
                             'Passport no', 'Visa no', 'Visa start', 'Visa expiry', 'Work permit no', 'Work permit expiry'],
      'holidays'         => ['Date', 'Holiday name'],
  ];
@endphp

@section('import-css')
<style>
  .rds-steps { display: flex; flex-wrap: wrap; gap: .5rem; margin: 0; padding: 0; list-style: none; }
  .rds-steps li { flex: 1 1 140px; display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; border-radius: .4rem; background: #f4f6f9; color: #6c757d; }
  .rds-steps li .n { width: 1.6rem; height: 1.6rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #dee2e6; font-weight: 600; flex: none; }
  .rds-steps li.done { color: #155724; background: #e8f5e9; } .rds-steps li.done .n { background: #28a745; color: #fff; }
  .rds-steps li.now { color: #004085; background: #e7f1ff; font-weight: 600; } .rds-steps li.now .n { background: #007bff; color: #fff; }
  .rds-sample th, .rds-sample td { white-space: nowrap; font-size: .8rem; }
  .rds-change { font-size: .85rem; } .rds-change .old { color: #a71d2a; text-decoration: line-through; } .rds-change .new { color: #155724; }
  .rds-stat { border: 1px solid #e9ecef; border-radius: .4rem; padding: .5rem .75rem; height: 100%; }
</style>
@endsection

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">

      {{-- Header + progress --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <h1 class="mb-0">{{ $resort->resort_name }} — Data Setup</h1>
          <a href="{{ route('admin.resort_data_setup.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> All resorts</a>
        </div>
        <div class="card-body">
          <ol class="rds-steps mb-3">
            @foreach($steps as $i => [$label, $done])
              <li class="{{ $done ? 'done' : ($i === $current ? 'now' : '') }}">
                <span class="n">@if($done)<i class="fas fa-check"></i>@else{{ $i + 1 }}@endif</span> {{ $label }}
              </li>
            @endforeach
          </ol>
          <p class="mb-0">In this resort now: <strong>{{ $existing['divisions'] }}</strong> divisions · <strong>{{ $existing['departments'] }}</strong> departments ·
            <strong>{{ $existing['positions'] }}</strong> positions · <strong>{{ $existing['employees'] }}</strong> employees.
            <span class="text-muted">Records are matched and updated, never duplicated — the same files can be imported again, and an import can be undone.</span></p>
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
        <div class="alert alert-{{ $shown->job_status === 'failed' || str_starts_with($shown->job_message, 'Undo refused') ? 'danger' : 'secondary' }}">{!! nl2br(e($shown->job_message)) !!}</div>
      @endif

      @if($credentials)
        <div class="card card-outline card-success">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title mb-0"><i class="fas fa-key"></i> New logins ({{ count($credentials) }})</h3>
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
            <p class="mb-2">Stored encrypted only until you clear them. Every account must change its password at first login. Placeholder emails are not real mailboxes — no email was sent.
              Casual and intern staff get no login, so they are not listed.</p>
            <div class="table-responsive" style="max-height: 320px;">
              <table class="table table-sm table-bordered mb-0">
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
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <h3 class="card-title mb-0">1. Upload files</h3>
          <button type="button" class="btn btn-link btn-sm p-0" data-toggle="collapse" data-target="#rds-help"><i class="far fa-question-circle"></i> Which files can I upload?</button>
        </div>
        <div class="card-body">
          <div class="collapse mb-3" id="rds-help">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-1">
                <thead><tr><th>File</th><th>What it needs</th><th></th></tr></thead>
                <tbody>
                  <tr><td>Divisions / Departments / Sections / Positions</td><td>Names (and the parent each belongs to). A Level column on positions sets their rank.</td><td></td></tr>
                  <tr><td>Levels</td><td>The list of grades — used to set level → rank below.</td><td></td></tr>
                  <tr><td>Staff list</td><td>Employee ID, name, position, level, department (column or group rows). Optional: hire date, gender, country, religion, email, employment type (Casual/Intern get their own positions and no login).</td><td></td></tr>
                  <tr><td>Employee details</td><td>Employee ID plus any of: date of birth, phone, employment type, reporting manager ID, salary &amp; currency, payment mode, bank details, passport, visa &amp; work permit. Updates staff that already exist.</td>
                    <td><button type="button" class="btn btn-outline-secondary btn-sm js-template" data-name="employee-details" data-cols="{{ json_encode($templates['employee_details']) }}"><i class="fas fa-download"></i> Template</button></td></tr>
                  <tr><td>Public holidays</td><td>Date and name.</td>
                    <td><button type="button" class="btn btn-outline-secondary btn-sm js-template" data-name="public-holidays" data-cols="{{ json_encode($templates['holidays']) }}"><i class="fas fa-download"></i> Template</button></td></tr>
                  <tr><td>Attendance</td><td>One row per employee, one column per day, a code per cell (AL, DO, …).</td><td></td></tr>
                </tbody>
              </table>
            </div>
            <small class="text-muted">Any column order and titles work — the layout is detected. Leave opening balances and past budgets are not imported here.</small>
          </div>
          @if($shown->status === 'imported' && !$files)
            <p class="text-success"><i class="fas fa-check-circle"></i> Last import finished {{ optional($shown->imported_at)->format('d M Y H:i') }}.
              Uploading again starts a new batch with the same options.</p>
          @endif
          <form method="post" action="{{ route('admin.resort_data_setup.upload', $resort->id) }}" enctype="multipart/form-data" class="form-inline flex-wrap">
            @csrf
            <input type="file" name="files[]" multiple required accept=".xls,.xlsx,.csv" class="form-control-file mr-2 mb-2" style="max-width: 100%;">
            <button type="submit" class="btn btn-primary mb-2" @disabled($busy)><i class="fas fa-upload"></i> Upload &amp; detect</button>
          </form>
          <small class="text-muted">.xls, .xlsx or .csv · up to 20 MB · 5,000 rows per file. A new layout costs a few hundred AI tokens once; known layouts are free. A file with the same name replaces the earlier one.</small>
        </div>
      </div>

      {{-- 2. Files --}}
      @if($files)
        <div class="card">
          <div class="card-header"><h3 class="card-title">2. Check each file</h3></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead><tr><th>File</th><th>Recognised as</th><th>Records</th><th>Detected by</th><th>Status</th><th style="width: 230px;">Action</th></tr></thead>
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
                          @case('cache') <span class="badge badge-success">Saved layout · free</span> @break
                          @case('manual') <span class="badge badge-primary">Set by hand</span> @break
                          @default <span class="badge badge-secondary">Not mapped</span>
                        @endswitch
                      </td>
                      <td>
                        @if(!empty($file['pending']))
                          <span class="text-info"><i class="fas fa-spinner fa-spin"></i> Reading…</span>
                        @elseif(!empty($file['note']))
                          <span class="text-danger"><i class="fas fa-exclamation-circle"></i> {{ $file['note'] }}</span>
                        @elseif($mapping)
                          <span class="text-success"><i class="fas fa-check"></i> Ready</span>
                        @endif
                      </td>
                      <td>
                        @if(!empty($file['sample']))
                          <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="collapse" data-target="#sample-{{ $file['id'] }}"><i class="far fa-eye"></i> Preview</button>
                        @endif
                        <button type="button" class="btn btn-info btn-sm" data-toggle="collapse" data-target="#map-{{ $file['id'] }}"><i class="fas fa-columns"></i> Columns</button>
                        <form method="post" action="{{ route('admin.resort_data_setup.remove', [$resort->id, $file['id']]) }}" class="d-inline" onsubmit="return confirm('Remove this file?');">
                          @csrf
                          <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove" @disabled($busy)><i class="fas fa-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                    @if(!empty($file['sample']))
                      @php($cols = collect($file['sample'])->flatMap(fn ($r) => array_keys($r))->unique()->values())
                      <tr class="collapse" id="sample-{{ $file['id'] }}">
                        <td colspan="6" class="bg-light">
                          <small class="text-muted d-block mb-1">First {{ count($file['sample']) }} of {{ $file['count'] }} records as the import will read them:</small>
                          <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white rds-sample mb-0">
                              <thead><tr>@foreach($cols as $c)<th>{{ $c === 'group' ? 'Group row' : str_replace('_', ' ', ucfirst($c)) }}</th>@endforeach</tr></thead>
                              <tbody>
                                @foreach($file['sample'] as $r)
                                  <tr>@foreach($cols as $c)<td>{{ $r[$c] ?? '' }}</td>@endforeach</tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>
                        </td>
                      </tr>
                    @endif
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
                              <label>Header row <small class="text-muted">(the row with the column titles)</small></label>
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
                          <button type="submit" class="btn btn-primary btn-sm" @disabled($busy)>Save columns</button>
                          <small class="text-muted ml-2">Saved layouts are reused for every later file with the same column titles.</small>
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
              @if($todo)
                <div class="alert alert-warning">
                  <strong><i class="fas fa-list-ul"></i> Still to do before validating:</strong>
                  <ul class="mb-0 mt-1">@foreach($todo as $t)<li>{{ $t }}</li>@endforeach</ul>
                </div>
              @else
                <div class="alert alert-success py-2 mb-3"><i class="fas fa-check"></i> All options are set.</div>
              @endif

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Placeholder login email domain</label>
                  <input type="text" name="email_domain" class="form-control" value="{{ $options['email_domain'] }}" required>
                  <small class="text-muted">New staff without an email get <code>&lt;Employee ID&gt;&#64;{{ $options['email_domain'] }}</code>. Must be unique to this resort.</small>
                </div>
              </div>

              @if($options['level_ranks'])
                <h5 class="mt-2">Level &rarr; rank</h5>
                <p class="text-muted mb-2">An EXCOM-level employee whose position is "General Manager" becomes GM automatically.</p>
                <div class="form-row">
                  @foreach($options['level_ranks'] as $level => $rank)
                    <div class="form-group col-md-3">
                      <label>{{ $level }}</label>
                      <select name="level_ranks[{{ $level }}]" class="form-control {{ $rank === '' ? 'is-invalid' : '' }}">
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
                <p class="text-muted mb-2">EXCOM, HOD and Manager level staff in these departments get the HR / Finance / Clinic role. The HR department's EXCOM position also gets full page access.</p>
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
                    <input type="date" name="period_start" class="form-control {{ $options['period_start'] ? '' : 'is-invalid' }}" value="{{ $options['period_start'] }}">
                  </div>
                  <div class="form-group col-md-5">
                    <label>Shift for migrated days</label>
                    <select name="shift_id" class="form-control {{ $options['shift_id'] ? '' : 'is-invalid' }}">
                      <option value="">Choose…</option>
                      @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected((string) $options['shift_id'] === (string) $shift->id)>{{ $shift->ShiftName }} ({{ $shift->StartTime }}–{{ $shift->EndTime }})</option>
                      @endforeach
                    </select>
                    @if($shifts->isEmpty())
                      <small class="text-danger">No shifts yet — create one in the resort portal (Time &amp; Attendance) first.</small>
                    @else
                      <small class="text-muted">Present days are stored as 8 hours from the shift start.</small>
                    @endif
                  </div>
                </div>
                <div class="form-row">
                  @foreach($options['codes'] as $code => $action)
                    <div class="form-group col-md-3">
                      <label>Code <strong>{{ $code }}</strong></label>
                      <select name="codes[{{ $code }}]" class="form-control {{ $action === '' ? 'is-invalid' : '' }}">
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
                  <small class="text-danger">No leave categories yet — create them in the resort portal (Leave configuration), then map the leave codes.</small>
                @else
                  <small class="text-muted">Leave codes become approved leave records (they count against leave balances), matching how payroll reads leave.</small>
                @endif
              @endif
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" @disabled($busy)><i class="fas fa-save"></i> Save options</button></div>
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
                  onsubmit="return confirm('Import into {{ addslashes($resort->resort_name) }}? This writes to the live tables (it can be undone afterwards).');">
              @csrf
              <button type="submit" class="btn btn-success" @disabled(!$canImport)><i class="fas fa-file-import"></i> Import</button>
            </form>
            <p class="text-muted mt-2 mb-0">
              @if($canImport)
                <i class="fas fa-check text-success"></i> Validation passed — review "What will change" below, then import. It runs the same steps and saves them, all or nothing.
              @else
                Validation is a full dry run: nothing is saved. Import unlocks once it passes with no errors; any change to files or options needs a new validation.
              @endif
            </p>
          </div>
        </div>
      @endif

      {{-- Report --}}
      @if($report && isset($report['counts']))
        @php($changes = $report['changes'] ?? [])
        @php($missing = $report['missing'] ?? [])
        <div class="card card-outline {{ empty($report['errors']) ? 'card-success' : 'card-danger' }}">
          <div class="card-header">
            <h3 class="card-title">
              {{ $report['mode'] === 'import' ? ($report['committed'] ? 'Import result' : 'Import failed — nothing saved') : 'Validation result — nothing saved' }}
              <small class="text-muted">{{ $report['at'] }}</small>
            </h3>
          </div>
          <div class="card-body">
            <div class="row">
              @foreach($report['counts'] as $type => $counts)
                <div class="col-sm-6 col-md-4 col-xl-2 mb-2">
                  <div class="rds-stat">
                    <strong>{{ $typeLabels[$type] ?? ucfirst($type) }}</strong>
                    <ul class="list-unstyled mb-0 small">
                      @foreach($counts as $what => $n)
                        <li>{{ ucfirst($what) }}: <strong>{{ number_format($n) }}</strong></li>
                      @endforeach
                    </ul>
                  </div>
                </div>
              @endforeach
            </div>

            <ul class="nav nav-tabs mt-3" role="tablist">
              <li class="nav-item"><a class="nav-link {{ $report['errors'] ? 'active' : '' }}" data-toggle="tab" href="#tab-errors">Errors <span class="badge badge-{{ $report['errors'] ? 'danger' : 'secondary' }}">{{ count($report['errors']) }}</span></a></li>
              <li class="nav-item"><a class="nav-link {{ $report['errors'] ? '' : 'active' }}" data-toggle="tab" href="#tab-changes">{{ $report['mode'] === 'import' ? 'What changed' : 'What will change' }} <span class="badge badge-info">{{ count($changes) }}</span></a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-missing">Not in staff file <span class="badge badge-secondary">{{ count($missing) }}</span></a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-warnings">Warnings <span class="badge badge-warning">{{ count($report['warnings']) }}</span></a></li>
            </ul>
            <div class="tab-content border border-top-0 p-3">
              <div class="tab-pane fade {{ $report['errors'] ? 'show active' : '' }}" id="tab-errors">
                @if($report['errors'])
                  <p class="text-danger mb-2">These must be fixed before importing.</p>
                  <div class="table-responsive" style="max-height: 360px;">
                    <table class="table table-sm table-bordered mb-0">
                      <thead><tr><th>File</th><th>Row</th><th>Problem</th></tr></thead>
                      <tbody>
                        @foreach(array_slice($report['errors'], 0, 300) as $e)
                          <tr><td>{{ $e['file'] ?? '—' }}</td><td>{{ $e['row'] ?? '—' }}</td><td>{{ $e['message'] }}</td></tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @else
                  <p class="text-success mb-0"><i class="fas fa-check"></i> No errors.</p>
                @endif
              </div>
              <div class="tab-pane fade {{ $report['errors'] ? '' : 'show active' }}" id="tab-changes">
                @if($changes)
                  <p class="text-muted mb-2">Existing records whose values {{ $report['mode'] === 'import' ? 'were' : 'will be' }} changed. New records are counted above; unchanged ones are left alone.</p>
                  <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-sm table-bordered mb-0 rds-change">
                      <thead><tr><th>Type</th><th>Record</th><th>Change</th></tr></thead>
                      <tbody>
                        @foreach($changes as $c)
                          <tr>
                            <td>{{ $kindLabels[$c['kind']] ?? ucfirst($c['kind']) }}</td>
                            <td>{{ $c['label'] }}</td>
                            <td>
                              @foreach($c['changes'] as $field => [$old, $new])
                                <div>{{ $fieldLabels[$field] ?? str_replace('_', ' ', ucfirst($field)) }}:
                                  <span class="old">{{ $showValue($field, $old) }}</span> &rarr; <span class="new">{{ $showValue($field, $new) }}</span></div>
                              @endforeach
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @else
                  <p class="mb-0 text-muted">No existing record changes — only new records{{ ($report['counts']['attendance']['days changed'] ?? 0) ? ' and the attendance days counted above' : '' }}.</p>
                @endif
              </div>
              <div class="tab-pane fade" id="tab-missing">
                @if($missing)
                  <p class="text-muted mb-2">Active employees in this resort who are not in the staff file. Nothing is done to them — mark leavers in the portal.</p>
                  <ul class="mb-0 small" style="columns: 3;">@foreach($missing as $m)<li>{{ $m }}</li>@endforeach</ul>
                @else
                  <p class="mb-0 text-muted">Everyone in the resort is in the staff file (or no staff file was imported).</p>
                @endif
              </div>
              <div class="tab-pane fade" id="tab-warnings">
                @if($report['warnings'])
                  <ul class="small mb-0">
                    @foreach($report['warnings'] as $message => $n)
                      <li>{{ $message }} @if($n > 1)<span class="badge badge-secondary">× {{ $n }}</span>@endif</li>
                    @endforeach
                  </ul>
                @else
                  <p class="mb-0 text-muted">No warnings.</p>
                @endif
              </div>
            </div>

            @if(!empty($report['undo']['conflicts']))
              <div class="alert alert-warning mt-3 mb-0">
                <strong>Kept during undo</strong> — changed after the import, so left as they are now:
                <ul class="mb-0 small">@foreach(array_slice($report['undo']['conflicts'], 0, 50) as $c)<li>{{ $c }}</li>@endforeach</ul>
              </div>
            @endif
          </div>
        </div>
      @endif

      {{-- History + undo --}}
      @if($batches->isNotEmpty())
        @php($latestImported = $batches->firstWhere('status', 'imported'))
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-history"></i> Import history</h3></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Imported</th><th>Status</th><th>Summary</th><th style="width: 120px;"></th></tr></thead>
                <tbody>
                  @foreach($batches as $b)
                    <tr>
                      <td>{{ $b->id }}</td>
                      <td>{{ optional($b->imported_at)->format('d M Y H:i') ?? '—' }}</td>
                      <td>
                        @if($b->status === 'imported')<span class="badge badge-success">Imported</span>
                        @elseif($b->status === 'undone')<span class="badge badge-secondary">Undone</span>
                        @else<span class="badge badge-light">{{ ucfirst($b->status) }}</span>@endif
                      </td>
                      <td class="small">
                        {{ collect($b->report['counts'] ?? [])->map(fn ($c, $t) => ($typeLabels[$t] ?? $t) . ': ' . collect($c)->map(fn ($n, $w) => "$n $w")->implode(', '))->implode(' · ') }}
                      </td>
                      <td>
                        @if($latestImported && $b->id === $latestImported->id)
                          <form method="post" action="{{ route('admin.resort_data_setup.undo', [$resort->id, $b->id]) }}"
                                onsubmit="return confirm('Undo this import? Everything it created is removed and every value it changed is put back. Values changed since then are kept.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm" @disabled($busy)><i class="fas fa-undo"></i> Undo</button>
                          </form>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">Only the most recent import can be undone, and only before imported staff start using the system (logging in, applying leave, punching in).</div>
        </div>
      @endif

      {{-- Record lookup --}}
      @if($batches->isNotEmpty() || $lookup !== '')
        <div class="card">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-search"></i> Record history</h3></div>
          <div class="card-body">
            <form method="get" action="{{ route('admin.resort_data_setup.show', $resort->id) }}" class="form-inline mb-3">
              <input type="text" name="lookup" value="{{ $lookup }}" class="form-control mr-2 mb-2" placeholder="Employee ID, name, position…" style="min-width: 260px;">
              <button type="submit" class="btn btn-secondary mb-2">Search</button>
              @if($lookup !== '')<a href="{{ route('admin.resort_data_setup.show', $resort->id) }}" class="btn btn-link mb-2">Clear</a>@endif
            </form>
            @if($lookup !== '')
              @if($history->isEmpty())
                <p class="text-muted mb-0">No import touched a record matching "{{ $lookup }}".</p>
              @else
                <div class="table-responsive" style="max-height: 420px;">
                  <table class="table table-sm table-bordered mb-0 rds-change">
                    <thead><tr><th>When</th><th>Record</th><th>What happened</th><th>Source</th></tr></thead>
                    <tbody>
                      @foreach($history as $h)
                        @php($data = json_decode($h->data ?? 'null', true))
                        <tr>
                          <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d M Y H:i') }}@if($h->batch_status === 'undone') <span class="badge badge-secondary">undone</span>@endif</td>
                          <td>{{ $h->label }}</td>
                          <td>
                            @if($h->action === 'created') Created ({{ str_replace('_', ' ', $h->table_name) }})
                            @elseif($h->action === 'deleted') Removed ({{ str_replace('_', ' ', $h->table_name) }})
                            @else
                              @foreach((array) $data as $field => [$old, $new])
                                <div>{{ $fieldLabels[$field] ?? str_replace('_', ' ', ucfirst($field)) }}: <span class="old">{{ $showValue($field, $old) }}</span> &rarr; <span class="new">{{ $showValue($field, $new) }}</span></div>
                              @endforeach
                            @endif
                          </td>
                          <td class="small">{{ $h->source_file ?? '—' }}@if($h->source_row), row {{ $h->source_row }}@endif</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            @else
              <p class="text-muted mb-0">Find which file and row created or changed any record, with its old and new values.</p>
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

    var csvDownload = function (name, rows) {
      var esc = function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; };
      var a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob([rows.map(function (r) { return r.map(esc).join(','); }).join('\r\n')], { type: 'text/csv' }));
      a.download = name;
      a.click();
    };
    $('.js-template').on('click', function () { csvDownload($(this).data('name') + '-template.csv', [$(this).data('cols')]); });

    var credentials = @json($credentials);
    $('#download-credentials').on('click', function () {
      csvDownload(@json(\Illuminate\Support\Str::slug($resort->resort_name) . '-logins.csv'),
        [['Employee ID', 'Name', 'Login email', 'Temporary password']].concat(credentials.map(function (c) { return [c.emp_id, c.name, c.email, c.password]; })));
    });
  });
</script>
@endsection
