{{-- AI-insight detail modals (completion / risk / throughput) for the
     performance dashboard. Reads $performanceInsights. Opened by the
     "View Details" links via data-bs-toggle="modal". --}}
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
<div class="modal fade" id="perfInsightCompletionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $pi['completion']['title'] ?? 'Appraisal Completion Outlook' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $pi['completion']['body'] ?? '' }}</p>
                @if(!empty($pi['completion']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $pi['completion']['recommendation'] }}</p>@endif
                @if(!empty($compDetails['departments']))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr><th>#</th><th>Department</th><th class="text-end">Total</th><th class="text-end">Completed</th><th class="text-end">Pending</th><th class="text-end">%</th></tr>
                            </thead>
                            <tbody>
                                @foreach($compDetails['departments'] as $i => $d)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $d['name'] }}</td>
                                        <td class="text-end">{{ $d['total'] }}</td>
                                        <td class="text-end">{{ $d['completed'] }}</td>
                                        <td class="text-end">{{ $d['pending'] }}</td>
                                        <td class="text-end">{{ $d['pct'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No appraisal data for the active cycle.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Risk: employees on active PIP / PDP -->
<div class="modal fade" id="perfInsightRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $pi['risk']['title'] ?? 'Performance Risk & PIP Watch' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $pi['risk']['body'] ?? '' }}</p>
                @if(!empty($pi['risk']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $pi['risk']['recommendation'] }}</p>@endif
                @if(!empty($riskDetails['employees']))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr><th>#</th><th>Employee</th><th>Department</th><th>Plan</th></tr>
                            </thead>
                            <tbody>
                                @foreach($riskDetails['employees'] as $i => $emp)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $emp['name'] }}</td>
                                        <td>{{ $emp['dept'] }}</td>
                                        <td>{{ $emp['type'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No employees on active PIP or PDP.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Throughput: self vs manager review progress -->
<div class="modal fade" id="perfInsightThroughputModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $pi['throughput']['title'] ?? 'Self vs Manager Review Throughput' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $pi['throughput']['body'] ?? '' }}</p>
                @if(!empty($pi['throughput']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $pi['throughput']['recommendation'] }}</p>@endif
                @if(!empty($thruDetails))
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr><th>Total appraisals in cycle</th><td class="text-end">{{ $thruDetails['total'] ?? 0 }}</td></tr>
                            <tr><th>Self-reviews completed</th><td class="text-end">{{ $thruDetails['self_completed'] ?? 0 }} ({{ $thruDetails['self_pct'] ?? 0 }}%)</td></tr>
                            <tr><th>Manager reviews completed</th><td class="text-end">{{ $thruDetails['manager_completed'] ?? 0 }} ({{ $thruDetails['manager_pct'] ?? 0 }}%)</td></tr>
                        </tbody>
                    </table>
                @else
                    <p class="mb-0">Not enough review activity to analyse yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
