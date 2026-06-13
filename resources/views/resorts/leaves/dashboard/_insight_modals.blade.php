{{-- AI-insight detail modals (occupancy / peak / behaviour).
     Included by the HR, Admin and HOD leave dashboards; reads $leaveInsights.
     Opened by the "View Details" links via data-bs-toggle="modal". --}}
<style>
    /* Keep the WAI Insight's card at its fixed height and let the insight
       list scroll inside it (the AI body + recommendation can overflow). */
    .card-wiINsight {
        display: flex;
        flex-direction: column;
    }
    .card-wiINsight .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
</style>
@php
    $occDetails  = $leaveInsights['occupancy']['details'] ?? [];
    $peakDetails = $leaveInsights['peak']['details'] ?? [];
    $behDetails  = $leaveInsights['behavior']['details'] ?? [];
    $monthNames  = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
    $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 1), '0'), '.'); };
@endphp

<!-- Occupancy: employees with unused annual leave -->
<div class="modal fade" id="leaveInsightOccupancyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $leaveInsights['occupancy']['title'] ?? 'Occupancy & Leave Window' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $leaveInsights['occupancy']['body'] ?? '' }}</p>
                @if(!empty($leaveInsights["occupancy"]["recommendation"]))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $leaveInsights["occupancy"]["recommendation"] }}</p>@endif
                @if(!empty($occDetails['employees']))
                    <p class="mb-2">Employees with unused annual leave
                        @if(!empty($occDetails['entitlement'])) (entitlement {{ $fmt($occDetails['entitlement']) }} days) @endif:</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr><th>#</th><th>Employee</th><th>Department</th><th class="text-end">Used</th><th class="text-end">Remaining</th></tr>
                            </thead>
                            <tbody>
                                @foreach($occDetails['employees'] as $i => $emp)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $emp['name'] }}</td>
                                        <td>{{ $emp['dept'] }}</td>
                                        <td class="text-end">{{ $fmt($emp['used']) }}</td>
                                        <td class="text-end">{{ $fmt($emp['remaining']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No employees with unused annual leave.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Peak: month-by-month leave days -->
<div class="modal fade" id="leaveInsightPeakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $leaveInsights['peak']['title'] ?? 'AI Forecasted Peak Leave Periods' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $leaveInsights['peak']['body'] ?? '' }}</p>
                @if(!empty($leaveInsights["peak"]["recommendation"]))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $leaveInsights["peak"]["recommendation"] }}</p>@endif
                @if(!empty($peakDetails['total']) && $peakDetails['total'] > 0)
                    <p class="mb-2">Leave days by month (total {{ $fmt($peakDetails['total']) }}):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Month</th><th class="text-end">Leave days</th><th class="text-end">Share</th></tr></thead>
                            <tbody>
                                @foreach(($peakDetails['months'] ?? []) as $m => $d)
                                    <tr>
                                        <td>{{ $monthNames[$m] ?? $m }}</td>
                                        <td class="text-end">{{ $fmt($d) }}</td>
                                        <td class="text-end">{{ (int) round(($d / max(1, $peakDetails['total'])) * 100) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">Not enough leave history to break down by month.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Behaviour: leave-category breakdown -->
<div class="modal fade" id="leaveInsightBehaviorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $leaveInsights['behavior']['title'] ?? 'Employee Leave Behavior Analysis' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $leaveInsights['behavior']['body'] ?? '' }}</p>
                @if(!empty($leaveInsights["behavior"]["recommendation"]))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $leaveInsights["behavior"]["recommendation"] }}</p>@endif
                @if(!empty($behDetails['categories']))
                    <p class="mb-2">Breakdown by leave type ({{ (int) ($behDetails['total'] ?? 0) }} requests):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr><th>Leave type</th><th class="text-end">Requests</th><th class="text-end">Share</th><th class="text-end">Avg days</th><th class="text-end">Approval</th></tr>
                            </thead>
                            <tbody>
                                @foreach($behDetails['categories'] as $cat)
                                    <tr>
                                        <td>{{ $cat['category'] }}</td>
                                        <td class="text-end">{{ $cat['count'] }}</td>
                                        <td class="text-end">{{ $cat['pct'] }}%</td>
                                        <td class="text-end">{{ $fmt($cat['avg_days']) }}</td>
                                        <td class="text-end">{{ $cat['approval'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No leave history yet to analyse.</p>
                @endif
            </div>
        </div>
    </div>
</div>
