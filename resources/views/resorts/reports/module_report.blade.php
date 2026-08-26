{{-- Generic predefined-report page. Driven by:
     $page_title, $reports (key/name/description/filters[]), $filterDefs (filter controls),
     $runRoute/$exportRoute/$insightsRoute (route names). Reused by every module
     report controller (Survey, L&D, Grievance, Incident, Accommodation, …). --}}
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    /* Keep the selected report's description readable on the active (blue) background. */
    .list-group-item.active, .list-group-item.active strong { color:#fff !important; }
    .list-group-item.active .text-muted, .list-group-item.active small { color:rgba(255,255,255,.85) !important; }
    /* .row is display:flex, so this flex item grows to fit a wide table
       instead of clipping it — min-width:0 lets .table-responsive inside
       actually scroll instead of blowing out the whole page horizontally. */
    .report-results-col { min-width: 0; }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto"><div class="page-title"><span>Report</span><h1>{{ $page_title }}</h1></div></div>
                <div class="col-auto"><a href="{{ route('resort.report.index') }}" class="btn eb-btn-secondary">Back to Reports</a></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>{{ $page_title }}</h3></div>
                    <div class="card-body">
                        <input type="text" class="form-control form-control-sm mb-3" id="modReportSearch" placeholder="Search reports…" autocomplete="off">
                        <div class="list-group" id="modReportList" style="max-height:70vh;overflow:auto">
                            @foreach($reports as $i => $r)
                                <button type="button" class="list-group-item list-group-item-action mod-report-item @if($i===0) active @endif"
                                        data-key="{{ $r['key'] }}" data-filters="{{ implode(',', $r['filters']) }}">
                                    <strong>{{ $r['name'] }}</strong><small class="d-block text-muted">{{ $r['description'] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 report-results-col">
                <div class="card">
                    <div class="card-title"><h3 id="modReportTitle">{{ $reports[0]['name'] ?? 'Report' }}</h3></div>
                    <div class="card-body">
                        <form id="modFilterForm" class="row g-2 align-items-end mb-3">
                            <input type="hidden" id="modReportKey" value="{{ $reports[0]['key'] ?? '' }}">

                            @foreach($filterDefs as $fd)
                                <div class="col-sm-6 mod-filter" data-filter="{{ $fd['filter'] }}">
                                    <label class="form-label">{{ $fd['label'] }}</label>
                                    @if($fd['type'] === 'select')
                                        <select class="form-select" data-name="{{ $fd['name'] }}">
                                            @if(!empty($fd['placeholder']))<option value="">{{ $fd['placeholder'] }}</option>@endif
                                            @foreach($fd['options'] as $opt)
                                                <option value="{{ $opt['value'] }}" @if(($fd['default'] ?? null) === $opt['value']) selected @endif>{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($fd['type'] === 'date')
                                        <input type="date" class="form-control" data-name="{{ $fd['name'] }}">
                                    @else
                                        <input type="text" class="form-control" data-name="{{ $fd['name'] }}" placeholder="{{ $fd['placeholder'] ?? '' }}">
                                    @endif
                                </div>
                            @endforeach

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn eb-btn-primary btn-sm" id="modRunBtn">Run Report</button>
                                <div class="dropdown">
                                    <button class="btn eb-btn-accent dropdown-toggle modActionBtn" disabled type="button" data-bs-toggle="dropdown"><i class="fa fa-download"></i> Export</button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item modExport" href="javascript:void(0)" data-format="csv"><i class="fa fa-file-csv"></i> CSV</a></li>
                                        <li><a class="dropdown-item modExport" href="javascript:void(0)" data-format="excel"><i class="fa fa-file-excel"></i> Excel</a></li>
                                        <li><a class="dropdown-item modExport" href="javascript:void(0)" data-format="pdf"><i class="fa fa-file-pdf"></i> PDF</a></li>
                                    </ul>
                                </div>
                                <button type="button" class="btn eb-btn-accent modActionBtn" id="modInsightsBtn" disabled>WAI Insights</button>
                                <button type="button" class="btn eb-btn-secondary d-none" id="modBackToData">Back to Data</button>
                            </div>
                        </form>

                        <div id="modResults" class="table-responsive"><p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p></div>
                        <div id="modInsights" class="wai-insights d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
    @include('resorts.reports.partials.wai_insights_css')
@endsection

@section('import-scripts')
<script>
    $(function () {
        var RUN_URL = '{{ route($runRoute) }}';
        var EXPORT_URL = '{{ route($exportRoute) }}';
        var INSIGHTS_URL = '{{ route($insightsRoute) }}';
        var TOKEN = '{{ csrf_token() }}';

        function currentFilters() {
            var d = { report: $('#modReportKey').val() };
            $('.mod-filter [data-name]').each(function () { d[$(this).data('name')] = $(this).val(); });
            return d;
        }
        function setActionsEnabled(on) { $('.modActionBtn').prop('disabled', !on); }
        function showData() { $('#modInsights').addClass('d-none').empty(); $('#modResults').show(); $('#modBackToData').addClass('d-none'); }
        function applyFilterVisibility($item) {
            var allowed = ($item.data('filters') || '').toString().split(',').filter(Boolean);
            $('.mod-filter').each(function () { $(this).toggle(allowed.indexOf($(this).data('filter')) !== -1); });
        }

        $('.mod-report-item').on('click', function () {
            $('.mod-report-item').removeClass('active'); $(this).addClass('active');
            $('#modReportKey').val($(this).data('key'));
            $('#modReportTitle').text($(this).find('strong').text());
            applyFilterVisibility($(this)); showData(); setActionsEnabled(false);
            $('#modResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#modFilterForm').on('submit', function (e) {
            e.preventDefault(); showData();
            var $btn = $('#modRunBtn').prop('disabled', true).text('Running…');
            $('#modResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');
            $.ajax({
                url: RUN_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    $('#modResults').html(res.success ? res.html : '<p class="text-danger text-center mb-0">' + (res.message || 'Failed.') + '</p>');
                    setActionsEnabled(res.success && res.count > 0);
                },
                error: function (xhr) {
                    $('#modResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                    setActionsEnabled(false);
                },
                complete: function () { $btn.prop('disabled', false).text('Run Report'); }
            });
        });

        $('.modExport').on('click', function () {
            var data = $.extend({ _token: TOKEN, format: $(this).data('format') }, currentFilters());
            var $f = $('<form>', { method: 'POST', action: EXPORT_URL }).css('display', 'none');
            $.each(data, function (k, v) { $f.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v })); });
            $f.appendTo('body').submit().remove();
        });

        $('#modInsightsBtn').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Working… Please Wait');
            $.ajax({
                url: INSIGHTS_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    var md = (res && res.data) || '';
                    $('#modInsights').html(md ? ((typeof marked !== 'undefined') ? (marked.parse ? marked.parse(md) : marked(md)) : $('<div>').text(md).html()) : '<p class="text-muted">No insights available (the WAI service may be offline).</p>');
                    $('#modResults').hide(); $('#modInsights').removeClass('d-none'); $('#modBackToData').removeClass('d-none');
                },
                error: function () { $('#modInsights').html('<p class="text-danger">Failed to load insights.</p>').removeClass('d-none'); $('#modResults').hide(); $('#modBackToData').removeClass('d-none'); },
                complete: function () { $b.prop('disabled', false).text('WAI Insights'); }
            });
        });

        $('#modBackToData').on('click', showData);
        $('#modReportSearch').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.mod-report-item').each(function () { $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1); });
        });

        applyFilterVisibility($('.mod-report-item.active').first());
    });
</script>
@endsection
