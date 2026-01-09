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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Time And Attendance</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    {{-- <input type="text" value="{{ date('d/m/Y') }}"class="form-control datepicker DashboardDatePicker" id="DashboardDatePicker"> --}}

                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employees</p>
                            <strong>{{ $EmployeesCount }}</strong>
                        </div>
                        <a href="{{route('resort.timeandattendance.employee')}}">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Present</p>
                            <strong id="totalPresentEmployee">{{ $totalPresentEmployee }}</strong>
                        </div>
                        {{-- <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a> --}}
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">On Leave</p>
                            <strong id="totalLeaveEmployee">{{ $totalLeaveEmployee }}</strong>
                        </div>
                        {{-- <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a> --}}
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Absent</p>
                            <strong id="totalAbsantEmployee">{{ $totalAbsantEmployee }}</strong>
                        </div>
                        {{-- <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a> --}}
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.AttandanceRegister',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Attendance</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWiseDateattandance" aria-label="Default select example">
                                        @for ($i = -1; $i < 2; $i++) <!-- Start from one year before the current year -->
                                        @php
                                            $year = date('Y') + $i;
                                            $current = date("Y");
                                        @endphp
                                            <option value="{{ $year }}" @if($year == $current) selected @endif>
                                                Jan {{ $year }} - Dec {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="myAttendance"></canvas>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-title d-flex justify-content-between">
                        <h3>Compliance Tracking</h3>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <p class="mb-0">Employees who's number of Weekly Working Hours Exceeded</p>
                            <span class="d-inline-block w-25 text-end text-danger">12</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <p class="mb-0">Excessive Overtime Hours</p>
                            <span class="d-inline-block w-25 text-end">00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <p class="mb-0">Mandatory Break Not Taken</p>
                            <span class="d-inline-block w-25 text-end text-danger">03</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <p class="mb-0">Consecutive Days Worked Exceeding Limit</p>
                            <span class="d-inline-block w-25 text-end text-danger">03</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <p class="mb-0">Overtime Without Prior Approval</p>
                            <span class="d-inline-block w-25 text-end text-danger">03</span>
                        </div>
                        <div class="d-flex justify-content-between  border-bottom pb-2">
                            <p class="mb-0">Accumulated Day-Off Balances Exceeding Limits</p>
                            <span class="d-inline-block w-25 text-end text-danger">03</span>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-title d-flex justify-content-between">
                        <h3>AI Insight's</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card " id="card-todoList">
                    <div class="card-title d-flex justify-content-between">
                        <h3>To Do List</h3>
                        <a href="{{ route('resort.timeandattendance.todolist') }}" class="a-link">View all</a>
                    </div>

                    <div class="todoList-main" style="max-height: 400px; overflow-y: auto;">
                        @forelse ($attendanceDataTodoList as $todo)
                            <div class="todoList-block">
                                <div class="img-circle">
                                    <img src="{{ $todo->profileImg }}" alt="image">
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1 small">
                                        <strong>{{ $todo->message }}</strong>
                                    </p>
                                    <p class="mb-2 small">
                                        {{ $todo->EmployeeName }} - {{ $todo->ShiftName }}<br>
                                        @if($todo->action_type == 'check_in')
                                            Shift: {{ $todo->StartTime }} - {{ $todo->ExpectedEndTime ?? $todo->EndTime }}
                                        @elseif($todo->action_type == 'check_out')
                                            Expected Check-Out: {{ $todo->ExpectedEndTime ?? $todo->EndTime }}
                                        @elseif($todo->action_type == 'overtime_pending')
                                            Date: {{ \Carbon\Carbon::parse($todo->date)->format('d/m/Y') }}
                                        @endif
                                    </p>
                                    @if($todo->action_type == 'overtime_pending')
                                        <button type="button" 
                                            class="btn btn-xs btn-warning update-overtime-status" 
                                            data-emp-id="{{ $todo->employee_id }}"
                                            data-date="{{ $todo->date }}"
                                            data-employee-name="{{ $todo->EmployeeName }}">
                                            <i class="fa-solid fa-clock me-1"></i>Update
                                        </button>
                                    @else
                                        <button type="button" 
                                            class="btn btn-sm {{ $todo->action_type == 'check_in' ? 'btn-danger' : 'btn-success' }} manual-check-action" 
                                            data-roster-id="{{ $todo->roster_id }}"
                                            data-action="{{ $todo->action_type }}"
                                            data-employee-name="{{ $todo->EmployeeName }}">
                                            <i class="fa-solid {{ $todo->action_type == 'check_in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt' }} me-1"></i>
                                            {{ $todo->action_type == 'check_in' ? 'Check-In' : 'Check-Out' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="todoList-block">
                                <p class="text-center text-muted">No pending actions for today.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.OverTime',config('settings.resort_permissions.view')) == false) d-none @endif">

                <div class="card">
                    <div class="card-title d-flex justify-content-between">
                        <h3>OT Hours</h3>
                    </div>
                    <canvas id="myOTHours" class="mb-2"></canvas>
                    <div class="row g-2 ">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Normal OT
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Holiday OT
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeYellow"></span>Total OT Hours
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Done --}}
            <div class="col-xl-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.CreateDutyRoster',config('settings.resort_permissions.view')) == false) d-none @endif ">
                <div class="card h-auto" id="card-duty">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Duty Roster</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{route('resort.timeandattendance.ViewDutyRoster')}}" class="btn btn-themeSkyblue btn-sm me-2">View All Duty Roster</a>
                                <a href="{{route('resort.timeandattendance.CreateDutyRoster')}}" class="btn btn-themeSkyblue btn-sm">Create Duty Roster</a>
                            </div>
                        </div>
                    </div>
                    <table id="DutyRoster" class="table  table-timeAtten w-100">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Shift</th>
                            </tr>
                        </thead>
                        <tbody>



                        </tbody>
                    </table>

                </div>
            </div>


        </div>

    </div>
