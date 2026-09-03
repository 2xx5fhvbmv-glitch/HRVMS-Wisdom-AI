@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #grievance-investigation-info-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #grievance-investigation-info-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="grievance-investigation-info-hero">
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
            $gviPriority = $Grivance_Parent->Priority ?? 'Low';
            $gviPriorityColor = $gviPriority == 'High' ? 'var(--error)' : ($gviPriority == 'Medium' ? 'var(--warning)' : 'var(--positive)');

            $gviConfType = $Grivance_Parent->Grivance_Submission_Type ?? 'NotApplicable';
            $gviIsConfidential = $gviConfType == 'Yes';

            // Unlike the interactive Investigation Report page, this
            // read-only "View Report" screen has never gated Attachments
            // behind $canViewIdentity — it's always shown here, existing
            // behaviour preserved exactly, not something introduced by
            // this redesign.
            $gviAttachments = \App\Helpers\Common::resolveGrievanceAttachments($Grivance_Parent->Attachements, $path."/".$Grivance_Parent->Grivance_id, $Grivance_Parent->resort_id);

            $gviInitials = strtoupper(substr($Grivance_Parent->first_name ?? '', 0, 1) . substr($Grivance_Parent->last_name ?? '', 0, 1)) ?: '?';

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

            $gviHistory = collect($GrivanceSubmissionHistory ?? [])->reverse()->values();
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
        @if(!empty($GrivanceSubmissionHistory) && count($GrivanceSubmissionHistory))
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
                                        {{-- Fixed: this page previously escaped these two fields
                                             ({{ }}) even though both can hold CKEditor-authored
                                             HTML (confirmed on the sibling Investigation Report
                                             page's identical data) — showing literal <p>/<ul> tags
                                             instead of the formatted text. Rendered raw here, same
                                             as the sibling page already does correctly. --}}
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

        {{-- ===== outcome / status ===== --}}
        <div class="gvi-card gvi-foot">
            <div class="fleft">
                <div class="fitem"><span class="fk">Outcome type</span>
                    @if(!empty($Grivance_Parent->outcome_type))
                        <span class="fv">{{ ucfirst($Grivance_Parent->outcome_type) }}</span>
                    @else
                        <span class="fv none">Not set</span>
                    @endif
                </div>
                <div class="fitem"><span class="fk">Status</span>
                    @if(!empty($Grivance_Parent->status))
                        <span class="fv">{{ $gviStatusLabels[$Grivance_Parent->status] ?? ucfirst($Grivance_Parent->status) }}</span>
                    @else
                        <span class="fv none">Not set</span>
                    @endif
                </div>
            </div>
            @if(!empty($Grivance_Parent->status))
                <span class="gvi-status-pill {{ $Grivance_Parent->status }}"><span class="gvi-dot" style="background:currentColor"></span>{{ $gviStatusLabels[$Grivance_Parent->status] ?? ucfirst($Grivance_Parent->status) }}</span>
            @endif
        </div>

        @if($rankKey == 'GM')
            <div class="gvi-card" style="padding:24px 26px; margin-top:16px;">
            <form id="investigationReportSubmit" data-parsley-validate>
                @csrf
                <input type="hidden" name="Grievant_form_id" value="{{ $Grivance_Parent->id}}">
                <input type="hidden" value="GmApproval" name="flag">

                <div class="row">
                    <div class="col-md-6">
                        <label for="Gm_Decision" class="form-label mt-3">Reason</label>
                        <textarea class="form-control" name="Gm_Resoan"> </textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="Gm_Decision" class="form-label mt-3">GM Decision</label>
                        <select class="form-select dd-native-select Gm_Decision" name="Gm_Decision" id="Gm_Decision" required data-parsley-required-message="Please select a decision">
                            <option value="">Select Decision</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                        <div class="dd" data-target="#Gm_Decision">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Decision</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="GM decision">
                                <div class="dd-scroll">
                                    <div class="dd-item" role="option" data-value="Approved"><span class="dd-nm">Approved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="Rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 rejection-container Rejection_reason" style="display: none;">
                    <div class="col-md-12">
                        <label for="Rejection_reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control rejection-field" id="Rejection_reason" name="Rejection_reason"
                            placeholder="Add detailed notes, observations, or findings as the investigation progresses" rows="4"
                            data-parsley-required="false"
                            data-parsley-required-message="Investigation findings are required"
                            data-parsley-minlength="20"
                            data-parsley-minlength-message="Please provide at least 20 characters of detailed findings"></textarea>
                    </div>
                </div>

                <div class="card-footer mt-3 text-end">
                    <button type="submit" class="btn eb-btn-primary btn-sm">Submit</button>
                </div>
            </form>
            </div>
        @endif

        </div>
    </div>
</div>
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
    $("#investigationReportSubmit").parsley();

    flatpickr('.approvedrejacted', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    });

});


    $(document).on("change",".Gm_Decision",function()
    {
        if($(this).val() != "Rejected")
        {
            $(".Rejection_reason").hide();
        }
        else{
            $(".Rejection_reason").show();
        }

    });
    $('#investigationReportSubmit').on('submit', function(e)
            {
                e.preventDefault();

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

</script>
@endsection
