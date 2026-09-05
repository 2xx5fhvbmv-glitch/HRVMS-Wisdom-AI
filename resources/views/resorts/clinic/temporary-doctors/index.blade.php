@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<style>
    #clinic-temporary-doctors-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #clinic-temporary-doctors-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="clinic-temporary-doctors-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Clinic</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="javascript:void(0);" class="btn btn-theme" id="btn-new-doctor">Add Temporary Doctor</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">
                <h3>Third-Party / Agency Doctors</h3>
                <p class="mb-0 text-muted">Mobile-app-only accounts for external clinic staff. Not employees — they never appear in payroll, headcount, or benefit reports.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-lable mb-1">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Agency</th>
                            <th>Capabilities</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $doctor)
                            <tr data-id="{{ $doctor->id }}">
                                <td class="td-name">{{ $doctor->name }}</td>
                                <td>{{ $doctor->email }}</td>
                                <td class="td-agency">{{ $doctor->agency_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $caps = [];
                                        if ($doctor->can_view_appointments) $caps[] = 'View Appointments';
                                        if ($doctor->can_manage_treatment) $caps[] = 'Manage Treatment';
                                        if ($doctor->can_view_medical_history) $caps[] = 'View Medical History';
                                        if ($doctor->can_issue_medical_certificate) $caps[] = 'Issue Medical Certificate';
                                    @endphp
                                    <span class="text-muted">{{ $caps ? implode(', ', $caps) : 'None granted' }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $doctor->status === 'Active' ? 'badge-themeSuccess' : 'badge-themeDanger' }} badge-status">{{ $doctor->status }}</span>
                                </td>
                                <td class="td-expires">{{ $doctor->expires_at ? $doctor->expires_at->format('d M Y') : 'No expiry' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="javascript:void(0);" class="a-link btn-edit"
                                           data-id="{{ $doctor->id }}"
                                           data-name="{{ $doctor->name }}"
                                           data-contact_no="{{ $doctor->contact_no }}"
                                           data-agency_name="{{ $doctor->agency_name }}"
                                           data-expires_at="{{ $doctor->expires_at ? $doctor->expires_at->format('Y-m-d') : '' }}"
                                           data-can_view_appointments="{{ $doctor->can_view_appointments ? 1 : 0 }}"
                                           data-can_manage_treatment="{{ $doctor->can_manage_treatment ? 1 : 0 }}"
                                           data-can_view_medical_history="{{ $doctor->can_view_medical_history ? 1 : 0 }}"
                                           data-can_issue_medical_certificate="{{ $doctor->can_issue_medical_certificate ? 1 : 0 }}">Edit</a>
                                        <a href="javascript:void(0);" class="a-link btn-reset-password" data-id="{{ $doctor->id }}">Reset Password</a>
                                        <a href="javascript:void(0);" class="a-link btn-revoke-sessions" data-id="{{ $doctor->id }}">Sign Out Everywhere</a>
                                        <a href="javascript:void(0);" class="a-link btn-toggle-status" data-id="{{ $doctor->id }}">{{ $doctor->status === 'Active' ? 'Deactivate' : 'Reactivate' }}</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No temporary doctor accounts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="doctor-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="doctor-modal-title">Add Temporary Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="doctorForm">
                    <input type="hidden" name="id" id="doctor_id">
                    <div class="row g-md-4 g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="name" id="doctor_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="red-mark">*</span></label>
                            <input type="email" class="form-control" name="email" id="doctor_email" required>
                            <small class="text-muted">Login credentials are emailed here. Cannot be changed after creation.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact No</label>
                            <input type="text" class="form-control" name="contact_no" id="doctor_contact_no">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agency / Hospital Name</label>
                            <input type="text" class="form-control" name="agency_name" id="doctor_agency_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Access Expires On</label>
                            <input type="date" class="form-control" name="expires_at" id="doctor_expires_at">
                            <small class="text-muted">Leave blank for no expiry.</small>
                        </div>
                    </div>

                    <div class="mt-lg-4 mt-3">
                        <label class="form-label d-block mb-2">What can this account do in the Clinic module?</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="can_view_appointments" id="cap_view_appointments" value="1">
                                    <label class="form-check-label" for="cap_view_appointments">View Appointments</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="can_manage_treatment" id="cap_manage_treatment" value="1">
                                    <label class="form-check-label" for="cap_manage_treatment">Manage Treatment</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="can_view_medical_history" id="cap_view_medical_history" value="1">
                                    <label class="form-check-label" for="cap_view_medical_history">View Medical History</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="can_issue_medical_certificate" id="cap_issue_medical_certificate" value="1">
                                    <label class="form-check-label" for="cap_issue_medical_certificate">Issue Medical Certificate</label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Appointment-category setup and leave-approval actions are never available to this account type.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeGray" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-themeBlue" id="doctor-save-btn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    var modal = new bootstrap.Modal(document.getElementById('doctor-modal'));

    function resetForm() {
        $('#doctorForm')[0].reset();
        $('#doctor_id').val('');
        $('#doctor_email').prop('disabled', false);
        $('#doctor-modal-title').text('Add Temporary Doctor');
    }

    $('#btn-new-doctor').on('click', function () {
        resetForm();
        modal.show();
    });

    $('.btn-edit').on('click', function () {
        resetForm();
        var d = $(this).data();
        $('#doctor-modal-title').text('Edit Temporary Doctor');
        $('#doctor_id').val(d.id);
        $('#doctor_name').val(d.name);
        $('#doctor_contact_no').val(d.contact_no);
        $('#doctor_agency_name').val(d.agency_name);
        $('#doctor_expires_at').val(d.expires_at);
        $('#doctor_email').prop('disabled', true);
        $('#cap_view_appointments').prop('checked', !!parseInt(d.can_view_appointments));
        $('#cap_manage_treatment').prop('checked', !!parseInt(d.can_manage_treatment));
        $('#cap_view_medical_history').prop('checked', !!parseInt(d.can_view_medical_history));
        $('#cap_issue_medical_certificate').prop('checked', !!parseInt(d.can_issue_medical_certificate));
        modal.show();
    });

    $('#doctor-save-btn').on('click', function () {
        var id = $('#doctor_id').val();
        var url = id
            ? "{{ url('resort/clinic/temporary-doctors') }}/" + id + "/update"
            : "{{ route('resort.clinic.temporary-doctors.store') }}";

        $.ajax({
            url: url,
            type: 'POST',
            data: $('#doctorForm').serialize(),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    modal.hide();
                    window.location.reload();
                } else {
                    toastr.error(res.message || 'Something went wrong.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var errors = xhr.responseJSON && xhr.responseJSON.errors;
                var msg = errors ? Object.values(errors)[0][0] : 'Something went wrong.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.btn-reset-password', function () {
        if (!confirm('Generate a new password and email it to this doctor? Any device already logged in will be signed out.')) return;
        var id = $(this).data('id');
        $.post("{{ url('resort/clinic/temporary-doctors') }}/" + id + "/reset-password", { _token: '{{ csrf_token() }}' }, function (res) {
            toastr[res.success ? 'success' : 'error'](res.message, res.success ? 'Success' : 'Error', { positionClass: 'toast-bottom-right' });
        });
    });

    $(document).on('click', '.btn-revoke-sessions', function () {
        if (!confirm('Sign this doctor out of every device immediately?')) return;
        var id = $(this).data('id');
        $.post("{{ url('resort/clinic/temporary-doctors') }}/" + id + "/revoke-sessions", { _token: '{{ csrf_token() }}' }, function (res) {
            toastr[res.success ? 'success' : 'error'](res.message, res.success ? 'Success' : 'Error', { positionClass: 'toast-bottom-right' });
        });
    });

    $(document).on('click', '.btn-toggle-status', function () {
        var id = $(this).data('id');
        $.post("{{ url('resort/clinic/temporary-doctors') }}/" + id + "/toggle-status", { _token: '{{ csrf_token() }}' }, function (res) {
            if (res.success) {
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                window.location.reload();
            } else {
                toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
});
</script>
@endsection
