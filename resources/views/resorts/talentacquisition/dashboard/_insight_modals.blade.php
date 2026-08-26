{{-- Talent-Acquisition AI-insight detail modals. Included by the TA HR
     dashboard; reads $taInsights. Opened by the "View Details" links via
     the shared frosted-modal system (partials/_wai_insight_modals.blade.php).
     Table-only body — title/issue already live on the card (this module
     doesn't currently populate a recommendation). --}}
@php
    $rejD = $taInsights['rejection']['details']  ?? [];
    $funD = $taInsights['funnel']['details']     ?? [];
    $accD = $taInsights['acceptance']['details'] ?? [];
    $tthD = $taInsights['tth']['details']        ?? [];
    $demD = $taInsights['demand']['details']     ?? [];
@endphp

<!-- Top rejection reasons -->
<div class="wai-backdrop" id="taInsightRejectionModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $taInsights['rejection']['title'] ?? 'Top Rejection Reasons' }}</div>
        @if(!empty($rejD['rows']))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Reason</th><th>Count</th><th>Share</th></tr></thead>
                    <tbody>
                        @foreach($rejD['rows'] as $row)
                            <tr><td>{{ $row['reason'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['pct'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No rejection reasons recorded.</p>
        @endif
    </div>
</div>

<!-- Hiring funnel & conversion -->
<div class="wai-backdrop" id="taInsightFunnelModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $taInsights['funnel']['title'] ?? 'Hiring Funnel & Conversion' }}</div>
        @if(!empty($funD['stages']))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Stage</th><th>Candidates</th><th>% of applied</th></tr></thead>
                    <tbody>
                        @foreach($funD['stages'] as $row)
                            <tr><td>{{ $row['stage'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['pct'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Offer / contract acceptance -->
<div class="wai-backdrop" id="taInsightAcceptanceModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $taInsights['acceptance']['title'] ?? 'Offer / Contract Acceptance' }}</div>
        @if(!empty($accD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Pending = sent but not yet responded to</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Document</th><th>Pending</th><th>Accepted</th><th>Rejected</th><th>Total</th><th>Accept rate</th></tr></thead>
                    <tbody>
                        @foreach($accD['rows'] as $row)
                            <tr class="{{ $row['rate'] == 0 ? 'attn' : '' }}">
                                <td>{{ $row['type'] }}</td>
                                <td>{{ $row['sent'] }}</td>
                                <td>{{ $row['accepted'] }}</td>
                                <td>{{ $row['rejected'] }}</td>
                                <td>{{ $row['total'] }}</td>
                                <td class="rate {{ $row['rate'] == 0 ? 'zero' : ($row['rate'] == 100 ? 'full' : '') }}">{{ $row['rate'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No offers or contracts sent yet.</p>
        @endif
    </div>
</div>

<!-- Time to hire -->
<div class="wai-backdrop" id="taInsightTthModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $taInsights['tth']['title'] ?? 'Time to Hire' }}</div>
        @if(!empty($tthD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Recent hires (application &rarr; contract accepted)</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Candidate</th><th>Department</th><th>Days</th></tr></thead>
                    <tbody>
                        @foreach($tthD['rows'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['dept'] }}</td><td>{{ $row['days'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No completed hires yet to measure.</p>
        @endif
    </div>
</div>

<!-- Hiring demand by department -->
<div class="wai-backdrop" id="taInsightDemandModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $taInsights['demand']['title'] ?? 'Hiring Demand by Department' }}</div>
        @if(!empty($demD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Open positions still to fill (total {{ $demD['total'] ?? 0 }})</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Department</th><th>Open positions</th></tr></thead>
                    <tbody>
                        @foreach($demD['rows'] as $row)
                            <tr><td>{{ $row['dept'] }}</td><td>{{ $row['open'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No open vacancies right now.</p>
        @endif
    </div>
</div>
