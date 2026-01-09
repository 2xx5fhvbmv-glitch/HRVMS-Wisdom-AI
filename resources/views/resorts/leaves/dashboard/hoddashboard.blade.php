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
                            <span>Leave</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="leaveDashHod-main">
                <div class="row g-3 g-xxl-4 card-heigth  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Total Applied Leaves</p>
                                    <strong>{{$total_applied_leaves ?? 0}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Approved</p>
                                    <strong>{{$total_approved_leaves ?? 0}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Rejected</p>
                                    <strong>{{$total_rejected_leaves ?? 0}}</strong>
                                </div>
                                <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Pending</p>
                                    <strong>{{$total_pending_leaves ?? 0}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card" id="card-onLeave">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-1">
                                    <div class="col">
                                        <h3>Who's On Leave</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group" style="width: 110px;">
                                            <select class="form-select filter-leave" aria-label="Default select example" data-url="{{ route('getEmployeesOnLeave') }}">
                                                <option value="Today" selected>Today</option>
                                                <option value="Tomorrow">Tomorrow</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-main" id="onleave">
                                <!-- Leave list will be dynamically updated here -->
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card" id="card-upLeave">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-1">
                                    <div class="col">
                                        <h3>Upcoming Leaves</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <select class="form-select" id="upcomingLeaveFilter" aria-label="Filter Upcoming Leaves">
                                                <option value="week" selected>This Week</option>
                                                <option value="month">This Month</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-main" id="upcomingLeavesContainer">
                                <!-- Dynamic content will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-auto" id="card-chartLeave ">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-1">
                                    <div class="col">
                                        <h3 class="text-nowrap">Leave Categories</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <select class="form-select YearWiseLeaveHistory" aria-label="Default select example">
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
                            <canvas id="myStackedBarChart" class="mb-3"></canvas>
                        </div>
                    </div>

                    <div class="col-12  @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-auto">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-md-3 g-1">
                                    <div class="col">
                                        <h3 class="text-nowrap">Leave Requests</h3>
                                    </div>
                                    <div class="col-auto">
                                        <!-- <div class="form-group">
                                            <select id="department-filter" class="form-select select2t-none" aria-label="Default select example">
                                                <option value="">All Departments</option>
                                                @if($resort_departments)
                                                    @foreach($resort_departments as $dept)
                                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div> -->
                                    </div>
                                    <div class="col-auto"><a href="{{route('leave.request')}}" class="a-link">View All</a></div>
                                </div>
                            </div>
                            <table id="leave-request-table" class="table table-leaveReq w-100">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Department</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Total Days</th>
                                        <th>Attachment</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="leave-requests-body">
                                    <!-- Dynamic rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-wiINsight">
                            <div class="card-title">
                                <h3>AI Insight's</h3>
                            </div>
                            <div class="leaveUser-main">
                                <div class="leaveUser-block">
                                    <div class="img">
                                        <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Low occupancy for upcoming 3 months</h6>
                                        <P>You can send 55 Employees on vacation leave this time so Resort can save
                                            around
                                            $1000</P>
                                    </div>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                        <a href="#" class="a-link">Request Leave</a>
                                    </div>
                                </div>
                                <div class="leaveUser-block">
                                    <div class="img">
                                        <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>AI Forecasted Peak Leave Periods</h6>
                                        <P>01 Jan to 30 March is peak leave period</P>
                                    </div>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                                <div class="leaveUser-block">
                                    <div class="img">
                                        <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Employee Leave Behavior Analysis</h6>
                                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                            Lorem
                                            Ipsum has been the industry.</p>
                                    </div>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-upcomingLeve">
                            <div class="card-title">
                                <div class="row g-1">
                                    <div class="col">
                                        <h3>Upcoming Public Holidays</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{route('resort.upcomingholiday.list')}}" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-main">
                                @if($upcomingHolidays && $upcomingHolidays->count() > 0)
                                    @foreach($upcomingHolidays as $holiday)
                                        <div class="leaveUser-bgBlock">
                                            <h6>{{ $holiday->PublicHolidayName }}</h6>
                                            <p>
                                                {{ \Carbon\Carbon::parse($holiday->PublicHolidaydate)->format('d M, D') }}
                                            </p>
                                        </div>
                                    @endforeach
                                @else
                                    <p>No upcoming holidays available.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

<!-- Modal HTML -->
<div id="rejectionModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reason for Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Enter a reason (optional)"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">

    $(document).ready(function () {
        function fetchLeaveData(filter) {
            const url = $('.filter-leave').data('url'); // Get the route URL from the dropdown

            $.ajax({
                url: url,
                type: 'GET',
                data: { filter: filter },
                success: function (data) {
                    const leaveList = $('#onleave');
                    leaveList.empty(); // Clear existing content
                    console.log(data.data);
                    if (data.data.length>0) {
                        data.data.forEach(employee => {
                            leaveList.append(`
                                <div class="leaveUser-block">
                                    <div class="img-circle">
                                        <img src="${employee.profile_picture}" alt="image">
                                    </div>
                                    <div>
                                        <h6>${employee.first_name} ${employee.last_name}</h6>
                                        <p>${employee.position}</p>
                                    </div>
                                    <div><span class="badge" style="color:${employee.color}; background:${employee.color}1F;">${employee.leave_type}</span></div>
                                </div>
                            `);
                        });
                    } else {
                        leaveList.append('<p class="text-center">No employees on leave for the selected date.</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching leave data:', error);
                }
            });
        }

        // Fetch data on dropdown change
        $('.filter-leave').on('change', function () {
            const filter = $(this).val();
            fetchLeaveData(filter);
        });

        // Trigger default fetch for 'Today'
        fetchLeaveData('Today');

        function loadUpcomingLeaves(filter) {
            $.ajax({
                url: "{{ route('getUpcomingLeaves') }}", // Add the correct route name here
                type: "GET",
                data: { filter: filter },
                beforeSend: function () {
                    $('#upcomingLeavesContainer').html('<p>Loading...</p>');
                },
                success: function (response) {
                    let content = '';
                    if (response.data.length > 0) {
                        response.data.forEach(employee => {
                            content += `
                                <div class="leaveUser-block">
                                    <div class="img-circle">
                                        <img src="${employee.profile_picture}" alt="image">
                                    </div>
                                    <div>
                                        <h6>${employee.first_name} ${employee.last_name}</h6>
                                        <p>${employee.position}</p>
                                        <span class="badge badge-themeNew1"><i class="fa-regular fa-calendar"></i> ${employee.leave_dates}</span>
                                    </div>
                                    <div>
                                        <span class="badge" style="color:${employee.color}; background:${employee.color}1F;">${employee.leave_type}</span>
                                        <span class="total">Total: ${employee.total_days}</span>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        content = '<p>No upcoming leaves found.</p>';
                    }
                    $('#upcomingLeavesContainer').html(content);
                },
                error: function () {
                    $('#upcomingLeavesContainer').html('<p>Error loading data.</p>');
                }
            });
        }

        // Load initial data for this week
        loadUpcomingLeaves('week');

        // Reload data when filter changes
        $('#upcomingLeaveFilter').change(function () {
            const filter = $(this).val();
            loadUpcomingLeaves(filter);
        });

        $('#leave-request-table tbody').empty();
        if ($.fn.DataTable.isDataTable('#leave-request-table'))
        {
            $('#leave-request-table').DataTable().destroy();
        }
        // Initialize DataTable with AJAX for server-side processing
        var table = $('#leave-request-table').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            ajax: function(data, callback, settings) {
                // Get the department filter value
                var departmentId = $('#department-filter').val();

                $.ajax({
                    url: "{{ route('leave-requests.get') }}",
                    method: "GET",
                    data: {
                        department_id: departmentId,
                        start: settings.start, // For pagination
                        length: settings.length, // For pagination
                    },
                    success: function(response) {
                        callback({
                            draw: settings.draw,
                            recordsTotal: response.recordsTotal, // Total records
                            recordsFiltered: response.recordsFiltered, // Filtered records
                            data: response.data // Data for the current page
                        });
                    }
                });
            },
            columns: [
                { data: 'employee_id' },
                { data: 'full_name', render: function(data, type, row) {
                    return '<div class="tableUser-block"><div class="img-circle"><img src="'+row.profile_picture+'" alt="user"></div><span class="userApplicants-btn">'+ row.first_name + ' ' + row.last_name + '</span></div>';
                }},
                { data: 'department', render: function(data, type, row) {
                    return '<td>'+row.department+' <span class="badge badge-themeLight">'+row.code+'</span></td>';
                }},
                { data: 'from_date' },
                { data: 'to_date' },
                { data: 'total_days' },
                { data: 'attachments',
                    render: function(data, type, row) {
                        // Check if there's an attachment
                        if (row.attachments) {
                            return `<a href="${row.attachments}" target="_blank">
                                        <img src="/resorts_assets/images/pdf1.svg" alt="icon">
                                    </a>`;
                        } else {
                            return 'No attachements'; // Return an empty string if there's no attachment
                        }
                    }
                },
                {   
                    data: 'leave_status',
                    render: function(data, type, row) {
                        let statusClass = 'badge-secondary'; // Default class

                        // Check for specific keywords in the status text and assign the appropriate class
                        if (row.status_text.includes('Approved')) {
                            statusClass = 'badge-themeSuccess'; // Green for approved
                        } else if (row.status_text.includes('Rejected')) {
                            statusClass = 'badge-themeDanger'; // Red for rejected
                        } else if (row.status_text.includes('Pending')) {
                            statusClass = 'badge-themeWarning'; // Yellow for pending
                        }

                        // Render the badge with the dynamic class and status text
                        return `<span class="badge ${statusClass}">${row.status_text}</span>`;
                    }
                },
                {
                    data: 'action',
                    render: function(data, type, row , meta) {
                        var permission = meta.settings.json ? meta.settings.json.permission : '';
                        return `
                            <a title="Leave Details" href="${row.routes}" class="eye-btn mx-1 ">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="#" class="correct-btn mx-1 approve-btn ${permission}" data-leave-id="${row.id}">
                                    <i class="fa-solid fa-check"></i>
                            </a>
                            <a href="#" class="close-btn mx-1 reject-btn ${permission}" data-leave-id="${row.id}">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        `;
                    }
                },
            ],
        });

        // Trigger table reload when department filter changes
        $('#department-filter').on('change', function() {
            table.ajax.reload(); // Reload the DataTable with the new department filter
        });

        let currentLeaveId = null; // To track the leave ID being rejected

        $('#leave-request-table').on('click', '.approve-btn', function () {
            const leaveId = $(this).data('leave-id');

            // Perform the approval action
            handleLeaveAction(leaveId, 'Approved', '');
        });

        $('#leave-request-table').on('click', '.reject-btn', function () {
            currentLeaveId = $(this).data('leave-id');

            // Show the rejection modal
            $('#rejectionModal').modal('show');
        });

        $('#confirmRejectBtn').on('click', function () {
            const reason = $('#rejectionReason').val(); // Get the reason (optional)

            // Perform the rejection action
            handleLeaveAction(currentLeaveId, 'Rejected', reason);

            // Hide the modal
            $('#rejectionModal').modal('hide');
            $('#rejectionReason').val(''); // Clear the input for the next use
        });

        function handleLeaveAction(leaveId, action, reason) {
            $.ajax({
                url: "{{route('leave.handleAction')}}",
                method: 'POST',
                data: {
                    leave_id: leaveId,
                    action: action,
                    reason: reason,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    // console.log(response);
                    if (response.status == "success") {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 403) {
                        let response = xhr.responseJSON;
                        if (response.status == "error") {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    } else {
                        toastr.error("An unexpected error occurred. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        }
    });

    //    equal heigth js 
    function equalizeHeights() {
        // Get the elements
        const block1 = document.getElementById('card-chartLeave');
        const block2_1 = document.getElementById('card-upLeave');
        const block2_2 = document.getElementById('card-onLeave');

        // Check if elements exist
        if (block1 && block2_1 && block2_2) {
            // Get the height of block1
            const block1Height = block1.offsetHeight;

            // Set the height of block2 elements to match block1's height
            block2_1.style.height = block1Height + 'px';
            block2_2.style.height = block1Height + 'px';
        }
    }

    window.onload = equalizeHeights; // Initial height adjustment

    // Adjust heights on window resize
    window.onresize = equalizeHeights;
</script>
<script type="module">
    var ctx = document.getElementById('myStackedBarChart').getContext('2d');
    var myStackedBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 20
                    }
                }
            }
        }
    });

    // Function to fetch and update the chart
    function GetLeaveHistory() {
        $.ajax({
            url: "{{ route('leave-chart-data') }}", // Replace with your actual route
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "YearWiseLeaveHistory": $(".YearWiseLeaveHistory").val()
            },
            success: function (response) {
                myStackedBarChart.data.labels = response.labels;
                myStackedBarChart.data.datasets = response.datasets;
                myStackedBarChart.update();
            },
            error: function (xhr) {
                console.error("Failed to fetch chart data", xhr);
            }
        });
    }

    // Trigger data load on dropdown change
    $(document).on("change", ".YearWiseLeaveHistory", function () {
        GetLeaveHistory();
    });

    // Initial chart load
    GetLeaveHistory();

</script>
@endsection