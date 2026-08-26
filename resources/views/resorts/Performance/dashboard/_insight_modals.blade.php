{{-- AI-insight detail modals (completion / risk / throughput) for the
     performance dashboard. Reads $performanceInsights. Opened by the
     "View Details" links via the shared frosted-modal system
     (partials/_wai_insight_modals.blade.php). Table-only body —
     title/issue/recommendation already live on the card and the shared
     Recommendation modal. --}}
<style>
    /* Let the AI body + recommendation scroll inside the fixed insight card. */
    .card-wiINsightperformance { display: flex; flex-direction: column; }
    .card-wiINsightperformance .leaveUser-main { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
</style>
@php
    $pi          = $performanceInsights ?? [];
    $compDetails = $pi['completion']['details'] ?? [];
    $riskDetails = $pi['risk']['details'] ?? [];
    $thruDetails = $pi['throughput']['details'] ?? [];
@endphp

<!-- Completion: per-department appraisal progress -->
<div class="wai-backdrop" id="perfInsightCompletionModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $pi['completion']['title'] ?? 'Appraisal Completion Outlook' }}</div>
        @if(!empty($compDetails['departments']))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Department</th><th>Total</th><th>Completed</th><th>Pending</th><th>Rate</th></tr></thead>
                    <tbody>
                        @foreach($compDetails['departments'] as $d)
                            <tr class="{{ $d['pct'] == 0 ? 'attn' : '' }}"><td>{{ $d['name'] }}</td><td>{{ $d['total'] }}</td><td>{{ $d['completed'] }}</td><td>{{ $d['pending'] }}</td><td class="rate {{ $d['pct'] == 0 ? 'zero' : ($d['pct'] == 100 ? 'full' : '') }}">{{ $d['pct'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No appraisal data for the active cycle.</p>
        @endif
    </div>
</div>

<!-- Risk: employees on active PIP / PDP -->
<div class="wai-backdrop" id="perfInsightRiskModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $pi['risk']['title'] ?? 'Performance Risk & PIP Watch' }}</div>
        @if(!empty($riskDetails['employees']))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Employee</th><th>Department</th><th>Plan</th></tr></thead>
                    <tbody>
                        @foreach($riskDetails['employees'] as $emp)
                            <tr><td>{{ $emp['name'] }}</td><td>{{ $emp['dept'] }}</td><td>{{ $emp['type'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No employees on active PIP or PDP.</p>
        @endif
    </div>
</div>

<!-- Throughput: self vs manager review progress -->
<div class="wai-backdrop" id="perfInsightThroughputModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $pi['throughput']['title'] ?? 'Self vs Manager Review Throughput' }}</div>
        @if(!empty($thruDetails))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table"><tbody>
                    <tr><td>Total appraisals in cycle</td><td>{{ $thruDetails['total'] ?? 0 }}</td></tr>
                    <tr><td>Self-reviews completed</td><td>{{ $thruDetails['self_completed'] ?? 0 }} ({{ $thruDetails['self_pct'] ?? 0 }}%)</td></tr>
                    <tr><td>Manager reviews completed</td><td>{{ $thruDetails['manager_completed'] ?? 0 }} ({{ $thruDetails['manager_pct'] ?? 0 }}%)</td></tr>
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">Not enough review activity to analyse yet.</p>
        @endif
    </div>
</div>
