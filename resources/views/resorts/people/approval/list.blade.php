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
                            <span>People </span>
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

                    </div>
                </div>
                <div class="list-main">
                    <table id="approval-request-table" class="table table-leaveReq w-100">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Request Type</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="approval-requests-body">
                            <!-- Dynamic rows will be inserted here -->
                        </tbody>
                    </table>
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
        datatablelist();

        $(document).on('click', '.action-btn', function (e) {
            e.preventDefault();
            var reqId = $(this).data('req_id');
            var approveUrl = $(this).data('approve_url');
            var method = $(this).data('method');
            var action = $(this).data('action');
            var status = $(this).data('status');
            const key = $('.action-btn').data('key');


            if (method == 'POST') {
                if (action === 'Rejected') {
                    $('#rejectionModal').modal('show');
                    $('#confirmRejectBtn').on('click', function () {
                        e.preventDefault();

                        var reqId = $('.action-btn').data('req_id');
                        var approveUrl = $('.action-btn').data('approve_url');
                        var method = $('.action-btn').data('method');
                        var action = $('.action-btn').data('action');
                        var status ='Rejected';

                        const key = $('.action-btn').data('key');
                        const reason = $('#rejectionReason').val();

                        var rejectReason = $('#rejectionReason').val();

                        if (!rejectReason.trim()) {
                            toastr.error('Reject reason is required.', 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                            return;
                        }

                        $.ajax({
                            url: approveUrl,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                [key]: reqId,
                                status: status,
                                 action: action,
                                reject_reason: rejectReason,
                            },
                            success: function (response) {
                                if (response.success) {
                                    toastr.success(response.message, 'Success', {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    $('#rejectionModal').modal('hide');
                                    $('#rejectionReason').val(''); 
                                    datatablelist();
                                } else {
                                    toastr.error(response.message, 'Error', {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            },
                            error: function (xhr) {
                                toastr.error(xhr.responseJSON.message, 'Error', {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                    });
                
                } else {
                
                    $.ajax({
                    url: approveUrl,
                    type: method,
                    data: {
                        [key]: reqId,
                        status: status,
                        action: action,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message);
                            datatablelist();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON.message);
                    }
                    });
                
                }
            }
        });

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
    
    function datatablelist() {
    $('#approval-request-table tbody').empty();

    if ($.fn.DataTable.isDataTable('#approval-request-table'))
    {
        $('#approval-request-table').DataTable().destroy();
    }

    // Initialize DataTable with AJAX for server-side processing
    var table = $('#approval-request-table').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 10,
        processing: true,
        serverSide: true,
        order:[[5, 'desc']],
        ajax: {
            url: "{{ route('people.approvel.index') }}",
            type: "GET",
            data: function(d) {
                d.department_id = $('#department-filter').val();
                d.position_id = $('#position-filter').val();
                d.search = $('#search-box').val();
            }
        },
        columns: [
           { data: 'emp_id', name: 'emp_id' },
           { data: 'name', name: 'name' },
           { data: 'position', name: 'position' },
           { data: 'department', name: 'department' },
           { data: 'request_type', name: 'request_type' },
           { data: 'created_at', name: 'created_at' },
           { data: 'status', name: 'status'},
           { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            processing: "Processing...",
            lengthMenu: "Show _MENU_ entries",
            zeroRecords: "No matching records found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last", 
                next: "Next",
                previous: "Previous"
            }
        }
    });

    return table;
}
</script>
@endsection
