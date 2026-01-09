@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto"><a href="{{route('payroll.payslip.index')}}" class="btn btn-white @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">Share Payslips</a></div>
                <div class="col-auto"><a href="{{route('payroll.run')}}" class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.create')) == false) d-none @endif">Run Payroll</a></div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employees</p>
                            <strong>{{$total_employees}}</strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Paid Employees</p>
                            <strong>{{$total_paid_employees}} <small>/{{$total_employees}}</small></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-500">Last Payroll</p>
                            <strong>
                                ${{ number_format($lastPayroll->total_payroll ?? 0, 2) }}
                            </strong>
                        </div>
                        <div class="text-end">
                            <span>{{ $lastPayroll ? \Carbon\Carbon::parse($lastPayroll->draft_date)->format('d M Y') : '-' }}</span><br>
                            <span class="badge badge-themeSuccess">
                                {{ ucfirst($lastPayroll->status ?? 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0 fw-500">Upcoming Payroll</p>
                            <strong>
                                ${{ number_format($upcomingPayroll->total_payroll ?? 0, 2) }}
                            </strong>
                        </div>
                        @if($upcomingPayroll)
                            <div class="text-end">
                                <span>{{ \Carbon\Carbon::parse($upcomingPayroll->draft_date)->format('d M Y') ?? '-' }}</span><br>
                                <span class="badge badge-themeWarning">
                                    {{ ucfirst($upcomingPayroll->status ?? 'Pending') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-serviceCharges">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Service Charges</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWiseServichCharges" aria-label="Default select example">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 3; $i++) {
                                            $startYear = $currentYear - $i;
                                            $endYear = $startYear + 1;
                                            echo "<option value=\"$startYear\"";
                                            if ($i == 0)
                                            {
                                                echo " selected";
                                            }
                                            echo ">Jan $startYear - Dec $startYear</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 align-items-center">
                        <div class="col-md-6">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Payroll overview</h3>
                    </div>
                    <div class="mb-xl-4 mb-3">
                        <label for="month" class="form-label">MONTH</label>
                        <select class="form-select select2t-none" id="month" aria-label="Default select example">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-xl-4 mb-3 pb-1 pb-xxl-3">
                        <label for="year" class="form-label">YEAR</label>
                        <select class="form-select select2t-none" id="year" aria-label="Default select example">
                            @foreach(range(now()->year - 5, now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="#" class="btn btn-themeBlue @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif" id="viewPayroll">View Payroll</a>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-2 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Payroll Expenses</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWisePayrollExpense" id="yearFilter" aria-label="Default select example">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 3; $i++) {
                                            $startYear = $currentYear - $i;
                                            $endYear = $startYear + 1;
                                            echo "<option value=\"$startYear\"";
                                            if ($i == 0)
                                            {
                                                echo " selected";
                                            }
                                            echo ">Jan $startYear - Dec $startYear</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="myStackedBarChart" width="363" height="309" class="mb-3"></canvas>
                    <div class="row g-2 ">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Payroll Cost
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeSkyblue"></span>OT Cost
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeWarning"></span>Service Charge
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-wiINsightPayroll" id="card-wiINsightPayroll" >
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">WI Insight's</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img">
                                <img src="assets/images/wisdom-ai-small.svg" alt="image">
                            </div>
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                </p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

              <div class="col-xl-3 col-lg-3 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-payrollDis">
                    <div class="card-title">
                        <h3>Payroll Distributions</h3>
                    </div>
                    <div class="text-center">
                        <div class="payrollDistr-chart">
                            <canvas id="myDoughnutChartPayroll" width="206" height="206" class="mb-3"></canvas>
                        </div>
                    </div>
                    <div class="row g-2 justify-content-center" id="payroll-distribution-container">
                        <div class="col-auto">
                            <div class="doughnut-label" id="cash_payment">
                                <span class="bg-theme"></span>Cash Payments <br>$<span id="cashPaymentsText">0.00</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label"  id="bank_TransfersText" >
                                <span class="bg-themeLightBlue"></span>Bank Transfers <br>$<span id="bankTransfersText">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-xl-3 col-md-6">
                <div class="card  card-activityLog" id="card-activityLog">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Activity Log</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                        <div class="leaveUser-block">
                            <div class="date-block bg">DEC <h5>01</h5> Mon</div>
                            <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting
                                industry ipsum.</p>
                        </div>
                    </div>
                </div>
            </div> -->


            <div class="col-lg-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.run',config('settings.resort_permissions.view')) == false) d-none @endif">                
                <div class="comparison-wrapper">
                    @include('resorts.renderfiles.payroll_comparison_card', ['payrollData' => $payrollData])
                </div>
            </div>
            

          

            <div class="col-xl-12 col-lg-12">
                <div class="card">
                    <div class="card-title">
                        <h3>Distribution by Department</h3>
                    </div>
                    <canvas id="myTreemap" width="1243" height="400" class="mb-2"></canvas>
                    <div id="department-labels" class="row g-2">
                        <!-- Dynamic department labels will be injected here -->
                    </div> <!-- Labels will be inserted here dynamically -->
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-2 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">OT Trend</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                   <select id="yearSelect" class="form-select" style="width: auto; display: inline-block;">
                                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="myLineChart" width="365" height="326"></canvas>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('payroll.pension.index', config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title ">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Pension</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWisePensionData" aria-label="Default select example">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($i = 0; $i < 3; $i++) {
                                            $startYear = $currentYear - $i;
                                            $endYear = $startYear + 1;
                                            echo "<option value=\"$startYear\"";
                                            if ($i == 0)
                                            {
                                                echo " selected";
                                            }
                                            echo ">Jan $startYear - Dec $startYear</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="pension" width="365" height="293" class="mb-2"></canvas>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Employee
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Employer
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-title">
                        <h3>Tax</h3>
                    </div>
                    <div class="taxChart-block">
                        <canvas id="taxChart" width="328" height="328"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-title">
                        <h3>Budget Comparison</h3>
                    </div>
                    <canvas id="budgetComp" width="365" height="293" class="mb-2"></canvas>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Budgeted Amount
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Actual Amount
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="assign-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Payroll Components</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="basic_salary" class="form-label">Total Basic Salary</label>
                    <input type="text" id="basic_salary" class="form-control" value="54,415.20">
                </div>
                <div class="mb-3">
                    <label for="service_charge" class="form-label">Service Charge Values</label>
                    <input type="text" id="service_charge" class="form-control" value="145.00">
                </div>
                <div class="mb-3">
                    <label for="normal_ot" class="form-label">Normal OT</label>
                    <input type="text" id="normal_ot" class="form-control" value="$1,110.00 (120 Hrs)">
                </div>
                <div>
                    <label for="holiday_ot" class="form-label">Holiday OT</label>
                    <input type="text" id="holiday_ot" class="form-control" value="$142.00 (70Hrs)">
                </div>

            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    document.getElementById('viewPayroll').addEventListener('click', function () {
        let selectedMonth = document.getElementById('month').value;
        let selectedYear = document.getElementById('year').value;

        let viewPayrollUrl = "{{ route('payroll.view.all') }}";

        // Construct the first and last date of the selected month
        let startDate = moment(`${selectedYear}-${selectedMonth}-01`);
        let endDate = moment(startDate).endOf('month');

        // Store selected date range in hidden input field
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: startDate,
            endDate: endDate,
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY", // Ensure consistent format
            }
        });

        // Redirect to payroll view with selected month and year
        window.location.href = `${viewPayrollUrl}?month=${selectedMonth}&year=${selectedYear}`;
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap"></script>
<script type="text/javascript">
    // tooltip 
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
      
    //    equal heigth js 
  
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide chart labels
        document.querySelectorAll('.chartImg-block').forEach(block => {
            block.addEventListener('click', function() {
                const chartLabelBlock = this.closest('.row').querySelector('.chartLabel-block');
                chartLabelBlock.classList.remove('d-none');
            });
        });

        // Equal heights functionality
        function equalizeHeights() {
            const block1 = document.getElementById('card-salaryCalc');
            const block2_1 = document.getElementById('card-wiINsightPayroll');
            const block2_2 = document.getElementById('card-activityLog');

            if (block1 && block2_1 && block2_2) {
                const block1Height = block1.offsetHeight;
                block2_1.style.height = block1Height + 'px';
                block2_2.style.height = block1Height + 'px';
            }
        }

        // Initialize equal heights
        equalizeHeights();

        // Adjust heights on window resize
        window.addEventListener('resize', equalizeHeights);

      
    });

</script>
<script type="module">
    window.initializeProgressBars = function() {
        const radius = 54;
        const circumference = 2 * Math.PI * radius;

        const progressContainers = document.querySelectorAll('.progress-container');
        progressContainers.forEach(container => {
            const progressCircle = container.querySelector('.progress');
            const progressValue = container.getAttribute('data-progress');
            const offset = circumference - (progressValue / 100 * circumference);

            progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;

            setTimeout(() => {
                progressCircle.style.strokeDashoffset = offset;
            }, 100);
        });
    }
    // Global variable to store the chart instance
    let myDoughnutChart = null;
    //Service Charges Chart
    const ctz = document.getElementById('myDoughnutChart').getContext('2d');
    const doughnutLabelsInsideN = {
        id: 'doughnutLabelsInsideN',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';
                        var position = element.tooltipPosition();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'normal 18px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    const centerText = {
        id: 'centerText',
        afterDraw: function (chart) {
            const width = chart.width;
            const height = chart.height;
            const ctx = chart.ctx;

            ctx.restore();

            // Calculate total dynamically from the chart data
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);

            // Format the total as a currency value
            const formattedTotal = '$' + total.toLocaleString();

            // Text configuration
            ctx.textBaseline = 'middle';
            ctx.textAlign = 'center';

            // Total number
            ctx.font = '500 22px Poppins';
            ctx.fillStyle = '#222222';
            // ctx.fillText('$' + total, width / 2, height / 2 - 15);
            ctx.fillText(formattedTotal, width / 2, height / 2 - 15);

            // "Total" label
            ctx.font = '500 13px Poppins';
            ctx.fillStyle = '#222222';
            ctx.fillText('Avg', width / 2, height / 2 + 15);

            ctx.save();
        }
    };

    // Function to fetch and update the chart
    function GetServiceChargeChart() {
        $.ajax({
            url: "{{ route('chart.service-charges') }}", // Replace with your actual route
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "YearWiseServichCharges": $(".YearWiseServichCharges").val()
            },
            success: function (response) {
                console.log(response);
                const data = response.data;
                const total = response.total;
                const labels = data.map(item => item.label);
                const serviceCharges = data.map(item => item.service_charge);
                const serviceChargespercentage = data.map(item => item.percentage);
                const colors = ['#014653', '#53CAFF', '#EFB408', '#50B9BF', '#333333', '#8DC9C9'];

                 // Check if the chart exists and destroy it
                if (myDoughnutChart !== null && typeof myDoughnutChart.destroy === 'function') {
                    myDoughnutChart.destroy();
                }
                // Update the chart
                myDoughnutChart = new Chart(document.getElementById('myDoughnutChart'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: serviceChargespercentage,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        layout: {
                            padding: {
                                top: 10,
                                bottom: 10,
                                left: 0,
                                right: 0
                            }
                        }
                    },
                    plugins: [doughnutLabelsInsideN, centerText] // Attach the plugin to this chart only
                });
                // Update the side labels
                const labelContainer = document.querySelector('.row.g-2');
                let labelsHTML = '';
                data.forEach((item, index) => {
                    labelsHTML += `
                        <div class="col-6">
                            <div class="doughnut-label">
                                <span style="background-color: ${colors[index]}"></span>${item.label} <br>$${item.service_charge}
                            </div>
                        </div>
                    `;
                });
                // Add total row
                labelsHTML += `
                    <div class="fw-500">Total: $${total}</div>
                `;

                // Insert into the DOM
                labelContainer.innerHTML = labelsHTML;
            },
            error: function (xhr) {
                console.error("Failed to fetch chart data", xhr);
            }
        });
    }

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWiseServichCharges", function () {
        GetServiceChargeChart();
    });

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWisePensionData", function () {
        fetchPensionChartData();
    });

    // Initial chart load
    GetServiceChargeChart();

    fetchPayrollData();

    fetchDepartmentDistribution();

    fetchPensionChartData();

    renderEwtTaxChart();

        $('#monthSelector').trigger('change');


    const defaultYear = $('#yearSelect').val();
    fetchOtTrendChart(defaultYear);

    // On year change
    $('#yearSelect').on('change', function () {
        const selectedYear = $(this).val();
        fetchOtTrendChart(selectedYear);
    });    // Rendering the chart

    function fetchPayrollData() {
        $.ajax({
            url: "{{ route('payroll.distribution') }}", // Replace with your actual route
            type: "GET",
            success: function (response) {
                // console.log(response);

                // Extracting cash & bank payment data
                const cashPayments = Number(response.cashPayments) || 0;
                const bankTransfers = Number(response.bankTransfers) || 0;
                // console.log("Extracted Data - Cash:", cashPayments, "Bank:", bankTransfers);

                // Calculate total payroll distribution
                const totalPayroll = cashPayments + bankTransfers;
                // console.log("Total Payroll:", totalPayroll);

                const cashPercentage = totalPayroll > 0 ? ((cashPayments / totalPayroll) * 100).toFixed(2) : 0;
                const bankPercentage = totalPayroll > 0 ? ((bankTransfers / totalPayroll) * 100).toFixed(2) : 0;
                // console.log("Calculated Percentages - Cash %:", cashPercentage, "Bank %:", bankPercentage);
                // Update the payroll distribution chart (Cash vs. Bank)
                updateDoughnutChartPayroll(cashPercentage, bankPercentage);

                // Update the labels under the chart
                const labelContainer = document.querySelector('#payroll-distribution-container');
                labelContainer.innerHTML = `
                    <div class="col-auto">
                        <div class="doughnut-label">
                            <span class="bg-theme"></span>Cash Payments <br>$${cashPayments.toLocaleString()}
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="doughnut-label">
                            <span class="bg-themeLightBlue"></span>Bank Transfers <br>$${bankTransfers.toLocaleString()}
                        </div>
                    </div>
                `;
            },
            error: function (xhr) {
                console.error("Failed to fetch payroll chart data", xhr);
            }
        });
    }

    // 🟢 Store chart instance globally
    var myDoughnutChartPayroll;

    // 🟢 Payroll Distribution Chart (Cash Payments vs. Bank Transfers)
    function updateDoughnutChartPayroll(cashPercentage, bankPercentage) {
        // console.log("Cash %:", cashPercentage, "Bank %:", bankPercentage);

        const ctxPayroll = document.getElementById('myDoughnutChartPayroll').getContext('2d');

        if (myDoughnutChartPayroll) {
            myDoughnutChartPayroll.destroy(); // Destroy existing chart instance
        }

         // Custom plugin only registered for this chart
         const doughnutLabelsInside = {
            id: 'doughnutLabelsInside',
            afterDraw: function (chart) {
                var ctx = chart.ctx;
                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function (element, index) {
                            var dataValue = dataset.data[index];
                            var label = chart.data.labels[index];

                            var total = dataset.data.reduce(function (acc, val) {
                                return acc + (isNaN(val) ? 0 : Number(val));
                            }, 0);

                            var percentage = total > 0 ? ((dataValue / total) * 100).toFixed(2) + '%' : '0%';
                            // console.log(percentage);
                            var position = element.tooltipPosition();

                            ctx.fillStyle = '#fff';
                            ctx.font = 'bold 16px Poppins';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillText(percentage, position.x, position.y);
                        });
                    }
                });
            }
        };
        myDoughnutChartPayroll = new Chart(ctxPayroll, {
            type: 'doughnut',
            data: {
                labels: ['Cash Payments', 'Bank Transfers'],
                datasets: [{
                    data: [cashPercentage, bankPercentage],
                    backgroundColor: ['#014653', '#2EACB3']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    doughnutLabelsInside: true, // Enable the custom plugin
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10,
                        left: 0,
                        right: 0
                    }
                },
                hover: {
                    onHover: function (event, activeElements) {
                        if (activeElements.length > 0) {
                            const chartSegment = activeElements[0];
                            chartSegment.element.options.hoverOffset = 100;
                        } else {
                            myDoughnutChartPayroll.data.datasets[0].hoverOffset = 10;
                        }
                        myDoughnutChartPayroll.update();
                    }
                },
                hoverOffset: 30
            },
            plugins: [doughnutLabelsInside]
        });
    }

    function fetchDepartmentDistribution() {
        $.ajax({
            url: "{{ route('payroll.departmentDistribution') }}", // Replace with actual route
            type: "GET",
            success: function (response) {
                console.log(response);

                const departmentData = response.data;

                // Configuration for Treemap Chart
                const config = {
                    type: 'treemap',
                    data: {
                        datasets: [{
                            label: 'Distribution by department',
                            tree: departmentData,
                            key: 'value',
                            borderWidth: 0,
                            borderRadius: 15,
                            spacing: 3,
                            backgroundColor(ctx) {
                                return ctx.type === 'data' ? ctx.raw._data.color : 'transparent';
                            },
                            labels: {
                                align: 'left',
                                display: true,
                                formatter(ctx) {
                                    return ctx.type === 'data' ? [ctx.raw._data.what, 'Value: $' + ctx.raw.v.toLocaleString()] : '';
                                },
                                color: ['white', 'whiteSmoke'],
                                font: [{ size: 16, weight: 'bold' }, { size: 13 }],
                                position: 'top',
                                padding: 10
                            }
                        }]
                    },
                    options: {
                        events: [],
                        plugins: {
                            title: { display: false },
                            legend: { display: false },
                            tooltip: { enabled: false }
                        }
                    }
                };

                // Render Chart
                const ctx = document.getElementById('myTreemap').getContext('2d');
                window.myTreemap = new Chart(ctx, config);

                // Update Labels under the chart
                const labelContainer = document.getElementById('department-labels');
                let labelsHTML = '';
                departmentData.forEach(dept => {
                    labelsHTML += `
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span style="background-color: ${dept.color};"></span>
                                ${dept.what}
                            </div>
                        </div>
                    `;
                });
                labelContainer.innerHTML = labelsHTML;

            },
            error: function (xhr) {
                console.error("Failed to fetch department distribution data", xhr);
            }
        });
    }

    var cty = document.getElementById('myStackedBarChart').getContext('2d');

    // Function to fetch chart data dynamically
    function fetchChartData(year) {
        $.ajax({
            url: "{{ route('payroll.getExpenses') }}", // Adjust route accordingly
            method: "GET",
            data: { year: year },
            success: function (response) {
                if (response.success) {
                    updateChart(response.labels, response.data);
                }
            },
            error: function (xhr, error, code) {
                console.error("Error fetching chart data:", error);
            }
        });
    }

    // Function to update the chart dynamically
    function updateChart(labels, datasetValues) {
        myStackedBarChart.data.labels = labels;
        myStackedBarChart.data.datasets[0].data = datasetValues.payrollCost;
        myStackedBarChart.data.datasets[1].data = datasetValues.otCost;
        myStackedBarChart.data.datasets[2].data = datasetValues.serviceCharge;
        myStackedBarChart.update(); // Update the chart
    }

    // Initialize the chart
    var myStackedBarChart = new Chart(cty, {
        type: 'bar',
        data: {
            labels: [], // Initially empty, will be updated via AJAX
            datasets: [
                {
                    label: 'Payroll Cost',
                    data: [],
                    backgroundColor: '#014653',
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 10,
                },
                {
                    label: 'OT Cost',
                    data: [],
                    backgroundColor: '#2EACB3',
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 10,
                },
                {
                    label: 'Service Charge',
                    data: [],
                    backgroundColor: '#EFB408',
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 10,
                },
            ]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (tooltipItem) {
                            return tooltipItem.dataset.label;
                        },
                        afterLabel: function (tooltipItem) {
                            return '$' + tooltipItem.raw;
                        }
                    },
                    displayColors: false
                }
            },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, grid: { display: false } }
            }
        }
    });

    // Fetch initial chart data
    let initialYear = $("#yearFilter").val();
    fetchChartData(initialYear);

    // Change event for the year filter
    $("#yearFilter").change(function () {
        let selectedYear = $(this).val();
        fetchChartData(selectedYear);
    });

    $('#monthSelector').on('change', function () {
        const selectedMonth = $(this).val();

        $.ajax({
            url: '{{ route("payroll.getPayrollComparison") }}',
            method: 'GET',
            data: { month: selectedMonth },
            beforeSend: function() {
                $('.comparison-wrapper').html('<p>Loading...</p>'); // Optional loading state
            },
            success: function (response) {
                $('.comparison-wrapper').html(response.html);
                window.initializeProgressBars(); // works globally

            },
            error: function () {
                $('.comparison-wrapper').html('<p class="text-danger">Error loading data.</p>');
            }
        });
    });



  
    function fetchOtTrendChart(selectedYear) {
        $.ajax({
            url: "{{ route('payroll.otTrendData') }}",
            type: "GET",
            data: { year: selectedYear },
            success: function (response) {
                const labels = response.labels;
                const data = response.data;

                const ctx = document.getElementById('myLineChart').getContext('2d');

                if (window.myLineChart && typeof window.myLineChart.destroy === 'function') {
                    window.myLineChart.destroy();
                }

                window.myLineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'OT Hours',
                            data: data,
                            borderColor: '#2EACB3',
                            backgroundColor: '#2EACB3',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { display: false }
                        },
                        layout: { padding: 0 },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                beginAtZero: true,
                                grid: { display: false },
                                ticks: { stepSize: 5 }
                            }
                        }
                    }
                });
            },
            error: function (xhr) {
                console.error("Failed to load OT trend data", xhr);
            }
        });
    }

    function renderLineChart(labels, dataPoints) {
        const ctx = document.getElementById('myLineChart').getContext('2d');

        if (window.myLineChart) {
            window.myLineChart.destroy(); // Destroy previous instance if needed
        }

        window.myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total OT Hours',
                    data: dataPoints,
                    borderColor: '#2EACB3',
                    backgroundColor: '#2EACB3',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                layout: {
                    padding: { top: 0, bottom: 0, left: 0, right: 0 }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: true }
                    },
                    y: {
                        grid: { display: false },
                        beginAtZero: true,
                        ticks: { stepSize: 5 }
                    }
                }
            }
        });
    }

    function fetchPensionChartData() {
        $.ajax({
            url: "{{route('payroll.getMonthlyPensionData')}}", // API endpoint
            method: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "YearWisePensionData": $(".YearWisePensionData").val()
            },
            success: function (response) {
                // console.log(response);

                const labels = response.map(item => item.month);
                const employeeData = response.map(item => item.employee);
                const employerData = response.map(item => item.employer);

                updatePensionChart(labels, employeeData, employerData);
            }
        });
    }

    function updatePensionChart(labels, employeeData, employerData) {
        const ctx = document.getElementById('pension').getContext('2d');

        if (window.pensionChart) {
            window.pensionChart.destroy(); // Destroy previous chart instance
        }

        window.pensionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Employee',
                        data: employeeData,
                        backgroundColor: '#014653',
                        borderColor: '#014653',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    },
                    {
                        label: 'Employer',
                        data: employerData,
                        backgroundColor: '#2EACB3',
                        borderColor: '#2EACB3',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    }
                ]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (tooltipItem) {
                                return ` $${tooltipItem.raw.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: true }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 5 },
                        grid: { display: false },
                        border: { display: true }
                    }
                }
            }
        });
    }

    var ctd = document.getElementById('budgetComp').getContext('2d');
    var budgetComp = new Chart(ctd, {
        type: 'line',
        data: {
            labels: ['', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024'], // X-axis labels
            datasets: [
                {
                    label: 'Budgeted Amount',
                    data: [7, 10, 15, 20, 30],
                    borderColor: '#014653',
                    backgroundColor: '#014653',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4, // Creates smooth curves
                    // cubicInterpolationMode: 'monotone', // Monotone interpolation
                    pointRadius: 0 // Remove dots
                },
                {
                    label: 'Actual Amount',
                    data: [4, 7, 20, 35, 25], // Data points for the dataset
                    borderColor: ' #2EACB3', // Line color
                    backgroundColor: '#2EACB3',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4, // Default cubic Bézier curve (smooth curve)
                    pointRadius: 0 // Remove dots
                },
            ]
        },
        options: {
            plugins: {
                doughnutLabelsInside: true, // Enable the custom plugin
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            },
            scales: {
                x: {
                    beginAtZero: true, // Start x-axis at zero
                    grid: {
                        display: false // Hide grid lines on the x-axis
                    },
                    border: {
                        display: true // Hide the x-axis border
                    }
                },
                y: {
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    },
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5,
                    }
                }
            }
        }

    });

    function renderEwtTaxChart(year = new Date().getFullYear()) {
        $.ajax({
            url: "{{ route('payroll.ewtBracketChart') }}",
            type: 'GET',
            data: { year: year },
            success: function (res) {
                const ctx = document.getElementById('taxChart').getContext('2d');

                if (window.taxChartInstance) window.taxChartInstance.destroy();

                window.taxChartInstance = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            data: res.data,
                            backgroundColor: res.labels.map(() => getRandomColor()),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            }
                        }
                    },
                    plugins: [pieLabelsInside]
                });
            }
        });
    }

    function getRandomColor() {
        const hue = Math.floor(Math.random() * 360);
        return `hsl(${hue}, 70%, 60%)`;
    }

    const pieLabelsInside = {
        id: 'pieLabelsInside',
        afterDraw(chart) {
            const ctx = chart.ctx;
            const dataset = chart.data.datasets[0];
            const meta = chart.getDatasetMeta(0);
            const total = dataset.data.reduce((a, b) => a + b, 0);

            meta.data.forEach((element, i) => {
                const value = dataset.data[i];
                const percent = ((value / total) * 100).toFixed(1) + '%';
                const { x, y } = element.tooltipPosition();

                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(percent, x, y);
            });
        }
    };

    // var cte = document.getElementById('taxChart').getContext('2d');

    // // Custom plugin to display percentage labels inside the pie chart
    // const pieLabelsInside = {
    //     id: 'pieLabelsInside',
    //     afterDraw: function (chart) {
    //         var ctx = chart.ctx;
    //         chart.data.datasets.forEach(function (dataset, i) {
    //             var meta = chart.getDatasetMeta(i);
    //             if (!meta.hidden) {
    //                 meta.data.forEach(function (element, index) {
    //                     var dataValue = dataset.data[index];
    //                     var total = dataset.data.reduce(function (acc, val) {
    //                         return acc + val;
    //                     }, 0);
    //                     var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

    //                     var position = element.tooltipPosition(); // Position for the label

    //                     ctx.fillStyle = '#fff'; // Label color
    //                     ctx.font = 'bold 18px Arial'; // Font style
    //                     ctx.textAlign = 'center';
    //                     ctx.textBaseline = 'middle';

    //                     // Draw the percentage label at the center of each slice
    //                     ctx.fillText(percentage, position.x, position.y);
    //                 });
    //             }
    //         });
    //     }
    // };

    // // Create the pie chart
    // var taxChart = new Chart(cte, {
    //     type: 'pie', // Change to 'pie' for pie chart
    //     data: {
    //         // labels: ['January 2024', 'February 2024', 'March 2024', 'April 2024', 'May 2024', 'June 2024'],
    //         datasets: [{
    //             data: [35, 45, 20],
    //             backgroundColor: ['#4C88BB', '#2EACB3', '#014653'],
    //             borderWidth: 0
    //         }]
    //     },
    //     options: {
    //         responsive: true,
    //         plugins: {
    //             pieLabelsInside: true, // Enable the custom plugin
    //             legend: {
    //                 display: false
    //             }
    //         },
    //         layout: {
    //             padding: {
    //                 top: 10,
    //                 bottom: 10,
    //                 left: 0,
    //                 right: 0
    //             }
    //         }
    //     },
    //     plugins: [pieLabelsInside] // Attach the plugin to this chart only
    // });

</script>
@endsection