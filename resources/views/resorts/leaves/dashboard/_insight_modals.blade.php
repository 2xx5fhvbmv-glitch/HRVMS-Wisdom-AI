{{-- AI-insight detail modals (occupancy / peak / behaviour).
     Included by the HR, Admin and HOD leave dashboards; reads $leaveInsights.
     Opened by the "View Details" links via the shared frosted-modal system
     (partials/_wai_insight_modals.blade.php). Table-only body —
     title/issue/recommendation already live on the card and the shared
     Recommendation modal. --}}
<style>
    /* Keep the WAI Insights card at its fixed height and let the insight
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
<div class="wai-backdrop" id="leaveInsightOccupancyModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $leaveInsights['occupancy']['title'] ?? 'Occupancy & Leave Window' }}</div>
        @if(!empty($occDetails['employees']))
            <div class="m-tablewrap">
                <div class="m-tcap">Employees with unused annual leave @if(!empty($occDetails['entitlement']))(entitlement {{ $fmt($occDetails['entitlement']) }} days)@endif</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Employee</th><th>Department</th><th>Used</th><th>Remaining</th></tr></thead>
                    <tbody>
                        @foreach($occDetails['employees'] as $emp)
                            <tr><td>{{ $emp['name'] }}</td><td>{{ $emp['dept'] }}</td><td>{{ $fmt($emp['used']) }}</td><td>{{ $fmt($emp['remaining']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No employees with unused annual leave.</p>
        @endif
    </div>
</div>

<!-- Peak: month-by-month leave days -->
<div class="wai-backdrop" id="leaveInsightPeakModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $leaveInsights['peak']['title'] ?? 'AI Forecasted Peak Leave Periods' }}</div>
        @if(!empty($peakDetails['total']) && $peakDetails['total'] > 0)
            <div class="m-tablewrap">
                <div class="m-tcap">Leave days by month (total {{ $fmt($peakDetails['total']) }})</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Month</th><th>Leave days</th><th>Share</th></tr></thead>
                    <tbody>
                        @foreach(($peakDetails['months'] ?? []) as $mo => $d)
                            <tr><td>{{ $monthNames[$mo] ?? $mo }}</td><td>{{ $fmt($d) }}</td><td>{{ (int) round(($d / max(1, $peakDetails['total'])) * 100) }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">Not enough leave history to break down by month.</p>
        @endif
    </div>
</div>

<!-- Behaviour: leave-category breakdown -->
<div class="wai-backdrop" id="leaveInsightBehaviorModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $leaveInsights['behavior']['title'] ?? 'Employee Leave Behavior Analysis' }}</div>
        @if(!empty($behDetails['categories']))
            <div class="m-tablewrap">
                <div class="m-tcap">Breakdown by leave type ({{ (int) ($behDetails['total'] ?? 0) }} requests)</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Leave type</th><th>Requests</th><th>Share</th><th>Avg days</th><th>Approval</th></tr></thead>
                    <tbody>
                        @foreach($behDetails['categories'] as $cat)
                            <tr class="{{ $cat['approval'] == 0 ? 'attn' : '' }}">
                                <td>{{ $cat['category'] }}</td>
                                <td>{{ $cat['count'] }}</td>
                                <td>{{ $cat['pct'] }}%</td>
                                <td>{{ $fmt($cat['avg_days']) }}</td>
                                <td class="rate {{ $cat['approval'] == 0 ? 'zero' : ($cat['approval'] == 100 ? 'full' : '') }}">{{ $cat['approval'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No leave history yet to analyse.</p>
        @endif
    </div>
</div>
