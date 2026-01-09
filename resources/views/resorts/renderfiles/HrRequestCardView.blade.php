<div class="card-title d-flex justify-content-between">
    <h3>Requests</h3>
</div>
<div class="bg-green send-m-display d-flex align-items-center justify-content-between mt-1">
    <div class="">
        <h5 class="fs-18 fw-500">send</h5>
        <strong class="ReponseDepartmentCount">{{$ManningPendingRequestCount}}</strong>
    </div>
    <img src="{{ URL::asset('resorts_assets/images/send.svg')}}" class="img-fluid" alt="" />
</div>
<p class="mt-4 mb-2 fw-600 PendingResponsesCount" >Pending responses - {{ $HODpendingResponse }}</p>
<div class="send-reminder-box bg-grey">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            @if(isset($PendingDepartmentResoponse) && !empty($PendingDepartmentResoponse) )


            @foreach ( $PendingDepartmentResoponse as $key=> $response)

            <li class="breadcrumb-item"><a href="#">{{ $response[0] }}</a></li>

            @endforeach

                    <li class="breadcrumb-item">
                        <a href="#Pending-Department" data-bs-toggle="modal" class="Pending-Department text-theme fw-600 text-underline">View All </a>

                    </li>
            @else

            <li class="breadcrumb-item">
                <a href="#"
                        class="text-theme fw-600 text-underline">No Pending Request found </a>
                    </li>

            @endif

        </ol>

    </nav>
    <div class="d-flex justify-content-center mt-3">
        <a href="#" class="btn btn-theme mx-auto">Send Reminder</a>
    </div>
</div>
