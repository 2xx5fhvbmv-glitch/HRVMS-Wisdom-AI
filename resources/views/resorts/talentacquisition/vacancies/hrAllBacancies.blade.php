@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <style>
        #ta-vacancies-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ta-vacancies-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="ta-vacancies-hero">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div class="card">
                <div class="card-title">
                    <h3>All Vacancies</h3>
                </div>
                <table class="table"  id="Vacancies" >
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Position Title</th>
                            <th>Employee Type</th>
                            <th>Required Starting</th>
                            <th>Budget</th>
                            <th>ReportingTo</th>
                            <th>Rank</th>
                            <th>Status</th>
                            @if(!empty($casualInternOnly))
                            <th>Action</th>
                            @endif

                        </tr>
                    </thead>


                </table>

            </div>


        </div>
    </div>
    <div class="modal fade" id="respond-rejectModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" rows="7" placeholder="Reason for Rejection"></textarea>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                <a href="#" class="btn ta-btn-primary">Submit</a>
            </div>

        </div>
    </div>
</div>

    @if(!empty($casualInternOnly))
    {{-- §23 — Mark as Recruited/Hired. Reuses ServiceProvider's existing
         vendor picker (talentacquisition/vacancies/create.blade.php:250-260)
         and creates the Employee the same way
         OfflineInterviewController::convertToEmployee() does. --}}
    <div class="modal fade" id="markRecruitedModal" tabindex="-1" aria-labelledby="markRecruitedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markRecruitedModalLabel">Mark as Recruited</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="markRecruitedForm">
                    <div class="modal-body">
                        <div id="markRecruitedAlert" class="alert alert-danger" style="display:none;"></div>

                        <div class="mb-3">
                            <label class="form-label">Department / Position</label>
                            <input type="text" class="form-control" id="mr_dept_position" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" id="mr_employee_type" disabled>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="mr_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="mr_last_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Passport number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="passport_number" id="mr_passport_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nationality <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nationality" id="mr_nationality" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reporting manager <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="reporting_to" id="mr_reporting_to" required>
                                <option value="">Select manager</option>
                            </select>
                        </div>

                        <div class="mb-3" id="mr_college_wrap" style="display:none;">
                            <label class="form-label">College / Institute name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="college_institute_name" id="mr_college_institute_name">
                        </div>

                        <div class="mb-3" id="mr_vendor_wrap" style="display:none;">
                            <label class="form-label">Vendor / Service provider</label>
                            <select class="form-control form-select" name="service_provider" id="mr_service_provider">
                                <option value="">Select vendor</option>
                            </select>
                            <input type="text" class="form-control mt-2" name="new_service_provider" id="mr_new_service_provider" placeholder="Or type a new vendor name">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary" id="mr_submit_btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endsection

@section('import-css')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {

    $('#Vacancies tbody').empty();
        var divisionTable = $('#Vacancies').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order:[[7, 'desc']],
        ajax: {
            url: '{{ route("resort.ta.GetAllVacancies") }}',
            type: 'GET',
            data: function(d) {
                d.casual_intern_only = {{ !empty($casualInternOnly) ? 'true' : 'false' }};
            },
        },
        columns: [
            { data: 'Department', name: 'Department', className: 'text-nowrap' },
            { data: 'Position', name: 'Position', className: 'text-nowrap' },
            {data:'EmployeeType',name:'EmployeeType',className:'text-nowrap'},
        { data: 'Required', name: 'Question', className: 'text-nowrap' },
            { data: 'Budget', name: 'action', className: 'text-nowrap'},
            { data: 'ReportingTo', name: 'ReportingTo', className: 'text-nowrap'},
            { data: 'rank_name', name: 'rank_name', className: 'text-nowrap'},
            { data: 'approval_status', name: 'approval_status', className: 'text-nowrap'},
            @if(!empty($casualInternOnly))
            { data: 'recruit_action', name: 'recruit_action', className: 'text-nowrap', orderable: false, searchable: false },
            @endif
            {data:'created_at', visible:false,searchable:false},
        ]
    });

    @if(!empty($casualInternOnly))
    // §23 — Mark as Recruited/Hired
    var mrVacancyId = null;
    var mrOptionsUrlTpl = '{{ route("resort.vacancies.recruitFormOptions", ["id" => "__ID__"]) }}';
    var mrSubmitUrlTpl = '{{ route("resort.vacancies.markRecruited", ["id" => "__ID__"]) }}';

    $('#Vacancies').on('click', '.mark-recruited-btn', function() {
        mrVacancyId = $(this).data('vacancy-id');
        $('#markRecruitedForm')[0].reset();
        $('#markRecruitedAlert').hide();
        $('#mr_reporting_to').html('<option value="">Loading…</option>');
        $('#mr_service_provider').html('<option value="">Loading…</option>');

        $.get(mrOptionsUrlTpl.replace('__ID__', mrVacancyId), function(res) {
            if (!res.success) {
                alert(res.message || 'Could not load recruit form.');
                return;
            }
            $('#mr_dept_position').val(res.vacancy.department_name + ' — ' + res.vacancy.position_title);
            $('#mr_employee_type').val(res.vacancy.employee_type);
            $('#mr_college_wrap').toggle(!!res.vacancy.is_intern);
            $('#mr_vendor_wrap').toggle(!res.vacancy.is_intern);

            var $mgr = $('#mr_reporting_to').empty().append('<option value="">Select manager</option>');
            (res.reporting_managers || []).forEach(function(m) {
                var name = (m.first_name || '') + ' ' + (m.last_name || '');
                var selected = (String(m.id) === String(res.vacancy.reporting_to)) ? 'selected' : '';
                $mgr.append('<option value="' + m.id + '" ' + selected + '>' + name.trim() + '</option>');
            });

            var $vendor = $('#mr_service_provider').empty().append('<option value="">Select vendor</option>');
            (res.service_providers || []).forEach(function(name) {
                var selected = (name === res.vacancy.service_provider_name) ? 'selected' : '';
                $vendor.append('<option value="' + name + '" ' + selected + '>' + name + '</option>');
            });

            $('#markRecruitedModal').modal('show');
        }).fail(function(xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Could not load recruit form.');
        });
    });

    $('#markRecruitedForm').on('submit', function(e) {
        e.preventDefault();
        $('#markRecruitedAlert').hide();
        $('#mr_submit_btn').prop('disabled', true).text('Submitting…');

        $.ajax({
            url: mrSubmitUrlTpl.replace('__ID__', mrVacancyId),
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                $('#markRecruitedModal').modal('hide');
                divisionTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if (xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).map(function(a){ return a[0]; }).join(' ');
                    }
                }
                $('#markRecruitedAlert').text(msg).show();
            },
            complete: function() {
                $('#mr_submit_btn').prop('disabled', false).text('Submit');
            }
        });
    });
    @endif
});
</script>
@endsection

