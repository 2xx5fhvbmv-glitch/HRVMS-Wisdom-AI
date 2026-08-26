{{-- Survey AI-insight detail modals. Included by the Survey HR dashboard;
     reads $surveyInsights. Opened by the "View Details" links via the shared
     frosted-modal system (partials/_wai_insight_modals.blade.php). Table-only
     body — title/issue/recommendation already live on the card and the
     shared Recommendation modal. --}}
@php
    $ptD = $surveyInsights['participation']['details'] ?? [];
    $acD = $surveyInsights['activity']['details']      ?? [];
    $seD = $surveyInsights['sentiment']['details']     ?? [];
    $hsD = $surveyInsights['hotspots']['details']      ?? [];
    $svRateClass = fn($rate) => $rate == 0 ? 'zero' : ($rate == 100 ? 'full' : '');
    $svRowAttn = fn($rate) => $rate == 0 ? 'attn' : '';
@endphp

<!-- Participation & Response Rate -->
<div class="wai-backdrop" id="surveyInsightParticipationModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $surveyInsights['participation']['title'] ?? 'Participation & Response Rate' }}</div>
        @if(!empty($ptD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Overall: {{ $ptD['responded'] ?? 0 }} of {{ $ptD['invited'] ?? 0 }} responded ({{ $ptD['overall'] ?? 0 }}%)</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Survey</th><th>Invited</th><th>Responded</th><th>Rate</th></tr></thead>
                    <tbody>
                        @foreach($ptD['rows'] as $row)
                            <tr class="{{ $svRowAttn($row['rate']) }}"><td>{{ $row['survey'] }}</td><td>{{ $row['invited'] }}</td><td>{{ $row['responded'] }}</td><td class="rate {{ $svRateClass($row['rate']) }}">{{ $row['rate'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No surveys with recipients yet.</p>
        @endif
    </div>
</div>

<!-- Survey Activity & Status -->
<div class="wai-backdrop" id="surveyInsightActivityModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $surveyInsights['activity']['title'] ?? 'Survey Activity & Status' }}</div>
        @if(!empty($acD))
            <div class="m-tablewrap">
                <div class="m-tcap">Active: {{ $acD['active'] ?? 0 }} &middot; Closing within 7 days: {{ $acD['closing_soon'] ?? 0 }}</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($acD['by_status'] ?? []) as $s => $c)
                        <tr><td>{{ $s }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">By cadence</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($acD['cadence'] ?? []) as $cad => $c)
                        <tr><td>{{ $cad }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Sentiment / Score Pulse -->
<div class="wai-backdrop" id="surveyInsightSentimentModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $surveyInsights['sentiment']['title'] ?? 'Sentiment / Score Pulse' }}</div>
        @if(!empty($seD) && !empty($seD['surveys']))
            <div class="m-tablewrap">
                <div class="m-tcap">Avg {{ $seD['avg'] ?? 0 }}/5 across {{ $seD['total'] ?? 0 }} responses &middot; {{ $seD['favourable'] ?? 0 }} favourable / {{ $seD['neutral'] ?? 0 }} neutral / {{ $seD['unfavourable'] ?? 0 }} unfavourable</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Survey</th><th>Avg</th><th>Responses</th></tr></thead>
                    <tbody>
                        @foreach($seD['surveys'] as $row)
                            <tr><td>{{ $row['survey'] }}</td><td>{{ $row['avg'] }}</td><td>{{ $row['responses'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No rating responses yet to analyse.</p>
        @endif
    </div>
</div>

<!-- Answer Hotspots -->
<div class="wai-backdrop" id="surveyInsightHotspotsModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $surveyInsights['hotspots']['title'] ?? 'Answer Hotspots' }}</div>
        @if(!empty($hsD['rows']))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Answer</th><th>Times chosen</th></tr></thead>
                    <tbody>
                        @foreach($hsD['rows'] as $row)
                            <tr><td>{{ $row['answer'] }}</td><td>{{ $row['count'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No choice responses yet to analyse.</p>
        @endif
    </div>
</div>
