@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    @php
        // Presentational-only helpers for this view. No query/data changes —
        // these only decide how the already-fetched models are displayed.
        // Same pattern already used in resorts/search/index.blade.php.
        $ivInitials = function ($name) {
            $parts = preg_split('/\s+/', trim((string) $name));
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            return $initials !== '' ? $initials : '?';
        };
        // Deterministic avatar tint so initials-only avatars aren't all one flat color.
        $ivPalette = ['#0E8A9E', '#6B4FA0', 'var(--positive)', 'var(--warning)', '#4A5F8A', '#A0527A'];
        $ivAvatarColor = function ($name) use ($ivPalette) {
            $hash = 0;
            foreach (str_split((string) $name) as $ch) {
                $hash = (ord($ch) + (($hash << 5) - $hash)) % 1000000007;
            }
            return $ivPalette[abs($hash) % count($ivPalette)];
        };
        // Facts-strip / classification pills — cosmetic color mapping only,
        // falls back to neutral for any value not in the map (e.g. a
        // resort-configured status/severity/priority not listed here).
        $ivPillClass = function ($value) {
            $map = [
                'Low' => 'neu', 'Medium' => 'warn', 'High' => 'error',
                'Minor' => 'ok', 'Moderate' => 'warn', 'Severe' => 'error',
                'Open' => 'neu', 'Under review' => 'warn', 'Under investigation' => 'warn',
                'Resolved' => 'ok', 'Closed' => 'ok',
            ];
            return $map[$value] ?? 'neu';
        };
        // Per-committee-member report status. The incidents_investigation
        // table has no explicit status column for an individual member's
        // entry, so this is derived from how far that entry's own fields
        // have been filled in — not a new backend field.
        $ivReportStatus = function ($inv) {
            if (!empty($inv->resolution_notes)) return ['done', 'Completed'];
            if (!empty($inv->investigation_findings)) return ['prog', 'In progress'];
            return ['review', 'Under review'];
        };
        $ivAttachments = [];
        if (!empty($incident->attachements)) {
            $decoded = json_decode($incident->attachements, true);
            if (is_array($decoded)) {
                foreach ($decoded as $a) {
                    if (isset($a['Filename']) && isset($a['Child_id'])) {
                        $ivAttachments[] = $a;
                    }
                }
            }
        }
    @endphp
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="iv-wrap">

                {{-- Case summary — main card + a separate "Reported by" side
                     panel, equal height via CSS grid. --}}
                <div class="iv-summary">
                    <div class="iv-sum-main">
                        <h3>{{ $incident->incident_name }}</h3>
                        <div class="iv-classify">
                            <span class="catx">{{ $incident->categoryName->category_name ?? 'Uncategorized' }}</span>
                            @if(!empty($incident->subcategoryName->subcategory_name))
                                <span class="sep">&rsaquo;</span>
                                <span class="subx">{{ $incident->subcategoryName->subcategory_name }}</span>
                            @endif
                        </div>
                        <div class="iv-account">
                            <span class="iv-mlabel">Reported account</span>
                            <div class="iv-quote">{{ $incident->description }}</div>
                        </div>
                        <div class="iv-strip">
                            <div class="iv-fct"><span class="iv-mlabel">Incident ID</span><span class="iv-ref">#{{ $incident->incident_id }}</span></div>
                            <div class="iv-fct"><span class="iv-mlabel">Status</span><span class="pill {{ $ivPillClass($incident->status) }}"><span class="d"></span>{{ $incident->status }}</span></div>
                            <div class="iv-fct"><span class="iv-mlabel">Severity</span><span class="pill {{ $ivPillClass($incident->severity) }}"><span class="d"></span>{{ $incident->severity ?: '-' }}</span></div>
                            <div class="iv-fct"><span class="iv-mlabel">Priority</span><span class="pill {{ $ivPillClass($incident->priority) }}"><span class="d"></span>{{ $incident->priority ?: '-' }}</span></div>
                            <div class="iv-fct">
                                <span class="iv-mlabel">Evidence</span>
                                @if(count($ivAttachments))
                                    <div class="iv-filechip">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12l-9 9a5 5 0 01-7-7l9-9a3.5 3.5 0 015 5l-9 9a2 2 0 01-3-3l8-8"/></svg>
                                        <a href="javascript:void(0)" class="download-link fn" data-id="{{ base64_encode($ivAttachments[0]['Child_id']) }}" title="{{ $ivAttachments[0]['Filename'] }}">{{ $ivAttachments[0]['Filename'] }}</a>
                                        @if(count($ivAttachments) > 1)
                                            <span class="more" title="{{ collect($ivAttachments)->pluck('Filename')->implode(', ') }}">+{{ count($ivAttachments) - 1 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="iv-att-none">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12l-9 9a5 5 0 01-7-7l9-9a3.5 3.5 0 015 5l-9 9a2 2 0 01-3-3l8-8"/></svg>None
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="iv-sum-side">
                        <div class="iv-ehead">Reported by</div>
                        <div class="iv-ebody">
                            <div class="iv-top">
                                <span class="bigav" style="background:{{ $ivAvatarColor($incident->reporter->resortAdmin->full_name ?? '') }};color:#fff">
                                    @if(!empty($incident->reporter->resortAdmin->profile_picture ?? null))
                                        <img src="{{ Common::getResortUserPicture($incident->reporter->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                    @endif
                                    {{ $ivInitials($incident->reporter->resortAdmin->full_name ?? '') }}
                                </span>
                                <div class="nm">{{ $incident->reporter->resortAdmin->full_name ?? '-' }}</div>
                            </div>
                            <div class="iv-rows">
                                <div class="r"><span class="l">Employee ID</span><span class="v">{{ $incident->reporter->Emp_id ?? '-' }}</span></div>
                                <div class="r"><span class="l">Department</span><span class="v">{{ $incident->reporter->department->name ?? 'N/A' }}</span></div>
                                <div class="r"><span class="l">Section</span><span class="v">{{ $incident->reporter->section->name ?? 'N/A' }}</span></div>
                                <div class="r"><span class="l">Reported</span><span class="v">{{ \Carbon\Carbon::parse($incident->getRawOriginal('created_at'))->format('d M Y') }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="incidentInvestigation">
                <input type="hidden" name="incident_id" id="incident_id" value="{{$incident->id}}"/>
                <input type="hidden" id="original_priority" value="{{ $incident->priority }}"/>

                {{-- Priority & severity + Authority notified — equal-height pair. --}}
                <div class="iv-pair iv-pair-eq">
                <section class="iv-sec">
                    <div class="iv-sec-h"><h2>Priority &amp; severity</h2></div>
                    <div class="iv-card">
                        <div class="iv-grid2">
                            <div>
                                <label for="priority_level" class="iv-fl">Priority level</label>
                                <div class="iv-selwrap">
                                    <select class="form-select select2t-none" id="priority_level" name="priority" aria-label="Priority level" {{ isset($investigations[0]) ? 'disabled' : '' }}>
                                        <option value="">Select Priority</option>
                                        <option value="Low" {{ $incident->priority == "Low" ? 'selected' : '' }}>Low</option>
                                        <option value="Medium" {{ $incident->priority == "Medium" ? 'selected' : '' }}>Medium</option>
                                        <option value="High" {{ $incident->priority == "High" ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                                @if(isset($investigations[0]))
                                    <small class="text-muted">Priority is locked once the incident has been classified.</small>
                                @endif
                            </div>
                            <div>
                                <label for="severity" class="iv-fl">Incident severity</label>
                                <div class="iv-selwrap">
                                    <select class="form-select select2t-none" name="severity" id="severity" aria-label="Incident severity" {{ isset($investigations[0]) ? 'disabled' : '' }}>
                                        <option value="">Select Severity</option>
                                        @if($severities)
                                            @foreach($severities as $severity)
                                                <option value="{{$severity}}" {{ $incident->severity == $severity ? 'selected' : '' }}>{{$severity}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                @if(isset($investigations[0]))
                                    <small class="text-muted">Severity is locked once the incident has been classified.</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Authority notified — real native radios (name="police"
                     etc.), just visually restyled as pill buttons via CSS
                     (:checked + label). Existing show/hide-date-time JS,
                     disabled-once-set behavior, and the submit handler's
                     :checked reads all keep working unchanged. --}}
                <section class="iv-sec">
                    <div class="iv-sec-h"><h2>Authority notified</h2></div>
                    <div class="iv-card">
                        <div class="iv-auth">
                            <span class="nm">Police</span>
                            <div class="iv-pillset">
                                <input type="radio" class="pill-radio police-option" name="police" id="yesPolice" value="yes" {{ isset($investigations[0]) && $investigations[0]->police_notified === 'yes' ? 'checked' : '' }} {{ isset($investigations[0]) && $investigations[0]->police_notified ? 'disabled' : '' }}><label for="yesPolice">Yes</label>
                                <input type="radio" class="pill-radio police-option" name="police" id="noPolice" value="no" {{ isset($investigations[0]) && $investigations[0]->police_notified === 'no' ? 'checked' : '' }} {{ isset($investigations[0]) && $investigations[0]->police_notified ? 'disabled' : '' }}><label for="noPolice">No</label>
                                <input type="radio" class="pill-radio police-option" name="police" id="notReqPolice" value="not_required" {{ isset($investigations[0]) && $investigations[0]->police_notified === 'not_required' ? 'checked' : '' }} {{ isset($investigations[0]) && $investigations[0]->police_notified ? 'disabled' : '' }}><label for="notReqPolice">Not required</label>
                            </div>
                        </div>
                        <div class="row mt-2 police-date-time d-none">
                            <div class="col-md-3">
                                <input type="text" class="form-control datepicker iv-inp" placeholder="Police Date" name="police_date" id="police_date" value="{{isset($investigations[0]) &&  $investigations[0]->police_date ?? '' }}" @if(isset($investigations[0]) && $investigations[0]->police_date) readonly @endif>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control iv-inp" placeholder="Police Time" name="police_time" id="police_time" value="{{ $investigations[0]->police_time ?? '' }}" @if(isset($investigations[0]) && $investigations[0]->police_time) readonly @endif>
                            </div>
                        </div>

                        <div class="iv-auth">
                            <span class="nm">MNDF</span>
                            <div class="iv-pillset">
                                <input type="radio" class="pill-radio mdf-option" name="mdf" id="yesMdf" value="yes" {{ isset($investigations[0]) && $investigations[0]->mdf_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}><label for="yesMdf">Yes</label>
                                <input type="radio" class="pill-radio mdf-option" name="mdf" id="noMdf" value="no" {{isset($investigations[0]) && $investigations[0]->mdf_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}><label for="noMdf">No</label>
                                <input type="radio" class="pill-radio mdf-option" name="mdf" id="notReqMdf" value="not_required" {{isset($investigations[0]) && $investigations[0]->mdf_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}><label for="notReqMdf">Not required</label>
                            </div>
                        </div>
                        <div class="row mt-2 mdf-date-time d-none">
                            <div class="col-md-3">
                                <input type="text" class="form-control datepicker iv-inp" placeholder="MNDF Date" name="mndf_date" id="mndf_date" value="{{ $investigations[0]->mndf_date ?? '' }}" {{isset($investigations[0]) && $investigations[0]->mndf_date ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control iv-inp" placeholder="MNDF Time" name="mndf_time" id="mndf_time" value="{{ $investigations[0]->mndf_time ?? '' }}" {{isset($investigations[0]) && $investigations[0]->mndf_time ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <div class="iv-auth">
                            <span class="nm">Fire &amp; Rescue Service</span>
                            <div class="iv-pillset">
                                <input type="radio" class="pill-radio fire-option" name="firerescue" id="yesFireRescue" value="yes" {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}><label for="yesFireRescue">Yes</label>
                                <input type="radio" class="pill-radio fire-option" name="firerescue" id="noFireRescue" value="no" {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}><label for="noFireRescue">No</label>
                                <input type="radio" class="pill-radio fire-option" name="firerescue" id="notReqFireRescue" value="not_required" {{ isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}><label for="notReqFireRescue">Not required</label>
                            </div>
                        </div>
                        <div class="row mt-2 fire-date-time d-none">
                            <div class="col-md-3">
                                <input type="text" class="form-control datepicker iv-inp" placeholder="Fire & Rescue Date" name="fire_date" id="fire_date" value="{{ $investigations[0]->fire_rescue_date ?? '' }}" {{isset($investigations[0]) &&  $investigations[0]->fire_rescue_date ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control iv-inp" placeholder="Fire & Rescue Time" name="fire_time" id="fire_time" value="{{ $investigations[0]->fire_rescue_time ??'' }}" {{isset($investigations[0]) &&  $investigations[0]->fire_rescue_time ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <div class="iv-auth">
                            <span class="nm">Ministry</span>
                            <div class="iv-pillset">
                                <input type="radio" class="pill-radio Ministry-option" name="Ministry_notified" id="yesMinistry" value="yes" {{isset($investigations[0]) && $investigations[0]->Ministry_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}><label for="yesMinistry">Yes</label>
                                <input type="radio" class="pill-radio Ministry-option" name="Ministry_notified" id="noMinistry" value="no" {{isset($investigations[0]) && $investigations[0]->Ministry_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}><label for="noMinistry">No</label>
                                <input type="radio" class="pill-radio Ministry-option" name="Ministry_notified" id="notReqMinistry" value="not_required" {{ isset($investigations[0]) && $investigations[0]->Ministry_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}><label for="notReqMinistry">Not required</label>
                            </div>
                        </div>
                        <div class="row mt-2 Ministry-date-time d-none">
                            <div class="col-md-3">
                                <input type="text" class="form-control datepicker iv-inp" placeholder="Ministry Date" name="Ministry_notified_date" id="Ministry_notified_date" value="{{ $investigations[0]->Ministry_notified_date ?? '' }}" {{isset($investigations[0]) &&  $investigations[0]->Ministry_notified_date ? 'readonly' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control iv-inp" placeholder="Ministry Time" name="Ministry_time" id="Ministry_time" value="{{ $investigations[0]->Ministry_time ??'' }}" {{isset($investigations[0]) &&  $investigations[0]->Ministry_time ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                </section>
                </div>

                {{-- Investigation reports — one tab per committee member who
                     has an investigation entry for this incident. Tabs come
                     straight from the existing $investigations collection
                     (each row is already one member's submission — same
                     data the old plain table rendered), so the tab count is
                     never hardcoded and always matches real records. --}}
                <section class="iv-sec">
                    <div class="iv-sec-h"><h2>Investigation reports</h2></div>
                    <div class="iv-card iv-card-flush">
                        @if($investigations && count($investigations))
                            <div class="iv-tabbar" role="tablist">
                                @foreach($investigations as $i => $investigation)
                                    @php
                                        $repName = $investigation->addedBy->employee->resortAdmin->full_name ?? '-';
                                        $repHasPhoto = !empty($investigation->addedBy->employee->resortAdmin->profile_picture ?? null);
                                        [$repStatusClass, $repStatusLabel] = $ivReportStatus($investigation);
                                    @endphp
                                    <button type="button" class="iv-tab {{ $i === 0 ? 'on' : '' }}" data-i="{{ $i }}">
                                        <span class="av" style="background:{{ $ivAvatarColor($repName) }};color:#fff">
                                            @if($repHasPhoto)
                                                <img src="{{ Common::getResortUserPicture($investigation->addedBy->employee->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                            @endif
                                            {{ $ivInitials($repName) }}
                                        </span>
                                        {{ $repName }}
                                        <span class="tstat {{ $repStatusClass }}"></span>
                                    </button>
                                @endforeach
                            </div>
                            @foreach($investigations as $i => $investigation)
                                @php
                                    $repName = $investigation->addedBy->employee->resortAdmin->full_name ?? '-';
                                    $repHasPhoto = !empty($investigation->addedBy->employee->resortAdmin->profile_picture ?? null);
                                    [$repStatusClass, $repStatusLabel] = $ivReportStatus($investigation);
                                @endphp
                                <div class="iv-tpanel" data-p="{{ $i }}" @if($i !== 0) hidden @endif>
                                    <div class="iv-prow">
                                        <div class="who">
                                            <span class="av" style="background:{{ $ivAvatarColor($repName) }};color:#fff">
                                                @if($repHasPhoto)
                                                    <img src="{{ Common::getResortUserPicture($investigation->addedBy->employee->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                                @endif
                                                {{ $ivInitials($repName) }}
                                            </span>
                                            <div><div class="n">{{ $repName }}</div></div>
                                        </div>
                                        <div class="iv-dpair">
                                            <div class="iv-dchip2"><div class="l">Start</div><div class="v">{{ $investigation->start_date ?: '-' }}</div></div>
                                            <div class="iv-dchip2"><div class="l">Expected</div><div class="v">{{ $investigation->expected_resolution_date ?: '-' }}</div></div>
                                        </div>
                                        <span class="pill {{ $repStatusClass }}"><span class="d"></span>{{ $repStatusLabel }}</span>
                                    </div>
                                    <div class="iv-blk"><div class="l">Investigation findings</div><p>{{ $investigation->investigation_findings ?: '-' }}</p></div>
                                    <div class="iv-blk"><div class="l">Follow-up actions</div><p>{{ $investigation->followupAction->followup_action ?? '-' }}</p></div>
                                    <div class="iv-blk"><div class="l">Resolution notes</div><p>{{ $investigation->resolution_notes ?: '-' }}</p></div>
                                </div>
                            @endforeach
                        @else
                            <div class="iv-empty">No investigation reports yet.</div>
                        @endif
                    </div>
                </section>
                {{-- Investigation details (editable — the current investigator's
                     own entry). Follow-up action lives in the same card, in
                     the left rail alongside the dates, rather than its own
                     separate section. --}}
                <section class="iv-sec">
                    <div class="iv-sec-h"><h2>Investigation details</h2></div>
                    <div class="iv-card">
                        <div class="iv-idgrid">
                            <div class="iv-idrail">
                                <div>
                                    <label for="start_date" class="iv-fl">Start date</label>
                                    <input type="text" class="form-control datepicker iv-inp iv-datefield" id="start_date" placeholder="Select date">
                                </div>
                                <div>
                                    <label for="expResoDate" class="iv-fl">Expected resolution date</label>
                                    <input type="text" class="form-control datepicker iv-inp iv-datefield" id="expResoDate" placeholder="Select date">
                                </div>
                                <div>
                                    <label for="followUpActions" class="iv-fl">Follow-up action</label>
                                    <div class="iv-selwrap">
                                        <select class="form-select select2t-none" id="followUpActions" aria-label="Follow-up action">
                                            <option value="">Select a follow-up action</option>
                                            @if($followup_actions)
                                                @foreach($followup_actions as $action)
                                                    <option value="{{ $action->id }}" data-requires-statement="{{ $action->requires_employee_statement ? '1' : '0' }}">{{ $action->followup_action }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="d-none mt-3" id="request-witness-statement">
                                        <a href="#" class="btn eb-btn-accent" id="btn-request-statement" data-incident-id="{{ $incident->id }}">
                                            Request employee statement
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="iv-idmain">
                                <label for="investFind" class="iv-fl">Investigation findings &amp; recommendations</label>
                                <textarea class="form-control iv-inp" id="investFind" placeholder="Document findings, evidence reviewed, and recommended actions&hellip;"></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Employee statements + Witness statements — two-column
                     pair, each an accordion. "Submitted" rows (real
                     statement text on record) expand; "Pending" rows have
                     nothing to expand, matching what's actually in the DB. --}}
                <div class="iv-pair">
                <section class="iv-sec">
                    <div class="iv-sec-h">
                        <h2>Employee statements</h2>
                        <span class="iv-countchip">{{ count($incident_employee_statements ?? []) }} {{ Str::plural('statement', count($incident_employee_statements ?? [])) }} &middot; {{ collect($incident_employee_statements ?? [])->filter(fn($s) => !empty($s->statement))->count() }} submitted</span>
                    </div>
                    @if($incident_employee_statements && count($incident_employee_statements))
                        <div class="iv-stmtcard">
                            @foreach($incident_employee_statements as $statement)
                                @php
                                    $stName = $statement->employee->resortAdmin->full_name ?? 'Unknown Employee';
                                    $stHasPhoto = !empty($statement->employee->resortAdmin->profile_picture ?? null);
                                    $stSubmitted = !empty($statement->statement);
                                    $stAttachments = array_filter(array_map(function ($f) {
                                        return trim(str_replace(['\\', '"'], '', $f));
                                    }, (array) json_decode($statement->document_path, true) ?: []));
                                @endphp
                                <div class="iv-srow {{ $stSubmitted ? '' : 'no-expand' }}">
                                    <div class="iv-shead {{ $stSubmitted ? 'click' : '' }}">
                                        <span class="av" style="background:{{ $ivAvatarColor($stName) }};color:#fff">
                                            @if($stHasPhoto)
                                                <img src="{{ Common::getResortUserPicture($statement->employee->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                            @endif
                                            {{ $ivInitials($stName) }}
                                        </span>
                                        <div class="who">
                                            <div class="n">{{ $stName }}</div>
                                            <div class="dt">{{ \Carbon\Carbon::flexible($statement->created_at)->format('d M Y') }} &middot; {{ \Carbon\Carbon::flexible($statement->created_at)->format('h:i A') }}</div>
                                        </div>
                                        <span class="pill {{ $stSubmitted ? 'ok' : 'pend' }}"><span class="d"></span>{{ $stSubmitted ? 'Submitted' : 'Pending' }}</span>
                                        @if($stSubmitted)<span class="chev">&rsaquo;</span>@endif
                                    </div>
                                    @if($stSubmitted)
                                        <div class="iv-sbody">
                                            <div class="txt">{{ $statement->statement }}</div>
                                            <div class="att">
                                                @if(count($stAttachments))
                                                    @foreach($stAttachments as $file)
                                                        @php
                                                            $fileUrl = \App\Helpers\StorageHelper::temporaryUrl($file);
                                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                            switch ($extension) {
                                                                case 'pdf':
                                                                    $icon = 'fa-file-pdf text-danger';
                                                                    break;
                                                                case 'doc':
                                                                case 'docx':
                                                                    $icon = 'fa-file-word text-primary';
                                                                    break;
                                                                case 'xls':
                                                                case 'xlsx':
                                                                    $icon = 'fa-file-excel text-success';
                                                                    break;
                                                                case 'jpg':
                                                                case 'jpeg':
                                                                case 'png':
                                                                case 'gif':
                                                                    $icon = 'fa-file-image text-warning';
                                                                    break;
                                                                default:
                                                                    $icon = 'fa-file text-secondary';
                                                                    break;
                                                            }
                                                        @endphp
                                                        <a href="{{ $fileUrl }}" target="_blank" class="me-3" title="{{ basename($file) }}"><i class="fa-solid {{ $icon }}"></i> View</a>
                                                    @endforeach
                                                @else
                                                    <span>No attachments</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="iv-empty">No employee statements yet.</div>
                    @endif
                </section>

                {{-- Witness statements — accordion, same pattern --}}
                <section class="iv-sec">
                    <div class="iv-sec-h">
                        <h2>Witness statements</h2>
                        <span class="iv-countchip">{{ count($incident_witness_statements ?? []) }} {{ Str::plural('witness', count($incident_witness_statements ?? []), 'witnesses') }} &middot; {{ collect($incident_witness_statements ?? [])->filter(fn($w) => !empty($w->witness_statements))->count() }} submitted</span>
                    </div>
                    @if($incident_witness_statements && count($incident_witness_statements))
                        <div class="iv-stmtcard">
                            @foreach($incident_witness_statements as $witness)
                                @php
                                    $wName = $witness->employee->resortAdmin->full_name ?? 'Unknown Witness';
                                    $wHasPhoto = !empty($witness->employee->resortAdmin->profile_picture ?? null);
                                    $wSubmitted = !empty($witness->witness_statements);
                                    $wAttachments = array_filter(array_map(function ($f) {
                                        return trim(str_replace(['\\', '"'], '', $f));
                                    }, (array) json_decode($witness->witness_statement_file, true) ?: []));
                                @endphp
                                <div class="iv-srow {{ $wSubmitted ? '' : 'no-expand' }}">
                                    <div class="iv-shead {{ $wSubmitted ? 'click' : '' }}">
                                        <span class="av" style="background:{{ $ivAvatarColor($wName) }};color:#fff">
                                            @if($wHasPhoto)
                                                <img src="{{ Common::getResortUserPicture($witness->employee->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                            @endif
                                            {{ $ivInitials($wName) }}
                                        </span>
                                        <div class="who">
                                            <div class="n">{{ $wName }}</div>
                                            <div class="dt">{{ \Carbon\Carbon::flexible($witness->created_at)->format('d M Y') }} &middot; {{ \Carbon\Carbon::flexible($witness->created_at)->format('h:i A') }}</div>
                                        </div>
                                        <span class="pill {{ $wSubmitted ? 'ok' : 'pend' }}"><span class="d"></span>{{ $wSubmitted ? 'Submitted' : 'Pending' }}</span>
                                        @if($wSubmitted)<span class="chev">&rsaquo;</span>@endif
                                    </div>
                                    @if($wSubmitted)
                                        <div class="iv-sbody">
                                            <div class="txt">{{ $witness->witness_statements }}</div>
                                            <div class="att">
                                                @if(count($wAttachments))
                                                    @foreach($wAttachments as $file)
                                                        @php
                                                            $fileUrl = \App\Helpers\StorageHelper::temporaryUrl($file);
                                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                            switch ($extension) {
                                                                case 'pdf':
                                                                    $icon = 'fa-file-pdf text-danger';
                                                                    break;
                                                                case 'doc':
                                                                case 'docx':
                                                                    $icon = 'fa-file-word text-primary';
                                                                    break;
                                                                case 'xls':
                                                                case 'xlsx':
                                                                    $icon = 'fa-file-excel text-success';
                                                                    break;
                                                                case 'jpg':
                                                                case 'jpeg':
                                                                case 'png':
                                                                case 'gif':
                                                                    $icon = 'fa-file-image text-warning';
                                                                    break;
                                                                default:
                                                                    $icon = 'fa-file text-secondary';
                                                                    break;
                                                            }
                                                        @endphp
                                                        <a href="{{ $fileUrl }}" target="_blank" class="me-3" title="{{ basename($file) }}"><i class="fa-solid {{ $icon }}"></i> View</a>
                                                    @endforeach
                                                @else
                                                    <span>No attachments</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="iv-empty">No witness statements yet.</div>
                    @endif
                </section>
                </div>

                {{-- Resolution & status. The HR-only outcome/preventive-
                     measures/action-taken/approval fields are unique to this
                     app (not part of the reference design) and stay as their
                     own block above the resolution/status pairing, so none
                     of that existing functionality is lost. --}}
                <section class="iv-sec">
                    <div class="iv-sec-h"><h2>Resolution &amp; status</h2></div>
                    <div class="iv-card">
                        @if($isHR)
                            <div class="iv-grid3" style="margin-bottom:16px">
                                <div>
                                    <label for="outcomeType" class="iv-fl">Outcome type</label>
                                    <div class="iv-selwrap">
                                        <select class="form-select select2t-none" id="outcomeType" aria-label="Outcome type">
                                            <option value="">Select outcome type</option>
                                            @if($outcome_types)
                                                @foreach($outcome_types as $type)
                                                    <option value="{{$type->id}}">{{$type->outcome_type}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="pre_mea" class="iv-fl">Preventive measures</label>
                                    <textarea class="form-control iv-inp" id="pre_mea" name="pre_mea" rows="1"></textarea>
                                </div>
                                <div>
                                    <label for="action_taken" class="iv-fl">Action taken</label>
                                    <div class="iv-selwrap">
                                        <select class="form-select select2t-none" id="action_taken" aria-label="Action taken">
                                            <option value="">Select action taken</option>
                                            @if($action_takens)
                                                @foreach($action_takens as $action)
                                                    <option value="{{$action->id}}">{{$action->action_taken}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check" style="margin-bottom:16px">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="approval" value="1" {{ $incident->approval == 1 ? 'checked' : '' }} {{ $incident->approval == 1 ? 'disabled' : '' }}>
                                <label class="form-check-label" for="inlineCheckbox1">Forward the investigation report to relevant approvers</label>
                            </div>
                        @endif

                        <div class="iv-idgrid iv-idgrid-rev">
                            <div class="iv-idmain">
                                <label for="resolutionNotes" class="iv-fl">Resolution notes</label>
                                <textarea class="form-control iv-inp" id="resolutionNotes" placeholder="Summarize the resolution, decisions, and any final actions&hellip;"></textarea>
                            </div>
                            <div class="iv-idrail">
                                <div>
                                    <label for="status" class="iv-fl">Status</label>
                                    <div class="iv-selwrap">
                                        <select class="form-select select2t-none" name="status" id="status" aria-label="Status">
                                            <option value="">Select status</option>
                                            @if($statuses)
                                                @foreach($statuses as $st)
                                                    <option value="{{$st}}" {{ $incident->status == $st ? 'selected' : '' }}>{{$st}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="iv-actions">
                    <a href="{{ route('incident.index') }}" class="btn eb-btn-secondary">Cancel</a>
                    <button type="submit" class="btn eb-btn-primary">Submit</button>
                </div>
                </form>
            </div>
        </div>
    </div>
     @include('partials._file_view_modal')
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
<style>
/* Incident Investigation — restyle only. Reuses the app's real global
   tokens (--teal/--ink/--muted/--faint/--line/--card/--positive/--warning/
   --error, from resorts/layouts/_design_tokens.blade.php, included by the
   base layout on every page) directly rather than inventing a parallel
   token set — the Consolidated Budget page regression earlier this session
   was exactly a page defining its own local --wb-* tokens that drifted out
   of sync with the shared ones, so this file deliberately avoids that
   pattern. Scoped under .iv-wrap so nothing here can leak onto other pages. */
.iv-wrap{width:100%}
.iv-wrap .tnum{font-variant-numeric:tabular-nums}

/* Two-up rows — pair on wide screens, stack on narrow. .eq additionally
   stretches both columns to match the taller card's height. */
.iv-pair{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;margin-top:26px}
.iv-pair>.iv-sec{margin-top:0}
.iv-pair-eq{align-items:stretch}
.iv-pair-eq>.iv-sec{display:flex;flex-direction:column}
.iv-pair-eq>.iv-sec>.iv-card{flex:1}
@media(max-width:900px){.iv-pair{grid-template-columns:1fr;gap:0}.iv-pair>.iv-sec+.iv-sec{margin-top:26px}}

.iv-sec{margin-top:26px}
.iv-sec-h{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.iv-sec-h h2{font-size:18px;font-weight:600;color:var(--ink);margin:0}
.iv-sec-h::after{content:"";flex:1;height:1px;background:var(--line)}
.iv-sec-h .iv-countchip{font-size:11px;font-weight:600;color:var(--muted);background:#fff;border:1px solid var(--line);border-radius:20px;padding:3px 10px}

.iv-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px 22px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05)}
.iv-card-flush{padding:0}

.iv-wrap .av{border-radius:50%;display:grid;place-items:center;font-weight:600;flex:none;position:relative;overflow:hidden;font-size:10px}
.iv-wrap .av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
.iv-mlabel{font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--faint);display:block}

/* Case summary — main card + a separate "Reported by" side panel,
   equal height (grid children stretch to the row height by default). */
.iv-summary{display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:stretch;margin:0 0 22px}
@media(max-width:820px){.iv-summary{grid-template-columns:1fr}}
.iv-sum-main{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:28px 30px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05)}
.iv-ref{font-size:11px;font-weight:600;color:var(--teal);background:var(--teal-3);padding:4px 11px;border-radius:20px;letter-spacing:.3px}
.iv-sum-main h3{font-size:22px;font-weight:600;letter-spacing:-.4px;color:var(--ink);line-height:1.22;margin:0}
.iv-classify{display:flex;align-items:center;gap:9px;margin-top:11px;flex-wrap:wrap}
.iv-classify .catx{font-size:14px;font-weight:600;color:var(--teal)}
.iv-classify .sep{color:var(--faint);font-size:14px;line-height:1}
.iv-classify .subx{font-size:14px;color:var(--muted)}
.iv-account{margin-top:22px}
.iv-account .iv-mlabel{margin-bottom:9px}
.iv-quote{font-size:14px;color:var(--muted);line-height:1.65;max-width:640px}
.iv-strip{display:flex;flex-wrap:wrap;gap:30px;margin-top:24px;padding-top:20px;border-top:1px solid var(--line)}
.iv-fct .iv-mlabel{margin-bottom:8px}

/* Reported-by side panel */
.iv-sum-side{background:var(--teal-soft);border:1px solid var(--line);border-radius:14px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);overflow:hidden}
.iv-ehead{font-size:14px;font-weight:600;color:var(--ink);padding:15px 20px;border-bottom:1px solid var(--line)}
.iv-ebody{padding:18px 20px}
.iv-top{display:flex;align-items:center;gap:13px}
.bigav{width:52px;height:52px;font-size:15px;border-radius:50%;display:grid;place-items:center;font-weight:600;position:relative;overflow:hidden;flex:none}
.bigav img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
.iv-top .nm{font-size:16px;font-weight:600;line-height:1.2;color:var(--ink)}
.iv-rows{margin-top:16px}
.iv-rows .r{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--line-2)}
.iv-rows .r:last-child{border-bottom:none}
.iv-rows .r .l{font-size:12.5px;color:var(--muted)}
.iv-rows .r .v{font-size:13px;font-weight:500;color:var(--ink);text-align:right;font-variant-numeric:tabular-nums}

.pill{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;padding:4px 11px;border-radius:20px;white-space:nowrap}
.pill .d{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.pill.warn{background:var(--warning-bg);color:var(--warning)}
.pill.ok{background:var(--positive-bg);color:var(--positive)}
.pill.error{background:var(--error-bg);color:var(--error)}
.pill.pend{background:var(--warning-bg);color:var(--warning)}
.pill.prog{background:var(--teal-3);color:var(--teal)}
.pill.done{background:var(--positive-bg);color:var(--positive)}
.pill.neu{background:var(--neutral-bg);color:var(--muted)}

.iv-filechip{display:inline-flex;align-items:center;gap:7px;max-width:220px;font-size:12.5px;color:var(--muted)}
.iv-filechip svg{color:var(--faint);flex:none}
.iv-filechip .fn{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--muted)}
.iv-filechip .more{color:var(--teal);font-weight:600;flex:none}
.iv-att-none{font-size:12.5px;color:var(--faint);display:flex;align-items:center;gap:7px}

/* Investigation reports — tabs */
.iv-tabbar{display:flex;gap:6px;padding:6px 16px 0;flex-wrap:wrap;border-bottom:1px solid var(--line)}
.iv-tab{display:flex;align-items:center;gap:9px;font-family:inherit;font-size:14px;font-weight:500;color:var(--muted);background:transparent;border:none;border-bottom:2px solid transparent;padding:8px 10px 12px;cursor:pointer;margin-bottom:-1px}
.iv-tab .av{width:26px;height:26px}
.iv-tab .tstat{width:7px;height:7px;border-radius:50%;flex:none}
.iv-tab .tstat.review{background:var(--warning)}.iv-tab .tstat.prog{background:var(--teal)}.iv-tab .tstat.done{background:var(--positive)}
.iv-tab.on{color:var(--teal);font-weight:600;border-bottom-color:var(--teal)}
.iv-tab:not(.on):hover{color:var(--ink)}
.iv-tpanel{padding:20px 22px 6px}
.iv-tpanel[hidden]{display:none}
.iv-prow{display:flex;align-items:center;gap:14px;margin-bottom:6px;flex-wrap:wrap}
.iv-prow .who{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.iv-prow .who .av{width:32px;height:32px}
.iv-prow .who .n{font-size:14px;font-weight:500;color:var(--ink)}
.iv-dpair{display:flex;gap:8px}
.iv-dchip2{background:var(--neutral-bg);border:1px solid var(--line);border-radius:9px;padding:5px 11px}
.iv-dchip2 .l{font-size:8.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--faint)}
.iv-dchip2 .v{font-size:12.5px;font-weight:600;color:var(--teal)}
.iv-blk{padding:13px 0;border-top:1px solid var(--line-2)}
.iv-blk .l{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px}
.iv-blk p{font-size:15px;color:var(--muted);line-height:1.6;margin:0}
.iv-empty{padding:20px 22px;font-size:14px;color:var(--faint)}

/* form fields */
.iv-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.iv-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
@media(max-width:640px){.iv-grid2,.iv-grid3{grid-template-columns:1fr}}
.iv-fl{font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);display:block;margin-bottom:7px}
.iv-inp{font-family:inherit;font-size:14px;color:var(--ink);border-color:var(--faint);border-radius:10px;padding:11px 13px}
.iv-inp:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(1,70,83,.10)}
.iv-inp[readonly]{background:var(--line-2);cursor:not-allowed}
.iv-datefield{cursor:pointer;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2393A4A9' stroke-width='2'><rect x='3' y='4' width='18' height='18' rx='3'/><path d='M3 10h18M8 2v4M16 2v4'/></svg>");background-repeat:no-repeat;background-position:right 13px center;padding-right:38px}
.iv-selwrap .select2-container{width:100% !important}
textarea.iv-inp{max-width:820px}

/* Investigation details / Resolution & status — left/right rail beside
   the main write-up. .iv-idgrid-rev flips which side is the rail. */
.iv-idgrid{display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:stretch}
@media(max-width:820px){.iv-idgrid{grid-template-columns:1fr}}
.iv-idrail{display:flex;flex-direction:column;gap:16px}
.iv-idmain{display:flex;flex-direction:column}
.iv-idmain textarea{max-width:none;flex:1;min-height:220px}
.iv-idgrid-rev{grid-template-columns:1fr 300px}
@media(max-width:820px){.iv-idgrid-rev{grid-template-columns:1fr}}

/* authority notified — pill radios */
.iv-auth{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid var(--line-2);flex-wrap:wrap;gap:10px}
.iv-auth:last-of-type{border-bottom:none}
.iv-auth .nm{font-size:14px;font-weight:500;color:var(--ink)}
.iv-pillset{display:flex;gap:8px;flex-wrap:wrap}
.iv-pillset .pill-radio{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
.iv-pillset label{border:1px solid var(--faint);background:#fff;border-radius:20px;padding:6px 15px;font-size:12.5px;font-weight:500;color:var(--muted);cursor:pointer;transition:.15s;margin:0}
.iv-pillset .pill-radio:checked+label{background:var(--teal);color:#fff;border-color:var(--teal);font-weight:600}
.iv-pillset .pill-radio:disabled+label{opacity:.65;cursor:not-allowed}
.iv-pillset .pill-radio:not(:checked):not(:disabled)+label:hover{border-color:var(--teal);color:var(--teal)}
.iv-pillset .pill-radio:focus-visible+label{outline:2px solid var(--teal);outline-offset:2px}

/* statements — accordion */
.iv-stmtcard{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);overflow:hidden}
.iv-srow{border-bottom:1px solid var(--line-2)}
.iv-srow:last-child{border-bottom:none}
.iv-shead{display:flex;align-items:center;gap:12px;padding:13px 20px}
.iv-shead.click{cursor:pointer}
.iv-shead.click:hover{background:var(--teal-soft)}
.iv-shead .av{width:34px;height:34px}
.iv-shead .who{flex:1;min-width:0}
.iv-shead .who .n{font-size:14px;font-weight:500;color:var(--ink)}
.iv-shead .who .dt{font-size:11px;color:var(--faint);margin-top:1px}
.iv-shead .chev{color:var(--faint);font-size:14px;transition:transform .2s ease}
.iv-srow.open .iv-shead{background:var(--teal-soft)}
.iv-srow.open .chev{transform:rotate(90deg);color:var(--teal)}
.iv-sbody{display:none;padding:16px 20px 18px 66px}
.iv-srow.open .iv-sbody{display:block}
.iv-sbody .txt{font-size:14px;color:var(--muted);line-height:1.6;max-width:900px}
.iv-sbody .att{font-size:11px;color:var(--faint);margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}

.iv-actions{display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:26px}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        $('.select2t-none').select2();
        $('.datepicker').each(function () {
            if (!$(this).prop('readonly')) {
                flatpickr(this, {
                    dateFormat: 'd/m/Y',
                    allowInput: true,
                    appendTo: document.body
                });
            }
        });

        // Investigation start / expected resolution dates must be today
        // or later. Other datepickers on this page (mndf_date, fire_date,
        // Ministry_notified_date) record past events, so the past-date
        // lock is applied per-field rather than globally on `.datepicker`.
        $('#start_date, #expResoDate').each(function () {
            if (this._flatpickr) {
                this._flatpickr.set('minDate', new Date());
            }
        });

        const originalPriority = $('#original_priority').val();

        $('#priority_level').on('change', function () {
            let newValue = $(this).val();

            if (originalPriority && newValue !== originalPriority) {
                wisdomConfirm({
                    role: 'confirm',
                    title: 'Are you sure?',
                    text: 'Priority level was already set by HR. Do you want to change it?',
                    confirmText: 'Yes, change it',
                    cancelText: 'No, keep original'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        $('#priority_level').val(originalPriority); // Revert if not confirmed
                    }
                });
            }
        });


        function toggleDateTimeSection(group, value) {
            if (value === 'yes') {
                $('.' + group + '-date-time').removeClass('d-none');
            } else {
                $('.' + group + '-date-time').addClass('d-none');
            }
        }

        const policeValue = $('input[name="police"]:checked').val();
        toggleDateTimeSection('police', policeValue);

        const mdfValue = $('input[name="mdf"]:checked').val();
        toggleDateTimeSection('mdf', mdfValue);

        const fireValue = $('input[name="firerescue"]:checked').val();
        toggleDateTimeSection('fire', fireValue);

        $('.police-option').change(function () {
            toggleDateTimeSection('police', $(this).val());
        });

        $('.mdf-option').change(function () {
            toggleDateTimeSection('mdf', $(this).val());
        });

        $('.fire-option').change(function () {
            toggleDateTimeSection('fire', $(this).val());
        });
        $('.Ministry-option').change(function () {
            toggleDateTimeSection('Ministry', $(this).val());
        });
        $('#followUpActions').on('change', function () {
            let selectedOption = $(this).find('option:selected');
            let requiresStatement = selectedOption.data('requires-statement');

            if (requiresStatement == 1) {
                $('#request-witness-statement').removeClass('d-none');
            } else {
                $('#request-witness-statement').addClass('d-none');
            }
        });

        $('#incidentInvestigation').on('submit', function(e) {
            e.preventDefault();

            let formData = {
                incident_id:$('#incident_id').val(),
                priority: $('#priority_level').val(),
                severity: $('#severity').val(),
                police: $("input[name='police']:checked").val(),
                police_date: $('#police_date').val(),
                police_time: $('#police_time').val(),
                mdf: $("input[name='mdf']:checked").val(),
                mndf_date: $('#mndf_date').val(),
                mndf_time: $('#mndf_time').val(),
                firerescue: $("input[name='firerescue']:checked").val(),
                fire_date: $('#fire_date').val(),
                fire_time: $('#fire_time').val(),
                start_date: $('#start_date').val(),
                expResoDate: $('#expResoDate').val(),
                investFind: $('#investFind').val(),
                followUpActions: $('#followUpActions').val(),
                resolutionNotes: $('#resolutionNotes').val(),
                outcomeType: $('#outcomeType').val(),
                pre_mea: $('#pre_mea').val(),
                action_taken: $('#action_taken').val(),
                approval: $('#inlineCheckbox1').is(':checked') ? 1 : 0,
                status: $('#status').val(),
                _token: '{{ csrf_token() }}',
                 Ministry_notified_date: $('#Ministry_notified_date').val(),
                Ministry_time: $('#Ministry_time').val(),
                Ministry_notified:$("input[name='Ministry_notified']:checked").val(),
            };

            $.ajax({
                url: "{{ route('incident.investigation.store') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // After saving, return to the incident list so the
                        // user can see the updated status in context.
                        setTimeout(function () {
                            window.location.href = '{{ route("incident.index") }}';
                        }, 800);
                    }
                },
                error: function(response) {
                    toastr.error('Error saving incident investigation. Please check inputs.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on('click', '#btn-request-statement', function (e) {
            e.preventDefault();

            const incidentId = $(this).data('incident-id');

            $.ajax({
                url: '{{ route("incident.request-statement") }}',
                method: 'POST',
                data: {
                    incident_id: incidentId,
                    "_token": "{{ csrf_token() }}"
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                },
                error: function (xhr) {
                    toastr.error("Failed to send request. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#inlineCheckbox1').on('change', function () {
            if ($(this).is(':checked')) {
                let incidentId = $('#incident_id').val();

                $.ajax({
                    url: '{{ route("incident.investigation.approve") }}',
                    type: 'POST',
                    data: {
                        incident_id: incidentId,
                        approval: $('#inlineCheckbox1').is(':checked') ? 1 : 0,
                        "_token": "{{ csrf_token() }}"
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        toastr.success(response.message || 'Approval forwarded to GM.', 'Success', {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function () {
                        toastr.error('Failed to update approval status.', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#inlineCheckbox1').prop('checked', false); // revert if failed
                    }
                });
            }
        });

         $(document).on("click", ".download-link", function(e) {
            e.preventDefault();
            var childId = $(this).data('id');
            var $downloadLink = $(this);

            // First, set a loading message
            $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Show the modal with the loading message
            $("#bdVisa-iframeModel-modal-lg").modal('show');

            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                data: { child_id: childId, "_token":"{{csrf_token()}}"},
                success: function(response)
                {
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
                    else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        // window.location.href = fileUrl; // Triggers download automatically
                    }
                },
                error: function(xhr, status, error)
                {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    toastr.error("An error occurred while downloading the file.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

    })
    document.addEventListener("DOMContentLoaded", function () {
        function toggleDateTime(radioClass, containerClass) {
            document.querySelectorAll("." + radioClass).forEach(function (radio) {
                radio.addEventListener("change", function () {
                    if (this.value === "yes") {
                        document.querySelector("." + containerClass).classList.remove("d-none");
                    } else {
                        document.querySelector("." + containerClass).classList.add("d-none");
                    }
                });
            });
        }
        toggleDateTime("police-option", "police-date-time");
        toggleDateTime("mdf-option", "mdf-date-time");
        toggleDateTime("fire-option", "fire-date-time");
        toggleDateTime("Ministry-option", "Ministry-date-time");
    });

    /* Investigation reports — tabs (switch between committee members' reports) */
    document.querySelectorAll('.iv-tabbar').forEach(function (bar) {
        var card = bar.parentElement;
        bar.querySelectorAll('.iv-tab').forEach(function (t) {
            t.addEventListener('click', function () {
                bar.querySelectorAll('.iv-tab').forEach(function (x) { x.classList.remove('on'); });
                t.classList.add('on');
                card.querySelectorAll('.iv-tpanel').forEach(function (p) {
                    p.hidden = (p.dataset.p !== t.dataset.i);
                });
            });
        });
    });

    /* Employee / witness statements — accordion (expand submitted rows only) */
    document.querySelectorAll('.iv-shead.click').forEach(function (h) {
        h.addEventListener('click', function () {
            h.closest('.iv-srow').classList.toggle('open');
        });
    });
</script>
@endsection
