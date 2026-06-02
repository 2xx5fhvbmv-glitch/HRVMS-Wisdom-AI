@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <span>People</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-liabilityOverPeopleEmp">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-themeGrayLight">
                            <h6>Total Estimated Liability {{ date('Y') }}</h6><strong>{!! Common::formatCurrency($estimated_liability, 'USD') !!}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-themeGrayLight">
                            <h6>Liability Reduction (YTD)</h6><strong>{!! Common::formatCurrency($current_liability, 'USD') !!}</strong>
                            <small class="d-block text-muted mt-1" style="font-size:11px;">
                                Burned down from the estimate so far this year.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-themeGrayLight position-relative" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#liabilityBreakdownModal" title="Click for breakdown">
                            <h6>
                                Remaining Liability
                                <i class="fa-solid fa-circle-info ms-1 text-primary" style="font-size:13px;"></i>
                            </h6>
                            <strong>{!! Common::formatCurrency($liability_reduction, 'USD') !!}</strong>
                            <small class="d-block text-muted mt-1" style="font-size:11px;">
                                Budget still on the books. Click for breakdown.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Breakdown modal: shows the four legs of Total Estimated
                     and the per-source components of Liability Reduction (YTD),
                     with the Remaining arithmetic at the bottom. Helps HR see
                     why the headline reads what it does instead of asking
                     why a single payroll run barely moves the value. --}}
                <div class="modal fade" id="liabilityBreakdownModal" tabindex="-1" aria-labelledby="liabilityBreakdownLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="liabilityBreakdownLabel">
                                    <i class="fa-solid fa-calculator me-2"></i>
                                    Remaining Liability — Breakdown
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    <strong>Remaining Liability</strong> = Total Estimated − Liability Reduction (YTD). It is what is <strong>still on the books</strong> for the rest of the year, not what has already been paid. With only one payroll run so far, almost the full year's budget is naturally still ahead — that's why this number is close to the full estimated total. Each completed payroll, work-permit renewal, insurance bill, etc. shifts dollars from <em>Remaining</em> into <em>Liability Reduction (YTD)</em>, and Remaining shrinks toward zero by year-end.
                                </p>

                                {{-- Total Estimated leg breakdown --}}
                                <h6 class="mt-3 mb-2">
                                    <i class="fa-solid fa-arrow-down me-1 text-success"></i>
                                    Total Estimated Liability
                                </h6>
                                <table class="table table-sm table-bordered mb-3" style="font-size:13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Source</th>
                                            <th class="text-end" style="width:160px;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $estTotal = 0; @endphp
                                        @foreach($estLegs as $leg)
                                            @php $estTotal += $leg['value']; @endphp
                                            <tr>
                                                <td>{{ $leg['label'] }}</td>
                                                <td class="text-end">{!! Common::formatCurrency($leg['value'], 'USD') !!}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-secondary fw-bold">
                                            <td>Total Estimated Liability</td>
                                            <td class="text-end">{!! Common::formatCurrency($estimated_liability, 'USD') !!}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                {{-- Liability Reduction (YTD) breakdown --}}
                                <h6 class="mt-3 mb-2">
                                    <i class="fa-solid fa-minus me-1 text-danger"></i>
                                    Liability Reduction (Year-to-Date spend)
                                </h6>
                                <table class="table table-sm table-bordered mb-3" style="font-size:13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Source</th>
                                            <th class="text-end" style="width:160px;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($currentLegs as $leg)
                                            <tr class="{{ $leg['value'] == 0 ? 'text-muted' : '' }}">
                                                <td>{{ $leg['label'] }}</td>
                                                <td class="text-end">{!! Common::formatCurrency($leg['value'], 'USD') !!}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-secondary fw-bold">
                                            <td>Liability Reduction (YTD)</td>
                                            <td class="text-end">{!! Common::formatCurrency($current_liability, 'USD') !!}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <small class="text-muted d-block mb-2">
                                    Note: Service Charge is excluded from Liability Reduction (YTD) — it's a guest-paid pass-through to employees, not a resort cost.
                                </small>

                                {{-- Final arithmetic --}}
                                <div class="alert alert-info py-2 mb-0" style="font-size:14px;">
                                    <div class="d-flex justify-content-between">
                                        <span>Total Estimated</span>
                                        <strong>{!! Common::formatCurrency($estimated_liability, 'USD') !!}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>− Liability Reduction (YTD)</span>
                                        <strong>{!! Common::formatCurrency($current_liability, 'USD') !!}</strong>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">= Remaining Liability</span>
                                        <strong class="text-primary">{!! Common::formatCurrency($liability_reduction, 'USD') !!}</strong>
                                    </div>
                                </div>

                                <small class="text-muted d-block mt-3" style="font-size:11px;">
                                    Tip: cost-template items like Food Cost and Pension are part of the Total Estimated but are not yet tracked as discrete spend events — they stay in the Remaining balance all year until the resort wires in actual-spend tracking.
                                </small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="liabilityOverPeopleEmp-tab">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#tabPane1"
                                type="button" role="tab" aria-controls="tabPane1" aria-selected="true">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="#tab2" data-bs-toggle="tab" data-bs-target="#tabPane2"
                                type="button" role="tab" aria-controls="tabPane2"
                                aria-selected="false">Employees</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#tabPane3"
                                type="button" role="tab" aria-controls="tabPane3" aria-selected="false">Estimation Vs
                                Actual</button>
                        </li>
                        <!-- <a href="{{route('people.liability.addCost')}}" class="btn btn-themeSkyblue btn-sm">+ Add Cost</a> -->
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="tabPane1" role="tabpanel" aria-labelledby="tab1" tabindex="0">
                            <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                                <div class="col-lg-6">
                                    <div class="bg-themeGrayLight h-100">
                                        <div class="card-title">
                                            <h3>Cost Distribution</h3>
                                        </div>
                                        <div class="row g-md-4 g-2 align-items-center">
                                            <div class="col-auto">
                                                <div class="costDistribution-chart">
                                                    <canvas id="myDoughnutChart" width="362" height="362"></canvas>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="row g-md-4 g-2 justify-content-center doughnut-labelTop">
                                                   @php
                                                    $backgroundColors = [
                                                        'bg-theme', 'bg-themeSkyblue', 'bg-themeSkyblueLightNew', 'bg-themeGray', 'bg-themeWarning',
                                                        'bg-themeSkyblueLight', 'bg-secondary', 'bg-danger', 'bg-success', 'bg-info', 'bg-primary',
                                                        'bg-themeGrayLight', 'bg-dark', 'bg-warning', 'bg-light', 'bg-danger-subtle'
                                                    ];
                                                    @endphp

                                                    @foreach(array_keys($chartData) as $index => $label)
                                                        <div class="col-xxl-6 col-lg-12 col-md-6 col-sm-12 col-auto">
                                                            <div class="doughnut-label">
                                                                <span class="{{ $backgroundColors[$index % count($backgroundColors)] }}"></span>{{ $label }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="bg-themeGrayLight h-100">
                                        <div class="card-title">
                                            <h3>Remaining Liability Trend</h3>
                                        </div>
                                        <div class="mb-md-4 mb-2"> <canvas id="liabilityTrendChart" width="802"
                                                height="293"></canvas>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade " id="tabPane2" role="tabpanel" aria-labelledby="#tab2" tabindex="0">
                            <div class="card-header">
                                <div class="row g-md-3 g-2 align-items-center">
                                    <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-7 col-sm-8 ">
                                        <div class="input-group">
                                            <input type="search" id="liabilityEmpSearch" class="form-control"
                                                placeholder="Search by Employee Name, ID or Manager Name" />
                                            <i class="fa-solid fa-search"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-2 col-lg-3 col-md-4 col-sm-4 col-6">
                                        <select id="liabilityEmpDeptFilter" class="form-select select2t-none" data-placeholder="By Department">
                                            <option value="">By Department</option>
                                            @if($resort_departments && count($resort_departments) > 0)
                                                @foreach($resort_departments as $department)
                                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse table-liabilityOverEmpPeopleEmp mb-1">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name</th>
                                            <th>Departments</th>
                                            <th>Position</th>
                                            <th>Nationality</th>
                                            <th>Salary</th>
                                            <th>OT</th>
                                            <th>Service Charge</th>
                                             {{-- Dynamically add Allowance columns --}}
                                            @foreach($allowanceTypes as $type)
                                                <th>{{ $type }}</th>
                                            @endforeach
                                            <th>Employee Allowance</th>
                                            <th>Insurance</th>
                                            <th>Work Permit</th>
                                            <th>Visa</th>
                                            <th>Medical</th>
                                            <th>Quota</th>
                                            <th>Recruitment Fee</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade " id="tabPane3" role="tabpanel" aria-labelledby="tab3" tabindex="0">
                            <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                                {{-- Estimation vs Actual comparison chart hidden by request — used hardcoded demo numbers, not real data. --}}
                                {{--
                                <div class="col-xxl-12 col-xl-12 col-lg-12">
                                    <div class="bg-themeGrayLight h-100">
                                        <div class="card-title">
                                            <h3>Estimation vs. Actual Comparison</h3>
                                        </div>
                                        <canvas id="myBarChart" width="503" height="298" class="mb-md-4 mb-2"></canvas>
                                        <div class="row g-md-4 g-2 justify-content-center">
                                            <div class="col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-theme"></span>Estimated
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeSkyblue"></span>Actual (YTD)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                --}}
                                <div class="col-xxl-12 col-xl-12 col-lg-12">
                                    <div class="bg-themeGrayLight h-100">
                                        <div class="table-responsive">
                                            {{-- Table is now driven by the chartData / per-category breakdown the
                                                 controller computes for this year. Estimated comes from the
                                                 resort_budget_costs cost templates per category; Actual is the
                                                 YTD spend the same controller already aggregated above for the
                                                 Liability Reduction Trend; Remaining = max(Estimated - Actual, 0).
                                                 The 5th "Cost Difference" column has been commented out by request. --}}
                                            {{-- $estVsActualRows is built server-side in
                                                 LiabilityEstimationController@index, so this
                                                 view never has to re-create the $budgetMatch
                                                 closure or reach for $monthlyVisa locals. --}}
                                            <table class="table table-lable table- mb-1">
                                                <thead>
                                                    <tr>
                                                        <th>Cost Category</th>
                                                        <th>Estimated Cost</th>
                                                        <th>Actual Cost</th>
                                                        <th>Remaining Liability</th>
                                                        {{-- Cost Difference column hidden by request --}}
                                                        {{-- <th>Liability Difference</th> --}}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($estVsActualRows as $row)
                                                        @php
                                                            $estimated = (float) ($row['estimated'] ?? 0);
                                                            $actual    = (float) ($row['actual']    ?? 0);
                                                            $remaining = max($estimated - $actual, 0);
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $row['label'] }}</td>
                                                            <td>{!! Common::formatCurrency($estimated, 'USD') !!}</td>
                                                            <td>{!! Common::formatCurrency($actual,    'USD') !!}</td>
                                                            <td>{!! Common::formatCurrency($remaining, 'USD') !!}</td>
                                                            {{-- <td><span class="text-{{ $estimated >= $actual ? 'success' : 'danger' }}">{!! Common::formatCurrency($estimated - $actual, 'USD') !!}</span></td> --}}
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    /* Liability "Employees" tab — the table has ~22 columns of currency
       cells. Default Bootstrap spacing made every $ value wrap (e.g. "$"
       on one line and "7,200.00" below) and header titles like
       "Male - Based / Airport Based Allowance" used 4+ lines of vertical
       space. Tightening cells so each row fits on a single visual line
       and the horizontal scroll feels intentional. */
    .table-liabilityOverEmpPeopleEmp th,
    .table-liabilityOverEmpPeopleEmp td {
        white-space: nowrap;
        padding: 6px 10px !important;
        font-size: 12.5px;
        vertical-align: middle;
    }
    .table-liabilityOverEmpPeopleEmp th {
        font-weight: 600;
        background-color: #f6f8f9;
    }
    /* Allow the multi-word allowance headers to wrap so the column
       itself doesn't get extremely wide. Body cells stay nowrap so the
       currency stays on one line. */
    .table-liabilityOverEmpPeopleEmp thead th {
        white-space: normal;
        min-width: 90px;
        max-width: 140px;
    }
    /* First couple of identity columns (ID, Name, Dept, Position,
       Nationality) get a touch more breathing room than currency cells. */
    .table-liabilityOverEmpPeopleEmp td:nth-child(-n+5) {
        padding-right: 12px !important;
    }
    .table-liabilityOverEmpPeopleEmp tbody td {
        border-color: #e9eef0;
    }
    /* Horizontal scrollbar stays usable on the wrapping table-responsive
       container that already wraps this table. */
    .table-responsive { -webkit-overflow-scrolling: touch; }
</style>
@endsection

@section('import-scripts')
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap"></script>
<script>
    let liabilityTable;
    const allowanceColumns = @json($allowanceTypes); // from controller


    $(document).ready(function () {
        // Initialize Select2 if needed
        $('.select2t-none').select2();

        // Initialize the DataTable
        initializeLiabilityDataTable();

        // Adjust columns after tab becomes visible
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('data-bs-target'); // ✅ correct way

            if (target === '#tabPane2' && liabilityTable) {
                setTimeout(() => {
                    liabilityTable.columns.adjust().draw();
                }, 300); // 2000ms is overkill; 300–500ms is usually enough
            }
        });

    });

    function initializeLiabilityDataTable() {
        if ($.fn.DataTable.isDataTable('.table-liabilityOverEmpPeopleEmp')) {
            $('.table-liabilityOverEmpPeopleEmp').DataTable().destroy();
        }

        const columns = [
            { data: 'Emp_id', name: 'Emp_id' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'department', name: 'department.name' },
            { data: 'position', name: 'position.name' },
            { data: 'nationality', name: 'nationality' },
            { data: 'salary', name: 'basic_salary', title: 'Salary' },
            { data: 'ot', name: 'ot', title: 'OT' },
            { data: 'service_charge', name: 'service_charge', title: 'Service Charge' },
        ];

        // Inject dynamic allowance columns
        allowanceColumns.forEach(type => {
            columns.push({
                data: type.toLowerCase().replace(/\s+/g, '_'), // example: food_allowance
                name: type.toLowerCase().replace(/\s+/g, '_'),
                title: type
            });
        });

        columns.push(
            { data: 'employee_allowance', name: 'employee_allowance', title: 'Employee Allowance' },
            { data: 'insurance', name: 'insurance', title: 'Insurance' },
            { data: 'work_permit', name: 'work_permit', title: 'Work Permit' },
            { data: 'visa', name: 'visa', title: 'Visa' },
            { data: 'medical', name: 'medical', title: 'Medical' },
            { data: 'quota', name: 'quota', title: 'Quota' },
            { data: 'recruitment_fee', name: 'recruitment_fee', title: 'Recruitment Fee' },
            { data: 'total', name: 'total', title: 'Total' },
            {
                data: 'details',
                orderable: false,
                searchable: false,
                title: 'Details',
                render: function (data, type, row) {
                    return `<button class="table-icon" data-bs-toggle="collapse" onclick="loadLiabilityDetails(this)" data-emp-id="${row.id}" aria-expanded="true"><i class="fa-solid fa-angle-down"></i></button>`;
                }
            }
        );

        liabilityTable = $('.table-liabilityOverEmpPeopleEmp').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("people.liabilities.data") }}',
                data: function (d) {
                    // Forward the in-header Search input + Department picker
                    // to the server. Without this the controls did nothing
                    // because the underlying DataTable had searching:false.
                    d.search_term   = $('#liabilityEmpSearch').val();
                    d.department_id = $('#liabilityEmpDeptFilter').val();
                }
            },
            columns: columns
        });

        // Re-run the table when the search box / department picker changes.
        var liabilitySearchTimer = null;
        $(document).off('input.liabSrch keyup.liabSrch', '#liabilityEmpSearch')
            .on('input.liabSrch keyup.liabSrch', '#liabilityEmpSearch', function () {
                clearTimeout(liabilitySearchTimer);
                liabilitySearchTimer = setTimeout(function () {
                    if (liabilityTable) liabilityTable.ajax.reload();
                }, 350);
            });
        $(document).off('change.liabDept', '#liabilityEmpDeptFilter')
            .on('change.liabDept', '#liabilityEmpDeptFilter', function () {
                if (liabilityTable) liabilityTable.ajax.reload();
            });
    }
    function loadLiabilityDetails(btn) {
        const empId = $(btn).data('emp-id');
        const tr = $(btn).closest('tr');

        // If rows already present, toggle visibility
        if ($(tr).next().hasClass('details-row')) {
            $(tr).nextUntil(':not(.details-row)').toggle(); // multiple expandable rows
            return;
        }

        // AJAX to load details
        const empLiabilityRoute = "{{ route('people.liabilities.emp-data', ['empId' => '__EMP_ID__']) }}";
        const finalUrl = empLiabilityRoute.replace('__EMP_ID__', empId);

        $.ajax({
            url: finalUrl,
            type: 'GET',
            success: function (data) {
                // Expecting HTML with multiple <tr class="details-row">
                $(tr).after(data.html);
            },
            error: function () {
                alert('Failed to load employee liability details.');
            }
        });
    }
