@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    /* Keep the selected report's description readable on the active (blue) background. */
    .list-group-item.active, .list-group-item.active strong { color:#fff !important; }
    .list-group-item.active .text-muted, .list-group-item.active small { color:rgba(255,255,255,.85) !important; }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Report</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.report.index') }}" class="btn btn-sm btn-themeSkyblue">Back to Reports</a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>Payroll Reports</h3></div>
                    <div class="card-body">
                        <input type="text" class="form-control form-control-sm mb-3" id="plReportSearch" placeholder="Search reports…" autocomplete="off">
                        <div class="list-group" id="plReportList" style="max-height:70vh;overflow:auto">
                            @foreach($reports as $i => $r)
                                <button type="button"
                                        class="list-group-item list-group-item-action pl-report-item @if($i===0) active @endif"
                                        data-key="{{ $r['key'] }}"
                                        data-filters="{{ implode(',', $r['filters']) }}">
                                    <strong>{{ $r['name'] }}</strong>
                                    <small class="d-block text-muted">{{ $r['description'] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-title"><h3 id="plReportTitle">{{ $reports[0]['name'] ?? 'Report' }}</h3></div>
                    <div class="card-body">
                        <form id="plFilterForm" class="row g-2 align-items-end mb-3">
                            <input type="hidden" id="plReportKey" value="{{ $reports[0]['key'] ?? '' }}">

                            <div class="col-sm-6 pl-filter" data-filter="payroll">
                                <label class="form-label">Payroll Period</label>
                                <select class="form-select" id="plPayroll">
                                    <option value="">All periods (use date range)</option>
                                    @foreach($payrolls as $p)
                                        <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="from_payroll">
                                <label class="form-label">From Payroll Period</label>
                                <select class="form-select" id="plFromPayroll">
                                    @foreach($payrolls as $p)
                                        <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="to_payroll">
                                <label class="form-label">To Payroll Period</label>
                                <select class="form-select" id="plToPayroll">
                                    @foreach($payrolls as $p)
                                        <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="department">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="plDepartment">
                                    <option value="">All departments</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="year">
                                <label class="form-label">Year</label>
                                <select class="form-select" id="plYear">
                                    <option value="">All years</option>
                                    @foreach($years as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="allowance_type">
                                <label class="form-label">Allowance Type</label>
                                <select class="form-select" id="plAllowanceType">
                                    <option value="">All types</option>
                                    @foreach($allowanceTypes as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="deduction_type">
                                <label class="form-label">Deduction Type</label>
                                <select class="form-select" id="plDeductionType">
                                    <option value="">All types</option>
                                    @foreach($deductionTypes as $dt)
                                        <option value="{{ $dt }}">{{ $dt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="bank">
                                <label class="form-label">Bank</label>
                                <select class="form-select" id="plBank">
                                    <option value="">All banks</option>
                                    @foreach($banks as $b)
                                        <option value="{{ $b }}">{{ $b }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="settlement_status">
                                <label class="form-label">Settlement Status</label>
                                <select class="form-select" id="plSettlementStatus">
                                    <option value="">All statuses</option>
                                    @foreach($settlementStatuses as $s)
                                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6 pl-filter" data-filter="duration">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="plFromDate">
                            </div>
                            <div class="col-sm-6 pl-filter" data-filter="duration">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="plToDate">
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-sm btn-theme" id="plRunBtn">Run Report</button>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle plActionBtn" disabled type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-download"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item plExport" href="javascript:void(0)" data-format="csv"><i class="fa fa-file-csv"></i> CSV</a></li>
                                        <li><a class="dropdown-item plExport" href="javascript:void(0)" data-format="excel"><i class="fa fa-file-excel"></i> Excel</a></li>
                                        <li><a class="dropdown-item plExport" href="javascript:void(0)" data-format="pdf"><i class="fa fa-file-pdf"></i> PDF</a></li>
                                    </ul>
                                </div>

                                <button type="button" class="btn btn-sm btn-theme plActionBtn" id="plInsightsBtn" disabled>WAI Insights</button>
                                <button type="button" class="btn btn-sm btn-themeSkyblue d-none" id="plBackToData">Back to Data</button>
                            </div>
                        </form>

                        <div id="plResults" class="table-responsive">
                            <p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p>
                        </div>
                        <div id="plInsights" class="wai-insights d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
    @include('resorts.reports.partials.wai_insights_css')
@endsection

@section('import-scripts')
<script>
    $(function () {
        var RUN_URL      = '{{ route("resort.report.payroll.run") }}';
        var EXPORT_URL   = '{{ route("resort.report.payroll.export") }}';
        var INSIGHTS_URL = '{{ route("resort.report.payroll.insights") }}';
        var TOKEN        = '{{ csrf_token() }}';

        function currentFilters() {
            return {
                report: $('#plReportKey').val(),
                payroll: $('#plPayroll').val(),
                from_payroll: $('#plFromPayroll').val(),
                to_payroll: $('#plToPayroll').val(),
                department: $('#plDepartment').val(),
                year: $('#plYear').val(),
                allowance_type: $('#plAllowanceType').val(),
                deduction_type: $('#plDeductionType').val(),
                bank: $('#plBank').val(),
                settlement_status: $('#plSettlementStatus').val(),
                from_date: $('#plFromDate').val(),
                to_date: $('#plToDate').val()
            };
        }
        function setActionsEnabled(on) { $('.plActionBtn').prop('disabled', !on); }
        function showData() {
            $('#plInsights').addClass('d-none').empty();
            $('#plResults').show();
            $('#plBackToData').addClass('d-none');
        }

        function applyFilterVisibility($item) {
            var allowed = ($item.data('filters') || '').toString().split(',').filter(Boolean);
            $('.pl-filter').each(function () {
                $(this).toggle(allowed.indexOf($(this).data('filter')) !== -1);
            });
        }

        $('.pl-report-item').on('click', function () {
            $('.pl-report-item').removeClass('active');
            $(this).addClass('active');
            $('#plReportKey').val($(this).data('key'));
            $('#plReportTitle').text($(this).find('strong').text());
            applyFilterVisibility($(this));
            showData();
            setActionsEnabled(false);
            $('#plResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#plFilterForm').on('submit', function (e) {
            e.preventDefault();
            showData();
            var $btn = $('#plRunBtn').prop('disabled', true).text('Running…');
            $('#plResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');

            $.ajax({
                url: RUN_URL,
                type: 'POST',
                data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    $('#plResults').html(res.success ? res.html
                        : '<p class="text-danger text-center mb-0">' + (res.message || 'Failed to run report.') + '</p>');
                    setActionsEnabled(res.success && res.count > 0);
                },
                error: function (xhr) {
                    $('#plResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                    setActionsEnabled(false);
                },
                complete: function () { $btn.prop('disabled', false).text('Run Report'); }
            });
        });

        $('.plExport').on('click', function () {
            var data = $.extend({ _token: TOKEN, format: $(this).data('format') }, currentFilters());
            var $f = $('<form>', { method: 'POST', action: EXPORT_URL }).css('display', 'none');
            $.each(data, function (k, v) { $f.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v })); });
            $f.appendTo('body').submit().remove();
        });

        $('#plInsightsBtn').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Working… Please Wait');
            $.ajax({
                url: INSIGHTS_URL,
                type: 'POST',
                data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    var md = (res && res.data) || '';
                    if (!md) {
                        $('#plInsights').html('<p class="text-muted">No insights available (the WAI service may be offline).</p>');
                    } else {
                        var html = (typeof marked !== 'undefined') ? (marked.parse ? marked.parse(md) : marked(md)) : $('<div>').text(md).html();
                        $('#plInsights').html(html);
                    }
                    $('#plResults').hide();
                    $('#plInsights').removeClass('d-none');
                    $('#plBackToData').removeClass('d-none');
                },
                error: function () {
                    $('#plInsights').html('<p class="text-danger">Failed to load insights.</p>').removeClass('d-none');
                    $('#plResults').hide();
                    $('#plBackToData').removeClass('d-none');
                },
                complete: function () { $b.prop('disabled', false).text('WAI Insights'); }
            });
        });

        $('#plBackToData').on('click', showData);

        // Search/filter the report list by name + description.
        $('#plReportSearch').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.pl-report-item').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
            });
        });

        applyFilterVisibility($('.pl-report-item.active').first());
    });
</script>
@endsection
