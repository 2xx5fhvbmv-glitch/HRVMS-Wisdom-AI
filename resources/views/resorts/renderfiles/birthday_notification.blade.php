{{-- Live (Pusher) notification card. Profile avatar removed per UX
     request — keep the layout consistent with the on-load bell render
     in Common::ResortNotification(). --}}
@if($message1 && $name )
    <div class="notification-box active class_remove_me_{{ $message1->id }}">
        <a href="#" class="d-flex profile-dropdown">
            <div class="flex-grow-1">
                <h5>{!!$message1->Type !!}</h5>
                <p>{!!$message1->message !!}</p>
                <br>
                <span>{{ \Carbon\Carbon::now()->diffForHumans() }}</span>
            </div>
        </a>
        <a href="javascript:void(0);" class="btn-lg-icon btn-light-grey MarkNotification" data-id="{{ $message1->id }}">
            <i class="fas fa-envelope-open" aria-hidden="true"></i>
        </a>
    </div>
@else
    <div class="notification-box">
        <p>No Notification</p>
    </div>
@endif

