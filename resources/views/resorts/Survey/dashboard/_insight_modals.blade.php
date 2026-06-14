{{-- Survey AI-insight detail modals. Included by the Survey HR dashboard;
     reads $surveyInsights. Opened by the "View Details" links. --}}
@php
    $ptD = $surveyInsights['participation']['details'] ?? [];
    $acD = $surveyInsights['activity']['details']      ?? [];
    $seD = $surveyInsights['sentiment']['details']     ?? [];
    $hsD = $surveyInsights['hotspots']['details']      ?? [];
@endphp

<!-- Participation & Response Rate -->
<div class="modal fade" id="surveyInsightParticipationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $surveyInsights['participation']['title'] ?? 'Participation & Response Rate' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $surveyInsights['participation']['body'] ?? '' }}</p>
                @if(!empty($surveyInsights['participation']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $surveyInsights['participation']['recommendation'] }}</p>@endif
                @if(!empty($ptD['rows']))
                    <p class="mb-1">Overall: {{ $ptD['responded'] ?? 0 }} of {{ $ptD['invited'] ?? 0 }} responded ({{ $ptD['overall'] ?? 0 }}%).</p>
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th>Survey</th><th class="text-end">Invited</th><th class="text-end">Responded</th><th class="text-end">Rate</th></tr></thead>
                        <tbody>@foreach($ptD['rows'] as $row)<tr><td>{{ $row['survey'] }}</td><td class="text-end">{{ $row['invited'] }}</td><td class="text-end">{{ $row['responded'] }}</td><td class="text-end">{{ $row['rate'] }}%</td></tr>@endforeach</tbody>
                    </table>
                @else<p class="mb-0">No surveys with recipients yet.</p>@endif
            </div>
        </div>
    </div>
</div>

<!-- Survey Activity & Status -->
<div class="modal fade" id="surveyInsightActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $surveyInsights['activity']['title'] ?? 'Survey Activity & Status' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $surveyInsights['activity']['body'] ?? '' }}</p>
                @if(!empty($surveyInsights['activity']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $surveyInsights['activity']['recommendation'] }}</p>@endif
                @if(!empty($acD))
                    <p class="mb-1">Active: {{ $acD['active'] ?? 0 }} · Closing within 7 days: {{ $acD['closing_soon'] ?? 0 }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By status</p>
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach(($acD['by_status'] ?? []) as $s => $c)<tr><td>{{ $s }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody></table>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By cadence</p>
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach(($acD['cadence'] ?? []) as $cad => $c)<tr><td>{{ $cad }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody></table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Sentiment / Score Pulse -->
<div class="modal fade" id="surveyInsightSentimentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $surveyInsights['sentiment']['title'] ?? 'Sentiment / Score Pulse' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $surveyInsights['sentiment']['body'] ?? '' }}</p>
                @if(!empty($surveyInsights['sentiment']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $surveyInsights['sentiment']['recommendation'] }}</p>@endif
                @if(!empty($seD))
                    <p class="mb-1">Avg {{ $seD['avg'] ?? 0 }}/5 across {{ $seD['total'] ?? 0 }} responses — {{ $seD['favourable'] ?? 0 }} favourable / {{ $seD['neutral'] ?? 0 }} neutral / {{ $seD['unfavourable'] ?? 0 }} unfavourable.</p>
                    @if(!empty($seD['surveys']))
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Survey</th><th class="text-end">Avg</th><th class="text-end">Responses</th></tr></thead>
                            <tbody>@foreach($seD['surveys'] as $row)<tr><td>{{ $row['survey'] }}</td><td class="text-end">{{ $row['avg'] }}</td><td class="text-end">{{ $row['responses'] }}</td></tr>@endforeach</tbody>
                        </table>
                    @endif
                @else<p class="mb-0">No rating responses yet to analyse.</p>@endif
            </div>
        </div>
    </div>
</div>

<!-- Answer Hotspots -->
<div class="modal fade" id="surveyInsightHotspotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $surveyInsights['hotspots']['title'] ?? 'Answer Hotspots' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $surveyInsights['hotspots']['body'] ?? '' }}</p>
                @if(!empty($surveyInsights['hotspots']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $surveyInsights['hotspots']['recommendation'] }}</p>@endif
                @if(!empty($hsD['rows']))
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th>Answer</th><th class="text-end">Times chosen</th></tr></thead>
                        <tbody>@foreach($hsD['rows'] as $row)<tr><td>{{ $row['answer'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach</tbody>
                    </table>
                @else<p class="mb-0">No choice responses yet to analyse.</p>@endif
            </div>
        </div>
    </div>
</div>
