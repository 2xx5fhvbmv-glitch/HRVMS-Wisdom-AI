{{-- Accommodation AI-insight detail modals. Included by the Accommodation HR
     dashboard; reads $accommodationInsights. Opened by the "View Details" links. --}}
@php
    $mtD = $accommodationInsights['maintenance']['details'] ?? [];
    $ocD = $accommodationInsights['occupancy']['details']   ?? [];
    $hsD = $accommodationInsights['hotspots']['details']    ?? [];
    $dmD = $accommodationInsights['demand']['details']      ?? [];
@endphp

<!-- Maintenance SLA & Backlog -->
<div class="modal fade" id="accomInsightMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $accommodationInsights['maintenance']['title'] ?? 'Maintenance SLA & Backlog' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $accommodationInsights['maintenance']['body'] ?? '' }}</p>
                @if(!empty($accommodationInsights['maintenance']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $accommodationInsights['maintenance']['recommendation'] }}</p>@endif
                @if(!empty($mtD))
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By status</p>
                            <table class="table table-sm table-striped align-middle">
                                <tbody>
                                    @foreach(($mtD['by_status'] ?? []) as $st => $c)
                                        <tr><td>{{ $st }}</td><td class="text-end">{{ $c }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By priority</p>
                            <table class="table table-sm table-striped align-middle">
                                <tbody>
                                    @foreach(($mtD['priority'] ?? []) as $p => $c)
                                        <tr><td>{{ $p }}</td><td class="text-end">{{ $c }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p class="mb-0">Average resolution: {{ $mtD['avg_resolution_days'] ?? '—' }} days · Aging (&gt;7 days open): {{ $mtD['aging'] ?? 0 }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bed & Room Occupancy -->
<div class="modal fade" id="accomInsightOccupancyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $accommodationInsights['occupancy']['title'] ?? 'Bed & Room Occupancy' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $accommodationInsights['occupancy']['body'] ?? '' }}</p>
                @if(!empty($accommodationInsights['occupancy']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $accommodationInsights['occupancy']['recommendation'] }}</p>@endif
                @if(!empty($ocD))
                    <table class="table table-sm table-striped align-middle mb-3">
                        <thead><tr><th></th><th class="text-end">Occupied</th><th class="text-end">Vacant</th><th class="text-end">Occupancy</th></tr></thead>
                        <tbody>
                            <tr><td>Male</td><td class="text-end">{{ $ocD['male']['occupied'] ?? 0 }}</td><td class="text-end">{{ $ocD['male']['vacant'] ?? 0 }}</td><td class="text-end">{{ $ocD['male']['pct'] ?? 0 }}%</td></tr>
                            <tr><td>Female</td><td class="text-end">{{ $ocD['female']['occupied'] ?? 0 }}</td><td class="text-end">{{ $ocD['female']['vacant'] ?? 0 }}</td><td class="text-end">{{ $ocD['female']['pct'] ?? 0 }}%</td></tr>
                            <tr class="fw-bold"><td>Total</td><td class="text-end">{{ $ocD['occupied'] ?? 0 }}</td><td class="text-end">{{ ($ocD['total'] ?? 0) - ($ocD['occupied'] ?? 0) }}</td><td class="text-end">{{ $ocD['occupancy_pct'] ?? 0 }}%</td></tr>
                        </tbody>
                    </table>
                    @if(!empty($ocD['buildings']))
                        <p class="mb-1 fw-bold">By building (nearest capacity first)</p>
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Building</th><th class="text-end">Occupied</th><th class="text-end">Beds</th><th class="text-end">Occupancy</th></tr></thead>
                            <tbody>
                                @foreach($ocD['buildings'] as $b)
                                    <tr><td>{{ $b['building'] }}</td><td class="text-end">{{ $b['occupied'] }}</td><td class="text-end">{{ $b['total'] }}</td><td class="text-end">{{ $b['pct'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Hotspots -->
<div class="modal fade" id="accomInsightHotspotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $accommodationInsights['hotspots']['title'] ?? 'Maintenance Hotspots' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $accommodationInsights['hotspots']['body'] ?? '' }}</p>
                @if(!empty($accommodationInsights['hotspots']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $accommodationInsights['hotspots']['recommendation'] }}</p>@endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top buildings</p>
                        @if(!empty($hsD['buildings']))
                            <table class="table table-sm table-striped align-middle">
                                <thead><tr><th>Building</th><th class="text-end">Requests</th></tr></thead>
                                <tbody>
                                    @foreach($hsD['buildings'] as $row)
                                        <tr><td>{{ $row['building'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top items</p>
                        @if(!empty($hsD['items']))
                            <table class="table table-sm table-striped align-middle">
                                <thead><tr><th>Item</th><th class="text-end">Requests</th></tr></thead>
                                <tbody>
                                    @foreach($hsD['items'] as $row)
                                        <tr><td>{{ $row['item'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vacancy vs Demand -->
<div class="modal fade" id="accomInsightDemandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $accommodationInsights['demand']['title'] ?? 'Vacancy vs Demand' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $accommodationInsights['demand']['body'] ?? '' }}</p>
                @if(!empty($accommodationInsights['demand']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $accommodationInsights['demand']['recommendation'] }}</p>@endif
                @if(!empty($dmD))
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th></th><th class="text-end">Male</th><th class="text-end">Female</th></tr></thead>
                        <tbody>
                            <tr><td>Vacant beds</td><td class="text-end">{{ $dmD['vacant']['male'] ?? 0 }}</td><td class="text-end">{{ $dmD['vacant']['female'] ?? 0 }}</td></tr>
                            <tr><td>Incoming hires</td><td class="text-end">{{ $dmD['incoming']['male'] ?? 0 }}</td><td class="text-end">{{ $dmD['incoming']['female'] ?? 0 }}</td></tr>
                            <tr class="fw-bold"><td>Projected shortfall</td><td class="text-end">{{ $dmD['shortfall']['male'] ?? 0 }}</td><td class="text-end">{{ $dmD['shortfall']['female'] ?? 0 }}</td></tr>
                        </tbody>
                    </table>
                    <p class="mb-0 text-muted">Active staff currently unhoused: {{ $dmD['unhoused'] ?? 0 }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
