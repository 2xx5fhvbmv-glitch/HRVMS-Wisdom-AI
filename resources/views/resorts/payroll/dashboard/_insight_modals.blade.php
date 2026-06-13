{{-- Payroll AI-insight detail modals. Included by the payroll dashboard;
     reads $payrollInsights. Opened by the "View Details" links. --}}
<style>
    /* Keep the WAI Insight's card at its fixed height and let the insight
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
<div class="modal fade" id="payrollInsightTrendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $payrollInsights['trend']['title'] ?? 'Payroll Cost Trend' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $payrollInsights['trend']['body'] ?? '' }}</p>
                @if(!empty($payrollInsights['trend']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $payrollInsights['trend']['recommendation'] }}</p>@endif
                @if(!empty($trendD['months']))
                    <p class="mb-1 fw-bold">Monthly payroll</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Month</th><th class="text-end">Payroll</th></tr></thead>
                            <tbody>
                                @foreach($trendD['months'] as $mn => $tot)
                                    <tr><td>{{ $trendD['month_names'][$mn] ?? $mn }}</td><td class="text-end">{{ $m($tot) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(!empty($trendD['by_dept']))
                    <p class="mb-1 fw-bold">By department (latest payroll)</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Department</th><th class="text-end">Payroll</th></tr></thead>
                            <tbody>
                                @foreach($trendD['by_dept'] as $row)
                                    <tr><td>{{ $row['dept'] }}</td><td class="text-end">{{ $m($row['total']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Overtime hotspots -->
<div class="modal fade" id="payrollInsightOvertimeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $payrollInsights['overtime']['title'] ?? 'Overtime Spend & Hotspots' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $payrollInsights['overtime']['body'] ?? '' }}</p>
                @if(!empty($payrollInsights['overtime']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $payrollInsights['overtime']['recommendation'] }}</p>@endif
                @if(!empty($otD['by_dept']))
                    <p class="mb-1 fw-bold">Overtime by department</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Department</th><th class="text-end">OT cost</th></tr></thead>
                            <tbody>
                                @foreach($otD['by_dept'] as $row)
                                    <tr><td>{{ $row['dept'] }}</td><td class="text-end">{{ $m($row['ot']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(!empty($otD['top_emps']))
                    <p class="mb-1 fw-bold">Top employees by overtime</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Employee</th><th>Department</th><th class="text-end">OT cost</th></tr></thead>
                            <tbody>
                                @foreach($otD['top_emps'] as $row)
                                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['dept'] }}</td><td class="text-end">{{ $m($row['ot']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    @if(empty($otD['by_dept']))<p class="mb-0">No overtime recorded in the latest payroll.</p>@endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Expat vs local -->
<div class="modal fade" id="payrollInsightExpatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $payrollInsights['expat']['title'] ?? 'Expat vs Local Cost' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $payrollInsights['expat']['body'] ?? '' }}</p>
                @if(!empty($payrollInsights['expat']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $payrollInsights['expat']['recommendation'] }}</p>@endif
                @if(!empty($expD))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th></th><th class="text-end">Headcount</th><th class="text-end">Salary cost</th></tr></thead>
                            <tbody>
                                <tr><td>Expat</td><td class="text-end">{{ (int)($expD['expat_count'] ?? 0) }}</td><td class="text-end">{{ $m($expD['expat_salary'] ?? 0) }}</td></tr>
                                <tr><td>Local</td><td class="text-end">{{ (int)($expD['local_count'] ?? 0) }}</td><td class="text-end">{{ $m($expD['local_salary'] ?? 0) }}</td></tr>
                                <tr class="fw-bold"><td>Total</td><td class="text-end">{{ (int)($expD['expat_count'] ?? 0) + (int)($expD['local_count'] ?? 0) }}</td><td class="text-end">{{ $m($expD['total'] ?? 0) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($expD['operational']))
                        <p class="mb-0 text-muted">Expat operational add-on (work permit / visa / insurance): ~{{ $m($expD['operational']) }}/mo.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Allowance spend -->
<div class="modal fade" id="payrollInsightAllowanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $payrollInsights['allowance']['title'] ?? 'Allowance Spend' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $payrollInsights['allowance']['body'] ?? '' }}</p>
                @if(!empty($payrollInsights['allowance']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $payrollInsights['allowance']['recommendation'] }}</p>@endif
                @if(!empty($alwD['types']))
                    <p class="mb-1">Allowance spend by type (total {{ $m($alwD['total'] ?? 0) }}):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Allowance type</th><th class="text-end">Amount</th><th class="text-end">Share</th></tr></thead>
                            <tbody>
                                @foreach($alwD['types'] as $row)
                                    <tr><td>{{ $row['type'] }}</td><td class="text-end">{{ $m($row['amount']) }}</td><td class="text-end">{{ $row['pct'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No allowances paid in the latest payroll.</p>
                @endif
            </div>
        </div>
    </div>
</div>
