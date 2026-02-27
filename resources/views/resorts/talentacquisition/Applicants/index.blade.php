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
                    <div class="col-auto ms-auto">
                        <a href="{{ route('resort.ta.shortlisted',$id) }}" class="btn btn-themeLightNew">Shortlisted Applicants</a>
                    </div>
                    <div class="col-auto">
                        <a href="{{route('resort.ta.UpcomingApplicants')}}" class="btn btn-themeLightNew">Upcoming Interviews</a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('resort.ta.RejectedApplicants') }}" class="btn btn-themeLightNew">Rejected Applications</a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('resort.ta.ReviewReminders') }}" class="btn btn-themeLightNew">Review Reminders</a>
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
                                    <th>Invitation Status</th>
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Interview Details </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-2" >
                        <table class="table table-lable" id="popupInterviewDetails">
                        </table>
                    </div>
                    <!-- <div class="text-center mb-2">
                        <a href="#shareMeetLink-modal" data-bs-toggle="modal" data-bs-dismiss="modal"
                            class="btn btn-themeSkyblue btn-sm">Share
                            Meeting Link</a>
                    </div> -->
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
                        <div class="mb-3">
                            <label class="form-label">Meeting Link</label>
                            <input type="text" class="form-control" name="MeetingLink" placeholder="Enter Meeting Link (Google Meet, Zoom, etc.)">
                        </div>
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
    <div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Download File</h5>
                
                    <a href="" class="btn btn-smbtn-primary downloadLink" target="_blank"> Download</a>
                
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                    <div class="modal-body">
                    
                            <div class=" ratio ratio-21x9" id="ViewModeOfFiles">

                            </div>
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" id="document-dismiss" class="btn btn-themeGray ms-auto">Cancel</a>
                    </div>
    
            </div>
        </div>
    </div>

    {{-- Rejection Confirmation Modal --}}
    <div class="modal fade" id="rejectCandidate-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectCandidateForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            Are you sure you want to reject this candidate?
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Email Template</label>
                            <select class="form-control" name="emailTemplateID" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason (Optional)</label>
                            <textarea class="form-control" name="rejectionReason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                        </div>
                        <input type="hidden" name="ApplicantID" id="reject_ApplicantID">
                        <input type="hidden" name="applicantstatusid" id="reject_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Selection Confirmation Modal --}}
    <div class="modal fade" id="selectCandidate-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="selectCandidateForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            Are you sure you want to select this candidate?
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Email Template</label>
                            <select class="form-control" name="emailTemplateID" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <input type="hidden" name="ApplicantID" id="select_ApplicantID">
                        <input type="hidden" name="applicantstatusid" id="select_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-success">Confirm Select</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Offer Letter Modal --}}
    <div class="modal fade" id="offerLetter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Offer Letter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="offerLetterForm" enctype="multipart/form-data">
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
                            <label class="form-label">Upload Offer Letter (PDF)</label>
                            <input type="file" class="form-control" name="offer_letter" accept=".pdf" required>
                        </div>
                        <div class="mb-3 d-none" id="viewOfferLetterWrapper">
                            <a href="#" target="_blank" id="viewOfferLetterLink" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye me-1"></i>View Offer Letter
                            </a>
                        </div>
                        <input type="hidden" name="applicant_id" id="offerLetter_ApplicantID">
                        <input type="hidden" name="applicant_status_id" id="offerLetter_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Send Offer Letter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Contract Modal --}}
    <div class="modal fade" id="contract-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="contractForm" enctype="multipart/form-data">
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
                            <label class="form-label">Upload Contract (PDF)</label>
                            <input type="file" class="form-control" name="contract_file" accept=".pdf" required>
                        </div>
                        <div class="mb-3 d-none" id="viewContractWrapper">
                            <a href="#" target="_blank" id="viewContractLink" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye me-1"></i>View Contract
                            </a>
                        </div>
                        <input type="hidden" name="applicant_id" id="contract_ApplicantID">
                        <input type="hidden" name="applicant_status_id" id="contract_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Send Contract</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
