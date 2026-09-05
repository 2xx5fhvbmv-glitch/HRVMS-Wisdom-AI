@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #grievance-investigation-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #grievance-investigation-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="grievance-investigation-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span> Grievance And Disciplinery</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div> -->
            </div>
        </div>

        <div class="gvi-wrap">
        @php
            // Priority dot-pill color — Low=positive, Medium=warning, High=error.
            $gviPriority = $Grivance_Parent->Priority ?? 'Low';
            $gviPriorityColor = $gviPriority == 'High' ? 'var(--error)' : ($gviPriority == 'Medium' ? 'var(--warning)' : 'var(--positive)');

            // Confidentiality is data-driven off Grivance_Submission_Type, not
            // hardcoded — this fact pill just states whether the CASE is
            // confidential; it's independent of $canViewIdentity (which is
            // whether THIS viewer specifically has been granted identity
            // disclosure on an already-confidential case).
            $gviConfType = $Grivance_Parent->Grivance_Submission_Type ?? 'NotApplicable';
            $gviIsConfidential = $gviConfType == 'Yes';

            // Attachments — resolved ONCE (this helper does AWS/Storage
            // lookups per file, so calling it repeatedly in a loop would be
            // wasteful) into a plain array, matching the existing Disciplinary
            // Investigation page's non-mutating attachment-list pattern.
            // Kept exactly where it already lived — inside the identity-gated
            // block — since these are the reporter's own uploaded files, not
            // case-level evidence.
            $gviAttachments = $canViewIdentity
                ? \App\Helpers\Common::resolveGrievanceAttachments($Grivance_Parent->Attachements, $path."/".$Grivance_Parent->Grivance_id, $Grivance_Parent->resort_id)
                : [];

            // Initials fallback for the employee avatar, matching the pattern
            // already established on the Disciplinary Investigation page.
            $gviInitials = strtoupper(substr($Grivance_Parent->first_name ?? '', 0, 1) . substr($Grivance_Parent->last_name ?? '', 0, 1)) ?: '?';

            // Follow-up action / investigation stage — humanise the raw
            // camel-case enum values for display only; unmapped values fall
            // back to the raw string rather than guessing.
            $gviActionLabels = [
                'GatherWitnessStatements' => 'Gather witness statements',
                'InspectSite' => 'Inspect site',
                'ReviewDocuments' => 'Review documents',
                'CCTVFootageReview' => 'CCTV footage review',
                'CheckAccessLogs' => 'Check access logs',
                'GatherPhysicalEvidence' => 'Gather physical evidence',
            ];
            $gviStageLabels = [
                'InitialReview' => ['Initial review', 'stage-init'],
                'Delegated' => ['Delegated', 'stage-ongoing'],
                'Ongoing' => ['Ongoing', 'stage-ongoing'],
                'Compiled' => ['Report compiled', 'stage-done'],
            ];
            $gviStatusLabels = [
                'pending' => 'Pending',
                'in_review' => 'In review',
                'resolved' => 'Resolved',
                'rejected' => 'Rejected',
            ];

            // Most-recent-first — a pure view-layer reorder of the same
            // already-fetched collection (the query itself has no orderBy),
            // no new query.
            $gviHistory = ($GrivanceSubmissionHistory ?? collect())->reverse()->values();

            // Threshold for collapsing a long/structured explanation behind
            // the native <details> disclosure instead of rendering inline —
            // measured on stripped text so HTML markup doesn't skew the
            // count. Real data ranges from ~50 to ~2600 chars; 300 cleanly
            // separates the short one-liners from genuine multi-section
            // reports.
            $gviDetailsThreshold = 300;
        @endphp

        {{-- ===== hero ===== --}}
        <div class="row g-3 g-xxl-4 gvi-hero">
            <div class="col-lg-8">
                <div class="gvi-card gvi-summary">
                    <div class="gvi-title">{{ $Grivance_Parent->SubCatName ?? '—' }}</div>
                    <div class="gvi-class">
                        <span class="cat">{{ $Grivance_Parent->CatName }}</span>
                        <span class="chev">&rsaquo;</span>
                        <span class="sub">{{ $Grivance_Parent->SubCatName ?? '—' }}</span>
                    </div>
                    <div class="gvi-desc">
                        <div class="dk">Description</div>
                        <div class="dv">{!! $Grivance_Parent->Grivance_description !!}</div>
                    </div>
                    <div class="gvi-facts">
                        <div class="gvi-fact"><span class="k">Grievance ID</span><span class="pill ref-pill">{{ $Grivance_Parent->Grivance_id }}</span></div>
                        <div class="gvi-fact"><span class="k">Priority</span><span class="pill"><span class="gvi-dot" style="background:{{ $gviPriorityColor }}"></span>{{ $gviPriority }}</span></div>
                        <div class="gvi-fact">
                            <span class="k">Confidentiality</span>
                            @if($gviIsConfidential)
                                <span class="pill lock"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>Confidential</span>
                            @elseif($gviConfType == 'No')
                                <span class="pill">Not confidential</span>
                            @else
                                <span class="pill">Not applicable</span>
                            @endif
                        </div>
                        @if($Grivance_Parent->created_at)
                            <div class="gvi-fact"><span class="k">Created</span><span class="fv">{{ \Carbon\Carbon::parse($Grivance_Parent->created_at)->format('d-M-Y') }}</span></div>
                        @endif
                        @if($canViewIdentity)
                            <div class="gvi-fact">
                                <span class="k">Attachments</span>
                                @if(count($gviAttachments))
                                    <a class="gvi-filefact" href="{{ $gviAttachments[0]['url'] }}" target="_blank" title="{{ $gviAttachments[0]['filename'] }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a5 5 0 01-7.07-7.07l9.19-9.19a3 3 0 014.24 4.24l-9.2 9.19a1 1 0 01-1.41-1.41l8.49-8.49"/></svg>
                                        <span class="fn">{{ $gviAttachments[0]['filename'] }}</span>
                                    </a>
                                    @if(count($gviAttachments) > 1)
                                        <span class="gvi-filemore" title="{{ collect($gviAttachments)->pluck('filename')->slice(1)->implode(', ') }}">+{{ count($gviAttachments) - 1 }}</span>
                                    @endif
                                @else
                                    <span class="gvi-filenone">None</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if($canViewIdentity)
                    <div class="gvi-card gvi-rail">
                        <div class="rh">Employee</div>
                        <div class="who">
                            <img class="gvi-av" src="{{ Common::getResortUserPicture($Grivance_Parent->Parentid) }}" alt=""
                                onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'gvi-av',textContent:'{{ $gviInitials }}'}))">
                            <div class="nm">{{ $Grivance_Parent->first_name }} {{ $Grivance_Parent->last_name }}</div>
                        </div>
                        <div class="klist">
                            <div class="krow"><span class="kk">Department</span><span class="kv">{{ $Grivance_Parent->DepartmentName }}</span></div>
                            <div class="krow"><span class="kk">Position</span><span class="kv">{{ $Grivance_Parent->PositiontName }}</span></div>
                            <div class="krow"><span class="kk">Contact</span><span class="kv">{{ $Grivance_Parent->personal_phone ?: '-' }}</span></div>
                        </div>
                    </div>
                @else
                    <div class="gvi-card gvi-rail locked">
                        <div class="rh">Employee</div>
                        <div class="gvi-lockg"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg></div>
                        <div class="gvi-lockt">Confidential</div>
                        <div class="gvi-locks">Employee identity is withheld on this grievance. Visible only to the assigned committee.</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== history — vertical timeline ===== --}}
        @if(!empty($GrivanceSubmissionHistory) && $GrivanceSubmissionHistory->isNotEmpty())
            <div class="gvi-card gvi-hist">
                <div class="gvi-sec-h">Investigation history</div>
                <div class="gvi-timeline">
                    @foreach($gviHistory as $key => $value)
                        @php
                            $gviStageInfo = $gviStageLabels[$value->investigation_stage] ?? [$value->investigation_stage, 'stage-init'];
                            $gviActionLabel = $gviActionLabels[$value->follow_up_action] ?? $value->follow_up_action;
                            $gviInitialsHist = strtoupper(substr($value->first_name ?? '', 0, 1) . substr($value->last_name ?? '', 0, 1)) ?: '?';
                            $gviExplanationLen = strlen(strip_tags($value->inves_find_recommendations ?? ''));
                        @endphp
                        <div class="gvi-hentry @if($key === 0) latest @endif">
                            <span class="gvi-hdot"></span>
                            <div class="gvi-hcard">
                                <div class="gvi-hhead">
                                    <div class="gvi-hav">{{ $gviInitialsHist }}</div>
                                    <div class="gvi-hwho"><div class="hn">{{ $value->first_name }} {{ $value->last_name }}</div><div class="hr">Committee member</div></div>
                                    @if(!empty($value->investigation_stage))
                                        <span class="gvi-hpill {{ $gviStageInfo[1] }}">{{ $gviStageInfo[0] }}</span>
                                    @endif
                                    @if(!empty($value->follow_up_action))
                                        <span class="gvi-hpill action">{{ $gviActionLabel }}</span>
                                    @endif
                                </div>
                                <div class="gvi-hbody">
                                    @if(!empty($value->follow_up_description))
                                        <div class="gvi-hrow"><div class="hk">Follow-up description</div><div class="hv">{!! $value->follow_up_description !!}</div></div>
                                    @endif
                                    @if(!empty($value->inves_find_recommendations))
                                        <div class="gvi-hrow">
                                            <div class="hk">Grievance explanation</div>
                                            @if($gviExplanationLen > $gviDetailsThreshold)
                                                <details class="gvi-report">
                                                    <summary><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>View full investigation report<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></summary>
                                                    <div class="gvi-report-body">{!! $value->inves_find_recommendations !!}</div>
                                                </details>
                                            @else
                                                <div class="hv">{!! $value->inves_find_recommendations !!}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($GrivanceInvestigationModel && !empty($GrivanceInvestigationModel->investigation_files))
                <div class="gvi-card gvi-hist">
                    <div class="gvi-sec-h">Attachments</div>
                    <div class="table-responsive" style="max-width:400px;">
                        <table class="table gr-history-table">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Attachments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (explode(',', $GrivanceInvestigationModel->investigation_files) as $f)
                                    <tr>
                                        <td>{{ $f }}</td>
                                        <td><a target="_blank" href="{{ \App\Helpers\StorageHelper::temporaryUrl($EveidanceFilePath.'/'. $f) }}">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        @if(isset($Grivance_Parent->Gm_Decision) && !empty($Grivance_Parent->Gm_Decision))
            <div class="gvi-card gvi-hist">
                <div class="gvi-sec-h">GM Response</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Outcome Type</th>
                                <th>Action Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $Grivance_Parent->Gm_Decision }}</td>
                                <td>{!!  $Grivance_Parent->Gm_Resoan  !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ===== outcome / status — read-only summary of the current
             persisted values, complementing (not replacing) the editable
             Outcome Type / Status controls further down in the form. ===== --}}
        <div class="gvi-card gvi-foot">
            <div class="fleft">
                <div class="fitem"><span class="fk">Outcome type</span>
                    @if(!empty($Grivance_Parent->outcome_type))
                        <span class="fv">{{ $Grivance_Parent->outcome_type }}</span>
                    @else
                        <span class="fv none">Not set</span>
                    @endif
                </div>
                <div class="fitem"><span class="fk">Status</span>
                    @if(!empty($Grivance_Parent->status))
                        <span class="fv">{{ $gviStatusLabels[$Grivance_Parent->status] ?? $Grivance_Parent->status }}</span>
                    @else
                        <span class="fv none">Not set</span>
                    @endif
                </div>
            </div>
            @if(!empty($Grivance_Parent->status))
                <span class="gvi-status-pill {{ $Grivance_Parent->status }}"><span class="gvi-dot" style="background:currentColor"></span>{{ $gviStatusLabels[$Grivance_Parent->status] ?? $Grivance_Parent->status }}</span>
            @endif
        </div>

        <div class="gvi-card" style="padding:24px 26px; margin-top:16px;">
        <form id="investigationReportSubmit" data-parsley-validate>
            @csrf
            @if($isCommitteeMember)
            <div class="gvi-card gvi-block">
                <div class="gvi-block-head">@if($Grivance_Parent->Assigned != "No")<span class="gvi-step">1</span>@endif<span class="gvi-sec-h" style="margin-bottom:0">Investigation setup</span></div>
                <div class="row align-items-end g-xl-4 g-3">
                    <div class="col-lg-4 col-md-5 col-sm-6">
                        <label for="assign_to" class="form-label">ASSIGN TO</label>
                        @php $assignToDisabled = $Grivance_Parent->Assigned == "Yes" || $Grivance_Parent->Assigned =='DeliverToHr'; @endphp
                        <select class="form-select dd-native-select" id="assign_to" name="assign_to"
                        @if($assignToDisabled) disabled @else required data-parsley-required-message="Please assign at least one committee member" @endif>
                            <option value=""></option>
                            @if($GrievanceCommitteeMemberParent)
                                @foreach ($GrievanceCommitteeMemberParent as $c)
                                    <option value="{{ $c->id }}" {{ $c->id == $Grivance_Parent->Committee_id ? 'selected' : '' }}>{{ $c->Grivance_CommitteeName }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#assign_to">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" @if($assignToDisabled) disabled aria-disabled="true" @endif>
                                <span class="dd-lbl">Select Committee</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Committee">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a committee…"></div>
                                <div class="dd-scroll">
                                    @if($GrievanceCommitteeMemberParent)
                                        @foreach ($GrievanceCommitteeMemberParent as $c)
                                            <div class="dd-item{{ $c->id == $Grivance_Parent->Committee_id ? ' active' : '' }}" role="option" data-value="{{ $c->id }}"><span class="dd-nm">{{ $c->Grivance_CommitteeName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($Grivance_Parent->Assigned !="DeliverToHr" ||  $Grivance_Parent->status !="resolved")
                        <div class="col-lg-4 col-sm-6">
                            <label for="inves_date" class="form-label">INVESTIGATION START DATE</label>
                            <input type="text" value="{{ isset($GrivanceInvestigationModel->inves_start_date) ? $GrivanceInvestigationModel->inves_start_date :'' }}"
                            @if($Grivance_Parent->Assigned=="No" || isset($GrivanceInvestigationModel->inves_start_date )) readonly @else required data-parsley-required-message="Investigation start date is required" @endif class="form-control datepicker" id="inves_date" name="invesigation_date" placeholder="Choose Date">
                        </div>
                        <div class="col-lg-4 col-sm-4">
                            <label for="exp_date" class="form-label">Anticipated Resolution Date</label>
                            <input type="text" value="{{ isset($GrivanceInvestigationModel->resolution_date) ? $GrivanceInvestigationModel->resolution_date : '' }}" @if($Grivance_Parent->Assigned=="No" || isset($GrivanceInvestigationModel->resolution_date)) readonly @else required data-parsley-required-message="Anticipated resolution date is required" data-parsley-greaterThan="#inves_date" data-parsley-greaterThan-message="Resolution date must be after the investigation start date" @endif class="form-control datepicker" id="exp_date" name="resolution_date" placeholder="Choose Date">
                        </div>
                    @endif
                </div>
            </div>
            @else
            <div class="alert alert-warning">You are not part of the assigned investigation committee for this grievance.</div>
            @endif

            <input type="hidden" name="Grievant_form_id" value="{{ $Grivance_Parent->id}}">
            <input type="hidden" value="{{ ($Grivance_Parent->Assigned =='No') ? 'AssignToComittee':'EditModeForCommittee' }}" name="flag">
            @if($Grivance_Parent->Assigned !="DeliverToHr" ||  $Grivance_Parent->status !="resolved")
                @if($Grivance_Parent->Assigned == "No")
                    {{-- No committee assigned yet — nothing to log an entry against, so this step stays hidden until Step 1's Assign To is submitted. --}}
                @elseif($isCommitteeMember)
                <div class="gvi-card gvi-block">
                    <div class="gvi-block-head"><span class="gvi-step">2</span><span class="gvi-sec-h" style="margin-bottom:0">Investigation entries</span></div>
                    <div class="gvi-entries-hint">Log each stage of the investigation as a separate entry. Add as many as you need.</div>

                <div class="appendHere">
                    <input type="hidden" name="counts" value="1" id="counts">
                    <div class="gvi-entry">
                        <div class="gvi-entry-h">
                            <span class="enum"><span class="ndot">1</span>Investigation entry</span>
                            <span class="gvi-stagechip">Stage not set</span>
                            <button type="button" class="gvi-rm btn eb-btn-critical delete-row-btn" data-id="1" hidden><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>Remove</button>
                            <svg class="caret" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <div class="gvi-entry-b">
                            <div class="field">
                                <label for="inves_find" class="flabel">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                                <textarea class="form-control gr-investigation-editor" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                            </div>
                            <div class="grid2">
                                <div class="field">
                                    <label for="followup_actions" class="flabel">FOLLOW-UP ACTIONS</label>
                                    @php $followUpDisabled = $Grivance_Parent->Assigned=="No"; @endphp
                                    <select class="form-select dd-native-select follow_up_action_id" id="follow_up_action" name="follow_up_action[]" @if($followUpDisabled) disabled @else required data-parsley-required-message="Please select a follow-up action" @endif aria-label="Follow-up actions">
                                        <option value="">Select Follow-Up Action</option>
                                        @if($Grivance_Parent->RequestforStatment !="Yes")
                                            <option value="GatherWitnessStatements">Gather Witness Statements</option>
                                        @endif
                                        <option value="InspectSite">Inspect Site</option>
                                        <option value="ReviewDocuments">Review Documents</option>
                                        <option value="CCTVFootageReview">CCTV Footage Review</option>
                                        <option value="CheckAccessLogs">Check Access Logs</option>
                                        <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                                    </select>
                                    <div class="dd" data-target="#follow_up_action">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" @if($followUpDisabled) disabled aria-disabled="true" @endif>
                                            <span class="dd-lbl">Select Follow-Up Action</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Follow-up action">
                                            <div class="dd-scroll">
                                                @if($Grivance_Parent->RequestforStatment !="Yes")
                                                    <div class="dd-item" role="option" data-value="GatherWitnessStatements"><span class="dd-nm">Gather Witness Statements</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endif
                                                <div class="dd-item" role="option" data-value="InspectSite"><span class="dd-nm">Inspect Site</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="ReviewDocuments"><span class="dd-nm">Review Documents</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="CCTVFootageReview"><span class="dd-nm">CCTV Footage Review</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="CheckAccessLogs"><span class="dd-nm">Check Access Logs</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="GatherPhysicalEvidence"><span class="dd-nm">Gather Physical Evidence</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="field">
                                    <label for="follow_up_description" class="flabel">Follow-up detail</label>
                                    <input type="text" class="form-control" placeholder="Type Here" name="follow_up_description[]" id="follow_up_description" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                                </div>
                            </div>
                            @if($Grivance_Parent->RequestforStatment !="Yes")
                                <div class="field d-none" id="RequestForStatement">
                                    <a href="javascript:void(0)" class="btn eb-btn-accent RequestForStatement" data-id="{{$Grivance_Parent->Grivance_id}}">Request For Statement</a>
                                </div>
                            @endif
                            <div class="field" style="max-width:50%">
                                <label for="inves_stage" class="flabel">INVESTIGATION STAGE</label>
                                @php $stageDisabled = $Grivance_Parent->Assigned=="No"; @endphp
                                <select class="form-select dd-native-select gvi-stage-select" id="investigation_stage" name="investigation_stage[]" aria-label="Investigation stage" @if($stageDisabled) disabled @else required data-parsley-required-message="Please select an investigation stage" @endif>
                                    <option value="">Select a stage</option>
                                    <option value="InitialReview">Initial Review</option>
                                    <option value="Delegated">Delegated</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Compiled">Report Compiled</option>
                                </select>
                                <div class="dd" data-target="#investigation_stage">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" @if($stageDisabled) disabled aria-disabled="true" @endif>
                                        <span class="dd-lbl">Select a stage</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Investigation stage">
                                        <div class="dd-scroll">
                                            <div class="dd-item" role="option" data-value="InitialReview"><span class="dd-nm">Initial Review</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="Delegated"><span class="dd-nm">Delegated</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="Ongoing"><span class="dd-nm">Ongoing</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="Compiled"><span class="dd-nm">Report Compiled</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="resol_notes" class="flabel">RESOLUTION NOTES</label>
                                <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Grivance_Parent->Assigned=="No") readonly @endif placeholder="Type Here..." rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="gvi-add-entry AddMoreGrivance"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>Add another entry</button>
                </div>
                @else
                <div class="alert alert-warning">You are not part of the assigned investigation committee for this grievance.</div>
                @endif

                {{-- Outcome Type / Action Taken / Status / Approval / File Upload
                     are all investigation-decision fields — same restriction as the
                     Assign To and investigation-dates/findings sections above, which
                     already correctly hide behind $isCommitteeMember. These four were
                     left unguarded, so a non-committee viewer (any HR/GM/EXCOM opening
                     the page) saw the "not part of the assigned investigation
                     committee" notice right above a fully live outcome/upload/submit
                     form for the exact same investigation. Request Identity Disclosure
                     below stays outside this gate on purpose — it's a Key Personnel
                     permission, a separate role from committee membership. --}}
                @if($isCommitteeMember)
                    @if($Grivance_Parent->Assigned == "Yes")
                        <div class="col-lg-4 col-sm-6">
                            <label for="outcome_type" class="form-label">OUTCOME TYPE</label>
                            @php
                                $outcomeOptions = $Grivance_Parent->Assigned == "Yes"
                                    ? [['DeliverToHr', 'Resolved — Send To HR'], ['Unresolved', 'Unresolved']]
                                    : [
                                        ['Resolved', 'Resolved'], ['Unresolved', 'Unresolved'], ['Dismissed', 'Dismissed'],
                                        ['OnHold', 'On Hold'], ['WithdrawalbyComplainant', 'Withdrawal by Complainant'],
                                        ['EscalatedforFurtherInvestigation', 'Escalated for Further Investigation'],
                                        ['PolicyImprovementSuggested', 'Policy Improvement Suggested'],
                                    ];
                                $outcomeCompareVal = $Grivance_Parent->Assigned == "Yes" ? null : 'resolved';
                            @endphp
                            <select class="form-select dd-native-select" id="outcome_type" name="outcome_type" aria-label="Outcome type">
                                <option value="">Select Outcome</option>
                                @foreach($outcomeOptions as [$val, $label])
                                    <option value="{{ $val }}" {{ $Grivance_Parent->outcome_type == $val || ($val === 'Resolved' && $Grivance_Parent->outcome_type == 'resolved') ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#outcome_type">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Outcome</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Outcome type">
                                    <div class="dd-scroll">
                                        @foreach($outcomeOptions as [$val, $label])
                                            <div class="dd-item{{ ($Grivance_Parent->outcome_type == $val || ($val === 'Resolved' && $Grivance_Parent->outcome_type == 'resolved')) ? ' active' : '' }}" role="option" data-value="{{ $val }}"><span class="dd-nm">{{ $label }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($Grivance_Parent->Assigned == "DeliverToHr")



                        <div class="col-lg-4 col-sm-6">
                            <label for="action_taken" class="form-label">ACTION TAKEN</label>

                            <select class="form-select dd-native-select" id="action_taken" name="action_taken" aria-label="Action taken">
                                <option value="">Select Action</option>
                                @if($ActionStore->isNotEmpty())
                                    @foreach($ActionStore as $item)
                                        <option value="{{ base64_encode($item->id) }}" {{ ($Grivance_Parent->action_taken == $item->id)  ?'selected':'' }}>{{ $item->ActionName }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#action_taken">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Action</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Action taken">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an action…"></div>
                                    <div class="dd-scroll">
                                        @if($ActionStore->isNotEmpty())
                                            @foreach($ActionStore as $item)
                                                <div class="dd-item{{ $Grivance_Parent->action_taken == $item->id ? ' active' : '' }}" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->ActionName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="action_taken" class="form-label">STATUS</label>

                            <select class="form-select dd-native-select" id="Status" name="STATUS" aria-label="Status">
                                <option value="">Select Status</option>
                                <option value="pending" {{$Grivance_Parent->status =="pending"  ?'selected':''}}>Pending</option>
                                <option value="in_review" {{$Grivance_Parent->status =="in_review"  ?'selected':''}}>In Review</option>
                                <option value="resolved" {{$Grivance_Parent->status =="resolved"  ?'selected':''}}>Resolved</option>
                                <option value="rejected" {{$Grivance_Parent->status =="rejected"  ?'selected':''}}>Rejected</option>
                            </select>
                            <div class="dd" data-target="#Status">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item{{ $Grivance_Parent->status == 'pending' ? ' active' : '' }}" role="option" data-value="pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ $Grivance_Parent->status == 'in_review' ? ' active' : '' }}" role="option" data-value="in_review"><span class="dd-nm">In Review</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ $Grivance_Parent->status == 'resolved' ? ' active' : '' }}" role="option" data-value="resolved"><span class="dd-nm">Resolved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ $Grivance_Parent->status == 'rejected' ? ' active' : '' }}" role="option" data-value="rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(!isset($Grivance_Parent->SentToGM ) ||$Grivance_Parent->SentToGM != "Yes" && !isset($Grivance_Parent->Gm_Decision))
                        <div class="col-lg-4 hideApprovalRequest">
                            <label for="" class="form-label">APPROVAL</label>
                            <div class="mt-lg-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="approval_request"
                                        @if($Grivance_Parent->Assigned != "DeliverToHr")
                                            disabled
                                        @endif
                                        id="inlineCheckbox1" >
                                    <label class="form-check-label" for="inlineCheckbox1">
                                        Forward the investigation report to relevant approvers
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endif
                    @if($Grivance_Parent->Assigned == "Yes")
                        <div class="col-lg-4 col-sm-6">
                            <label class="form-label">FILE UPLOAD</label>
                            <div class="uploadFile-block">
                                <div class="uploadFile-btn">
                                    <a href="#" class="btn eb-btn-accent btn-sm">Upload File</a>
                                    <input type="file"
                                    id="uploadFile"
                                    multiple
                                    name="investigation_file[]"
                                    accept=".pdf, image/*"
                                    @if($Grivance_Parent->Assigned=="No") disabled @else data-parsley-max-file-size="5" data-parsley-fileextension="pdf,png,jpg,jpeg,gif,svg,webp,heic,heif" data-parsley-fileextension-message="Only PDF and image files are allowed" @endif>
                                </div>
                                <div class="uploadFile-text">PNG, JPEG, PDF</div>
                                <div class="uploadFile-selected small text-muted mt-1"></div>
                            </div>
                        </div>
                    @endif
                @endif
                    @if($Grivance_Parent->Grivance_Submission_Type == "Yes" && !$canViewIdentity && in_array($auth_id, $GrivanceKeys) && !isset($Grivance_Parent->Gm_Decision))
                        <div class="col-lg-4 col-sm-6 align-self-end">
                            @if($Grivance_Parent->Request_Identity_Disclosure == 'Requested')
                                <span class="text-muted">Identity disclosure requested — awaiting response</span>
                            @else
                                <a href="javascript:void(0)" @if($Grivance_Parent->Assigned=="No") disabled @endif class="btn eb-btn-accent RequestIdentity" data-id="{{ $Grivance_Parent->id}}">Request Identity Disclosure</a>
                            @endif
                        </div>
                    @endif

                </div>
            @endif

            @if($isCommitteeMember)
            <div class="card-footer text-end">
                <button type="submit" class="btn eb-btn-primary btn-sm">Submit</button>
            </div>
            @endif
        </form>
        </div>

        </div>
    </div>

    {{-- Appeals panel — commented out for now, per request. Re-enable by
         removing this @if(false)/@endif wrapper. --}}
    @if(false)
    {{-- Appeals panel: shows existing appeals for this grievance + lets the
         submitter or HR file a new one. Only one *active* appeal can exist
         at a time per grievance (controller enforces this). --}}
    @php
        $existingAppeals = \App\Models\GrievanceAppeal::where('grievance_id', $Grivance_Parent->id)
            ->orderByDesc('id')->get();
        $hasActiveAppeal = $existingAppeals->whereIn('status', ['Pending', 'In_Hearing'])->isNotEmpty();
        // File-appeal is allowed when the parent grievance has been
        // closed/decided OR when there is no active appeal yet. Block
        // duplicates so users can't fire two at once.
        $canFileAppeal = !$hasActiveAppeal;
    @endphp
    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Appeals</h5>
                    @if($canFileAppeal)
                        <button type="button" class="btn eb-btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#fileAppealModal">
                            <i class="fa fa-gavel me-1"></i> File Appeal
                        </button>
                    @else
                        <span class="text-muted small">An active appeal already exists for this grievance.</span>
                    @endif
                </div>

                @forelse($existingAppeals as $ap)
                    @php
                        $cssMap = ['Pending'=>'badge-warning','In_Hearing'=>'badge-info','Resolved'=>'badge-success','Rejected'=>'badge-danger','Withdrawn'=>'badge-secondary'];
                        $css = $cssMap[$ap->status] ?? 'badge-secondary';
                    @endphp
                    <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <strong>{{ $ap->appeal_no }}</strong>
                            <span class="badge {{ $css }} ms-2">{{ str_replace('_',' ', $ap->status) }}</span>
                            @if($ap->decision)
                                <span class="ms-2 text-muted small">Decision: {{ $ap->decision }}</span>
                            @endif
                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($ap->reason, 140) }}</div>
                        </div>
                        <a href="{{ route('GrievanceAndDisciplinery.Appeals.Show', base64_encode($ap->id)) }}" class="btn eb-btn-secondary btn-sm">Open</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">No appeals filed against this grievance.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- File Appeal Modal --}}
@if($canFileAppeal)
<div class="modal fade" id="fileAppealModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="fileAppealForm">
                @csrf
                <input type="hidden" name="grievance_id" value="{{ $Grivance_Parent->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">File Appeal — Grievance {{ $Grivance_Parent->Grivance_id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Filing an appeal opens a new case file under the appeals workflow. HR / GM are notified automatically. You can schedule hearings and record a decision from the appeal detail page.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Appeal <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="5" maxlength="2000" required placeholder="Explain why this grievance decision should be reviewed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-neutral" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn eb-btn-primary">Submit Appeal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif
@include('resorts.GrievanceAndDisciplinery.grivance._investigation_report_styles')
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    .gr-history-table td, .gr-history-table th {
        white-space: normal;
        word-break: break-word;
        vertical-align: top;
    }
</style>
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {
    // File-appeal modal submit. Posts the reason + grievance_id to the
    // appeals.store endpoint and redirects to the new appeal's detail page
    // on success. Idempotent server-side: re-submitting a duplicate gets a
    // 422 which we surface as a toastr.
    $('#fileAppealForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("GrievanceAndDisciplinery.Appeals.Store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function (r) {
            if (r.success) {
                toastr.success(r.message, 'Success', { positionClass: 'toast-bottom-right' });
                if (r.redirect_url) setTimeout(() => window.location.href = r.redirect_url, 600);
            } else {
                toastr.error(r.message || 'Failed to file appeal', 'Error', { positionClass: 'toast-bottom-right' });
            }
        })
        .fail(function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to file appeal', 'Error', { positionClass: 'toast-bottom-right' });
        });
    });

    flatpickr('.datepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    });
    $("#investigationReportSubmit").parsley();

    $(".gr-investigation-editor").each(function() {
        CKEDITOR.replace(this.id);
    });

            $('#investigationReportSubmit').on('submit', function(e)
            {
                e.preventDefault();

                // Sync CKEditor content back into its textarea before
                // Parsley validates and FormData reads the raw DOM value —
                // CKEditor only does this itself on a native form submit,
                // which never fires here since this handler already
                // preventDefault()s and posts via ajax.
                for (var instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }

                let form = $(this);
                if (form.parsley().isValid())
                {
                    let formData = new FormData(this);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.grivance.InvestigationReportStore') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                window.location.href = response.route;
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                    toastr.error(response.message, "Error",
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                            }
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            if (errors.error)
                            {
                                toastr.error(errors.error, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                            else
                            {
                                var errs = '';
                                $.each(errors.errors, function(key, error)
                                {
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, "Validation Error",{
                                   positionClass: 'toast-bottom-right'
                                });
                            }
                        }

                    });
                    return false;
                }
            });



});
$(document).on("change",".follow_up_action_id",function() {

    var action = $(this).val();
    if(action =="GatherWitnessStatements")
    {
        $("#RequestForStatement").removeClass('d-none');
    }
    else
    {
        $("#RequestForStatement").addClass('d-none');
    }
});
$(document).on("click",".RequestForStatement",function() {

    var id = $(this).data('id');
    $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.grivance.RequestForStatement') }}",
                type: 'POST',
                data: {"id":id,"_token":"{{ csrf_token() }}"},
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message,"Success",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                            toastr.error(response.message, "Error",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                },
                error: function(response)
                {
                    var errors = response.responseJSON;
                    if (errors.error)
                    {
                        toastr.error(errors.error, "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                    else
                    {
                        var errs = '';
                        $.each(errors.errors, function(key, error)
                        {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, "Validation Error",{
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }

                });

});


    $(document).on("change","#Employee_id",function() {

        var emp =  $(this).val();

        $.ajax({
        url: "{{ route('GrievanceAndDisciplinery.grivance.GetEmployeeDetails') }}",
        type: "get",
        data: {"_token":"{{ csrf_token() }}","emp":emp},
        success: function(response) {
            console.log(response);
            if (response.success) {

             $("#supervisor").val(response.data.Superviser.Main_Name);
              $("#employee_main_id").val(response.data.Employee.Emp_id);
              $("#Department").val(response.data.Employee.DepartmentName);
              $("#job_title").val(response.data.Employee.PositionName);
            }
            else
            {
                toastr.error(response.message, "Error",
                {
                    positionClass: 'toast-bottom-right'
                });
            }
        },
        error: function(response) {
            var errors = response.responseJSON;
            var errs = '';
            $.each(errors.errors, function(key, error) {
                errs += error + '<br>';
            });
            toastr.error(errs, {
                positionClass: 'toast-bottom-right'
            });
        }
    });
    });
    // Numbers the visible ".ndot" badges 1..N in DOM order and hides the
    // Remove button whenever exactly one entry remains (min 1) — same rule
    // for the static first entry and every JS-added one, since they now
    // share the identical .gvi-entry markup shape.
    function gviRenumberEntries() {
        var $entries = $(".appendHere .gvi-entry");
        $entries.each(function (i) {
            $(this).find(".ndot").first().text(i + 1);
        });
        $entries.find(".gvi-rm").prop("hidden", $entries.length <= 1);
    }

    $(document).on("click",".AddMoreGrivance",function(){
        var counts = parseInt( $("#counts").val());
        counts = counts+1;
        // Collapse existing entries so the newly-added one is the only one
        // open — keeps focus on the row being filled instead of a wall of
        // identical open blocks.
        $(".appendHere .gvi-entry").addClass("collapsed");
        var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
        var string=`
            <div class="gvi-entry Remove_c_${counts}">
                <div class="gvi-entry-h">
                    <span class="enum"><span class="ndot">${counts}</span>Investigation entry</span>
                    <span class="gvi-stagechip">Stage not set</span>
                    <button type="button" class="gvi-rm btn eb-btn-critical delete-row-btn" data-id="${counts}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>Remove</button>
                    <svg class="caret" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </div>
                <div class="gvi-entry-b">
                    <div class="field">
                        <label for="inves_find_${counts}" class="flabel">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                        <textarea class="form-control gr-investigation-editor" @if($Grivance_Parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find_${counts}" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                    </div>
                    <div class="grid2">
                        <div class="field">
                            <label for="followup_actions" class="flabel">FOLLOW-UP ACTIONS</label>
                            <select class="form-select dd-native-select follow_up_action_id" id="follow_up_action_${counts}" name="follow_up_action[]" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select a follow-up action" @endif aria-label="Follow-up actions">
                                <option value=""></option>
                                <option value="InspectSite">Inspect Site</option>
                                <option value="ReviewDocuments">Review Documents</option>
                                <option value="CCTVFootageReview">CCTV Footage Review</option>
                                <option value="CheckAccessLogs">Check Access Logs</option>
                                <option value="GatherPhysicalEvidence">Gather Physical Evidence</option>
                            </select>
                            <div class="dd" data-target="#follow_up_action_${counts}">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Follow-Up Action</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Follow-up action">
                                    <div class="dd-scroll">
                                        <div class="dd-item" role="option" data-value="InspectSite"><span class="dd-nm">Inspect Site</span>${tickSvg}</div>
                                        <div class="dd-item" role="option" data-value="ReviewDocuments"><span class="dd-nm">Review Documents</span>${tickSvg}</div>
                                        <div class="dd-item" role="option" data-value="CCTVFootageReview"><span class="dd-nm">CCTV Footage Review</span>${tickSvg}</div>
                                        <div class="dd-item" role="option" data-value="CheckAccessLogs"><span class="dd-nm">Check Access Logs</span>${tickSvg}</div>
                                        <div class="dd-item" role="option" data-value="GatherPhysicalEvidence"><span class="dd-nm">Gather Physical Evidence</span>${tickSvg}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="follow_up_description" class="flabel">Follow-up detail</label>
                            <input type="text" class="form-control" placeholder="Type Here" name="follow_up_description[]" id="follow_up_description" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                        </div>
                    </div>
                    <div class="field" style="max-width:50%">
                        <label for="inves_stage" class="flabel">INVESTIGATION STAGE</label>
                        <select class="form-select dd-native-select gvi-stage-select" id="investigation_stage_${counts}" name="investigation_stage[]" aria-label="Investigation stage" @if($Grivance_Parent->Assigned=="No") disabled @else required data-parsley-required-message="Please select an investigation stage" @endif>
                            <option value="">Select a stage</option>
                            <option value="InitialReview">Initial Review</option>
                            <option value="Delegated">Delegated</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Compiled">Report Compiled</option>
                        </select>
                        <div class="dd" data-target="#investigation_stage_${counts}">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select a stage</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Investigation stage">
                                <div class="dd-scroll">
                                    <div class="dd-item" role="option" data-value="InitialReview"><span class="dd-nm">Initial Review</span>${tickSvg}</div>
                                    <div class="dd-item" role="option" data-value="Delegated"><span class="dd-nm">Delegated</span>${tickSvg}</div>
                                    <div class="dd-item" role="option" data-value="Ongoing"><span class="dd-nm">Ongoing</span>${tickSvg}</div>
                                    <div class="dd-item" role="option" data-value="Compiled"><span class="dd-nm">Report Compiled</span>${tickSvg}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="resol_notes" class="flabel">RESOLUTION NOTES</label>
                        <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Grivance_Parent->Assigned=="No") readonly @endif placeholder="Type Here..." rows="4"></textarea>
                    </div>
                </div>
            </div>`;

            $(".appendHere").append(string);
            $("#counts").val(counts)
            CKEDITOR.replace('inves_find_'+counts);
            gviRenumberEntries();
    });
    $(document).on("change","#uploadFile",function(){
        var names = Array.from(this.files).map(f => f.name);
        $(".uploadFile-selected").text(names.length ? 'Selected: ' + names.join(', ') : '');
    });

    $(document).on("click",".delete-row-btn",function(e){
        e.stopPropagation(); // don't let the click also toggle the entry open/closed
        if ($(".appendHere .gvi-entry").length <= 1) return; // min 1 entry

        var location = $(this).data("id");
        if (CKEDITOR.instances['inves_find_'+location]) {
            CKEDITOR.instances['inves_find_'+location].destroy(true);
        }
        $(this).closest(".gvi-entry").remove();
        $("#counts").val(parseInt( $("#counts").val())-1);
        gviRenumberEntries();
    });

    // Header click expands/collapses its own entry (Remove already stops
    // its own click above, so this never double-fires on that button).
    $(document).on("click",".gvi-entry-h",function(){
        $(this).closest(".gvi-entry").toggleClass("collapsed");
    });

    // Live stage chip in the entry header — purely a display reflection of
    // the real investigation_stage[] select already driving the actual
    // submission, not a separate field.
    $(document).on("change",".gvi-stage-select",function(){
        var $chip = $(this).closest(".gvi-entry").find(".gvi-stagechip");
        var label = $(this).find("option:selected").text();
        if ($(this).val()) {
            $chip.text(label).addClass("set");
        } else {
            $chip.text("Stage not set").removeClass("set");
        }
    });

    gviRenumberEntries(); // initial state — Remove hidden while only entry 1 exists
    $(document).on("change","#outcome_type",function(){

        if($(this).val() == "Resolved")
        {
            $(".hideApprovalRequest").hide();
        }
        else{
            $(".hideApprovalRequest").show();
        }



    });

    $(document).on("click",".RequestIdentity",function(){
        var $requestBtn = $(this);

        $.ajax({
            url: "{{ route('GrievanceAndDisciplinery.grivance.RequestIdentity') }}",
            type: 'POST',
            data: {"_token":"{{ csrf_token()}}","id":$requestBtn.data("id")},
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message,"Success",
                    {
                        positionClass: 'toast-bottom-right'
                    });

                    // Was $(".align-self-end").hide() — an unscoped class
                    // selector that hid the whole button/status wrapper
                    // instead of showing the pending state, so a successful
                    // request left no visible trace at all once the toast
                    // faded. Replace just the clicked button with the same
                    // "awaiting response" status a page reload would show
                    // (Request_Identity_Disclosure == 'Requested').
                    $requestBtn.replaceWith('<span class="text-muted">Identity disclosure requested — awaiting response</span>');
                }
                else
                {
                        toastr.error(response.message, "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                }
            },
            error: function(response)
            {
                var errors = response.responseJSON;
                if (errors.error)
                {
                    toastr.error(errors.error, "Error",
                    {
                        positionClass: 'toast-bottom-right'
                    });
                }
                else
                {
                    var errs = '';
                    $.each(errors.errors, function(key, error)
                    {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Validation Error",{
                        positionClass: 'toast-bottom-right'
                    });
                }
            }

        });
    });

         window.Parsley.addValidator('greaterThan', {
        validateString: function(value, requirement) {
            // Get the dates
            var startDateStr = $(requirement).val();
            var endDateStr = value;

            if (!startDateStr || !endDateStr) return true;

            // Parse dates (assuming format dd-mm-yyyy)
            var startParts = startDateStr.split('-');
            var endParts = endDateStr.split('-');

            if (startParts.length !== 3 || endParts.length !== 3) return true;

            var startDate = new Date(startParts[2], startParts[1] - 1, startParts[0]);
            var endDate = new Date(endParts[2], endParts[1] - 1, endParts[0]);

            // Return true if end date is greater than start date
            return endDate > startDate;
        },
        priority: 33
    });

    // Custom validator for file size
    window.Parsley.addValidator('maxFileSize', {
        validateString: function(_value, maxSize, parsleyInstance) {
            var files = parsleyInstance.$element[0].files;
            if (files.length === 0) {
                return true;
            }

            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxSize * 1024 * 1024) {
                    return false;
                }
            }
            return true;
        },
        requirementType: 'integer',
        messages: {
            en: 'File size must not exceed %sMB'
        }
    });

</script>
@endsection
