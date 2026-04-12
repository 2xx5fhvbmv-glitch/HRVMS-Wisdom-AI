
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('Performance.Meeting.index') }}" class="btn btn-theme">
                            <i class="fa-solid fa-plus me-1"></i> Create Meeting
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card calendar-card calendarAccom-card">
            <div class="row g-4">
                <div class="col-xxl-9 col-lg-8">
                    <div id="calendar" class="calendar-event"></div>
                </div>
                <div class="col-xxl-3 col-lg-4">
                    <div class="leaveUser-main" id="calsidebar" style="overflow-y:auto;">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-auto">
                <span class="badge" style="background:#28a745;color:#fff;">&#9679;</span> All Accepted
            </div>
            <div class="col-auto">
                <span class="badge" style="background:#EFB408;color:#fff;">&#9679;</span> Pending Responses
            </div>
            <div class="col-auto">
                <span class="badge" style="background:#dc3545;color:#fff;">&#9679;</span> Has Declines
            </div>
            <div class="col-auto">
                <span class="badge" style="background:#014653;color:#fff;">&#9679;</span> No Participants
            </div>
        </div>
    </div>
</div>

<!-- Meeting Detail Modal -->
<div class="modal fade" id="meetingDetail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#014653;color:#fff;">
                <h5 class="modal-title" id="meetingDetailTitle">Meeting Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="meetingDetailBody">
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray">Close</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        loadSidebar();
        initCalendar();

        function equalizeHeights() {
            const block1 = document.getElementById('calendar');
            const block2 = document.getElementById('calsidebar');
            if (block1 && block2) {
                block2.style.height = block1.offsetHeight + 'px';
            }
        }
        window.onload = equalizeHeights;
        window.onresize = equalizeHeights;
    });

    function initCalendar() {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,basicWeek,basicDay',
            },
            defaultDate: new Date(),
            navLinks: true,
            editable: false,
            eventLimit: true,
            events: '{{ route("Performance.Meeting.calendarData") }}',
            eventRender: function(event, element) {
                var tooltip = event.title;
                if (event.total > 0) {
                    tooltip += ' | ' + event.accepted + ' accepted, ' + event.pending + ' pending, ' + event.declined + ' declined';
                }
                element.attr('data-bs-toggle', 'tooltip');
                element.attr('title', tooltip);
            },
            eventAfterAllRender: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            eventClick: function(event) {
                var body = '<div class="mb-3">';
                body += '<p class="mb-1"><strong><i class="fa-regular fa-clock me-1"></i> Time:</strong> ' + event.start.format('hh:mm A') + ' - ' + (event.end ? event.end.format('hh:mm A') : '') + '</p>';
                body += '<p class="mb-1"><strong><i class="fa-regular fa-calendar me-1"></i> Date:</strong> ' + event.start.format('DD MMM YYYY') + '</p>';
                if (event.location) body += '<p class="mb-1"><strong><i class="fa-solid fa-location-dot me-1"></i> Location:</strong> ' + event.location + '</p>';
                if (event.conference_link) body += '<p class="mb-1"><strong><i class="fa-solid fa-link me-1"></i> Meeting Link:</strong> ' + event.conference_link + '</p>';
                if (event.description) body += '<p class="mb-1"><strong><i class="fa-regular fa-file-lines me-1"></i> Description:</strong> ' + event.description + '</p>';
                body += '</div>';

                if (event.total > 0) {
                    body += '<hr><div class="mb-2"><strong>Participants (' + event.total + ')</strong></div>';
                    body += '<div class="d-flex gap-3 mb-2">';
                    body += '<span class="badge badge-green">' + event.accepted + ' Accepted</span>';
                    body += '<span class="badge badge-themeWarning">' + event.pending + ' Pending</span>';
                    body += '<span class="badge badge-danger">' + event.declined + ' Declined</span>';
                    body += '</div>';
                }

                $('#meetingDetailTitle').text(event.title);
                $('#meetingDetailBody').html(body);
                $('#meetingDetail-modal').modal('show');
            }
        });
    }

    function loadSidebar() {
        $.ajax({
            url: '{{ route("Performance.Meeting.sidebar") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#calsidebar').html(response.data);
                }
            },
            error: function() {
                $('#calsidebar').html('<p class="text-muted text-center p-3">Failed to load meetings</p>');
            }
        });
    }
</script>
@endsection
