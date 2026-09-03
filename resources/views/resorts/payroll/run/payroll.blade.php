@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-payrollMain">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                            <div class="text-start">
                                <div class="dateRangeAb datepicker"  id="datapicker">
                                    <div>
                                        <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput" data-start-date="{{ $start_date ?? now()->startOfMonth()->format('Y-m-d') }}"
                                        data-end-date="{{ $end_date ?? now()->endOfMonth()->format('Y-m-d') }}">
                                    </div>
                                    <p id="startDate" class="d-none">Start Date:</p>
                                    <p id="endDate" class="d-none">End Date:</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="departmentFilter" class="form-select dd-native-select">
                                <option value="">All Departments</option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#departmentFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Departments</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($departments)
                                            @foreach($departments as $department)
                                            <div class="dd-item" role="option" data-value="{{ $department->id }}"><span class="dd-nm">{{ $department->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="positionFilter" class="form-select dd-native-select">
                                <option value="">All Positions</option>
                                @if($positions)
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#positionFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Positions</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($positions)
                                            @foreach($positions as $position)
                                            <div class="dd-item" role="option" data-value="{{ $position->id }}"><span class="dd-nm">{{ $position->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-3">
                    <div class="row g-xl-4 g-2 align-items-center">
                        <div class="col-auto">
                            @if(isset($payroll->start_date) && !empty($payroll->start_date))
                                <h6>{{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</h6>
                            @endif
                        </div>
                        @if(isset($payroll_id) && !empty($payroll_id))
                        <div class="col-auto">
                            <a href="{{ route('payroll.bankcashsheet.download', ['id' => $payroll_id]) }}" class="a-link">Cash And Bank Sheets</a>
                        </div>
                            <div class="col-auto">
                                <a href="{{ route('payroll.activity-log', ['payroll_id' => base64_encode($payroll_id)]) }}" class="btn payroll-btn-secondary">Activity Log</a>
                            </div>
                        <div class="col-auto">
                            <a href="{{ route('payroll.export.review', ['payrollId' => $payroll_id, 'type' => 'excel']) }}" class="btn payroll-btn-secondary btn-sm">
                                <i class="fa-solid fa-file-excel me-1"></i> Download Excel
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="payrollViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-view-tab="attendance" type="button">Time & Attendance</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-view-tab="overtime" type="button">Overtime</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-view-tab="earnings" type="button">Earnings</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-view-tab="deductions" type="button">Deductions</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-view-tab="summary" type="button">Summary</button>
                    </li>
                </ul>

                <!-- data-Table  -->
                <div class="table-responsive">
                    <table id="table-payroll" class="table table-payroll w-100">
                        <thead>
                            <tr id="table-payroll-header"></tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr id="table-payroll-footer" style="font-weight:bold;"></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts._dropdown_styles')
<style>
    .dateRangeAb{position: relative;}
    .dateRangeAb .daterangepicker {
        position: absolute !important;
        background-color: #fff;
        width: 100%;
    }
    .dateRangeAb .form-control {
        background-image: url('{{ URL::asset("resorts_assets/images/calendar.svg") }}');
        background-position: right 10px center;
        background-repeat: no-repeat;
    }
    th { white-space: nowrap!important; }

    /* Tab-based column visibility via CSS — no DataTables redraw needed */
    #table-payroll .grp-attendance,
    #table-payroll .grp-overtime,
    #table-payroll .grp-earnings,
    #table-payroll .grp-deductions,
    #table-payroll .grp-summary { display: none; }

    #table-payroll.tab-attendance .grp-attendance { display: table-cell; }
    #table-payroll.tab-overtime .grp-overtime { display: table-cell; }
    #table-payroll.tab-earnings .grp-earnings { display: table-cell; }
    #table-payroll.tab-deductions .grp-deductions { display: table-cell; }
    #table-payroll.tab-summary .grp-summary { display: table-cell; }
</style>
@endsection

@section('import-scripts')
<script>
    var currentTab = 'attendance';

    $(document).ready(function () {
        // Set default tab class
        $('#table-payroll').addClass('tab-attendance');

        fetchDynamicColumns();

        let startDate = moment($("#hiddenInput").data('start-date'), "YYYY-MM-DD");
        let endDate = moment($("#hiddenInput").data('end-date'), "YYYY-MM-DD");

        if (startDate && endDate) {
            initializeDateRange(startDate, endDate);
        } else {
            initializeDateRange(moment().subtract(1, 'months').startOf('month'), moment().subtract(1, 'months').endOf('month'));
        }

        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            $("#startDate").text("Start Date: " + picker.startDate.format("DD-MM-YYYY"));
            $("#endDate").text("End Date: " + picker.endDate.format("DD-MM-YYYY"));
            $('#table-payroll').DataTable().ajax.reload();
        });

        $("#searchInput, #departmentFilter, #positionFilter").on("input change", debounce(function () {
            $('#table-payroll').DataTable().ajax.reload();
        }, 300));

        // Tab switching — CSS only, no DataTables API calls
        $(document).on('click', '#payrollViewTabs .nav-link', function() {
            $('#payrollViewTabs .nav-link').removeClass('active');
            $(this).addClass('active');
            currentTab = $(this).data('view-tab');
            $('#table-payroll').removeClass('tab-attendance tab-overtime tab-earnings tab-deductions tab-summary')
                .addClass('tab-' + currentTab);
        });
    });

    function initializeDateRange(start, end) {
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: start,
            endDate: end,
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: { format: "DD-MM-YYYY" }
        });
    }

    let dynamicURL = "{{ $payroll_id ? route('payroll.getColumns', ['payroll_id' => $payroll_id]) : '' }}";

    function fetchDynamicColumns() {
        if (!dynamicURL) {
            $("#table-payroll tbody").html('<tr><td colspan="6" class="text-center">No payroll found</td></tr>');
            return;
        }

        $.ajax({
            url: dynamicURL,
            method: "GET",
            success: function (response) {
                if (response.success) {
                    var dynamicCols = response.columns || [];
                    var cs = currencySymbol;

                    function fmtCol(data) {
                        if (data === null || data === undefined || data === '') return cs + '0.00';
                        var num = parseFloat(String(data).replace(/,/g, '')) || 0;
                        return cs + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }

                    // Build column definitions matching step 6 review headings
                    var tableColumns = [
                        // Base (always visible)
                        { data: 'Emp_id', name: 'Emp_id', title: 'ID', className: '' },
                        { data: 'employee_name', name: 'employee_name', title: 'Employee Name', className: '' },
                        { data: 'position', name: 'position', title: 'Position', className: '' },

                        // Attendance: Present, Absent, Day Off, Other Leaves
                        { data: 'present_days', name: 'present_days', title: 'Present', className: 'grp-attendance' },
                        { data: 'absent_days', name: 'absent_days', title: 'Absent', className: 'grp-attendance' },
                        { data: 'day_off', name: 'day_off', title: 'Day Off', className: 'grp-attendance' },
                        { data: 'leave_types', name: 'leave_types', title: 'Other Leaves', className: 'grp-attendance' },

                        // Overtime: Regular OT, Friday OT, Holiday OT, Total OT Pay
                        { data: 'regular_ot_pay', name: 'regular_ot_pay', title: 'Regular OT', render: fmtCol, className: 'grp-overtime' },
                        { data: 'friday_ot_pay', name: 'friday_ot_pay', title: 'Friday OT', render: fmtCol, className: 'grp-overtime' },
                        { data: 'holiday_ot_pay', name: 'holiday_ot_pay', title: 'Holiday OT', render: fmtCol, className: 'grp-overtime' },
                        { data: 'total_OTPay', name: 'total_OTPay', title: 'Total OT Pay', render: fmtCol, className: 'grp-overtime' },

                        // Earnings: Service Charge, Basic Earned, [Allowances...], Total Earnings
                        { data: 'service_charge', name: 'service_charge', title: 'Service Charge', render: fmtCol, className: 'grp-earnings' },
                        { data: 'earned_salary', name: 'earned_salary', title: 'Basic Earned', render: fmtCol, className: 'grp-earnings' },
                    ];

                    // Dynamic allowance columns (earnings)
                    dynamicCols.forEach(function(col) {
                        tableColumns.push({ data: col, name: col, title: col, render: fmtCol, className: 'grp-earnings' });
                    });

                    // KPI Bonus (from Performance > Bonus Configuration — matched by rank + payroll month/year)
                    tableColumns.push(
                        { data: 'kpi_bonus', name: 'kpi_bonus', title: 'KPI Bonus', render: fmtCol, className: 'grp-earnings' }
                    );

                    // Total Earnings (shown in earnings, deductions, summary)
                    tableColumns.push(
                        { data: 'total_pay', name: 'total_pay', title: 'Total Earnings', render: fmtCol, className: 'grp-earnings grp-summary' }
                    );

                    // Deductions: Attendance, City Ledger, Staff Shop, Pension, EWT, Other, Total Deductions
                    tableColumns.push(
                        { data: 'attendance_deduction', name: 'attendance_deduction', title: 'Attendance', render: fmtCol, className: 'grp-deductions' },
                        { data: 'city_ledger', name: 'city_ledger', title: 'City Ledger', render: fmtCol, className: 'grp-deductions' },
                        { data: 'staff_shop', name: 'staff_shop', title: 'Staff Shop', render: fmtCol, className: 'grp-deductions' },
                        { data: 'pension', name: 'pension', title: 'Pension', render: fmtCol, className: 'grp-deductions' },
                        { data: 'ewt', name: 'ewt', title: 'EWT', render: fmtCol, className: 'grp-deductions' },
                        { data: 'other_deduction', name: 'other_deduction', title: 'Other', render: fmtCol, className: 'grp-deductions' },
                        { data: 'deductions', name: 'deductions', title: 'Total Deductions', render: fmtCol, className: 'grp-deductions grp-ded-sum' },
                        { data: 'net_pay', name: 'net_pay', title: 'Net Salary', render: function(data) { return '<strong>' + fmtCol(data) + '</strong>'; }, className: 'grp-summary' }
                    );

                    // Build header with matching CSS classes
                    var headerHtml = '';
                    tableColumns.forEach(function(col) {
                        headerHtml += '<th class="text-nowrap ' + (col.className || '') + '">' + col.title + '</th>';
                    });
                    $('#table-payroll-header').html(headerHtml);
                    $('#table-payroll-footer').html(headerHtml);

                    initializeDataTable(tableColumns);
                }
            },
            error: function () {
                $("#table-payroll tbody").html('<tr><td colspan="6" class="text-center">Error loading columns</td></tr>');
            }
        });
    }

    let payrollURL = "{{ $payroll_id ? route('payroll.getData', ['payroll_id' => $payroll_id]) : '' }}";

    function initializeDataTable(tableColumns) {
        if (!payrollURL) return;

        if ($.fn.DataTable.isDataTable("#table-payroll")) {
            $("#table-payroll").DataTable().destroy();
        }

        var dtTable = $("#table-payroll").DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();
                var cs = currencySymbol;
                var json = api.ajax.json();
                var totals = (json && json.totals) ? json.totals : {};

                api.columns().every(function(colIdx) {
                    var col = tableColumns[colIdx];
                    var footerNode = this.footer();
                    if (!footerNode || !col) return;

                    var cls = col.className || '';
                    var colName = col.name || '';

                    if (!cls) {
                        $(footerNode).html(colIdx === 0 ? 'Total' : '');
                    } else if (colName === 'leave_types') {
                        $(footerNode).html('-');
                    } else if (colName === 'day_off' || cls.indexOf('grp-attendance') !== -1) {
                        var val = totals[colName] !== undefined ? totals[colName] : 0;
                        $(footerNode).html(val);
                    } else {
                        var val = totals[colName] !== undefined ? parseFloat(totals[colName]) : 0;
                        $(footerNode).html(cs + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    }

                    $(footerNode).attr('class', cls);
                });
            },
            ajax: {
                url: payrollURL,
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.department = $('#departmentFilter').val();
                    d.position = $('#positionFilter').val();
                    var startDate = $("#hiddenInput").data('daterangepicker').startDate.format("YYYY-MM-DD");
                    var endDate = $("#hiddenInput").data('daterangepicker').endDate.format("YYYY-MM-DD");
                    d.start_date = startDate;
                    d.end_date = endDate;
                },
                dataSrc: function (json) {
                    if (json.data.length === 0) {
                        $("#table-payroll tbody").html('<tr><td colspan="' + tableColumns.length + '" class="text-center">No data available</td></tr>');
                    }
                    return json.data;
                },
                error: function (xhr, error, code) {
                    console.error("AJAX Error:", error);
                }
            },
            columns: tableColumns
        });
    }

    function debounce(func, delay) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, arguments), delay);
        };
    }
</script>
@include('resorts._dropdown_script')
@endsection
