{{-- Admin-broadcast notification cards — always system-origin, so the
     avatar is the Wisdom AI mark, matching the rest of the bell dropdown. --}}
@if($getNotifications->isNotEmpty())
@foreach ($getNotifications as $notification )
    <div class="notification-box active">
        <a href="#" class="d-flex">
            <div class="ntf-av ntf-av-wisdom"><span class="ntf-mk"></span></div>
            <div class="flex-grow-1">
                <h5>{{ $notification->name }}</h5>
                <p>{!!$notification->content  !!}</p>
                <span>Start Time: {{ $notification->start_date }}, End Time: {{ $notification->end_date }}  </span>
            </div>
        </a>
        <a href="#" class="btn-lg-icon btn-light-grey">
            <img src="{{ URL::asset('resorts_assets/images/trash-white.svg')}}" alt="" class="img-fluid">
        </a>
    </div>
@endforeach
@else
@include('partials._notifications_empty')
@endif
