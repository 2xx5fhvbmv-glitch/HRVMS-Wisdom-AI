{{-- Incident AI-insight detail modals. Included by the Incident HR dashboard;
     reads $incidentInsights. Opened by the "View Details" links. --}}
@php
    $volD = $incidentInsights['volume']['details']   ?? [];
    $hotD = $incidentInsights['hotspots']['details'] ?? [];
    $outD = $incidentInsights['outcomes']['details'] ?? [];
@endphp

<!-- Incident Volume & Trend -->
<div class="modal fade" id="incidentInsightVolumeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $incidentInsights['volume']['title'] ?? 'Incident Volume & Trend' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $incidentInsights['volume']['body'] ?? '' }}</p>
                @if(!empty($incidentInsights['volume']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $incidentInsights['volume']['recommendation'] }}</p>@endif
                @if(!empty($volD))
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By priority</p>
                            <table class="table table-sm table-striped align-middle">
                                <tbody>@foreach(($volD['priority'] ?? []) as $p => $c)<tr><td>{{ $p }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By severity</p>
                            <table class="table table-sm table-striped align-middle">
                                <tbody>@foreach(($volD['severity'] ?? []) as $s => $c)<tr><td>{{ $s }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody>
                            </table>
                        </div>
                    </div>
                    @if(!empty($volD['months']))
                        <p class="mb-1 fw-bold">Monthly volume</p>
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Month</th><th class="text-end">Incidents</th></tr></thead>
                            <tbody>@foreach($volD['months'] as $row)<tr><td>{{ $row['month'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach</tbody>
                        </table>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Category & Severity Hotspots -->
<div class="modal fade" id="incidentInsightHotspotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $incidentInsights['hotspots']['title'] ?? 'Category & Severity Hotspots' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $incidentInsights['hotspots']['body'] ?? '' }}</p>
                @if(!empty($incidentInsights['hotspots']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $incidentInsights['hotspots']['recommendation'] }}</p>@endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top categories</p>
                        @if(!empty($hotD['categories']))
                            <table class="table table-sm table-striped align-middle"><tbody>
                                @foreach($hotD['categories'] as $row)<tr><td>{{ $row['category'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach
                            </tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top subcategories</p>
                        @if(!empty($hotD['subcategories']))
                            <table class="table table-sm table-striped align-middle"><tbody>
                                @foreach($hotD['subcategories'] as $row)<tr><td>{{ $row['subcategory'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach
                            </tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Severity</p>
                        <table class="table table-sm table-striped align-middle"><tbody>
                            @foreach(($hotD['severity'] ?? []) as $s => $c)<tr><td>{{ $s }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach
                        </tbody></table>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top locations</p>
                        @if(!empty($hotD['locations']))
                            <table class="table table-sm table-striped align-middle"><tbody>
                                @foreach($hotD['locations'] as $row)<tr><td>{{ $row['location'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach
                            </tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Outcomes & Preventive Actions -->
<div class="modal fade" id="incidentInsightOutcomesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $incidentInsights['outcomes']['title'] ?? 'Outcomes & Preventive Actions' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $incidentInsights['outcomes']['body'] ?? '' }}</p>
                @if(!empty($incidentInsights['outcomes']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $incidentInsights['outcomes']['recommendation'] }}</p>@endif
                @if(!empty($outD))
                    <p class="mb-1">Preventive measures recorded on {{ $outD['preventive_recorded'] ?? 0 }} of {{ $outD['resolved'] ?? 0 }} resolved ({{ $outD['preventive_pct'] ?? 0 }}%); {{ $outD['preventive_missing'] ?? 0 }} missing.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By outcome</p>
                            @if(!empty($outD['outcomes']))
                                <table class="table table-sm table-striped align-middle"><tbody>
                                    @foreach($outD['outcomes'] as $row)<tr><td>{{ $row['outcome'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach
                                </tbody></table>
                            @else<p class="text-muted small">No data.</p>@endif
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By action taken</p>
                            @if(!empty($outD['actions']))
                                <table class="table table-sm table-striped align-middle"><tbody>
                                    @foreach($outD['actions'] as $row)<tr><td>{{ $row['action'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>@endforeach
                                </tbody></table>
                            @else<p class="text-muted small">No data.</p>@endif
                        </div>
                    </div>
                @else
                    <p class="mb-0">No resolved incidents yet to analyse.</p>
                @endif
            </div>
        </div>
    </div>
</div>
