@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto"><div class="page-title"><span>Report</span><h1>{{ $page_title }}</h1></div></div>
                <div class="col-auto"><a href="{{ route('resort.report.index') }}" class="btn btn-sm btn-themeSkyblue">Back to Reports</a></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>File Management Reports</h3></div>
                    <div class="card-body">
                        <input type="text" class="form-control form-control-sm mb-3" id="fmReportSearch" placeholder="Search reports…" autocomplete="off">
                        <div class="list-group" id="fmReportList">
                            @foreach($reports as $i => $r)
                                <button type="button" class="list-group-item list-group-item-action fm-report-item @if($i===0) active @endif"
                                        data-key="{{ $r['key'] }}" data-filters="{{ implode(',', $r['filters']) }}">
                                    <strong>{{ $r['name'] }}</strong><small class="d-block text-muted">{{ $r['description'] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-title"><h3 id="fmReportTitle">{{ $reports[0]['name'] ?? 'Report' }}</h3></div>
                    <div class="card-body">
                        <form id="fmFilterForm" class="row g-2 align-items-end mb-3">
                            <input type="hidden" id="fmReportKey" value="{{ $reports[0]['key'] ?? '' }}">

                            <div class="col-sm-6 fm-filter" data-filter="department">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="fmDepartment"><option value="">All departments</option>
                                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 fm-filter" data-filter="employee">
                                <label class="form-label">Employee</label>
                                <select class="form-select" id="fmEmployee"><option value="">All employees</option>
                                    @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 fm-filter" data-filter="document_name">
                                <label class="form-label">Document Name</label>
                                <input type="text" class="form-control" id="fmDocumentName" placeholder="Search file name…">
                            </div>
                            <div class="col-sm-6 fm-filter" data-filter="duration">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="fmFromDate">
                            </div>
                            <div class="col-sm-6 fm-filter" data-filter="duration">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="fmToDate">
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-sm btn-theme" id="fmRunBtn">Run Report</button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle fmActionBtn" disabled type="button" data-bs-toggle="dropdown"><i class="fa fa-download"></i> Export</button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item fmExport" href="javascript:void(0)" data-format="csv"><i class="fa fa-file-csv"></i> CSV</a></li>
                                        <li><a class="dropdown-item fmExport" href="javascript:void(0)" data-format="excel"><i class="fa fa-file-excel"></i> Excel</a></li>
                                        <li><a class="dropdown-item fmExport" href="javascript:void(0)" data-format="pdf"><i class="fa fa-file-pdf"></i> PDF</a></li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-sm btn-theme fmActionBtn" id="fmInsightsBtn" disabled>WAI Insights</button>
                                <button type="button" class="btn btn-sm btn-themeSkyblue d-none" id="fmBackToData">Back to Data</button>
                            </div>
                        </form>

                        <div id="fmResults" class="table-responsive"><p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p></div>
                        <div id="fmInsights" class="wai-insights d-none"></div>
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
        var RUN_URL = '{{ route("resort.report.filemgmt.run") }}';
        var EXPORT_URL = '{{ route("resort.report.filemgmt.export") }}';
        var INSIGHTS_URL = '{{ route("resort.report.filemgmt.insights") }}';
        var TOKEN = '{{ csrf_token() }}';

        function currentFilters() {
            return {
                report: $('#fmReportKey').val(),
                department: $('#fmDepartment').val(),
                employee: $('#fmEmployee').val(),
                document_name: $('#fmDocumentName').val(),
                from_date: $('#fmFromDate').val(),
                to_date: $('#fmToDate').val()
            };
        }
        function setActionsEnabled(on) { $('.fmActionBtn').prop('disabled', !on); }
        function showData() { $('#fmInsights').addClass('d-none').empty(); $('#fmResults').show(); $('#fmBackToData').addClass('d-none'); }
        function applyFilterVisibility($item) {
            var allowed = ($item.data('filters') || '').toString().split(',').filter(Boolean);
            $('.fm-filter').each(function () { $(this).toggle(allowed.indexOf($(this).data('filter')) !== -1); });
        }

        $('.fm-report-item').on('click', function () {
            $('.fm-report-item').removeClass('active'); $(this).addClass('active');
            $('#fmReportKey').val($(this).data('key'));
            $('#fmReportTitle').text($(this).find('strong').text());
            applyFilterVisibility($(this)); showData(); setActionsEnabled(false);
            $('#fmResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#fmFilterForm').on('submit', function (e) {
            e.preventDefault(); showData();
            var $btn = $('#fmRunBtn').prop('disabled', true).text('Running…');
            $('#fmResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');
            $.ajax({
                url: RUN_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    $('#fmResults').html(res.success ? res.html : '<p class="text-danger text-center mb-0">' + (res.message || 'Failed.') + '</p>');
                    setActionsEnabled(res.success && res.count > 0);
                },
                error: function (xhr) {
                    $('#fmResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                    setActionsEnabled(false);
                },
                complete: function () { $btn.prop('disabled', false).text('Run Report'); }
            });
        });

        $('.fmExport').on('click', function () {
            var data = $.extend({ _token: TOKEN, format: $(this).data('format') }, currentFilters());
            var $f = $('<form>', { method: 'POST', action: EXPORT_URL }).css('display', 'none');
            $.each(data, function (k, v) { $f.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v })); });
            $f.appendTo('body').submit().remove();
        });

        $('#fmInsightsBtn').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Working… Please Wait');
            $.ajax({
                url: INSIGHTS_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    var md = (res && res.data) || '';
                    $('#fmInsights').html(md ? ((typeof marked !== 'undefined') ? (marked.parse ? marked.parse(md) : marked(md)) : $('<div>').text(md).html()) : '<p class="text-muted">No insights available (the WAI service may be offline).</p>');
                    $('#fmResults').hide(); $('#fmInsights').removeClass('d-none'); $('#fmBackToData').removeClass('d-none');
                },
                error: function () { $('#fmInsights').html('<p class="text-danger">Failed to load insights.</p>').removeClass('d-none'); $('#fmResults').hide(); $('#fmBackToData').removeClass('d-none'); },
                complete: function () { $b.prop('disabled', false).text('WAI Insights'); }
            });
        });

        $('#fmBackToData').on('click', showData);
        $('#fmReportSearch').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.fm-report-item').each(function () { $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1); });
        });

        applyFilterVisibility($('.fm-report-item.active').first());
    });
</script>
@endsection
