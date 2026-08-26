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
                <div class="row justify-content-between g-3 align-items-center">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    @if($canCreate)
                    <div class="col-auto">
                        <a href="javascript:void(0)" class="btn eb-btn-accent" data-bs-toggle="modal" data-bs-target="#selectIncidentForMeetingModal">
                            <i class="fa-solid fa-plus me-1"></i> Create Meeting
                        </a>
                    </div>
                    @endif
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
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                </div>
                <div class="mb-md-3 mb-2">
                    <table id="table-incidentInvesMeet" class="table table-incidentInvesMeet w-100 mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Meeting Subject</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Attachment</th>
                                <th>Participants</th>
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
    {{-- Create-meeting flow needs an incident first; this picker routes to
         incident.meeting.create/{base64-id} once the user chooses one. --}}
    @if($canCreate)
    <div class="modal fade" id="selectIncidentForMeetingModal" tabindex="-1" aria-labelledby="selectIncidentForMeetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectIncidentForMeetingModalLabel">Select Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="select_incident_for_meeting" class="form-label">INCIDENT <span class="red-mark">*</span></label>
                    <select class="form-select select2t-none" id="select_incident_for_meeting">
                        <option value="">Select Incident</option>
                        @foreach($incidents as $inc)
                            <option value="{{ base64_encode($inc->id) }}">{{ $inc->incident_id }} — {{ $inc->incident_name }}</option>
                        @endforeach
                    </select>
                    @if($incidents->isEmpty())
                        <p class="text-muted mt-2 mb-0">No active incidents available. Resolve or create an incident first.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral">Cancel</a>
                    <a href="javascript:void(0)" id="proceedToCreateMeeting" class="btn eb-btn-primary {{ $incidents->isEmpty() ? 'disabled' : '' }}">Proceed</a>
                </div>
            </div>
        </div>
    </div>
    @endif

     @include('partials._file_view_modal')
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@php
    $viewIcon = '<i class="fa-regular fa-eye"></i>';
    $editIcon = '<i class="fa-solid fa-pen-to-square"></i>';
    $trashIcon = '<i class="fa-regular fa-trash-can"></i>';
    $updateIcon = '<i class="fa-solid fa-check"></i>';
    $viewRouteTemplate = route('incident.meeting.detail', '__MEETING_ID__'); // placeholder
    $cancelIcon = '<i class="fa-solid fa-xmark"></i>';
@endphp

