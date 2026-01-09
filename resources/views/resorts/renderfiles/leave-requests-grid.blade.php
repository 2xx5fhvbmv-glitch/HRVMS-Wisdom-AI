@if($finalLeaveRequests->count())
    @php
        $leaves = [];
        $colors = [];
        $totalDays = 0;
        $todate;
    @endphp
    @foreach($finalLeaveRequests as $request)
        @if(isset($request->CombineLeave) && !empty($request->CombineLeave))
            @foreach($request->CombineLeave as $leave)
                @php
                    // Append the leave types to the $leaves array
                    $leaves[] = $leave[0]->leave_type; // Add the first leave type
                    $leaves[] = $request->leave_type; // Add the current request leave type
                    $totalDays = $leave[0]->total_days + $request->total_days;
                    $todate = $leave[0]->to_date;
                    $colors[] = $leave[0]->color;
                    $colors[] =  $request->color;
                @endphp

            @endforeach
            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                <div class="leaveReqGrid-block">
                    <div class="dot" style="background:{{ $request->color ?? '#ccc' }}"></div>
                    <div class="img-circle">
                        <img src="{{ $request->profile_picture ? $request->profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                    </div>
                    <h6>{{ $request->first_name ?? 'N/A' }} {{ $request->last_name ?? '' }}</h6>
                    <span class="badge badge-themeLight">#{{ $request->employee_id ?? 'N/A' }}</span>
                    <p>{{ $request->position ?? 'N/A' }}</p>
                    <span class="position">{{ $request->department ?? 'N/A' }}</span>
                    <div class="bg">
                        <i>Applied On: {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</i>
                        <div>
                            <div class="bg-white date-block">
                                {{ \Carbon\Carbon::parse($request->from_date)->format('M') }}
                                <h5>{{ \Carbon\Carbon::parse($request->from_date)->format('d') }}</h5>
                                {{ \Carbon\Carbon::parse($request->from_date)->format('D') }}
                            </div>
                            <div>
                                <img src="{{ URL::asset('resorts_assets/images/arrow.svg') }}" alt="">
                                {{ $totalDays }} days
                            </div>
                            <div class="bg-white date-block">
                                {{ \Carbon\Carbon::parse($todate)->format('M') }}
                                <h5>{{ \Carbon\Carbon::parse($todate)->format('d') }}</h5>
                                {{ \Carbon\Carbon::parse($todate)->format('D') }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex">
                        @foreach($leaves as $key => $name)
                            <span class="badge" style="color:{{ $colors[$key] }}; background:{{$colors[$key]}}1F;">
                                {{ $name ?? 'N/A' }}
                            </span>
                        @endforeach
                        @if($request->attachments)
                            <a href="{{ URL::asset($request->attachments) }}" target="_blank">
                                <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="icon">
                            </a>
                        @endif
                    </div>
                    <p class="text-start">{{ $request->reason ?? 'No reason provided' }}</p>
                    <div class="bg leave">
                        <p><a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="a-link">View Leave Balance</a></p>
                    </div>
                    <div class="btn-block">
                        <a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="btn btn-themeSkyblue btn-sm">View</a>
                        <button class="btn btn-themeBlue btn-sm approve-btn" data-leave-id="{{ $request->id }}">Approve</button>
                        <button class="btn btn-danger btn-sm reject-btn" data-leave-id="{{ $request->id }}">Reject</button>
                    </div>
                </div>
            </div>
        @else
            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                <div class="leaveReqGrid-block">
                    <div class="dot" style="background:{{ $request->color ?? '#ccc' }}"></div>
                    <div class="img-circle">
                        <img src="{{ $request->profile_picture ? $request->profile_picture : URL::asset('resorts_assets/images/user-2.svg') }}" alt="image">
                    </div>
                    <h6>{{ $request->first_name ?? 'N/A' }} {{ $request->last_name ?? '' }}</h6>
                    <span class="badge badge-themeLight">#{{ $request->employee_id ?? 'N/A' }}</span>
                    <p>{{ $request->position ?? 'N/A' }}</p>
                    <span class="position">{{ $request->department ?? 'N/A' }}</span>
                    <div class="bg">
                        <i>Applied On: {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</i>
                        <div>
                            <div class="bg-white date-block">
                                {{ \Carbon\Carbon::parse($request->from_date)->format('M') }}
                                <h5>{{ \Carbon\Carbon::parse($request->from_date)->format('d') }}</h5>
                                {{ \Carbon\Carbon::parse($request->from_date)->format('D') }}
                            </div>
                            <div>
                                <img src="{{ URL::asset('resorts_assets/images/arrow.svg') }}" alt="">
                                {{ $request->total_days }} days
                            </div>
                            <div class="bg-white date-block">
                                {{ \Carbon\Carbon::parse($request->to_date)->format('M') }}
                                <h5>{{ \Carbon\Carbon::parse($request->to_date)->format('d') }}</h5>
                                {{ \Carbon\Carbon::parse($request->to_date)->format('D') }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex">
                        <span class="badge" style="color:{{ $request->color }}; background:{{ $request->color }}1F;">{{ $request->leave_type ?? 'N/A' }}</span>
                        @if($request->attachments)
                            <a href="{{ URL::asset($request->attachments) }}" target="_blank">
                                <img src="{{ URL::asset('resorts_assets/images/pdf1.svg') }}" alt="icon">
                            </a>
                        @endif
                    </div>
                    <p class="text-start">{{ $request->reason ?? 'No reason provided' }}</p>
                    <div class="bg leave">
                        <p><span class="text-lightblue">{{$request -> available_balance}}</span> Leaves Available</p>
                    </div>
                    <div class="btn-block">
                        <a href="{{ route('leave.details', ['leave_id' => base64_encode($request->id)]) }}" class="btn btn-themeSkyblue btn-sm">View</a>
                        <button class="btn btn-themeBlue btn-sm approve-btn" data-leave-id="{{ $request->id }}">Approve</button>
                        <button class="btn btn-danger btn-sm reject-btn" data-leave-id="{{ $request->id }}">Reject</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@else
    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
        <h5>No Request Found.</h5>
    </div>
@endif