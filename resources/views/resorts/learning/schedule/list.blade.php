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
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <select id="typeFilter" class="form-select select2t-none">
                                <option value=""> By Learning Type</option>
                                <option value="face-to-face">Face-to-Face</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="online">Online</option>        
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive">
                    <table id="table-training" class="table  table-training w-100">
                        <thead>
                            <tr>
                                <th>Learning Name</th>
                                <th>Learning Type </th>
                                <th>Trainer</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Attendees</th>
                                <th>Status</th>
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
@endsection

@section('import-css')
<style></style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });
        loadSchedulesTrainings();

        $('#searchInput, #typeFilter,.datepicker').on('keyup change', function () {
            loadSchedulesTrainings();
        });
    });

    function loadSchedulesTrainings() {
        if ($.fn.DataTable.isDataTable('#table-training')) {
            $('#table-training').DataTable().destroy();
        }

        $('#table-training').DataTable({
            searching: false,
            lengthChange: false,
            filter: true,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 6,
            processing: true,
            serverSide: true,
            order: [[9, 'desc']],
            ajax: {
                url: '{{ route("learning.schedule.list") }}',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.type = $('#typeFilter').val();
                    
                    // Convert date format from d/m/Y to Y-m-d
                    let selectedDate = $('.datepicker').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`; // YYYY-MM-DD format
                    } else {
                        d.date = '';
                    }
                },
                type: 'GET',
            },
            columns: [
                { 
                    data: 'learning_name', name: 'Learning Name', 
                    render: function(data, type, row) {
                        let encodedId = btoa(row.id); // Base64 encode the schedule_id
                        return `<a title="Mark Attendance" href="{{ route("learning.schedule.attendance") }}?schedule_id=${encodedId}" class="text-primary">${data}</a>`;
                    }
                },
                { data: 'learning_type', name: 'Learning Type' },
                { data: 'trainer', name: 'Trainer' },
                { data: 'start_date', name: 'Start Date' },
                { data: 'end_date', name: 'End Date' },
                { data: 'start_time', name: 'Start Time' },
                { data: 'end_time', name: 'End Time' },
                { data: 'attendees', name: 'Attendees' },
                { data: 'status', name: 'Status' },
                { data: 'action', name: 'Action' },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }

    $(document).on("click", "#table-training .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var scheduleId = $(this).data('schedule-id');

        // Fetch existing values
        var $startDateCell = $row.find("td:nth-child(4)");
        var $endDateCell = $row.find("td:nth-child(5)");

        var currentStartDate = $startDateCell.text().trim();
        var currentEndDate = $endDateCell.text().trim();

        // Create empty input fields with unique IDs
        var startDateId = 'edit-start-date-' + scheduleId;
        var endDateId = 'edit-end-date-' + scheduleId;
        
        $startDateCell.html(`<input type="text" id="${startDateId}" class="form-control"  />`);
        $endDateCell.html(`<input type="text" id="${endDateId}" class="form-control"  />`);

        // Replace action buttons
        var $actionCell = $row.find("td:last-child");
        $actionCell.html(`
            <button class="btn btn-sm btn-success update-row-btn" data-schedule-id="${scheduleId}">Update</button>
            <button class="btn btn-sm btn-secondary cancel-row-btn" data-schedule-id="${scheduleId}">Cancel</button>
        `);

        // Store original values as data attributes for cancel
        $row.data('original-start', currentStartDate);
        $row.data('original-end', currentEndDate);

        // Force jQuery to create new datepicker instances
        $('#' + startDateId).datepicker('destroy');
        $('#' + endDateId).datepicker('destroy');
        
        // Initialize with empty configuration first
        $('#' + startDateId).datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            clearBtn: true,
            container: 'body',
            orientation: 'bottom auto'
        });
        
        $('#' + endDateId).datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            clearBtn: true,
            container: 'body',
            orientation: 'bottom auto'
        });
        
        // After initialization, try to set dates manually
        try {
            if (currentStartDate) {
                var parts = currentStartDate.split('/');
                if (parts.length === 3) {
                    var dateObj = new Date(parts[2], parts[1]-1, parts[0]);
                    $('#' + startDateId).datepicker('update', dateObj);
                }
            }
            
            if (currentEndDate) {
                var parts = currentEndDate.split('/');
                if (parts.length === 3) {
                    var dateObj = new Date(parts[2], parts[1]-1, parts[0]);
                    $('#' + endDateId).datepicker('update', dateObj);
                }
            }
        } catch (e) {
            console.error("Error setting dates:", e);
        }
    });

    // Fix the cancel button handler to use stored original values
    $(document).on("click", ".cancel-row-btn", function () {
        var $row = $(this).closest("tr");
        var scheduleId = $row.find(".update-row-btn").data('schedule-id');
        
        // Get original values from jQuery data storage
        var originalStartDate = $row.data('original-start');
        var originalEndDate = $row.data('original-end');
        
        // Restore original text
        $row.find("td:nth-child(4)").text(originalStartDate);
        $row.find("td:nth-child(5)").text(originalEndDate);
        
        // Restore original action buttons
        $row.find("td:last-child").html(`
            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-schedule-id="${scheduleId}">
                <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="Edit" class="img-fluid">
            </a>
        `);
    });

    $(document).on("click", ".update-row-btn", function () {
        var $row = $(this).closest("tr");
        var scheduleId = $(this).data('schedule-id');
        
        // Get the values from the input fields
        var startDateInput = $('#edit-start-date-' + scheduleId);
        var endDateInput = $('#edit-end-date-' + scheduleId);
        
        // Prepare data object with only the required fields
        var data = {
            _token: '{{ csrf_token() }}',
            id: scheduleId
        };
        
        // Only include start_date if it has a value
        if (startDateInput.val()) {
            data.start_date = startDateInput.val();
        }
        
        // Only include end_date if it has a value
        if (endDateInput.val()) {
            data.end_date = endDateInput.val();
        }
        
        // Get original values for any field not being updated
        var originalStartDate = $row.data('original-start');
        var originalEndDate = $row.data('original-end');
        
        $.ajax({
            url: '{{ route("learning.schedule.update") }}',
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    
                    toastr.success("Date updated successfully!", "Success", {
                        positionClass: 'toast-bottom-right'
                    });  
                    // Update the text cells with new values or keep original if not changed
                    var newStartDate = startDateInput.val() || originalStartDate;
                    var newEndDate = endDateInput.val() || originalEndDate;
                    
                    $row.find("td:nth-child(4)").text(newStartDate);
                    $row.find("td:nth-child(5)").text(newEndDate);
                    
                    // Restore original action buttons
                    $row.find("td:last-child").html(`
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-schedule-id="${scheduleId}">
                            <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="Edit" class="img-fluid">
                        </a>
                    `);
                } else {
                    toastr.error("Failed to update. Try again!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });  
                }
            },
            error: function () {
                toastr.error("Error updating the date.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });  
            }
        });
    });

</script>
@endsection