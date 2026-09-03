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
                            <a href="#" class="btn btn-grid active"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="#" class="btn btn-list"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
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
                                    @if($isHrDepartment)
                                    <th></th>
                                    @endif
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main">
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
                            <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                            <button type="submit"  class="btn ta-btn-primary">Submit</button>
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
                            class="btn ta-btn-secondary btn-sm">Share
                            Meeting Link</a>
                    </div> -->
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <a href="#" class="btn ta-btn-primary">Submit</a>
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
    <div class="modal fade" id="wai-insights-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-robot me-2"></i>WAI Insights</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="wai-insights-body">
                    <!-- filled by JS -->
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Close</a>
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
@endsection

@section('import-scripts')
    <script>
        var isHrDepartment = @json($isHrDepartment);
        var defaultApplicantPicture = "{{ url(config('settings.default_picture')) }}";
        $(document).ready(function() {
            datatablelist();
            // Grid is the default visible view now — #grid_main_view is
            // server-rendered empty and only ever populated by DatatableGrid(),
            // previously fired only on a manual .btn-grid click, so the grid
            // showed blank on load until the user toggled away and back.
            DatatableGrid();
            $('.table-applicants tbody').empty();

            // WAI Insights — score this applicant's CV against the position's Job Description.
            $(document).on('click', '.waiInsightsBtn', function () {
                var id = $(this).data('id');
                var $body = $('#wai-insights-body');
                $body.html('<div class="py-4">'
                    + '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                    + '<p class="mt-3 mb-0">Wisdom AI is reviewing the CV against the job description…</p>'
                    + '<small class="text-muted">This can take up to a minute.</small></div>');
                $('#wai-insights-modal').modal('show');
                $.ajax({
                    url: "{{ route('resort.ta.WaiInsights') }}",
                    type: 'POST',
                    global: false, // we show our own modal spinner — suppress the page-level global loader
                    data: { id: id, _token: "{{ csrf_token() }}" },
                    success: function (res) {
                        if (res && res.success) {
                            $body.html(
                                '<div class="mb-2"><span class="fw-bold">' + (res.applicant || 'Applicant') + '</span>'
                                + '<div class="text-muted small">Position: ' + (res.position || '—') + '</div></div>'
                                + '<div style="font-size:48px;font-weight:700;line-height:1.1;" class="text-' + res.color + '">' + res.score + '%</div>'
                                + '<div class="mb-2"><span class="badge bg-' + res.color + '">' + res.label + '</span></div>'
                                + '<div class="progress" style="height:10px;"><div class="progress-bar bg-' + res.color + '" role="progressbar" style="width:' + res.score + '%;"></div></div>'
                                + (res.summary ? '<p class="mt-3 mb-0 text-start">' + $('<div>').text(res.summary).html() + '</p>' : '')
                                + '<small class="text-muted d-block mt-3">AI-estimated match between the applicant\'s CV and the job description. Use as guidance alongside your own review.</small>'
                            );
                        } else {
                            $body.html('<div class="py-3"><i class="fa-solid fa-circle-info text-warning fa-2x mb-2"></i>'
                                + '<p class="mb-0">' + ((res && res.message) || 'Could not generate insights.') + '</p></div>');
                        }
                    },
                    error: function () {
                        $body.html('<div class="py-3"><i class="fa-solid fa-triangle-exclamation text-danger fa-2x mb-2"></i>'
                            + '<p class="mb-0">Something went wrong contacting Wisdom AI. Please try again.</p></div>');
                    }
                });
            });

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
                        if(status == "Selected")
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
                                        let postInterviewStatuses = ['Complete', 'Selected', 'Rejected', 'Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected'];
                                        if(postInterviewStatuses.includes(status))
                                        {
                                            newTag =`<span class="badge bg-success">Round Completed</span>`;
                                        }
                                        else if(response.data.InterviewStatus == 'Slot Not Booked' || response.data.InterviewStatus == 'Invitation Rejected')
                                        {
                                            newTag =`<a href="javascript:void(0)"
                                                data-Resort_id="${response.data.Resort_id}"
                                                data-ApplicantID="${response.data.ApplicantID}"
                                                data-ApplicantStatus_id="${response.data.ApplicantStatus_id}"
                                                class="btn ta-btn-attention btn-small SortlistedEmployee">Send Interview Invitation</a>`;
                                        }
                                        else if(response.data.InterviewStatus == "Pending Review")
                                        {
                                            newTag =`<a href="javascript:void(0)"
                                                class="btn ta-btn-attention btn-small confirmPendingReview"
                                                data-interview_id="${response.data.Interview_id}"
                                                data-email_template_id="${response.data.EmailTemplateId || ''}"
                                                >Confirm & Send</a>`;
                                        }
                                        else if(response.data.InterviewStatus == "Invitation Sent")
                                        {
                                            newTag =`<span class="badge bg-info text-white">Invitation Sent - Awaiting Response</span>`;
                                        }
                                        else if(response.data.InterviewStatus =="Slot Booked" && !isNaN(response.data.MeetingLink) )
                                        {

                                            newTag =`<a class="btn ta-btn-secondary btn-small ApplicantShareLink"
                                            data-round="${response.data.round}"
                                                data-rank_name="${response.data.rank_name}"
                                            data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Add Interview Link</a>`;

                                        }
                                        else
                                        {
                                            newTag =`<a href="${response.data.MeetingLink}" target="_blank" class="btn ta-btn-secondary btn-small"
                                            data-rond="${response.data.round}"
                                            data-rank_name="${response.data.rank_name}"
                                            data-interview_id="${response.data.Interview_id}" href="javascript:void(0)">Start Interview  </a>`;
                                        }

                                        // <a href="#shareMeetLink-modal" data-bs-toggle="modal" class="btn btn-themeSkyblue btn-small">
                                        //                         Share Meeting Link
                                        //                     </a>
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
                                                    nrStatusBadge = `<span class="badge bg-danger">Invitation Rejected</span>${nr.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${nr.interviewRejectionReason}</div>` : ''}`;
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
                                                            <td>${nr.MalidivanTime}</td>
                                                            <td>${nr.ApplicantTime}</td>
                                                            <td>${nrStatusBadge}</td>
                                                            <td>${nrAction}</td>
                                                        </tr>`;
                                        }

                                        // Build past rounds rows
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
                                                    <td>${pr.MalidivanTime}</td>
                                                    <td>${pr.ApplicantTime}</td>
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
                                                            <td>${response.data.MalidivanTime}</td>
                                                            <td>${response.data.ApplicantTime}</td>
                                                            <td>${response.data.InterviewStatus}${response.data.InterviewStatus == 'Invitation Rejected' && response.data.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${response.data.interviewRejectionReason}</div>` : ''}</td>
                                                            <td>
                                                                ${newTag}
                                                            </td>
                                                        </tr>
                                                        ${nextRoundRow}
                                                    </table>
                                                    ${response.data.rejectionReason ? `<div class="mt-2 p-2" style="background:#fff3f3; border-left:3px solid #dc3545; border-radius:4px;">
                                                        <strong class="text-danger">${response.data.applicantStatusRaw == 'Offer Letter Rejected' ? 'Offer Letter' : 'Contract'} Declined</strong>
                                                        <p class="mb-0 mt-1"><strong>Reason:</strong> ${response.data.rejectionReason}</p>
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

                                        $currentRow.after(newRow);
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
                                    console.log(response);
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
                                                nrStatusBadge = `<span class="badge bg-danger">Invitation Rejected</span>${nr.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${nr.interviewRejectionReason}</div>` : ''}`;
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
                                                        <td>${nr.MalidivanTime}</td>
                                                        <td>${nr.ApplicantTime}</td>
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
                                                <td>${pr.MalidivanTime}</td>
                                                <td>${pr.ApplicantTime}</td>
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
                                                        <td>${response.data.MalidivanTime}</td>
                                                        <td>${response.data.ApplicantTime}</td>
                                                        <td>${response.data.InterviewStatus}${response.data.InterviewStatus == 'Invitation Rejected' && response.data.interviewRejectionReason ? `<div class="mt-1 p-1" style="background:#fff3f3; border-left:2px solid #dc3545; border-radius:3px; font-size:12px;"><strong>Reason:</strong> ${response.data.interviewRejectionReason}</div>` : ''}</td>
                                                        <td>
                                                            ${newTag}
                                                        </td>
                                                    </tr>
                                                    ${nextRoundRow}
                                                </table>
                                                ${response.data.rejectionReason ? `<div class="mt-2 p-2" style="background:#fff3f3; border-left:3px solid #dc3545; border-radius:4px;">
                                                    <strong class="text-danger">${response.data.applicantStatusRaw == 'Offer Letter Rejected' ? 'Offer Letter' : 'Contract'} Declined</strong>
                                                    <p class="mb-0 mt-1"><strong>Reason:</strong> ${response.data.rejectionReason}</p>
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
                    index: fileIndex
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
                                <div class="img-circle"><img src="${row.profileImg || defaultApplicantPicture}" alt="user"></div>
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
                        @if($isHrDepartment)
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
                        @endif
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

