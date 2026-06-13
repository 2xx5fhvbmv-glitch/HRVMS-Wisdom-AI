{{-- Talent-Acquisition AI-insight detail modals. Included by the TA HR dashboard;
     reads $taInsights. Opened by the "View Details" links. --}}
@php
    $rejD = $taInsights['rejection']['details']  ?? [];
    $funD = $taInsights['funnel']['details']     ?? [];
    $accD = $taInsights['acceptance']['details'] ?? [];
    $tthD = $taInsights['tth']['details']        ?? [];
    $demD = $taInsights['demand']['details']     ?? [];
@endphp

<!-- Top rejection reasons -->
<div class="modal fade" id="taInsightRejectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $taInsights['rejection']['title'] ?? 'Top Rejection Reasons' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $taInsights['rejection']['body'] ?? '' }}</p>
                @if(!empty($rejD['rows']))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Reason</th><th class="text-end">Count</th><th class="text-end">Share</th></tr></thead>
                            <tbody>
                                @foreach($rejD['rows'] as $row)
                                    <tr><td>{{ $row['reason'] }}</td><td class="text-end">{{ $row['count'] }}</td><td class="text-end">{{ $row['pct'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No rejection reasons recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hiring funnel & conversion -->
<div class="modal fade" id="taInsightFunnelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $taInsights['funnel']['title'] ?? 'Hiring Funnel & Conversion' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $taInsights['funnel']['body'] ?? '' }}</p>
                @if(!empty($funD['stages']))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Stage</th><th class="text-end">Candidates</th><th class="text-end">% of applied</th></tr></thead>
                            <tbody>
                                @foreach($funD['stages'] as $row)
                                    <tr><td>{{ $row['stage'] }}</td><td class="text-end">{{ $row['count'] }}</td><td class="text-end">{{ $row['pct'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Offer / contract acceptance -->
<div class="modal fade" id="taInsightAcceptanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $taInsights['acceptance']['title'] ?? 'Offer / Contract Acceptance' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $taInsights['acceptance']['body'] ?? '' }}</p>
                @if(!empty($accD['rows']))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Document</th><th class="text-end">Pending</th><th class="text-end">Accepted</th><th class="text-end">Rejected</th><th class="text-end">Total</th><th class="text-end">Accept rate</th></tr></thead>
                            <tbody>
                                @foreach($accD['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['type'] }}</td>
                                        <td class="text-end">{{ $row['sent'] }}</td>
                                        <td class="text-end">{{ $row['accepted'] }}</td>
                                        <td class="text-end">{{ $row['rejected'] }}</td>
                                        <td class="text-end">{{ $row['total'] }}</td>
                                        <td class="text-end">{{ $row['rate'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mb-0 text-muted">Pending = sent but not yet responded to.</p>
                @else
                    <p class="mb-0">No offers or contracts sent yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Time to hire -->
<div class="modal fade" id="taInsightTthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $taInsights['tth']['title'] ?? 'Time to Hire' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $taInsights['tth']['body'] ?? '' }}</p>
                @if(!empty($tthD['rows']))
                    <p class="mb-1 fw-bold">Recent hires (application → contract accepted)</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Candidate</th><th>Department</th><th class="text-end">Days</th></tr></thead>
                            <tbody>
                                @foreach($tthD['rows'] as $row)
                                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['dept'] }}</td><td class="text-end">{{ $row['days'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No completed hires yet to measure.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hiring demand by department -->
<div class="modal fade" id="taInsightDemandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $taInsights['demand']['title'] ?? 'Hiring Demand by Department' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $taInsights['demand']['body'] ?? '' }}</p>
                @if(!empty($demD['rows']))
                    <p class="mb-1">Open positions still to fill (total {{ $demD['total'] ?? 0 }}):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Department</th><th class="text-end">Open positions</th></tr></thead>
                            <tbody>
                                @foreach($demD['rows'] as $row)
                                    <tr><td>{{ $row['dept'] }}</td><td class="text-end">{{ $row['open'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No open vacancies right now.</p>
                @endif
            </div>
        </div>
    </div>
</div>
