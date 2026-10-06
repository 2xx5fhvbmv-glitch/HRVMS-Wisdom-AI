@php
    $apxIcon = [
        'dots' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>',
        'note' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
        'eye'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>',
        'wai'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z"/></svg>',
    ];
@endphp
@if($Applicant_form_data->isNotEmpty())
    <div class="apx-resinfo">
        Showing <b>{{ $Applicant_form_data->firstItem() }}–{{ $Applicant_form_data->lastItem() }}</b>
        of <b>{{ $Applicant_form_data->total() }}</b> {{ \Illuminate\Support\Str::plural('applicant', $Applicant_form_data->total()) }}
    </div>
    <div class="apx-grid">
    @foreach($Applicant_form_data as $a)
        @php
            $apxName   = trim(ucfirst($a->first_name) . ' ' . ucfirst($a->last_name));
            $apxRole   = ucfirst((string) $a->position_title);
            $apxAid    = base64_encode($a->id);
            $apxIni    = strtoupper(mb_substr((string) $a->first_name, 0, 1) . mb_substr((string) $a->last_name, 0, 1)) ?: '?';
            $apxExp    = $a->total_work_exp;
            $apxExpTxt = is_numeric($apxExp) ? $apxExp . ' ' . ((float) $apxExp == 1.0 ? 'yr' : 'yrs') : ($apxExp ?: '—');
            $apxEmployed = $a->employment_status !== 'Available';
            // Hiring progress (config-driven, no queries): same helper the old card used.
            $apxProg    = \App\Helpers\Common::applicantProgress($a->ApplicantStatus, $a->As_ApprovedBy, $a->vacancy_rank ?? null);
            $apxRingCls = $apxProg['state'] === 'rejected' ? 'err' : ($apxProg['state'] === 'success' ? 'ok' : 'info');
        @endphp
        <div class="apx-card" data-status="{{ $a->ApplicantStatus }}" data-rank="{{ $a->rank_name }}">
            <button type="button" class="apx-kebab" aria-label="More actions" aria-haspopup="menu"
                data-sid="{{ $a->applicant_id }}" data-aid="{{ $apxAid }}" data-rowid="{{ $a->id }}"
                data-status="{{ $a->ApplicantStatus }}" data-name="{{ $apxName }}" data-role="{{ $apxRole }}" data-country="{{ $a->countryName }}">{!! $apxIcon['dots'] !!}</button>
            <div class="apx-top">
                <div class="apx-ring {{ $apxRingCls }}" data-progress="{{ $apxProg['percent'] }}" title="Hiring progress: {{ $apxProg['percent'] }}%">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="trk" cx="60" cy="60" r="54"/><circle class="val" cx="60" cy="60" r="54"/></svg>
                    <span class="apx-av apx-av-lg">{{ $apxIni }}@if($a->profileImg)<img src="{{ $a->profileImg }}" alt="" onerror="this.remove()">@endif</span>
                </div>
                <div class="nm">{{ $apxName }}</div>
                <div class="ro">{{ $apxRole }}</div>
                <div class="apx-status">
                    <span class="apx-stage"></span>
                    <span class="apx-pill {{ $apxEmployed ? 'info' : 'muted' }}">{{ $apxEmployed ? 'Employed' : 'Not employed' }}</span>
                </div>
            </div>
            <div class="apx-sections">
                <div class="apx-sec">
                    <div class="apx-inf"><span class="k">Email</span><span class="v" title="{{ $a->email }}">{{ $a->email }}</span></div>
                    <div class="apx-inf"><span class="k">Contact</span><span class="v">{{ $a->contact }}</span></div>
                </div>
                <div class="apx-sec">
                    <div class="apx-inf"><span class="k">Country</span><span class="v apx-country" data-country="{{ $a->countryName }}">{{ $a->countryName ?: '—' }}</span></div>
                    <div class="apx-inf"><span class="k">Experience</span><span class="v">{{ $apxExpTxt }}</span></div>
                    <div class="apx-inf"><span class="k">Applied on</span><span class="v">{{ $a->Application_date }}</span></div>
                    <div class="apx-inf"><span class="k">Passport no.</span><span class="v">{{ $a->passport_no ?: '—' }}</span></div>
                    <div class="apx-inf"><span class="k">Employment status</span><span class="v">{{ $apxEmployed ? 'Employed' : 'Not employed' }}</span></div>
                    <div class="apx-inf"><span class="k">Current position</span><span class="v" title="{{ $a->job_title }}">{{ $a->job_title ?: '—' }}</span></div>
                </div>
            </div>
            <div class="apx-actions">
                <button type="button" class="apx-btn ghost ApplicantsNotes ApplicantsNotes_{{ $a->applicant_id }}" data-notes="{{ $a->Notes }}" data-id="{{ $apxAid }}" data-name="{{ $apxName }}" data-role="{{ $apxRole }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Notes" aria-label="Notes">{!! $apxIcon['note'] !!}</button>
                <button type="button" class="apx-btn ghost userApplicants-btn" data-id="{{ $a->applicant_id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="View applicant" aria-label="View applicant">{!! $apxIcon['eye'] !!}</button>
                <button type="button" class="apx-btn wai waiInsightsBtn" data-id="{{ $apxAid }}" data-bs-toggle="tooltip" data-bs-placement="top" title="WAI Insights — CV vs job description">{!! $apxIcon['wai'] !!}WAI CV</button>
            </div>
        </div>
    @endforeach
    </div>
    <div class="apx-pager">{!! $pagination !!}</div>
@else
    <div class="apx-empty">No applicants match your search.</div>
@endif
@include('resorts._emotional_buttons_v2_styles')
