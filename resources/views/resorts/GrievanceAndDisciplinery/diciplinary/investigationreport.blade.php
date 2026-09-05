@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #disciplinary-investigation-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #disciplinary-investigation-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid dvi-wrap">
        <div class="page-hedding" id="disciplinary-investigation-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span> Grievance &amp; Disciplinary</span>
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

        @php
            // Priority dot-pill color — Low=positive, Medium=warning, High=error.
            $dviPriority = $Disciplinary_parent->Priority ?? 'Low';
            $dviPriorityColor = $dviPriority == 'High' ? 'var(--error)' : ($dviPriority == 'Medium' ? 'var(--warning)' : 'var(--positive)');

            // Initials fallback for the employee avatar — the real photo
            // already comes with a built-in default-picture fallback via
            // Common::getResortUserPicture(), this is just an extra safety
            // net if that URL itself ever fails to load.
            $dviInitials = strtoupper(substr($Disciplinary_parent->first_name ?? '', 0, 1) . substr($Disciplinary_parent->last_name ?? '', 0, 1)) ?: '?';

            // Attachments — build a clean, non-mutating file list (the old
            // code mutated $Path itself inside the loop, corrupting every
            // file link rendered afterwards on this page, including the
            // Signed Document link and the History table's file links below).
            $dviAttachments = [];
            if (!empty($Disciplinary_parent->Attachements)) {
                foreach (explode(",", $Disciplinary_parent->Attachements) as $dviFile) {
                    $dviFile = trim($dviFile);
                    if ($dviFile !== '') {
                        $dviAttachments[] = ['name' => $dviFile, 'url' => URL::asset($Path . '/' . $dviFile)];
                    }
                }
            }
            $dviSignedDoc = !empty($Disciplinary_parent->upload_signed_document)
                ? ['name' => $Disciplinary_parent->upload_signed_document, 'url' => URL::asset($Path . '/' . $Disciplinary_parent->upload_signed_document)]
                : null;
        @endphp

        {{-- ===== hero ===== --}}
        <div class="row g-3 g-xxl-4 dvi-hero">
            <div class="col-lg-8">
                <div class="dvi-card dvi-summary">
                    <div class="dvi-title" role="heading" aria-level="2">{{ $Disciplinary_parent->OffensesName ?? '—' }}</div>
                    <div class="dvi-class">
                        <span class="cat">{{ $Disciplinary_parent->CatName }}</span>
                        <span class="chev">&rsaquo;</span>
                        <span class="sub">{{ $Disciplinary_parent->OffensesName ?? '—' }}</span>
                    </div>
                    <div class="dvi-desc">
                        <div class="dk">Description</div>
                        <div class="dv">{!! $Disciplinary_parent->Incident_description !!}</div>
                    </div>
                    <div class="dvi-facts">
                        <div class="dvi-fact"><span class="k">Disciplinary ID</span><span class="pill ref-pill">{{ $Disciplinary_parent->Disciplinary_id }}</span></div>
                        <div class="dvi-fact"><span class="k">Priority</span><span class="pill"><span class="dvi-dot" style="background:{{ $dviPriorityColor }}"></span>{{ $dviPriority }}</span></div>
                        <div class="dvi-fact"><span class="k">Committee</span><span class="pill"><span class="dvi-dot" style="background:var(--faint)"></span>{{ $Disciplinary_parent->CommitteeName ?: 'Unassigned' }}</span></div>
                        <div class="dvi-fact"><span class="k">Created</span><span class="fv">{{ $Disciplinary_parent->created_at ? \Carbon\Carbon::flexible($Disciplinary_parent->created_at)->format('d M Y \\· h:i A') : '—' }}</span></div>
                        <div class="dvi-fact">
                            <span class="k">Action valid until</span>
                            @php $dviExp = $Disciplinary_parent->Expiry_date ?? null; @endphp
                            <span class="fv">{{ (empty($dviExp) || $dviExp === '0000-00-00') ? '—' : \Carbon\Carbon::parse($dviExp)->format('d M Y') }}</span>
                        </div>
                        <div class="dvi-fact">
                            <span class="k">Attachments</span>
                            @if(count($dviAttachments))
                                <a class="filefact" href="{{ $dviAttachments[0]['url'] }}" target="_blank" title="{{ $dviAttachments[0]['name'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a5 5 0 01-7.07-7.07l9.19-9.19a3 3 0 014.24 4.24l-9.2 9.19a1 1 0 01-1.41-1.41l8.49-8.49"/></svg>
                                    <span class="fn">{{ $dviAttachments[0]['name'] }}</span>
                                </a>
                                @if(count($dviAttachments) > 1)
                                    <span class="filemore" title="{{ collect($dviAttachments)->pluck('name')->slice(1)->implode(', ') }}">+{{ count($dviAttachments) - 1 }}</span>
                                @endif
                            @else
                                <span class="filenone">None</span>
                            @endif
                        </div>
                        <div class="dvi-fact">
                            <span class="k">Signed document</span>
                            @if($dviSignedDoc)
                                <a class="filefact" href="{{ $dviSignedDoc['url'] }}" target="_blank" title="{{ $dviSignedDoc['name'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                                    <span class="fn">{{ $dviSignedDoc['name'] }}</span>
                                </a>
                            @else
                                <span class="filenone">None</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dvi-card dvi-rail">
                    <div class="rh">Employee</div>
                    <div class="who">
                        <img class="dvi-av" src="{{ Common::getResortUserPicture($Disciplinary_parent->GetEmployee->resortAdmin->id) }}" alt=""
                            onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'dvi-av',textContent:'{{ $dviInitials }}'}))">
                        <div class="nm">{{ $Disciplinary_parent->first_name }} {{ $Disciplinary_parent->last_name }}</div>
                    </div>
                    <div class="klist">
                        <div class="krow"><span class="kk">Employee ID</span><span class="kv">{{ $Disciplinary_parent->employee_code ?? '-' }}</span></div>
                        <div class="krow"><span class="kk">Department</span><span class="kv">{{ $Disciplinary_parent->DepartmentName }}</span></div>
                        <div class="krow"><span class="kk">Position</span><span class="kv">{{ $Disciplinary_parent->PositiontName }}</span></div>
                        <div class="krow">
                            <span class="kk">Email</span>
                            @if(!empty($Disciplinary_parent->employee_email))
                                <a class="kv link" href="mailto:{{ $Disciplinary_parent->employee_email }}">{{ $Disciplinary_parent->employee_email }}</a>
                            @else
                                <span class="kv">-</span>
                            @endif
                        </div>
                        <div class="krow"><span class="kk">Contact</span><span class="kv">{{ $Disciplinary_parent->personal_phone ?: '-' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== history ===== --}}
        @if(isset($parent) && $parent->isNotEmpty())
            <div class="dvi-card dvi-hist">
                <div class="dvi-sec-h">History</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Investigation Start Date &ndash; Anticipated Resolution Date</th>
                                <th>Committee Member</th>
                                <th>Outcome</th>
                                <th>Attachments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parent as $key => $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->invesigation_date ? \Carbon\Carbon::flexible($value->invesigation_date)->format('d M Y') : '—' }} &ndash; {{ $value->resolution_date ? \Carbon\Carbon::flexible($value->resolution_date)->format('d M Y') : '—' }}</td>
                                    <td>{{ $value->first_name }} {{ $value->last_name }} ({{ $value->rank }})</td>
                                    <td>{{ $value->outcome_type }}</td>
                                    <td>
                                        @php
                                            $dviHistFiles = !empty($value->investigation_file) ? array_filter(array_map('trim', explode(",", $value->investigation_file))) : [];
                                        @endphp
                                        @if(count($dviHistFiles))
                                            @foreach($dviHistFiles as $dviHf)
                                                <a class="filefact" href="{{ URL::asset($Path . '/' . $dviHf) }}" target="_blank" title="{{ $dviHf }}"><span class="fn">{{ $dviHf }}</span></a><br>
                                            @endforeach
                                        @else
                                            <span class="filenone">None</span>
                                        @endif
                                    </td>
                                </tr>
                                @php $childData = $child->where('Disciplinary_P_id', $value->id); @endphp
                                @if($childData->isNotEmpty())
                                    <tr>
                                        <td colspan="5">
                                            <table class="dvi-sub">
                                                <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Investigation Findings and Recommendations</th>
                                                        <th>Follow Up Action</th>
                                                        <th>Follow Up Description</th>
                                                        <th>Investigation Stage</th>
                                                        <th>Resolution Note</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($childData as $c)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $c->inves_find_recommendations }}</td>
                                                            <td>{{ $c->follow_up_action }}</td>
                                                            <td>{{ $c->follow_up_description }}</td>
                                                            <td>{{ $c->investigation_stage }}</td>
                                                            <td>{{ $c->resolution_note }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ===== investigation form — two equal-height cards ===== --}}
        @if($Disciplinary_parent->Assigned !="DeliverToHr" ||  $Disciplinary_parent->status !="resolved")
        <form id="investigationReportSubmit" data-parsley-validate>
            @csrf
            <input type="hidden" name="Disciplinary_form_id" value="{{ $Disciplinary_parent->Disciplinary_id}}">
            <input type="hidden" name="committee_member_id" value="{{$committee_member_id}}">
            <input type="hidden" name="counts" value="1" id="counts">

            <div class="row g-3 g-xxl-4 dvi-form-grid">
                {{-- Investigation details --}}
                <div class="col-lg-6">
                    <div class="dvi-card">
                        <div class="dvi-pad">
                            <div class="dvi-sec-h">Investigation details</div>
                            <div class="dvi-fld">
                                <label for="inves_date">Investigation timeline</label>
                                <div class="dvi-frow">
                                    <input type="text" value="{{ isset($GrivanceInvestigationModel->inves_start_date) ? $GrivanceInvestigationModel->inves_start_date :'' }}"
                                        @if($Disciplinary_parent->Assigned=="No" || isset($GrivanceInvestigationModel->inves_start_date )) readonly @else required data-parsley-required-message="Investigation start date is required" @endif
                                        class="form-control datepicker" id="inves_date" name="invesigation_date" placeholder="Start date">
                                    <input type="text" value="{{ isset($GrivanceInvestigationModel->resolution_date) ? $GrivanceInvestigationModel->resolution_date : '' }}"
                                        @if($Disciplinary_parent->Assigned=="No" || isset($GrivanceInvestigationModel->resolution_date)) readonly @else required data-parsley-required-message="Anticipated resolution date is required" data-parsley-greaterThan="#inves_date" data-parsley-greaterThan-message="Resolution date must be after the investigation start date" @endif
                                        class="form-control datepicker" id="exp_date" name="resolution_date" placeholder="Anticipated resolution date">
                                    {{-- "Add more" — commented out per request: each committee only
                                         files one investigation round (timeline + stage + findings),
                                         never several, so the multi-round add/remove flow isn't
                                         needed right now. Uncomment if that changes; the JS handler
                                         below (.AddMoreGrivance) and the delete-row flow are left
                                         intact, just unreachable without this trigger. --}}
                                    {{-- <a href="javascript:void(0)" class="btn eb-btn-accent AddMoreGrivance">Add more</a> --}}
                                </div>
                            </div>

                            <div class="dvi-append-left">
                                <div class="dvi-fld">
                                    <label for="investigation_stage">Investigation stage</label>
                                    @php $stageDisabledD = $Disciplinary_parent->Assigned=="No"; @endphp
                                    <select class="form-select dd-native-select" id="investigation_stage" name="investigation_stage[]" aria-label="Investigation stage" @if($stageDisabledD) disabled @else required data-parsley-required-message="Please select an investigation stage" data-parsley-errors-container="#investigation_stage_error" @endif>
                                        <option value="" disabled selected>Select a stage</option>
                                        <option value="InitialReview">Initial Review</option>
                                        <option value="Delegated">Delegated</option>
                                        <option value="Ongoing">Ongoing</option>
                                        <option value="Compiled">Report Compiled</option>
                                    </select>
                                    <div class="dd" data-target="#investigation_stage">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" @if($stageDisabledD) disabled aria-disabled="true" @endif>
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
                                    <div id="investigation_stage_error"></div>
                                </div>
                                <div class="dvi-fld dvi-grow">
                                    <label for="inves_find">Findings &amp; recommendations</label>
                                    <textarea class="form-control" @if($Disciplinary_parent->Assigned=="No") readonly @else required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" @endif id="inves_find" name="inves_find_recommendations[]" placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resolution & outcome --}}
                <div class="col-lg-6">
                    <div class="dvi-card">
                        <div class="dvi-pad">
                            <div class="dvi-sec-h">Resolution &amp; outcome</div>

                            <div class="dvi-append-right">
                                <div class="dvi-fld">
                                    <label for="follow_up_action">Follow-up action</label>
                                    <div class="dvi-frow dvi-two">
                                        @php
                                            // "Gather Witness Statements" is hidden when a witness statement
                                            // request is already in progress, matching prior hardcoded behaviour.
                                            $hideGatherWitness = ($Disciplinary_parent->Request_For_Statement == "Yes");
                                            $followUpDisabledD = $Disciplinary_parent->Assigned=="No";
                                        @endphp
                                        <select class="form-select dd-native-select follow_up_action_id" id="follow_up_action" name="follow_up_action[]" @if($followUpDisabledD) disabled @else required data-parsley-required-message="Please select a follow-up action" data-parsley-errors-container="#follow_up_action_error" @endif aria-label="Follow-up action">
                                            <option value="">Select follow-up action</option>
                                            @foreach($FollowUpActions ?? [] as $fu)
                                                @if($hideGatherWitness && stripos($fu->name, 'Gather Witness') !== false)
                                                    @continue
                                                @endif
                                                <option value="{{ $fu->name }}">{{ $fu->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="dd" data-target="#follow_up_action">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" @if($followUpDisabledD) disabled aria-disabled="true" @endif>
                                                <span class="dd-lbl">Select follow-up action</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Follow-up action">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a follow-up action…"></div>
                                                <div class="dd-scroll">
                                                    @foreach($FollowUpActions ?? [] as $fu)
                                                        @if($hideGatherWitness && stripos($fu->name, 'Gather Witness') !== false)
                                                            @continue
                                                        @endif
                                                        <div class="dd-item" role="option" data-value="{{ $fu->name }}"><span class="dd-nm">{{ $fu->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" placeholder="Add detail" name="follow_up_description[]" id="follow_up_description" @if($followUpDisabledD) disabled @else required data-parsley-required-message="Additional follow-up information is required" @endif>
                                    </div>
                                    <div id="follow_up_action_error"></div>
                                    <div id="RequestForStatement" class="d-none mt-1">
                                        <a href="javascript:void(0)" class="btn eb-btn-accent RequestForStatement" data-id="{{$Disciplinary_parent->Disciplinary_id}}">Request For Statement</a>
                                    </div>
                                </div>
                                <div class="dvi-fld">
                                    <label for="resol_notes">Resolution notes</label>
                                    <textarea class="form-control" id="resol_notes" name="resolution_note[]" @if($Disciplinary_parent->Assigned=="No") readonly @endif placeholder="Type here…" rows="4"></textarea>
                                </div>
                            </div>

                            <div class="dvi-fld">
                                <label for="outcome_type">Outcome type</label>
                                @php
                                    $outcomeOptionsD = $Disciplinary_parent->SendtoHr != "Yes"
                                        ? [['DeliverToHr', 'Resolved — Send To HR'], ['Unresolved', 'Unresolved']]
                                        : [
                                            ['Unresolved', 'Unresolved'], ['Dismissed', 'Dismissed'], ['OnHold', 'On Hold'],
                                            ['WithdrawalbyComplainant', 'Withdrawal by Complainant'],
                                            ['EscalatedforFurtherInvestigation', 'Escalated for Further Investigation'],
                                            ['PolicyImprovementSuggested', 'Policy Improvement Suggested'],
                                        ];
                                @endphp
                                <select class="form-select dd-native-select" id="outcome_type" name="outcome_type" aria-label="Outcome type">
                                    <option value="">Select outcome</option>
                                    @foreach($outcomeOptionsD as [$val, $label])
                                        <option value="{{ $val }}" {{ $Disciplinary_parent->outcome_type == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="dd" data-target="#outcome_type">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select outcome</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Outcome type">
                                        <div class="dd-scroll">
                                            @foreach($outcomeOptionsD as [$val, $label])
                                                <div class="dd-item{{ $Disciplinary_parent->outcome_type == $val ? ' active' : '' }}" role="option" data-value="{{ $val }}"><span class="dd-nm">{{ $label }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($Disciplinary_parent->SendtoHr == "Yes")
                                <div class="dvi-fld">
                                    <label for="Status">Status</label>
                                    <select class="form-select dd-native-select" id="Status" name="STATUS" aria-label="Status">
                                        <option value="">Select status</option>
                                        <option value="pending" {{$Disciplinary_parent->status =="pending"  ?'selected':''}}>Pending</option>
                                        <option value="in_review" {{$Disciplinary_parent->status =="in_review"  ?'selected':''}}>In Review</option>
                                        <option value="resolved" {{$Disciplinary_parent->status =="resolved"  ?'selected':''}}>Resolved</option>
                                        <option value="rejected" {{$Disciplinary_parent->status =="rejected"  ?'selected':''}}>Rejected</option>
                                    </select>
                                    <div class="dd" data-target="#Status">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select status</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Status">
                                            <div class="dd-scroll">
                                                <div class="dd-item{{ $Disciplinary_parent->status == 'pending' ? ' active' : '' }}" role="option" data-value="pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item{{ $Disciplinary_parent->status == 'in_review' ? ' active' : '' }}" role="option" data-value="in_review"><span class="dd-nm">In Review</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item{{ $Disciplinary_parent->status == 'resolved' ? ' active' : '' }}" role="option" data-value="resolved"><span class="dd-nm">Resolved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item{{ $Disciplinary_parent->status == 'rejected' ? ' active' : '' }}" role="option" data-value="rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="dvi-fld">
                                <label>File upload</label>
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn">
                                        <a href="#" class="btn eb-btn-accent btn-sm">Upload file</a>
                                        <input
                                            type="file"
                                            id="uploadFile"
                                            multiple
                                            name="investigation_file[]"
                                            accept=".pdf, image/*"
                                            @if($Disciplinary_parent->Assigned=="No")
                                                disabled
                                            @else
                                                data-parsley-max-file-size="5"
                                                data-parsley-fileextension="pdf,png,jpg,jpeg,gif,svg,webp,heic,heif"
                                                data-parsley-fileextension-message="Only PDF and image files are allowed"
                                            @endif
                                        >
                                    </div>
                                    <div class="uploadFile-text dvi-hint">PNG, JPEG, PDF</div>
                                </div>
                                <ul id="selectedFileNames" class="mt-1 list-unstyled text-primary small mb-0"></ul>
                            </div>

                            <div class="dvi-mfoot"><button type="submit" class="btn eb-btn-primary btn-sm">Submit</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@include('resorts.GrievanceAndDisciplinery.diciplinary._investigation_styles')
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
// Real follow-up-action options (same source as the first/static round's
// dropdown) — used to build a correct, in-sync dropdown for every row the
// "Add more" button appends, instead of a stale hardcoded option list.
const dviFollowUpActions = @json(($FollowUpActions ?? collect())->pluck('name'));
const dviHideGatherWitness = {{ ($Disciplinary_parent->Request_For_Statement == "Yes") ? 'true' : 'false' }};

$(document).ready(function() {

    document.getElementById('uploadFile').addEventListener('change', function () {
    const fileList = this.files;
    const output = document.getElementById('selectedFileNames');
    output.innerHTML = ''; // Clear previous list

    if (fileList.length === 0) {
        output.innerHTML = '<li>No file selected</li>';
        return;
    }

    for (let i = 0; i < fileList.length; i++) {
        const li = document.createElement('li');
        li.textContent = fileList[i].name;
        output.appendChild(li);
    }
});
    flatpickr('.datepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    });
    $("#investigationReportSubmit").parsley();

            $('#investigationReportSubmit').on('submit', function(e)
            {
                e.preventDefault();

                let form = $(this);
                if (form.parsley().isValid())
                {
                    let formData = new FormData(this);
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.Disciplinary.InvestigationReportStore') }}",
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
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.RequestForStatement') }}",
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

    $(document).on("click", ".AddMoreGrivance", function () {
            var counts = parseInt($("#counts").val());
            counts += 1;

            // Build the real follow-up-action option list from the same
            // data source the first row uses, instead of a stale hardcoded
            // set — keeps every appended row's dropdown in sync with it.
            var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            var fuOptions = '<option value="">Select follow-up action</option>';
            var fuItems = '';
            dviFollowUpActions.forEach(function (name) {
                if (dviHideGatherWitness && name.toLowerCase().indexOf('gather witness') !== -1) return;
                fuOptions += '<option value="' + name + '">' + name + '</option>';
                fuItems += '<div class="dd-item" role="option" data-value="' + name + '"><span class="dd-nm">' + name + '</span>' + tickSvg + '</div>';
            });

            var stringLeft = `
                <div class="dvi-fld Remove_c_${counts}">
                    <label class="form-label">Investigation stage</label>
                    <select class="form-select dd-native-select" name="investigation_stage[]" id="investigation_stage_${counts}" required data-parsley-required-message="Please select an investigation stage" data-parsley-errors-container="#investigation_stage_error_${counts}">
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
                    <div id="investigation_stage_error_${counts}"></div>
                </div>
                <div class="dvi-fld dvi-grow Remove_c_${counts}">
                    <label class="form-label">Findings &amp; recommendations</label>
                    <textarea class="form-control" name="inves_find_recommendations[]" required data-parsley-required-message="Investigation findings are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters of detailed findings" rows="4" placeholder="Add detailed notes..."></textarea>
                </div>`;

            var stringRight = `
                <div class="dvi-fld Remove_c_${counts}">
                    <label class="form-label">Follow-up action</label>
                    <div class="dvi-frow dvi-two">
                        <select class="form-select dd-native-select" name="follow_up_action[]" id="follow_up_action_${counts}" required data-parsley-required-message="Please select a follow-up action" data-parsley-errors-container="#follow_up_action_error_${counts}">
                            ${fuOptions}
                        </select>
                        <div class="dd" data-target="#follow_up_action_${counts}">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select follow-up action</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Follow-up action">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a follow-up action…"></div>
                                <div class="dd-scroll">${fuItems}</div>
                            </div>
                        </div>
                        <input type="text" class="form-control" name="follow_up_description[]" placeholder="Add detail" required data-parsley-required-message="Follow-up description is required">
                    </div>
                    <div id="follow_up_action_error_${counts}"></div>
                </div>
                <div class="dvi-fld Remove_c_${counts}">
                    <label class="form-label">Resolution notes</label>
                    <textarea class="form-control" name="resolution_note[]" required data-parsley-required-message="Resolution notes are required" data-parsley-minlength="20" data-parsley-minlength-message="Please provide at least 20 characters" rows="4" placeholder="Type here…"></textarea>
                </div>
                <div class="Remove_c_${counts} text-end">
                    <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-id="${counts}">
                        <i class="fa-regular fa-trash-can"></i>
                    </a>
                </div>`;

            $(".dvi-append-left").append(stringLeft);
            $(".dvi-append-right").append(stringRight);
            $("#counts").val(counts);

            // Rebind Parsley on the real form id (was targeting a
            // nonexistent #yourFormId, silently no-oping — newly added
            // rows never actually got Parsley validation re-attached).
            $('#investigationReportSubmit').parsley().destroy();
            $('#investigationReportSubmit').parsley();
        });

    $(document).on("click",".delete-row-btn",function(){

        var location = $(this).data("id");
        $(".Remove_c_"+location).remove();
        $("#counts").val(parseInt( $("#counts").val())-1);
    });
    $(document).on("change","#outcome_type",function(){

        if($(this).val() == "Resolved")
        {
            $(".hideApprovalRequest").hide();
        }
        else
        {
            $(".hideApprovalRequest").show();
        }
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
