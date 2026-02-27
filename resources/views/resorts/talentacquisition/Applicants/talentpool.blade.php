@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        @if(!in_array($rank, [2, 7]))
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select" name="Department" id="ResortDepartment">
                                <option selected disabled>Select Department</option>
                                @if($ResortDepartment->isNotEmpty())
                                    @foreach ($ResortDepartment as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}">{{ $item->name }}</option>

                                    @endforeach

                                @endif
                            </select>
                        </div>
                        @elseif($rank == 2)
                        <input type="hidden" id="ResortDepartment" value="{{ $employeeDeptId }}">
                        @endif
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select  Positions" name="Positions">
                                <option selected disabled>Select Poitions</option>
                           </select>
                        </div>
                        {{-- <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control" placeholder="18/10/2024">
                        </div> --}}
                        <div class="col-auto ms-auto">
                            <a href="javascript:void(0)" class="btn btn-grid"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="javascript:void(0)" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main ">
                    <div class="table-responsive">
                        <table class="table table-collapseNew TalentPool">
                            <thead>
                                <tr>
                                    <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Nationality<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Passport No.</th>
                                    <th>Experience<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Applied Date<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Stage<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Consent Expiry Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main d-none  ">
                    <div class="row g-md-4 g-3 mb-4 " id="grid_main_view">

                    </div>

                </div>

            </div>


        </div>
    </div>

    <div class="userApplicants-wrapper">
    </div>

    {{-- Rejection Reason Modal --}}
    <div class="modal fade" id="Response-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejection Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='RevertResponeForm'>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="hidden" name="applicant_status_id" id="applicant_status_id">
                            <textarea class="form-control" readonly disabled id="Reason" name="Reason" placeholder="Reason"></textarea>
                        </div>
                        <input type="hidden" name="Interview_id" id="Interview_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue RevertBack d-none">Revert Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Consent Request Modal --}}
    <div class="modal fade" id="consentRequest-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Consent Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="consentRequestForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Data Retention Expiry Date</label>
                            <input type="date" class="form-control" name="consent_expiry_date" required min="{{ date('Y-m-d') }}">
                        </div>
                        <p class="text-muted small">An email will be sent to the applicant requesting their consent to retain their profile data until the selected date.</p>
                        <input type="hidden" name="applicant_id" id="consent_applicant_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Check Availability Modal --}}
    <div class="modal fade" id="checkAvailability-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check Availability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="checkAvailabilityForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Email Template</label>
                            <select class="form-control" name="email_template_id" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting Link (Optional)</label>
                            <input type="text" class="form-control" name="meeting_link" placeholder="Enter Meeting Link">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Job Link (Optional)</label>
                            <input type="text" class="form-control" name="job_link" placeholder="Enter Job Posting Link">
                        </div>
                        <input type="hidden" name="applicant_id" id="availability_applicant_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Send Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- File Viewer Modal --}}
    <div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download File</h5>
                    <a href="" class="btn btn-sm btn-primary downloadLink" target="_blank">Download</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ratio ratio-21x9" id="ViewModeOfFiles"></div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" id="document-dismiss" class="btn btn-themeGray ms-auto">Cancel</a>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {

        $('.search').on('keyup', function() {

            let girdview = $(".btn-grid").hasClass('active');

            if(girdview)
            {
                DatatableGrid();

            }
            else
            {
                DatatableList();
            }
        });
        if($("#ResortDepartment").is("select")) {
            $("#ResortDepartment").select2({"Placeholder":"Select Department"});
        }
        $(".Positions").select2({"Placeholder":"Select Positions"});

        @if($rank == 2 && $employeeDeptId)
        // Auto-load positions for HOD's department
        $.ajax({
            url: "{{ route('resort.get.position') }}",
            type: "post",
            data: { deptId: {{ $employeeDeptId }} },
            success: function(data) {
                if(data.success == true) {
                    let string = '<option selected disabled>Select Positions</option>';
                    $.each(data.data, function(key, value) {
                        string += '<option value="'+value.id+'">'+value.position_title+'</option>';
                    });
                    $(".Positions").html(string);
                }
            }
        });
        @endif

        $(document).on("change", ".Positions", function() {
            let girdview = $(".btn-grid").hasClass('active');

                    if(girdview)
                    {
                        DatatableGrid();

                    }
                    else
                    {
                        DatatableList();
                    }
     });
        $(document).on('change', '#ResortDepartment', function() {
                var deptId = $(this).val();

                let currentDepartment = $(this).val();
                let isDuplicate = false;

                let string='<option selected disabled>Select Positions</option>';
                $(".Positions").html(string);
                    $.ajax({
                        url: "{{ route('resort.get.position') }}",
                        type: "post",
                        data: {
                            deptId: deptId
                        },
                        success: function(data) {
                            if(data.success == true)
                            {
                                $.each(data.data, function(key, value) {
                                    console.log(value.position_title);
                                    string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                                });
                                $(".Positions").html(string);
                            }
                        },
                        error: function(response) {
                            toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                        }
                    });
            });
            $(".btn-grid").click(function () {
                $(this).addClass("active");
                $(".grid-main").addClass("d-block");
                $(".grid-main").removeClass("d-none");
                $(".btn-list").removeClass("active");
                $(".list-main").addClass("d-none");
                $(".list-main").removeClass("d-block");
                DatatableGrid()
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
                $('.TalentPool').DataTable().ajax.reload();
            });
            DatatableList();
            $('#RevertResponeForm').validate({
                rules: {
                    Reason: {
                        required: true,
                    },
                    applicant_status_id: {
                        required: true,
                    }
                },
                messages :
                {
                    Reason: {
                        required: "Reason is not exitest.",
                    }
                    ,
                    applicant_status_id: {
                        required:  "Reason Applicant Status Not Found.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.RevertBack') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                DatatableList(); DatatableGrid();
                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        $("#Response-modal").modal('hide');

                            }

                    },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            console.log(errors.errors);
                            $.each(errors.errors, function(key, error)
                            {
                                console.log(error);
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right'});
                        }
                    });
                }
            });
        });
    $(document).on("click", ".destoryApplicant", function() {
            var base64_id = $(this).attr('data-id');
            var location = $(this).attr('data-location');

            // SweetAlert confirmation dialog
            Swal.fire({
                title: "Are you sure?",
                text: "This action will permanently delete the applicant.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with AJAX request after confirmation
                    $.ajax({
                        url: "{{ route('resort.ta.destoryApplicant') }}",
                        type: "POST",
                        data: { base64_id: base64_id, "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success) {
                                Swal.fire(
                                    "Deleted!",
                                    response.message,
                                    "success"
                                );
                                $("#talentPool_" + location).remove();
                                DatatableList(); DatatableGrid();
                            } else {
                                Swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                );
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                console.log(error);
                                errs += error + '<br>';
                            });
                            Swal.fire("Error!", errs, "error");
                        }
                    });
                }
            });
        });

        $(document).on("click", ".RejactionReason", function() {
        let Comments = $(this).attr('data-Comments');
        let Rank = $(this).attr('data-Rank');
        let applicant_status_id = $(this).attr('data-applicant_status_id');


        $("#Reason").val(Comments);
        if(Rank == 0)
        {
            $(".RevertBack").removeClass('d-none');
            $("#applicant_status_id").val(applicant_status_id);
        }

        $("#Response-modal").modal('show');
    });
    $(document).on("click", ".destoryApplicant", function() {
            var base64_id = $(this).attr('data-id');
            var location = $(this).attr('data-location');

            // SweetAlert confirmation dialog
            Swal.fire({
                title: "Are you sure?",
                text: "This action will permanently delete the applicant.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed with AJAX request after confirmation
                    $.ajax({
                        url: "{{ route('resort.ta.destoryApplicant') }}",
                        type: "POST",
                        data: { base64_id: base64_id, "_token": "{{ csrf_token() }}" },
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success) {
                                Swal.fire(
                                    "Deleted!",
                                    response.message,
                                    "success"
                                );
                                $("#talentPool_" + location).remove();
                                DatatableList(); DatatableGrid();
                            } else {
                                Swal.fire(
                                    "Error!",
                                    response.message,
                                    "error"
                                );
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                console.log(error);
                                errs += error + '<br>';
                            });
                            Swal.fire("Error!", errs, "error");
                        }
                    });
                }
            });
        });



    function DatatableList()
    {
            if ($.fn.DataTable.isDataTable('.TalentPool'))
            {
                $('.TalentPool').DataTable().destroy();
            }
            var TalentPool = $('.TalentPool').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bFilter: true,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: true,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    order:[[12, 'desc']],
                    ajax: {
                        url: "{{ route('resort.ta.TalentPool')}}",
                        type: 'GET',
                        data: function(d) {
                            d.ResortDepartment = $("#ResortDepartment").val();
                            d.searchTerm = $('.search').val();
                            d.Positions  = $('.Positions ').val()
                        }
                    },
                    columns: [
                        { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                            return `<div class="tableUser-block">
                                <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                                <span class="userApplicants-btn" data-id="${row.applicant_id}">${row.name}</span>
                            </div>`;
                        }},

                        { data: 'Position', name: 'Position' },
                        { data: 'Department', name: 'Department' },
                        { data: 'countryName', name: 'countryName' },
                        { data: 'passport_no', name: 'passport_no' },
                        { data: 'total_work_exp', name: 'total_work_exp', render: function (data) {
                            return `${data} year`;
                        }},
                        { data: 'email', name: 'email' },
                        { data: 'contact', name: 'contact' },
                        { data: 'Application_date', name: 'Application_date' },
                        { data: 'Stage', name: 'Stage'},
                        { data: 'ConsentExpiryDate', name: 'ConsentExpiryDate'},
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                        {data:'created_at', visible:false,searchable:false},
                    ]

                });



    }
    function DatatableGrid()
    {


                        $.ajax({
                            url:"{{ route('resort.ta.getTalentPoolApplicant') }}",
                            type: "post",
                            data:
                            {
                                id :$("#vacancy-id").val(),
                                searchTerm : $('.search').val(),
                                ResortDepartment: $("#ResortDepartment").val(),
                                searchTerm: $('.search').val(),
                                Positions : $('.Positions ').val()
                            },
                            success: function(response)
                            {
                                if (response.success)
                                {
                                    $("#grid_main_view").html(response.view);
                                    // $('.table-applicants').DataTable().ajax.reload();
                                    ApplicantProgress();
                                }
                            },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    var errs = '';
                                    console.log(errors.errors);
                                    $.each(errors.errors, function(key, error)
                                    {
                                        console.log(error);
                                        errs += error + '<br>';
                                    });
                                    toastr.error(errs, { positionClass: 'toast-bottom-right'});
                                }
                        });
    }

    // Sidebar - View Profile
    const $userApplicantsWrapper = $(".userApplicants-wrapper");
    $(document).on("click", ".userApplicants-btn", function (e) {
        e.stopPropagation();
        let id = $(this).data("id");
        let url = "{{ route('resort.ta.TaUserApplicantsSideBar', ':id') }}";
        url = url.replace(':id', id);
        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $(".userApplicants-wrapper").html(response.view);
                }
            },
            error: function() {
                toastr.error('Something went wrong.', { positionClass: 'toast-bottom-right' });
            }
        });
        $userApplicantsWrapper.toggleClass("end-0");
    });

    $(document).on("click", ".closeSlider", function (e) {
        e.preventDefault();
        $userApplicantsWrapper.toggleClass("end-0");
    });

    $(document).on("click", "#document-dismiss", function () {
        $("#bdVisa-iframeModel-modal-lg").modal('hide');
        $("#ViewModeOfFiles").empty();
        $(".downloadLink").attr("href", "");
        $(".userApplicants-wrapper").addClass("end-0");
    });

    // Download File in sidebar
    $(document).on("click", ".DownloadFile", function () {
        let fileId = $(this).data("id");
        let fileFlag = $(this).data("flag");
        $.ajax({
            url: "{{ route('resort.ta.DownloadFile') }}",
            type: "POST",
            data: { id: fileId, flag: fileFlag },
            success: function(response) {
                if (response.success) {
                    $("#ViewModeOfFiles").html('<div class="text-center"><p>Loading...</p></div>');
                    $("#bdVisa-iframeModel-modal-lg").modal('show');
                    let fileUrl = response.NewURLshow;
                    $(".downloadLink").attr("href", fileUrl);
                    let mimeType = response.mimeType.toLowerCase();
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    let iframeTypes = ['video/mp4', 'application/pdf', 'text/plain'];
                    if (imageTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html('<img src="'+fileUrl+'" class="popupimgFileModule" alt="Preview">');
                    } else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html('<iframe style="width:100%;height:100%;" src="'+fileUrl+'" allowfullscreen></iframe>');
                    } else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        window.location.href = fileUrl;
                    }
                }
            }
        });
    });

    // Download All Files
    $(document).on("click", ".DownloadAllFiles", function () {
        let fileId = $(this).data("id");
        let btn = $(this);
        btn.prop('disabled', true).text('Downloading...');
        $.ajax({
            url: "{{ route('resort.ta.DownloadAllFiles') }}",
            type: "POST",
            data: { id: fileId },
            success: function(response) {
                btn.prop('disabled', false).text('Download All');
                if (response.success) {
                    response.files.forEach(function(file, index) {
                        setTimeout(function() {
                            let a = document.createElement('a');
                            a.href = file.url;
                            a.download = file.name;
                            a.target = '_blank';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        }, index * 500);
                    });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Download All');
                toastr.error("Something went wrong!", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // View/Hide Comments
    $(document).on("click", ".userAppInt-vCommBtn", function () {
        $(this).addClass("d-none").removeClass("d-block").siblings(".userAppInt-hCommBtn").addClass("d-block").removeClass("d-none");
        $(this).closest("tr").find(".userAppInt-commBlock").addClass("d-block");
    });
    $(document).on("click", ".userAppInt-hCommBtn", function () {
        $(this).addClass("d-none").removeClass("d-block").siblings(".userAppInt-vCommBtn").addClass("d-block").removeClass("d-none");
        $(this).closest("tr").find(".userAppInt-commBlock").removeClass("d-block");
    });

    // Send Consent Request - open modal
    $(document).on("click", ".sendConsentRequestBtn", function() {
        var applicantId = $(this).data("id");
        $("#consent_applicant_id").val(applicantId);
        $("#consentRequest-modal").modal("show");
    });

    // Send Consent Request - submit
    $('#consentRequestForm').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: "{{ route('resort.ta.sendConsentRequest') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $("#consentRequest-modal").modal("hide");
                    $('#consentRequestForm')[0].reset();
                    DatatableList();
                } else {
                    toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Send Request');
            }
        });
    });

    // Check Availability - open modal
    $(document).on("click", ".checkAvailabilityBtn", function() {
        var applicantId = $(this).data("id");
        $("#availability_applicant_id").val(applicantId);
        $("#checkAvailability-modal").modal("show");
    });

    // Check Availability - submit
    $('#checkAvailabilityForm').on('submit', function(e) {
        e.preventDefault();
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: "{{ route('resort.ta.checkAvailability') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $("#checkAvailability-modal").modal("hide");
                    $('#checkAvailabilityForm')[0].reset();
                } else {
                    toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Send Email');
            }
        });
    });

</script>
@endsection

