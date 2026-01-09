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
                                        <!-- Hidden input field to attach the calendar to -->
                                        <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput" data-start-date="{{ $start_date ?? now()->startOfMonth()->format('Y-m-d') }}"
                                        data-end-date="{{ $end_date ?? now()->endOfMonth()->format('Y-m-d') }}">
                                    </div>
                                    <p id="startDate" class="d-none">Start Date:</p>
                                    <p id="endDate" class="d-none">End Date:</p>
                                </div>  
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="departmentFilter" class="form-select select2t-none">
                                <option value="">All Departments</option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="positionFilter" class="form-select select2t-none">
                                <option value="">All Positions</option>
                                <!-- Example: populate dynamically or statically -->
                                @if($positions)
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <!-- <div class="col-auto ms-auto">
                            <div class="d-flex align-items-center">
                                <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">Rufiyaa</label>
                                <div class="form-check form-switch form-switchTheme">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="flexSwitchCheckDefault">
                                    <label class="form-check-label" for="flexSwitchCheckDefault">USD</label>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-3">
                    <div class="row g-xl-4 g-2 align-items-center">
                        <div class="col-auto">
                            @if(isset($payroll->start_date) && !empty($payroll->start_date))
                                <h6>{{ \Carbon\Carbon::parse($payroll->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($payroll->end_date)->format('d M Y') }}</h6>
                            @endif
                        </div>
                        <div class="col-auto ms-auto"><a href="#" class="a-link" id="notesBtn">Notes</a></div>
                        <div class="col-auto">
                            <a href="{{ route('payroll.bankcashsheet.download', ['id' => $payroll_id]) }}" class="a-link">Cash And Bank Sheets</a>
                        </div>
                        @if(isset($payroll_id) && !empty($payroll_id))
                            <div class="col-auto"> 
                                <a href="{{ route('payroll.activity-log', ['payroll_id' => base64_encode($payroll_id)]) }}" class="btn btn-themeSkyblue">Activity Log</a>
                            </div>
                        @endif
                        <div class="col-auto"> <button id="btn-download" class="btn btn-themeBlue">Download</button> </div>
                    </div>
                </div>

                <!-- data-Table  -->
                <table id="table-payroll" class="table table-payroll w-100">
                    <thead>
                        <tr id="table-payroll-header">
                            <th class="text-nowrap">ID</th>
                            <th class="text-nowrap">Name</th>
                            <th class="text-nowrap">Department</th>
                            <th class="text-nowrap">Position</th>
                            <th class="text-nowrap">Hire Date</th>
                            <th class="text-nowrap">No. of Days</th>
                            <th class="text-nowrap">Total OT Amount</th>
                            <th class="text-nowrap">Service Charge</th>
                            <th class="text-nowrap">Basic Pay</th>
                            <th class="text-nowrap">Earned Salary</th>
                            <!-- Dynamic Columns Will be Inserted Here -->
                            <th class="text-nowrap">Total Allowances</th>
                            <th class="text-nowrap">Total Earnings</th>
                            <th class="text-nowrap">Deduction</th>
                            <th class="text-nowrap">Net Pay</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="payrollModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payrollModalLabel">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">Loading...</div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
<style>
    .dateRangeAb{position: relative;}
    .dateRangeAb .daterangepicker {
        position: absolute !important;
        background-color: #fff;
        width: 100%;
        /* min-width: 350px; */
    }
    .dateRangeAb .form-control {
        background-image: url('{{ URL::asset("resorts_assets/images/calendar.svg") }}');
        background-position: right 10px center;
        background-repeat: no-repeat;
    }
    th{
        white-space: nowrap!important;
    }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.select2t-none').select2();
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

        $("#notesBtn").on("click", function (e) {
            e.preventDefault();
            openNotesModal();
        });

        $("#searchInput, #departmentFilter, #positionFilter").on("input change", debounce(function () {
            $('#table-payroll').DataTable().ajax.reload();
        }, 300));

        $('#btn-download').click(function (e) {
            e.preventDefault();
            $('#btn-download').prop('disabled', true);
            handlePayrollDownload();
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
            locale: {
                format: "DD-MM-YYYY"
            }
        });
    }

    let dynamicURL = "{{ $payroll_id ? route('payroll.getColumns', ['payroll_id' => $payroll_id]) : '' }}";

    function fetchDynamicColumns() {
        if (!dynamicURL) {
            $("#table-payroll tbody").html('<tr><td colspan="15" class="text-center">No payroll found</td></tr>');
            return;
        }

        $.ajax({
            url: dynamicURL,
            method: "GET",
            success: function (response) {
                if (response.success) {
                    let dynamicColumns = response.columns.map(col => ({ data: col, name: col }));
                    let tableColumns = [
                        { data: 'Emp_id', name: 'Emp_id' },
                        { data: 'employee_name', name: 'employee_name' },
                        { data: 'department', name: 'department' },
                        { data: 'position', name: 'position' },
                        { data: 'hire_date', name: 'hire_date' },
                        { data: 'present_days', name: 'present_days' },
                        { data: 'total_OTPay', name: 'total_OTPay' },
                        { data: 'service_charge', name: 'service_charge' },
                        { data: 'basic_pay', name: 'basic_pay' },
                        { data: 'earned_salary', name: 'earned_salary' },
                        ...dynamicColumns,
                        { data: 'total_allowance', name: 'total_allowance' },
                        { data: 'total_pay', name: 'total_pay' },
                        { data: 'deductions', name: 'deductions' },
                        { data: 'net_pay', name: 'net_pay' },
                    ];

                    updateTableHeader(response.columns);
                    initializeDataTable(tableColumns);
                }
            },
            error: function () {
                $("#table-payroll tbody").html('<tr><td colspan="15" class="text-center">Error loading columns</td></tr>');
            }
        });
    }

    function updateTableHeader(dynamicColumnNames) {
        let headerRow = $("#table-payroll-header");
        headerRow.find("th.dynamic-column").remove();

        let insertAfterIndex = 9; // 0-based index, 9th column = Earned Salary
        dynamicColumnNames.forEach(col => {
            $('<th class="dynamic-column">' + col + '</th>').insertAfter(headerRow.children().eq(insertAfterIndex++));
        });
    }

    let payrollURL = "{{ $payroll_id ? route('payroll.getData', ['payroll_id' => $payroll_id]) : '' }}";

    function initializeDataTable(tableColumns) {
        if (!payrollURL) {
            console.warn("Payroll URL is missing. Skipping DataTable initialization.");
            return;
        }

        if ($.fn.DataTable.isDataTable("#table-payroll")) {
            $("#table-payroll").DataTable().destroy();
        }

        $("#table-payroll").DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
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
                    $("#table-payroll tbody").html('<tr><td colspan="' + tableColumns.length + '" class="text-center">Error loading data</td></tr>');
                }
            },
            columns: tableColumns
        });
    }

    let payrollnotesURL = @if($payroll_id)
        "{{ route('payroll.getNotes', ['payroll_id' => $payroll_id]) }}"
    @else
        "#"
    @endif;

    function openNotesModal() {
        $("#payrollModalLabel").text("Employee Notes");
        $("#modalContent").html("Loading...");

        $.ajax({
            url: payrollnotesURL,
            method: "GET",
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    let tableHtml = `<table class="table table-bordered">
                        <thead><tr><th>Employee Name</th><th>Notes</th></tr></thead><tbody>`;
                    response.data.forEach(note => {
                        tableHtml += `<tr><td>${note.employee_name}</td><td>${note.notes}</td></tr>`;
                    });
                    tableHtml += `</tbody></table>`;
                    $("#modalContent").html(tableHtml);
                } else {
                    $("#modalContent").html("<p>No Notes Available.</p>");
                }
            }
        });

        $("#payrollModal").modal("show");
    }

    function handlePayrollDownload() {
        let payrollId = "{{ $payroll_id ?? '' }}";
        if (!payrollId) return;

        let downloadUrl = "{{ $payroll_id ? route('payroll.download', ['payroll_id' => $payroll_id]) : '#' }}";
        const searchTerm = $('#searchInput').val();
        const department = $('#departmentFilter').val();
        const position = $('#positionFilter').val();
        const dateRange = $('#hiddenInput').val();

        const start_date = dateRange ? dateRange.split(' - ')[0] : '';
        const end_date = dateRange ? dateRange.split(' - ')[1] : '';

        $.ajax({
            url: downloadUrl,
            method: 'GET',
            data: {
                searchTerm,
                department,
                position,
                start_date,
                end_date
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (response) {
                const blob = new Blob([response], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Payroll_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                document.body.appendChild(a);
                a.click();

                $("#btn-download").prop('disabled', false);
                window.URL.revokeObjectURL(url);
            },
            error: function (xhr, status, error) {
                console.error('Download failed:', error);
                alert('Download failed. Please try again.');
            }
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
@endsection