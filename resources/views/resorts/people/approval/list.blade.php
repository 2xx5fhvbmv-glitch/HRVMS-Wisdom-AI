@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #people-approvel-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #people-approvel-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding" id="people-approvel-hero">
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
                            <select id="department-filter" class="form-select Department dd-native-select" name="department" aria-label="Default select example">
                                <option value="">All Departments</option>
                                @if($resort_departments)
                                    @foreach($resort_departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#department-filter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Departments</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($resort_departments)
                                            @foreach($resort_departments as $dept)
                                                <div class="dd-item" role="option" data-value="{{$dept->id}}"><span class="dd-nm">{{$dept->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Position filter hidden by request. Select stays in
                             the DOM (display:none) because JS elsewhere reads
                             $('.Position') / $('#position-filter') and would
                             otherwise error. --}}
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6" style="display:none;">
                            <select id="position-filter" class="form-select mb-2 Position dd-native-select" name="position" aria-label="Default select example">
                                <option selected value="">Select Position</option>
                            </select>
                            <div class="dd" data-target="#position-filter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Position</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
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
                    <h5 class="modal-title" id="reasonModalTitle">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Enter a reason"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRejectBtn" class="btn eb-btn-critical">Reject</button>
                </div>
            </div>
        </div>
    </div>
    <div id="approvalViewModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="approvalViewBody"></div>
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script type="text/javascript">

    // new DataTable('#example');
    $(document).ready(function () {
        $(".select2t-none").select2();
        datatablelist();

        // Cache the originating click's row data so the reject modal's Confirm
        // button uses THIS row's values, not whichever button jQuery happened to
        // pick first when querying `.action-btn`.
        var pendingReject = null;

        // Eye icon for types whose detail view is a modal partial
        // (show() returns {status, html}). Read-only here — act with the
        // row's own buttons, so the partial's footer buttons are dropped.
        $(document).on('click', '.view-modal-btn', function (e) {
            e.preventDefault();
            $.get($(this).attr('href'), function (response) {
                if (response && response.status === 'success') {
                    $('#approvalViewBody').html(response.html).find('.modal-footer').remove();
                    $('#approvalViewModal').modal('show');
                } else {
                    toastr.error('Request not found.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            }).fail(function () {
                toastr.error('Something went wrong.', 'Error', { positionClass: 'toast-bottom-right' });
            });
        });

        $(document).on('click', '.action-btn', function (e) {
            e.preventDefault();
            var $btn       = $(this);
            var reqId      = $btn.data('req_id');
            var approveUrl = $btn.data('approve_url') || $btn.data('hold_url') || $btn.data('reject_url') || $btn.attr('href');
            var method     = ($btn.data('method') || 'POST').toUpperCase();
            var action     = $btn.data('action');
            var status     = $btn.data('status');
            var key        = $btn.data('key');
            // The endpoint's own parameters (e.g. payroll action=reject, budget year/department,
            // salary-advance stage) — merged last so they override the generic status/action.
            var payload    = $btn.data('payload') || {};
            var needsReason = !!$btn.data('needs_reason');

            if (!approveUrl || approveUrl === 'javascript:void(0)') {
                toastr.error('Action URL missing.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }

            // Reject — always open the reason modal regardless of method, then
            // submit as POST with reject_reason. (GET-style endpoints like
            // promotion.review.action still accept the same payload.)
            if (action === 'Rejected' || needsReason) {
                var isHold = action !== 'Rejected';
                pendingReject = { reqId: reqId, approveUrl: approveUrl, method: method, action: action, status: isHold ? status : 'Rejected', key: key, payload: payload };
                $('#reasonModalTitle').text(isHold ? 'Reason for Hold' : 'Reason for Rejection');
                $('#confirmRejectBtn').text(isHold ? 'Put on Hold' : 'Reject');
                $('#rejectionReason').val('');
                $('#rejectionModal').modal('show');
                return;
            }

            // Approve / Hold — fire the request. Use the action's declared
            // method (GET or POST) so promotion/transfer GET endpoints work
            // the same way as POST endpoints (info-update, resignation, etc.).
            $.ajax({
                url: approveUrl,
                type: method,
                data: (function () {
                    var d = { status: status, action: action, _token: $('meta[name="csrf-token"]').attr('content') };
                    if (key) d[key] = reqId;
                    return $.extend(d, payload);
                })(),
                success: function (response) {
                    // Promotion / transfer / resignation endpoints return
                    // different success shapes — some `{success: true}`,
                    // some `{status: 'success'}`. Treat either as success.
                    var isSuccess = response && (response.success === true || response.status === 'success');
                    var isError   = response && (response.success === false || response.status === 'error');

                    if (isSuccess) {
                        toastr.success(response.message || response.msg || 'Done.', 'Success', { positionClass: 'toast-bottom-right' });
                        // Refresh the page so the actioned row drops out
                        // of the inbox and counters / cached state are
                        // re-read. Follows redirect_url when provided
                        // (eg. promotion returns the list URL), otherwise
                        // reloads the current inbox.
                        setTimeout(function () {
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        }, 400);
                    } else if (isError) {
                        toastr.error(response.message || response.msg || 'Action failed.', 'Error', { positionClass: 'toast-bottom-right' });
                    } else if (response && response.message) {
                        // Ambiguous shape — server didn't 4xx/5xx and
                        // sent a message but no success / status flag.
                        // Surface it as success rather than error.
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function () { window.location.reload(); }, 400);
                    } else {
                        // No JSON body (HTML / redirect response).
                        toastr.success('Updated.', 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function () { window.location.reload(); }, 400);
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg || xhr.responseJSON.error)) || 'Action failed.';
                    toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Reject confirmation — bound ONCE with .off/.on so it doesn't stack
        // across repeated reject clicks.
        $(document).off('click', '#confirmRejectBtn').on('click', '#confirmRejectBtn', function () {
            if (!pendingReject) return;
            var rejectReason = ($('#rejectionReason').val() || '').trim();
            if (!rejectReason) {
                toastr.error('A reason is required.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }
            var p = pendingReject;
            $.ajax({
                url: p.approveUrl,
                type: p.method,
                data: (function () {
                    var d = {
                        status: p.status,
                        action: p.action,
                        // Each module reads its own key for the reason.
                        reject_reason: rejectReason,
                        rejection_reason: rejectReason,
                        hold_reason: rejectReason,       // resignation hold
                        reason: rejectReason,            // learning, visa payment
                        remarks: rejectReason,           // payroll
                        ReviseBudgetComment: rejectReason, // budget
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    };
                    if (p.key) d[p.key] = p.reqId;
                    return $.extend(d, p.payload || {});
                })(),
                success: function (response) {
                    var isErr = response && (response.success === false || response.status === 'error');
                    if (!isErr) {
                        toastr.success((response && (response.message || response.msg)) || 'Done.', 'Success', { positionClass: 'toast-bottom-right' });
                        $('#rejectionModal').modal('hide');
                        $('#rejectionReason').val('');
                        pendingReject = null;
                        // Refresh so the rejected row drops out of the
                        // inbox immediately. Same redirect/reload pattern
                        // as the Approve/Hold flow above.
                        setTimeout(function () {
                            if (response && response.redirect_url) {
                                window.location.href = response.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        }, 400);
                    } else {
                        toastr.error(response.message || response.msg || 'Action failed.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg || xhr.responseJSON.error)) || 'Action failed.';
                    toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $(document).on('change', '.Department', function () {
            const deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "POST",
                data: { deptId: deptId, "_token": "{{ csrf_token() }}" },
                success: function (response) {
                    $(".Position").html('<option value="">Select Position</option>'); // Reset Position dropdown

                    if (response.success) {
                        let positionOptions = '<option value="">Select Position</option>';
                        $.each(response.data.ResortPosition, function (key, value) {
                            positionOptions += `<option value="${value.id}">${value.position_title}</option>`;
                        });
                        $(".Position").html(positionOptions);
                    } else {
                        toastr.warning("No Positions found for the selected Department.", { positionClass: 'toast-bottom-right' });
                    }
                    window.wisdomDD.rebuild('#position-filter');
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
        // Reuse the existing table (reload its rows) and still return it, as the function did before.
        var existingTable = $('#approval-request-table').DataTable();
        existingTable.ajax.reload();
        return existingTable;
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
                // Forward ?empId=<base64> from the URL when the page is
                // opened from the Employee Detail "Requests" tab so the
                // inbox scopes to that employee's pending requests only.
                const empIdFromUrl = new URLSearchParams(window.location.search).get('empId');
                if (empIdFromUrl) { d.empId = empIdFromUrl; }
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
@include('resorts._dropdown_script')
@endsection
