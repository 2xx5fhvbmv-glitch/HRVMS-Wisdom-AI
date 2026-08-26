{{-- Learning & Development AI-insight detail modals. Included by the Learning
     HR dashboard; reads $learningInsights. Opened by the "View Details" links
     via the vanilla-JS frosted-modal system in hrdashboard.blade.php (matches
     modals_reference.html exactly). Table-only body — title/issue/recommendation
     already live on the WAI Insights card and the Recommendation modal. --}}
@php
    $compD = $learningInsights['completion']['details']   ?? [];
    $mandD = $learningInsights['mandatory']['details']    ?? [];
    $reqD  = $learningInsights['requests']['details']     ?? [];
    $probD = $learningInsights['probationary']['details'] ?? [];

    $ldRateClass = fn($rate) => $rate == 0 ? 'zero' : ($rate == 100 ? 'full' : '');
    $ldRowAttn = fn($rate) => $rate == 0 ? 'attn' : '';
@endphp

<!-- Training Completion Rate -->
<div class="wai-backdrop" id="learningInsightCompletionModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $learningInsights['completion']['title'] ?? 'Training Completion Rate' }}</div>
        @if(!empty($compD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Completion by program &middot; threshold {{ $compD['threshold'] ?? 0 }}%</div>
                <div class="m-tscroll">
                    <table class="m-table">
                        <thead><tr><th>Program</th><th>Participants</th><th>Met threshold</th><th>Rate</th></tr></thead>
                        <tbody>
                            @foreach($compD['rows'] as $row)
                                <tr class="{{ $ldRowAttn($row['rate']) }}"><td>{{ $row['program'] }}</td><td>{{ $row['participants'] }}</td><td>{{ $row['completed'] }}</td><td class="rate {{ $ldRateClass($row['rate']) }}">{{ $row['rate'] }}%</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="m-empty">No training attendance recorded yet.</p>
        @endif
    </div>
</div>

<!-- Mandatory Training Compliance -->
<div class="wai-backdrop" id="learningInsightMandatoryModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $learningInsights['mandatory']['title'] ?? 'Mandatory Training Compliance' }}</div>
        @if(!empty($mandD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">{{ $mandD['completed'] ?? 0 }} of {{ $mandD['required'] ?? 0 }} required completions ({{ $mandD['pct'] ?? 0 }}%) &middot; {{ $mandD['outstanding'] ?? 0 }} outstanding</div>
                <div class="m-tscroll">
                    <table class="m-table">
                        <thead><tr><th>Program</th><th>Required</th><th>Completed</th><th>Compliance</th></tr></thead>
                        <tbody>
                            @foreach($mandD['rows'] as $row)
                                <tr class="{{ $ldRowAttn($row['pct']) }}"><td>{{ $row['program'] }}</td><td>{{ $row['required'] }}</td><td>{{ $row['completed'] }}</td><td class="rate {{ $ldRateClass($row['pct']) }}">{{ $row['pct'] }}%</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="m-empty">No mandatory programs configured yet.</p>
        @endif
    </div>
</div>

<!-- Learning Request Pipeline -->
<div class="wai-backdrop" id="learningInsightRequestsModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $learningInsights['requests']['title'] ?? 'Learning Request Pipeline' }}</div>
        @if(!empty($reqD['counts']))
            <div class="m-tablewrap">
                <div class="m-tcap">Pipeline @if(isset($reqD['approval_rate']) && $reqD['approval_rate'] !== null)&middot; approval rate {{ $reqD['approval_rate'] }}%@endif</div>
                <div class="m-tscroll">
                    <table class="m-table">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            @foreach($reqD['counts'] as $st => $c)
                                <tr><td>{{ $st }}</td><td>{{ $c }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(!empty($reqD['categories']))
                <div class="m-tablewrap">
                    <div class="m-tcap">Top requested categories</div>
                    <div class="m-tscroll">
                        <table class="m-table">
                            <thead><tr><th>Category</th><th>Requests</th></tr></thead>
                            <tbody>
                                @foreach($reqD['categories'] as $row)
                                    <tr><td>{{ $row['category'] }}</td><td>{{ $row['count'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            @if(!empty($reqD['denials']))
                <div class="m-tablewrap">
                    <div class="m-tcap">Top denial reasons</div>
                    <div class="m-tscroll">
                        <table class="m-table">
                            <thead><tr><th>Reason</th><th>Count</th></tr></thead>
                            <tbody>
                                @foreach($reqD['denials'] as $row)
                                    <tr><td>{{ $row['reason'] }}</td><td>{{ $row['count'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            <p class="m-empty">No learning requests submitted yet.</p>
        @endif
    </div>
</div>

<!-- Probationary Training Progress -->
<div class="wai-backdrop" id="learningInsightProbationaryModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $learningInsights['probationary']['title'] ?? 'Probationary Training Progress' }}</div>
        @if(!empty($probD))
            <div class="m-tablewrap">
                <div class="m-tscroll">
                    <table class="m-table">
                        <tbody>
                            <tr><td>Staff on probation</td><td>{{ $probD['employees'] ?? 0 }}</td></tr>
                            <tr><td>Required programs</td><td>{{ $probD['programs'] ?? 0 }}</td></tr>
                            <tr><td>Completed</td><td>{{ $probD['completed'] ?? 0 }} of {{ $probD['total'] ?? 0 }}</td></tr>
                            <tr><td>Outstanding</td><td>{{ $probD['outstanding'] ?? 0 }}</td></tr>
                            <tr class="{{ $ldRowAttn($probD['pct'] ?? 0) }}"><td>Completion</td><td class="rate {{ $ldRateClass($probD['pct'] ?? 0) }}">{{ $probD['pct'] ?? 0 }}%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="m-empty">No probationary programs or staff on probation yet.</p>
        @endif
    </div>
</div>
