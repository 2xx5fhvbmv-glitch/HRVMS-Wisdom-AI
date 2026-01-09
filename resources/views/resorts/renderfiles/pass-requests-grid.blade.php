@if($passRequests->count())
    @foreach($passRequests as $request)
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="leaveReqGrid-block">
                <div class="img-circle">
                    <img src="{{ $request->profile_picture ? $request->profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                </div>
                <h6>{{ $request->first_name ?? 'N/A' }} {{ $request->last_name ?? '' }}</h6>
                <span class="badge badge-themeLight">#{{ $request->EmpID ?? 'N/A' }}</span>
                <p>{{ $request->position ?? 'N/A' }}</p>
                <span class="position">{{ $request->department ?? 'N/A' }}</span>
                <div class="bg">
                    <i>Applied On: {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</i>
                    <div>
                        <div class="bg-white date-block">
                            {{ \Carbon\Carbon::parse($request->departure_date)->format('M') }}
                            <h5>{{ \Carbon\Carbon::parse($request->departure_date)->format('d') }}</h5>
                            {{ \Carbon\Carbon::parse($request->departure_date)->format('D') }}
                        </div>
                        <div>
                            <img src="{{ URL::asset('resorts_assets/images/arrow.svg') }}" alt="">
                        </div>
                        <div class="bg-white date-block">
                            {{ \Carbon\Carbon::parse($request->arrival_date)->format('M') }}
                            <h5>{{ \Carbon\Carbon::parse($request->arrival_date)->format('d') }}</h5>
                            {{ \Carbon\Carbon::parse($request->arrival_date)->format('D') }}
                        </div>
                    </div>
                </div>
                <p class="text-start">{{ $request->reason ?? 'No reason provided' }}</p>
                <div class="btn-block">
                    <button class="btn btn-themeBlue btn-sm approve-btn" data-leave-id="{{ $request->id }}">Approve</button>
                    <button class="btn btn-danger btn-sm reject-btn" data-leave-id="{{ $request->id }}">Reject</button>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
        <h5>No Request Found.</h5>
    </div>
@endif