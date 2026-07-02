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
            {{-- Report picker --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>Predefined Reports</h3></div>
                    <div class="card-body">
                        <input type="text" class="form-control form-control-sm mb-3" id="wfpReportSearch" placeholder="Search reports…" autocomplete="off">
                        <div class="list-group" id="wfpReportList">
                            @foreach($reports as $i => $r)
                                <button type="button"
                                        class="list-group-item list-group-item-action wfp-report-item @if($i===0) active @endif"
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

            {{-- Filters + results --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-title"><h3 id="wfpReportTitle">{{ $reports[0]['name'] ?? 'Report' }}</h3></div>
                    <div class="card-body">
                        <form id="wfpFilterForm" class="row g-2 align-items-end mb-3">
                            <input type="hidden" id="wfpReportKey" value="{{ $reports[0]['key'] ?? '' }}">

                            <div class="col-sm-4 wfp-filter" data-filter="year">
                                <label class="form-label">Year</label>
                                <select class="form-select" id="wfpYear">
                                    <option value="">All years</option>
                                    @foreach($years as $y)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="department">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="wfpDepartment">
                                    <option value="">All departments</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="position">
                                <label class="form-label">Position</label>
                                <select class="form-select" id="wfpPosition">
                                    <option value="">All positions</option>
                                    @foreach($positions as $p)
                                        <option value="{{ $p->id }}" data-dept="{{ $p->dept_id }}">{{ $p->position_title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="month">
                                <label class="form-label">Month</label>
                                <select class="form-select" id="wfpMonth">
                                    <option value="">All months</option>
                                    @foreach($months as $m)
                                        <option value="{{ $m['value'] }}">{{ $m['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="status">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="wfpStatus">
                                    <option value="">All statuses</option>
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="employment_type">
                                <label class="form-label">Employment Type</label>
                                <select class="form-select" id="wfpEmploymentType">
                                    <option value="">All types</option>
                                    @foreach($employmentTypes as $et)
                                        <option value="{{ $et }}">{{ $et }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-4 wfp-filter" data-filter="duration">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="wfpFromDate">
                            </div>
                            <div class="col-sm-4 wfp-filter" data-filter="duration">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="wfpToDate">
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-sm btn-theme" id="wfpRunBtn">Run Report</button>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle wfpActionBtn" disabled type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-download"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item wfpExport" href="javascript:void(0)" data-format="csv"><i class="fa fa-file-csv"></i> CSV</a></li>
                                        <li><a class="dropdown-item wfpExport" href="javascript:void(0)" data-format="excel"><i class="fa fa-file-excel"></i> Excel</a></li>
                                        <li><a class="dropdown-item wfpExport" href="javascript:void(0)" data-format="pdf"><i class="fa fa-file-pdf"></i> PDF</a></li>
                                    </ul>
                                </div>

                                <button type="button" class="btn btn-sm btn-theme wfpActionBtn" id="wfpInsightsBtn" disabled>WAI Insights</button>
                                <button type="button" class="btn btn-sm btn-themeSkyblue d-none" id="wfpBackToData">Back to Data</button>
                            </div>
                        </form>

                        <div id="wfpResults">
                            <p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p>
                        </div>
                        <div id="wfpInsights" class="wai-insights d-none"></div>
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
        var RUN_URL      = '{{ route("resort.report.wfp.run") }}';
        var EXPORT_URL   = '{{ route("resort.report.wfp.export") }}';
        var INSIGHTS_URL = '{{ route("resort.report.wfp.insights") }}';
        var TOKEN        = '{{ csrf_token() }}';

        function currentFilters() {
            return {
                report: $('#wfpReportKey').val(),
                year: $('#wfpYear').val(),
                department: $('#wfpDepartment').val(),
                position: $('#wfpPosition').val(),
                month: $('#wfpMonth').val(),
                status: $('#wfpStatus').val(),
                employment_type: $('#wfpEmploymentType').val(),
                from_date: $('#wfpFromDate').val(),
                to_date: $('#wfpToDate').val()
            };
        }
        function setActionsEnabled(on) {
            $('.wfpActionBtn').prop('disabled', !on);
        }
        function showData() {
            $('#wfpInsights').addClass('d-none').empty();
            $('#wfpResults').show();
            $('#wfpBackToData').addClass('d-none');
        }

        // Show only the filters a report declares; keep the rest hidden.
        function applyFilterVisibility($item) {
            var allowed = ($item.data('filters') || '').toString().split(',');
            allowed.push('year'); // year always available
            $('.wfp-filter').each(function () {
                $(this).toggle(allowed.indexOf($(this).data('filter')) !== -1);
            });
        }

        // Limit positions to the chosen department.
        function syncPositions() {
            var dept = $('#wfpDepartment').val();
            $('#wfpPosition option').each(function () {
                if (!$(this).val()) return;
                $(this).toggle(!dept || String($(this).data('dept')) === String(dept));
            });
            var $sel = $('#wfpPosition option:selected');
            if ($sel.length && $sel.val() && $sel.is(':hidden')) $('#wfpPosition').val('');
        }

        $('.wfp-report-item').on('click', function () {
            $('.wfp-report-item').removeClass('active');
            $(this).addClass('active');
            $('#wfpReportKey').val($(this).data('key'));
            $('#wfpReportTitle').text($(this).find('strong').text());
            applyFilterVisibility($(this));
            showData();
            setActionsEnabled(false);
            $('#wfpResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#wfpDepartment').on('change', syncPositions);

        $('#wfpFilterForm').on('submit', function (e) {
            e.preventDefault();
            showData();
            var $btn = $('#wfpRunBtn').prop('disabled', true).text('Running…');
            $('#wfpResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');

            $.ajax({
                url: RUN_URL,
                type: 'POST',
                data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    if (res.success) {
                        $('#wfpResults').html(res.html);
                        setActionsEnabled(res.count > 0);
                    } else {
                        $('#wfpResults').html('<p class="text-danger text-center mb-0">' + (res.message || 'Failed to run report.') + '</p>');
                        setActionsEnabled(false);
                    }
                },
                error: function (xhr) {
                    $('#wfpResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                    setActionsEnabled(false);
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Run Report');
                }
            });
        });

        // Export — POST the current filters + format via a real form so the
        // browser downloads the file.
        $('.wfpExport').on('click', function () {
            var data = $.extend({ _token: TOKEN, format: $(this).data('format') }, currentFilters());
            var $f = $('<form>', { method: 'POST', action: EXPORT_URL }).css('display', 'none');
            $.each(data, function (k, v) { $f.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v })); });
            $f.appendTo('body').submit().remove();
        });

        // WAI Insights — render the AI markdown analysis in place of the table.
        $('#wfpInsightsBtn').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Working… Please Wait');
            $.ajax({
                url: INSIGHTS_URL,
                type: 'POST',
                data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    var md = (res && res.data) || '';
                    if (!md) {
                        $('#wfpInsights').html('<p class="text-muted">No insights available (the WAI service may be offline).</p>');
                    } else {
                        var html = (typeof marked !== 'undefined') ? (marked.parse ? marked.parse(md) : marked(md)) : $('<div>').text(md).html();
                        $('#wfpInsights').html(html);
                    }
                    $('#wfpResults').hide();
                    $('#wfpInsights').removeClass('d-none');
                    $('#wfpBackToData').removeClass('d-none');
                },
                error: function () {
                    $('#wfpInsights').html('<p class="text-danger">Failed to load insights.</p>').removeClass('d-none');
                    $('#wfpResults').hide();
                    $('#wfpBackToData').removeClass('d-none');
                },
                complete: function () { $b.prop('disabled', false).text('WAI Insights'); }
            });
        });

        $('#wfpBackToData').on('click', showData);

        // Search/filter the report list by name + description.
        $('#wfpReportSearch').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.wfp-report-item').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
            });
        });

        // Initialise from the first (active) report.
        applyFilterVisibility($('.wfp-report-item.active').first());
        syncPositions();
    });
</script>
@endsection
