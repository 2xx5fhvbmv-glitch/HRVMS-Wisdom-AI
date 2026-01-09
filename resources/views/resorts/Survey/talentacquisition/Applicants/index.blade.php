@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

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
                    <div class="col-auto ms-auto">
                        <a href="{{ route('resort.ta.shortlisted',$id) }}" class="btn btn-themeLightNew">Shortlisted Applicants</a>
                    </div>
                    <div class="col-auto">
                        <a href="{{route('resort.ta.UpcomingApplicants')}}" class="btn btn-themeLightNew">Upcoming Interviews</a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn btn-themeLightNew">Rejected Applications</a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn btn-themeLightNew">Review Reminders</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="#" class="btn btn-grid"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="#" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main ">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants">
                            <thead>
                                <tr>
                                    <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Nationality<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Passport No.</th>
                                    <th>Experience<i class="fa-solid fa-caret-up"></i></th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Applied Date<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Stage<i class="fa-solid fa-caret-down"></i></th>
                                    <th></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main d-none  ">
                    <div class="row g-md-4 g-3 mb-4" id="grid_main_view">

                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="vacancy-id" value="{{ $id }}">

    <div class="modal fade" id="ApplicantsNotes-Model" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="ApplicantNoteForm">
                        @csrf
                        <div class="modal-body">
                            <textarea class="form-control" rows="7"  id="ApplicantNote" name="ApplicantNote" placeholder="Note.."></textarea>
                        </div>
                        <input type="hidden" id="Applicant_id" name="Applicant_id">

                        <div class="modal-footer justify-content-center">
                            <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                            <button type="submit"  class="btn btn-themeBlue">Submit</button>
                        </div>
                    </form>
                </div>

        </div>
    </div>

    <!-- modal -->

    <div class="modal fade" id="intDetail-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Interview Details </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-2">
                        <table class="table table-lable">
                            <tbody>
                                <tr>
                                    <th>Interview Template</th>
                                    <td>Lorem ipsum</td>
                                </tr>
                                <tr>
                                    <th>Round</th>
                                    <td>HR Round</td>
                                </tr>
                                <tr>
                                    <th>Interview Type</th>
                                    <td>Introductory</td>
                                </tr>
                                <tr>
                                    <th>Interviewer</th>
                                    <td>HR</td>
                                </tr>
                                <tr>
                                    <th>Interview Status</th>
                                    <td><span class="badge badge-info">Slot Booked</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mb-2">
                        <a href="#shareMeetLink-modal" data-bs-toggle="modal" data-bs-dismiss="modal"
                            class="btn btn-themeSkyblue btn-sm">Share
                            Meeting Link</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="#" class="btn btn-themeBlue">Submit</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reviewInview-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Review Interview Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-lable mb-0">
                            <tbody class="InterviewReviewData"id="InterviewReviewData">

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeBlue">Submit</a>
                </div>
            </div>
        </div>
    </div>


    <div class="userApplicants-wrapper ">

    </div>

    {{-- request Interview --}}
    <div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="InterviewRequestSentForm">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label mb-8">Select date</label>
                        <div class="modalCalendar-block">
                            <div id="calendarModalSendInterView"></div>

                            <input type="date" class="InterviewDateModel"  id="InterviewDate" name="InterviewDate">

                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <div class="modal fade" id="TimeSlots-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-small modal-timeSlotsModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="TimeSlotsForm">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label mb-sm-4 mb-3">SELECT TIME SLOTS</label>
                        <div class="sendRequestTime-main">
                        </div>
                        <input type="hidden" id="Resort_id" name="Resort_id">
                        <input type="hidden" id="ApplicantID" name="ApplicantID">
                        <input type="hidden" id="ApplicantStatus_id" name="ApplicantStatus_id">
                        <input type="hidden" id="Calender_ta_id" name="ta_id">
                        <input type="date" style="display: none" id="TimeSlotsFormdate" name="TimeSlotsFormdate">
                        <input type="hidden" id="EmailTemplate" name="EmailTemplate">
                        <input type="hidden" id="Interviewer" name="Interviewer">
                        <input type="hidden" id="Round" name="Round">
                        <input type="hidden" id="InterviewType" name="InterviewType">

                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="sendRequestFinal-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0">
                    <div class="table-responsive">
                        <table class="table table-sendRequestFinal w-100">
                            <tbody id="Final_response_data">

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="javascript:void(0)"  data-bs-dismiss="modal" class="btn btn-theme" >Submit</a>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="shareMeetLink-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Share Interview Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='shareMeetLinkForm'>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="" class="form-label ">PLEASE PROVIDE THE MEETING LINK FOR INTERVIEW</label>
                            <input type="text" class="form-control" name="MeetingLink" placeholder="Meeting Link">
                        </div>
                        <div style="height:180px;"></div>
                        <input type="hidden" name="Interview_id" id="Interview_id">
                        <input type="hidden" name="Round" id="Round1">
                        <input type="hidden" name="InterviewType" id="InterviewType1">
                        <input type="hidden" id="EmailTemplate1" name="EmailTemplate">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="Email-template-selection-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Select EMail Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='EmailTemplateForm'>
                    @csrf
                    <div class="modal-body">
                    <select class="form-control EmailTemplate" name="EmailTemplate-popup" id="EmailTemplate-popup" required>
                        <option selected disabled value="">Select Email Template</option>
                        @foreach ($EmailTamplete as $e)
                            <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                        @endforeach
                    </select>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- End of Interview Request --}}
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script>
        $(document).ready(function() {
            datatablelist();
            $('.table-applicants tbody').empty();

            $('.table-applicants tbody').on('click', 'a.details-toggle', function () {
                const $link = $(this);
                const rowId = $link.attr('data-row-id');
                const status = $link.attr('data-status');
                const applicant_id = $link.attr('data-applicant_id');
                const $currentRow = $link.closest('tr');

                if ($(`#detailsRow${rowId}`).length )
                {
                    $(`#detailsRow${rowId}`).toggle();
                }
                else
                {
                    if( status != "Sortlisted By Wisdom AI")
                    {
                        if(status == "Complete")
                        {
                            toastr.error('Please Wait For the next Responsed .', { positionClass: 'toast-bottom-right', timeOut: 5000 });
                            return false;
                        }
                        else if(status == "Selected")
                        {
                            toastr.error('This Applicant Already Selected By GM.', { positionClass: 'toast-bottom-right', timeOut: 5000 });
                        }
                        else
                        {
                            let url = "{{ route('resort.ta.ApplicantWiseStatus') }}";
                            $.ajax({
                                url: url,
                                type: "POST",
                                data:{"Applicant_id":applicant_id,"rowId":rowId,"status":status,"_token": "{{ csrf_token() }}"},
                                success: function(response)
                                {
                                    if (response.success)
                                    {
                                        console.log(response);
                                        let newTag = 'send Link';
                                        if(response.data.InterviewStatus == 'Slot Not Booked')
                                        {
                                            newTag =`<a href="javascript:void(0)"
                                                data-Resort_id="${response.data.Resort_id}"
                                                data-ApplicantID="${response.data.ApplicantID}"
                                                data-ApplicantStatus_id="${response.data.ApplicantStatus_id}"
                                                class="btn btn-themeSkyblue btn-small SortlistedEmployee">Send Interview Request</a>`;
                                        }
                                        else if(response.data.InterviewStatus =="Slot Booked" && !isNaN(response.data.MeetingLink) )
                                        {
                                            newTag =`<a class="btn btn-themeSkyblue btn-small ApplicantShareLink"
                                            data-round="${response.data.round}"
                                                data-rank_name="${response.data.rank_name}"
                                            data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Add Interview Link</a>`;

                                        }
                                        else
                                        {
                                            newTag =`<a href="${response.data.MeetingLink}" target="_blank" class="btn btn-themeSkyblue btn-small"
                                            data-rond="${response.data.round}"
                                            data-rank_name="${response.data.rank_name}"
                                            data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Start Interview  </a>`;
                                        }

                                        // <a href="#shareMeetLink-modal" data-bs-toggle="modal" class="btn btn-themeSkyblue btn-small">
                                        //                         Share Meeting Link
                                        //                     </a>
                                        const newRow =`
                                        <tr id="detailsRow${rowId}" class="details-row">
                                            <td colspan="10">
                                                <div class="bg">
                                                    <table class="w-100">
                                                        <tr>
                                                            <th>Interview Template</th>
                                                            <th>Round</th>
                                                            <th>Interview Type</th>
                                                            <th>Interviewer</th>
                                                            <th>Interview Date</th>
                                                            <th>Maldives Time</th>
                                                            <th>Applicant Time</th>
                                                            <th>Interview Status</th>

                                                            <th>Action</th>
                                                        </tr>
                                                        <tr>
                                                            <td><select class="form-control EmailTemplate" name='EmailTemplate'>
                                                                <option selected disabled>Select Email Template </option>
                                                                    @foreach ($EmailTamplete as $e)
                                                                        <option value="{{ $e->id}}">{{ $e->TempleteName }}</option>
                                                                    @endforeach
                                                                    </select>
                                                            </td>
                                                            <td>${response.data.rank_name}</td>
                                                            <td>${response.data.round}</td>
                                                            <td><input type="hidden"  class="Round"  name="Round" value="${response.data.rank_name}">
                                                            <input type="hidden"  class="InterviewType"  name="InterviewType" value="${response.data.round}">
                                                            <input type="hidden"  class="Interviewer"  name="Interviewer" value="${response.data.Interviewer}">
                                                                ${response.data.Interviewer}</td>
                                                            <td>${response.data.Date}</td>
                                                            <td>${response.data.MalidivanTime}</td>
                                                            <td>${response.data.ApplicantTime}</td>
                                                            <td>${response.data.InterviewStatus}</td>
                                                            <td>
                                                                ${newTag}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>`;

                                        $currentRow.after(newRow);
                                            $(".EmailTemplate").select2({
                                                "Placeholder": "Select Email Template",
                                            });
                                    }
                                },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    if (errors && errors.errors) {
                                        var errs = '';
                                        $.each(errors.errors, function(key, error) {
                                            errs += error + '<br>';
                                        });
                                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                                    } else {
                                        // Handle other types of errors
                                        toastr.error('An unexpected error occurred. Please try again.', { positionClass: 'toast-bottom-right' });
                                        console.log(response); // Debugging log for unexpected errors
                                    }
                                }
                            });
                        }
                    }
                    else
                    {
                        toastr.error('Please Wait HR Response.', { positionClass: 'toast-bottom-right', timeOut: 5000 });
                    }
                }
            });

            $(document).on("click",".ApplicantsNotes",function(suc){

                let Applicantid =  $(this).data('id');
                let notes = $(this).data('notes');
                let id =Applicantid;
                $("#ApplicantsNotes-Model").modal('show');
                $("#Applicant_id").val(Applicantid);
                let url = "{{ route('resort.ta.getApplicantWiseNotes', ':id') }}";
                url = url.replace(':id',id);
                                $.ajax({
                                    url:url,
                                    type: "GET",
                                    success: function(response)
                                    {
                                        if (response.success)
                                        {
                                            $("#ApplicantNote").val(response.notes);

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

            });
            $('#ApplicantNoteForm').validate({
                rules: {
                    ApplicantNote: {
                        required: true,
                    }
                },
                messages :
                {
                    ApplicantNote: {
                        required: "Please Write Something.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.ApplicantNote') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {
                                $("#FreshHiringRequest").html(response.view);

                                $("#ApplicantsNotes-Model").modal('hide');
                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });

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
            $('.search').on('keyup', function()
            {
                let girdview = $(".btn-grid").hasClass('active');
                if(girdview)
                {
                    DatatableGrid();
                }
                else
                {
                    datatablelist();
                }
            });

            // Desinger Code start
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
                $('.table-applicants').DataTable().ajax.reload();
                ApplicantProgress();
            });

            const $userApplicantsWrapper = $(".userApplicants-wrapper");
            $(document).on("click", ".userApplicants-btn", function (e) {
                e.stopPropagation(); // Prevent event from bubbling up to the document click

                let id = $(this).data("id");
                let url = "{{ route('resort.ta.TaUserApplicantsSideBar', ':id') }}";

                    url = url.replace(':id',id);
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function(response)
                        {
                                if (response.success)
                                {
                                    $(".userApplicants-wrapper").html(response.view);
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
                $userApplicantsWrapper.toggleClass("end-0");
            });

            $(document).on("click", function (e) {
                if (
                    !$userApplicantsWrapper.is(e.target) &&
                    $userApplicantsWrapper.has(e.target).length === 0 &&
                    !$(e.target).closest(".userApplicants-btn").length
                ) {
                    $userApplicantsWrapper.removeClass("end-0");
                }
            });

            $(document).on("click", ".userAppInt-vCommBtn", function () {
                // Handle "View Comments" button
                $(this)
                    .addClass("d-none")
                    .removeClass("d-block")
                    .siblings(".userAppInt-hCommBtn")
                    .addClass("d-block")
                    .removeClass("d-none");

                // Show the comments block for the current row
                $(this)
                    .closest("tr") // Select the current table row
                    .find(".userAppInt-commBlock") // Target the comments block in the same row
                    .addClass("d-block");
            });

            $(document).on("click", ".userAppInt-hCommBtn", function () {
                // Handle "Hide Comments" button
                $(this)
                    .addClass("d-none")
                    .removeClass("d-block")
                    .siblings(".userAppInt-vCommBtn")
                    .addClass("d-block")
                    .removeClass("d-none");

                // Hide the comments block for the current row
                $(this)
                    .closest("tr") // Select the current table row
                    .find(".userAppInt-commBlock") // Target the comments block in the same row
                    .removeClass("d-block");
            });

            $('.search').on('keyup', function() {
                let girdview = $(".btn-grid").hasClass('active');

                if(girdview)
                {
                    DatatableGrid();
                }
                else
                {
                    datatablelist();
                }
            });

            $(function () {
                var todayDate = moment().startOf('day');
                var YM = todayDate.format('YYYY-MM');
                var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
                var TODAY = todayDate.format('YYYY-MM-DD');
                var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

                var cal = $('#calendar').fullCalendar({
                    header: {
                        left: 'prev ',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0, // allow "more" link when too many events
                    navLinks: true,
                    dayRender: function (a) {
                        //console.log(a)
                    }
                });
            });
        });

        function ApplicantProgress() {
            const radius = 54;
            const circumference = 2 * Math.PI * radius;

            const progressContainers = document.querySelectorAll('.progress-container');
            progressContainers.forEach(container => {
                const progressCircle = container.querySelector('.progress');
                const progressValue = container.getAttribute('data-progress');
                const offset = circumference - (progressValue / 100 * circumference);
                if (progressCircle)
                {
                    progressCircle.style.transition = 'none';
                    progressCircle.style.strokeDasharray = circumference;
                    progressCircle.style.strokeDashoffset = circumference;
                    progressCircle.offsetHeight;
                    setTimeout(() => {
                        progressCircle.style.transition = 'stroke-dashoffset 0.75s ease-in-out';
                        progressCircle.style.strokeDashoffset = offset;
                    }, 100);
                }
            });
        }
        $(document).on("focus", '[name^="MalidivanManualTime"], [name^="ApplicantManualTime"]', function () {
            $(".row_time").removeClass("active").find("input").prop("disabled", false);
            $('[name^="ApplicantInterviewtime"]').val('');
            $('[name^="ResortInterviewtime"]').val('');
        });
        $(document).on("change", '[name="MalidivanManualTime"]', function () {
            const timeValue = $(this).val(); // Get the value of the time input
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                let MalidivanManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1); // Display in console
            } else {
                console.log("No time selected");
            }
        });
        $(document).on("change", '[name="ApplicantManualTime"]', function () {
            const timeValue = $(this).val(); // Get the value of the time input
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                let ApplicantManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="ApplicantManualTime1"]').val(ApplicantManualTime1); // Display in console
            } else {
                console.log("No time selected");
            }
        });

        $(document).on("click", ".Timezone_checkBox", function() {

            $(".row_time").not($(this).closest(".row_time")).removeClass("active").find("input").prop("disabled", false);

            $(this).closest(".row_time").toggleClass("active");
            let location = $(this).data('id');

            if ($(this).closest(".row_time").hasClass("active")) {
                $(".row_time").not($(this).closest(".row_time")).find("input").prop("disabled", true);
                $('[name^="ApplicantInterviewtime"]').val('');
                $('[name^="ResortInterviewtime"]').val('');
                $('[name^="ManualTime"]').val('');

                let ApplicantInterviewtime = $(this).data('applicantinterviewtime');
                let ResortInterviewtime = $(this).data('resortinterviewtime');

                if (ApplicantInterviewtime) {
                    $("#ApplicantInterviewtime_" + location).val(ApplicantInterviewtime);
                }

                if (ResortInterviewtime) {
                    $("#ResortInterviewtime_" + location).val(ResortInterviewtime);
                }
            } else {
                // Enable all rows if no row is active
                $(".row_time").find("input").prop("disabled", false);
            }
        });
        $('#respond-HoldModel').on('shown.bs.modal', function () {
            $('#calendarModal').fullCalendar('render');
        });

        $('#sendRequest-modal').on('shown.bs.modal', function () {
            $('#calendarModalSendInterView').fullCalendar('render');
        });

        $(function () {
            var todayDate = moment().startOf('day');
            var YM = todayDate.format('YYYY-MM');
            var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
            var TODAY = todayDate.format('YYYY-MM-DD');
            var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

            // Calendar for respond modal
            $('#calendarModal').fullCalendar({
                header: {
                        left: 'prev',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0,
                    navLinks: true,
                    selectable: true,
                    select: function(start, end) {
                        var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                        $("#HoldDate").val(selectedStartDate);
                        isDateSelected = true;
                        $("#respond-HoldModel").modal("show");
                    },
            });

            // Calendar for send request modal
            $('#calendarModalSendInterView').fullCalendar({
                header: {
                        left: 'prev',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0,
                    navLinks: true,
                    selectable: true, // Add this line
                    select: function(start, end) {
                        var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                        $("#InterviewDate").val(selectedStartDate);
                        $("#TimeSlotsFormdate").val(selectedStartDate);
                        $("#sendRequest-modal").modal("show");
                    }
            });
        });

        //SortListed Employee
        $(document).on("click", ".SortlistedEmployee", function()
        {
            const EmailTemplate = $(this).closest("tr").find(".EmailTemplate").val();
            const Interviewer = $(this).closest("tr").find(".Interviewer").val();
            const Round = $(this).closest("tr").find(".Round").val();
            const InterviewType = $(this).closest("tr").find(".InterviewType").val();

            if (!EmailTemplate)
            {
                toastr.error("Please select an Email Template before proceeding.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }
            let resort_id= $(this).data('resort_id');
            let ApplicantID= $(this).data('applicantid');
            let ApplicantStatus_id= $(this).data('applicantstatus_id');
            $("#Resort_id").val(resort_id);
            $("#ApplicantID").val(ApplicantID);
            $("#ApplicantStatus_id").val(ApplicantStatus_id);
            $("#Interviewer").val(Interviewer);
            $("#EmailTemplate").val(EmailTemplate);
            $("#InterviewType").val(InterviewType);
            $("#Round").val(Round);

            $("#sendRequest-modal").modal("show");
        });

        $('#InterviewRequestSentForm').validate({
            rules: {
                InterviewDate: {
                    required: true,
                }
            },
            messages :
            {
                InterviewDate: {
                    required: "Please Select Inteview Date.",
                }
            },
            submitHandler: function(form) {
                let Resort_id = $("#Resort_id").val();
                let ApplicantID = $("#ApplicantID").val();
                let ApplicantStatus_id = $("#ApplicantStatus_id").val();
                let Interviewer =$("#Interviewer").val();
                let EmailTemplate =$("#EmailTemplate").val();
                let Round =$("#Round").val();
                let InterviewType =$("#InterviewType").val();
                let InterviewDate = $('#InterviewDate').val();

                $.ajax({
                    url: "{{ route('resort.ta.ApplicantTimeZoneget') }}",
                    type: "POST",
                    data:{"InterviewDate":InterviewDate,"Round":Round,"InterviewType":InterviewType,"EmailTemplate":EmailTemplate,"Interviewer":Interviewer,"Resort_id":Resort_id,"ApplicantID":ApplicantID,"ApplicantStatus_id":ApplicantStatus_id,"_token":"{{ csrf_token()}}"},
                    success: function(response) {
                        if (response.success)
                        {

                            toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                            });
                            InterViewDate = response.InterviewDate;
                            $("#sendRequest-modal").modal("hide");
                            $("#TimeSlots-modal").modal("show");
                            $(".sendRequestTime-main").html(response.view);

                        }
                        else
                        {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                    // ,
                    // error: function(response) {
                    //     var errors = response.responseJSON;
                    //     var errs = '';
                    //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                    //         console.log(error);
                    //         errs += error + '<br>';
                    //     });
                    //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    // }
                });
            }
        });

        $('#TimeSlotsForm').validate({
            rules: {
                        SlotBook: {
                            required: function (element) {
                                // Require SlotBook only if both ManualTime fields are empty
                                return (
                                    $('[name="MalidivanManualTime"]').val().trim() === "" &&
                                    $('[name="ApplicantManualTime"]').val().trim() === ""
                                );
                            },
                        },
                        MalidivanManualTime: {
                            required: function (element) {
                                // Require ManualTime fields only if SlotBook is not selected
                                return $('[name="SlotBook"]:checked').length === 0;
                            },
                        },
                        ApplicantManualTime: {
                            required: function (element) {
                                // Same condition for the second ManualTime field
                                return $('[name="SlotBook"]:checked').length === 0;
                            },
                        },
                    },
                    messages: {
                        SlotBook: {
                            required: "Please select a valid time slot or enter a manual time.",
                        },
                        MalidivanManualTime: {
                            required: "Please enter Malidivan Manual Time or select a valid time slot.",
                        },
                        ApplicantManualTime: {
                            required: "Please enter Applicant Manual Time or select a valid time slot.",
                        },
                    },
            errorPlacement: function(error, element) {
                if (element.hasClass("Timezone_checkBox")) {
                    // Append error message after the .row_time element
                    element.closest(".row_time").after(error);
                } else {
                    error.insertAfter(element); // Default behavior
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.ta.InterviewRequest') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,

                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });


                            $("#sendRequest-modal").modal("hide");
                            $("#TimeSlots-modal").modal("hide");
                            $(".sendRequestTime-main").html(response.view);
                            $("#todoList-main").html( response.TodoDataview);
                            $("#Final_response_data").html(response.Final_response_data);
                            $("#sendRequestFinal-modal").modal("show");
                            datatablelist();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                });
            }
        });
        $(document).on("click", ".ApplicantShareLink", function () {
            let Interview_id = $(this).data("interview_id");
            let Round = $(this).data("rank_name");
            let InterviewType = $(this).data("round");
            const EmailTemplate = $(this).closest("tr").find(".EmailTemplate").val();

            if (!EmailTemplate)
            {
                toastr.error("Please select an Email Template before proceeding.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }

            console.log("Interview ID:", Interview_id);
            console.log("Round:", Round);
            console.log("InterviewType:", InterviewType);

            // Set values after the modal is fully initialized
            $("#shareMeetLink-modal").modal("show").on('shown.bs.modal', function () {
                $("#Interview_id").val(Interview_id);
                $("#Round1").val(Round).trigger("change");
                $("#InterviewType1").val(InterviewType).trigger("change");
                $("#EmailTemplate1").val(EmailTemplate);
            });

            datatablelist();
        });

        $('#shareMeetLinkForm').validate({
            rules: {
                MeetingLink: {
                    required: true,
                }
            },
            messages :
            {
                MeetingLink: {
                    required: "Please Enter Meeting Link.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('resort.ta.AddInterViewLink') }}",
                        type: "POST",
                        data:formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success)
                            {

                                toastr.success(response.message, "Success", {
                                            positionClass: 'toast-bottom-right'
                                });

                                    $("#reviewInview-modal").modal("show");
                                    $(".InterviewReviewData").html(response.Final_response_data);

                                $("#shareMeetLink-modal").modal("hide");
                                datatablelist();
                            }
                            else
                            {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                        // ,
                        // error: function(response) {
                        //     var errors = response.responseJSON;
                        //     var errs = '';
                        //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        //         console.log(error);
                        //         errs += error + '<br>';
                        //     });
                        //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        // }
                    });
            }
        });
        function datatablelist()
        {
            if ($.fn.DataTable.isDataTable('.table-applicants'))
            {
                $('.table-applicants').DataTable().destroy();
            }

            var divisionTable = $('.table-applicants').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bFilter: true,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: true,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('resort.ta.getApplicant')}}",
                        type: 'GET',
                        data: function(d) {
                            d.vacanccyId = $("#vacancy-id").val();
                            var searchTerm = $('.search').val();
                            d.searchTerm = $('.search').val();
                        }
                    },
                    columns: [
                        { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                            return `<div class="tableUser-block">
                                <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                                <span class="userApplicants-btn" data-id="${row.applicant_id}">${row.name}</span>
                            </div>`;
                        }},
                        { data: 'countryName', name: 'countryName' },
                        { data: 'passport_no', name: 'passport_no' },
                        { data: 'total_work_exp', name: 'total_work_exp', render: function (data) {
                            return `${data} year`;
                        }},
                        { data: 'email', name: 'email' },
                        { data: 'contact', name: 'contact' },
                        { data: 'Application_date', name: 'Application_date' },
                        { data: 'Stage', name: 'Stage'},
                        {
                        data: 'details-control',
                            name: 'details-control',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row) {
                                return `
                            <a class="a-link details-toggle" data-row-id="${row.id}"  data-status="${row.status}"  data-applicant_id="${row.applicant_id}" aria-expanded="false">
                                Interview Details
                            </a>`;
                            }
                        },
                        { data: 'action', name: 'action', orderable: false, searchable: false }
                    ]

                });
        }
        function DatatableGrid()
        {
            $.ajax({
                url:"{{ route('resort.ta.getApplicantWiseGridWise') }}",
                type: "post",
                data:
                {
                    id :$("#vacancy-id").val(),
                    searchTerm : $('.search').val()
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
        // $(document).on("click",".ApprovedOrSortListed",function(suc){
        //     let ApplicantID = $(this).attr('data-Progress_ApplicantID');
        //     let Rank = $(this).attr('data-Progress_Rank');
        //     let interviewRound = $(this).attr('data-interviewRound');


        //     let applicantstatusid = $(this).attr('data-progress_applicantstatusid');
        //         $.ajax({
        //             url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
        //             type: "POST",
        //             data:{"interviewRound":interviewRound,"ApplicantID":ApplicantID,"applicantstatusid":applicantstatusid,"Rank":Rank,"_token": "{{ csrf_token() }}"},
        //             success: function(response)
        //             {
        //                     if (response.success)
        //                     {
        //                         $(".userApplicants-wrapper").html(response.view);
        //                         DatatableGrid();
        //                         datatablelist();
        //                         // $(".userApplicants-btn").click();
        //                         $(".userApplicants-wrapper").removeClass('end-0');
        //                         toastr.success("Request Updated Successfully", "Success", {
        //                                 positionClass: 'toast-bottom-right'
        //                         });
        //                     }
        //             },
        //                 error: function(response) {
        //                     var errors = response.responseJSON;
        //                     var errs = '';
        //                     console.log(errors.errors);
        //                     $.each(errors.errors, function(key, error)
        //                     {
        //                         console.log(error);
        //                         errs += error + '<br>';
        //                     });
        //                     toastr.error(errs, { positionClass: 'toast-bottom-right'});
        //                 }
        //         });
        // });
        $(document).on("click", ".ApprovedOrSortListed", function () {
            // Cache the data attributes
            let ApplicantID = $(this).attr("data-Progress_ApplicantID");
            let Rank = $(this).attr("data-Progress_Rank");
            let interviewRound = $(this).attr("data-interviewRound");
            let applicantstatusid = $(this).attr("data-progress_applicantstatusid");

            if (Rank === "Complete" || Rank === "Rejected" || Rank == "Selected") {
                // Store them in hidden fields or temporary variables
                $("#EmailTemplateForm").data("ApplicantID", ApplicantID);
                $("#EmailTemplateForm").data("Rank", Rank);
                $("#EmailTemplateForm").data("interviewRound", interviewRound);
                $("#EmailTemplateForm").data("applicantstatusid", applicantstatusid);

                // Open the modal for email template selection
                $("#Email-template-selection-modal").modal("show");
            } else {
                // Directly make the AJAX call since no email needs to be sent
                makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, null);
            }
        });

        $(document).on("submit", "#EmailTemplateForm", function (e) {
            e.preventDefault();

            // Retrieve the cached data and selected template ID
            let ApplicantID = $(this).data("ApplicantID");
            let Rank = $(this).data("Rank");
            let interviewRound = $(this).data("interviewRound");
            let applicantstatusid = $(this).data("applicantstatusid");
            let emailTemplateID = $("#EmailTemplate-popup").val(); // Fixed selector issue

            if (!emailTemplateID) {
                toastr.error("Please select an email template.", "Error", {
                    positionClass: "toast-bottom-right",
                });
                return;
            }

            // Make the AJAX request with the email template ID
            makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, emailTemplateID);

            // Close the modal
            $("#Email-template-selection-modal").modal("hide");
        });

        // Function to make the AJAX request
        function makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, emailTemplateID) {
            // Remove ' Rank' and anything after it from interviewRound
            if (Rank && interviewRound.includes(" Complete")) {
                // console.log(interviewRound);
                interviewRound = interviewRound.split(" Complete")[0]; // Keep only the part before " Rank"
            }
            // console.log(interviewRound);
            $.ajax({
                url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
                type: "POST",
                data: {
                    interviewRound: interviewRound,
                    ApplicantID: ApplicantID,
                    applicantstatusid: applicantstatusid,
                    Rank: Rank,
                    emailTemplateID: emailTemplateID, // Can be null if no template is used
                    _token: "{{ csrf_token() }}",
                },
                success: function (response) {
                    if (response.success) {
                        $(".userApplicants-wrapper").html(response.view);
                        DatatableGrid();
                        datatablelist();
                        $(".userApplicants-wrapper").removeClass("end-0");
                        toastr.success("Request Updated Successfully", "Success", {
                            positionClass: "toast-bottom-right",
                        });
                    }
                },
                error: function (response) {
                    var errors = response.responseJSON;
                    var errs = "";
                    $.each(errors.errors, function (key, error) {
                        errs += error + "<br>";
                    });
                    toastr.error(errs, { positionClass: "toast-bottom-right" });
                },
            });
        }

        $(document).on('submit', '#ApplicantNoteForm', function(e) {
            e.preventDefault();


            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('resort.ta.ApplicantNote') }}",
                type: "POST",
                data: formData,
                processData: false, // Prevent jQuery from automatically processing the data
                contentType: false, // Prevent jQuery from setting contentType
                success: function(response) {
                    $('#respond-rejectModal').modal('hide');
                    if (response.success) {
                        $("#FreshHiringRequest").html(response.view);
                        $("#ApplicantsNotes-Model").modal('hide');

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    console.log(errors.errors);
                    $.each(errors.errors, function(key, error) {
                        console.log(error);
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        });
        $(document).on('submit', '#RoundWiseForm', function(e) {
            e.preventDefault();


                let formData = new FormData(this);

            $.ajax({
                url: "{{ route('resort.ta.RoundWiseForm') }}",
                type: "POST",
                data: formData,
                processData: false, // Prevent jQuery from automatically processing the data
                contentType: false, // Prevent jQuery from setting contentType
                success: function(response) {
                    $('#respond-rejectModal').modal('hide');
                    if (response.success) {
                        $("#FreshHiringRequest").html(response.view);
                        $("#ApplicantsNotes-Model").modal('hide');

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    console.log(errors.errors);
                    $.each(errors.errors, function(key, error) {
                        console.log(error);
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        });
    </script>
@endsection

