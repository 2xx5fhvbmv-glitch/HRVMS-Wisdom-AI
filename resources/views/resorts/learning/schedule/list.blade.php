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
@include('resorts.Learning._learning_buttons_v2_styles')
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
                    // Pre-filter from ?status=… query string when the user lands here
                    // from a dashboard tile (e.g. "Scheduled Learning").
                    var qs = new URLSearchParams(window.location.search);
                    if (qs.get('status')) {
                        d.status = qs.get('status');
                    }

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
            ],
            drawCallback: function () {
                // Attendee avatars carry the name in title + data-bs-toggle.
                // Initialise Bootstrap tooltips after each (server-side) draw
                // so hovering an avatar shows the employee name. The native
                // title attribute is the fallback if Bootstrap isn't present.
                if (window.bootstrap && bootstrap.Tooltip) {
                    document.querySelectorAll('#table-training [data-bs-toggle="tooltip"]').forEach(function (el) {
                        if (!el._ttInit) {
                            new bootstrap.Tooltip(el);
                            el._ttInit = true;
                        }
                    });
                }
            }
        });
    }

    // Convert "YYYY-MM-DD" → "dd/mm/yyyy" for the datepicker input.
    function isoToDmy(iso) {
        if (!iso) return '';
        var p = iso.split('-');
        return (p.length === 3) ? (p[2] + '/' + p[1] + '/' + p[0]) : '';
    }
    // Convert "dd/mm/yyyy" → "DD MMM YYYY" (matches server formatter).
    function dmyToDisplay(dmy) {
        if (!dmy) return '';
        var p = dmy.split('/');
        if (p.length !== 3) return dmy;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var mi = parseInt(p[1], 10) - 1;
        if (mi < 0 || mi > 11) return dmy;
        return p[0].padStart(2,'0') + ' ' + months[mi] + ' ' + p[2];
    }
    // Convert "HH:mm" or "HH:mm:ss" → "hh:MM AM/PM".
    function timeRawToDisplay(raw) {
        if (!raw) return '';
        var parts = raw.split(':');
        var h = parseInt(parts[0], 10);
        var m = (parts[1] || '00').padStart(2,'0');
        if (isNaN(h)) return raw;
        var period = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12; if (h12 === 0) h12 = 12;
        return String(h12).padStart(2,'0') + ':' + m + ' ' + period;
    }

    $(document).on("click", "#table-training .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var scheduleId = $(this).data('schedule-id');

        // Fetch existing values — columns: 1 Name | 2 Type | 3 Trainer | 4 Start Date | 5 End Date | 6 Start Time | 7 End Time | 8 Attendees | 9 Status | 10 Action
        var $startDateCell = $row.find("td:nth-child(4)");
        var $endDateCell   = $row.find("td:nth-child(5)");
        var $startTimeCell = $row.find("td:nth-child(6)");
        var $endTimeCell   = $row.find("td:nth-child(7)");

        // Raw ISO / HH:mm:ss live on the wrapper span the server renders,
        // so the displayed "DD MMM YYYY" / "hh:MM AM/PM" don't need parsing.
        var isoStart = $startDateCell.find('span').attr('data-iso') || '';
        var isoEnd   = $endDateCell.find('span').attr('data-iso')   || '';
        var rawStart = $startTimeCell.find('span').attr('data-raw') || '';
        var rawEnd   = $endTimeCell.find('span').attr('data-raw')   || '';

        var currentStartDate = isoToDmy(isoStart);
        var currentEndDate   = isoToDmy(isoEnd);
        var currentStartTime = rawStart;
        var currentEndTime   = rawEnd;

        // Trim seconds for the time input (HH:mm)
        var startTimeForInput = currentStartTime.slice(0, 5);
        var endTimeForInput   = currentEndTime.slice(0, 5);

        // Create empty input fields with unique IDs
        var startDateId = 'edit-start-date-' + scheduleId;
        var endDateId   = 'edit-end-date-' + scheduleId;
        var startTimeId = 'edit-start-time-' + scheduleId;
        var endTimeId   = 'edit-end-time-' + scheduleId;

        $startDateCell.html(`<input type="text" id="${startDateId}" class="form-control" />`);
        $endDateCell.html(`<input type="text" id="${endDateId}" class="form-control" />`);
        $startTimeCell.html(`<input type="time" id="${startTimeId}" class="form-control" value="${startTimeForInput}" />`);
        $endTimeCell.html(`<input type="time" id="${endTimeId}" class="form-control" value="${endTimeForInput}" />`);

        // Stash the original action cell HTML (Edit + Mark Attendance) so cancel /
        // update can restore both buttons. Replacing with only "Edit" lost the
        // Mark Attendance link.
        var $actionCell = $row.find("td:last-child");
        $row.data('original-action', $actionCell.html());

        $actionCell.html(`
            <button class="btn btn-sm lnd-btn-positive update-row-btn" data-schedule-id="${scheduleId}">Update</button>
            <button class="btn btn-sm lnd-btn-neutral cancel-row-btn" data-schedule-id="${scheduleId}">Cancel</button>
        `);

        // Store original values for cancel
        $row.data('original-start', currentStartDate);
        $row.data('original-end',   currentEndDate);
        $row.data('original-start-time', currentStartTime);
        $row.data('original-end-time',   currentEndTime);

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

        var originalStartDate = $row.data('original-start');
        var originalEndDate   = $row.data('original-end');
        var originalStartTime = $row.data('original-start-time');
        var originalEndTime   = $row.data('original-end-time');

        // Restore using the same wrapper-span shape the server renders.
        var isoStart = originalStartDate ? originalStartDate.split('/').reverse().join('-') : '';
        var isoEnd   = originalEndDate   ? originalEndDate.split('/').reverse().join('-')   : '';
        $row.find("td:nth-child(4)").html('<span data-iso="' + isoStart + '">' + dmyToDisplay(originalStartDate) + '</span>');
        $row.find("td:nth-child(5)").html('<span data-iso="' + isoEnd   + '">' + dmyToDisplay(originalEndDate)   + '</span>');
        $row.find("td:nth-child(6)").html('<span data-raw="' + originalStartTime + '">' + timeRawToDisplay(originalStartTime) + '</span>');
        $row.find("td:nth-child(7)").html('<span data-raw="' + originalEndTime   + '">' + timeRawToDisplay(originalEndTime)   + '</span>');

        // Restore the full action cell (Edit + Mark Attendance) from the stash.
        var originalAction = $row.data('original-action');
        if (originalAction) {
            $row.find("td:last-child").html(originalAction);
        } else {
            $row.find("td:last-child").html(`
                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" title="Edit" data-schedule-id="${scheduleId}">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>
            `);
        }
    });

    $(document).on("click", ".update-row-btn", function () {
        var $row = $(this).closest("tr");
        var scheduleId = $(this).data('schedule-id');

        var startDateInput = $('#edit-start-date-' + scheduleId);
        var endDateInput   = $('#edit-end-date-' + scheduleId);
        var startTimeInput = $('#edit-start-time-' + scheduleId);
        var endTimeInput   = $('#edit-end-time-' + scheduleId);

        var data = {
            _token: '{{ csrf_token() }}',
            id: scheduleId
        };
        if (startDateInput.val()) data.start_date = startDateInput.val();
        if (endDateInput.val())   data.end_date   = endDateInput.val();
        if (startTimeInput.val()) data.start_time = startTimeInput.val();
        if (endTimeInput.val())   data.end_time   = endTimeInput.val();

        var originalStartDate = $row.data('original-start');
        var originalEndDate   = $row.data('original-end');
        var originalStartTime = $row.data('original-start-time');
        var originalEndTime   = $row.data('original-end-time');

        $.ajax({
            url: '{{ route("learning.schedule.update") }}',
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    toastr.success("Schedule updated successfully!", "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    var newStartDate = startDateInput.val() || originalStartDate;
                    var newEndDate   = endDateInput.val()   || originalEndDate;
                    var newStartTime = startTimeInput.val() ? (startTimeInput.val().length === 5 ? startTimeInput.val() + ':00' : startTimeInput.val()) : originalStartTime;
                    var newEndTime   = endTimeInput.val()   ? (endTimeInput.val().length   === 5 ? endTimeInput.val()   + ':00' : endTimeInput.val())   : originalEndTime;

                    // Re-render in canonical formats inside the wrapper span the
                    // edit handler reads from.
                    var isoStart = newStartDate.split('/').reverse().join('-');
                    var isoEnd   = newEndDate.split('/').reverse().join('-');
                    $row.find("td:nth-child(4)").html('<span data-iso="' + isoStart + '">' + dmyToDisplay(newStartDate) + '</span>');
                    $row.find("td:nth-child(5)").html('<span data-iso="' + isoEnd   + '">' + dmyToDisplay(newEndDate)   + '</span>');
                    $row.find("td:nth-child(6)").html('<span data-raw="' + newStartTime + '">' + timeRawToDisplay(newStartTime) + '</span>');
                    $row.find("td:nth-child(7)").html('<span data-raw="' + newEndTime   + '">' + timeRawToDisplay(newEndTime)   + '</span>');

                    // Restore the full action cell (Edit + Mark Attendance) from the stash.
                    var originalAction = $row.data('original-action');
                    if (originalAction) {
                        $row.find("td:last-child").html(originalAction);
                    } else {
                        $row.find("td:last-child").html(`
                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-schedule-id="${scheduleId}">
                                <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="Edit" class="img-fluid">
                            </a>
                        `);
                    }
                } else {
                    toastr.error(response.message || "Failed to update. Try again!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || "Error updating the schedule.";
                toastr.error(msg, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

</script>
@endsection