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
        <div class="page-hedding" id="ta-attendance-hero">
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
        <style>
    /* Same requested push as the Payroll / Talent Acquisition / People
       dashboards — extra breathing room between the hero and the KPI row
       below it, scoped to this page (.page-hedding's own margin-bottom is
       shared by every page's hero). padding-bottom, not margin: adjacent
       sibling margins collapse to the larger of the two rather than
       summing. Below Bootstrap's sm breakpoint the extra padding pushes
       the KPI row's first card into the teal hero curve's rounded
       bottom-left corner (body::before, border-radius 0 0 50px 50px) —
       same collision found on Payroll — neutralized below 576px. */
    #ta-attendance-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #ta-attendance-hero { padding-bottom: 0; }
    }

    /* Custom 5 column layout for large screens */
    @media (min-width: 992px) {
        .col-custom-5 {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }

    /* This row's default Bootstrap flex behaviour (align-items: stretch)
       was stretching Attendance's column to match To Do List's own natural
       (often much taller, many-item) content height BEFORE the JS below
       ever ran — so the JS was then measuring an already-inflated
       Attendance height and copying that same inflated number back onto
       To Do List, leaving a large empty gap under Attendance's chart.
       align-self: flex-start opts both columns out of that stretch so each
       sizes to its own real content again; the JS then does the actual
       exact-match cleanly on top of that. */
    .ta-attendance-col,
    .ta-todo-col {
        align-self: flex-start;
    }

    /* To Do List — sits alongside Attendance (both col-xl-6). The actual
       exact-match to Attendance's height is done by JS below (measures
       Attendance's real rendered height and applies it here), since a
       fixed guess here would rarely line up pixel-for-pixel. This
       max-height is only a safety net for before that JS runs (or if it
       can't run at all) — generous enough that it should never actually
       constrain the JS-set height in normal use, just stop the list from
       growing truly unbounded. */
    .ta-todo-card-v2 {
        display: flex;
        flex-direction: column;
        max-height: 800px;
    }
    .ta-todo-card-v2 .ta-todo-list-v2 {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
    /* Moves the Check-In/Check-Out button to the right of the text instead
       of stacked underneath it. */
    .ta-todo-card-v2 .todoList-block {
        align-items: center;
    }
    .ta-todo-card-v2 .ta-todo-action {
        flex-shrink: 0;
        margin-left: 12px;
    }
    /* Check-In/Check-Out otherwise size to their own text ("Check-Out" is
       longer than "Check-In"), so the two buttons ended up visibly
       different widths across rows. 116px wasn't actually wide enough to
       be the binding constraint on the longer "Check-Out" button (its own
       content already exceeded that), so it had no visible effect —
       150px clears both, and both are forced to render at that same
       explicit width instead of just a floor either one could exceed.
       Scoped to .ta-todo-action rather than the shared .manual-check-action
       class itself, since that class is also used by todolist.blade.php
       and the HOD dashboard. */
    .ta-todo-card-v2 .ta-todo-action .manual-check-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 150px;
    }
    .ta-todo-card-v2 .ta-todo-initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
    }
