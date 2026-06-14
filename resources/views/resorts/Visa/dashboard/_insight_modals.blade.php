{{-- Visa AI-insight detail modals. Included by the Visa HR dashboard;
     reads $visaInsights. Opened by the "View Details" links. --}}
@php
    $pmD = $visaInsights['payments']['details']  ?? [];
    $liD = $visaInsights['liability']['details'] ?? [];
    $reD = $visaInsights['renewal']['details']   ?? [];
    $exD = $visaInsights['expiry']['details']    ?? [];
@endphp

<!-- Quota & Work-Permit Payments -->
<div class="modal fade" id="visaInsightPaymentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $visaInsights['payments']['title'] ?? 'Quota & Work-Permit Payments' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $visaInsights['payments']['body'] ?? '' }}</p>
                @if(!empty($visaInsights['payments']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $visaInsights['payments']['recommendation'] }}</p>@endif
                @if(!empty($pmD))
                    <p class="mb-1">Paid {{ $pmD['paid'] ?? 0 }} · Unpaid {{ $pmD['unpaid'] ?? 0 }} · Overdue {{ $pmD['overdue'] ?? 0 }} · Outstanding ~{{ number_format($pmD['outstanding'] ?? 0) }}</p>
                    @if(!empty($pmD['by_kind']))
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Type</th><th class="text-end">Total</th><th class="text-end">Unpaid</th><th class="text-end">Outstanding</th></tr></thead>
                            <tbody>@foreach($pmD['by_kind'] as $kind => $g)<tr><td>{{ $kind }}</td><td class="text-end">{{ $g['total'] }}</td><td class="text-end">{{ $g['unpaid'] }}</td><td class="text-end">{{ number_format($g['outstanding']) }}</td></tr>@endforeach</tbody>
                        </table>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Expat Liability by Nationality -->
<div class="modal fade" id="visaInsightLiabilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $visaInsights['liability']['title'] ?? 'Expat Liability by Nationality' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $visaInsights['liability']['body'] ?? '' }}</p>
                @if(!empty($visaInsights['liability']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $visaInsights['liability']['recommendation'] }}</p>@endif
                @if(!empty($liD['rows']))
                    <p class="mb-1">Total liability ~{{ number_format($liD['total_liability'] ?? 0) }} across {{ $liD['total_heads'] ?? 0 }} expats.</p>
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th>Nationality</th><th class="text-end">Headcount</th><th class="text-end">Rate</th><th class="text-end">Liability</th></tr></thead>
                        <tbody>@foreach($liD['rows'] as $row)<tr><td>{{ $row['nationality'] }}</td><td class="text-end">{{ $row['headcount'] }}</td><td class="text-end">{{ number_format($row['rate']) }}</td><td class="text-end">{{ number_format($row['liability']) }}</td></tr>@endforeach</tbody>
                    </table>
                @else<p class="mb-0">No nationality deposit rates configured yet.</p>@endif
            </div>
        </div>
    </div>
</div>

<!-- Renewal Backlog -->
<div class="modal fade" id="visaInsightRenewalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $visaInsights['renewal']['title'] ?? 'Renewal Backlog' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $visaInsights['renewal']['body'] ?? '' }}</p>
                @if(!empty($visaInsights['renewal']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $visaInsights['renewal']['recommendation'] }}</p>@endif
                @if(!empty($reD))
                    <table class="table table-sm table-striped align-middle">
                        <tbody>
                            <tr><td>Visa renewals</td><td class="text-end">{{ $reD['visa_total'] ?? 0 }}</td></tr>
                            <tr><td>&nbsp;&nbsp;Pending</td><td class="text-end">{{ $reD['visa_pending'] ?? 0 }}</td></tr>
                            <tr><td>&nbsp;&nbsp;Paid</td><td class="text-end">{{ $reD['visa_paid'] ?? 0 }}</td></tr>
                            <tr><td>Due within 30 days</td><td class="text-end">{{ $reD['due_soon'] ?? 0 }}</td></tr>
                            <tr><td>Work-permit payments pending</td><td class="text-end">{{ $reD['work_permit_pending'] ?? 0 }}</td></tr>
                            <tr><td>Quota payments pending</td><td class="text-end">{{ $reD['quota_pending'] ?? 0 }}</td></tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Visa & Permit Expiry Pipeline -->
<div class="modal fade" id="visaInsightExpiryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $visaInsights['expiry']['title'] ?? 'Visa & Permit Expiry Pipeline' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $visaInsights['expiry']['body'] ?? '' }}</p>
                @if(!empty($visaInsights['expiry']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $visaInsights['expiry']['recommendation'] }}</p>@endif
                @if(!empty($exD))
                    <table class="table table-sm table-striped align-middle">
                        <tbody>
                            <tr class="text-danger"><td>Already expired</td><td class="text-end">{{ $exD['expired'] ?? 0 }}</td></tr>
                            <tr><td>Expiring within 30 days</td><td class="text-end">{{ $exD['in_30'] ?? 0 }}</td></tr>
                            <tr><td>31–60 days</td><td class="text-end">{{ $exD['in_60'] ?? 0 }}</td></tr>
                            <tr><td>61–90 days</td><td class="text-end">{{ $exD['in_90'] ?? 0 }}</td></tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
