@if(isset($type) && $type==10)
    <div class="notification-box active">
        <a href="#" class="d-flex ">
            <div class="flex-shrink-0 img-box">
                <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="..." class="img-fluid" />
            </div>
            <div class="flex-grow-1 ms-3">
                <h5>{{ $name }}</h5>
                <p>{!! $content  !!}</p>
            </div>
        </a>
        <a href="#" class="btn-lg-icon btn-light-grey">
            <img src="{{ URL::asset('resorts_assets/images/trash-white.svg')}}" alt="" class="img-fluid">
        </a>
    </div>

@else
<div class="notification-box">
    <p>No Notification</p>
</div>
@endif
