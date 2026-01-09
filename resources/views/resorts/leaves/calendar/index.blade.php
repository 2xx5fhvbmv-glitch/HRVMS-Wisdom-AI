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
    // new DataTable('#example');
    $(document).ready(function () {
        // full-calendar   
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
            defaultDate: new Date(), // Set to the current date
            navLinks: true,
            editable: false, // Make events non-editable
            eventLimit: true, // Allow "more" link when too many events
            events: '{{route("calendar.leaves.get")}}', // Laravel route to fetch events
            eventRender: function(event, element) {
                element.attr('title', event.title); // Add tooltip
            }
        });


        //    equal heigth js 
        function equalizeHeights() {
            // Get the elements
            const block1 = document.getElementById('calendar');
            const block2 = document.getElementById('calsidebar');

            // Get the height of block1
            const block1Height = block1.offsetHeight;

            // Set the height of block2 to match block1's height
            block2.style.height = block1Height + 'px';
        }

        window.onload = equalizeHeights; // Initial height adjustment

        // Adjust heights on window resize
        window.onresize = equalizeHeights;
    });
</script>
@endsection