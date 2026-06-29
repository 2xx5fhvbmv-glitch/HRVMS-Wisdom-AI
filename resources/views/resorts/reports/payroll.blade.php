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
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-title"><h3>Payroll Reports</h3></div>
                    <div class="card-body">
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

                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-theme" id="plRunBtn">Run Report</button>
                            </div>
                        </form>

                        <div id="plResults" class="table-responsive">
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
        var RUN_URL = '{{ route("resort.report.payroll.run") }}';
        var TOKEN   = '{{ csrf_token() }}';

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
            $('#plResults').html('<p class="text-muted text-center mb-0">Click <strong>Run Report</strong> to load.</p>');
        });

        $('#plFilterForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#plRunBtn').prop('disabled', true).text('Running…');
            $('#plResults').html('<p class="text-center mb-0"><i class="fa fa-spinner fa-spin"></i> Loading…</p>');

            $.ajax({
                url: RUN_URL,
                type: 'POST',
                data: {
                    _token: TOKEN,
                    report: $('#plReportKey').val(),
                    payroll: $('#plPayroll').val(),
                    from_payroll: $('#plFromPayroll').val(),
                    to_payroll: $('#plToPayroll').val(),
                    department: $('#plDepartment').val(),
                    year: $('#plYear').val(),
                    allowance_type: $('#plAllowanceType').val(),
                    deduction_type: $('#plDeductionType').val(),
                    bank: $('#plBank').val(),
                    settlement_status: $('#plSettlementStatus').val()
                },
                success: function (res) {
                    $('#plResults').html(res.success ? res.html
                        : '<p class="text-danger text-center mb-0">' + (res.message || 'Failed to run report.') + '</p>');
                },
                error: function (xhr) {
                    $('#plResults').html('<p class="text-danger text-center mb-0">' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to run report.') + '</p>');
                },
                complete: function () { $btn.prop('disabled', false).text('Run Report'); }
            });
        });

        applyFilterVisibility($('.pl-report-item.active').first());
    });
</script>
@endsection
