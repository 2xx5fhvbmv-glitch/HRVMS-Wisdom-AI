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
                <div class="card-title">
                    <h3>All To Do List</h3>
                </div>
                <table class="table"  id="SortlistedApplicants" >
                    <thead>
                        <tr>
                            <th>Applicants</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Applied Date</th>
                            <th>Stage</th>
                            <th>Nationality	</th>
                            <th>InterViewDate</th>
                            <th>Malidivan Time</th>
                            <th>Applicant Time</th>
                            <th>Interview Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>


                </table>

            </div>


        </div>
    </div>

</div>
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
                        <input type="text" id="Resort_id" name="Resort_id">
                        <input type="text" id="ApplicantID" name="ApplicantID">
                        <input type="text" id="ApplicantStatus_id" name="ApplicantStatus_id">
                        <input type="text" id="Calender_ta_id" name="ta_id">
                        <input type="date"  id="TimeSlotsFormdate" name="TimeSlotsFormdate">

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
                    <a href="javascript:void(0)"  data-bs-dismiss="modal"class="btn btn-theme" >Submit</a>
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
    <div class="userApplicants-wrapper ">

    </div>
    @endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>

$(document).ready(function() {


    $('#SortlistedApplicants tbody').empty();
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
            order:[[12, 'desc']],
            ajax: {
                url: '{{ route("resort.ta.shortlistedapplicants") }}',
                type: 'GET',
            },
            columns: [

                { data: 'Applicants', name: 'Applicants', className: 'text-nowrap' },
                { data: 'Position', name: 'Position', className: 'text-nowrap' },
                { data: 'Email', name: 'Email', className: 'text-nowrap' },
                { data: 'Contact', name: 'Contact', className: 'text-nowrap' },
                { data: 'AppliedDate', name: 'AppliedDate', className: 'text-nowrap' },
                { data: 'Stage', name: 'Stage', className: 'text-nowrap' },
                { data: 'Nationality', name: 'Nationality', className: 'text-nowrap' },
                { data: 'InterViewDate', name: 'InterViewDate', className: 'text-nowrap' },
                { data: 'MalidivanTime', name: 'MalidivanTime', className: 'text-nowrap' },
                { data: 'ApplicantTime', name: 'ApplicantTime', className: 'text-nowrap' },
                { data: 'InterviewStatus', name: 'InterviewStatus', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', className: 'text-nowrap' },
                {data:'created_at', visible:false,searchable:false},

            ]
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
    $(document).on("click",".closeSlider", function (e) {
        e.preventDefault();

        $userApplicantsWrapper.toggleClass("end-0");
    });
});
 function makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, emailTemplateID) 
 {

    console.log(Rank && interviewRound.includes(" Complete"));
    if (Rank && interviewRound.includes(" Complete")) {
        // console.log(interviewRound);
        interviewRound = interviewRound.split(" Complete")[0]; // Keep only the part before " Rank"
    }
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
                 $('#SortlistedApplicants').DataTable().ajax.reload();
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
$(document).on("click", ".Timezone_checkBox", function() {

        $(".row_time").not($(this).closest(".row_time")).removeClass("active").find("input").prop("disabled", false);

            $(this).closest(".row_time").toggleClass("active");
            let location = $(this).data('id');

            if ($(this).closest(".row_time").hasClass("active")) {
                $(".row_time").not($(this).closest(".row_time")).find("input").prop("disabled", true);
                $('[name^="ApplicantInterviewtime"]').val('');
                $('[name^="ResortInterviewtime"]').val('');

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


                let resort_id= $(this).data('resort_id');
                let ApplicantID= $(this).data('applicantid');
                let ApplicantStatus_id= $(this).data('applicantstatus_id');
                $("#Resort_id").val(resort_id);
                $("#ApplicantID").val(ApplicantID);
                $("#ApplicantStatus_id").val(ApplicantStatus_id);
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


                $.ajax({
                    url: "{{ route('resort.ta.ApplicantTimeZoneget') }}",
                    type: "POST",
                    data:{Resort_id:Resort_id, ApplicantID:ApplicantID, ApplicantStatus_id:ApplicantStatus_id,"_token":"{{ csrf_token()}}"},

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
                    required: true,
                },

            },
            messages: {
                SlotBook: {
                    required: "Please select a valid time slot  .",
                }
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
                            $('#SortlistedApplicants').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                });
            }
        });
        $(document).on("click", ".ApplicantShareLink", function() {
            let Interview_id = $(this).data("interview_id");
            $("#Interview_id").val(Interview_id);
            $("#shareMeetLink-modal").modal("show");

        }) ;


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

                            InterViewDate = response.InterviewDate;

                            $("#shareMeetLink-modal").modal("hide");
                            $('#SortlistedApplicants').DataTable().ajax.reload();

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
</script>
@endsection

