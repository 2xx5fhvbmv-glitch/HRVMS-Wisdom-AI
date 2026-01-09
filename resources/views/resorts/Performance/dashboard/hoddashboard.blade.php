@extends('resorts.layouts.app')
@section('page_tab_title' ,"Performance Dashboard")

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
                        <span>Performance</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <input type="text" value="{{ date('d/m/Y') }}"class="form-control datepicker DashboardDatePicker" id="DashboardDatePicker">

                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employees</p>
                            <strong>{{ $EmployeesCount }}</strong>
                        </div>
                        <a href="">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Present</p>
                            <strong id="totalPresentEmployee">{{ $totalPresentEmployee }}</strong>
                        </div>
                        <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">On Leave</p>
                            <strong id="totalLeaveEmployee">{{ $totalLeaveEmployee }}</strong>
                        </div>
                        <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Absent</p>
                            <strong id="totalAbsantEmployee">{{ $totalAbsantEmployee }}</strong>
                        </div>
                        <a href="#">
                            <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
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


                    <div class="todoList-main">


                        @forelse ($attendanceDataTodoList as $todo)

                        <div class="todoList-block">
                                    @if ($todo->flag == 'previous_day' &&  isset($todo->CheckingOutTime))

                                            <div class="img-circle">
                                                <img src="{{ $todo->profileImg }}" alt="image">
                                            </div>
                                        <div>
                                            <p>
                                                {{ $todo->EmployeeName }} is doing Overtime for yesterday
                                                {{ $todo->StartTime }} to {{ $todo->EndTime }}
                                                (including overtime: {{ $todo->EndTimeWithOvertime }})
                                            </p>
                                            <a href="javascript:void(0)"
                                                data-todoimage="{{ $todo->profileImg }}"
                                                data-todoname="{{ $todo->first_name .' '. $todo->last_name }}"
                                                data-differenceInHours="{{ $todo->differenceInHours }}"
                                                data-shiftname="{{ $todo->ShiftName }}"
                                                data-todoshiftstime="{{ $todo->StartTime }}"
                                                data-todoshiftetime="{{ $todo->EndTime }}"
                                                data-todoassignedot="{{ $todo->OverTime ?? '-' }}"
                                                data-totalExtraHours="{{ $todo->differenceInHours }}"
                                                data-flag="{{ $todo->flag }}"
                                                       data-CheckingOutTime="{{ $todo->CheckingOutTime }}"

                                                data-bs-toggle="modal" class="eye-btn OverTimeModel">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                        </div>



                                @elseif ($todo->flag == 'today')

                                            <div class="img-circle">
                                                <img src="{{ $todo->profileImg }}" alt="image">
                                            </div>
                                        <div>
                                            <p>
                                                {{ $todo->EmployeeName }}  checkout time is missing is  he/she doing Overtime for today
                                                {{-- {{ $todo->StartTime }} to {{ $todo->EndTime }} --}}
                                            </p>
                                            <a href="javascript:void(0)"
                                                data-todoimage="{{ $todo->profileImg }}"
                                                data-todoname="{{ $todo->first_name .' '. $todo->last_name }}"
                                                data-differenceInHours="{{ $todo->differenceInHours }}"
                                                data-shiftname="{{ $todo->ShiftName }}"
                                                data-attendance_id ="{{ $todo->attendance_id }}"
                                                data-todoshiftstime="{{ $todo->StartTime }}"
                                                data-todoshiftetime="{{ $todo->EndTime }}"
                                                data-todoassignedot="{{ $todo->OverTime ?? '-' }}"
                                                data-totalExtraHours="{{ $todo->differenceInHours }}"
                                                data-OTStatus="{{ $todo->OTStatus }}"
                                                data-bs-toggle="modal" class="eye-btn OverTimeModel"><i class="fa-regular fa-eye"></i>
                                            </a>
                                        </div>


                                @else
                                    <p>No tasks available for today.</p>
                                @endif

                        </div>

                    @empty
                        <div class="todoList-block">
                            <p>No tasks available for today.</p>
                        </div>
                    @endforelse

                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-title d-flex justify-content-between">
                        <h3>OT Hours</h3>
                    </div>
                    <canvas id="myOTHours" class="mb-2"></canvas>
                    <div class="row g-2 ">
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-theme"></span>Preplannned OT
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="doughnut-label">
                                <span class="bg-themeLightBlue"></span>Preplannned OT
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
            <div class="col-xl-6">
                <div class="card h-auto" id="card-duty">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Duty Roster</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select" aria-label="Default select example" id="ResortPosition">
                                        <option selected="">All Poistion</option>
                                        @if($ResortPosition->isNotEmpty())
                                            @foreach($ResortPosition as $position)
                                                <option value="{{$position->id}}">{{$position->position_title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <input type="text" class="form-control datepicker RosterDate" id="RosterDate">
                                </div>
                            </div>
                            <div class="col-auto"><a href="{{route('resort.timeandattendance.CreateDutyRoster')}}" class="btn btn-themeSkyblue btn-sm">Create Duty
                                    Roster</a>
                            </div>
                        </div>
                    </div>
                    <table id="DutyRoster" class="table  table-timeAtten w-100">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Poisition</th>
                                <th>Shift</th>
                                <th>Action</th>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>

    const ctx = document.getElementById('myAttendance').getContext('2d');
    const labelsAttandance = [];
    const firstMonth = 0; // January (0-indexed in JavaScript)
    const lastMonth = 11; // December (0-indexed in JavaScript)
    const currentYear = new Date().getFullYear();

    for (let i = firstMonth; i <= lastMonth; i++) {
        const month = new Date(currentYear, i);
        labelsAttandance.push(month.toLocaleString('default', { month: 'short', year: 'numeric' }));
    }
    const myAttendance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
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
                    beginAtZero: true, // Do not start y-axis at zero
                    grid: {
                        display: false // Hide grid lines on the y-axis
                    }, ticks: {
                        stepSize: 20,
                    },
                    border: {
                        display: true // Show the y-axis border
                    },
                }
            }
        }
    });
    GetAttandance();
    $(".YearWiseDateattandance").on('change', function () {
        GetAttandance();
    });
    $("#DashboardDatePicker").on('change', function () {

        GetAttandance();
        GetmyOTHours();
        DutyRosterList();

        let date  =  $("#DashboardDatePicker").val().split('/').reverse().join('-');
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
        let date  =  $("#DashboardDatePicker").val().split('/').reverse().join('-');
        let YearWiseDateattandance = $(".YearWiseDateattandance").val();
        $.ajax({
            url: "{{ route('resort.timeandattendance.GetYearWiseAttandanceData', ['year' => '__year__','date' => '__date__']) }}".replace('__date__', date).replace('__year__', YearWiseDateattandance),
            type: "get",

                success: function (response) {
                    myAttendance.data.labels = response.labels;
                    myAttendance.data.datasets = response.datasets;
                    myAttendance.update();
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
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
        let date  =  $("#DashboardDatePicker").val().split('/').reverse().join('-');

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
        $('#ResortPosition').select2({
            placeholder: "Select a Poitions", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });


        DutyRosterList();

        $('#ResortPosition').on('change', function () {
            DutyRosterList();
        });
        $('#RosterDate').on('change', function () {

            DutyRosterList();
        });
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });

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
    $(document).on("click", ".OverTimeModel", function()
    {


            $("#eyeRespond-modal").modal('show');


            $("#totalExtraHours").html($(this).attr('data-differenceinhours'));
            $("#todoimage").attr("src",$(this).attr('data-todoimage'));
            $("#todoname").html($(this).attr('data-todoname'));

            $("#todoshiftstime").html($(this).attr('data-todoshiftstime'));
            $("#todoshiftetime").html($(this).attr('data-todoshiftetime'));
            $("#todoassignedot").html($(this).attr('data-todoassignedot'));
            $("#totalExtraHours").html($(this).attr('data-totalExtraHours'));
            $("#attendance_id").val($(this).attr('data-attendance_id'));


            $("#todoshiftname").html($(this).attr('data-shiftname'));

            let OTStatus = $(this).attr('data-OTStatus');

            let CheckingOutTime = $(this).attr('data-CheckingOutTime');

            if(OTStatus !="" || CheckingOutTime !="")
            {
                $(".todoListApprove , .todoListReject").attr('disabled', true);
            }
    });
    $(document).on("click", ".todoListApprove , .todoListReject", function(e)
    {
        e.preventDefault();
        let flag = $(this).data('button');

        confirmations(flag, $('#attendance_id').val());
        $("#eyeRespond-modal").modal("hide");

    });

    function DutyRosterList()
    {
        if ($.fn.DataTable.isDataTable('#DutyRoster'))
            {
                $('#DutyRoster').DataTable().destroy();
            }
            var DutyRoster = $('#DutyRoster').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bFilter: true,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: true,
                    iDisplayLength: 6,
                    processing: true,
                    serverSide: true,
                    order:[[4, 'desc']],
                    ajax: {
                        url: "{{ route('resort.timeandattendance.DutyRosterdashboardTable')}}",
                        type: 'GET',
                        data: function(d) {
                            d.ResortDepartment = $("#ResortDepartment").val();
                            d.RosterDate = $('#RosterDate').val();
                            d.Positions  = $('#ResortPosition').val();
                            d.DashboardDatePicker  =  $("#DashboardDatePicker").val().split('/').reverse().join('-');
                        }
                    },
                    columns: [
                        { data: 'EmployeeName', name: 'EmployeeName', render: function (data, type, row) {
                            return `<div class="tableUser-block">
                                <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                                <span class="userApplicants-btn" data-id="${row.id}">${row.EmployeeName}</span>
                            </div>`;
                        }},
                        { data: 'Position', name: 'Position' },
                        { data: 'Shift', name: 'Shift' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                        {data:'created_at', visible:false,searchable:false},
                    ]

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
