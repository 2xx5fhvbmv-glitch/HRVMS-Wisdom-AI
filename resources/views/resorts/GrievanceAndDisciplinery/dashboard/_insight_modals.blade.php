{{-- Grievance & Disciplinary AI-insight detail modals. Included by the HR
     dashboard; reads $grievanceInsights. Opened by the "View Details" links. --}}
@php
    $voD = $grievanceInsights['volume']['details']   ?? [];
    $slD = $grievanceInsights['sla']['details']      ?? [];
    $hsD = $grievanceInsights['hotspots']['details'] ?? [];
    $ouD = $grievanceInsights['outcomes']['details'] ?? [];
@endphp

<!-- Case Volume & Status -->
<div class="modal fade" id="grievInsightVolumeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $grievanceInsights['volume']['title'] ?? 'Case Volume & Status' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $grievanceInsights['volume']['body'] ?? '' }}</p>
                @if(!empty($grievanceInsights['volume']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $grievanceInsights['volume']['recommendation'] }}</p>@endif
                @if(!empty($voD))
                    <p class="mb-1">{{ $voD['grievance'] ?? 0 }} grievance · {{ $voD['disciplinary'] ?? 0 }} disciplinary · {{ $voD['resolution_rate'] ?? 0 }}% resolution</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Grievance status</p>
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach(($voD['grievance_status'] ?? []) as $s => $c)<tr><td>{{ ucfirst(str_replace('_',' ',$s)) }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody></table>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">Disciplinary status</p>
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach(($voD['disciplinary_status'] ?? []) as $s => $c)<tr><td>{{ ucfirst(str_replace('_',' ',$s)) }}</td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody></table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- SLA Compliance & Overdue -->
<div class="modal fade" id="grievInsightSlaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $grievanceInsights['sla']['title'] ?? 'SLA Compliance & Overdue' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $grievanceInsights['sla']['body'] ?? '' }}</p>
                @if(!empty($grievanceInsights['sla']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $grievanceInsights['sla']['recommendation'] }}</p>@endif
                @if(!empty($slD))
                    <table class="table table-sm table-striped align-middle">
                        <tbody>
                            <tr class="text-danger"><td>Past SLA deadline</td><td class="text-end">{{ $slD['overdue'] ?? 0 }}</td></tr>
                            <tr><td>Due within 3 days</td><td class="text-end">{{ $slD['due_soon'] ?? 0 }}</td></tr>
                            <tr><td>Open cases</td><td class="text-end">{{ $slD['open'] ?? 0 }}</td></tr>
                            <tr class="fw-bold"><td>Avg resolution (days)</td><td class="text-end">{{ $slD['avg_resolution_days'] ?? '—' }}</td></tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Category & Offense Hotspots -->
<div class="modal fade" id="grievInsightHotspotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $grievanceInsights['hotspots']['title'] ?? 'Category & Offense Hotspots' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $grievanceInsights['hotspots']['body'] ?? '' }}</p>
                @if(!empty($grievanceInsights['hotspots']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $grievanceInsights['hotspots']['recommendation'] }}</p>@endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top grievance categories</p>
                        @if(!empty($hsD['grievance_categories']))
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach($hsD['grievance_categories'] as $r)<tr><td>{{ $r['category'] }}</td><td class="text-end">{{ $r['count'] }}</td></tr>@endforeach</tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 fw-bold">Top disciplinary offenses</p>
                        @if(!empty($hsD['offenses']))
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach($hsD['offenses'] as $r)<tr><td>{{ $r['offense'] }}</td><td class="text-end">{{ $r['count'] }}</td></tr>@endforeach</tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                    <div class="col-md-12">
                        <p class="mb-1 fw-bold">Disciplinary severity mix</p>
                        @if(!empty($hsD['severity']))
                            <table class="table table-sm table-striped align-middle"><tbody>@foreach($hsD['severity'] as $r)<tr><td>{{ $r['severity'] }}</td><td class="text-end">{{ $r['count'] }}</td></tr>@endforeach</tbody></table>
                        @else<p class="text-muted small">No data.</p>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disciplinary Outcomes & Severity -->
<div class="modal fade" id="grievInsightOutcomesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $grievanceInsights['outcomes']['title'] ?? 'Disciplinary Outcomes & Severity' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $grievanceInsights['outcomes']['body'] ?? '' }}</p>
                @if(!empty($grievanceInsights['outcomes']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $grievanceInsights['outcomes']['recommendation'] }}</p>@endif
                @if(!empty($ouD))
                    <p class="mb-1">{{ $ouD['total'] ?? 0 }} disciplinary cases · {{ $ouD['repeat_offenders'] ?? 0 }} repeat offenders</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By action taken</p>
                            @if(!empty($ouD['actions']))
                                <table class="table table-sm table-striped align-middle"><tbody>@foreach($ouD['actions'] as $r)<tr><td>{{ $r['action'] }}</td><td class="text-end">{{ $r['count'] }}</td></tr>@endforeach</tbody></table>
                            @else<p class="text-muted small">No data.</p>@endif
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 fw-bold">By severity</p>
                            @if(!empty($ouD['severity']))
                                <table class="table table-sm table-striped align-middle"><tbody>@foreach($ouD['severity'] as $r)<tr><td>{{ $r['severity'] }}</td><td class="text-end">{{ $r['count'] }}</td></tr>@endforeach</tbody></table>
                            @else<p class="text-muted small">No data.</p>@endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