</div>
<div class="modal fade" id="eyeRespond-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="TodoListAttandance">
                    <div class="timeAttenRespond-block">
                        <div class="img-circle">
                            <img src="assets/images/user-2.svg" id="todoimage" alt="image">
                        </div>
                        <div>
                            <h6 id="todoname"></h6>

                        </div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class=" table-timeAttenRespond">
                            <tbody>
                                <tr>
                                    <th>Shift Name:</th>
                                    <td><p id="todoshiftname"></p></td>
                                </tr>
                                <tr>
                                    <th>Shift Starting Time:</th>
                                    <td><p id="todoshiftstime"></p></td>
                                </tr>
                                <tr>
                                    <th>Total Ending Time:</th>
                                    <td><p id="todoshiftetime"></p></td>
                                </tr>
                                <tr>
                                    <th>Assigned Overtime:</th>
                                    <td><p id="todoassignedot"></p></td>
                                </tr>

                                <tr>
                                    <th>Total additional hours completed:</th>
                                    <td><p id="totalExtraHours"></p></td>
                                    <input type="hidden" id="attendance_id">
                                </tr>

                            </tbody>
                        </table>
                    </div>
                    <div class="row g-2 justify-content-center mb-3">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-themeBlue btn-sm todoListApprove" data-button="approve"><i  class="fa-solid fa-check me-2"></i>Approved</button>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-danger btn-sm todoListReject"  data-button="reject"><i class="fa-solid fa-xmark me-2"></i>Reject</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="viewMapDashboard-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Map View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe  width="1075" height="450" style="border:0;" id="ModalIframe" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="updateOvertimeStatusModal" tabindex="-1" aria-labelledby="updateOvertimeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateOvertimeStatusModalLabel">Update Overtime Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateOvertimeStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="overtimeModalDate">
                    <input type="hidden" id="overtimeModalEmpId">
                    
                    <div class="timeAttenRespond-block mb-3">
                        <div class="img-circle">
                            <img src="" id="overtimeEmployeeImage" alt="image">
                        </div>
                        <div>
                            <h6 id="overtimeEmployeeName"></h6>
                            <p class="mb-0 small" id="overtimeEmployeeId"></p>
                        </div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table-timeAttenRespond">
                            <tbody>
                                <tr>
                                    <th>Date:</th>
                                    <td><p id="overtimeDate"></p></td>
                                </tr>
                                <tr>
                                    <th>Shift Name:</th>
                                    <td><p id="overtimeShiftName"></p></td>
                                </tr>
                                <tr>
                                    <th>Duty Roster Overtime:</th>
                                    <td><p id="dutyRosterOvertime"></p></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="overtimeEntriesContainer">
                        <!-- Overtime entries will be added here -->
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-sm btn-primary" id="addOvertimeEntry">
                            <i class="fa fa-plus"></i> Add Entry
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>

    let myAttendance;
    const ctx = document.getElementById('myAttendance');
    if (!ctx) {
        console.error('Attendance chart canvas not found');
    } else {
        const ctx2d = ctx.getContext('2d');
        const labelsAttandance = [];
        const firstMonth = 0; // January (0-indexed in JavaScript)
        const lastMonth = 11; // December (0-indexed in JavaScript)
        const currentYear = new Date().getFullYear();

        for (let i = firstMonth; i <= lastMonth; i++) {
            const month = new Date(currentYear, i);
            labelsAttandance.push(month.toLocaleString('default', { month: 'short', year: 'numeric' }));
        }
        myAttendance = new Chart(ctx2d, {
            type: 'bar',
            data: {
                labels: labelsAttandance, // Initialize with default month labels
                datasets: [{
                    label: 'Attendance Percentage',
                    data: new Array(12).fill(0), // Initialize with zeros
                    backgroundColor: '#014653',
                    borderColor: '#014653',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 25,
                }]
            },
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
                        label: function (tooltipItem)
                        {
                            const value = tooltipItem.raw.toLocaleString();
                            return `${value}%`;
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
                    beginAtZero: true, // Start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, 
                    ticks: {
                        stepSize: 20,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
    }
    
    if (typeof myAttendance !== 'undefined') {
        GetAttandance();
        $(".YearWiseDateattandance").on('change', function () {
            GetAttandance();
        });
    }
    $("#DashboardDatePicker").on('change', function () {

        GetAttandance();
        GetmyOTHours();
        DutyRosterList();

        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        $.ajax({
            url: "{{ route('resort.timeandattendance.HodDashboardCount', ['date' => '__date__']) }}".replace('__date__', date),
            type: "get",

                success: function (response) {
                    $("#totalPresentEmployee").html(response.data.totalPresentEmployee);
                    $("#totalAbsantEmployee").html(response.data.totalAbsantEmployee);
                    $("#totalLeaveEmployee").html(response.data.totalLeaveEmployee);
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });
    });
    function GetAttandance()
    {
        if (typeof myAttendance === 'undefined') {
            console.error('Attendance chart not initialized');
            return;
        }
        
        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        let YearWiseDateattandance = $(".YearWiseDateattandance").val();
        $.ajax({
            url: "{{ route('resort.timeandattendance.GetYearWiseAttandanceData', ['year' => '__year__','date' => '__date__']) }}".replace('__date__', date).replace('__year__', YearWiseDateattandance),
            type: "get",
            success: function (response) {
                if (response && response.labels && response.datasets) {
                    myAttendance.data.labels = response.labels;
                    myAttendance.data.datasets = response.datasets;
                    myAttendance.update();
                } else {
                    console.error("Invalid response format", response);
                }
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch chart data", {xhr: xhr, status: status, error: error});
            }
        });
    }
    const cty = document.getElementById('myOTHours').getContext('2d');
    const labels = [];
    for (let i = 0; i < 4; i++)
    {
        const month = new Date(new Date().getFullYear(), new Date().getMonth() + i);
        labels.push(month.toLocaleString('default', { month: 'short', year: 'numeric' }));
    }

    // Chart.js configuration
    const myOTHours = new Chart(cty, {
        type: 'bar',
        data: {
            labels: labels, // Use dynamic labels here
            datasets: []
        },
        options: {
            plugins: {
                legend: {
                    display: false // Hide legend if not needed
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
                            const value = tooltipItem.raw.toLocaleString();
                            return ` ${value}`; // Customize tooltip label
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false // Remove gridlines
                    },
                    border: {
                        display: true
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false // Remove gridlines
                    },
                    ticks: {
                        stepSize: 5 // Adjust step size for better readability
                    },
                    border: {
                        display: true
                    }
                }
            }
        }
    });
    GetmyOTHours()
    function GetmyOTHours()
    {
        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];

        $.ajax({
            url: "{{ route('resort.timeandattendance.MonthOverTimeChart', ['date' => '__date__']) }}".replace('__date__', date),
                type: "get",

                success: function (response) {
                    myOTHours.data.labels = response.labels;
                    myOTHours.data.datasets = response.datasets;
                    myOTHours.update();
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });

    }

    function equalizeHeights()
    {
        const block1 = document.getElementById('card-duty');
        const block2 = document.getElementById('card-todoList');
        const block1Height = block1.offsetHeight;
        block2.style.height = block1Height + 'px';
    }

    window.onload = equalizeHeights;
    window.onresize = equalizeHeights;

    $(document).ready(function () {
        DutyRosterList();
    });
    $(document).on("click", ".LocationHistoryData", function()
    {
        let location1 = $(this).attr('data-location');
        let type =$(this).data('id');

        if (!location1 || location1.trim() === "")
        {
            toastr.error("data not avilable", "Validation Error", {
                positionClass: 'toast-bottom-right'
            });

            return false;
        }
        else
        {
            $("#viewMapDashboard-modal").modal('show');
            $("#ModalIframe").attr("src", location1);
        }



    });
    // Handle update overtime status button click
    $(document).on("click", ".update-overtime-status", function() {
        const empId = $(this).data('emp-id');
        const date = $(this).data('date');
        const employeeName = $(this).data('employee-name');
        const button = $(this);
        
        // Disable button during request
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Loading...');
        
        $.ajax({
            url: "{{ route('resort.timeandattendance.GetOvertimeEntries') }}",
            type: 'GET',
            data: {
                emp_id: empId,
                date: date
            },
            success: function(response) {
                button.prop('disabled', false).html('<i class="fa-solid fa-clock me-1"></i>Update');
                
                if (response.success) {
                    // Set hidden fields
                    $('#overtimeModalDate').val(date);
                    $('#overtimeModalEmpId').val(empId);
                    
                    // Populate modal with employee info
                    $('#overtimeEmployeeImage').attr('src', response.employee.profile_img || 'assets/images/default-user.svg');
                    $('#overtimeEmployeeName').text(response.employee.name);
                    $('#overtimeEmployeeId').text('ID: ' + response.employee.emp_id);
                    $('#overtimeDate').text(response.date);
                    $('#overtimeShiftName').text(response.shift_name);
                    $('#dutyRosterOvertime').text(response.duty_roster_overtime);
                    
                    // Clear and populate overtime entries
                    $('#overtimeEntriesContainer').empty();
                    
                    if (response.overtime_entries && response.overtime_entries.length > 0) {
                        response.overtime_entries.forEach(function(entry, index) {
                            addOvertimeEntry(entry, index + 1);
                        });
                    } else {
                        // Add one empty entry if none exist
                        addOvertimeEntry(null, 1);
                    }
                    
                    // Show modal
                    $('#updateOvertimeStatusModal').modal('show');
                } else {
                    Swal.fire(
                        'Error!',
                        response.message || 'Failed to load overtime entries.',
                        'error'
                    );
                }
            },
            error: function(xhr) {
                button.prop('disabled', false).html('<i class="fa-solid fa-clock me-1"></i>Update');
                Swal.fire(
                    'Error!',
                    'An error occurred while loading overtime entries.',
                    'error'
                );
                console.error('Error:', xhr);
            }
        });
    });
    
    // Add overtime entry row
    function addOvertimeEntry(entry = null, entryNumber = null) {
        // Get current entry count if not provided
        if (entryNumber === null) {
            entryNumber = $('#overtimeEntriesContainer .overtime-entry-row').length + 1;
        }

        let entryHtml = '<div class="overtime-entry-row mb-3 p-3 border rounded">';
        if (entry && entry.id) {
            entryHtml += '<input type="hidden" class="overtime-entry-id" value="' + entry.id + '">';
        }
        entryHtml += '<div class="d-flex justify-content-between align-items-center mb-2">';
        entryHtml += '<h6 class="mb-0">Entry ' + entryNumber + '</h6>';
        entryHtml += '<button type="button" class="btn btn-sm btn-danger remove-overtime-entry"><i class="fa fa-times"></i> Remove</button>';
        entryHtml += '</div>';
        entryHtml += '<div class="row g-3">';
        entryHtml += '<div class="col-md-4">';
        entryHtml += '<label class="form-label">Check In Time</label>';
        entryHtml += '<input type="text" class="form-control overtime-start-time" value="' + (entry ? entry.start_time : '') + '" placeholder="HH:MM">';
        entryHtml += '</div>';
        entryHtml += '<div class="col-md-4">';
        entryHtml += '<label class="form-label">Check Out Time</label>';
        entryHtml += '<input type="text" class="form-control overtime-end-time" value="' + (entry ? entry.end_time : '') + '" placeholder="HH:MM">';
        entryHtml += '</div>';
        entryHtml += '<div class="col-md-4">';
        entryHtml += '<label class="form-label">Status</label>';
        entryHtml += '<select class="form-select overtime-status">';
        entryHtml += '<option value="pending"' + (entry && entry.status === 'pending' ? ' selected' : '') + '>Pending</option>';
        entryHtml += '<option value="approved"' + (entry && entry.status === 'approved' ? ' selected' : '') + '>Approved</option>';
        entryHtml += '<option value="rejected"' + (entry && entry.status === 'rejected' ? ' selected' : '') + '>Rejected</option>';
        entryHtml += '</select>';
        entryHtml += '</div>';
        entryHtml += '</div>';
        entryHtml += '</div>';

        $('#overtimeEntriesContainer').append(entryHtml);

        // Initialize time pickers for the newly added entry
        let $newRow = $('#overtimeEntriesContainer .overtime-entry-row').last();
        $newRow.find('.overtime-start-time, .overtime-end-time').each(function() {
            if (!$(this).data('flatpickr')) {
                flatpickr(this, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    minuteIncrement: 1
                });
            }
        });
    }

    // Remove overtime entry
    $(document).on('click', '.remove-overtime-entry', function() {
        $(this).closest('.overtime-entry-row').remove();
        // Renumber remaining entries
        $('#overtimeEntriesContainer .overtime-entry-row').each(function(index) {
            $(this).find('h6').text('Entry ' + (index + 1));
        });
    });

    // Add new overtime entry
    $(document).on('click', '#addOvertimeEntry', function() {
        let entryCount = $('#overtimeEntriesContainer .overtime-entry-row').length;
        addOvertimeEntry(null, entryCount + 1);
    });
    
    // Handle form submission
    $('#updateOvertimeStatusForm').on('submit', function(e) {
        e.preventDefault();

        let date = $('#overtimeModalDate').val();
        let empId = $('#overtimeModalEmpId').val();
        let entries = [];

        $('.overtime-entry-row').each(function() {
            let entryId = $(this).find('.overtime-entry-id').val();
            let startTime = $(this).find('.overtime-start-time').val();
            let endTime = $(this).find('.overtime-end-time').val();
            let status = $(this).find('.overtime-status').val();

            if (startTime && endTime) {
                entries.push({
                    id: entryId || null,
                    start_time: startTime,
                    end_time: endTime,
                    status: status
                });
            }
        });

        if (entries.length === 0) {
            toastr.error('Please add at least one overtime entry.', "Error", {
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        $.ajax({
            url: "{{ route('resort.timeandattendance.StoreOverTime') }}",
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "date": date,
                "Emp_id": empId,
                "entries": entries
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    if (typeof bootstrap !== 'undefined') {
                        var modalElement = document.getElementById('updateOvertimeStatusModal');
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    } else {
                        $('#updateOvertimeStatusModal').modal('hide');
                    }
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                if (response.status === 422) {
                    var errors = response.responseJSON.errors;
                    var errs = '';
                    $.each(errors, function (field, messages) {
                        $.each(messages, function (index, message) {
                            errs += message + '<br>';
                        });
                    });
                    toastr.error(errs, "Validation Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    });
    
    // Handle manual check-in/check-out actions
    $(document).on("click", ".manual-check-action", function() {
        const rosterId = $(this).data('roster-id');
        const action = $(this).data('action');
        const employeeName = $(this).data('employee-name');
        const actionText = action === 'check_in' ? 'Check-In' : 'Check-Out';
        const button = $(this);
        
        Swal.fire({
            title: `Confirm ${actionText}`,
            text: `Are you sure you want to record ${actionText.toLowerCase()} for ${employeeName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'check_in' ? '#dc3545' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${actionText}`,
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button during request
                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Processing...');
                
                $.ajax({
                    url: "{{ route('resort.timeandattendance.ManualCheckInOut') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        roster_id: rosterId,
                        action: action
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Success!',
                                response.message,
                                'success'
                            ).then(() => {
                                // Reload the page to refresh the todo list
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message || 'An error occurred.',
                                'error'
                            );
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while processing the request.',
                            'error'
                        );
                        button.prop('disabled', false);
                        console.error('Error:', xhr);
                    }
                });
            }
        });
    });

    function DutyRosterList()
    {
        // #region agent log
        console.log('[DEBUG] DutyRosterList called');
        fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:643','message':'DutyRosterList called','data':{},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch((e)=>{console.error('[DEBUG] Log fetch failed:',e);});
        // #endregion
        if ($.fn.DataTable.isDataTable('#DutyRoster'))
            {
                $('#DutyRoster').DataTable().destroy();
            }
            var ajaxUrl = "{{ route('resort.timeandattendance.DutyRosterdashboardTable')}}";
            // #region agent log
            console.log('[DEBUG] AJAX URL:', ajaxUrl);
            fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:649','message':'AJAX URL prepared','data':{ajaxUrl:ajaxUrl},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch((e)=>{console.error('[DEBUG] Log fetch failed:',e);});
            // #endregion

            // Fetch data via AJAX first, then initialize DataTables with client-side processing
            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    // #region agent log
                    console.log('[DEBUG] AJAX success, response:', response);
                    fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:658','message':'AJAX success','data':{dataCount:response.data?response.data.length:0,recordsTotal:response.recordsTotal,hasError:!!response.error},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch((e)=>{console.error('[DEBUG] Log fetch failed:',e);});
                    // #endregion
                    // Initialize DataTables with the fetched data
                    var tableData = response.data || [];
                    // #region agent log
                    console.log('[DEBUG] Table data length:', tableData.length, 'Data:', tableData);
                    fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:660','message':'Table data extracted','data':{tableDataLength:tableData.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch((e)=>{console.error('[DEBUG] Log fetch failed:',e);});
                    // #endregion
                    var DutyRoster = $('#DutyRoster').DataTable({
                        searching: false,
                        bLengthChange: false,
                        bFilter: false,
                        bInfo: false,
                        bAutoWidth: false,
                        scrollX: true,
                        paging: false,
                        processing: false,
                        serverSide: false,
                        data: tableData,
                        order:[[4, 'desc']],
                        columns: [
                            { data: 'EmployeeName', name: 'EmployeeName', render: function (data, type, row) {
                                return `<div class="tableUser-block">
                                    <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                                    <span class="userApplicants-btn" data-id="${row.id}">${row.EmployeeName}</span>
                                </div>`;
                            }},
                            { data: 'Position', name: 'Position' },
                            { data: 'Shift', name: 'Shift' },
                            {data:'created_at', visible:false,searchable:false},
                        ]
                    });
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:686','message':'DataTable initialized','data':{},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch(()=>{});
                    // #endregion
                },
                error: function(xhr, error, thrown) {
                    // #region agent log
                    console.error('[DEBUG] AJAX error:', {status:xhr.status,error:error,thrown:thrown,responseText:xhr.responseText});
                    fetch('http://127.0.0.1:7242/ingest/f9694a9e-af15-42e2-8444-553f0ae8bbff',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'hoddashboard.blade.php:687','message':'AJAX error','data':{status:xhr.status,error:error,thrown:thrown,responseText:xhr.responseText},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch((e)=>{console.error('[DEBUG] Log fetch failed:',e);});
                    // #endregion
                    // Initialize empty table on error
                    var DutyRoster = $('#DutyRoster').DataTable({
                        searching: false,
                        bLengthChange: false,
                        bFilter: false,
                        bInfo: false,
                        bAutoWidth: false,
                        scrollX: true,
                        paging: false,
                        data: [],
                        columns: [
                            { data: 'EmployeeName', name: 'EmployeeName' },
                            { data: 'Position', name: 'Position' },
                            { data: 'Shift', name: 'Shift' },
                            {data:'created_at', visible:false,searchable:false},
                        ]
                    });
                }
            });
    }

    function confirmations(flag, itemId)
    {
        const action = flag === 'approve' ? 'approved' : 'rejected'; // Determine action based on flag

        Swal.fire({
            title: `Are you sure you want to ${flag} this OT?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: flag === 'approve' ? '#28a745' : '#dc3545', // Green for approve, red for reject
            cancelButtonColor: '#6c757d', // Gray for cancel
            confirmButtonText: `Yes, ${flag} it!`,
            cancelButtonText: 'No, cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform the AJAX request
                $.ajax({
                    url: '{{ route("resort.timeandattendance.OTStatusUpdate") }}', // Replace with your backend endpoint
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        action: flag,
                        AttdanceId: itemId // Pass the item ID
                    },
                    success: function(response) {
                        // Show success message
                        Swal.fire(
                            `${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                            `The OT has been successfully ${action}.`,
                            'success'
                        );
                        window.location.reload();

                        // Optional: Update the UI (e.g., remove the item or update status)
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        Swal.fire(
                            'Error!',
                            'An error occurred while processing the request.',
                            'error'
                        );

                        console.error(error);
                    }
                });
            } else {
                console.log('Action canceled');
            }
        });
    }


</script>

@endsection
