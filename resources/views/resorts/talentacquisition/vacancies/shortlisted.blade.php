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
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    {{-- <div class="col-auto ms-auto">
                        <a href="{{ route('resort.ta.shortlistedapplicants') }}" class="btn btn-themeLightNew">Shortlisted Applicants To share Link</a>
                    </div> --}}
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div class="card">

                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select" name="department" id="department">
                                <option selected disabled>Select Department</option>
                                @foreach ($department_details as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>

                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                    <table class="table"  id="SortlistedApplicants" >
                        <thead>
                            <tr>
                                <th>Applicants</th>
                                <th>Rank</th>
                                <th>Score</th>
                                <th>Nation</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Position	</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>


                    </table>

                </div>
            </div>
        </div>

    </div>
    <input type="hidden" name="{{     $id }}" value="{{$id }}" id="RequestedID" >

    {{-- Applicant Sidebar --}}
    <div class="userApplicants-wrapper"></div>
    @endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>

$(document).ready(function() {



    $("#department").select2({
        "placeholder":"Select Department "
    });
    $('.Search').on('keyup', function() {
        SortlistedList();
    });

    $("#department").on('change', function() {
        SortlistedList();
    });
    SortlistedList()

});

    // Applicant sidebar handler
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
            error: function(response) {
                toastr.error('Failed to load applicant details.', { positionClass: 'toast-bottom-right' });
            }
        });
        $userApplicantsWrapper.toggleClass("end-0");
    });

    $(document).on("click", ".closeSlider", function (e) {
        e.preventDefault();
        $userApplicantsWrapper.toggleClass("end-0");
    });

    // Close sidebar when clicking outside
    $(document).on("click", function (e) {
        if (!$userApplicantsWrapper.is(e.target) && $userApplicantsWrapper.has(e.target).length === 0 &&
            !$(e.target).hasClass('userApplicants-btn') && $(e.target).closest('.userApplicants-btn').length === 0) {
            $userApplicantsWrapper.removeClass("end-0");
        }
    });

function  SortlistedList()
    {
        $('#SortlistedApplicants tbody').empty();
        if ($.fn.DataTable.isDataTable('#SortlistedApplicants'))
        {
        $('#SortlistedApplicants').DataTable().destroy();
        }
            var SortlistedApplicants = $('#SortlistedApplicants').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order:[[8, 'desc']],
                ajax: {
                    url: (function() {
                        let id = $("#RequestedID").val();
                        let url = "{{ route('resort.ta.shortlisted', ':id') }}";
                        return url.replace(':id', id);
                    })(),
                    type: 'GET',
                    data: function(d) {

                                // var complianceStatus = $('#complianceSelect').val();
                                // d.compliance_status = complianceStatus;
                                let Department = $('#department').val();
                                var searchTerm = $('.Search').val();
                                d.searchTerm = searchTerm;
                                d.Department= Department;


                            }
                },
                columns: [

                    { data: 'Applicants', name: 'Applicants', className: 'text-nowrap' },
                    { data: 'Rank', name: 'Rank', className: 'text-nowrap' },
                    { data: 'Score', name: 'Score', className: 'text-nowrap' },
                    { data: 'Nation', name: 'Nation', className: 'text-nowrap' },
                    { data: 'Email', name: 'Email', className: 'text-nowrap' },
                    { data: 'Contact', name: 'Contact', className: 'text-nowrap' },
                    { data: 'Position', name: 'Position', className: 'text-nowrap' },
                    { data: 'Department', name: 'Department', className: 'text-nowrap' },
                    { data: 'Action', name: 'Action', orderable: false, searchable: false, className: 'text-nowrap' },
                    {data:'created_at', visible:false,searchable:false},

                ]
        });
    }

    // Notes form AJAX submit
    $(document).on('submit', '#ApplicantNoteForm', function(e) {
        e.preventDefault();
        let form = this;
        let formData = new FormData(form);
        let noteText = $(form).find('textarea[name="ApplicantNote"]').val();
        if (!noteText || !noteText.trim()) {
            toastr.error('Please write a note before submitting.', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: "{{ route('resort.ta.ApplicantNote') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    let $notesBlock = $('#tabPane4 .notes-display-block');
                    if ($notesBlock.length) {
                        $notesBlock.find('p').text(noteText);
                    } else {
                        let notesHtml = '<div class="intUserApp-block mt-3 notes-display-block"><h6>Notes:</h6><p>' + $('<span>').text(noteText).html() + '</p></div>';
                        let $last = $('#tabPane4 .a-link').last();
                        if ($last.length) { $last.after(notesHtml); } else { $('#tabPane4 .table-responsive').after(notesHtml); }
                    }
                    $('#myTab button[data-bs-target="#tabPane4"]').tab('show');
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = (errors && errors.errors) ? Object.values(errors.errors).join('<br>') : 'Failed to save note.';
                toastr.error(errs, { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // Comments form AJAX submit
    $(document).on('submit', '#RoundWiseForm', function(e) {
        e.preventDefault();
        let form = this;
        let formData = new FormData(form);
        let commentText = $(form).find('textarea[name="Comment"]').val();
        if (!commentText || !commentText.trim()) {
            toastr.error('Please write a comment before submitting.', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: "{{ route('resort.ta.RoundWiseForm') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $(form).find('textarea[name="Comment"]').val('');
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    let $commentsBlock = $('#tabPane4 .comments-display-block');
                    let commentHtml = '<div class="mb-2 p-2" style="background:#f5f5f5; border-radius:6px;"><p class="mb-0">' + $('<span>').text(commentText).html() + '</p></div>';
                    if ($commentsBlock.length) {
                        $commentsBlock.append(commentHtml);
                    } else {
                        let commentsBlockHtml = '<div class="intUserApp-block mt-3 comments-display-block"><h6>Comments:</h6>' + commentHtml + '</div>';
                        let $notesBlock = $('#tabPane4 .notes-display-block');
                        if ($notesBlock.length) { $notesBlock.after(commentsBlockHtml); }
                        else {
                            let $last = $('#tabPane4 .a-link').last();
                            if ($last.length) { $last.after(commentsBlockHtml); } else { $('#tabPane4 .table-responsive').after(commentsBlockHtml); }
                        }
                    }
                    $('#myTab button[data-bs-target="#tabPane4"]').tab('show');
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = (errors && errors.errors) ? Object.values(errors.errors).join('<br>') : 'Failed to save comment.';
                toastr.error(errs, { positionClass: 'toast-bottom-right' });
            }
        });
    });

</script>
@endsection

