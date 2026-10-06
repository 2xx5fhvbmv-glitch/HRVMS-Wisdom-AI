@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <style>
        #ta-shortlisted-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ta-shortlisted-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding" id="ta-shortlisted-hero">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="javascript:history.back()" class="btn ta-btn-ghost"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
                    </div>
                </div>
            </div>


            <div id="spx">
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                            <div class="input-group">
                                <input type="search" class="form-control" id="spxSearch" placeholder="Search" autocomplete="off">
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="spxStage">
                                <option value="" selected>All stages</option>
                                <option value="HR Shortlisted">HR Shortlisted</option>
                                <option value="EXCOM Shortlisted">EXCOM Shortlisted</option>
                            </select>
                            <div class="dd" data-target="#spxStage">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All stages</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Stage">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All stages</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="HR Shortlisted"><span class="dd-nm">HR Shortlisted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="EXCOM Shortlisted"><span class="dd-nm">EXCOM Shortlisted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="spxIv">
                                <option value="" selected>All interview status</option>
                                <option value="Slot Booked">Slot booked</option>
                                <option value="Slot Not Booked">Slot not booked</option>
                            </select>
                            <div class="dd" data-target="#spxIv">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All interview status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Interview status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All interview status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Slot Booked"><span class="dd-nm">Slot booked</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Slot Not Booked"><span class="dd-nm">Slot not booked</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="tbl" id="SortlistedApplicants">
                        <thead>
                            <tr>
                                <th style="width:26px"></th>
                                <th>Applicant</th>
                                <th>Stage</th>
                                <th>Nationality</th>
                                <th>Interview</th>
                                <th>Interview status</th>
                                <th class="spx-actcell" style="text-align:right">Action</th>
                                <th></th><th></th><th></th><th></th><th></th><th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            {{-- Frosted kebab menu (inside #spx, outside the table's scroll area so it is never clipped) --}}
            <div class="kmenu" id="spxMenu" role="menu">
                <div class="kmi userApplicants-btn" role="menuitem" data-m="profile"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View profile</div>
                <div class="kmi" role="menuitem" data-m="copy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>Copy meeting link</div>
            </div>
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
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <a href="javascript:void(0)"  data-bs-dismiss="modal"class="btn ta-btn-primary" >Submit</a>
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
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>
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
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="rescheduleInterview-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reschedule Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rescheduleInterviewForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="interview_id" id="reschedule_interview_id">
                        <div class="mb-3">
                            <label class="form-label">New date</label>
                            <input type="date" class="form-control" name="TimeSlotsFormdate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resort time</label>
                            <input type="text" class="form-control" name="ResortInterviewtime" placeholder="e.g. 10:00 AM" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Candidate's local time</label>
                            <input type="text" class="form-control" name="ApplicantInterviewtime" placeholder="e.g. 11:30 AM" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting link (optional)</label>
                            <input type="text" class="form-control" name="MeetingLink" placeholder="https://">
                        </div>
                        <p class="small text-muted mb-0">If the candidate was already invited, they are told the old slot is cancelled and a new invitation is sent.</p>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-primary">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="removeShortlist-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove from Shortlist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="removeShortlistForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="ApplicantStatus_id" id="remove_applicant_status_id">
                        <p class="mb-2">The applicant is moved to <strong>Rejected</strong> and any open interview slot is cancelled.</p>
                        <textarea class="form-control" name="reason" rows="3" maxlength="1000" placeholder="Reason (required)" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit" class="btn ta-btn-critical">Remove</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="userApplicants-wrapper ">

    </div>
    @endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
<style>
#spx{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9; --g4:#C7CDCF;
  --line:#E2EBEC; --line-2:#EEF4F4; --bg:#EEF2F2; --card:#fff;
  --ok:#1F9D6B; --ok-bg:#E7F4EE; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --violet:#6B5FC7; --violet-bg:#EEE9FB; --info:#1E7A85; --info-bg:#E2F0F2; --lime:#E0FF02;
  --shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.06);
  --spring:cubic-bezier(.34,1.56,.64,1);
  --font:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
#spx .pill{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
#spx .pill.ok{background:var(--ok-bg);color:var(--ok)}
#spx .pill.info{background:var(--info-bg);color:var(--info)}
#spx .pill.warn{background:var(--warn-bg);color:var(--warn)}
#spx .pill.teal{background:var(--teal-soft);color:var(--teal)}
#spx .pill.muted{background:var(--line-2);color:var(--g2)}
#spx .scrollwrap{border:1px solid var(--line);border-radius:14px;overflow:auto}
#spx .tbl{border-collapse:separate;border-spacing:0;width:100%;font-size:12.5px;min-width:1020px}
#spx .tbl th,#spx .tbl td{padding:12px 14px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--line-2);vertical-align:middle}
#spx .tbl thead th{position:sticky;top:0;background:var(--teal-soft);z-index:2;font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
#spx .tbl thead th.r,#spx .tbl td.r{text-align:right}
#spx .tbl tbody tr.main{cursor:pointer}
#spx .tbl tbody tr.main:hover td{background:#fafcfc}
#spx .appcell{display:flex;align-items:center;gap:11px;min-width:0}
#spx .spx-av{flex:none;width:36px;height:36px;border-radius:50%;background:#E1EBEC;color:var(--teal);font-size:11px;font-weight:600;display:grid;place-items:center;overflow:hidden;border:2px solid #fff;box-shadow:0 0 0 1.5px var(--teal-3)}
#spx .spx-av img{width:100%;height:100%;object-fit:cover}
#spx .appcell .nm{font-size:13.5px;font-weight:500;color:var(--ink)}
#spx .appcell .sub{font-size:11px;color:var(--muted);margin-top:1px}
#spx .iv .ivd{font-size:13px;font-weight:500;color:var(--g1)}
#spx .iv .ivt{font-size:11px;color:var(--muted);margin-top:2px}
#spx .iv .ivt b{font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums}
#spx .iv .ivt .sep{color:var(--g4);margin:0 5px}
#spx .flg{font-size:13.5px;margin-right:5px;line-height:1;vertical-align:-1px}
#spx .iv .none{font-size:12.5px;color:var(--faint)}
#spx .chev{transition:transform .2s}
#spx tr.main.open .chev{transform:rotate(90deg)}
#spx .detail td{background:#fafcfc;border-bottom:1px solid var(--line-2)}
#spx .detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px 26px;padding:4px 2px}
#spx .detail-grid .d-k{font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--faint)}
#spx .detail-grid .d-v{font-size:13px;color:var(--g1);margin-top:3px}
#spx .actcell{display:flex;align-items:center;gap:7px;justify-content:flex-end}
#spx .btn-s{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;border-radius:9px;padding:7px 12px;border:1px solid transparent;white-space:nowrap;transition:background .15s,border-color .15s,transform .15s var(--spring)}
#spx .btn-s:active{transform:scale(.96)}
#spx .btn-s svg{width:13px;height:13px}
#spx .btn-s.spx-join{background:var(--teal);color:#fff}
#spx .btn-s.spx-join:hover{background:var(--teal-2)}
#spx .btn-s.spx-share{background:#fff;color:var(--teal);border-color:var(--teal)}
#spx .btn-s.spx-share:hover{background:var(--teal-soft)}
#spx .iact{width:30px;height:30px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--teal);display:grid;place-items:center}
#spx .iact:hover{border-color:var(--teal);background:var(--teal-soft)}
#spx .iact svg{width:14px;height:14px}
#spx .kebab{background:none;border:none;color:var(--faint);width:30px;height:30px;border-radius:8px;display:grid;place-items:center}
#spx .kebab:hover{background:var(--line-2);color:var(--g1)}
#spx .kmenu{position:fixed;width:198px;border-radius:14px;padding:6px;z-index:100;display:none;
  background:rgba(255,255,255,.9);-webkit-backdrop-filter:blur(20px) saturate(150%);backdrop-filter:blur(20px) saturate(150%);
  border:1px solid var(--line);box-shadow:0 2px 6px rgba(1,70,83,.08),0 18px 40px rgba(1,70,83,.16)}
#spx .kmenu.is-on{display:block;animation:kpop .14s ease}
@keyframes kpop{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
#spx .kmi{display:flex;align-items:center;gap:11px;padding:10px 11px;border-radius:9px;font-size:13px;font-weight:500;color:var(--teal);cursor:pointer}
#spx .kmi svg{width:16px;height:16px;flex:none}
#spx .kmi:hover{background:var(--teal-soft)}
#spx .kmi.del{color:var(--err)}
#spx .kmi.del:hover{background:var(--err-bg)}
#spx .kmsep{height:1px;background:var(--line-2);margin:3px 8px}
@media (prefers-reduced-transparency:reduce){#spx .kmenu{background:#fff;-webkit-backdrop-filter:none;backdrop-filter:none}}
#spx{font-family:var(--font)}
#spx .pill.err{background:var(--err-bg);color:var(--err)}
#spx .tbl{min-width:1020px;margin:0}
#spx .tbl td.spx-chevcell{width:26px;padding-right:0}
#spx .tbl tbody tr.main.dt-hasChild .chev{transform:rotate(90deg)}
#spx .tbl .detail td{background:#fafcfc}
#spx .tbl td.spx-actcell,#spx .tbl th.spx-actcell{text-align:right}
#spx .spx-btn{text-decoration:none}
#spx a.btn-s,#spx button.btn-s{cursor:pointer}
#spx .btn-s.wait{background:var(--line-2);color:var(--g2);cursor:default}
#spx .ccode{display:inline-block;font-size:9.5px;font-weight:600;letter-spacing:.3px;color:var(--teal);background:var(--teal-soft);border-radius:5px;padding:1px 5px;margin-right:6px;vertical-align:1px}
#spx .iv .ivt .ccode{margin-right:4px}
#spx .flagimg{width:20px;height:14px;object-fit:cover;border-radius:3px;box-shadow:0 0 0 1px rgba(1,70,83,.12);margin-right:6px;vertical-align:-2px}
#spx .iv .ivt .flagimg{margin-right:4px}
#spx .tbl thead th .dt-column-title{white-space:nowrap}
#spx .tbl thead th.dt-orderable-asc span.dt-column-order:before,
#spx .tbl thead th.dt-orderable-asc span.dt-column-order:after,
#spx .tbl thead th.dt-orderable-desc span.dt-column-order:before,
#spx .tbl thead th.dt-orderable-desc span.dt-column-order:after{opacity:0}
#spx .tbl thead th.dt-ordering-asc span.dt-column-order:before,
#spx .tbl thead th.dt-ordering-desc span.dt-column-order:after{opacity:1;color:var(--teal);font-size:.7em}
#spx .tbl thead th.dt-ordering-asc span.dt-column-order:after,
#spx .tbl thead th.dt-ordering-desc span.dt-column-order:before{display:none}
@media (prefers-reduced-motion:reduce){#spx *{transition:none!important;animation:none!important}}

</style>

@endsection

@section('import-scripts')
<script>

$(document).ready(function() {


    // ---- Shortlisted applicants (DataTables, server-side — same endpoint, sort and paging as before) ----
    const escHtml = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const SPX_CODES = {Afghanistan:'AF',Albania:'AL',Algeria:'DZ',Argentina:'AR',Australia:'AU',Austria:'AT',Bangladesh:'BD',Belgium:'BE',Bhutan:'BT',Brazil:'BR',Bulgaria:'BG',Cambodia:'KH',Canada:'CA',China:'CN',Colombia:'CO',Croatia:'HR','Czech Republic':'CZ',Denmark:'DK',Egypt:'EG',Ethiopia:'ET',Fiji:'FJ',Finland:'FI',France:'FR',Germany:'DE',Ghana:'GH',Greece:'GR',Hungary:'HU',India:'IN',Indonesia:'ID',Iran:'IR',Iraq:'IQ',Ireland:'IE',Italy:'IT',Japan:'JP',Jordan:'JO',Kenya:'KE',Kuwait:'KW',Lebanon:'LB',Madagascar:'MG',Malaysia:'MY',Maldives:'MV',Mauritius:'MU',Mexico:'MX',Morocco:'MA',Myanmar:'MM',Nepal:'NP',Netherlands:'NL','New Zealand':'NZ',Nigeria:'NG',Norway:'NO',Oman:'OM',Pakistan:'PK',Peru:'PE',Philippines:'PH',Poland:'PL',Portugal:'PT',Qatar:'QA',Romania:'RO',Russia:'RU','Saudi Arabia':'SA',Serbia:'RS',Seychelles:'SC',Singapore:'SG','South Africa':'ZA','South Korea':'KR',Spain:'ES','Sri Lanka':'LK',Sweden:'SE',Switzerland:'CH',Syria:'SY',Thailand:'TH',Tunisia:'TN',Turkey:'TR',Uganda:'UG',Ukraine:'UA','United Arab Emirates':'AE','United Kingdom':'GB','United States':'US',Uzbekistan:'UZ',Vietnam:'VN',Zimbabwe:'ZW'};
    // Real flag images (bare emoji don't render on Windows): the app's own Maldives asset, and the same flagcdn.com PNGs the app already
    // uses for countries.flag_url. If an image can't load, it falls back to a 2-letter code chip.
    const SPX_MV_FLAG = @json(URL::asset('resorts_assets/images/flag-maldives.webp'));
    const spxChip = c => c ? `<span class="ccode">${c}</span>` : '';
    const spxFlag = c => {
        if (!c) return '';
        const src = c === 'MV' ? SPX_MV_FLAG : 'https://flagcdn.com/w40/' + c.toLowerCase() + '.png';
        return `<img class="flagimg" src="${src}" alt="${c}" loading="lazy" data-c="${c}" onerror="this.outerHTML='<span class=&quot;ccode&quot;>'+this.dataset.c+'</span>'">`;
    };
    const spxIni = n => (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')) || '?';
    const spxB64 = v => btoa(String(v));
    const SPX_STATUS = {
        'Slot Booked': ['Slot booked', 'teal'], 'Slot Not Booked': ['Slot not booked', 'warn'], 'Pending Review': ['Pending review', 'warn'],
        'Invitation Sent': ['Invitation sent', 'info'], 'Invitation Rejected': ['Invitation rejected', 'err']
    };
    const spxStage = row => String(row.Stage || '').replace(/<[^>]*>/g, '').trim();

    function spxInterview(row) {
        if (!row.InterViewDate || row.InterViewDate === '-') return '<div class="iv"><span class="none">Not scheduled</span></div>';
        const t = v => (v && v !== '-') ? escHtml(v) : '—';
        const natCode = SPX_CODES[row.Nationality] || '';
        return `<div class="iv"><div class="ivd">${escHtml(row.InterViewDate)}</div><div class="ivt"><span title="Maldives time">${spxFlag('MV')}<b>${t(row.MalidivanTime)}</b></span><span class="sep">·</span><span title="${escHtml(row.Nationality)} · applicant local time">${spxFlag(natCode)}<b>${t(row.ApplicantTime)}</b></span></div></div>`;
    }

    function spxPrimary(row) {
        const st = row.InterviewStatus, join = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>';
        const share = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v14"/></svg>';
        if (st === 'Slot Booked' && row.MeetingLink) return `<a class="btn-s spx-join" href="${escHtml(row.MeetingLink)}" target="_blank" rel="noopener" title="Start interview">${join}Join</a>`;
        if (st === 'Slot Booked') return `<button type="button" class="btn-s spx-share ApplicantShareLink" data-interview_id="${spxB64(row.Interview_id)}" title="Add the meeting link">${share}Share link</button>`;
        if (st === 'Pending Review') return '<span class="btn-s wait" title="Pending review — email not sent yet">Awaiting</span>';
        if (st === 'Invitation Sent') return '<span class="btn-s wait" title="Invitation sent — awaiting response">Awaiting reply</span>';
        // Slot not booked / invitation declined → start the existing interview-request flow
        return `<button type="button" class="btn-s spx-share SortlistedEmployee" data-resort_id="" data-applicantid="${spxB64(row.Applicant_id)}" data-applicantstatus_id="${spxB64(row.ApplicantStatus_id)}" title="Send interview request">${share}Share link</button>`;
    }

    const SpxTable = $('#SortlistedApplicants').DataTable({
        searching: true,
        layout: { topStart: null, topEnd: null },   // search is driven by the toolbar below, not DataTables' own box
        bLengthChange: false,
        bInfo: true,
        bAutoWidth: false,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order: [[7, 'desc']],
        createdRow: function (tr) { $(tr).addClass('main'); },
        ajax: { url: '{{ route("resort.ta.shortlistedapplicants") }}', type: 'GET' },
        columns: [
            { data: null, orderable: false, searchable: false, className: 'spx-chevcell', defaultContent: '<svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--faint)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>' },
            { data: 'first_name', name: 'first_name', render: function (d, t, row) {
                const name = ((row.first_name || '') + ' ' + (row.last_name || '')).replace(/^./, c => c.toUpperCase()).trim();
                const pm = /<img[^>]*src="([^"]+)"/.exec(row.Applicants || ''), photo = pm ? pm[1] : '';
                const img = photo ? `<img src="${escHtml(photo)}" alt="${escHtml(name)}" data-i="${spxIni(name)}" onerror="this.parentNode.textContent=this.dataset.i">` : spxIni(name);
                return `<div class="appcell"><span class="spx-av">${img}</span><div><div class="nm userApplicants-btn" data-id="${spxB64(row.ApplicantStatus_id)}" style="cursor:pointer">${escHtml(name)}</div><div class="sub">${escHtml(row.Position)}</div></div></div>`;
            }},
            { data: 'Stage', name: 'Stage', render: function (d, t, row) {
                const s = spxStage(row), cls = /^EXCOM/.test(s) ? 'ok' : (/^HR/.test(s) ? 'info' : 'teal');
                return `<span class="pill ${cls}">${escHtml(s)}</span>`;
            }},
            { data: 'Nationality', name: 'Nationality', render: function (d) { return spxFlag(SPX_CODES[d] || '') + escHtml(d); } },
            { data: 'InterViewDate', name: 'InterViewDate', render: function (d, t, row) { return spxInterview(row); } },
            { data: 'InterviewStatus', name: 'InterviewStatus', render: function (d) {
                const s = SPX_STATUS[d] || [d || 'Slot not booked', 'muted'];
                return `<span class="pill ${s[1]}">${escHtml(s[0])}</span>`;
            }},
            { data: 'Action', name: 'Action', orderable: false, searchable: false, className: 'spx-actcell', render: function (d, t, row) {
                const sid = spxB64(row.ApplicantStatus_id);
                return `<div class="actcell">${spxPrimary(row)}
                    <button type="button" class="iact userApplicants-btn" title="View applicant" data-id="${sid}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    <button type="button" class="kebab" aria-label="More actions" data-pid="${sid}" data-link="${escHtml(row.MeetingLink || '')}"><svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg></button>
                </div>`;
            }},
            { data: 'created_at', visible: false, searchable: false },
            // hidden, searchable-only columns so the toolbar search still matches position / contact fields shown in the row-expand
            { data: 'Position', name: 'Position', visible: false },
            { data: 'Email', name: 'Email', visible: false },
            { data: 'Contact', name: 'Contact', visible: false },
            { data: 'AppliedDate', name: 'AppliedDate', visible: false },
            { data: 'last_name', name: 'last_name', visible: false },
        ],
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Toolbar → existing DataTables request params (global search + per-column search; no new endpoint)
    let spxDebounce;
    $('#spxSearch').on('keyup', function () {
        clearTimeout(spxDebounce);
        const v = this.value;
        spxDebounce = setTimeout(function () { SpxTable.search(v).draw(); }, 350);
    });
    $(document).on('change', '#spxStage', function () { SpxTable.column('Stage:name').search(this.value).draw(); });
    $(document).on('change', '#spxIv', function () { SpxTable.column('InterviewStatus:name').search(this.value).draw(); });

    // Row interactions: chevron toggles the detail row; anywhere else opens the profile (same handler as the name).
    $(document).on('click', '#SortlistedApplicants tbody tr.main', function (e) {
        const $t = $(e.target);
        if ($t.closest('.spx-actcell, .userApplicants-btn, a, button').length) return;
        if ($t.closest('.spx-chevcell').length) {
            const row = SpxTable.row(this), d = row.data();
            if (row.child.isShown()) { row.child.hide(); $(this).removeClass('dt-hasChild'); }
            else {
                const item = (k, v) => `<div><div class="d-k">${k}</div><div class="d-v">${v}</div></div>`;
                row.child(`<div class="detail-grid">${item('Email', escHtml(d.Email) || '—')}${item('Contact', escHtml(d.Contact) || '—')}${item('Applied date', escHtml(d.AppliedDate) || '—')}</div>`, 'detail').show();
                $(this).addClass('dt-hasChild');
            }
            return;
        }
        $(this).find('.nm.userApplicants-btn').first().trigger('click');
    });

    // Frosted kebab menu
    const $spxMenu = $('#spxMenu');
    function spxCloseMenu() { $spxMenu.removeClass('is-on'); }
    $(document).on('click', '#spx .kebab', function (e) {
        e.stopPropagation();
        const d = this.dataset;
        $spxMenu.find('[data-m="profile"]').attr('data-id', d.pid).data('id', d.pid);
        $spxMenu.find('[data-m="copy"]').attr('data-link', d.link).toggle(!!d.link);
        $spxMenu.addClass('is-on');
        const r = this.getBoundingClientRect(), w = $spxMenu.outerWidth() || 198, h = $spxMenu.outerHeight() || 100;
        let top = r.bottom + 6; if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
        $spxMenu.css({ left: Math.max(8, r.right - w) + 'px', top: top + 'px' });
    });
    $spxMenu.on('click', '[data-m="copy"]', function () {
        const link = this.getAttribute('data-link');
        const done = () => toastr.success('Meeting link copied.', 'Success', { positionClass: 'toast-bottom-right' });
        if (navigator.clipboard && link) navigator.clipboard.writeText(link).then(done);
    });
    $(document).on('click', function (e) { if (!$(e.target).closest('#spx .kebab, #spxMenu').length || $(e.target).closest('#spxMenu .kmi').length) spxCloseMenu(); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') spxCloseMenu(); });
    window.addEventListener('scroll', spxCloseMenu, true);

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

    // Basic Salary vs budgeted-salary cap can be entered in a different
    // currency than the cap itself (Currency dropdown is independent of
    // the vacancy's own budgeted-salary currency) — convert to the same
    // currency before comparing. Only DollertoMVR is stored anywhere in
    // this app; MVR->USD divides by it, USD->MVR multiplies by it, never
    // the inverse. This page (Shortlisted Applicants To Share Link) loads
    // the same sidebar partial as the Talent Pool page but never had this
    // validation JS ported over — the red warning and the Save button
    // were both non-functional here.
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

    // Save Salary Allocation — this button rendered on this page (via the
    // shared sidebar partial) but had no click handler bound at all.
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

        const [hours, minutes] = timeValue.split(":");
        const period = hours >= 12 ? "PM" : "AM";
        const formattedHours = hours % 12 || 12;
        let MalidivanManualTime1 = formattedHours + ":" + minutes + " " + period;
        $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1);

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

        // ---- Reschedule / remove from shortlist / copy booking link
        function taErr(xhr) {
            var m = (xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {}).flat().join(' '))) || 'Something went wrong.';
            toastr.error(m, 'Error', { positionClass: 'toast-bottom-right' });
        }
        $(document).on('click', '.CopyBookingLink', function () {
            var link = $(this).data('link');
            var done = function () { toastr.success('Booking link copied.', 'Success', { positionClass: 'toast-bottom-right' }); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(link).then(done); }
            else { var $t = $('<textarea>').val(link).appendTo('body').select(); document.execCommand('copy'); $t.remove(); done(); }
        });
        $(document).on('click', '.RescheduleInterview', function () {
            $('#rescheduleInterviewForm')[0].reset();
            $('#reschedule_interview_id').val($(this).data('interview_id'));
            $('#rescheduleInterview-modal').modal('show');
        });
        $('#rescheduleInterviewForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
            $.ajax({
                url: "{{ route('resort.ta.RescheduleInterview') }}", type: 'POST', data: $(this).serialize(),
                success: function (res) {
                    $('#rescheduleInterview-modal').modal('hide');
                    $('#SortlistedApplicants').DataTable().ajax.reload();
                    if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    // Re-send the invitation straight away when the interview already has an email template.
                    if (res.email_template_id && confirm('Send the new invitation to the candidate now?')) {
                        $.post("{{ route('resort.ta.SendInterviewEmail') }}", { interview_id: res.interview_id, email_template_id: res.email_template_id, _token: '{{ csrf_token() }}' })
                            .done(function (r) { toastr[r.success ? 'success' : 'error'](r.message, r.success ? 'Success' : 'Error', { positionClass: 'toast-bottom-right' }); $('#SortlistedApplicants').DataTable().ajax.reload(); })
                            .fail(taErr);
                    }
                },
                error: taErr,
                complete: function () { $btn.prop('disabled', false); }
            });
        });
        $(document).on('click', '.RemoveFromShortlist', function () {
            $('#removeShortlistForm')[0].reset();
            $('#remove_applicant_status_id').val($(this).data('id'));
            $('#removeShortlist-modal').modal('show');
        });
        $('#removeShortlistForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
            $.ajax({
                url: "{{ route('resort.ta.RemoveFromShortlist') }}", type: 'POST', data: $(this).serialize(),
                success: function (res) {
                    $('#removeShortlist-modal').modal('hide');
                    toastr[res.success ? 'success' : 'error'](res.message, res.success ? 'Success' : 'Error', { positionClass: 'toast-bottom-right' });
                    $('#SortlistedApplicants').DataTable().ajax.reload();
                },
                error: taErr,
                complete: function () { $btn.prop('disabled', false); }
            });
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

                            $("#shareMeetLink-modal").modal("hide");
                            $('#SortlistedApplicants').DataTable().ajax.reload();
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

        // Download single file
        $(document).on("click", ".DownloadFile", function () {
            let fileId = $(this).data("id");
            let fileFlag = $(this).data("flag");
            $.ajax({
                url: "{{ route('resort.ta.DownloadFile') }}",
                type: "POST",
                data: { id: fileId, flag: fileFlag, "_token": "{{ csrf_token() }}" },
                success: function(response) {
                    if (response.success) {
                        let fileUrl = response.NewURLshow;
                        let mimeType = response.mimeType.toLowerCase();
                        let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (imageTypes.includes(mimeType)) {
                            window.open(fileUrl, '_blank');
                        } else {
                            window.location.href = fileUrl;
                        }
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        });

        // Generate/regenerate the "Analyze Of AI" summary from notes + comments
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

        // Download All Files — a single .zip named after the candidate +
        // position, instead of opening every document in its own tab.
        $(document).on("click", ".DownloadAllFiles", function () {
            let fileId = $(this).data("id");
            window.location.href = "{{ route('resort.ta.DownloadAllFilesZip', ':id') }}".replace(':id', fileId);
        });

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
@include('resorts._dropdown_script')
@endsection

