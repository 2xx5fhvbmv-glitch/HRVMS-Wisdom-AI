@php
    // Presentation helpers only — every value below comes from the rows getTalentPoolGridApplicant() already returns.
    $canReject = \App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool', config('settings.resort_permissions.edit'));
    $canDelete = \App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool', config('settings.resort_permissions.delete'));
    $initials = fn ($n) => collect(explode(' ', trim((string) $n)))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') ?: '?';
    $availMap = [
        'available'        => ['Available', 'ok'],
        'pending'          => ['Pending Response', 'warn'],
        'unavailable'      => ['Unavailable', 'muted'],
        'consent_rejected' => ['Consent Rejected', 'err'],
    ];
@endphp
@if($Applicant_form_data->isNotEmpty())
    <div class="tpx-grid mb-4">
    @foreach($Applicant_form_data as $a)
        @php
            $name = trim(ucfirst($a->first_name) . ' ' . ucfirst($a->last_name));
            [$availLabel, $availClass] = $availMap[$a->availability_status] ?? ['Not checked', 'muted'];
            $stage = (($a->rank_name ?? '') === 'Wisdom AI' ? 'WAI' : ($a->rank_name ?? '')) . ' Rejected';
            $exp = $a->total_work_exp;
            $expText = ($exp !== null && $exp !== '') ? $exp . ' ' . ((float) $exp == 1 ? 'yr' : 'yrs') : null;
            $natExp = collect([$a->countryName, $expText])->filter()->implode(' · ') ?: '—';
            $consentDate = $a->consent_expiry_date ? \Carbon\Carbon::parse($a->consent_expiry_date) : null;
            $consentWord = null; $consentClass = 'muted';
            if ($consentDate) {
                if ($a->consent_status === 'pending') { $consentWord = 'Pending'; }
                elseif ($consentDate->isPast()) { $consentWord = 'Expired'; $consentClass = 'err'; }
                elseif ($consentDate->diffInDays(now()) <= 30) { $consentWord = 'Expiring'; $consentClass = 'warn'; }
                else { $consentWord = 'Valid'; $consentClass = 'ok'; }
            }
            $wai = is_numeric($a->AIRanking) ? max(0, min(100, (float) $a->AIRanking)) : null;
            $score = is_numeric($a->Scoring) ? max(0, min(100, (float) $a->Scoring)) : null;
        @endphp
        <div class="tp-card">
            <button type="button" class="kebab" aria-label="More actions"
                data-pid="{{ $a->applicant_id }}" data-rank="{{ $a->As_ApprovedBy }}" data-sid="{{ $a->applicant_status_id }}"
                data-comments="{{ $a->Comments }}" data-del-id="{{ base64_encode($a->id) }}" data-del-loc="{{ $a->id }}"
                data-can-reject="{{ $canReject ? 1 : 0 }}" data-can-delete="{{ $canDelete ? 1 : 0 }}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
            </button>
            <div class="tp-top">
                <span class="tpx-av">@if($a->profileImg)<img src="{{ $a->profileImg }}" alt="{{ $name }}" data-i="{{ $initials($name) }}" onerror="this.parentNode.textContent=this.dataset.i">@else{{ $initials($name) }}@endif</span>
                <div class="nm userApplicants-btn" data-id="{{ $a->applicant_id }}" style="cursor:pointer">{{ $name }}</div>
                <div class="ro">{{ ucfirst($a->position_title) }}</div>
                <div class="tp-status statuscell">
                    <span class="pill {{ $availClass }}">{{ $availLabel }}</span>
                    <span class="rejchip" data-stage="{{ $stage }}" data-reason="{{ $a->Comments }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>{{ $stage }}</span>
                </div>
            </div>
            <div class="tp-metrics">
                <div class="metric wai"><div class="ml">WAI Rank</div><div class="mv">{{ $wai !== null ? rtrim(rtrim(number_format($wai, 1), '0'), '.') : '—' }}@if($wai !== null)<small>/100</small>@endif</div><div class="bar"><i style="width:{{ $wai ?? 0 }}%"></i></div></div>
                <div class="metric score"><div class="ml">Scoring</div><div class="mv">{{ $score !== null ? rtrim(rtrim(number_format($score, 1), '0'), '.') : '—' }}@if($score !== null)<small>/100</small>@endif</div><div class="bar"><i style="width:{{ $score ?? 0 }}%"></i></div></div>
            </div>
            <div class="tp-info">
                <div class="inf"><span class="k">Nationality</span><span class="v">{{ $natExp }}</span></div>
                <div class="inf"><span class="k">Department</span><span class="v">{{ $a->Department ?: '—' }}</span></div>
                <div class="inf"><span class="k">Documents</span><span class="docs">
                    @if($a->curriculum_vitae)<a target="_blank" rel="noopener" href="{{ URL::asset($a->curriculum_vitae) }}" class="tpx-doc">CV</a>@endif
                    @if($a->passport_img)<a target="_blank" rel="noopener" href="{{ URL::asset($a->passport_img) }}" class="tpx-doc">Passport</a>@endif
                    @if(!$a->curriculum_vitae && !$a->passport_img)<span class="v">—</span>@endif
                </span></div>
                <div class="inf"><span class="k">Consent expiry</span><span class="v">
                    @if($consentDate){{ $consentDate->format('d M Y') }} · <span class="tpx-cs {{ $consentClass }}">{{ $consentWord }}</span>
                    @elseif($a->data_retention_month || $a->data_retention_year){{ $a->data_retention_month }} M/{{ $a->data_retention_year }} Y
                    @else — @endif
                </span></div>
            </div>
            <div class="tp-actions">
                <a href="javascript:void(0)" class="tpx-btn ghost checkAvailabilityBtn" data-id="{{ base64_encode($a->id) }}" title="Check availability">Check availability</a>
                <a href="javascript:void(0)" class="tpx-btn ghost sendConsentRequestBtn" data-id="{{ base64_encode($a->id) }}" title="Send consent request">Send consent</a>
            </div>
        </div>
    @endforeach
    </div>
@else
    <div class="tpx-empty">No applicants found.</div>
@endif

<nav aria-label="Talent pool pages">
    {!! $pagination !!}
</nav>
