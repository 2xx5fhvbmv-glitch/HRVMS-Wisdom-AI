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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
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
     <div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Download File</h5>
                
                    <a href="" class="btn btn-smbtn-primary downloadLink" target="_blank"> Download</a>
                
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                    <div class="modal-body">
                    
                            <div class=" ratio ratio-21x9" id="ViewModeOfFiles">

                            </div>
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    </div>
    
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@php
    $viewIcon = '<i class="fa-regular fa-eye"></i>';
    $editIcon = asset("resorts_assets/images/edit.svg");
    $trashIcon = asset("resorts_assets/images/trash-red.svg");
    $updateIcon = asset("resorts_assets/images/update.svg");
    $viewRouteTemplate = route('incident.meeting.detail', '__MEETING_ID__'); // placeholder
    $cancelIcon = asset("resorts_assets/images/cancel.svg");
@endphp

<script>
    $(document).ready(function () {
        $('#dateFilter').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
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
                <a href="javascript:void(0)" class="update-row-btn btn-lg-icon icon-bg-green me-1" data-meeting-id="${meetingId}">
                    <img src="{{ $updateIcon }}" alt="Update" class="img-fluid" />
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red mx-1 cancel-row-btn" data-meeting-id="${meetingId}">
                    <img src="{{ $cancelIcon }}" alt="Update" class="img-fluid" />
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
                <a href="${viewUrl}" title="View Meeting Detail" class="btn-tableIcon btnIcon-yellow me-1">
                    {!! $viewIcon !!}
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-meeting-id="${meetingId}">
                    <img src="{{ $editIcon }}" alt="Edit" class="img-fluid" />
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-meeting-id="${meetingId}">
                    <img src="{{ $trashIcon }}" alt="Delete" class="img-fluid" />
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
                        <a href="${viewUrl}" title="View Meeting Detail" class="btn-tableIcon btnIcon-yellow me-1">
                            {!! $viewIcon !!}
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-meeting-id="${meetingId}">
                            <img src="{{ $editIcon }}" alt="Edit" class="img-fluid" />
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-meeting-id="${meetingId}">
                            <img src="{{ $trashIcon }}" alt="Delete" class="img-fluid" />
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

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
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