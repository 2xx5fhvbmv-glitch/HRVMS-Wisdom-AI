<?php $__env->startSection('page_tab_title' ,$page_title); ?>

    <?php if($message = Session::get('success')): ?>
        <div class="alert alert-success">
            <p><?php echo e($message); ?></p>
        </div>
    <?php endif; ?>

    <?php $__env->startSection('content'); ?>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1><?php echo e($page_title); ?></h1>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        
                        

                        
                    </div>
                </div>
                <div class="list-main d-block">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-ta-employeeslist">
                            <thead>
                                <tr>
                                    <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                    <th>Details</th>
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
    <?php $__env->stopSection(); ?>
    <?php $__env->startSection('import-css'); ?>

    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('import-scripts'); ?>

    <script type="text/javascript">
    $(document).ready(function () {







        datatablelist()
    });
    $(document).on('keyup', '.Search', function () {

        datatablelist()
    });






    $(document).on('change', '#position', function () {
        datatablelist()
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
            url: "<?php echo e(route('resort.timeandattendance.GetOvertimeEntries')); ?>",
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
            url: "<?php echo e(route('resort.timeandattendance.StoreOverTime')); ?>",
            type: "POST",
            data: {
                "_token": "<?php echo e(csrf_token()); ?>",
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
                        datatablelist();
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
        const row = button.closest('tr');

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
                                // Reload the datatable
                                datatablelist();
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

    function datatablelist()
    {
        if ($.fn.DataTable.isDataTable('.table-ta-employeeslist'))
        {
            $('.table-ta-employeeslist').DataTable().destroy();
        }

        var divisionTable = $('.table-ta-employeeslist').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order:[[0, 'asc']],
                ajax: {
                    url: "<?php echo e(route('resort.timeandattendance.todolist')); ?>",
                    type: 'GET',
                    data: function(d) {
                        d.position = $("#position").val();
                        d.searchTerm = $('.Search').val();
                    }
                },
                columns: [
                    { data: 'Applicant', name: 'Applicant',},
                    { data: 'Details', name: 'Details' },
                    { data: 'Action', name: 'Action', orderable: false, searchable: false },
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
                        datatablelist();
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

<?php echo $__env->make('resorts.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/timeandattendance/todolist.blade.php ENDPATH**/ ?>