<script>
    $(document).ready(function () {
        // Initialize select2 inside the create-meeting picker modal once it opens
        // so the dropdown doesn't render with zero width.
        $('#selectIncidentForMeetingModal').on('shown.bs.modal', function () {
            $('#select_incident_for_meeting').select2({
                dropdownParent: $('#selectIncidentForMeetingModal'),
                placeholder: 'Select Incident',
                width: '100%'
            });
        });

        $('#proceedToCreateMeeting').on('click', function () {
            var encodedId = $('#select_incident_for_meeting').val();
            if (!encodedId) {
                toastr.error('Please choose an incident.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }
            window.location.href = "{{ route('incident.meeting.create', ':id') }}".replace(':id', encodedId);
        });

        flatpickr('#dateFilter', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });

        getIncidentMeetings();

        $('#searchInput, #dateFilter').on('keyup change', function () {
            getIncidentMeetings();
        });

        // === Edit Meeting Row ===
        $(document).on("click", "#table-incidentInvesMeet .edit-row-btn", function (event) {
            event.preventDefault();

            const $row = $(this).closest("tr");
            const meetingId = $(this).data('meeting-id');
            const escapedId = $.escapeSelector(meetingId);

            const $dateCell = $row.find("td:nth-child(3)");
            const $timeCell = $row.find("td:nth-child(4)");
            const originalDate = $dateCell.text().trim();
            const originalTime = $timeCell.text().trim();

            $row.data('original-date', originalDate);
            $row.data('original-time', originalTime);

            $dateCell.html(`<input type="text" id="edit-meeting-date-${meetingId}" class="form-control datepicker" />`);
            $timeCell.html(`<input type="time" id="edit-meeting-time-${meetingId}" class="form-control" value="${originalTime}" />`);

            $row.find("td:last-child").html(`
                <a href="javascript:void(0)" title="Update" class="update-row-btn btn-tableIcon btnIcon-success me-1" data-meeting-id="${meetingId}">
                    {!! $updateIcon !!}
                </a>
                <a href="javascript:void(0)" title="Cancel" class="btn-tableIcon eb-icon-neutral mx-1 cancel-row-btn" data-meeting-id="${meetingId}">
                    {!! $cancelIcon !!}
                </a>
               
            `);

            const $datepicker = $(`#edit-meeting-date-${escapedId}`);
            $datepicker.datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                container: 'body',
                orientation: 'bottom auto'
            });

            if (originalDate) {
                const parts = originalDate.split('/');
                if (parts.length === 3) {
                    const dateObj = new Date(parts[2], parts[1] - 1, parts[0]);
                    $datepicker.datepicker('update', dateObj);
                }
            }
        });

        // === Cancel Edit ===
        $(document).on("click", "#table-incidentInvesMeet .cancel-row-btn", function () {
            const $row = $(this).closest("tr");
            const meetingId = $(this).data('meeting-id');

            const originalDate = $row.data('original-date');
            const originalTime = $row.data('original-time');

            $row.find("td:nth-child(3)").text(originalDate);
            $row.find("td:nth-child(4)").text(originalTime);

            const viewUrl = "{{ $viewRouteTemplate }}".replace('__MEETING_ID__', meetingId);

            $row.find("td:last-child").html(`
                <a href="${viewUrl}" title="View Meeting Detail" class="btn-tableIcon btnIcon-teal me-1">
                    {!! $viewIcon !!}
                </a>
                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-meeting-id="${meetingId}">
                    {!! $editIcon !!}
                </a>
                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-meeting-id="${meetingId}">
                    {!! $trashIcon !!}
                </a>
            `);
        });

        // === Update Row ===
        $(document).on("click", "#table-incidentInvesMeet .update-row-btn", function () {
            const meetingId = $(this).data("meeting-id");
            const escapedId = $.escapeSelector(meetingId);
            const $row = $(this).closest("tr");

            const meetingDate = $(`#edit-meeting-date-${escapedId}`).val();
            const meetingTime = $(`#edit-meeting-time-${escapedId}`).val();

            $.ajax({
                url: "{{ route('incident.meeting.inlineUpdate') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: meetingId,
                    meeting_date: meetingDate,
                    meeting_time: meetingTime
                },
                success: function () {
                    $row.find("td:nth-child(3)").text(meetingDate);
                    $row.find("td:nth-child(4)").text(meetingTime);

                    toastr.success("Meeting date & time updated.", "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    const viewUrl = "{{ $viewRouteTemplate }}".replace('__MEETING_ID__', meetingId);

                    $row.find("td:last-child").html(`
                        <a href="${viewUrl}" title="View Meeting Detail" class="btn-tableIcon btnIcon-teal me-1">
                            {!! $viewIcon !!}
                        </a>
                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-meeting-id="${meetingId}">
                            {!! $editIcon !!}
                        </a>
                        <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-meeting-id="${meetingId}">
                            {!! $trashIcon !!}
                        </a>
                    `);
                },
                error: function () {
                    toastr.error("Failed to update meeting.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        // === Delete Meeting ===
        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();

            const meetingId = $(this).data('meeting-id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                confirmText: 'Yes',
                cancelText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('incident.meeting.delete', ':id') }}".replace(':id', meetingId),
                        dataType: "json"
                    }).done(function (result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            getIncidentMeetings();
                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function (jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        // === DataTable Initialization ===
        function getIncidentMeetings() {
            if ($.fn.DataTable.isDataTable("#table-incidentInvesMeet")) {
                $("#table-incidentInvesMeet").DataTable().destroy();
            }

            $('#table-incidentInvesMeet').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 10,
                processing: true,
                serverSide: true,
                order: [[8, 'desc']],
                ajax: {
                    url: "{{ route('incident.meeting.list') }}",
                    type: "GET",
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                        const selectedDate = $('#dateFilter').val();
                        if (selectedDate) {
                            const parts = selectedDate.split('/');
                            d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                        } else {
                            d.date = '';
                        }
                    }
                },
                columns: [
                    { data: 'incidentID', name: 'incidentID', className: 'text-nowrap' },
                    { data: 'meeting_subject', name: 'meeting_subject', className: 'text-nowrap' },
                    { data: 'date', name: 'date', className: 'text-nowrap' },
                    { data: 'time', name: 'time', className: 'text-nowrap' },
                    { data: 'location', name: 'location', className: 'text-nowrap' },
                    { data: 'attachments', name: 'attachments', className: 'text-nowrap' },
                    { data: 'participants', name: 'participants', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'created_at', visible: false, searchable: false }
                ],
                error: function (xhr) {
                    console.error(xhr.responseText);
                }
            });
        }
    });
</script>

<script>
    $(document).ready(function() {
        $(document).on("click", ".download-link", function(e) {
            e.preventDefault();
            var childId = $(this).data('id');
            var $downloadLink = $(this);

            // First, set a loading message
            $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            // Show the modal with the loading message
            $("#bdVisa-iframeModel-modal-lg").modal('show');
            
            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                data: { child_id: childId, "_token":"{{csrf_token()}}"},
                success: function(response) 
                {
                    let fileUrl = response.NewURLshow;
                    $(".downloadLink").attr("href", fileUrl);
                    
                    let mimeType = response.mimeType.toLowerCase();
                    let iframeTypes = [
                                        'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                                        'application/pdf', 'text/plain',                   // PDF & Text
                                        'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                                    ];
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
                    // Clear the loading message and show the actual content
                    if (imageTypes.includes(mimeType)) 
                    {
                        $("#ViewModeOfFiles").html(`
                            <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                    } 
                    // If file type is supported for iframe display
                    else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html(`
                            <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                        `);
                    } 
                    else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        // window.location.href = fileUrl; // Triggers download automatically
                    }
                },
                error: function(xhr, status, error) 
                {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    toastr.error("An error occurred while downloading the file.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>

@endsection