</style>
        <div class="row g-3 g-xxl-4 card-heigth">
             <!-- Total Employees -->
                <div class="col-custom-5 col-sm-6 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0 fw-500">Total Employees</p>
                                <strong id="TotalEmployees">{{ $EmployeesCount }}</strong>
                            </div>
                            <a href="{{ route('resort.timeandattendance.employee') }}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Total Present -->
                <div class="col-custom-5 col-sm-6 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0 fw-500">Total Present</p>
                                <strong id="totalPresentEmployee">{{ $totalPresentEmployee }}</strong>
                            </div>
                            <!-- <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                            </a> -->
                        </div>
                    </div>
                </div>

                <!-- On Leave -->
                <div class="col-custom-5 col-sm-6 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0 fw-500">On Leave</p>
                                <strong id="totalLeaveEmployee">{{ $totalLeaveEmployee }}</strong>
                            </div>
                            <!-- <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                            </a> -->
                        </div>
                    </div>
                </div>

                <!-- Absent -->
                <div class="col-custom-5 col-sm-6 col-12 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card dashboard-boxcard timeAttend-boxcard h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0 fw-500">Absent</p>
                                <strong id="totalAbsantEmployee">{{ $totalAbsantEmployee }}</strong>
                            </div>
                            <!-- <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                            </a> -->
                        </div>
                    </div>
                </div>

                <!-- 5th Box Example -->
                <div class="col-custom-5 col-sm-6 col-12">
                    <div class="card dashboard-boxcard timeAttend-boxcard h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0 fw-500">Unknown Status</p>
                                <strong id="totalunknown_status_Employee">{{ $totalunknown_status_Employee ?? 0 }}</strong>
                            </div>
                            <!-- <a href="#">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                            </a> -->
                        </div>
                    </div>
                </div>
            <div class="col-xl-6 ta-attendance-col @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.AttandanceRegister',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card" id="card-attendance">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Attendance</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWiseDateattandance dd-native-select" id="hrYearWiseDateattandance" aria-label="Default select example">
                                        @for ($i = -1; $i < 2; $i++) <!-- Start from one year before the current year -->
                                        @php
                                            $year = date('Y') + $i;
                                            $current = date("Y");
                                        @endphp
                                            <option value="{{ $year }}" @if($year == $current) selected @endif>
                                                {{ $year }}
                                            </option>
                                        @endfor
                                    </select>
                                    <div class="dd" data-target="#hrYearWiseDateattandance">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">{{ date('Y') }}</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Attendance year">
                                            <div class="dd-scroll">
                                                @for ($i = -1; $i < 2; $i++)
                                                @php
                                                    $year = date('Y') + $i;
                                                    $current = date("Y");
                                                @endphp
                                                <div class="dd-item @if($year == $current) active @endif" role="option" data-value="{{ $year }}">
                                                    <span class="dd-nm">{{ $year }}</span>
                                                    <svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>
                                                </div>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <canvas id="myAttendance"></canvas>
                </div>
            </div>
            <!-- <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-title d-flex justify-content-between">
                        <h3>AI Insight's</h3>
                    </div>
                </div>
            </div> -->
            <div class="col-xl-6 col-lg-6 col-md-6 ta-todo-col @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.todolist',config('settings.resort_permissions.view')) == false) d-none @endif" id="hrdashboard-todo-section">
                <div class="card ta-todo-card-v2" id="card-todoListTA">
                    <div class="card-title d-flex justify-content-between">
                        <h3>To Do List</h3>
                        <a href="{{ route('resort.timeandattendance.todolist') }}" class="a-link">View all</a>
                    </div>

                    <div class="todoList-main ta-todo-list-v2">
                        @php
                            $todoDefaultPhoto = url(config('settings.default_picture'));
                            $todoPalette = ['var(--teal)', '#0E8A9E', 'var(--aqua)', '#4A5F8A', 'var(--muted)'];
                            $todoInitials = function ($name) {
                                $parts = preg_split('/\s+/', trim((string) $name));
                                $initials = '';
                                foreach (array_slice($parts, 0, 2) as $part) {
                                    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                }
                                return $initials !== '' ? $initials : '?';
                            };
                            $todoAvatarColor = function ($name) use ($todoPalette) {
                                $hash = 0;
                                foreach (str_split((string) $name) as $ch) {
                                    // Bounded modulo every step — without this, a long
                                    // enough name overflows PHP's int range into a
                                    // float, and abs()/% on that float can produce a
                                    // negative or out-of-range result (crashed with
                                    // "Undefined array key -4" on a real employee name).
                                    $hash = (ord($ch) + (($hash << 5) - $hash)) % 1000000007;
                                }
                                return $todoPalette[abs($hash) % count($todoPalette)];
                            };
                        @endphp
                        @forelse ($attendanceDataTodoList as $todo)
                            @php
                                $todoHasPhoto = !empty($todo->profileImg) && $todo->profileImg !== $todoDefaultPhoto;
                            @endphp
                            <div class="todoList-block">
                                <div class="img-circle">
                                    @if($todoHasPhoto)
                                        <img src="{{ $todo->profileImg }}" alt="image">
                                    @else
                                        <span class="ta-todo-initials" style="background:{{ $todoAvatarColor($todo->EmployeeName) }};">{{ $todoInitials($todo->EmployeeName) }}</span>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1">
@if($todo->action_type == "")
    {{ 'Action missing' }}
