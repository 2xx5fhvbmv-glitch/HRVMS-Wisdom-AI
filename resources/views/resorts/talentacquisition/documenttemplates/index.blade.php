@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('resort.ta.configration') }}" class="btn btn-theme">Back to Configuration</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upload Section --}}
        <div class="card mb-30">
            <div class="card-title">
                <div class="row g-3 align-items-center justify-content-between">
                    <div class="col-auto">
                        <h3>Upload New Template</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ asset('sample/document_filed.docx') }}" download class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-download me-1"></i> Download Sample DOCX
                        </a>
                    </div>
                </div>
            </div>
            <form id="uploadTemplateForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="row g-3 mb-3">
                    <div class="col-lg-4">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Standard Offer Letter" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Upload DOCX File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="template_file" accept=".docx" required>
                    </div>
                    <div class="col-lg-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-themeSkyblue">
                            <i class="fa-solid fa-upload me-1"></i> Upload Template
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <details>
                            <summary class="text-primary" style="cursor:pointer;font-size:13px;">View Available Placeholders</summary>
                            <div style="padding:8px;background:#f8f9fa;border-radius:6px;margin-top:6px;font-size:12px;">
                                <strong>Personal:</strong> <code>@{{candidate_full_name}}</code> <code>@{{candidate_first_name}}</code> <code>@{{candidate_last_name}}</code> <code>@{{candidate_gender}}</code> <code>@{{candidate_date_of_birth}}</code> <code>@{{candidate_nationality}}</code> <code>@{{candidate_passport_number}}</code> <code>@{{candidate_address}}</code> <code>@{{candidate_email}}</code> <code>@{{candidate_phone_number}}</code><br>
                                <strong>Job:</strong> <code>@{{job_title}}</code> <code>@{{department_name}}</code> <code>@{{reporting_manager_name}}</code> <code>@{{reporting_manager_title}}</code> <code>@{{employment_type}}</code> <code>@{{work_location_name}}</code> <code>@{{work_location_address}}</code> <code>@{{employment_start_date}}</code> <code>@{{probation_period_months}}</code><br>
                                <strong>Company:</strong> <code>@{{company_name}}</code> <code>@{{company_registration_number}}</code> <code>@{{company_address}}</code><br>
                                <strong>Compensation:</strong> <code>@{{basic_salary_amount}}</code> <code>@{{currency}}</code> <code>@{{salary_frequency}}</code> <code>@{{allowances_total}}</code> <code>@{{gross_salary_amount}}</code> <code>@{{service_charge_eligible}}</code> <code>@{{accommodation_provided}}</code> <code>@{{meals_provided}}</code> <code>@{{uniform_provided}}</code><br>
                                <strong>Working Hours & Terms:</strong> <code>@{{working_hours_per_day}}</code> <code>@{{working_days_per_week}}</code> <code>@{{weekly_off_days}}</code> <code>@{{overtime_rate}}</code> <code>@{{termination_notice_period_days}}</code> <code>@{{termination_notice_during_probation_days}}</code><br>
                                <strong>Dates & Signatures:</strong> <code>@{{offer_issue_date}}</code> <code>@{{offer_expiry_date}}</code> <code>@{{candidate_signature_placeholder}}</code> <code>@{{offer_signature_date}}</code>
                            </div>
                        </details>
                    </div>
                </div>
            </form>
        </div>

        {{-- Templates DataTable --}}
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
            <table class="table table-docTemplates w-100">
                <thead>
                    <tr>
                        <th>Template Name</th>
                        <th>File</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th>Default</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
$(document).ready(function() {

    var listUrl = "{{ $type === 'offer_letter' ? route('resort.ta.offerLetterTemplates.list') : route('resort.ta.contractTemplates.list') }}";

    $('.table-docTemplates tbody').empty();
    var docTable = $('.table-docTemplates').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 10,
        processing: true,
        serverSide: true,
        order: [[3, 'desc']],
        ajax: {
            url: listUrl,
            type: 'GET',
            data: function(d) {
                d.searchTerm = $('.search').val();
            }
        },
        columns: [
            { data: 'name', name: 'ta_document_templates.name', className: 'text-nowrap' },
            { data: 'file_name', name: 'file_name', className: 'text-nowrap', orderable: false, searchable: false },
            { data: 'uploaded_by_name', name: 'uploaded_by_name', className: 'text-nowrap', orderable: false, searchable: false },
            { data: 'created_at', name: 'ta_document_templates.created_at', className: 'text-nowrap' },
            { data: 'default_badge', name: 'default_badge', className: 'text-nowrap', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('.search').on('keyup', function() {
        docTable.ajax.reload();
    });

    // Upload form
    $('#uploadTemplateForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        if ($btn.prop('disabled')) return false;
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Uploading...');

        var formData = new FormData(this);
        $.ajax({
            url: "{{ route('resort.ta.documentTemplate.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    docTable.ajax.reload();
                    $('#uploadTemplateForm')[0].reset();
                } else {
                    toastr.error(response.message || "Failed to upload.", "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || "An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-1"></i> Upload Template');
            }
        });
    });

    // Set as Default
    $(document).on('click', '.set-default-btn', function() {
        var id = $(this).data('id');
        $.ajax({
            type: "PUT",
            url: "{{ route('resort.ta.documentTemplate.setDefault', '') }}/" + id,
            data: { _token: "{{ csrf_token() }}" },
            dataType: "json",
        }).done(function(response) {
            if (response.success) {
                toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                docTable.ajax.reload();
            } else {
                toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
            }
        }).fail(function() {
            toastr.error("Something went wrong", "Error", { positionClass: 'toast-bottom-right' });
        });
    });

    // Delete
    $(document).on('click', '.delete-row-btn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('resort.ta.documentTemplate.destroy', '') }}/" + id,
                    data: { _token: "{{ csrf_token() }}" },
                    dataType: "json",
                }).done(function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        docTable.ajax.reload();
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                }).fail(function() {
                    toastr.error("Something went wrong", "Error", { positionClass: 'toast-bottom-right' });
                });
            }
        });
    });

});
</script>
@endsection
