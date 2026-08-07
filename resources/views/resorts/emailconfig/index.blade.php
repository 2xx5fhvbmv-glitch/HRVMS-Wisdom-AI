@extends('resorts.layouts.app')
@section('page_tab_title', 'Email Config')

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Pages</span>
                        <h1>Email Config</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col">
                        <div class="card-title border-0 p-0 m-0">
                            <h3>SMTP Settings</h3>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('resort.Page.Permission') }}" target="_blank" class="btn btn-themeSkyblue btn-sm">PAGE PERMISSION</a>
                    </div>
                </div>
            </div>
            <p class="text-muted px-3 mb-0">Configure this resort's own SMTP account. Once saved, every module (Leave, Payroll, Talent Acquisition, Incidents, etc.) sends email through it instead of the system default.</p>

            <form method="POST" id="emailConfigForm" class="form-horizontal">
                @csrf
                <div class="row g-md-4 g-3 mb-4 mt-1">
                    <div class="col-sm-6">
                        <label for="host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="host" id="host" placeholder="e.g. smtp.yourdomain.com" value="{{ $config->host ?? '' }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="port" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="port" id="port" placeholder="e.g. 587" value="{{ $config->port ?? 587 }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="username" class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" name="username" id="username" value="{{ $config->username ?? '' }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="password" class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="{{ $config ? 'Leave blank to keep the current password' : '' }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="encryption" class="form-label">Encryption</label>
                        <select class="form-select" name="encryption" id="encryption">
                            <option value="" {{ empty($config->encryption ?? null) ? 'selected' : '' }}>None</option>
                            <option value="tls" {{ ($config->encryption ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($config->encryption ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                    <div class="col-sm-6"></div>
                    <div class="col-sm-6">
                        <label for="from_address" class="form-label">From Address</label>
                        <input type="email" class="form-control" name="from_address" id="from_address" value="{{ $config->from_address ?? '' }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" name="from_name" id="from_name" value="{{ $config->from_name ?? '' }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3 px-3 pb-3">
                    <button type="submit" class="btn btn-theme">Save</button>
                </div>
            </form>

            <hr class="mx-3">

            <div class="px-3 pb-4">
                <h5>Send Test Email</h5>
                <p class="text-muted">Save your configuration above, then send a test email to confirm it works.</p>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-6">
                        <label for="test_email" class="form-label">Recipient Address</label>
                        <input type="email" class="form-control" id="test_email" placeholder="you@example.com">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-themeSkyblue" id="sendTestEmailBtn">Send Test Email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-scripts')
<script type="text/javascript">
    $('#emailConfigForm').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) return false;
        $submitBtn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('resort.emailconfig.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    $('#password').val('').attr('placeholder', 'Leave blank to keep the current password');
                } else {
                    toastr.error(response.message || "Save failed.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr) {
                var errs = '';
                if (xhr.status === 400 && xhr.responseJSON) {
                    $.each(xhr.responseJSON, function(key, value) {
                        errs += value[0] + '<br>';
                    });
                } else {
                    errs = 'An unexpected error occurred. Please try again.';
                }
                toastr.error(errs, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Save');
            }
        });
    });

    $('#sendTestEmailBtn').on('click', function() {
        var $btn = $(this);
        var testEmail = $('#test_email').val();
        if (!testEmail) {
            toastr.error("Please enter a recipient address.", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: "{{ route('resort.emailconfig.test') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                test_email: testEmail
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message || "Test email failed.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'An unexpected error occurred. Please try again.';
                toastr.error(msg, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).text('Send Test Email');
            }
        });
    });
</script>
@endsection
