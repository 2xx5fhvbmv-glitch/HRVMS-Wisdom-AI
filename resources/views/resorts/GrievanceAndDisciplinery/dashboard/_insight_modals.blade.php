{{-- Grievance & Disciplinary AI-insight detail modals. Included by the HR
     dashboard; reads $grievanceInsights. Opened by the "View Details" links
     via the shared frosted-modal system (partials/_wai_insight_modals.blade.php).
     Table-only body — title/issue/recommendation already live on the WAI
     Insights card and the shared Recommendation modal. --}}
@php
    $voD = $grievanceInsights['volume']['details']   ?? [];
    $slD = $grievanceInsights['sla']['details']      ?? [];
    $hsD = $grievanceInsights['hotspots']['details'] ?? [];
    $ouD = $grievanceInsights['outcomes']['details'] ?? [];
@endphp

<!-- Case Volume & Status -->
<div class="wai-backdrop" id="grievInsightVolumeModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $grievanceInsights['volume']['title'] ?? 'Case Volume & Status' }}</div>
        @if(!empty($voD))
            <div class="m-tablewrap">
                <div class="m-tcap">Grievance status &middot; {{ $voD['grievance'] ?? 0 }} cases</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($voD['grievance_status'] ?? []) as $s => $c)
                        <tr><td>{{ ucfirst(str_replace('_',' ',$s)) }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">Disciplinary status &middot; {{ $voD['disciplinary'] ?? 0 }} cases &middot; {{ $voD['resolution_rate'] ?? 0 }}% resolution</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @foreach(($voD['disciplinary_status'] ?? []) as $s => $c)
                        <tr><td>{{ ucfirst(str_replace('_',' ',$s)) }}</td><td>{{ $c }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- SLA Compliance & Overdue -->
<div class="wai-backdrop" id="grievInsightSlaModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $grievanceInsights['sla']['title'] ?? 'SLA Compliance & Overdue' }}</div>
        @if(!empty($slD))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table"><tbody>
                    <tr class="attn"><td>Past SLA deadline</td><td class="rate zero">{{ $slD['overdue'] ?? 0 }}</td></tr>
                    <tr><td>Due within 3 days</td><td>{{ $slD['due_soon'] ?? 0 }}</td></tr>
                    <tr><td>Open cases</td><td>{{ $slD['open'] ?? 0 }}</td></tr>
                    <tr><td>Avg resolution (days)</td><td>{{ $slD['avg_resolution_days'] ?? '—' }}</td></tr>
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Category & Offense Hotspots -->
<div class="wai-backdrop" id="grievInsightHotspotsModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $grievanceInsights['hotspots']['title'] ?? 'Category & Offense Hotspots' }}</div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top grievance categories</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hsD['grievance_categories'] ?? []) as $r)
                    <tr><td>{{ $r['category'] }}</td><td>{{ $r['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Top disciplinary offenses</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hsD['offenses'] ?? []) as $r)
                    <tr><td>{{ $r['offense'] }}</td><td>{{ $r['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
        <div class="m-tablewrap">
            <div class="m-tcap">Disciplinary severity mix</div>
            <div class="m-tscroll"><table class="m-table"><tbody>
                @forelse(($hsD['severity'] ?? []) as $r)
                    <tr><td>{{ $r['severity'] }}</td><td>{{ $r['count'] }}</td></tr>
                @empty
                    <tr><td colspan="2" class="m-empty">No data.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>

<!-- Disciplinary Outcomes & Severity -->
<div class="wai-backdrop" id="grievInsightOutcomesModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $grievanceInsights['outcomes']['title'] ?? 'Disciplinary Outcomes & Severity' }}</div>
        @if(!empty($ouD))
            <div class="m-tablewrap">
                <div class="m-tcap">By action taken &middot; {{ $ouD['total'] ?? 0 }} cases &middot; {{ $ouD['repeat_offenders'] ?? 0 }} repeat offenders</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @forelse(($ouD['actions'] ?? []) as $r)
                        <tr><td>{{ $r['action'] }}</td><td>{{ $r['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="m-empty">No data.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
            <div class="m-tablewrap">
                <div class="m-tcap">By severity</div>
                <div class="m-tscroll"><table class="m-table"><tbody>
                    @forelse(($ouD['severity'] ?? []) as $r)
                        <tr><td>{{ $r['severity'] }}</td><td>{{ $r['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="m-empty">No data.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>
