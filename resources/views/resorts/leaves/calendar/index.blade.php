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
                        <span>Leave</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card calendar-card">
            <div class="row g-4">
                <div class="col-xxl-9 col-lg-8 ">
                    <div id="calendar" class="calendar-event"></div>
                </div>
                <div class="col-xxl-3 col-lg-4 ">
                    <div class="leaveUser-main" id="calsidebar">
                        @if($calendarData)
                            @foreach($calendarData as $data)
                                <div class="leaveUser-bgBlock" style="color:{{$data['textColor']}};background:{{$data['backgroundColor']}}">
                                    <h6>{{$data['title']}}</h6>
                                </div>
                                <div class="leaveUser-block">
                                    <div class="img-circle">
                                        <img src="{{$data['profile_picture']}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>{{$data['employee_name']}}</h6>
                                        <p>{{$data['position']}}</p>
                                        <span class="badge badge-themeNew1">
                                            <i class="fa-regular fa-calendar"></i> 
                                            @if (\Carbon\Carbon::parse($data['start'])->eq(\Carbon\Carbon::parse($data['end'])))
                                                {{ \Carbon\Carbon::parse($data['start'])->format('d M') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($data['start'])->format('d M') }} to 
                                                {{ \Carbon\Carbon::parse($data['end'])->format('d M') }}
                                            @endif
                                        </span>
                                    </div>
                                    @php
                                        $badgeClass = match ($data['status']) {
                                            'Approved' => 'badge-themeSuccess',
                                            'Rejected' => 'badge-themeDanger',
                                            'Pending'  => 'badge-themeWarning',
                                            default    => 'badge-secondary', // Fallback class
                                        };
                                    @endphp
                                    <div> 
                                        <span class="badge {{ $badgeClass }}">{{ $data['status'] }}</span>
                                        <span class="fw-500">{{$data['total_days']}} Days</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        var calendarLeavesUrl = '{{ route("calendar.leaves.get") }}';

        function renderSidebarFromEvents(events) {
            var $sidebar = $('#calsidebar');
            $sidebar.empty();
            if (!events || events.length === 0) {
                $sidebar.html('<p class="text-muted small mb-0">No leaves in this period.</p>');
                return;
            }
            var badgeClass = function(status) {
                if (status === 'Approved') return 'badge-themeSuccess';
                if (status === 'Rejected') return 'badge-themeDanger';
                if (status === 'Pending') return 'badge-themeWarning';
                return 'badge-secondary';
            };
            events.forEach(function(event) {
                var startFmt = event.start ? moment(event.start).format('D MMM') : '';
                var endFmt = event.end ? moment(event.end).subtract(1, 'day').format('D MMM') : '';
                var dateRange = (event.start && event.end && event.start.substring(0,10) === event.end.substring(0,10)) ? startFmt : (startFmt + ' to ' + endFmt);
                var bgStyle = 'color:' + (event.textColor || '') + ';background:' + (event.backgroundColor || '');
                var blockHtml = '<div class="leaveUser-bgBlock" style="' + bgStyle + '"><h6>' + (event.title || '') + '</h6></div>' +
                    '<div class="leaveUser-block">' +
                    '<div class="img-circle"><img src="' + (event.profile_picture || '') + '" alt=""></div>' +
                    '<div><h6>' + (event.employee_name || '') + '</h6><p>' + (event.position || '') + '</p>' +
                    '<span class="badge badge-themeNew1"><i class="fa-regular fa-calendar"></i> ' + dateRange + '</span></div>' +
                    '<div><span class="badge ' + badgeClass(event.status) + '">' + (event.status || '') + '</span><span class="fw-500">' + (event.total_days || 0) + ' Days</span></div></div>';
                $sidebar.append(blockHtml);
            });
        }

        function fetchAndUpdateSidebar(start, end) {
            if (!start || !end) return;
            var startStr = typeof start === 'string' ? start : (start.format ? start.format('YYYY-MM-DD') : '');
            var endStr = typeof end === 'string' ? end : (end.format ? end.format('YYYY-MM-DD') : '');
            $.ajax({
                url: calendarLeavesUrl,
                data: { start: startStr, end: endStr },
                dataType: 'json',
                success: function(data) {
                    renderSidebarFromEvents(data);
                },
                error: function() {
                    $('#calsidebar').html('<p class="text-muted small mb-0">Unable to load leave list.</p>');
                }
            });
        }

        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
            defaultView: 'month',
            defaultDate: new Date(),
            navLinks: true,
            editable: false,
            eventLimit: true,
            // Use URL string so FullCalendar automatically sends start & end query params
            events: calendarLeavesUrl,
            eventRender: function(event, element) {
                element.attr('title', event.title || '');
            },
            viewRender: function(view) {
                var start = view.start;
                var end = view.end;
                if (start && end) {
                    var startStr = start.format ? start.format('YYYY-MM-DD') : (start.toString ? start.toString().substring(0, 10) : '');
                    var endStr = end.format ? end.format('YYYY-MM-DD') : (end.toString ? end.toString().substring(0, 10) : '');
                    if (startStr && endStr) fetchAndUpdateSidebar(startStr, endStr);
                }
            }
        });

        function equalizeHeights() {
            var block1 = document.getElementById('calendar');
            var block2 = document.getElementById('calsidebar');
            if (block1 && block2) block2.style.height = block1.offsetHeight + 'px';
        }
        window.onload = equalizeHeights;
        window.onresize = equalizeHeights;
    });
</script>
@endsection