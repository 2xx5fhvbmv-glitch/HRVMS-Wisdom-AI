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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave </span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" id="search-box" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-5 col-sm-4 col-6">
                            <select id="department-filter" class="form-select select2t-none Department "name="department" aria-label="Default select example">
                                <option value="">All Departments</option>
                                @if($resort_departments)
                                    @foreach($resort_departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="position-filter" class="form-select select2t-none mb-2 Position" name="position" aria-label="Default select example">
                                <option selected value="">Select Position</option>
                            </select>
                        </div>

                        <div class="col-auto ms-auto">
                            <a href="javascript:void(0);" class="btn btn-grid active"><img src="{{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="javascript:void(0);" class="btn btn-list "><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
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
                <div class="grid-main d-block">
                    <div class="row g-md-4 g-3 mb-4" id="results-container" >
                        
                        @if($finalLeaveRequests->count())
                            @php
                                $leaves = [];
                                $colors = [];
                                $totalDays = 0;
                                $todate;
                            @endphp
                            @foreach($finalLeaveRequests as $request)
                                @if(isset($request->CombineLeave) && !empty($request->CombineLeave))
                                    @foreach($request->CombineLeave as $leave)
                                        @php
                                            // Append the leave types to the $leaves array
                                            $leaves[] = $leave[0]->leave_type; // Add the first leave type
                                            $leaves[] = $request->leave_type; // Add the current request leave type
                                            $totalDays = $leave[0]->total_days + $request->total_days;
                                            $todate = $leave[0]->to_date;
                                            $colors[] = $leave[0]->color;
                                            $colors[] =  $request->color;;
                                        @endphp

                                    @endforeach
                                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                        <div class="leaveReqGrid-block">
                                            <div class="dot" style="background:{{ $request->color ?? '#ccc' }}"></div>
                                            <div class="img-circle">
                                                <img src="{{ $request->profile_picture ? $request->profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                                            </div>
                                            <h6>{{ $request->first_name ?? 'N/A' }} {{ $request->last_name ?? '' }}</h6>
                                            <span class="badge badge-themeLight">#{{ $request->employee_id ?? 'N/A' }}</span>
                                            <p>{{ $request->position ?? 'N/A' }}</p>
                                            <span class="position">{{ $request->department ?? 'N/A' }}</span>
                                            <div class="bg">
                                                <i>Applied On: {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</i>
                                                <div>
                                                    <div class="bg-white date-block">
                                                        {{ \Carbon\Carbon::parse($request->from_date)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($request->from_date)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($request->from_date)->format('D') }}
                                                    </div>
                                                    <div>
                                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg') }}" alt="">
                                                        {{ $totalDays }} days
                                                    </div>
                                                    <div class="bg-white date-block">
                                                        {{ \Carbon\Carbon::parse($todate)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($todate)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($todate)->format('D') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex">
                                                @foreach($leaves as $key => $name)
                                                    <span class="badge" style="color:{{ $colors[$key] }}; background:{{$colors[$key]}}1F;">
                                                        {{ $name ?? 'N/A' }}
                                                    </span>
                                                @endforeach
                                                @if($request->attachments)
                                                    <a href="{{ URL::asset($request->attachments) }}" target="_blank">
                                                        <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="icon">
                                                    </a>
                                                @endif
                                            </div>
                                            <p class="text-start">{{ $request->reason ?? 'No reason provided' }}</p>
                                            <div class="bg leave">
                                                <p><a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="a-link">View Leave Balance</a></p>
                                            </div>
                                            
                                            <div class="btn-block">
                                                <a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="btn btn-themeSkyblue btn-sm @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">View</a>
                                                @if($request->approval_status == 'Pending')
                                                    <button class="btn btn-themeBlue btn-sm approve-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{ $request->id }}"   >Approve</button>
                                                    <button class="btn btn-danger btn-sm reject-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{ $request->id }}">Reject</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                        <div class="leaveReqGrid-block">
                                            <div class="dot" style="background:{{ $request->color ?? '#ccc' }}"></div>
                                            <div class="img-circle">
                                                <img src="{{ $request->profile_picture ? $request->profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                                            </div>
                                            <h6>{{ $request->first_name ?? 'N/A' }} {{ $request->last_name ?? '' }}</h6>
                                            <span class="badge badge-themeLight">#{{ $request->employee_id ?? 'N/A' }}</span>
                                            <p>{{ $request->position ?? 'N/A' }}</p>
                                            <span class="position">{{ $request->department ?? 'N/A' }}</span>
                                            <div class="bg">
                                                <i>Applied On: {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</i>
                                                <div>
                                                    <div class="bg-white date-block">
                                                        {{ \Carbon\Carbon::parse($request->from_date)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($request->from_date)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($request->from_date)->format('D') }}
                                                    </div>
                                                    <div>
                                                        <img src="{{ URL::asset('resorts_assets/images/arrow.svg') }}" alt="">
                                                        {{ $request->total_days }} days
                                                    </div>
                                                    <div class="bg-white date-block">
                                                        {{ \Carbon\Carbon::parse($request->to_date)->format('M') }}
                                                        <h5>{{ \Carbon\Carbon::parse($request->to_date)->format('d') }}</h5>
                                                        {{ \Carbon\Carbon::parse($request->to_date)->format('D') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex">
                                                <span class="badge" style="color:{{ $request->color }}; background:{{ $request->color }}1F;">{{ $request->leave_type ?? 'N/A' }}</span>
                                                @if($request->attachments)
                                                    <a href="{{ URL::asset($request->attachments) }}" target="_blank">
                                                        <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="icon">
                                                    </a>
                                                @endif
                                            </div>
                                            <p class="text-start">{{ $request->reason ?? 'No reason provided' }}</p>
                                            <div class="bg leave">
                                                <p><span class="text-lightblue">{{$request -> available_balance}}</span> Leaves Available</p>
                                            </div>
                                            <div class="btn-block">
                                                <a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="btn btn-themeSkyblue btn-sm @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.view')) == false) d-none @endif">View</a>
                                                @if($request->approval_status == 'Pending')
                                                    <button class="btn btn-themeBlue btn-sm approve-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{ $request->id }}">Approve</button>
                                                    <button class="btn btn-danger btn-sm reject-btn @if(App\Helpers\Common::checkRouteWisePermission('leave.request',config('settings.resort_permissions.edit')) == false) d-none @endif" data-leave-id="{{ $request->id }}">Reject</button>
                                                @endif
                                                    <!-- <button class="btn btn-themeBlue btn-sm approve-btn" data-leave-id="{{ $request->id }}">Approve</button>
                                                <button class="btn btn-danger btn-sm reject-btn" data-leave-id="{{ $request->id }}">Reject</button> -->
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                                <h5>No Request Found.</h5>
                            </div>
                        @endif
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

    // new DataTable('#example');
    $(document).ready(function () {
        $(".select2t-none").select2();

        // Handle Department change event
        $(document).on('change', '.Department', function () {
            const deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "POST",
                data: { deptId: deptId },
                success: function (response) {
                    $(".Position").html('<option value="">Select Position</option>'); // Reset Position dropdown

                    if (response.success) {
                        let positionOptions = '<option value=""></option>';
                        $.each(response.data.ResortPosition, function (key, value) {
                            positionOptions += `<option value="${value.id}">${value.position_title}</option>`;
                        });
                        $(".Position").html(positionOptions);
                        $(".Position").select2({ placeholder: "Select Position" });
                    } else {
                        toastr.warning("No Positions found for the selected Department.", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error("Error fetching Positions.", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        let currentLeaveId = null; // To track the leave ID being rejected

        $(document).on('click', '.approve-btn', function() {
            const leaveId = $(this).data('leave-id');

            // Perform the approval action
            handleLeaveAction(leaveId, 'Approved', '');
        });

        $(document).on('click', '.reject-btn', function() {
            currentLeaveId = $(this).data('leave-id');

            // Show the rejection modal
            $('#rejectionModal').modal('show');
        });

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

        // Grid view toggle
        $(".btn-grid").click(function () {
            $(this).addClass("active");
            $(".grid-main").addClass("d-block").removeClass("d-none");
            $(".btn-list").removeClass("active");
            $(".list-main").addClass("d-none").removeClass("d-block");
            applyFilters(); // Apply filters when switching to grid
        });

        // List view toggle
        $(".btn-list").click(function () {
            $(this).addClass("active");
            $(".list-main").addClass("d-block").removeClass("d-none");
            $(".btn-grid").removeClass("active");
            $(".grid-main").addClass("d-none").removeClass("d-block");

            // Adjust DataTable after switching to list view
            if ($.fn.DataTable.isDataTable('#leave-request-table')) {
                $('#leave-request-table').DataTable().columns.adjust().draw();
            }

            // Apply filters when switching to list view
            datatablelist();
        });

       // Filters for search, department, and position
        $(document).on('keyup', '.search', function() {
            applyFilters();
            datatablelist();  // Apply datatable list after filters are updated
        });

        $(document).on('change', '#position-filter, #department-filter', function() {
            applyFilters();
            datatablelist();  // Apply datatable list after filters are updated
        });
    });

    function applyFilters() {
        let search = document.querySelector('#search-box').value;
        let department = document.querySelector('#department-filter').value;
        let position = document.querySelector('#position-filter').value;

        $.ajax({
            url: "{{ route('leave.filter.grid') }}",
            type: "GET",
            data: {
                "_token": "{{ csrf_token() }}",
                "search": search,
                "position": position,
                "department": department
            },
            success: function (response) {
                // Update grid view
                $('#results-container').html(response.html);
            },
            error: function (response) {
                console.error('Error:', response);
                toastr.error('An error occurred while fetching leave requests.', { positionClass: 'toast-bottom-right' });
            }
        });
    }

    function formatDate(dateString, format) {
        const date = new Date(dateString);
        const options = {
            d: { day: '2-digit' },
            dd: { day: 'numeric' },
            MMM: { month: 'short' },
            EEE: { weekday: 'short' },
            yyyy: { year: 'numeric' },
            'd MMM, yyyy': { day: '2-digit', month: 'short', year: 'numeric' },
        };
        const formatter = new Intl.DateTimeFormat('en-US', options[format]);
        return formatter.format(date);
    }

    function datatablelist() {
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
                var positionId = $('#position-filter').val();
                var search = $('#search-box').val();

                $.ajax({
                    url: "{{ route('leave-requests.get') }}",
                    method: "GET",
                    data: {
                        department_id: departmentId,
                        position_id: positionId,
                        search: search,
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
                            return 'No attachements';  // Return an empty string if there's no attachment
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
    }
</script>
@endsection