<style>
    .toast-bottom-right {
    bottom: 12px;
    right: 12px;
    top: auto !important;
}
.modal.show ~ .modal.show {
    z-index: 1062;
}
</style>
@endsection

@section('import-scripts')
    <script>
        $(document).ready(function() {
            datatablelist();
            $('.table-applicants tbody').empty();

            $(document).on('click', 'a.details-toggle', function () {
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
                                        let newTag = 'send Link';
                                        if(response.data.InterviewStatus == 'Slot Not Booked' || response.data.InterviewStatus == 'Invitation Rejected')
                                        {
                                            newTag =`<a href="javascript:void(0)"
                                                data-Resort_id="${response.data.Resort_id}"
                                                data-ApplicantID="${response.data.ApplicantID}"
                                                data-ApplicantStatus_id="${response.data.ApplicantStatus_id}"
                                                class="btn btn-themeSkyblue btn-small SortlistedEmployee">Send Interview Invitation</a>`;
                                        }
                                        else if(response.data.InterviewStatus == "Invitation Sent")
                                        {
                                            newTag =`<span class="badge bg-info text-white">Invitation Sent - Awaiting Response</span>`;
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
                                                                        <option value="{{ $e->id}}" data-name="{{ $e->TempleteName }}">{{ $e->TempleteName }}</option>
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

            $(document).on('click', '.gridview-link', function()
            {
                let rowId = $(this).data('row-id');
                let status = $(this).data('status');
                let applicant_id = $(this).data('applicant_id');
                if(status != "Sortlisted By Wisdom AI") {
                    if(status == "Complete") {
                        toastr.error('Please Wait For the next Responsed .', { 
                            positionClass: 'toast-bottom-right', 
                            timeOut: 5000 
                        });
                        return false;
                    }
                    else if(status == "Selected") {
                        toastr.error('This Applicant Already Selected By GM.', { 
                            positionClass: 'toast-bottom-right',
                            timeOut: 5000 
                        });
                    }
                    else {
                        let url = "{{ route('resort.ta.ApplicantWiseStatus') }}";
                        $.ajax({
                            url: url,
                            type: "POST",
                            data:{"Applicant_id":applicant_id,"rowId":rowId,"status":status,"_token": "{{ csrf_token() }}"},
                            success: function(response) {
                                if (response.success) {
                                    console.log(response);
                                    let newTag = 'send Link';
                                    if(response.data.InterviewStatus == 'Slot Not Booked' || response.data.InterviewStatus == 'Invitation Rejected') {
                                        newTag =`<a href="javascript:void(0)"
                                            data-Resort_id="${response.data.Resort_id}"
                                            data-ApplicantID="${response.data.ApplicantID}"
                                            data-ApplicantStatus_id="${response.data.ApplicantStatus_id}"
                                            class="btn btn-themeSkyblue btn-small SortlistedEmployee">Send Interview Request</a>`;
                                    }
                                    else if(response.data.InterviewStatus == "Invitation Sent") {
                                        newTag =`<span class="badge bg-info text-white">Invitation Sent - Awaiting Response</span>`;
                                    }
                                    else if(response.data.InterviewStatus =="Slot Booked" && !isNaN(response.data.MeetingLink)) {
                                        newTag =`<a class="btn btn-themeSkyblue btn-small ApplicantShareLink"
                                        data-round="${response.data.round}"
                                            data-rank_name="${response.data.rank_name}"
                                        data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Add Interview Link</a>`;
                                    }
                                    else {
                                        newTag =`<a href="${response.data.MeetingLink}" target="_blank" class="btn btn-themeSkyblue btn-small"
                                        data-rond="${response.data.round}"
                                        data-rank_name="${response.data.rank_name}"
                                        data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Start Interview  </a>`;
                                    }

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

                                    $("#intDetail-modal").modal('show');
                                    $("#popupInterviewDetails").html(newRow);
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
                                    toastr.error(errs, { positionClass: 'toast-bottom-right', timeOut: 5000 });
                                } else {
                                    // Handle other types of errors
                                    toastr.error('An unexpected error occurred. Please try again.', { positionClass: 'toast-bottom-right', timeOut: 5000 });
                                    console.log(response); // Debugging log for unexpected errors
                                }
                            }
                        });
                    }
                }
                else {
                    toastr.error('Please Wait HR Response.', { positionClass: 'toast-bottom-right', timeOut: 5000 });
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


            $(document).on("click",".closeSlider", function (e) {
                e.preventDefault();

                $userApplicantsWrapper.toggleClass("end-0");
            });

             $(document).on("click", "#document-dismiss", function () {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    $("#ViewModeOfFiles").empty();
                    $(".downloadLink").attr("href", "");
                    $(".userApplicants-wrapper").addClass("end-0");
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
        // Multi-select time slots - click on row for Safari compatibility
        $(document).on("click", ".row_time:not(.disable)", function(e) {
            if ($(e.target).is('input[type="hidden"]')) return;

            var $row = $(this);
            var $checkbox = $row.find(".Timezone_checkBox");

            // Toggle this row
            $row.toggleClass("active");
            $checkbox.prop("checked", $row.hasClass("active"));

            // Clear manual time fields when selecting slots
            $('[name="MalidivanManualTime"]').val('');
            $('[name="ApplicantManualTime"]').val('');
            $('[name="MalidivanManualTime1"]').val('');
            $('[name="ApplicantManualTime1"]').val('');

            // Collect all selected slot times
            var resortTimes = [];
            var applicantTimes = [];
            $(".row_time.active .Timezone_checkBox").each(function() {
                resortTimes.push($(this).data('resortinterviewtime'));
                applicantTimes.push($(this).data('applicantinterviewtime'));
            });
            $("#ResortInterviewtime_collected").val(resortTimes.join(', '));
            $("#ApplicantInterviewtime_collected").val(applicantTimes.join(', '));
        });

        // Clear selected slots when manual time is focused
        $(document).on("focus", '[name="MalidivanManualTime"], [name="ApplicantManualTime"]', function () {
            $(".row_time").removeClass("active");
            $(".row_time .Timezone_checkBox").prop("checked", false);
            $("#ResortInterviewtime_collected").val('');
            $("#ApplicantInterviewtime_collected").val('');
        });

        $(document).on("change", '[name="MalidivanManualTime"]', function () {
            const timeValue = $(this).val();
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":");
                const period = hours >= 12 ? "PM" : "AM";
                const formattedHours = hours % 12 || 12;
                let MalidivanManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1);
            }
        });
        $(document).on("change", '[name="ApplicantManualTime"]', function () {
            const timeValue = $(this).val();
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":");
                const period = hours >= 12 ? "PM" : "AM";
                const formattedHours = hours % 12 || 12;
                let ApplicantManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="ApplicantManualTime1"]').val(ApplicantManualTime1);
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
                navLinks: false, // Disable built-in link navigation
                selectable: true,
                select: function(start, end, jsEvent, view) {
                    jsEvent.preventDefault(); // Prevent redirect
                    const selectedDate = start.format('YYYY-MM-DD');
                    $("#InterviewDate").val(selectedDate);
                    $("#TimeSlotsFormdate").val(selectedDate);
                    $("#sendRequest-modal").modal("show");
                }
            });
        });

        // Update action button when email template changes
        $(document).on("change", ".EmailTemplate", function() {
            var $row = $(this).closest("tr");
            var $btn = $row.find(".SortlistedEmployee");
            if (!$btn.length) return;

            var templateName = $(this).find("option:selected").data("name") || '';
            var isRejection = templateName.toLowerCase().indexOf('reject') !== -1;

            if (isRejection) {
                $btn.text('Send Rejection Email').removeClass('btn-themeSkyblue').addClass('btn-danger');
            } else {
                $btn.text('Send Interview Invitation').removeClass('btn-danger').addClass('btn-themeSkyblue');
            }
        });

        //SortListed Employee
        $(document).on("click", ".SortlistedEmployee", function()
        {
            var $row = $(this).closest("tr");
            const EmailTemplate = $row.find(".EmailTemplate").val();
            const EmailTemplateName = $row.find(".EmailTemplate option:selected").data("name") || '';
            const Interviewer = $row.find(".Interviewer").val();
            const Round = $row.find(".Round").val();
            const InterviewType = $row.find(".InterviewType").val();

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

            // If rejection template selected, send rejection email directly
            var isRejection = EmailTemplateName.toLowerCase().indexOf('reject') !== -1;
            if (isRejection) {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Sending...');
                $.ajax({
                    url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
                    type: "POST",
                    data: {
                        ApplicantID: ApplicantID,
                        applicantstatusid: ApplicantStatus_id,
                        Rank: "Rejected",
                        Progress_Rank: "Rejected",
                        interviewRound: Round || "HR",
                        emailTemplateID: EmailTemplate,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("Rejection email sent successfully!", "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            datatablelist();
                        } else {
                            toastr.error(response.message || "Something went wrong.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function() {
                        toastr.error("Something went wrong. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Send Rejection Email');
                    }
                });
                return false;
            }

            // Normal flow — open time slots modal
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
                "SlotBook[]": {
                    required: function () {
                        return (
                            $('[name="MalidivanManualTime"]').val().trim() === "" &&
                            $('[name="ApplicantManualTime"]').val().trim() === ""
                        );
                    },
                },
                MalidivanManualTime: {
                    required: function () {
                        return $('[name="SlotBook[]"]:checked').length === 0;
                    },
                },
                ApplicantManualTime: {
                    required: function () {
                        return $('[name="SlotBook[]"]:checked').length === 0;
                    },
                },
            },
            messages: {
                "SlotBook[]": {
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
                    element.closest(".sendRequestTime-main").find(".block").after(error);
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                var $submitBtn = $(form).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).text('Submitting...');
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
                    },
                    error: function() {
                        toastr.error("Something went wrong. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('Submit');
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

            // Set values and show only the Share Meeting Link modal
            $("#Interview_id").val(Interview_id);
            $("#Round1").val(Round).trigger("change");
            $("#InterviewType1").val(InterviewType).trigger("change");
            $("#EmailTemplate1").val(EmailTemplate);
            $("#shareMeetLink-modal").modal("show");
        });

        $(document).on("click",".DownloadFile", function () {
            let fileId = $(this).data("id");
            let fileFlag = $(this).data("flag");

            $.ajax({
                url: "{{ route('resort.ta.DownloadFile') }}",
                type: "POST",
                data: {
                    id: fileId,
                    flag: fileFlag
                },
                success: function(response) {
                    if (response.success) 
                    {
                        $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                        // Show the modal with the loading message
                        $("#bdVisa-iframeModel-modal-lg").modal('show');
                         let fileUrl = response.NewURLshow;
                        $(".downloadLink").attr("href", fileUrl);
                        
                        let mimeType = response.mimeType.toLowerCase();
                        let iframeTypes = [
                                            'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                                            'application/pdf', 'text/plain',                   // PDF & Text
                                            'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                                        ];
                        let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                        // Clear the loading message and show the actual content
                        if (imageTypes.includes(mimeType)) 
                        {
                            $("#ViewModeOfFiles").html(`
                                <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                        } 
                        // If file type is supported for iframe display
                        else if (iframeTypes.includes(mimeType)) {
                            $("#ViewModeOfFiles").html(`
                                <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                            `);
                        } 
                        // If file is a ZIP or unsupported type → Download it
                        else {
                            $("#bdVisa-iframeModel-modal-lg").modal('hide');
                            window.location.href = fileUrl; // Triggers download automatically
                        }
                    }
                    else
                    {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
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
                var $submitBtn = $(form).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).text('Submitting...');
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
                    },
                    error: function() {
                        toastr.error("Something went wrong. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('Submit');
                    }
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
                    order:[[10, 'desc']],
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
                        { data: 'InvitationStatus', name: 'InvitationStatus', orderable: false, searchable: false },
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
                    ],
                    drawCallback: function() {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
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

        // Reject Candidate - open modal
        $(document).on("click", ".rejectCandidateBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#reject_ApplicantID").val(applicantId);
            $("#reject_applicantstatusid").val(applicantStatusId);
            $("#rejectCandidate-modal").modal("show");
        });

        // Reject Candidate - form submit
        $('#rejectCandidateForm').on('submit', function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Rejecting...');

            $.ajax({
                url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
                type: "POST",
                data: {
                    ApplicantID: $('#reject_ApplicantID').val(),
                    applicantstatusid: $('#reject_applicantstatusid').val(),
                    Rank: "Rejected",
                    interviewRound: "select",
                    emailTemplateID: $(this).find('[name="emailTemplateID"]').val(),
                    rejectionReason: $(this).find('[name="rejectionReason"]').val(),
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || "Candidate rejected successfully!", "Success", { positionClass: 'toast-bottom-right' });
                        $("#rejectCandidate-modal").modal("hide");
                        $('.table-applicants').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function() {
                    toastr.error("Something went wrong. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Confirm Reject');
                }
            });
        });

        // Select Candidate - open modal
        $(document).on("click", ".selectCandidateBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#select_ApplicantID").val(applicantId);
            $("#select_applicantstatusid").val(applicantStatusId);
            $("#selectCandidate-modal").modal("show");
        });

        // Select Candidate - form submit
        $('#selectCandidateForm').on('submit', function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Selecting...');

            $.ajax({
                url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
                type: "POST",
                data: {
                    ApplicantID: $('#select_ApplicantID').val(),
                    applicantstatusid: $('#select_applicantstatusid').val(),
                    Rank: "Selected",
                    interviewRound: "select",
                    emailTemplateID: $(this).find('[name="emailTemplateID"]').val(),
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || "Candidate selected successfully!", "Success", { positionClass: 'toast-bottom-right' });
                        $("#selectCandidate-modal").modal("hide");
                        $('.table-applicants').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message || "Something went wrong.", "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function() {
                    toastr.error("Something went wrong. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Confirm Select');
                }
            });
        });

        // Send Offer Letter - open modal
        $(document).on("click", ".sendOfferLetterBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#offerLetter_ApplicantID").val(applicantId);
            $("#offerLetter_applicantstatusid").val(applicantStatusId);
            $("#offerLetter-modal").modal("show");
        });

        // Send Offer Letter - form submit
        $('#offerLetterForm').on('submit', function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Sending...');
            var formData = new FormData(this);

            $.ajax({
                url: "{{ route('resort.ta.sendOfferLetter') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $("#offerLetter-modal").modal("hide");
                        $('#offerLetterForm')[0].reset();
                        $('.table-applicants').DataTable().ajax.reload();
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
                    $submitBtn.prop('disabled', false).text('Send Offer Letter');
                }
            });
        });

        // Send Contract - open modal
        $(document).on("click", ".sendContractBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#contract_ApplicantID").val(applicantId);
            $("#contract_applicantstatusid").val(applicantStatusId);
            $("#contract-modal").modal("show");
        });

        // Send Contract - form submit
        $('#contractForm').on('submit', function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Sending...');
            var formData = new FormData(this);

            $.ajax({
                url: "{{ route('resort.ta.sendContract') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $("#contract-modal").modal("hide");
                        $('#contractForm')[0].reset();
                        $('.table-applicants').DataTable().ajax.reload();
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
                    $submitBtn.prop('disabled', false).text('Send Contract');
                }
            });
        });
    </script>
@endsection

