    <div class="row g-lg-4 g-3 mb-4">
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Present Days</h6>
                    <strong>{{ $employee->PresentCount }}</strong>
                </div>
                <div>
                    {{-- <a href="#">
                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                    </a> --}}
                </div>
            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Absent Days</h6>
                    <strong>{{ $employee->AbsentCount }}</strong>
                </div>
                <div>
                    {{-- <a href="#">
                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                    </a> --}}
                </div>
            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Total Hours Worked</h6>
                    <strong>{{ $employee->TotalHoursWorked }}</strong>
                </div>
                <div>
                    {{-- <a href="#">
                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                    </a> --}}
                </div>
            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>OT Hours</h6>
                    <strong>{{ $employee->TotalOverTime }}</strong>
                </div>
                <div>
                    {{-- <a href="#">
                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                    </a> --}}
                </div>
            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-6 col">
            <div class="empDetail-block empDetailPro-block">
                <div>
                    <div class="progress progress-custom progress-themeskyblue">
                        <div class="progress-bar" role="progressbar" style="width: {{$employee->onTimePercentage }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $employee->onTimePercentage }}%

                        </div>

                    </div>
                    <span>On Time</span>
                </div>
                <div>
                    <div class="progress progress-custom progress-themeDanger">
                        <div class="progress-bar" role="progressbar" style="width: {{ $employee->LatePercentage}}" aria-valuemin="0" aria-valuemax="100">
                            {{$employee->LatePercentage }}%

                        </div>
                    </div>
                    <span>Late Coming</span>
                </div>
            </div>
        </div>
    </div>
    <div class="empDetails-leave mb-4">
        <div class="card-title">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3>Leave Balance</h3>
                </div>
                <div class="col-auto"><span class="badge badge-themeNew">Total:{{ $TotalSum }}</span></div>
            </div>
        </div>
        <div class="row  gx-xl-5  gx-4">
            @php
                $chunkedLeaveCategories = $leave_categories->chunk(4);
            @endphp

            @foreach($chunkedLeaveCategories as $chunk)
                <div class="col-lg-3 col-sm-6">
                    <table class="table-leave">
                        <tbody>
                            @foreach($chunk as $item)
                                <tr>
                                    <th>{{ $item->leave_type }}</th>
                                    <td>{{ $item->ThisYearOfused_days ?? 0 }}/{{ $item->allocated_days }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

        </div>
    </div>
    <div class="card-header">
        <div class="row g-md-3 g-2 align-items-center">
            <div class="col">
                <div class="card-title  pb-0 mb-0 border-0">
                    <h3>Attendance History</h3>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <a href="javascript:void(0)" class="btn btn-grid"><img src=" {{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                <a href="javascript:void(0)" class="btn btn-list active"><img src=" {{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
            </div>
        </div>
    </div>
    <div class="list-main">
        <div class="table-responsive">
            <table class="table table-collapseNew table-applicants  " id="EmployeeDetails">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>Check in Time</th>
                        <th>Check Out Time</th>
                        <th>Over Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>


                </tbody>
            </table>
        </div>
    </div>
    <div class="grid-main d-none">
        <div class="row g-md-4 g-3 mb-4">

            @if($AttendanceHistroy->isNotEmpty())
                @foreach ($AttendanceHistroy as $item)
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="empDetailsGrid-block">
                            <div class="header">
                                <p><i class="fa-regular fa-calendar"></i>{{ $item->date }}</p>

                                    {!!  $item->Status !!}
                            </div>
                            <div class="time">
                                <div>
                                    <div class="label">Check In</div>
                                    <h6>{{ $item->CheckInTime }}</h6>
                                </div>
                                <div>
                                    <div class="label">Check Out</div>
                                    <h6>{{ $item->CheckOutTime }}</h6>
                                </div>
                                <div>
                                    <div class="label">Total</div>
                                        <h6>{{ $item->DayWiseTotalHours }}hr </h6>
                                        <p>+{{ (isset($item->OverTime) && $item->OverTime  != '-' ) ? $item->OverTime: '00:00' }}<span class="badge badge-themeWarning">OT</span></p>
                                    </div>
                            </div>
                            <div>
                                <div class="label">Notes</div>
                                <p>{{ isset($item->note) ?  $item->note  : "No notes found.." }}</p>
                            </div>
                            <div class="text-center">
                                <a href="javascritp:void(0)" class="btn btn-themeBlue btn-sm LocationHistoryData" data-location ="{{ $item->InTime_Location }}" data-id="{{ $item->id }}"><i class="fa-regular fa-location-dot"></i></a>
                                <a href="javascritp:void(0)" class="btn btn-themeSkyblue btn-sm edit-row-btn" data-note="{{ $item->note }}" data-CheckInTime="{{ $item->CheckInTimeOne }}"  data-CheckOutTime="{{ $item->CheckOutTimeOne }}"  data-OverTime="{{ $item->OverTime }}" data-id="{{ base64_encode($item->Child_id) }}" data-ParentAttd_id="{{ base64_encode($item->ParentAttd_id) }}"  > Edit</a>
                            </div>

                        </div>
                    </div>
                @endforeach
                @else
                        <div class="col-md-12">
                            <p style="text-align: center"> No Records Found.. </p>
                            <hr>
                        </div>
            @endif
        </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            {!! $AttendanceHistroy->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}

        </nav>
    </div>
</div>