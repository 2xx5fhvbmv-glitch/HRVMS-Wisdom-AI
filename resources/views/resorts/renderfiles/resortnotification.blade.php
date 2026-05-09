{{-- Admin-broadcast notification cards. Profile avatar removed per UX
     request to match the rest of the bell dropdown. --}}
@if($getNotifications->isNotEmpty())
@foreach ($getNotifications as $notification )
    <div class="notification-box active">
        <a href="#" class="d-flex">
            <div class="flex-grow-1">
                <h5>{{ $notification->name }}</h5>
                <p>{!!$notification->content  !!}</p>
                <span>Start Time: {{ $notification->start_date }} , End Time: {{ $notification->end_date }}  </span>
            </div>
        </a>
        <a href="#" class="btn-lg-icon btn-light-grey">
            <img src="{{ URL::asset('resorts_assets/images/trash-white.svg')}}" alt="" class="img-fluid">
        </a>
    </div>
@endforeach
@else
<div class="notification-box">
    <p>No Notification</p>
</div>
@endif
