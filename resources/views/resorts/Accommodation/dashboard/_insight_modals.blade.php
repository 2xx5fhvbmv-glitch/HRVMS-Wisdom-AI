{{-- Accommodation AI-insight detail modals. Included by the Accommodation HR
     dashboard; reads $accommodationInsights. Opened by the "View Details"
     links via the shared frosted-modal system
     (partials/_wai_insight_modals.blade.php). Table-only body —
     title/issue/recommendation already live on the card and the shared
     Recommendation modal. --}}
@php
    $mtD = $accommodationInsights['maintenance']['details'] ?? [];
    $ocD = $accommodationInsights['occupancy']['details']   ?? [];
    $hsD = $accommodationInsights['hotspots']['details']    ?? [];
    $dmD = $accommodationInsights['demand']['details']      ?? [];
@endphp

<!-- Maintenance SLA & Backlog -->
<div class="wai-backdrop" id="accomInsightMaintenanceModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $accommodationInsights['maintenance']['title'] ?? 'Maintenance SLA & Backlog' }}</div>
        @if(!empty($mtD))
            <div class="m-tablewrap">
                <div class="m-tcap">By status</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($mtD['by_status'] ?? []) as $st => $c)
                        <tr><td>{{ $st }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">By priority &middot; avg resolution {{ $mtD['avg_resolution_days'] ?? '—' }} days &middot; aging (&gt;7 days) {{ $mtD['aging'] ?? 0 }}</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($mtD['priority'] ?? []) as $p => $c)
                        <tr><td>{{ $p }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Bed & Room Occupancy -->
<div class="wai-backdrop" id="accomInsightOccupancyModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $accommodationInsights['occupancy']['title'] ?? 'Bed & Room Occupancy' }}</div>
        @if(!empty($ocD))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th></th><th>Occupied</th><th>Vacant</th><th>Occupancy</th></tr></thead>
                    <tbody>
                        <tr><td>Male</td><td>{{ $ocD['male']['occupied'] ?? 0 }}</td><td>{{ $ocD['male']['vacant'] ?? 0 }}</td><td class="rate">{{ $ocD['male']['pct'] ?? 0 }}%</td></tr>
                        <tr><td>Female</td><td>{{ $ocD['female']['occupied'] ?? 0 }}</td><td>{{ $ocD['female']['vacant'] ?? 0 }}</td><td class="rate">{{ $ocD['female']['pct'] ?? 0 }}%</td></tr>
                        <tr><td>Total</td><td>{{ $ocD['occupied'] ?? 0 }}</td><td>{{ ($ocD['total'] ?? 0) - ($ocD['occupied'] ?? 0) }}</td><td class="rate">{{ $ocD['occupancy_pct'] ?? 0 }}%</td></tr>
                    </tbody>
                </table></div>
            </div>
            @if(!empty($ocD['buildings']))
                <div class="m-tablewrap">
                    <div class="m-tcap">By building (nearest capacity first)</div>
                    <div class="m-tscroll"><table class="m-table">
                        <thead><tr><th>Building</th><th>Occupied</th><th>Beds</th><th>Occupancy</th></tr></thead>
                        <tbody>
                            @foreach($ocD['buildings'] as $b)
                                <tr><td>{{ $b['building'] }}</td><td>{{ $b['occupied'] }}</td><td>{{ $b['total'] }}</td><td class="rate">{{ $b['pct'] }}%</td></tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
            @endif
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Maintenance Hotspots -->
<div class="wai-backdrop" id="accomInsightHotspotsModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $accommodationInsights['hotspots']['title'] ?? 'Maintenance Hotspots' }}</div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top buildings</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hsD['buildings'] ?? []) as $row)
                    <tr><td>{{ $row['building'] }}</td><td>{{ $row['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top items</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hsD['items'] ?? []) as $row)
                    <tr><td>{{ $row['item'] }}</td><td>{{ $row['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>

<!-- Vacancy vs Demand -->
<div class="wai-backdrop" id="accomInsightDemandModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $accommodationInsights['demand']['title'] ?? 'Vacancy vs Demand' }}</div>
        @if(!empty($dmD))
            <div class="m-tablewrap">
                <div class="m-tcap">Active staff currently unhoused: {{ $dmD['unhoused'] ?? 0 }}</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th></th><th>Male</th><th>Female</th></tr></thead>
                    <tbody>
                        <tr><td>Vacant beds</td><td>{{ $dmD['vacant']['male'] ?? 0 }}</td><td>{{ $dmD['vacant']['female'] ?? 0 }}</td></tr>
                        <tr><td>Incoming hires</td><td>{{ $dmD['incoming']['male'] ?? 0 }}</td><td>{{ $dmD['incoming']['female'] ?? 0 }}</td></tr>
                        <tr><td>Projected shortfall</td><td>{{ $dmD['shortfall']['male'] ?? 0 }}</td><td>{{ $dmD['shortfall']['female'] ?? 0 }}</td></tr>
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>