</script>

<script type="module">
   // chartDataDisplay = $chartData values pre-converted to the resort's
   // current display currency (USD or MVR). $chartData itself stays USD
   // because it feeds the Est-vs-Actual table via formatCurrency. Using
   // the display version here makes the doughnut tooltip render in the
   // unit shown in the headline cards.
   const chartLabels = {!! json_encode(array_keys($chartDataDisplay ?? $chartData)) !!};
    const chartValues = {!! json_encode(array_values($chartDataDisplay ?? $chartData)) !!};

    // Auto-generate pastel colors if too many labels
    const baseColors = [
        '#014653', '#2EACB3', '#53CAFF', '#333333', '#EFB408',
        '#8DC9C9', '#AAAAAA', '#F3A6C9', '#5ba457', '#1abc9c',
        '#9b59b6', '#34495e', '#e67e22', '#d35400', '#7f8c8d'
    ];

    // If more labels than baseColors, auto-fill with soft generated colors
    while (baseColors.length < chartLabels.length) {
        baseColors.push(
            `hsl(${Math.floor(Math.random() * 360)}, 60%, 70%)`
        );
    }

    const ctx = document.getElementById('myDoughnutChart').getContext('2d');

    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDraw(chart) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    const total = dataset.data.reduce((acc, val) => acc + (isNaN(val) ? 0 : Number(val)), 0);
                    if (total > 0) {
                        meta.data.forEach((element, index) => {
                            const value = Number(dataset.data[index]);
                            if (!isNaN(value) && value > 0) {
                                const percentage = ((value / total) * 100).toFixed(0) + '%';
                                const pos = element.tooltipPosition();
                                ctx.fillStyle = '#ffffff';
                                ctx.font = '14px Poppins';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(percentage, pos.x, pos.y);
                            }
                        });
                    }
                }
            });
        }
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartValues,
                backgroundColor: baseColors.slice(0, chartLabels.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            layout: {
                padding: { top: 10, bottom: 10, left: 0, right: 0 }
            }
        },
        plugins: [doughnutLabelsInside]
    });

    // reductionDataDisplay = monthly remaining-liability values in the
    // resort's display currency. $reductionData stays USD for downstream
    // PHP consumers; the chart uses the display version so the y-axis
    // labels (also formatted with GetResortCurrencySymbol) match the data.
    const trendLabels = {!! json_encode($labels) !!};
    const trendData = {!! json_encode($reductionDataDisplay ?? $reductionData) !!};

    const trendCtx = document.getElementById('liabilityTrendChart').getContext('2d');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [{
                label: 'Remaining Liability',
                data: {!! json_encode($reductionDataDisplay ?? $reductionData) !!},
                borderColor: '#014653',
                backgroundColor: '#014653',
                fill: false,
                tension: 0.4,
                // Make points visible at a small size so the hover tooltip has
                // something to latch onto. The previous pointRadius:0 made
                // every month invisible, and combined with Chart.js's default
                // tooltip mode (intersect:true) the hover never fired.
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#014653',
            }]
        },
        options: {
            responsive: true,
            // Tooltip fires when the cursor is NEAR a point (any month),
            // not only when it lands exactly on the point. Without this
            // hovering anywhere on the chart did nothing.
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Remaining Liability ({{ Common::GetResortCurrencySymbol() }})'
                    },
                    grid: {
                        display: false
                    },
                    ticks: {
                        // Format y-axis labels with the system currency symbol
                        // (was hardcoded "USD" and broke on MVR resorts).
                        callback: function (value) {
                            return '{{ Common::GetResortCurrencySymbol() }} ' + Number(value).toLocaleString();
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    },
                    grid: {
                        display: false
                    },
                }
            },
            plugins: {
                legend: { display: true },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        // Hover label was returning "undefined  <value>" because
                        // `currencySymbol` was never defined in this scope.
                        // Pull the real numeric value off the dataset, format
                        // with thousand-separators, and prepend the system
                        // currency symbol.
                        label: function (context) {
                            var raw = context.parsed && context.parsed.y !== undefined
                                ? context.parsed.y
                                : context.raw;
                            return '{{ Common::GetResortCurrencySymbol() }} ' + Number(raw).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            }
        }
    });


    // Estimation vs Actual bar chart commented out — canvas was removed from
    // the tab markup and the chart had hardcoded demo data anyway.
    /*
    const ctc = document.getElementById('myBarChart').getContext('2d');
    const myBarChart = new Chart(ctc, {
        type: 'bar',
        data: {
            labels: ['Salaries', 'Overtime', 'Insurance', 'Recruitment', 'Recruitment'],
            datasets: [
                {
                    label: 'Estimated',
                    data: [18, 25, 14, 18, 25],
                    backgroundColor: '#014653',
                    borderColor: '#014653',
                    borderWidth: 1,
                    borderRadius: 3, barThickness: 26
                },
                {
                    label: 'Actual (YTD)',
                    data: [19, 23, 13, 19, 23],
                    backgroundColor: '#2EACB3',
                    borderColor: '#2EACB3',
                    borderWidth: 1,
                    borderRadius: 3, barThickness: 26
                },
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                tooltip: {
                    enabled: true, // Enable tooltips
                    callbacks: {
                        label: function (tooltipItem) {
                            // const datasetLabel = tooltipItem.dataset.label || '';
                            const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                            return formatAmount(value, 'USD'); // Custom tooltip format
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Show the x-axis border
                    }
                },
                y: {
                    beginAtZero: true, // Do not start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, ticks: {
                        stepSize: 5,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
    */
</script>
@endsection