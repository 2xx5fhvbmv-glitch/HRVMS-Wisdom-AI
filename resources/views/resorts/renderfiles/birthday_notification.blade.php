@if($message1 && $name )
    <div class="notification-box active class_remove_me_{{ $message1->id }}">
        <a href="#" class="d-flex profile-dropdown  ">
            <div class="flex-shrink-0 img-box">
                <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="..." class="img-fluid" />
            </div>
            <div class="flex-grow-1 ms-3">
                <h5>{!!$message1->Type !!}</h5>
                <p>{!!$message1->message !!}</p>
                <br>
                <span>Current Date andc Time: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span>
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

