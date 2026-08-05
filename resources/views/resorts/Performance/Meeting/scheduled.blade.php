
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
                        <a href="{{ route('Performance.Meeting.index') }}" class="btn perf-btn-accent">
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
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn perf-btn-neutral">Close</a>
            </div>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    #meetingDetail-modal .modal-body { font-size: 14px; color: #333; }
    .meet-info .meet-row { display: flex; align-items: baseline; gap: 6px; padding: 4px 0; font-size: 14px; line-height: 1.5; }
    .meet-info .meet-ico { color: #014653; width: 14px; }
    .meet-info .meet-label { font-weight: 600; color: #555; min-width: 100px; }
    .meet-info .meet-value { color: #222; word-break: break-word; }

    .meet-participants-head { font-size: 15px; font-weight: 600; color: #014653; }
    .meet-group { margin-bottom: 16px; }
    .meet-group-head { margin-bottom: 8px; }
    .meet-plist { list-style: none; padding-left: 0; margin: 0; }
    .meet-plist-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
    .meet-plist-item:last-child { border-bottom: none; }
    .meet-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #e9ecef; }
    .meet-pinfo { flex: 1; min-width: 0; }
    .meet-pname { font-size: 14px; font-weight: 600; color: #222; }
    .meet-ppos { font-size: 12px; color: #777; }
    .meet-preason { font-size: 12px; color: #a94442; margin-top: 2px; }

    /* Right sidebar meeting cards */
    .mtg-card { display: flex; gap: 12px; padding: 12px; border: 1px solid #e9ecef; border-radius: 10px; background: #fff; margin-bottom: 12px; }
    .mtg-date { width: 54px; text-align: center; background: #f1f5f9; border-radius: 8px; padding: 8px 4px; flex-shrink: 0; }
    .mtg-date .mtg-day { font-size: 20px; font-weight: 700; color: #014653; line-height: 1; }
    .mtg-date .mtg-month { font-size: 12px; font-weight: 600; color: #555; text-transform: uppercase; margin-top: 2px; }
    .mtg-date .mtg-weekday { font-size: 11px; color: #888; margin-top: 2px; }
    .mtg-body { flex: 1; min-width: 0; }
    .mtg-title { font-size: 14px; font-weight: 600; color: #222; margin: 0 0 4px; line-height: 1.3; word-break: break-word; }
    .mtg-meta { font-size: 12px; color: #666; margin-bottom: 2px; }
    .mtg-meta i { color: #014653; margin-right: 4px; }
    .mtg-badges { display: flex; flex-wrap: wrap; gap: 4px; margin: 8px 0; }
    .mtg-badge { display: inline-block; font-size: 11px; padding: 3px 8px; border-radius: 10px; font-weight: 600; line-height: 1.2; }
    .mtg-badge-green  { background: #e6f4ea; color: #1f8a3a; }
    .mtg-badge-warn   { background: #fff4e5; color: #b87a00; }
    .mtg-badge-danger { background: #fde7e7; color: #b32020; }

    .mtg-participants { margin-top: 6px; }
    .mtg-participant { display: flex; align-items: center; gap: 8px; padding: 5px 0; border-top: 1px solid #f2f2f2; font-size: 13px; }
    .mtg-participant:first-child { border-top: none; }
    .mtg-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; background: #e9ecef; flex-shrink: 0; }
    .mtg-pname { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #333; }
    .mtg-status { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; font-size: 10px; flex-shrink: 0; }
    .mtg-status-accepted { background: #e6f4ea; color: #1f8a3a; }
    .mtg-status-declined { background: #fde7e7; color: #b32020; }
    .mtg-status-pending  { background: #fff4e5; color: #b87a00; }
</style>
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
                var escape = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };

                function row(icon, label, value) {
                    if (!value) return '';
                    return '<div class="meet-row"><i class="fa-regular ' + icon + ' meet-ico"></i>'
                         + '<span class="meet-label">' + label + ':</span> '
                         + '<span class="meet-value">' + escape(value) + '</span></div>';
                }

                var body = '<div class="mb-3 meet-info">';
                body += row('fa-clock',        'Time',         event.start.format('hh:mm A') + ' - ' + (event.end ? event.end.format('hh:mm A') : ''));
                body += row('fa-calendar',     'Date',         event.start.format('DD MMM YYYY'));
                body += row('fa-map',          'Location',     event.location);
                body += row('fa-file-lines',   'Description',  event.description);
                if (event.conference_link) {
                    body += '<div class="meet-row"><i class="fa-solid fa-link meet-ico"></i>'
                          + '<span class="meet-label">Meeting Link:</span> '
                          + '<a href="' + escape(event.conference_link) + '" target="_blank" class="meet-value">' + escape(event.conference_link) + '</a></div>';
                }
                body += '</div>';

                function renderList(list, statusClass, statusLabel) {
                    if (!list || !list.length) return '';
                    var html = '<div class="meet-group">';
                    html += '<div class="meet-group-head"><span class="badge ' + statusClass + '">' + list.length + ' ' + statusLabel + '</span></div>';
                    html += '<ul class="meet-plist">';
                    list.forEach(function (p) {
                        html += '<li class="meet-plist-item">';
                        html += '<img src="' + (p.photo || '') + '" class="meet-avatar" onerror="this.style.visibility=\'hidden\'"/>';
                        html += '<div class="meet-pinfo">';
                        html += '<div class="meet-pname">' + escape(p.name) + '</div>';
                        if (p.position) html += '<div class="meet-ppos">' + escape(p.position) + '</div>';
                        if (p.reason && statusLabel === 'Declined') {
                            html += '<div class="meet-preason"><em>Reason:</em> ' + escape(p.reason) + '</div>';
                        }
                        html += '</div></li>';
                    });
                    html += '</ul></div>';
                    return html;
                }

                if (event.total > 0) {
                    body += '<hr><div class="mb-2 meet-participants-head">Participants (' + event.total + ')</div>';
                    body += renderList(event.accepted_list, 'badge-green',        'Accepted');
                    body += renderList(event.pending_list,  'badge-themeWarning', 'Pending');
                    body += renderList(event.declined_list, 'badge-danger',       'Declined');
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
