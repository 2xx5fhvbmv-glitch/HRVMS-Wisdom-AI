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
                <div class="col-auto">
                    <div class="page-title"><span>Report</span><h1>{{ $page_title }}</h1></div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.report.index') }}" class="btn eb-btn-secondary">Back to Reports</a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>Visa / Immigration Reports</h3></div>
                    <div class="card-body">
                        <input type="text" class="form-control form-control-sm mb-3" id="vsReportSearch" placeholder="Search reports…" autocomplete="off">
                        <div class="list-group" id="vsReportList" style="max-height:70vh;overflow:auto">
                            @foreach($reports as $i => $r)
                                <button type="button" class="list-group-item list-group-item-action vs-report-item @if($i===0) active @endif"
                                        data-key="{{ $r['key'] }}" data-filters="{{ implode(',', $r['filters']) }}">
                                    <strong>{{ $r['name'] }}</strong>
                                    <small class="d-block text-muted">{{ $r['description'] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 report-results-col">
                <div class="card">
                    <div class="card-title"><h3 id="vsReportTitle">{{ $reports[0]['name'] ?? 'Report' }}</h3></div>
                    <div class="card-body">
                        <form id="vsFilterForm" class="row g-2 align-items-end mb-3">
                            <input type="hidden" id="vsReportKey" value="{{ $reports[0]['key'] ?? '' }}">

                            <div class="col-sm-6 vs-filter" data-filter="department">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="vsDepartment"><option value="">All departments</option>
                                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="nationality">
                                <label class="form-label">Nationality</label>
                                <select class="form-select" id="vsNationality"><option value="">All nationalities</option>
                                    @foreach($nationalities as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="employee">
                                <label class="form-label">Employee</label>
                                <select class="form-select" id="vsEmployee"><option value="">All employees</option>
                                    @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="status">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="vsStatus"><option value="">All statuses</option>
                                    @foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="employment_status">
                                <label class="form-label">Employment Status</label>
                                <select class="form-select" id="vsEmploymentStatus"><option value="">All employment statuses</option>
                                    @foreach($employmentStatuses as $es)<option value="{{ $es }}">{{ $es }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="liability_type">
                                <label class="form-label">Liability Type</label>
                                <select class="form-select" id="vsLiabilityType"><option value="">All types</option>
                                    @foreach($liabilityTypes as $lt)<option value="{{ $lt }}">{{ $lt }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="expiry_period">
                                <label class="form-label">Expiry Period</label>
                                <select class="form-select" id="vsExpiryPeriod">
                                    @foreach($expiryPeriods as $ep)<option value="{{ $ep['v'] }}" @if($ep['v']==='90') selected @endif>{{ $ep['l'] }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="year">
                                <label class="form-label">Year</label>
                                <select class="form-select" id="vsYear">
                                    @foreach($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="payment_request">
                                <label class="form-label">Payment Request</label>
                                <select class="form-select" id="vsPaymentRequest"><option value="">All requests</option>
                                    @foreach($paymentRequests as $pr)<option value="{{ $pr->id }}">{{ $pr->Requestd_id }} ({{ $pr->Request_date ? \Carbon\Carbon::parse($pr->Request_date)->format('d M Y') : '' }})</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="duration">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="vsFromDate">
                            </div>
                            <div class="col-sm-6 vs-filter" data-filter="duration">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="vsToDate">
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn eb-btn-primary btn-sm" id="vsRunBtn">Run Report</button>
                                <div class="dropdown">
                                    <button class="btn eb-btn-accent dropdown-toggle vsActionBtn" disabled type="button" data-bs-toggle="dropdown"><i class="fa fa-download"></i> Export</button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item vsExport" href="javascript:void(0)" data-format="csv"><i class="fa fa-file-csv"></i> CSV</a></li>
                                        <li><a class="dropdown-item vsExport" href="javascript:void(0)" data-format="excel"><i class="fa fa-file-excel"></i> Excel</a></li>
                                        <li><a class="dropdown-item vsExport" href="javascript:void(0)" data-format="pdf"><i class="fa fa-file-pdf"></i> PDF</a></li>
                                    </ul>
                                </div>
                                <button type="button" class="btn eb-btn-accent vsActionBtn" id="vsInsightsBtn" disabled>WAI Insights</button>
                                <button type="button" class="btn eb-btn-secondary d-none" id="vsBackToData">Back to Data</button>
                            </div>
                        </form>

                        <div id="vsResults" class="table-responsive">
                            <p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p>
                        </div>
                        <div id="vsInsights" class="wai-insights d-none"></div>
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
        var RUN_URL = '{{ route("resort.report.visa.run") }}';
        var EXPORT_URL = '{{ route("resort.report.visa.export") }}';
        var INSIGHTS_URL = '{{ route("resort.report.visa.insights") }}';
        var TOKEN = '{{ csrf_token() }}';

        function currentFilters() {
            return {
                report: $('#vsReportKey').val(),
                department: $('#vsDepartment').val(),
                nationality: $('#vsNationality').val(),
                employee: $('#vsEmployee').val(),
                status: $('#vsStatus').val(),
                employment_status: $('#vsEmploymentStatus').val(),
                liability_type: $('#vsLiabilityType').val(),
                expiry_period: $('#vsExpiryPeriod').val(),
                year: $('#vsYear').val(),
                payment_request: $('#vsPaymentRequest').val(),
                from_date: $('#vsFromDate').val(),
                to_date: $('#vsToDate').val()
            };
        }
        function setActionsEnabled(on) { $('.vsActionBtn').prop('disabled', !on); }
        function showData() { $('#vsInsights').addClass('d-none').empty(); $('#vsResults').show(); $('#vsBackToData').addClass('d-none'); }
        function applyFilterVisibility($item) {
            var allowed = ($item.data('filters') || '').toString().split(',').filter(Boolean);
            $('.vs-filter').each(function () { $(this).toggle(allowed.indexOf($(this).data('filter')) !== -1); });
        }

        $('.vs-report-item').on('click', function () {
            $('.vs-report-item').removeClass('active'); $(this).addClass('active');
            $('#vsReportKey').val($(this).data('key'));
            $('#vsReportTitle').text($(this).find('strong').text());
            applyFilterVisibility($(this)); showData(); setActionsEnabled(false);
            $('#vsResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#vsFilterForm').on('submit', function (e) {
            e.preventDefault(); showData();
            var $btn = $('#vsRunBtn').prop('disabled', true).text('Running…');
            $('#vsResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');
            $.ajax({
                url: RUN_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    $('#vsResults').html(res.success ? res.html : '<p class="text-danger text-center mb-0">' + (res.message || 'Failed.') + '</p>');
                    setActionsEnabled(res.success && res.count > 0);
                },
                error: function (xhr) {
                    $('#vsResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                    setActionsEnabled(false);
                },
                complete: function () { $btn.prop('disabled', false).text('Run Report'); }
            });
        });

        $('.vsExport').on('click', function () {
            var data = $.extend({ _token: TOKEN, format: $(this).data('format') }, currentFilters());
            var $f = $('<form>', { method: 'POST', action: EXPORT_URL }).css('display', 'none');
            $.each(data, function (k, v) { $f.append($('<input>', { type: 'hidden', name: k, value: v == null ? '' : v })); });
            $f.appendTo('body').submit().remove();
        });

        $('#vsInsightsBtn').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Working… Please Wait');
            $.ajax({
                url: INSIGHTS_URL, type: 'POST', data: $.extend({ _token: TOKEN }, currentFilters()),
                success: function (res) {
                    var md = (res && res.data) || '';
                    $('#vsInsights').html(md ? ((typeof marked !== 'undefined') ? (marked.parse ? marked.parse(md) : marked(md)) : $('<div>').text(md).html()) : '<p class="text-muted">No insights available (the WAI service may be offline).</p>');
                    $('#vsResults').hide(); $('#vsInsights').removeClass('d-none'); $('#vsBackToData').removeClass('d-none');
                },
                error: function () { $('#vsInsights').html('<p class="text-danger">Failed to load insights.</p>').removeClass('d-none'); $('#vsResults').hide(); $('#vsBackToData').removeClass('d-none'); },
                complete: function () { $b.prop('disabled', false).text('WAI Insights'); }
            });
        });

        $('#vsBackToData').on('click', showData);
        $('#vsReportSearch').on('input', function () {
            var q = $(this).val().toLowerCase();
            $('.vs-report-item').each(function () { $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1); });
        });

        applyFilterVisibility($('.vs-report-item.active').first());
    });
</script>
@endsection
