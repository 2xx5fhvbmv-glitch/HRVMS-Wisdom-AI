{{-- Payroll AI-insight detail modals. Included by the payroll dashboard;
     reads $payrollInsights. Opened by the "View Details" links via the
     shared frosted-modal system (partials/_wai_insight_modals.blade.php).
     Table-only body — title/issue/recommendation already live on the card
     and the shared Recommendation modal. --}}
<style>
    /* Keep the WAI Insights card at its fixed height and let the insight
       list scroll inside it (the AI body + recommendation can overflow). */
    .card-wiINsightPayroll {
        display: flex;
        flex-direction: column;
    }
    .card-wiINsightPayroll .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
</style>
@php
    $trendD = $payrollInsights['trend']['details']     ?? [];
    $otD    = $payrollInsights['overtime']['details']  ?? [];
    $expD   = $payrollInsights['expat']['details']     ?? [];
    $alwD   = $payrollInsights['allowance']['details'] ?? [];
    $sym    = '$';
    try { $sym = \App\Helpers\Common::GetResortCurrencySymbol() ?: '$'; } catch (\Throwable $e) {}
    $m = function ($n) use ($sym) { return $sym . ' ' . number_format((float) $n, 0); };
@endphp

<!-- Payroll cost trend -->
<div class="wai-backdrop" id="payrollInsightTrendModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $payrollInsights['trend']['title'] ?? 'Payroll Cost Trend' }}</div>
        @if(!empty($trendD['months']))
            <div class="m-tablewrap">
                <div class="m-tcap">Monthly payroll</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Month</th><th>Payroll</th></tr></thead>
                    <tbody>
                        @foreach($trendD['months'] as $mn => $tot)
                            <tr><td>{{ $trendD['month_names'][$mn] ?? $mn }}</td><td>{{ $m($tot) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif
        @if(!empty($trendD['by_dept']))
            <div class="m-tablewrap">
                <div class="m-tcap">By department (latest payroll)</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Department</th><th>Payroll</th></tr></thead>
                    <tbody>
                        @foreach($trendD['by_dept'] as $row)
                            <tr><td>{{ $row['dept'] }}</td><td>{{ $m($row['total']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif
        @if(empty($trendD['months']) && empty($trendD['by_dept']))
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Overtime hotspots -->
<div class="wai-backdrop" id="payrollInsightOvertimeModal">
    <div class="wai-modal wide" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $payrollInsights['overtime']['title'] ?? 'Overtime Spend & Hotspots' }}</div>
        @if(!empty($otD['by_dept']))
            <div class="m-tablewrap">
                <div class="m-tcap">Overtime by department</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Department</th><th>OT cost</th></tr></thead>
                    <tbody>
                        @foreach($otD['by_dept'] as $row)
                            <tr><td>{{ $row['dept'] }}</td><td>{{ $m($row['ot']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif
        @if(!empty($otD['top_emps']))
            <div class="m-tablewrap">
                <div class="m-tcap">Top employees by overtime</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Employee</th><th>Department</th><th>OT cost</th></tr></thead>
                    <tbody>
                        @foreach($otD['top_emps'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['dept'] }}</td><td>{{ $m($row['ot']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @endif
        @if(empty($otD['by_dept']) && empty($otD['top_emps']))
            <p class="m-empty">No overtime recorded in the latest payroll.</p>
        @endif
    </div>
</div>

<!-- Expat vs local -->
<div class="wai-backdrop" id="payrollInsightExpatModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $payrollInsights['expat']['title'] ?? 'Expat vs Local Cost' }}</div>
        @if(!empty($expD))
            <div class="m-tablewrap">
                <div class="m-tcap">
                    @if(!empty($expD['operational']))Expat operational add-on (work permit / visa / insurance): ~{{ $m($expD['operational']) }}/mo @endif
                </div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th></th><th>Headcount</th><th>Salary cost</th></tr></thead>
                    <tbody>
                        <tr><td>Expat</td><td>{{ (int)($expD['expat_count'] ?? 0) }}</td><td>{{ $m($expD['expat_salary'] ?? 0) }}</td></tr>
                        <tr><td>Local</td><td>{{ (int)($expD['local_count'] ?? 0) }}</td><td>{{ $m($expD['local_salary'] ?? 0) }}</td></tr>
                        <tr><td>Total</td><td>{{ (int)($expD['expat_count'] ?? 0) + (int)($expD['local_count'] ?? 0) }}</td><td>{{ $m($expD['total'] ?? 0) }}</td></tr>
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No data.</p>
        @endif
    </div>
</div>

<!-- Allowance spend -->
<div class="wai-backdrop" id="payrollInsightAllowanceModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt">{{ $payrollInsights['allowance']['title'] ?? 'Allowance Spend' }}</div>
        @if(!empty($alwD['types']))
            <div class="m-tablewrap">
                <div class="m-tcap">Allowance spend by type (total {{ $m($alwD['total'] ?? 0) }})</div>
                <div class="m-tscroll"><table class="m-table">
                    <thead><tr><th>Allowance type</th><th>Amount</th><th>Share</th></tr></thead>
                    <tbody>
                        @foreach($alwD['types'] as $row)
                            <tr><td>{{ $row['type'] }}</td><td>{{ $m($row['amount']) }}</td><td>{{ $row['pct'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        @else
            <p class="m-empty">No allowances paid in the latest payroll.</p>
        @endif
    </div>
</div>
