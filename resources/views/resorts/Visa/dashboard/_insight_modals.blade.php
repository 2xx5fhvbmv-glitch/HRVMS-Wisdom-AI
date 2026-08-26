{{-- Visa AI-insight detail modals. Included by the Visa HR dashboard;
     reads $visaInsights. Opened by the "View Details" links via the shared
     frosted-modal system (partials/_wai_insight_modals.blade.php). Table-only
     body — title/issue/recommendation already live on the card and the
     shared Recommendation modal. --}}
@php
    $pmD = $visaInsights['payments']['details']  ?? [];
    $liD = $visaInsights['liability']['details'] ?? [];
    $reD = $visaInsights['renewal']['details']   ?? [];
    $exD = $visaInsights['expiry']['details']    ?? [];
@endphp

<!-- Quota & Work-Permit Payments -->
<div class="wai-backdrop" id="visaInsightPaymentsModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $visaInsights['payments']['title'] ?? 'Quota & Work-Permit Payments' }}</div>
        @if(!empty($pmD['by_kind']))
            <div class="m-tablewrap">
                <div class="m-tcap">Paid {{ $pmD['paid'] ?? 0 }} &middot; Unpaid {{ $pmD['unpaid'] ?? 0 }} &middot; Overdue {{ $pmD['overdue'] ?? 0 }} &middot; Outstanding ~{{ number_format($pmD['outstanding'] ?? 0) }}</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Type</th><th>Total</th><th>Unpaid</th><th>Outstanding</th></tr></thead>
                    <tbody>
                        @foreach($pmD['by_kind'] as $kind => $g)
                            <tr><td>{{ $kind }}</td><td>{{ $g['total'] }}</td><td>{{ $g['unpaid'] }}</td><td>{{ number_format($g['outstanding']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Expat Liability by Nationality -->
<div class="wai-backdrop" id="visaInsightLiabilityModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $visaInsights['liability']['title'] ?? 'Expat Liability by Nationality' }}</div>
        @if(!empty($liD['rows']))
            <div class="m-tablewrap">
                <div class="m-tcap">Total liability ~{{ number_format($liD['total_liability'] ?? 0) }} across {{ $liD['total_heads'] ?? 0 }} expats</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Nationality</th><th>Headcount</th><th>Rate</th><th>Liability</th></tr></thead>
                    <tbody>
                        @foreach($liD['rows'] as $row)
                            <tr><td>{{ $row['nationality'] }}</td><td>{{ $row['headcount'] }}</td><td>{{ number_format($row['rate']) }}</td><td>{{ number_format($row['liability']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No nationality deposit rates configured yet.</p>
        @endif
    </div>
</div>

<!-- Renewal Backlog -->
<div class="wai-backdrop" id="visaInsightRenewalModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $visaInsights['renewal']['title'] ?? 'Renewal Backlog' }}</div>
        @if(!empty($reD))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table"><tbody>
                    <tr><td>Visa renewals</td><td>{{ $reD['visa_total'] ?? 0 }}</td></tr>
                    <tr><td>Pending</td><td>{{ $reD['visa_pending'] ?? 0 }}</td></tr>
                    <tr><td>Paid</td><td>{{ $reD['visa_paid'] ?? 0 }}</td></tr>
                    <tr><td>Due within 30 days</td><td>{{ $reD['due_soon'] ?? 0 }}</td></tr>
                    <tr><td>Work-permit payments pending</td><td>{{ $reD['work_permit_pending'] ?? 0 }}</td></tr>
                    <tr><td>Quota payments pending</td><td>{{ $reD['quota_pending'] ?? 0 }}</td></tr>
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Visa & Permit Expiry Pipeline -->
<div class="wai-backdrop" id="visaInsightExpiryModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $visaInsights['expiry']['title'] ?? 'Visa & Permit Expiry Pipeline' }}</div>
        @if(!empty($exD))
            <div class="m-tablewrap">
                <div class="m-tscroll"><table class="m-table"><tbody>
                    <tr class="attn"><td>Already expired</td><td class="rate zero">{{ $exD['expired'] ?? 0 }}</td></tr>
                    <tr><td>Expiring within 30 days</td><td>{{ $exD['in_30'] ?? 0 }}</td></tr>
                    <tr><td>31&ndash;60 days</td><td>{{ $exD['in_60'] ?? 0 }}</td></tr>
                    <tr><td>61&ndash;90 days</td><td>{{ $exD['in_90'] ?? 0 }}</td></tr>
                </tbody></table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>
