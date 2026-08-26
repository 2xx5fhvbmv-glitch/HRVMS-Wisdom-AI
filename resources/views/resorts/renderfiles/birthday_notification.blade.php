{{-- Live (Pusher) notification card — these are system-generated events
     (birthdays, committee assignment, exit clearance), so the avatar is
     always the Wisdom AI mark; keep the row layout consistent with the
     on-load bell render in Common::ResortNotification(). --}}
@if($message1 && $name )
    <div class="notification-box active class_remove_me_{{ $message1->id }}">
        <a href="#" class="d-flex profile-dropdown">
            <div class="ntf-av ntf-av-wisdom"><span class="ntf-mk"></span></div>
            <div class="flex-grow-1">
                <h5>{!!$message1->Type !!}</h5>
                <p>{!!$message1->message !!}</p>
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

