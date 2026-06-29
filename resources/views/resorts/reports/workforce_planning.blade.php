@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
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

                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-theme" id="wfpRunBtn">Run Report</button>
                            </div>
                        </form>

                        <div id="wfpResults">
                            <p class="text-muted text-center mb-0">Choose a report and click <strong>Run Report</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
    $(function () {
        var RUN_URL = '{{ route("resort.report.wfp.run") }}';
        var TOKEN   = '{{ csrf_token() }}';

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
            $('#wfpResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#wfpDepartment').on('change', syncPositions);

        $('#wfpFilterForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#wfpRunBtn').prop('disabled', true).text('Running…');
            $('#wfpResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');

            $.ajax({
                url: RUN_URL,
                type: 'POST',
                data: {
                    _token: TOKEN,
                    report: $('#wfpReportKey').val(),
                    year: $('#wfpYear').val(),
                    department: $('#wfpDepartment').val(),
                    position: $('#wfpPosition').val()
                },
                success: function (res) {
                    if (res.success) {
                        $('#wfpResults').html(res.html);
                    } else {
                        $('#wfpResults').html('<p class="text-danger text-center mb-0">' + (res.message || 'Failed to run report.') + '</p>');
                    }
                },
                error: function (xhr) {
                    $('#wfpResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Run Report');
                }
            });
        });

        // Initialise from the first (active) report.
        applyFilterVisibility($('.wfp-report-item.active').first());
        syncPositions();
    });
</script>
@endsection
