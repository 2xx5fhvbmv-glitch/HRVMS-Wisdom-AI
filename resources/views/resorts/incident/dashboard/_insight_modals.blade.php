{{-- Incident AI-insight detail modals. Included by the Incident HR dashboard;
     reads $incidentInsights. Opened by the "View Details" links via the
     shared frosted-modal system (partials/_wai_insight_modals.blade.php).
     Table-only body — title/issue/recommendation already live on the card
     and the shared Recommendation modal. --}}
@php
    $volD = $incidentInsights['volume']['details']   ?? [];
    $hotD = $incidentInsights['hotspots']['details'] ?? [];
    $outD = $incidentInsights['outcomes']['details'] ?? [];
@endphp

<!-- Incident Volume & Trend -->
<div class="wai-backdrop" id="incidentInsightVolumeModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $incidentInsights['volume']['title'] ?? 'Incident Volume & Trend' }}</div>
        @if(!empty($volD))
            <div class="m-tablewrap">
                <div class="m-tcap">By priority</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($volD['priority'] ?? []) as $p => $c)
                        <tr><td>{{ $p }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">By severity</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($volD['severity'] ?? []) as $s => $c)
                        <tr><td>{{ $s }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
            @if(!empty($volD['months']))
                <div class="m-tablewrap">
                    <div class="m-tcap">Monthly volume</div>
                    <div class="m-tscroll"><table class="m-table">
                        <thead><tr><th>Month</th><th>Incidents</th></tr></thead>
                        <tbody>
                            @foreach($volD['months'] as $row)
                                <tr><td>{{ $row['month'] }}</td><td>{{ $row['count'] }}</td></tr>
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

<!-- Category & Severity Hotspots -->
<div class="wai-backdrop" id="incidentInsightHotspotsModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $incidentInsights['hotspots']['title'] ?? 'Category & Severity Hotspots' }}</div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top categories</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hotD['categories'] ?? []) as $row)
                    <tr><td>{{ $row['category'] }}</td><td>{{ $row['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top subcategories</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hotD['subcategories'] ?? []) as $row)
                    <tr><td>{{ $row['subcategory'] }}</td><td>{{ $row['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Severity</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @foreach(($hotD['severity'] ?? []) as $s => $c)
                    <tr><td>{{ $s }}</td><td>{{ $c }}</td></tr>
                @endforeach
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top locations</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hotD['locations'] ?? []) as $row)
                    <tr><td>{{ $row['location'] }}</td><td>{{ $row['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>

<!-- Outcomes & Preventive Actions -->
<div class="wai-backdrop" id="incidentInsightOutcomesModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $incidentInsights['outcomes']['title'] ?? 'Outcomes & Preventive Actions' }}</div>
        @if(!empty($outD))
            <div class="m-tablewrap">
                <div class="m-tcap">By outcome &middot; preventive measures on {{ $outD['preventive_recorded'] ?? 0 }} of {{ $outD['resolved'] ?? 0 }} resolved ({{ $outD['preventive_pct'] ?? 0 }}%); {{ $outD['preventive_missing'] ?? 0 }} missing</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @forelse(($outD['outcomes'] ?? []) as $row)
                        <tr><td>{{ $row['outcome'] }}</td><td>{{ $row['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="m-empty">No data.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">By action taken</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @forelse(($outD['actions'] ?? []) as $row)
                        <tr><td>{{ $row['action'] }}</td><td>{{ $row['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="m-empty">No data.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No resolved incidents yet to analyse.</p>
        @endif
    </div>
</div>