@endif                                        <strong>{{ $todo->message }}</strong>
                                    </p>
                                    <p class="mb-0 small">
                                        {{ $todo->EmployeeName }} - {{ $todo->ShiftName }}<br>
                                        @if($todo->action_type == 'check_in')
                                            Shift: {{ $todo->StartTime }} - {{ $todo->ExpectedEndTime ?? $todo->EndTime }}
                                        @else
                                            Expected Check-Out: {{ $todo->ExpectedEndTime ?? $todo->EndTime }}
                                        @endif
                                        for date {{ $todo->shift_date}}
                                    </p>
                                </div>
                                <div class="ta-todo-action">
                                    <button type="button"
                                        class="btn btn-sm {{ $todo->action_type == 'check_in' ? 'taa-btn-positive' : 'taa-btn-attention' }} manual-check-action"
                                        data-roster-id="{{ $todo->roster_id }}"
                                        data-action="{{ $todo->action_type }}"
                                        data-date="{{ $todo->shift_date }}"
                                        @if($todo->action_type == 'check_in')
                                            data-time="{{ $todo->StartTime }}"
                                        @else
                                           data-time="{{ $todo->ExpectedEndTime ?? $todo->EndTime }}"
                                        @endif
                                        data-employee-name="{{ $todo->EmployeeName }}"
                                        data-shift-name="{{ trim(($todo->ShiftName ?? '') . ' · ' . ($todo->StartTime ?? '') . ' - ' . ($todo->ExpectedEndTime ?? $todo->EndTime ?? ''), ' ·') }}"
                                        data-employee-image="{{ $todo->profileImg ?? '' }}">
                                        <i class="fa-solid {{ $todo->action_type == 'check_in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt' }} me-1"></i>
                                        {{ $todo->action_type == 'check_in' ? 'Check-In' : 'Check-Out' }}
                                    </button>
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
            <div class="col-xl-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.ViewDutyRoster',config('settings.resort_permissions.view')) == false) d-none @endif ">
                <div class="card h-auto" id="card-duty">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Duty Roster</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{route('resort.timeandattendance.ViewDutyRoster')}}" class="btn taa-btn-secondary btn-sm me-2  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.ViewDutyRoster',config('settings.resort_permissions.view')) == false) d-none @endif">View All Duty Roster</a>
                                <a href="{{route('resort.timeandattendance.CreateDutyRoster')}}" class="btn taa-btn-primary btn-sm  @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.CreateDutyRoster',config('settings.resort_permissions.view')) == false) d-none @endif">Create Duty Roster</a>
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
            <div class="col-xl-3 @if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.OverTime',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card" id="card-otHours">
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
            <div class="col-xl-3">
                @include('resorts.timeandattendance.dashboard.partials.wai-insights', ['waiInsights' => $waiInsights ?? [], 'cardId' => 'card-waiInsightsOT'])
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
                            <button type="submit" class="btn taa-btn-positive btn-sm todoListApprove" data-button="approve"><i  class="fa-solid fa-check me-2"></i>Approved</button>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn taa-btn-attention btn-sm todoListReject"  data-button="reject"><i class="fa-solid fa-xmark me-2"></i>Reject</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@include('partials._checkinout_modal')
@endsection

