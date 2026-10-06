@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
    <style>
        #ta-applicants-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ta-applicants-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding" id="ta-applicants-hero">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="ta-tabnav">
                            <a href="{{ route('resort.ta.shortlistedapplicants') }}">Shortlisted Applicants</a>
                            <a href="{{route('resort.ta.UpcomingApplicants')}}">Upcoming Interviews</a>
                            <a href="{{ route('resort.ta.RejectedApplicants') }}">Rejected Applications</a>
                            <a href="{{ route('resort.ta.ReviewReminders') }}">Review Reminders</a>
                        </div>
                    </div>
                </div>
            </div>
            <div id="apx">
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6 apx-listonly d-none">
                            <select class="form-select dd-native-select" id="apxStage"><option value="" selected>All stages</option><option value="^Sortlisted( By Wisdom AI)?$">Shortlisted</option><option value="^Round$">Interview round</option><option value="^Complete$">Round complete</option><option value="^Selected$">Selected</option><option value="^Offer Letter">Offer letter</option><option value="^Contract">Contract</option></select>
                            <div class="dd" data-target="#apxStage">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All stages</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Stage">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All stages</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Sortlisted( By Wisdom AI)?$"><span class="dd-nm">Shortlisted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Round$"><span class="dd-nm">Interview round</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Complete$"><span class="dd-nm">Round complete</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Selected$"><span class="dd-nm">Selected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Offer Letter"><span class="dd-nm">Offer letter</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Contract"><span class="dd-nm">Contract</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6 apx-listonly d-none">
                            <select class="form-select dd-native-select" id="apxInv"><option value="" selected>All invitations</option><option value="^Slot Booked$">Accepted</option><option value="^(Slot Not Booked|Active)$">Pending</option><option value="^Invitation Rejected$">Declined</option><option value="^Invitation Sent$">Invitation sent</option><option value="^Pending Review$">Pending review</option></select>
                            <div class="dd" data-target="#apxInv">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All invitations</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Invitation status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All invitations</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Slot Booked$"><span class="dd-nm">Accepted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^(Slot Not Booked|Active)$"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Invitation Rejected$"><span class="dd-nm">Declined</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Invitation Sent$"><span class="dd-nm">Invitation sent</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="^Pending Review$"><span class="dd-nm">Pending review</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="#" class="btn btn-grid active"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="#" class="btn btn-list"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
                    <div class="table-responsive">
                        <table class="apx-tbl table-applicants w-100">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Passport no.</th>
                                    <th>Contact</th>
                                    <th>Applied</th>
                                    <th>Stage</th>
                                    <th>Invitation</th>
                                    <th class="r">Action</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main">
                    <div id="grid_main_view">

                    </div>
                </div>
            </div>

            {{-- Frosted kebab menu (inside #apx, outside the scroll areas so it is never clipped) --}}
            <div class="apx-menu" id="apxMenu" role="menu">
                <div class="kmi userApplicants-btn" role="menuitem" tabindex="0" data-m="view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View profile</div>
                @if($isHrDepartment)
                <div class="kmi gridview-link" role="menuitem" tabindex="0" data-m="interview"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>Interview details</div>
                @endif
                <div class="kmi ApplicantsNotes" role="menuitem" tabindex="0" data-m="note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>Add note</div>
                <div class="kmi waiInsightsBtn" role="menuitem" tabindex="0" data-m="wai"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/></svg>WAI insights</div>
            </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="vacancy-id" value="{{ $id }}">

    <div class="modal fade apx-glass" id="ApplicantsNotes-Model" tabindex="-1" aria-labelledby="apxNoteTtl" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered apx-dlg-note">
            <div class="modal-content apx-gm">
                <form id="ApplicantNoteForm">
                    @csrf
                    <div class="apx-gh">
                        <div><div class="ttl" id="apxNoteTtl">Note</div><div class="sub" id="apxNoteSub"></div></div>
                        <button type="button" class="apx-gx" data-bs-dismiss="modal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="apx-gb">
                        <textarea class="apx-ta" rows="7" id="ApplicantNote" name="ApplicantNote" maxlength="250" placeholder="Write a note about this applicant…"></textarea>
                        <input type="hidden" id="Applicant_id" name="Applicant_id">
                        <div class="apx-cap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Internal note. It is not shared with the applicant.</div>
                    </div>
                    <div class="apx-gf">
                        <button type="button" class="apx-btn ghost" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="apx-btn solid">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal -->

    <div class="modal fade apx-glass" id="intDetail-modal" tabindex="-1" aria-labelledby="apxIvTtl" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered apx-dlg-iv">
            <div class="modal-content apx-gm">
                <div class="apx-gh">
                    <div><div class="ttl" id="apxIvTtl">Interview Details</div><div class="sub" id="apxIvSub"></div></div>
                    <button type="button" class="apx-gx" data-bs-dismiss="modal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <div class="apx-gb">
                    <div class="table-responsive">
                        <table class="table table-lable" id="popupInterviewDetails">
                        </table>
                    </div>
                </div>
                <div class="apx-gf">
                    <button type="button" class="apx-btn ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="apx-btn solid" data-bs-dismiss="modal">Submit</button>
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
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-primary">Submit</a>
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
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>
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
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="sendRequestFinal-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
                </div>
                <div class="modal-body pb-0">
                    <div class="table-responsive">
                        <table class="table table-sendRequestFinal w-100">
                            <tbody id="Final_response_data">

                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" id="review_interview_id" value="">
                    <input type="hidden" id="review_email_template_id" value="">
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="javascript:void(0)" id="cancelPendingInterview" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <a href="javascript:void(0)" id="confirmSendInterviewEmail" class="btn ta-btn-attention">Submit</a>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmCancelSlot-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Interview Slot</h5>
                </div>
                <div class="modal-body">
                    <p>If you cancel, all saved slot information will be deleted and you will need to book a slot again.</p>
                    <p><strong>Are you sure?</strong></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="javascript:void(0)" id="cancelSlotNo" class="btn ta-btn-secondary ms-auto">No, Go Back</a>
                    <a href="javascript:void(0)" id="cancelSlotYes" class="btn ta-btn-critical">Yes, Delete Slot</a>
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
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Confirmation Modal for Interview Progress Actions (available to all roles) --}}
    <div class="modal fade" id="confirm-action-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirm-action-message">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ta-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn ta-btn-primary" id="confirm-action-yes">Yes, Proceed</button>
                </div>
            </div>
        </div>
    </div>

    @if($isHrDepartment)
    <div class="modal fade" id="Email-template-selection-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Select Email Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='EmailTemplateForm'>
                    @csrf
                    <div class="modal-body">
                    <select class="form-control dd-native-select EmailTemplate" name="EmailTemplate-popup" id="EmailTemplate-popup" required>
                        <option selected disabled value="">Select Email Template</option>
                        @foreach ($EmailTamplete as $e)
                            <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#EmailTemplate-popup">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select Email Template</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Email Template">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach ($EmailTamplete as $e)
                                <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 mt-3" id="rejectionReasonGroup" style="display:none;">
                        <label class="form-label">Rejection Reason<span class="text-danger">*</span></label>
                        <textarea class="form-control" name="rejectionReason" id="rejectionReasonText" rows="3" placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @include('partials._file_view_modal', ['cancelId' => 'document-dismiss'])

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
                            <select class="form-control dd-native-select" name="emailTemplateID" id="rejectEmailTemplateID" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#rejectEmailTemplateID">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Email Template</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Email Template">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if(isset($EmailTamplete))
                                        @foreach ($EmailTamplete as $e)
                                        <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason (Optional)</label>
                            <textarea class="form-control" name="rejectionReason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                        </div>
                        <input type="hidden" name="ApplicantID" id="reject_ApplicantID">
                        <input type="hidden" name="applicantstatusid" id="reject_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-critical">Confirm Reject</button>
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
                            <select class="form-control dd-native-select" name="emailTemplateID" id="selectEmailTemplateID" required>
                                <option selected disabled value="">Select Email Template</option>
                                @if(isset($EmailTamplete))
                                @foreach ($EmailTamplete as $e)
                                    <option value="{{ $e->id }}">{{ $e->TempleteName }}</option>
                                @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#selectEmailTemplateID">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Email Template</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Email Template">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if(isset($EmailTamplete))
                                        @foreach ($EmailTamplete as $e)
                                        <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="ApplicantID" id="select_ApplicantID">
                        <input type="hidden" name="applicantstatusid" id="select_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-celebrate">Confirm Select</button>
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
                        @if(isset($offerLetterTemplates) && $offerLetterTemplates->count() > 0)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Template</label>
                                <select name="template_id" class="form-select dd-native-select" id="offerLetterTemplateSelect">
                                    @foreach($offerLetterTemplates as $tpl)
                                        <option value="{{ $tpl->id }}" {{ $tpl->is_default ? 'selected' : '' }}>
                                            {{ $tpl->name }}{{ $tpl->is_default ? ' (Default)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @php
                                    $hasDefaultOfferTpl = $offerLetterTemplates->contains('is_default', true);
                                    $selectedOfferTpl = $hasDefaultOfferTpl ? $offerLetterTemplates->first(fn($t) => $t->is_default) : $offerLetterTemplates->first();
                                @endphp
                                <div class="dd" data-target="#offerLetterTemplateSelect">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedOfferTpl ? $selectedOfferTpl->name . ($selectedOfferTpl->is_default ? ' (Default)' : '') : 'Select Template' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Offer Letter Template">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                        <div class="dd-scroll">
                                            @foreach($offerLetterTemplates as $tpl)
                                            <div class="dd-item{{ ($tpl->is_default || (!$hasDefaultOfferTpl && $loop->first)) ? ' active' : '' }}" role="option" data-value="{{ $tpl->id }}"><span class="dd-nm">{{ $tpl->name }}{{ $tpl->is_default ? ' (Default)' : '' }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size:13px;">
                                <i class="fa-solid fa-file-word me-1"></i>
                                The offer letter will be auto-generated from the selected DOCX template with all placeholders filled in.
                            </p>
                        @else
                            <p class="text-warning mb-3">
                                <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                No templates uploaded yet. <a href="{{ route('resort.ta.offerLetterTemplates.index') }}">Upload one</a> or upload a PDF manually below.
                            </p>
                        @endif
                        {{-- Manual PDF upload fallback --}}
                        <div id="offerLetterUploadSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Upload Offer Letter (PDF)</label>
                                <input type="file" class="form-control" name="offer_letter" accept=".pdf">
                            </div>
                        </div>
                        <div>
                            <a href="javascript:void(0)" id="toggleOfferLetterUpload" class="text-muted small">
                                <i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually
                            </a>
                        </div>
                        <input type="hidden" name="applicant_id" id="offerLetter_ApplicantID">
                        <input type="hidden" name="applicant_status_id" id="offerLetter_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-attention">Send Offer Letter</button>
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
                        @if(isset($contractTemplates) && $contractTemplates->count() > 0)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Template</label>
                                <select name="template_id" class="form-select dd-native-select" id="contractTemplateSelect">
                                    @foreach($contractTemplates as $tpl)
                                        <option value="{{ $tpl->id }}" {{ $tpl->is_default ? 'selected' : '' }}>
                                            {{ $tpl->name }}{{ $tpl->is_default ? ' (Default)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @php
                                    $hasDefaultContractTpl = $contractTemplates->contains('is_default', true);
                                    $selectedContractTpl = $hasDefaultContractTpl ? $contractTemplates->first(fn($t) => $t->is_default) : $contractTemplates->first();
                                @endphp
                                <div class="dd" data-target="#contractTemplateSelect">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedContractTpl ? $selectedContractTpl->name . ($selectedContractTpl->is_default ? ' (Default)' : '') : 'Select Template' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Contract Template">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                        <div class="dd-scroll">
                                            @foreach($contractTemplates as $tpl)
                                            <div class="dd-item{{ ($tpl->is_default || (!$hasDefaultContractTpl && $loop->first)) ? ' active' : '' }}" role="option" data-value="{{ $tpl->id }}"><span class="dd-nm">{{ $tpl->name }}{{ $tpl->is_default ? ' (Default)' : '' }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size:13px;">
                                <i class="fa-solid fa-file-word me-1"></i>
                                The contract will be auto-generated from the selected DOCX template with all placeholders filled in.
                            </p>
                        @else
                            <p class="text-warning mb-3">
                                <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                No templates uploaded yet. <a href="{{ route('resort.ta.contractTemplates.index') }}">Upload one</a> or upload a PDF manually below.
                            </p>
                        @endif
                        {{-- Manual PDF upload fallback --}}
                        <div id="contractUploadSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Upload Contract (PDF)</label>
                                <input type="file" class="form-control" name="contract_file" accept=".pdf">
                            </div>
                        </div>
                        <div>
                            <a href="javascript:void(0)" id="toggleContractUpload" class="text-muted small">
                                <i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually
                            </a>
                        </div>
                        <input type="hidden" name="applicant_id" id="contract_ApplicantID">
                        <input type="hidden" name="applicant_status_id" id="contract_applicantstatusid">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-attention">Send Contract</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- WAI Insights — CV vs Job Description compatibility -->
    <div class="modal fade apx-glass" id="wai-insights-modal" tabindex="-1" aria-labelledby="apxWaiTtl" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered apx-dlg-wai">
            <div class="modal-content apx-gm">
                <div class="apx-gh">
                    <div class="apx-wai-ttl">
                        <span class="apx-wai-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z"/></svg></span>
                        <div class="ttl" id="apxWaiTtl">WAI Insights</div>
                    </div>
                    <button type="button" class="apx-gx" data-bs-dismiss="modal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <div class="apx-gb" id="wai-insights-body">
                    <!-- filled by JS -->
                </div>
                <div class="apx-gf">
                    <button type="button" class="apx-btn ghost" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
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
<style>
#apx{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9; --g4:#C7CDCF;
  --line:#E2EBEC; --line-2:#EEF4F4;
  --ok:#1F9D6B; --ok-bg:#E7F4EE; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --violet:#6B5FC7; --violet-bg:#EEE9FB; --info:#1E7A85; --info-bg:#E2F0F2; --lime:#E0FF02;
  --shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.06);
  --spring:cubic-bezier(.34,1.56,.64,1);
  font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
/* Shared by the page and the three glass modals (which sit outside #apx) */
.apx-pill{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
.apx-pill.ok{background:#E7F4EE;color:#1F9D6B}.apx-pill.violet{background:#EEE9FB;color:#6B5FC7}
.apx-pill.info{background:#E2F0F2;color:#1E7A85}.apx-pill.teal{background:#f1f7f7;color:#014653}
.apx-pill.warn{background:#FBF0DC;color:#B7791F}.apx-pill.err{background:#FDEEEB;color:#E5573F}
.apx-pill.muted{background:#EEF4F4;color:#6B7378}
.apx-av{flex:none;border-radius:50%;background:#E1EBEC;color:#014653;font-weight:600;display:grid;place-items:center;overflow:hidden;border:2px solid #fff;box-shadow:0 0 0 1.5px #E6F0F1;position:relative}
.apx-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.apx-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:12.5px;font-weight:600;border-radius:10px;padding:9px 12px;border:1px solid transparent;white-space:nowrap;cursor:pointer;text-decoration:none;transition:background .15s,border-color .15s,transform .15s cubic-bezier(.34,1.56,.64,1)}
.apx-btn:active{transform:scale(.97)}
.apx-btn svg{width:14px;height:14px}
.apx-btn.ghost{background:#fff;color:#014653;border-color:#E2EBEC}
.apx-btn.ghost:hover{border-color:#014653;background:#f1f7f7}
.apx-btn.solid{background:#014653;color:#fff;border-color:#014653}
.apx-btn.solid:hover{background:#035b6c}
.apx-btn.wai{background:#E0FF02;color:#014653;border-color:#cde800}
.apx-btn.wai:hover{filter:brightness(.98);box-shadow:0 5px 14px rgba(190,220,0,.4)}
.apx-btn.wai svg{width:15px;height:15px}
#apx .apx-ring{position:relative;width:84px;height:84px;margin-bottom:7px;--ring:var(--info)}
#apx .apx-ring.ok{--ring:var(--ok)}#apx .apx-ring.err{--ring:var(--err)}
#apx .apx-ring svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg)}
#apx .apx-ring .trk{fill:none;stroke:var(--line);stroke-width:5}
#apx .apx-ring .val{fill:none;stroke:var(--ring);stroke-width:5;stroke-linecap:round;stroke-dasharray:339.29;stroke-dashoffset:339.29;transition:stroke-dashoffset .75s ease-in-out}
#apx .apx-av-lg{position:absolute;inset:9px;width:auto;height:auto;font-size:18px;border:0;box-shadow:none}

/* ===== toolbar / grid ===== */
#apx #grid_main_view{padding:0 18px 18px}
#apx .apx-resinfo{font-size:12px;color:var(--muted);margin:0 2px 12px}
#apx .apx-resinfo b{color:var(--ink);font-weight:600}
#apx .apx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(272px,1fr));gap:16px}
#apx .apx-empty{color:var(--muted);padding:30px 4px}
#apx .apx-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:16px;cursor:pointer;transition:box-shadow .18s,transform .18s var(--spring)}
#apx .apx-card:hover{box-shadow:0 4px 10px rgba(1,70,83,.07),0 16px 36px rgba(1,70,83,.09);transform:translateY(-2px)}
#apx .apx-top{display:flex;flex-direction:column;align-items:center;text-align:center;gap:2px;padding-top:4px}
#apx .apx-top .nm{font-size:15px;font-weight:600;color:var(--ink);line-height:1.25}
#apx .apx-top .ro{font-size:12.5px;color:var(--muted);margin-top:1px}
#apx .apx-kebab{flex:none;background:none;border:none;color:var(--faint);width:28px;height:28px;border-radius:7px;display:grid;place-items:center;padding:0}
#apx .apx-kebab svg{width:16px;height:16px}
#apx .apx-kebab:hover{background:var(--line-2);color:var(--g1)}
#apx .apx-card .apx-kebab{position:absolute;top:12px;right:12px}
#apx .apx-status{margin-top:10px;display:flex;gap:7px;flex-wrap:wrap;justify-content:center;min-height:22px}
#apx .apx-sections{margin-top:14px;display:flex;flex-direction:column;gap:9px}
#apx .apx-sec{background:var(--teal-soft);border:1px solid var(--line-2);border-radius:12px;padding:11px 13px;display:flex;flex-direction:column;gap:7px}
#apx .apx-inf{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12.5px}
#apx .apx-inf .k{color:var(--muted);flex:none}
#apx .apx-inf .v{color:var(--g1);font-weight:400;text-align:right;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#apx .apx-actions{display:flex;gap:8px;margin-top:auto;padding-top:14px}
#apx .apx-actions .apx-btn.ghost{flex:none}
#apx .apx-actions .apx-btn.wai{flex:1}
#apx .apx-actions .apx-btn{padding:9px 12px}
#apx .flagimg{width:20px;height:14px;object-fit:cover;border-radius:3px;box-shadow:0 0 0 1px rgba(1,70,83,.12);margin-right:6px;vertical-align:-2px}
#apx .ccode,.apx-glass .ccode{display:inline-block;font-size:9.5px;font-weight:600;letter-spacing:.3px;color:#014653;background:#f1f7f7;border-radius:5px;padding:1px 5px;margin-right:6px;vertical-align:1px}
.apx-glass .flagimg{width:20px;height:14px;object-fit:cover;border-radius:3px;box-shadow:0 0 0 1px rgba(1,70,83,.12);margin-right:6px;vertical-align:-2px}
#apx .apx-pager{padding-top:16px}
#apx .apx-pager .pagination{justify-content:flex-end;gap:4px;margin:0}
#apx .apx-pager .page-link{border:1px solid var(--line);border-radius:8px;color:var(--teal);font-size:12.5px;padding:5px 11px;background:#fff}
#apx .apx-pager .page-link:hover{background:var(--teal-soft)}
#apx .apx-pager .page-item.active .page-link{background:var(--teal);border-color:var(--teal);color:#fff}
#apx .apx-pager .page-item.disabled .page-link{color:var(--faint);background:#fff}

/* ===== list ===== */
#apx .list-main{padding:0 18px 18px}
#apx .apx-tbl{border-collapse:separate;border-spacing:0;font-size:12.5px;min-width:1040px;margin:0}
#apx .apx-tbl th,#apx .apx-tbl td{padding:12px 14px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--line-2);vertical-align:middle;font-weight:400;color:var(--g1)}
#apx .apx-tbl thead th{background:var(--teal-soft);font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
#apx .apx-tbl th.r,#apx .apx-tbl td.r{text-align:right}
#apx .apx-tbl tbody tr.main{cursor:pointer}
#apx .apx-tbl tbody tr.main:hover td{background:#fafcfc}
#apx .apx-appcell{display:flex;align-items:center;gap:11px;min-width:0}
#apx .apx-appcell .apx-av{width:36px;height:36px;font-size:11px}
#apx .apx-appcell .nm{font-size:13.5px;font-weight:500;color:var(--ink)}
#apx .apx-appcell .sub{font-size:11px;color:var(--muted);margin-top:1px}
#apx .apx-cstack .e{font-size:12.5px;color:var(--g1)}
#apx .apx-cstack .p{font-size:11px;color:var(--muted);margin-top:1px}
#apx .apx-actcell{display:flex;align-items:center;gap:6px;justify-content:flex-end}
#apx .apx-ivbtn{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--teal);background:var(--teal-soft);border:1px solid var(--line);border-radius:9px;padding:7px 10px;white-space:nowrap;cursor:pointer}
#apx .apx-ivbtn:hover{border-color:var(--teal)}
#apx .apx-ivbtn svg{width:12px;height:12px}
#apx .apx-iact{width:30px;height:30px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--teal);display:grid;place-items:center;padding:0;cursor:pointer;text-decoration:none}
#apx .apx-iact:hover{border-color:var(--teal);background:var(--teal-soft)}
#apx .apx-iact svg{width:14px;height:14px}
#apx .apx-iact.wai{background:var(--lime);border-color:#cde800;color:var(--teal)}
#apx .apx-iact.wai:hover{filter:brightness(.97);box-shadow:0 4px 12px rgba(190,220,0,.4);background:var(--lime)}
#apx .apx-iact.wai svg{width:15px;height:15px}
#apx .apx-iact.pos{color:var(--ok)}#apx .apx-iact.pos:hover{background:var(--ok-bg);border-color:var(--ok)}
#apx .apx-iact.neg{color:var(--err)}#apx .apx-iact.neg:hover{background:var(--err-bg);border-color:var(--err)}
#apx .apx-iact.att{color:var(--warn)}#apx .apx-iact.att:hover{background:var(--warn-bg);border-color:var(--warn)}
#apx .apx-tbl thead th .dt-column-title{white-space:nowrap}
#apx .apx-tbl thead th.dt-orderable-asc span.dt-column-order:before,
#apx .apx-tbl thead th.dt-orderable-asc span.dt-column-order:after,
#apx .apx-tbl thead th.dt-orderable-desc span.dt-column-order:before,
#apx .apx-tbl thead th.dt-orderable-desc span.dt-column-order:after{opacity:0}
#apx .apx-tbl thead th.dt-ordering-asc span.dt-column-order:before,
#apx .apx-tbl thead th.dt-ordering-desc span.dt-column-order:after{opacity:1;color:var(--teal);font-size:.7em}
#apx .apx-tbl thead th.dt-ordering-asc span.dt-column-order:after,
#apx .apx-tbl thead th.dt-ordering-desc span.dt-column-order:before{display:none}

/* frosted kebab menu */
#apx .apx-menu{position:fixed;width:200px;border-radius:14px;padding:6px;z-index:1050;display:none;
  background:rgba(255,255,255,.9);-webkit-backdrop-filter:blur(20px) saturate(150%);backdrop-filter:blur(20px) saturate(150%);
  border:1px solid var(--line);box-shadow:0 2px 6px rgba(1,70,83,.08),0 18px 40px rgba(1,70,83,.16)}
#apx .apx-menu.is-on{display:block;animation:apxPop .14s ease}
@keyframes apxPop{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
#apx .apx-menu .kmi{display:flex;align-items:center;gap:11px;padding:10px 11px;border-radius:9px;font-size:13px;font-weight:500;color:var(--teal);cursor:pointer}
#apx .apx-menu .kmi svg{width:16px;height:16px;flex:none}
#apx .apx-menu .kmi:hover{background:var(--teal-soft)}

/* ===== Liquid Glass modals (Note, Interview Details, WAI Insights) ===== */
body.apx-glass-on .modal-backdrop{background:rgba(8,28,33,.46);-webkit-backdrop-filter:blur(7px);backdrop-filter:blur(7px)}
body.apx-glass-on .modal-backdrop.show{opacity:1}
/* Set max-width directly: older Bootstrap builds ignore --bs-modal-width and cap .modal-dialog at 500px */
.apx-glass .modal-dialog.apx-dlg-iv{width:92vw;max-width:92vw}
@media (min-width:992px){.apx-glass .modal-dialog.apx-dlg-iv{width:82vw;max-width:1400px}}
.apx-glass .modal-dialog.apx-dlg-wai{max-width:540px}
.apx-glass .modal-dialog.apx-dlg-note{max-width:560px}
.apx-glass .apx-gm{font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;border-radius:22px;overflow:hidden;
  background:rgba(255,255,255,.74);-webkit-backdrop-filter:blur(44px) saturate(185%);backdrop-filter:blur(44px) saturate(185%);
  border:1px solid rgba(255,255,255,.55);
  box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 30px 80px rgba(1,70,83,.32),0 8px 24px rgba(1,70,83,.18)}
.apx-glass.fade .modal-dialog{transform:scale(.96) translateY(8px);transition:transform .28s cubic-bezier(.34,1.56,.64,1),opacity .15s}
.apx-glass.show .modal-dialog{transform:none}
.apx-glass .apx-gh{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 22px;border-bottom:1px solid rgba(1,70,83,.1)}
.apx-glass .apx-gh .ttl{font-size:17px;font-weight:600;color:#14232A}
.apx-glass .apx-gh .sub{font-size:12.5px;color:#5D6F75;margin-top:2px}
.apx-glass .apx-gx{flex:none;width:34px;height:34px;border-radius:10px;border:1px solid rgba(1,70,83,.12);background:rgba(255,255,255,.5);color:#3A4145;display:grid;place-items:center;padding:0;cursor:pointer}
.apx-glass .apx-gx:hover{background:#fff;color:#14232A}
.apx-glass .apx-gx svg{width:16px;height:16px}
.apx-glass .apx-gb{padding:18px 22px;max-height:64vh;overflow:auto}
.apx-glass .apx-gf{display:flex;justify-content:flex-end;gap:10px;padding:16px 22px;border-top:1px solid rgba(1,70,83,.1)}
/* note */
.apx-glass .apx-ta{width:100%;min-height:150px;resize:vertical;font:inherit;font-size:13.5px;color:#14232A;background:#fff;border:1px solid #E2EBEC;border-radius:12px;padding:12px 14px;line-height:1.55}
.apx-glass .apx-ta:focus{outline:none;border-color:#014653;box-shadow:0 0 0 3px rgba(1,70,83,.1)}
.apx-glass .apx-ta::placeholder{color:#93A4A9}
.apx-glass .apx-cap{font-size:11.5px;color:#5D6F75;margin-top:10px;display:flex;align-items:center;gap:6px}
.apx-glass .apx-cap svg{width:13px;height:13px;color:#93A4A9}
.apx-glass label.error{display:block;font-size:12px;color:#E5573F;margin-top:6px}
/* interview details (markup comes from the existing handler) */
.apx-glass #popupInterviewDetails{width:100%;margin:0;border:0;background:none}
.apx-glass #popupInterviewDetails>tbody>tr>td{padding:0;border:0;background:none}
.apx-glass #popupInterviewDetails .bg{background:none;border:0;padding:0;margin:0}
.apx-glass #popupInterviewDetails .bg>table{width:100%;border-collapse:separate;border-spacing:0;font-size:12px;background:#fff;border:1px solid #E2EBEC;border-radius:11px;overflow:hidden;min-width:0}
.apx-glass #popupInterviewDetails .bg>table th{font-family:inherit;background:#f1f7f7;text-align:left;padding:9px 10px;font-size:9.5px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;color:#5D6F75;white-space:nowrap}
.apx-glass #popupInterviewDetails .bg>table td{padding:10px 10px;border-top:1px solid #EEF4F4;white-space:nowrap;color:#3A4145;font-size:12px;font-weight:400;vertical-align:middle}
.apx-glass #popupInterviewDetails .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px}
.apx-glass #popupInterviewDetails .badge.bg-success{background:#E7F4EE!important;color:#1F9D6B!important}
.apx-glass #popupInterviewDetails .badge.bg-info{background:#E2F0F2!important;color:#1E7A85!important}
.apx-glass #popupInterviewDetails .badge.bg-warning{background:#FBF0DC!important;color:#B7791F!important}
.apx-glass #popupInterviewDetails .badge.bg-danger{background:#FDEEEB!important;color:#E5573F!important}
.apx-glass #popupInterviewDetails .badge.bg-secondary{background:#EEF4F4!important;color:#6B7378!important}
.apx-glass #popupInterviewDetails .btn-small{font-size:11.5px;font-weight:600;border-radius:8px;padding:6px 11px}
.apx-glass #popupInterviewDetails b{font-weight:600;color:#014653;font-variant-numeric:tabular-nums}
/* WAI insights */
.apx-glass .apx-wai-ttl{display:flex;align-items:center;gap:11px}
.apx-glass .apx-wai-badge{flex:none;width:34px;height:34px;border-radius:10px;background:#E0FF02;color:#014653;display:grid;place-items:center}
.apx-glass .apx-wai-badge svg{width:19px;height:19px}
.apx-glass .apx-wai-load,.apx-glass .apx-wai-msg{text-align:center;padding:22px 8px;color:#3A4145;font-size:13.5px}
.apx-glass .apx-wai-load small,.apx-glass .apx-wai-msg small{display:block;color:#5D6F75;margin-top:4px}
.apx-glass .apx-wai-cand{margin-bottom:16px}
.apx-glass .apx-wai-cand .nm{font-size:16px;font-weight:600;color:#14232A}
.apx-glass .apx-wai-cand .ro{font-size:12.5px;color:#5D6F75;margin-top:2px}
.apx-glass .apx-wai-hero{display:flex;align-items:center;gap:20px;background:linear-gradient(135deg,#014653,#035b6c);border-radius:16px;padding:18px 20px;color:#fff}
.apx-glass .apx-wai-ringwrap{position:relative;flex:none;width:104px;height:104px;display:grid;place-items:center}
.apx-glass .apx-wai-ring{width:104px;height:104px;transform:rotate(-90deg)}
.apx-glass .apx-wai-ring .trk{fill:none;stroke:rgba(255,255,255,.2);stroke-width:9}
.apx-glass .apx-wai-ring .val{fill:none;stroke-width:9;stroke-linecap:round;transition:stroke-dashoffset .7s cubic-bezier(.34,1.56,.64,1)}
.apx-glass .apx-wai-pct{position:absolute;inset:0;display:grid;place-items:center;font-size:25px;font-weight:600;color:#fff;font-variant-numeric:tabular-nums}
.apx-glass .apx-lvl{display:inline-flex;align-items:center;font-size:12.5px;font-weight:600;padding:5px 13px;border-radius:20px;color:#fff}
.apx-glass .apx-lvl.ok{background:#2FBE86}.apx-glass .apx-lvl.warn{background:#E0A93B}.apx-glass .apx-lvl.err{background:#E5573F}
.apx-glass .apx-wai-hero .cap{font-size:12px;color:rgba(255,255,255,.72);margin-top:9px;line-height:1.45}
.apx-glass .apx-wai-sec{font-size:10.5px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:#5D6F75;margin:18px 2px 8px}
.apx-glass .apx-wai-insight{font-size:13.5px;color:#3A4145;line-height:1.6}
.apx-glass .apx-wai-disc{font-size:11.5px;color:#93A4A9;text-align:center;line-height:1.5;margin-top:16px;padding-top:14px;border-top:1px solid rgba(1,70,83,.1)}
@media (max-width:720px){#apx #grid_main_view,#apx .list-main{padding-left:12px;padding-right:12px}.apx-glass .apx-wai-hero{flex-direction:column;text-align:center}}
@media (prefers-reduced-transparency:reduce){
  #apx .apx-menu{background:#fff;-webkit-backdrop-filter:none;backdrop-filter:none}
  body.apx-glass-on .modal-backdrop{background:rgba(8,28,33,.62);-webkit-backdrop-filter:none;backdrop-filter:none}
  .apx-glass .apx-gm{background:#fff;-webkit-backdrop-filter:none;backdrop-filter:none;border-color:#E2EBEC}
}
@media (prefers-reduced-motion:reduce){#apx *,.apx-glass *{transition:none!important;animation:none!important}}
</style>
@endsection

@section('import-scripts')
    <script>
        var isHrDepartment = @json($isHrDepartment);
        var defaultApplicantPicture = "{{ url(config('settings.default_picture')) }}";
        function escHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
        $(document).ready(function() {
            datatablelist();
            // Grid is the default visible view now — #grid_main_view is
            // server-rendered empty and only ever populated by DatatableGrid(),
            // previously fired only on a manual .btn-grid click, so the grid
            // showed blank on load until the user toggled away and back.
            DatatableGrid();
            $('.table-applicants tbody').empty();

            // WAI Insights — score this applicant's CV against the position's Job Description.
            const APX_WAI_BAND = { success: 'ok', warning: 'warn', danger: 'err' };
            const APX_WAI_STROKE = { ok: '#2FBE86', warn: '#E0A93B', err: '#E5573F' };
            $(document).on('click', '.waiInsightsBtn', function () {
                var id = $(this).data('id');
                var $body = $('#wai-insights-body');
                $body.html('<div class="apx-wai-load">'
                    + '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                    + '<p class="mt-3 mb-0">Wisdom AI is reviewing the CV against the job description…</p>'
                    + '<small>This can take up to a minute.</small></div>');
                $('#wai-insights-modal').modal('show');
                $.ajax({
                    url: "{{ route('resort.ta.WaiInsights') }}",
                    type: 'POST',
                    global: false, // we show our own modal spinner — suppress the page-level global loader
                    data: { id: id, _token: "{{ csrf_token() }}" },
                    success: function (res) {
                        if (res && res.success) {
                            var band = APX_WAI_BAND[res.color] || 'warn', score = Math.max(0, Math.min(100, +res.score || 0));
                            var r = 42, C = 2 * Math.PI * r, off = C * (1 - score / 100);
                            $body.html('<div class="apx-wai-cand"><div class="nm">' + escHtml(res.applicant || 'Applicant') + '</div>'
                                + '<div class="ro">Position: ' + escHtml(res.position || '—') + '</div></div>'
                                + '<div class="apx-wai-hero"><div class="apx-wai-ringwrap">'
                                + '<svg class="apx-wai-ring" viewBox="0 0 100 100"><circle class="trk" cx="50" cy="50" r="' + r + '"/>'
                                + '<circle class="val" cx="50" cy="50" r="' + r + '" style="stroke:' + APX_WAI_STROKE[band] + ';stroke-dasharray:' + C.toFixed(1) + ';stroke-dashoffset:' + C.toFixed(1) + '"/></svg>'
                                + '<div class="apx-wai-pct">' + score + '%</div></div>'
                                + '<div><span class="apx-lvl ' + band + '">' + escHtml(res.label || '') + '</span>'
                                + '<div class="cap">AI-estimated fit between this applicant\'s CV and the job description.</div></div></div>'
                                + (res.summary ? '<div class="apx-wai-sec">Detailed insight</div><div class="apx-wai-insight">' + escHtml(res.summary) + '</div>' : '')
                                + '<div class="apx-wai-disc">AI-estimated match between the applicant\'s CV and the job description. Use as guidance alongside your own review.</div>');
                            // animate the ring up to the score
                            var val = $body.find('.apx-wai-ring .val')[0];
                            if (val) requestAnimationFrame(function () { requestAnimationFrame(function () { val.style.strokeDashoffset = off.toFixed(1); }); });
                        } else {
                            $body.html('<div class="apx-wai-msg"><i class="fa-solid fa-circle-info text-warning fa-2x mb-2"></i>'
                                + '<p class="mb-0">' + escHtml((res && res.message) || 'Could not generate insights.') + '</p></div>');
                        }
                    },
                    error: function () {
                        $body.html('<div class="apx-wai-msg"><i class="fa-solid fa-triangle-exclamation text-danger fa-2x mb-2"></i>'
                            + '<p class="mb-0">Something went wrong contacting Wisdom AI. Please try again.</p></div>');
                    }
                });
            });

            $(document).on('click', '.gridview-link', function()
            {
                let rowId = $(this).data('row-id');
                let status = $(this).data('status');
                let applicant_id = $(this).data('applicant_id');
                // Subtitle + flags for the dual-timezone cells (applicant's country comes from the trigger)
                const apxName = $(this).data('name') || '', apxRole = $(this).data('role') || '';
                $('#apxIvSub').text([apxName, apxRole].filter(Boolean).join(' · '));
                const apxNat = taFlag(TA_COUNTRY_CODES[$(this).data('country')] || '');
                const apxMv = taFlag('MV');
                if(status != "Sortlisted By Wisdom AI") {
                    if(status == "Selected") {
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
                                    let newTag = 'send Link';
                                    let postInterviewStatuses = ['Complete', 'Selected', 'Rejected', 'Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected'];
                                    if(postInterviewStatuses.includes(status)) {
                                        newTag =`<span class="badge bg-success">Round Completed</span>`;
                                    }
                                    else if(response.data.InterviewStatus == 'Slot Not Booked' || response.data.InterviewStatus == 'Invitation Rejected') {
                                        newTag =`<a href="javascript:void(0)"
                                            data-Resort_id="${response.data.Resort_id}"
                                            data-ApplicantID="${response.data.ApplicantID}"
                                            data-ApplicantStatus_id="${response.data.ApplicantStatus_id}"
                                            class="btn ta-btn-attention btn-small SortlistedEmployee">Send Interview Request</a>`;
                                    }
                                    else if(response.data.InterviewStatus == "Pending Review") {
                                        newTag =`<a href="javascript:void(0)"
                                            class="btn ta-btn-attention btn-small confirmPendingReview"
                                            data-interview_id="${response.data.Interview_id}"
                                            data-email_template_id="${response.data.EmailTemplateId || ''}"
                                            >Confirm & Send</a>`;
                                    }
                                    else if(response.data.InterviewStatus == "Invitation Sent") {
                                        newTag =`<span class="badge bg-info text-white">Invitation Sent - Awaiting Response</span>`;
                                    }
                                    else if(response.data.InterviewStatus =="Slot Booked" && !isNaN(response.data.MeetingLink)) {
                                        newTag =`<a class="btn ta-btn-secondary btn-small ApplicantShareLink"
                                        data-round="${response.data.round}"
                                            data-rank_name="${response.data.rank_name}"
                                        data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Add Interview Link</a>`;
                                    }
                                    else {
                                        newTag =`<a href="${response.data.MeetingLink}" target="_blank" class="btn ta-btn-secondary btn-small"
                                        data-rond="${response.data.round}"
                                        data-rank_name="${response.data.rank_name}"
                                        data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Start Interview  </a>`;
                                    }

                                    // Build next round row if Complete and has next round
                                    let nextRoundRow = '';
                                    if (status == "Complete" && response.data.nextRound) {
                                        const nr = response.data.nextRound;
                                        let nrAction = '';
                                        let nrStatusBadge = `<span class="badge bg-secondary">Pending</span>`;
                                        const nrApplicantStatusId = nr.ApplicantStatus_id || response.data.ApplicantStatus_id;

                                        if (nr.InterviewStatus == 'Pending' || nr.InterviewStatus == 'Slot Not Booked' || nr.InterviewStatus == 'Invitation Rejected') {
                                            nrAction = `<a href="javascript:void(0)"
                                                data-Resort_id="${response.data.Resort_id}"
                                                data-ApplicantID="${response.data.ApplicantID}"
                                                data-ApplicantStatus_id="${nrApplicantStatusId}"
                                                class="btn ta-btn-attention btn-small SortlistedEmployee">Send Interview Invitation</a>`;
                                            if (nr.InterviewStatus == 'Invitation Rejected') {
                                                nrStatusBadge = `<span class="badge bg-danger">Invitation Rejected</span>${nr.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${escHtml(nr.interviewRejectionReason)}</div>` : ''}`;
                                            }
                                        } else if (nr.InterviewStatus == 'Invitation Sent') {
                                            nrAction = `<span class="badge bg-info text-white">Invitation Sent - Awaiting Response</span>`;
                                            nrStatusBadge = `<span class="badge bg-info text-white">Invitation Sent</span>`;
                                        } else if (nr.InterviewStatus == 'Slot Booked' && (!nr.MeetingLink || !isNaN(nr.MeetingLink))) {
                                            nrAction = `<a class="btn ta-btn-secondary btn-small ApplicantShareLink"
                                                data-round="${nr.round}"
                                                data-rank_name="${nr.rank_name}"
                                                data-interview_id="${nr.Interview_id}" href="javascript:void(0)">Add Interview Link</a>`;
                                            nrStatusBadge = `<span class="badge bg-warning text-dark">Slot Booked</span>`;
                                        } else if (nr.InterviewStatus == 'Slot Booked' && nr.MeetingLink) {
                                            nrAction = `<a href="${nr.MeetingLink}" target="_blank" class="btn ta-btn-secondary btn-small">Start Interview</a>`;
                                            nrStatusBadge = `<span class="badge bg-success">Ready</span>`;
                                        }

                                        nextRoundRow = `<tr>
                                                        <td><select class="form-control dd-native-select EmailTemplate EmailTemplate-next" id="EmailTemplateNext-${rowId}" name='EmailTemplate'>
                                                            <option selected disabled>Select Email Template </option>
                                                                @foreach ($EmailTamplete as $e)
                                                                    <option value="{{ $e->id}}" data-name="{{ $e->TempleteName }}">{{ $e->TempleteName }}</option>
                                                                @endforeach
                                                                </select>
                                                                <div class="dd" data-target="#EmailTemplateNext-${rowId}">
                                                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                                        <span class="dd-lbl">Select Email Template</span>
                                                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                                    </button>
                                                                    <div class="dd-panel" role="listbox" aria-label="Email Template">
                                                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                                                        <div class="dd-scroll">
                                                                            <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                            @foreach ($EmailTamplete as $e)
                                                                            <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                        </td>
                                                        <td>${nr.rank_name}</td>
                                                        <td>${nr.round}</td>
                                                        <td><input type="hidden" class="Round" name="Round" value="${nr.rank_name}">
                                                        <input type="hidden" class="InterviewType" name="InterviewType" value="${nr.round}">
                                                        <input type="hidden" class="Interviewer" name="Interviewer" value="${nr.Interviewer}">
                                                            ${nr.Interviewer}</td>
                                                        <td>${nr.Date}</td>
                                                        <td>${apxMv}<b>${nr.MalidivanTime}</b></td>
                                                        <td>${apxNat}<b>${nr.ApplicantTime}</b></td>
                                                        <td>${nrStatusBadge}</td>
                                                        <td>${nrAction}</td>
                                                    </tr>`;
                                    }

                                    // Build past rounds rows for grid view
                                    let pastRoundsRows = '';
                                    if (response.data.pastRounds && response.data.pastRounds.length > 0) {
                                        response.data.pastRounds.forEach(function(pr) {
                                            let prStatusBadge = pr.status == 'Complete'
                                                ? `<span class="badge bg-success">Completed</span>`
                                                : `<span class="badge bg-secondary">${pr.InterviewStatus}</span>`;
                                            pastRoundsRows += `<tr>
                                                <td>${pr.emailTemplate || '-'}</td>
                                                <td>${pr.rank_name}</td>
                                                <td>${pr.round}</td>
                                                <td>${pr.Interviewer}</td>
                                                <td>${pr.Date}</td>
                                                <td>${apxMv}<b>${pr.MalidivanTime}</b></td>
                                                <td>${apxNat}<b>${pr.ApplicantTime}</b></td>
                                                <td>${prStatusBadge}</td>
                                                <td><span class="badge bg-success">Round Completed</span></td>
                                            </tr>`;
                                        });
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
                                                    ${pastRoundsRows}
                                                    <tr>
                                                        <td>${['Completed','Pending Review','Invitation Sent','Slot Booked'].includes(response.data.InterviewStatus) && response.data.emailTemplate && response.data.emailTemplate !== '-'
                                                            ? response.data.emailTemplate
                                                            : `<select class="form-control dd-native-select EmailTemplate" id="EmailTemplate-${rowId}" name='EmailTemplate'>
                                                            <option selected disabled>Select Email Template </option>
                                                                @foreach ($EmailTamplete as $e)
                                                                    <option value="{{ $e->id}}" data-name="{{ $e->TempleteName }}">{{ $e->TempleteName }}</option>
                                                                @endforeach
                                                                </select>
                                                                <div class="dd" data-target="#EmailTemplate-${rowId}">
                                                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                                        <span class="dd-lbl">Select Email Template</span>
                                                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                                    </button>
                                                                    <div class="dd-panel" role="listbox" aria-label="Email Template">
                                                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a template…"></div>
                                                                        <div class="dd-scroll">
                                                                            <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Email Template</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                            @foreach ($EmailTamplete as $e)
                                                                            <div class="dd-item" role="option" data-value="{{ $e->id }}"><span class="dd-nm">{{ $e->TempleteName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>`}
                                                        </td>
                                                        <td>${response.data.rank_name}</td>
                                                        <td>${response.data.round}</td>
                                                        <td><input type="hidden"  class="Round"  name="Round" value="${response.data.rank_name}">
                                                        <input type="hidden"  class="InterviewType"  name="InterviewType" value="${response.data.round}">
                                                        <input type="hidden"  class="Interviewer"  name="Interviewer" value="${response.data.Interviewer}">
                                                            ${response.data.Interviewer}</td>
                                                        <td>${response.data.Date}</td>
                                                        <td>${apxMv}<b>${response.data.MalidivanTime}</b></td>
                                                        <td>${apxNat}<b>${response.data.ApplicantTime}</b></td>
                                                        <td><span class="apx-pill ${apxIvCls(response.data.InterviewStatus)}">${escHtml(response.data.InterviewStatus)}</span>${response.data.InterviewStatus == 'Invitation Rejected' && response.data.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${escHtml(response.data.interviewRejectionReason)}</div>` : ''}</td>
                                                        <td>
                                                            ${newTag}
                                                        </td>
                                                    </tr>
                                                    ${nextRoundRow}
                                                </table>
                                                ${response.data.rejectionReason ? `<div class="mt-2 p-2" style="background:#fff3f3; border-left:3px solid #dc3545; border-radius:4px;">
                                                    <strong class="text-danger">${response.data.applicantStatusRaw == 'Offer Letter Rejected' ? 'Offer Letter' : 'Contract'} Declined</strong>
                                                    <p class="mb-0 mt-1"><strong>Reason:</strong> ${escHtml(response.data.rejectionReason)}</p>
                                                </div>` : ''}
                                                @if($isHrDepartment)
                                                ${response.data.applicantStatusRaw == 'Offer Letter Rejected' ? `<div class="mt-2">
                                                    <a href="javascript:void(0)" class="btn ta-btn-attention btn-sm sendOfferLetterBtn"
                                                        data-id="${response.data.ApplicantID}"
                                                        data-applicantstatusid="${response.data.ApplicantStatus_id}">
                                                        Resend Offer Letter
                                                    </a>
                                                </div>` : ''}
                                                ${response.data.applicantStatusRaw == 'Contract Rejected' ? `<div class="mt-2">
                                                    <a href="javascript:void(0)" class="btn ta-btn-attention btn-sm sendContractBtn"
                                                        data-id="${response.data.ApplicantID}"
                                                        data-applicantstatusid="${response.data.ApplicantStatus_id}">
                                                        Resend Contract
                                                    </a>
                                                </div>` : ''}
                                                @endif
                                            </div>
                                        </td>
                                    </tr>`;

                                    $("#intDetail-modal").modal('show');
                                    $("#popupInterviewDetails").html(newRow);
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
                $('#apxNoteSub').text([$(this).data('name'), $(this).data('role')].filter(Boolean).join(' · '));
                $("#ApplicantNote").val('');
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
                        required: "Please write something.",
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
            // Debounced: each reload runs the applicants query.
            let apxSearchTimer;
            $('.search').on('keyup', function() {
                clearTimeout(apxSearchTimer);
                apxSearchTimer = setTimeout(function () {
                    if ($(".btn-grid").hasClass('active')) { DatatableGrid(); } else { datatablelist(); }
                }, 250);
            });

            // Desinger Code start
            $(".btn-grid").click(function () {
                $(this).addClass("active");
                $(".grid-main").addClass("d-block");
                $(".grid-main").removeClass("d-none");
                $(".btn-list").removeClass("active");
                $(".list-main").addClass("d-none");
                $(".list-main").removeClass("d-block");
                $('.apx-listonly').addClass('d-none'); // stage / invitation filters need the list endpoint
                DatatableGrid()
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
                $('.apx-listonly').removeClass('d-none');
                $('.table-applicants').DataTable().ajax.reload();
            });

            // ---- Redesign interactions ---------------------------------------
            // Stage / invitation filters (list view): server-side column search on the raw status columns
            $(document).on('change', '#apxStage, #apxInv', function () {
                const dt = $('.table-applicants').DataTable();
                dt.column('ApplicantStatus:name').search($('#apxStage').val() || '', true, false);
                dt.column('InterviewStatus:name').search($('#apxInv').val() || '', true, false).draw();
            });
            // Card / row -> applicant profile. Buttons, links and the kebab never trigger it.
            $(document).on('click', '#apx .apx-card', function (e) {
                if ($(e.target).closest('a, button, .apx-kebab').length) return;
                $(this).find('.userApplicants-btn').first().trigger('click');
            });
            $(document).on('click', '#apx .apx-tbl tbody tr.main', function (e) {
                if ($(e.target).closest('a, button, .apx-kebab, .dd').length) return;
                $(this).find('.userApplicants-btn').first().trigger('click');
            });
            // Grid pagination links are plain hrefs to a POST-only endpoint; load the page in place instead.
            $(document).on('click', '#apx .apx-pager a.page-link', function (e) {
                e.preventDefault();
                const page = new URLSearchParams((this.href.split('?')[1] || '')).get('page');
                if (page) DatatableGrid(page);
            });
            // Glass modals: dark blurred scrim + focus the note field
            $('.apx-glass').on('show.bs.modal', function () { document.body.classList.add('apx-glass-on'); })
                           .on('hidden.bs.modal', function () { if (!$('.apx-glass.show').length) document.body.classList.remove('apx-glass-on'); });
            $('#ApplicantsNotes-Model').on('shown.bs.modal', function () { $('#ApplicantNote').trigger('focus'); });

            // Frosted kebab — items reuse the existing handlers (.userApplicants-btn, .gridview-link, .ApplicantsNotes, .waiInsightsBtn).
            // jQuery caches .data() reads, so values are written to both the attribute and the data store.
            const $apxMenu = $('#apxMenu');
            const apxSet = ($el, o) => { $.each(o, function (k, v) { $el.attr('data-' + k, v).data(k, v); }); };
            const apxCloseMenu = () => $apxMenu.removeClass('is-on');
            $(document).on('click', '#apx .apx-kebab', function (e) {
                e.stopPropagation();
                const d = this.dataset;
                apxSet($apxMenu.find('[data-m="view"]'), { id: d.sid });
                apxSet($apxMenu.find('[data-m="interview"]'), { 'row-id': d.rowid, status: d.status, applicant_id: d.sid, name: d.name, role: d.role, country: d.country });
                apxSet($apxMenu.find('[data-m="note"]'), { id: d.aid, name: d.name, role: d.role });
                apxSet($apxMenu.find('[data-m="wai"]'), { id: d.aid });
                $apxMenu.addClass('is-on');
                const r = this.getBoundingClientRect(), w = $apxMenu.outerWidth() || 200, h = $apxMenu.outerHeight() || 170;
                let top = r.bottom + 6; if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
                $apxMenu.css({ left: Math.max(8, r.right - w) + 'px', top: top + 'px' });
            });
            $apxMenu.on('keydown', '.kmi', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });
            $(document).on('click', function (e) { if (!$(e.target).closest('#apx .apx-kebab, #apxMenu').length || $(e.target).closest('#apxMenu .kmi').length) apxCloseMenu(); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape') apxCloseMenu(); });
            window.addEventListener('scroll', apxCloseMenu, true);

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
                                    // The over-budget check only ran on user input/change —
                                    // an applicant whose salary allocation was already saved
                                    // over budget showed no warning at all until someone
                                    // retyped the value. Run it once as soon as the form
                                    // (with its already-saved basic_salary) is in the DOM.
                                    if ($("#salaryAllocationForm").length && typeof checkSalaryOverBudget === 'function') {
                                        checkSalaryOverBudget();
                                    }
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
        $(document).on("focus", '[name="MalidivanManualTime"]', function () {
            $(".row_time").removeClass("active");
            $(".row_time .Timezone_checkBox").prop("checked", false);
            $("#ResortInterviewtime_collected").val('');
            $("#ApplicantInterviewtime_collected").val('');
        });

        $(document).on("change", '[name="MalidivanManualTime"]', function () {
            const timeValue = $(this).val();
            if (timeValue) {
                const resortTz = $('#resortTimezone').val();
                const applicantTz = $('#applicantTimezone').val();

                // Format resort time to 12h
                const [hours, minutes] = timeValue.split(":");
                const period = hours >= 12 ? "PM" : "AM";
                const formattedHours = hours % 12 || 12;
                let MalidivanManualTime1 = formattedHours + ":" + minutes + " " + period;
                $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1);

                // Auto-convert to applicant timezone using moment-timezone
                var resortMoment = moment.tz(timeValue, 'HH:mm', resortTz);
                var applicantMoment = resortMoment.clone().tz(applicantTz);
                var applicantTime24 = applicantMoment.format('HH:mm');
                var applicantTime12 = applicantMoment.format('h:mm A');

                $('[name="ApplicantManualTime"]').val(applicantTime24);
                $('[name="ApplicantManualTime1"]').val(applicantTime12);
            } else {
                $('[name="ApplicantManualTime"]').val('');
                $('[name="ApplicantManualTime1"]').val('');
                $('[name="MalidivanManualTime1"]').val('');
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
                $btn.text('Send Rejection Email').removeClass('ta-btn-attention').addClass('ta-btn-critical');
            } else {
                $btn.text('Send Interview Invitation').removeClass('ta-btn-critical').addClass('ta-btn-attention');
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
                            $('tr.details-row').remove();
                            $('td.details-control').closest('tr').removeClass('shown');
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
                MeetingLink: {
                    required: true,
                },
                "SlotBook[]": {
                    required: function () {
                        return $('[name="MalidivanManualTime"]').val().trim() === "";
                    },
                },
                MalidivanManualTime: {
                    required: function () {
                        return $('[name="SlotBook[]"]:checked').length === 0;
                    },
                },
            },
            messages: {
                MeetingLink: {
                    required: "Please enter a Meeting Link.",
                },
                "SlotBook[]": {
                    required: "Please select a valid time slot or enter a manual time.",
                },
                MalidivanManualTime: {
                    required: "Please enter your time or select a valid time slot.",
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
                            $("#todoList-main").html( response.TodoDataview);
                            $("#Final_response_data").html(response.Final_response_data);
                            $("#review_interview_id").val(response.interview_id);
                            $("#review_email_template_id").val(response.email_template_id);
                            $("#sendRequestFinal-modal").modal("show");
                            // Remove all expanded details rows so stale data doesn't persist
                            $('tr.details-row').remove();
                            $('td.details-control').closest('tr').removeClass('shown');
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
        // Confirm & Send - for Pending Review interviews (open review modal)
        $(document).on("click", ".confirmPendingReview", function() {
            var interviewId = $(this).data('interview_id');
            var emailTemplateId = $(this).data('email_template_id');
            $("#review_interview_id").val(interviewId);
            $("#review_email_template_id").val(emailTemplateId);
            $("#sendRequestFinal-modal").modal("show");
        });

        // Review modal - Confirm and send interview email
        $(document).on("click", "#confirmSendInterviewEmail", function() {
            var $btn = $(this);
            $btn.addClass('disabled').text('Sending...');

            $.ajax({
                url: "{{ route('resort.ta.SendInterviewEmail') }}",
                type: "POST",
                data: {
                    interview_id: $("#review_interview_id").val(),
                    email_template_id: $("#review_email_template_id").val(),
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $("#sendRequestFinal-modal").modal("hide");
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Refresh the table
                        $('tr.details-row').remove();
                        $('td.details-control').closest('tr').removeClass('shown');
                        datatablelist();
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function() {
                    $("#sendRequestFinal-modal").modal("hide");
                    toastr.error("Something went wrong.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function() {
                    $btn.removeClass('disabled').text('Submit');
                }
            });
        });

        // Review modal - Cancel: show confirmation modal
        $(document).on("click", "#cancelPendingInterview", function() {
            $("#sendRequestFinal-modal").modal("hide");
            $("#confirmCancelSlot-modal").modal("show");
        });

        // Confirmation modal - No, Go Back: return to review modal
        $(document).on("click", "#cancelSlotNo", function() {
            $("#confirmCancelSlot-modal").modal("hide");
            $("#sendRequestFinal-modal").modal("show");
        });

        // Confirmation modal - Yes, Delete Slot: delete and close
        $(document).on("click", "#cancelSlotYes", function() {
            $.ajax({
                url: "{{ route('resort.ta.DeletePendingInterview') }}",
                type: "POST",
                data: {
                    interview_id: $("#review_interview_id").val(),
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $("#confirmCancelSlot-modal").modal("hide");
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('tr.details-row').remove();
                        $('td.details-control').closest('tr').removeClass('shown');
                        datatablelist();
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function() {
                    $("#confirmCancelSlot-modal").modal("hide");
                    toastr.error("Something went wrong.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
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
            let fileIndex = $(this).data("index");

            $.ajax({
                url: "{{ route('resort.ta.DownloadFile') }}",
                type: "POST",
                data: {
                    id: fileId,
                    flag: fileFlag,
                    index: fileIndex,
                    "_token": "{{ csrf_token() }}"
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

        // Download All Files — a single .zip named after the candidate +
        // position, instead of opening every document in its own tab.
        $(document).on("click", ".DownloadAllFiles", function () {
            let fileId = $(this).data("id");
            window.location.href = "{{ route('resort.ta.DownloadAllFilesZip', ':id') }}".replace(':id', fileId);
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
                            // Remove all expanded details rows so stale data doesn't persist
                            $('tr.details-row').remove();
                            $('td.details-control').closest('tr').removeClass('shown');
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
        // ---- Applicants redesign helpers ---------------------------------
        @include('resorts.talentacquisition._flag_js')
        const apxAttr = v => escHtml(v).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const APX_ICON = {
            dots: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>',
            note: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
            eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>',
            wai: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z"/></svg>',
            cal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
            check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>',
            x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>',
            doc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>'
        };
        const apxIni = n => (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')) || '?';
        const apxExp = e => { const n = parseFloat(e); return isNaN(n) ? (e || '—') : (n + (n === 1 ? ' yr' : ' yrs')); };
        const apxPill = (cls, text) => '<span class="apx-pill ' + cls + '">' + escHtml(text) + '</span>';

        // Pipeline stage -> label + pill colour. Unknown values fall back to a muted pill (never dropped).
        function apxStage(status, rank) {
            const R = rank && rank !== 'Wisdom AI' ? rank + ' ' : '';
            switch (status) {
                case 'Sortlisted By Wisdom AI': return { t: 'Shortlisted by WAI', c: 'muted' };
                case 'Sortlisted': return { t: R + 'Shortlisted', c: rank === 'EXCOM' ? 'teal' : 'violet' };
                case 'Round': return { t: R + 'Interview round', c: 'info' };
                case 'Complete': return { t: R + 'Round complete', c: 'teal' };
                case 'Selected': return { t: 'Selected', c: 'ok' };
                case 'Rejected': return { t: R + 'Rejected', c: 'err' };
                case 'Offer Letter Sent': return { t: 'Offer letter sent', c: 'warn' };
                case 'Offer Letter Accepted': return { t: 'Offer letter accepted', c: 'ok' };
                case 'Offer Letter Rejected': return { t: 'Offer letter rejected', c: 'err' };
                case 'Contract Sent': return { t: 'Contract sent', c: 'info' };
                case 'Contract Accepted': return { t: 'Contract accepted', c: 'ok' };
                case 'Contract Rejected': return { t: 'Contract rejected', c: 'err' };
                default: return { t: String(status || '—').replace('Sortlisted', 'Shortlisted'), c: 'muted' };
            }
        }
        const apxStagePill = (status, rank) => { const x = apxStage(status, rank); return apxPill(x.c, x.t); };
        // Interview round status (modal) -> pill colour
        function apxIvCls(st) {
            if (/^Complete/.test(st)) return 'ok';
            if (st === 'Slot Booked' || st === 'Invitation Sent') return 'info';
            if (st === 'Invitation Rejected') return 'err';
            if (st === 'Pending Review' || st === 'Slot Not Booked' || st === 'Active' || st === 'Pending') return 'warn';
            return 'muted';
        }
        // Invitation column: applicant outcome first, then the latest interview invitation state
        function apxInvitation(r) {
            if (r.ApplicantStatus === 'Rejected') return apxPill('err', 'Rejected');
            if (r.ApplicantStatus === 'Selected') return apxPill('ok', 'Selected');
            switch (r.InterviewStatus) {
                case 'Slot Booked': return apxPill('ok', 'Accepted');
                case 'Slot Not Booked': case 'Active': return apxPill('warn', 'Pending');
                case 'Invitation Rejected': return apxPill('err', 'Declined');
                case 'Invitation Sent': return apxPill('info', 'Invitation sent');
                case 'Pending Review': return apxPill('warn', 'Pending review');
                default: return apxPill('muted', 'Not sent');
            }
        }
        // The server renders Select / Reject / Offer letter / Contract as status-dependent buttons in the
        // action column. Keep them (same classes + data attributes the existing handlers use), restyled.
        function apxExtraActions(actionHtml) {
            const $h = $('<div>').html(actionHtml || '');
            return [['.selectCandidateBtn', 'pos', 'Select candidate', APX_ICON.check], ['.rejectCandidateBtn', 'neg', 'Reject candidate', APX_ICON.x],
                    ['.sendOfferLetterBtn', 'att', 'Send offer letter', APX_ICON.doc], ['.sendContractBtn', 'att', 'Send contract', APX_ICON.doc]].map(function (d) {
                const $e = $h.find(d[0]).first();
                if (!$e.length) return '';
                return '<button type="button" class="apx-iact ' + d[1] + ' ' + d[0].slice(1) + '" data-id="' + apxAttr($e.attr('data-id')) + '" data-applicantstatusid="' + apxAttr($e.attr('data-applicantstatusid')) + '" data-bs-toggle="tooltip" data-bs-placement="top" title="' + d[2] + '" aria-label="' + d[2] + '">' + d[3] + '</button>';
            }).join('');
        }
        function apxActions(r) {
            const aid = btoa(String(r.id)), who = 'data-name="' + apxAttr(r.name) + '" data-role="' + apxAttr(r.position_title) + '"';
            let h = '';
            if (isHrDepartment) {
                h += '<button type="button" class="apx-ivbtn gridview-link" data-row-id="' + apxAttr(r.id) + '" data-status="' + apxAttr(r.ApplicantStatus) + '" data-applicant_id="' + apxAttr(r.applicant_id) + '" ' + who + ' data-country="' + apxAttr(r.countryName) + '">' + APX_ICON.cal + 'Interview details</button>';
            }
            h += '<button type="button" class="apx-iact userApplicants-btn" data-id="' + apxAttr(r.applicant_id) + '" data-bs-toggle="tooltip" data-bs-placement="top" title="View applicant" aria-label="View applicant">' + APX_ICON.eye + '</button>';
            h += '<button type="button" class="apx-iact ApplicantsNotes" data-notes="' + apxAttr(r.Notes) + '" data-id="' + aid + '" ' + who + ' data-bs-toggle="tooltip" data-bs-placement="top" title="Add note" aria-label="Add note">' + APX_ICON.note + '</button>';
            h += '<button type="button" class="apx-iact wai waiInsightsBtn" data-id="' + aid + '" data-bs-toggle="tooltip" data-bs-placement="top" title="WAI Insights — CV vs job description" aria-label="WAI Insights">' + APX_ICON.wai + '</button>';
            h += apxExtraActions(r.action);
            h += '<button type="button" class="apx-kebab" aria-label="More actions" aria-haspopup="menu" data-sid="' + apxAttr(r.applicant_id) + '" data-aid="' + aid + '" data-rowid="' + apxAttr(r.id) + '" data-status="' + apxAttr(r.ApplicantStatus) + '" ' + who + ' data-country="' + apxAttr(r.countryName) + '">' + APX_ICON.dots + '</button>';
            return '<div class="apx-actcell">' + h + '</div>';
        }
        // Grid cards are server-rendered; fill the pieces that share the list's mapping.
        function apxHydrate() {
            $('#grid_main_view .apx-card').each(function () {
                $(this).find('.apx-stage').html(apxStagePill(this.dataset.status, this.dataset.rank));
            });
            // Progress ring: animate from empty to the applicant's hiring progress
            const C = 2 * Math.PI * 54;
            $('#grid_main_view .apx-ring').each(function () {
                const val = this.querySelector('.val'), off = C * (1 - (parseFloat(this.dataset.progress) || 0) / 100);
                if (val) setTimeout(function () { val.style.strokeDashoffset = off; }, 100);
            });
            $('#grid_main_view .apx-country').each(function () {
                const c = this.dataset.country;
                if (c) $(this).html(taFlag(TA_COUNTRY_CODES[c] || '') + escHtml(c));
            });
            $('[data-bs-toggle="tooltip"]').tooltip();
        }

        function datatablelist()
        {
            if ($.fn.DataTable.isDataTable('.table-applicants'))
            {
                $('.table-applicants').DataTable().destroy();
            }
            // Stage / invitation filters are regexes on the raw status columns (server-side column search).
            const fcol = v => v ? { search: v, regex: true, smart: false } : null;

            var divisionTable = $('.table-applicants').DataTable({
                    searching: true,
                    bLengthChange: false,
                    bInfo: true,
                    bAutoWidth: false,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    order: [],
                    layout: { topStart: null, topEnd: null },
                    searchCols: [null, null, null, null, null, null, null, fcol($('#apxStage').val()), fcol($('#apxInv').val())],
                    language: {
                        info: 'Showing _START_ to _END_ of _TOTAL_ applicants',
                        infoEmpty: 'No applicants to show',
                        infoFiltered: '',
                        emptyTable: 'No applicants found.',
                        zeroRecords: 'No applicants match your filters.',
                        processing: 'Loading…'
                    },
                    ajax: {
                        url: "{{ route('resort.ta.getApplicant')}}",
                        type: 'GET',
                        data: function(d) {
                            d.vacanccyId = $("#vacancy-id").val();
                            d.searchTerm = $('.search').val();
                        }
                    },
                    createdRow: function (tr) { $(tr).addClass('main'); },
                    columns: [
                        { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                            if (type !== 'display') return data;
                            const code = TA_COUNTRY_CODES[row.countryName] || '';
                            const sub = [taFlag(code) + escHtml(row.countryName || '—'), escHtml(apxExp(row.total_work_exp)), escHtml(row.position_title || '')].filter(Boolean).join(' · ');
                            return '<div class="apx-appcell"><span class="apx-av">' + escHtml(apxIni(row.name))
                                + (row.profileImg ? '<img src="' + apxAttr(row.profileImg) + '" alt="" onerror="this.remove()">' : '')
                                + '</span><div><div class="nm">' + escHtml(row.name) + '</div><div class="sub">' + sub + '</div></div></div>';
                        }},
                        { data: 'passport_no', name: 'passport_no', render: function (d, t) { return t === 'display' ? escHtml(d || '—') : d; } },
                        { data: 'contact', name: 'contact', render: function (d, t, row) {
                            return t === 'display' ? '<div class="apx-cstack"><div class="e">' + escHtml(row.email) + '</div><div class="p">' + escHtml(d) + '</div></div>' : d;
                        }},
                        { data: 'Application_date', name: 'Application_date' },
                        { data: 'Stage', name: 'Stage', render: function (d, t, row) { return t === 'display' ? apxStagePill(row.ApplicantStatus, row.rank_name) : d; } },
                        { data: 'InvitationStatus', name: 'InvitationStatus', orderable: false, searchable: false, render: function (d, t, row) { return t === 'display' ? apxInvitation(row) : d; } },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'r', render: function (d, t, row) { return t === 'display' ? apxActions(row) : d; } },
                        { data: 'ApplicantStatus', name: 'ApplicantStatus', visible: false, orderable: false, searchable: true, defaultContent: '' },
                        { data: 'InterviewStatus', name: 'InterviewStatus', visible: false, orderable: false, searchable: true, defaultContent: '' }
                    ],
                    drawCallback: function() {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                });
        }
        function DatatableGrid(page)
        {
            $.ajax({
                url:"{{ route('resort.ta.getApplicantWiseGridWise') }}",
                type: "post",
                data:
                {
                    id :$("#vacancy-id").val(),
                    searchTerm : $('.search').val(),
                    page: page || 1,
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response)
                {
                    if (response.success)
                    {
                        $("#grid_main_view").html(response.view);
                        apxHydrate();
                    }
                },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each((errors && errors.errors) || {}, function(key, error)
                        {
                            errs += error + '<br>';
                        });
                        toastr.error(errs || 'Could not load applicants. Please try again.', { positionClass: 'toast-bottom-right'});
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

            // Build confirmation message based on the action
            let actionLabel = $(this).text().trim();
            let confirmMsg = 'Are you sure you want to <strong>' + actionLabel + '</strong> this applicant?';
            if (Rank === "Rejected") {
                confirmMsg = 'Are you sure you want to <strong>Reject</strong> this applicant? This action cannot be undone.';
            } else if (Rank === "Selected") {
                confirmMsg = 'Are you sure you want to <strong>Select</strong> this applicant?';
            } else if (Rank === "Complete") {
                confirmMsg = 'Are you sure you want to mark this round as <strong>Complete</strong>?';
            } else if (Rank === "Sortlisted") {
                confirmMsg = 'Are you sure you want to <strong>Shortlist</strong> this applicant?';
            }

            $("#confirm-action-message").html(confirmMsg);

            // Change confirm button style for destructive actions
            if (Rank === "Rejected") {
                $("#confirm-action-yes").removeClass("ta-btn-primary").addClass("ta-btn-critical").text("Yes, Reject");
            } else {
                $("#confirm-action-yes").removeClass("ta-btn-critical").addClass("ta-btn-primary").text("Yes, Proceed");
            }

            // Store data for the confirm callback
            $("#confirm-action-modal").data({ ApplicantID, Rank, interviewRound, applicantstatusid });
            $("#confirm-action-modal").modal("show");
        });

        // Handle confirmation
        $(document).on("click", "#confirm-action-yes", function () {
            let data = $("#confirm-action-modal").data();
            let ApplicantID = data.ApplicantID;
            let Rank = data.Rank;
            let interviewRound = data.interviewRound;
            let applicantstatusid = data.applicantstatusid;

            $("#confirm-action-modal").modal("hide");

            if ((Rank === "Complete" || Rank === "Rejected" || Rank == "Selected") && isHrDepartment) {
                // HR users: show email template selection modal
                $("#EmailTemplateForm").data("ApplicantID", ApplicantID);
                $("#EmailTemplateForm").data("Rank", Rank);
                $("#EmailTemplateForm").data("interviewRound", interviewRound);
                $("#EmailTemplateForm").data("applicantstatusid", applicantstatusid);

                // Show/hide rejection reason field based on Rank
                if (Rank === "Rejected") {
                    $("#rejectionReasonGroup").show();
                    $("#rejectionReasonText").prop("required", true);
                } else {
                    $("#rejectionReasonGroup").hide();
                    $("#rejectionReasonText").prop("required", false);
                }
                $("#rejectionReasonText").val("");

                // Open the modal for email template selection
                $("#Email-template-selection-modal").modal("show");
            } else {
                // Non-HR users or Round actions: directly update without email
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

            let rejectionReason = Rank === "Rejected" ? $("#rejectionReasonText").val() : null;

            // Make the AJAX request with the email template ID
            makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, emailTemplateID, rejectionReason);

            // Close the modal
            $("#Email-template-selection-modal").modal("hide");
        });

        // Function to make the AJAX request
        function makeAjaxRequest(interviewRound, ApplicantID, applicantstatusid, Rank, emailTemplateID, rejectionReason) {
            // Remove ' Rank' and anything after it from interviewRound
            if (Rank && interviewRound.includes(" Complete")) {
                interviewRound = interviewRound.split(" Complete")[0];
            }

            // Show loader on all progress buttons and disable them
            var $progressBtns = $('.ApprovedOrSortListed');
            $progressBtns.each(function() {
                $(this).prop('disabled', true);
                if (!$(this).find('.spinner-border').length) {
                    $(this).prepend('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>');
                }
            });

            // Also disable confirm button if visible
            $('#confirm-action-yes').prop('disabled', true);

            $.ajax({
                url: "{{ route('resort.ta.ApprovedOrSortApplicantWiseStatus') }}",
                type: "POST",
                data: {
                    interviewRound: interviewRound,
                    ApplicantID: ApplicantID,
                    applicantstatusid: applicantstatusid,
                    Rank: Rank,
                    emailTemplateID: emailTemplateID,
                    rejectionReason: rejectionReason || null,
                    _token: "{{ csrf_token() }}",
                },
                success: function (response) {
                    if (response.success) {
                        $(".userApplicants-wrapper").html(response.view);
                        DatatableGrid();
                        $('tr.details-row').remove();
                        $('td.details-control').closest('tr').removeClass('shown');
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
                complete: function() {
                    // Remove loader and re-enable buttons
                    $progressBtns.each(function() {
                        $(this).prop('disabled', false);
                        $(this).find('.spinner-border').remove();
                    });
                    $('#confirm-action-yes').prop('disabled', false);
                },
            });
        }

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
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        // Update notes in INTERVIEW tab without reloading sidebar
                        let $notesBlock = $('#tabPane4 .notes-display-block');
                        if ($notesBlock.length) {
                            $notesBlock.find('p').text(noteText);
                        } else {
                            let notesHtml = '<div class="intUserApp-block mt-3 notes-display-block"><h6>Notes:</h6><p>' + $('<span>').text(noteText).html() + '</p></div>';
                            let $interviewAssessments = $('#tabPane4 .a-link').last();
                            if ($interviewAssessments.length) {
                                $interviewAssessments.after(notesHtml);
                            } else {
                                $('#tabPane4 .table-responsive').after(notesHtml);
                            }
                        }

                        // Switch to INTERVIEW tab
                        $('#myTab button[data-bs-target="#tabPane4"]').tab('show');
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = 'Failed to save note.';
                    }
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        });
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
                        // Clear textarea
                        $(form).find('textarea[name="Comment"]').val('');

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        // Add comment to INTERVIEW tab without reloading sidebar
                        let $commentsBlock = $('#tabPane4 .comments-display-block');
                        let commentHtml = '<div class="mb-2 p-2" style="background:#f5f5f5; border-radius:6px;"><p class="mb-0">' + $('<span>').text(commentText).html() + '</p></div>';
                        if ($commentsBlock.length) {
                            $commentsBlock.append(commentHtml);
                        } else {
                            let commentsBlockHtml = '<div class="intUserApp-block mt-3 comments-display-block"><h6>Comments:</h6>' + commentHtml + '</div>';
                            let $notesBlock = $('#tabPane4 .notes-display-block');
                            if ($notesBlock.length) {
                                $notesBlock.after(commentsBlockHtml);
                            } else {
                                let $interviewAssessments = $('#tabPane4 .a-link').last();
                                if ($interviewAssessments.length) {
                                    $interviewAssessments.after(commentsBlockHtml);
                                } else {
                                    $('#tabPane4 .table-responsive').after(commentsBlockHtml);
                                }
                            }
                        }

                        // Switch to INTERVIEW tab
                        $('#myTab button[data-bs-target="#tabPane4"]').tab('show');
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = 'Failed to save comment.';
                    }
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

        // ===== OFFER LETTER MODAL =====

        // Toggle manual PDF upload section
        $('#toggleOfferLetterUpload').on('click', function() {
            $('#offerLetterUploadSection').toggle();
            var isVisible = $('#offerLetterUploadSection').is(':visible');
            $(this).html(isVisible
                ? '<i class="fa-solid fa-times me-1"></i> Cancel manual upload'
                : '<i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually');
        });

        // Send Offer Letter - open modal
        $(document).on("click", ".sendOfferLetterBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#offerLetter_ApplicantID").val(applicantId);
            $("#offerLetter_applicantstatusid").val(applicantStatusId);
            // Reset form state
            $('#offerLetterForm')[0].reset();
            window.wisdomDD.sync('#offerLetterTemplateSelect');
            $('#offerLetterUploadSection').hide();
            $('#toggleOfferLetterUpload').html('<i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually');
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
                        window.wisdomDD.sync('#offerLetterTemplateSelect');
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

        // ===== CONTRACT MODAL =====

        // Toggle manual PDF upload section
        $('#toggleContractUpload').on('click', function() {
            $('#contractUploadSection').toggle();
            var isVisible = $('#contractUploadSection').is(':visible');
            $(this).html(isVisible
                ? '<i class="fa-solid fa-times me-1"></i> Cancel manual upload'
                : '<i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually');
        });

        // Send Contract - open modal
        $(document).on("click", ".sendContractBtn", function() {
            var applicantId = $(this).data("id");
            var applicantStatusId = $(this).data("applicantstatusid");
            $("#contract_ApplicantID").val(applicantId);
            $("#contract_applicantstatusid").val(applicantStatusId);
            // Reset form state
            $('#contractForm')[0].reset();
            window.wisdomDD.sync('#contractTemplateSelect');
            $('#contractUploadSection').hide();
            $('#toggleContractUpload').html('<i class="fa-solid fa-upload me-1"></i> Or upload a PDF manually');
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
                        window.wisdomDD.sync('#contractTemplateSelect');
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
        // Basic Salary vs budgeted-salary cap can be entered in a different
        // currency than the cap itself (Currency dropdown is independent of
        // the vacancy's own budgeted-salary currency) — convert to the same
        // currency before comparing. Only DollertoMVR is stored anywhere in
        // this app; MVR->USD divides by it, USD->MVR multiplies by it, never
        // the inverse.
        function convertSalaryToCurrency(amount, fromCurrency, toCurrency, rate) {
            if (!amount || fromCurrency === toCurrency) return amount;
            if (fromCurrency === 'USD' && toCurrency === 'MVR') return amount * rate;
            if (fromCurrency === 'MVR' && toCurrency === 'USD') return amount / rate;
            return amount;
        }

        function checkSalaryOverBudget() {
            var $form = $('#salaryAllocationForm');
            var basicSalary = parseFloat($form.find('#salaryAllocationBasicSalary').val());
            var maxSalary = parseFloat($form.find('#maxBudgetedSalary').val());
            var maxCurrency = $form.find('#maxBudgetedSalaryCurrency').val();
            var enteredCurrency = $form.find('#salaryAllocationCurrency').val();
            var rate = parseFloat($form.find('#dollerToMvrRate').val()) || 15.42;

            var basicSalaryInMaxCurrency = convertSalaryToCurrency(basicSalary, enteredCurrency, maxCurrency, rate);
            var isOverBudget = maxSalary > 0 && basicSalaryInMaxCurrency > maxSalary;

            $form.find('#salaryAllocationBasicSalary').toggleClass('is-invalid border-danger', isOverBudget);
            $form.find('#salaryOverBudgetWarning').toggleClass('d-none', !isOverBudget);

            return isOverBudget;
        }

        $(document).on('input', '#salaryAllocationBasicSalary', checkSalaryOverBudget);
        $(document).on('change', '#salaryAllocationCurrency', checkSalaryOverBudget);

        // Save Salary Allocation
        $(document).on("click", ".saveSalaryAllocation", function() {
            var $btn = $(this);
            var $form = $('#salaryAllocationForm');
            var basicSalary = parseFloat($form.find('input[name="basic_salary"]').val());
            var maxSalary = parseFloat($form.find('#maxBudgetedSalary').val());

            if (!basicSalary || basicSalary <= 0) {
                toastr.error("Please enter a valid basic salary.", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }

            if (checkSalaryOverBudget()) {
                toastr.error("Basic salary cannot exceed budgeted salary of " + maxSalary.toFixed(2) + ".", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }

            // Validate allowances against their budget amounts
            var hasError = false;
            $form.find('.allowance-input').each(function() {
                var val = parseFloat($(this).val());
                var max = parseFloat($(this).data('max'));
                var name = $(this).data('name');
                if (val && max > 0 && val > max) {
                    toastr.error(name + " cannot exceed budget amount of " + max.toFixed(2) + ".", "Error", { positionClass: 'toast-bottom-right' });
                    hasError = true;
                    return false;
                }
            });
            if (hasError) return;

            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...');

            $.ajax({
                url: "{{ route('resort.ta.saveSalaryAllocation') }}",
                type: "POST",
                data: $form.serialize() + '&_token={{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $btn.text('Update Salary Allocation');
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
                    $btn.prop('disabled', false);
                    if ($btn.find('.fa-spinner').length) {
                        $btn.html('Update Salary Allocation');
                    }
                }
            });
        });

        // Generate/regenerate the "Analyze Of AI" summary from notes + comments.
        // The button (TaUserApplicantsSideBar.blade.php) was already rendered
        // on this page — this was the only applicant-detail view missing the
        // click handler that actually calls generate-ai-analysis, so clicking
        // it here did nothing (works on rejected/talentpool/SortlistedapplicantLinkShare).
        $(document).on("click", ".generateAiAnalysis-btn", function () {
            let $btn = $(this);
            let $block = $btn.closest(".ai-analysis-block");
            let applicantId = $block.data("applicant-id");
            let $textBox = $block.find(".ai-analysis-text");
            let originalLabel = $btn.text();
            $btn.text("Generating...").addClass("disabled");
            $.ajax({
                url: "{{ url('resort/talent-acquisition/applicant') }}/" + applicantId + "/generate-ai-analysis",
                type: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function (response) {
                    if (response.success) {
                        $textBox.html('<p class="mb-1"></p>');
                        $textBox.find('p').text(response.analysis);
                        $btn.text("Regenerate");
                    } else {
                        toastr.error(response.message || "Could not generate analysis.", "Error", { positionClass: 'toast-bottom-right' });
                        $btn.text(originalLabel);
                    }
                },
                error: function () {
                    toastr.error("Something went wrong. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                    $btn.text(originalLabel);
                },
                complete: function () {
                    $btn.removeClass("disabled");
                }
            });
        });
    </script>
@include('resorts._dropdown_script')
@endsection

