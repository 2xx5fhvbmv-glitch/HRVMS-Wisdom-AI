
<?php $__env->startSection('page_tab_title' ,"Dashboard"); ?>

<?php if($message = Session::get('success')): ?>
    <div class="alert alert-success">
        <p><?php echo e($message); ?></p>
    </div>
<?php endif; ?>

<?php $__env->startSection('content'); ?>
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
                    

                </div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3 col-sm-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Employees</p>
                            <strong id="TotalEmployees"><?php echo e($EmployeesCount); ?></strong>
                        </div>
                        <a href="<?php echo e(route('resort.timeandattendance.employee')); ?>">
                            <img src="<?php echo e(URL::asset('resorts_assets/images/arrow-right-circle.svg')); ?>" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Present</p>
                            <strong id="totalPresentEmployee"><?php echo e($totalPresentEmployee); ?></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">On Leave</p>
                            <strong id="totalLeaveEmployee"><?php echo e($totalLeaveEmployee); ?></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.employee',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Absent</p>
                            <strong id="totalAbsantEmployee"><?php echo e($totalAbsantEmployee); ?></strong>
                        </div>
                        <a href="#">
                            <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.AttandanceRegister',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
                <div class="card">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Attendance</h3>
                            </div>
                            <div class="col-auto">
                                <div class="form-group">
                                    <select class="form-select YearWiseDateattandance" aria-label="Default select example">
                                        <?php for($i = -1; $i < 2; $i++): ?> <!-- Start from one year before the current year -->
                                        <?php
                                            $year = date('Y') + $i;
                                            $current = date("Y");
                                        ?>
                                            <option value="<?php echo e($year); ?>" <?php if($year == $current): ?> selected <?php endif; ?>>
                                                Jan <?php echo e($year); ?> - Dec <?php echo e($year); ?>

                                            </option>
                                        <?php endfor; ?>
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
                        <a href="<?php echo e(route('resort.timeandattendance.todolist')); ?>" class="a-link">View all</a>
                    </div>

                    <div class="todoList-main" style="max-height: 400px; overflow-y: auto;">
                        <?php $__empty_1 = true; $__currentLoopData = $attendanceDataTodoList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $todo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="todoList-block">
                                <div class="img-circle">
                                    <img src="<?php echo e($todo->profileImg); ?>" alt="image">
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1">
                                        <strong><?php echo e($todo->message); ?></strong>
                                    </p>
                                    <p class="mb-2 small">
                                        <?php echo e($todo->EmployeeName); ?> - <?php echo e($todo->ShiftName); ?><br>
                                        <?php if($todo->action_type == 'check_in'): ?>
                                            Shift: <?php echo e($todo->StartTime); ?> - <?php echo e($todo->ExpectedEndTime ?? $todo->EndTime); ?>

                                        <?php else: ?>
                                            Expected Check-Out: <?php echo e($todo->ExpectedEndTime ?? $todo->EndTime); ?>

                                        <?php endif; ?>
                                    </p>
                                    <button type="button" 
                                        class="btn btn-sm <?php echo e($todo->action_type == 'check_in' ? 'btn-danger' : 'btn-success'); ?> manual-check-action" 
                                        data-roster-id="<?php echo e($todo->roster_id); ?>"
                                        data-action="<?php echo e($todo->action_type); ?>"
                                        data-employee-name="<?php echo e($todo->EmployeeName); ?>">
                                        <i class="fa-solid <?php echo e($todo->action_type == 'check_in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt'); ?> me-1"></i>
                                        <?php echo e($todo->action_type == 'check_in' ? 'Check-In' : 'Check-Out'); ?>

                                    </button>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="todoList-block">
                                <p class="text-center text-muted">No pending actions for today.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.CreateDutyRoster',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?> ">
                <div class="card h-auto" id="card-duty">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3>Duty Roster</h3>
                            </div>
                            <div class="col-auto">
                                <a href="<?php echo e(route('resort.timeandattendance.ViewDutyRoster')); ?>" class="btn btn-themeSkyblue btn-sm me-2">View All Duty Roster</a>
                                <a href="<?php echo e(route('resort.timeandattendance.CreateDutyRoster')); ?>" class="btn btn-themeSkyblue btn-sm">Create Duty Roster</a>
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
            <div class="col-xl-3 <?php if(App\Helpers\Common::checkRouteWisePermission('resort.timeandattendance.OverTime',config('settings.resort_permissions.view')) == false): ?> d-none <?php endif; ?>">
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>

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
            url: "<?php echo e(route('resort.timeandattendance.HrDashboardCount', ['date' => '__date__'])); ?>".replace('__date__', date),
            type: "get",

                success: function (response) {
                    if (response && response.data) {
                        $("#totalPresentEmployee").html(response.data.totalPresentEmployee);
                        $("#totalAbsantEmployee").html(response.data.totalAbsantEmployee);
                        $("#totalLeaveEmployee").html(response.data.totalLeaveEmployee);
                    }
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
        let YearWiseDateattandance = $(".YearWiseDateattandance").val() || new Date().getFullYear();
        let deptId = '<?php echo e(base64_encode("All")); ?>';
        
        let url = "<?php echo e(route('resort.timeandattendance.GetYearHrWiseAttandanceData', ['Year' => '__Year__', 'Dept_id' => '__Dept_id__', 'date' => '__date__'])); ?>"
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
    GetmyOTHours()
    function GetmyOTHours()
    {
        let date  =  $("#DashboardDatePicker").val() ? $("#DashboardDatePicker").val().split('/').reverse().join('-') : new Date().toISOString().split('T')[0];
        let deptId = '<?php echo e(base64_encode("All")); ?>';

        $.ajax({
            url: "<?php echo e(route('resort.timeandattendance.HRMonthOverTimeChart', ['Dept_id' => '__Dept_id__', 'date' => '__date__'])); ?>".replace('__Dept_id__', deptId).replace('__date__', date),
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

    function equalizeHeights()
    {
        const block1 = document.getElementById('card-duty');
        const block2 = document.getElementById('card-todoList');
        if (block1 && block2) {
            const block1Height = block1.offsetHeight;
            block2.style.height = block1Height + 'px';
        }
    }

    window.onload = equalizeHeights;
    window.onresize = equalizeHeights;

    $(document).ready(function () {
        // Load initial dashboard counts
        let date = new Date().toISOString().split('T')[0];
        let countUrl = "<?php echo e(route('resort.timeandattendance.HrDashboardCount', ['date' => '__date__'])); ?>".replace('__date__', date);
        
        $.ajax({
            url: countUrl,
            type: "get",
            success: function (response) {
                if (response && response.data) {
                    $("#totalPresentEmployee").html(response.data.totalPresentEmployee);
                    $("#totalAbsantEmployee").html(response.data.totalAbsantEmployee);
                    $("#totalLeaveEmployee").html(response.data.totalLeaveEmployee);
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
            var ajaxUrl = "<?php echo e(route('resort.timeandattendance.HrDutyRosterdashboardTable')); ?>";

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

    function equalizeHeights()
    {
        const block1 = document.getElementById('card-duty');
        const block2 = document.getElementById('card-todoList');
        if (block1 && block2) {
            const block1Height = block1.offsetHeight;
            block2.style.height = block1Height + 'px';
        }
    }

    window.onload = equalizeHeights;
    window.onresize = equalizeHeights;

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
                    url: "<?php echo e(route('resort.timeandattendance.ManualCheckInOut')); ?>",
                    type: 'POST',
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>",
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
                    url: '<?php echo e(route("resort.timeandattendance.OTStatusUpdate")); ?>', // Replace with your backend endpoint
                    type: 'POST',
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>",
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
<?php $__env->stopSection(); ?>


<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/dashboard/hrdashboard.blade.php ENDPATH**/ ?>