@section('import-css')
@include('resorts.timeandattendance._taa_buttons_v2_styles')
@include('resorts._dropdown_styles')
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

    const firstMonth = 0;   // January
    const lastMonth = 11;   // December
    const currentYear = new Date().getFullYear();

    // Generate labels like "Jan 26"
    for (let i = firstMonth; i <= lastMonth; i++) {
        const date = new Date(currentYear, i);
        const month = date.toLocaleString('en-US', { month: 'short' });
        const year = date.getFullYear().toString().slice(-2);

        labelsAttandance.push(`${month} ${year}`);
    }

    // Initialize the chart
    // Placeholder colour only — GetAttandance() below replaces
    // data.datasets wholesale with the backend response on load, so the
    // real colour is server-supplied (out of scope); this just keeps the
    // brief pre-AJAX flash on-theme.
    var _pTaa1 = window.WaiChart ? window.WaiChart.palette().teal : '#014653';
    myAttendance = new Chart(ctx2d, {
        type: 'bar',
        data: {
            labels: labelsAttandance,
            datasets: [{
                label: 'Attendance Percentage',
                data: new Array(12).fill(0),
                backgroundColor: _pTaa1,
                borderColor: _pTaa1,
                borderWidth: 1,
                borderRadius: 6,
                barThickness: 25
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return `${tooltipItem.raw}%`;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { stepSize: 20, callback: value => `${value}%` }
                }
            }
        }
    });
    if (window.WaiChart) window.WaiChart.registerForTheme(myAttendance);
}

    if (typeof myAttendance !== 'undefined') {
        GetAttandance();
       
    }
    $(".YearWiseDateattandance").on('change', function () {
          
            GetAttandance();
        });
    $("#DashboardDatePicker").on('change', function () {

        GetAttandance();
        GetmyOTHours();
        DutyRosterList();

        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        $.ajax({
            url: "{{ route('resort.timeandattendance.HrDashboardCount', ['date' => '__date__']) }}".replace('__date__', date),
            type: "get",

                success: function (response) {
                    if (response && response.data) {
                        $("#totalPresentEmployee").html(response.data.totalPresentEmployee);
                        $("#totalAbsantEmployee").html(response.data.totalAbsantEmployee);
                        $("#totalLeaveEmployee").html(response.data.totalLeaveEmployee);
                        $("#totalunknown_status_Employee").html(response.data.totalunknown_status_Employee);
                        
                    }
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });
    });
    function GetAttandance()
    {   
        
        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        let YearWiseDateattandance = $(".YearWiseDateattandance").val() || new Date().getFullYear();
        let deptId = '{{ base64_encode("All") }}';
        
        let url = "{{ route('resort.timeandattendance.GetYearHrWiseAttandanceData', ['Year' => '__Year__', 'Dept_id' => '__Dept_id__', 'date' => '__date__']) }}"
            .replace('__Year__', YearWiseDateattandance)
            .replace('__Dept_id__', deptId)
            .replace('__date__', date);
        
        $.ajax({
            url: url,
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
    // datasets are populated entirely from the AJAX response below (server-
    // supplied colours, out of scope) — only axes/legend/tooltip retheme.
    if (window.WaiChart) window.WaiChart.registerForTheme(myOTHours);
    GetmyOTHours()
    function GetmyOTHours()
    {
        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        let deptId = '{{ base64_encode("All") }}';

        $.ajax({
            url: "{{ route('resort.timeandattendance.HRMonthOverTimeChart', ['Dept_id' => '__Dept_id__', 'date' => '__date__']) }}".replace('__Dept_id__', deptId).replace('__date__', date),
                type: "get",

                success: function (response) {
                    if (response && response.labels && response.datasets) {
                        myOTHours.data.labels = response.labels;
                        myOTHours.data.datasets = response.datasets;
                        myOTHours.update();
                    } else {
                        console.error("Invalid OT Hours response format", response);
                    }
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });

    }

    $(document).ready(function () {
        // Load initial dashboard counts
        let date = new Date().toISOString().split('T')[0];
        let countUrl = "{{ route('resort.timeandattendance.HrDashboardCount', ['date' => '__date__']) }}".replace('__date__', date);
        
        $.ajax({
            url: countUrl,
            type: "get",
            success: function (response) {
                if (response && response.data) {
                    $("#totalPresentEmployee").html(response.data.totalPresentEmployee);
                    $("#totalAbsantEmployee").html(response.data.totalAbsantEmployee);
                    $("#totalLeaveEmployee").html(response.data.totalLeaveEmployee);
                    $("#totalunknown_status_Employee").html(response.data.totalunknown_status_Employee);

                }
            },
            error: function (xhr) {
                console.error("Failed to fetch dashboard count data", xhr);
            }
        });
        
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
    function DutyRosterList()
    {
        if ($.fn.DataTable.isDataTable('#DutyRoster'))
            {
                $('#DutyRoster').DataTable().destroy();
            }
            var ajaxUrl = "{{ route('resort.timeandattendance.HrDutyRosterdashboardTable')}}";

            // Fetch data via AJAX first, then initialize DataTables with client-side processing
            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    // Initialize DataTables with the fetched data
                    var tableData = response.data || [];
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
                },
                error: function(xhr, error, thrown) {
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

    // WAI Insights (now placed beside OT Hours) should match OT Hours'
    // own height rather than stretching to Duty Roster's height, which is
    // usually much taller (a full attendance table). addEventListener
    // (not window.onload = ...) so this can't silently clobber — or get
    // clobbered by — the window.onload assignment above; ResizeObserver
    // keeps it in sync if OT Hours' chart/legend content changes size later.
    function equalizeOtHoursInsightHeight() {
        var otCard = document.getElementById('card-otHours');
        var insightCard = document.getElementById('card-waiInsightsOT');
        if (!otCard || !insightCard) return;
        var otHeight = otCard.offsetHeight;
        if (!otHeight) return;
        insightCard.style.setProperty('height', otHeight + 'px', 'important');
    }
    document.addEventListener('DOMContentLoaded', equalizeOtHoursInsightHeight);
    window.addEventListener('load', equalizeOtHoursInsightHeight);
    window.addEventListener('resize', equalizeOtHoursInsightHeight);
    setTimeout(equalizeOtHoursInsightHeight, 500);
    if (window.ResizeObserver) {
        var otCardEl = document.getElementById('card-otHours');
        if (otCardEl) {
            new ResizeObserver(equalizeOtHoursInsightHeight).observe(otCardEl);
        }
    }

    // To Do List should match Attendance's own height exactly (Attendance's
    // canvas-based height is stable regardless of how many To Do items
    // exist, so it's the one being matched, not the other way around).
    // The card's own max-height:450px CSS fallback still applies before
    // this runs and stays as a safety cap if Attendance is ever taller
    // than that, keeping the list scrollable rather than unbounded.
    function equalizeAttendanceTodoHeight() {
        var attendanceCard = document.getElementById('card-attendance');
        var todoCard = document.getElementById('card-todoListTA');
        if (!attendanceCard || !todoCard) return;
        var attendanceHeight = attendanceCard.offsetHeight;
        if (!attendanceHeight) return;
        todoCard.style.setProperty('height', attendanceHeight + 'px', 'important');
    }
    document.addEventListener('DOMContentLoaded', equalizeAttendanceTodoHeight);
    window.addEventListener('load', equalizeAttendanceTodoHeight);
    window.addEventListener('resize', equalizeAttendanceTodoHeight);
    setTimeout(equalizeAttendanceTodoHeight, 500);
    if (window.ResizeObserver) {
        var attendanceCardEl = document.getElementById('card-attendance');
        if (attendanceCardEl) {
            new ResizeObserver(equalizeAttendanceTodoHeight).observe(attendanceCardEl);
        }
    }

    // Handle manual check-in/check-out actions
    // Confirm dialog is the shared openCheckInOutModal (see
    // partials._checkinout_modal); submit itself is unchanged.
    $(document).on("click", ".manual-check-action", function() {
        const rosterId = $(this).data('roster-id');
        const action = $(this).data('action');
        const employeeName = $(this).data('employee-name');
        const shiftName = $(this).data('shift-name');
        const employeeImage = $(this).data('employee-image');
        const date = $(this).data('date');
        const time = $(this).data('time');

        openCheckInOutModal({
            action: action,
            employeeName: employeeName,
            shiftLabel: shiftName,
            employeeImage: employeeImage,
            time: time,
            onConfirm: function (selectedTime, reset, close) {
                $.ajax({
                    url: "{{ route('resort.timeandattendance.ManualCheckInOut') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        roster_id: rosterId,
                        action: action,
                        date:date,
                        time:selectedTime
                    },
                    success: function(response) {
                        reset();
                        if (response.success) {
                            close();
                            wisdomAlert({
                                type: 'success',
                                title: 'Success!',
                                text: response.message
                            }).then(() => {
                                // Reload the page to refresh the todo list
                                window.location.reload();
                            });
                        } else {
                            wisdomAlert({
                                type: 'error',
                                title: 'Error!',
                                text: response.message || 'An error occurred.'
                            });
                        }
                    },
                    error: function(xhr) {
                        reset();
                        wisdomAlert({
                            type: 'error',
                            title: 'Error!',
                            text: 'An error occurred while processing the request.'
                        });
                        console.error('Error:', xhr);
                    }
                });
            }
        });
    });

    function confirmations(flag, itemId)
    {
        const action = flag === 'approve' ? 'approved' : 'rejected'; // Determine action based on flag

        wisdomConfirm({
            role: flag === 'approve' ? 'positive' : 'destructive',
            title: `Are you sure you want to ${flag} this OT?`,
            confirmText: `Yes, ${flag} it!`,
            cancelText: 'No, cancel'
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
                        wisdomAlert({
                            type: 'success',
                            title: `${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                            text: `The OT has been successfully ${action}.`
                        });
                        window.location.reload();

                        // Optional: Update the UI (e.g., remove the item or update status)
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        wisdomAlert({
                            type: 'error',
                            title: 'Error!',
                            text: 'An error occurred while processing the request.'
                        });

                        console.error(error);
                    }
                });
            } else {
                console.log('Action canceled');
            }
        });
    }

</script>
@include('resorts._dropdown_script')
@endsection

