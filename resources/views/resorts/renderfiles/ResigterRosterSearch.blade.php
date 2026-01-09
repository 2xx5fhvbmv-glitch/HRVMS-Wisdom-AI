<div class="weekly-main @if($sendclass =="Weekly") d-block @else d-none @endif" >
    <div class="attendance-bg">
        <div class="row justify-content-center gx-md-4 g-2">
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeSuccess"></span>On Time
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themePurple"></span>Late
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeDanger"></span>Missing
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeWarning"></span>DayOff
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive table-arrowAtten mb-4">
        {{-- <a href="javascript:void(0)" class="arrow-left"><i class="fa-solid fa-angle-left"></i></a>
        <a href="javascript:void(0)" class="arrow-right"><i class="fa-solid fa-angle-right"></i></a> --}}
        <table class="table table-collapseNew table-attendance   mb-1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    @foreach ($headers as $d)
                        @php
                            $currentDate = isset($d['full_date']) ? $d['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($d['date']));
                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                        @endphp
                        <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $d['date'] }} <span>{{ $d['day'] }}</span></th>
                    @endforeach
                    <th>Worked <br>Hours</th>
                    <th>Balance <br>Hours</th>
                    <th>OT <br>Hours</th>
                    <th>Leave</th>

                </tr>
            </thead>
            <tbody>

                @if($attandanceregister->isNotEmpty())
                    @foreach ($attandanceregister as $a)
                        <tr>
                            <td>{{$a->EmployeeId }}</td>
                            <td>
                                <div class="tableUser-block">
                                    <div class="img-circle"><img src="{{$a->profileImg }}" alt="user">
                                    </div>
                                    <span>{{ $a->EmployeeName }}</span>
                                </div>
                            </td>
                            @php

                                $RosterInternalData = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth,"weekly");
                                $totalHours = 0;
                                $TotalweeklyOt=0;
                                $TotalweeklyLeave=0;
                                $dataCount = $RosterInternalData->count();
                                $minColumns = 7; // Minimum number of columns to account for all days of the week
                            @endphp
                                @foreach ($headers as $header)
                                @php
                                    // Get shift data for the current header date
                                    $currentDate = isset($header['newdate']) ? date('Y-m-d', strtotime(str_replace('-', '/', $header['newdate']))) : date('Y-m-d', strtotime($header['date']));
                                    $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                    $shiftData = $RosterInternalData->firstWhere('date', $currentDate);
                                    $toatalHoursForDay = 0;


                                    if (isset($shiftData) && $shiftData->Status == "Present") {
                                        list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours ?? '0:0');


                                        $toatalHoursForDay = (int)$hours + ((int)$minutes / 60);


                                        $totalHours += $toatalHoursForDay;

                                        list($Othours, $Otminutes) = explode(':', $shiftData->OverTime ?? '0:0');
                                        $TotalweeklyOt +=  (int)$Othours + ((int)$Otminutes / 60);

                                        }


                                @endphp

                                <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }} @if(isset($shiftData) && $shiftData->Status == "DayOff" ) Register-col-leave @endif">
                                        @if (isset($shiftData) && $shiftData->Status == "Present" )
                                            @if (isset($shiftData->CheckingTime))

                                                <div
                                                    @if(isset($shiftData->InternalStatus) &&  $shiftData->InternalStatus =="OnTime" ||$shiftData->InternalStatus =="Early")
                                                        class="badge badge-themeSuccess"
                                                        @php $icon= 'rigth-success.svg'; @endphp
                                                    @else
                                                    class="badge badge-themePurple"

                                                    @php $icon= 'rigth-purple.svg'; @endphp
                                                    @endif
                                                    >
                                                    <img src="{{ URL::asset('resorts_assets/images/'.$icon) }}" alt="icon" class="left">
                                                    {{ $shiftData->CheckingTime }}
                                                </div>
                                            @endif
                                            <br>

                                            @if ($shiftData->CheckingOutTime)
                                                <div class="badge badge-themePurple">
                                                    <img src="{{ URL::asset('resorts_assets/images/rigth-purple.svg') }}" alt="">
                                                    {{ $shiftData->CheckingOutTime }}
                                                </div>
                                            @else

                                            @if ($shiftData->msg =="PleaseCheckout")
                                            <div class="badge badge-themeDanger tt-hover tt-large"><img src="{{ URL::asset('resorts_assets/images/right-danger.svg')}}" alt="">--:--
                                                    <span class="tt-main"><span class="tt-inner">
                                                        <p><b>Missing CheckOut</b>The Employee has not Checked Out at the
                                                            expected time. would you like to assign them overtime?
                                                            {{ $shiftData->differenceInHours }}
                                                        </p>
                                                        {{-- <a href="#" class="btn btn-themeSkyblue">Accept</a> --}}
                                                            <a href="javascript:void(0)" class="btn btn-themeSkyblue CheckOutModel"
                                                                    data-attendance_id="{{ $shiftData->Attd_id }}"
                                                                    data-shiftname="{{ $shiftData->ShiftName }}"
                                                                    data-todoshiftstime="{{ $shiftData->StartTime}}"
                                                                    data-todoshiftetime="{{ $shiftData->EndTime }}"
                                                                    data-todoshiftstimeShow="{{ $shiftData->StartTimeShow}}"
                                                                    data-todoshiftetimeShow="{{ $shiftData->EndTimeShow }}"
                                                                    data-todoassignedot="{{ ($shiftData->OverTime ?? '-') }}"
                                                                    data-totalExtraHours="{{ $shiftData->differenceInHours }}"
                                                                    data-OTStatus="{{ $shiftData->OTStatus }}"
                                                                    data-CheckingOutTime="{{ $shiftData->CheckingOutTime }}"
                                                            >Enter Checkout Time
                                                        </a>
                                                    </span></span>
                                            </div>
                                            @endif


                                        @endif

                                        <div class="btn-block">
                                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-blue LocationHistoryData" data-location="{{ $shiftData->InTime_Location }}"><i class="fa-regular fa-location-dot"></i></a>

                                            @if(isset($shiftData->ApprovedName) && $shiftData->ApprovedName != "")
                                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-skyblue tt-hover"><i class="fa-regular fa-comment "></i>
                                                <span class="tt-main"><span class="tt-inner">OT Approved by
                                                        {{$shiftData->ApprovedName }}</span></span></a>
                                            @endif
                                        </div>
                                        @if ($shiftData->OverTime && $shiftData->OverTime != "-" && $shiftData->OTStatus == null && $shiftData->OTstatus !="Rejacted")
                                            <p>
                                                +{{ $shiftData->OverTime }}<span class="badge badge-themeWarning">OT</span>
                                            </p>
                                        @endif


                                    @elseif (isset($shiftData) && $shiftData->Status == "Absent")
                                        <b class="text-skyblue" >Absent</b>
                                        @php
                                            $TotalweeklyLeave = $TotalweeklyLeave+1;
                                        @endphp
                                    @elseif(isset($shiftData) && $shiftData->Status == "DayOff" )

                                        <i class="fa-solid fa-xmark"></i>
                                    @else

                                    -
                                    @endif

                                </td>
                            @endforeach

                            @php

                            $toatalweeklyHoursForDay = $totalHours - floor($TotalweeklyOt);
                        @endphp
                                <td>{{ $toatalweeklyHoursForDay }}</td>
                                <td>{{ 48-$toatalweeklyHoursForDay  }}</td>
                                <td> {{ $TotalweeklyOt }} </td>
                                <td>
                                    @if($TotalweeklyLeave != 0)
                                    <span class="ttb-hover">
                                        {{ $TotalweeklyLeave }}
                                        @if($LeaveCategory->isNotEmpty())
                                            @foreach ($LeaveCategory as $k => $leave)
                                                @php
                                                    $matchedLeaveType = null;
                                                    $matchedFromDate = null;
                                                    $matchedToDate = null;

                                                    // Check if there's a matching leave in RosterInternalDataMonth
                                                    $weeklyleaveMatched = $RosterInternalData->first(function ($shiftData) use ($leave, $k) {
                                                        return isset($shiftData->LeaveData[$k]) && $shiftData->LeaveData[$k]['leave_cat_id'] == $leave->id;
                                                    });

                                                    if ($weeklyleaveMatched) {
                                                        $weeklyleaveMatched = $weeklyleaveMatched->LeaveData[$k];
                                                        $matchedLeaveType = $weeklyleaveMatched['leave_type'] ?? null;
                                                        $matchedFromDate = $weeklyleaveMatched['from_date'] ?? null;
                                                        $matchedToDate = $weeklyleaveMatched['to_date'] ?? null;
                                                    }
                                                @endphp
                                         @if($weeklyleaveMatched)
                                                    <span class="ttb-main">
                                                        <span class="ttb-inner">
                                                            <span>
                                                                <span>{{ \Carbon\Carbon::parse($matchedFromDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($matchedToDate)->format('d/m/Y') }}</span>
                                                                <p>{{ $matchedLeaveType }}</p>
                                                            </span>
                                                        </span>
                                                    </span>
                                                @endif
                                            @endforeach
                                        @endif
                                    </span>
                                @else
                                    0
                                    <span class="ttb-main">
                                        <span class="ttb-inner">
                                            <span>
                                                <p>No Leave Found..</p>
                                            </span>
                                        </span>
                                    </span>
                                @endif

                                </td>


                        </tr>
                    @endforeach
                @endif

            </tbody>
        </table>
    </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            {!! $attandanceregister->appends(['view' => request('view', 'weekly')])->links('pagination::bootstrap-4') !!}

        </nav>
    </div>
</div>
<div class="monthly-main  @if($sendclass !="Weekly") d-block @else d-none @endif"">
    <div class="attendance-bg">
        <div class="row justify-content-center gx-md-4 g-2">
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeSuccess"></span>On Time
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themePurple"></span>Late
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeDanger"></span>Missingd
                </div>
            </div>
            <div class="col-auto">
                <div class="doughnut-label">
                    <span class="bg-themeWarning"></span>DayOff
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive mb-4">
        <table class="table table-collapseNew  table-attendance  mb-1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    @if(!empty($monthwiseheaders))
                        @foreach ($monthwiseheaders as $h)
                            @php
                                $currentDate = isset($h['newdate']) ? date('Y-m-d', strtotime(str_replace('-', '/', $h['newdate']))) : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                            @endphp
                            <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                        @endforeach
                    @endif
                    <th>Worked <br>Hours</th>
                    <th>Balance <br>Hours</th>
                    <th>OT <br>Hours</th>
                    <th>Leave</th>
                </tr>
            </thead>
            <tbody>
                @if ($attandanceregister->isNotEmpty())
                @foreach ($attandanceregister as $a)
                    <tr>
                        <td>{{ $a->EmployeeId }}</td>
                        <td>
                            <div class="tableUser-block">
                                <div class="img-circle">
                                    <img src="{{ $a->profileImg }}" alt="user">
                                </div>
                                <span>{{ $a->EmployeeName }}</span>
                            </div>
                        </td>
                        @php
                            $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");
                            $totalHours = 0;
                            $TotalMonthlyOt  = 0;
                            $TotalMonthlyLeave = 0;
                            $TotalMonltyDayOff=0;

                        @endphp

                        @foreach ($monthwiseheaders as $h)
                            @php
                                $date = date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT);
                                $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);

                                $toatalHoursForDay = 0;

                                if ($shiftData && $shiftData->Status == "Present") {
                                    list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours ?? '0:0');
                                    $toatalHoursForDay = (int)$hours + ((int)$minutes / 60);
                                    $totalHours += $toatalHoursForDay;

                                    list($Othours, $Otminutes) = explode(':', $shiftData->OverTime ?? '0:0');
                                    $TotalMonthlyOt += (int)$Othours + ((int)$Otminutes / 60);
                                }
                            @endphp

                            <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }} @if(isset($shiftData) && $shiftData->Status == "DayOff" ) Register-col-leave @endif">

                                @if ($shiftData && $shiftData->Status == "Present" )
                                    @if ($shiftData->CheckingTime)

                                        <div
                                            @if(isset($shiftData->InternalStatus) &&  $shiftData->InternalStatus =="OnTime" ||$shiftData->InternalStatus =="Early")
                                                class="badge badge-themeSuccess"
                                                @php $icon= 'rigth-success.svg'; @endphp
                                            @else
                                            class="badge badge-themePurple"

                                            @php $icon= 'rigth-purple.svg'; @endphp
                                            @endif
                                            >
                                            <img src="{{ URL::asset('resorts_assets/images/'.$icon) }}" alt="icon" class="left">
                                            {{ $shiftData->CheckingTime }}
                                        </div>
                                    @endif
                                    <br>

                                    @if ($shiftData->CheckingOutTime)
                                        <div class="badge badge-themePurple">
                                            <img src="{{ URL::asset('resorts_assets/images/rigth-purple.svg') }}" alt="">
                                            {{ $shiftData->CheckingOutTime }}
                                        </div>
                                    @else

                                    @if ($shiftData->msg =="PleaseCheckout")
                                    <div class="badge badge-themeDanger tt-hover tt-large"><img src="{{ URL::asset('resorts_assets/images/right-danger.svg')}}" alt="">--:--
                                            <span class="tt-main"><span class="tt-inner">
                                                <p><b>Missing CheckOut</b>The Employee has not Checked Out at the
                                                    expected time. would you like to assign them overtime?
                                                    {{ $shiftData->differenceInHours }}
                                                </p>
                                                {{-- <a href="#" class="btn btn-themeSkyblue">Accept</a> --}}
                                                    <a href="javascript:void(0)" class="btn btn-themeSkyblue CheckOutModel"
                                                            data-attendance_id="{{ $shiftData->Attd_id }}"
                                                            data-shiftname="{{ $shiftData->ShiftName }}"
                                                            data-todoshiftstime="{{ $shiftData->StartTime}}"
                                                            data-todoshiftetime="{{ $shiftData->EndTime }}"
                                                            data-todoshiftstimeShow="{{ $shiftData->StartTimeShow}}"
                                                            data-todoshiftetimeShow="{{ $shiftData->EndTimeShow }}"
                                                            data-todoassignedot="{{ ($shiftData->OverTime ?? '-') }}"
                                                            data-totalExtraHours="{{ $shiftData->differenceInHours }}"
                                                            data-OTStatus="{{ $shiftData->OTStatus }}"
                                                            data-CheckingOutTime="{{ $shiftData->CheckingOutTime }}"
                                                    >Enter Checkout Time
                                                </a>
                                            </span></span>
                                    </div>
                                    @endif


                                @endif

                                <div class="btn-block">
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-blue LocationHistoryData" data-location="{{ $shiftData->InTime_Location }}"><i class="fa-regular fa-location-dot"></i></a>

                                    @if(isset($shiftData->ApprovedName) && $shiftData->ApprovedName != "")
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-skyblue tt-hover"><i class="fa-regular fa-comment "></i>
                                        <span class="tt-main"><span class="tt-inner">OT Approved by
                                                {{$shiftData->ApprovedName }}</span></span></a>
                                    @endif
                                </div>
                                @if ($shiftData->OverTime && $shiftData->OverTime != "-" && $shiftData->OTStatus == null && $shiftData->OTstatus !="Rejacted")
                                    <p>
                                        +{{ $shiftData->OverTime }}<span class="badge badge-themeWarning">OT</span>
                                    </p>
                                @endif


                            @elseif (isset($shiftData)  && $shiftData->Status !="" && $shiftData->Status == "Absent")
                                <b class="text-skyblue" > On Leave</b>
                                @php
                                   $TotalMonthlyLeave += 1;
                                @endphp
                            @elseif(isset($shiftData) && $shiftData->Status == "DayOff" )
                                @php
                                    $TotalMonltyDayOff += 1;
                                @endphp
                            <i class="fa-solid fa-xmark"></i>
                            @else

                            -
                            @endif

                            </td>
                        @endforeach


                        @php

                        $TotalMonthlyOt = (float) $TotalMonthlyOt;  // Cast to float

                        $totalHours = (float) $totalHours;  // Cast to float

                        $TotalMonthlyOtFormatted = sprintf('%d:%02d', floor($TotalMonthlyOt), round(($TotalMonthlyOt - floor($TotalMonthlyOt)) * 60));

                        $toatalMonthHoursForDay = $totalHours - floor($TotalMonthlyOt);  // This will result in a float value
                            $monthdays = count($monthwiseheaders) - $TotalMonltyDayOff;
                            $expectedHours = $monthdays * 8;
                            $balanceHours = $expectedHours - $totalHours;
                        @endphp

                        <td>{{ $toatalMonthHoursForDay  }}</td>
                        <td>{{ $balanceHours }}</td>
                        <td>{{ $TotalMonthlyOt }}</td>

                        <td>

                        @if($TotalMonthlyLeave !=0)
                        <span class="ttb-hover">
                            {{$TotalMonthlyLeave}}
                            @if($LeaveCategory->isNotEmpty())
                                @foreach ($LeaveCategory as $k => $leave)
                                    @php
                                        $matchedLeaveType = null;
                                        $matchedFromDate = null;
                                        $matchedToDate = null;

                                        // Check if there's a matching leave in RosterInternalDataMonth
                                        $leaveMatched = $RosterInternalDataMonth->first(function ($shiftData) use ($leave, $k) {
                                            return isset($shiftData->LeaveData[$k]) && $shiftData->LeaveData[$k]['leave_cat_id'] == $leave->id;
                                        });

                                        if ($leaveMatched) {
                                            // Extract data from matched leave
                                            $matchedLeaveData = $leaveMatched->LeaveData[$k];
                                            $matchedLeaveType = $matchedLeaveData['leave_type'] ?? null;
                                            $matchedFromDate = $matchedLeaveData['from_date'] ?? null;
                                            $matchedToDate = $matchedLeaveData['to_date'] ?? null;
                                        }
                                    @endphp

                                    @if($leaveMatched)

                                            <span class="ttb-main">
                                                <span class="ttb-inner">
                                                    <span>
                                                        <span>{{ \Carbon\Carbon::parse($matchedFromDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($matchedToDate)->format('d/m/Y') }}</span>
                                                        <p>{{ $matchedLeaveType }}</p>
                                                    </span>

                                                </span>
                                            </span>

                                    @endif
                                @endforeach
                            @endif

                        </span>
                        @else
                           0
                            <span class="ttb-main">

                                <span class="ttb-inner">
                                    <span>
                                        <p>No Leave Found..</p>
                                    </span>

                                </span>
                            </span>
                        @endif
                    </td>
                    </tr>
                @endforeach
            @endif

            </tbody>
        </table>
    </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            {!! $attandanceregister->appends(['view' => request('view', 'monthly')])->links('pagination::bootstrap-4') !!}
        </nav>
    </div>
</div>
