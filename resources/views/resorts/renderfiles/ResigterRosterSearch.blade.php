<div class="weekly-main @if($sendclass =="Weekly") d-block @else d-none @endif " >
   
    <!-- <div class="attendance-bg">
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
    </div> -->
    
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

    <!-- <div class="attendance-bg">
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
    </div> -->
    <div class="table-responsive mb-4">
    @php
        $leaveCategoryTotalCount = [];
        if ($LeaveCategory->isNotEmpty() && $attandanceregister->isNotEmpty()) {
            foreach ($attandanceregister as $empRow) {
                $rosterData = Common::GetAttandanceRegister($resort_id, $empRow->duty_roster_id, $empRow->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                foreach ($rosterData as $shiftData) {
                    if (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData)) {
                        foreach ($shiftData->LeaveData as $leaveData) {
                            $catId = isset($leaveData['leave_cat_id']) ? $leaveData['leave_cat_id'] : (isset($leaveData->leave_cat_id) ? $leaveData->leave_cat_id : null);
                            if ($catId !== null && $catId !== '') {
                                $leaveCategoryTotalCount[(int)$catId] = ($leaveCategoryTotalCount[(int)$catId] ?? 0) + 1;
                            }
                        }
                    }
                }
            }
            $leaveCategoriesWithData = array_keys(array_filter($leaveCategoryTotalCount, function ($count) { return $count > 0; }));
        } else {
            $leaveCategoriesWithData = [];
        }
    @endphp
    <table class="table table-bordered attendance-grid-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="employee-col">EMPLOYEE</th>
                                            @foreach ($monthwiseheaders as $h)
                                                @php
                                                    $currentDate = isset($h['newdate']) ? \Carbon\Carbon::createFromFormat('d-m-Y', $h['newdate'])->format('Y-m-d') : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                    $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                @endphp
                                                <th class="date-col {{ $isPublicHoliday ? 'public-holiday-header' : '' }}">
                                                    <div>{{ $h['dayname'] }}</div>
                                                    <div>{{ $h['day'] }}</div>
                                                </th>
                                            @endforeach
                                            <th class="leave-stat-col">Present</th>
                                            <th class="leave-stat-col">Absent</th>
                                            <th class="leave-stat-col">Day-off</th>
                                            @if($LeaveCategory->isNotEmpty())
                                                @foreach ($LeaveCategory as $l)
                                                    @if(in_array((int)$l->id, $leaveCategoriesWithData))
                                                    <th class="leave-stat-col">
                                                        <span class="leave-stat-badge" style="background-color: {{ $l->color }}">
                                                            {{ substr($l->leave_type, 0, 1) }}
                                                        </span>
                                                    </th>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($attandanceregister->isNotEmpty())
                                            @foreach ($attandanceregister as $a)
                                                @php
                                                    $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");
                                                    $presentCountRow = 0;
                                                    $absentCountRow = 0;
                                                    $dayOffCountRow = 0;
                                                    foreach ($RosterInternalDataMonth as $sd) {
                                                        if (isset($sd->Status)) {
                                                            if ($sd->Status == 'Present' && !empty($sd->CheckingTime)) $presentCountRow++;
                                                            elseif ($sd->Status == 'Absent') $absentCountRow++;
                                                            elseif ($sd->Status == 'DayOff') $dayOffCountRow++;
                                                        }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="employee-col">
                                                        <div class="employee-info">
                                                            <strong>{{ $a->EmployeeName }}</strong>
                                                            <small class="text-muted d-block">{{ $a->Position }}</small>
                                                        </div>
                                                    </td>
                                                    @foreach ($monthwiseheaders as $h)
                                                        @php
                                                            $date = isset($h['newdate']) ? \Carbon\Carbon::createFromFormat('d-m-Y', $h['newdate'])->format('Y-m-d') : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                                            $shiftData = $RosterInternalDataMonth->firstWhere('date', $date);
                                                            $overtimeForDate = null;
                                                            if ($overtimeData->has($a->emp_id)) {
                                                                $empOvertime = $overtimeData->get($a->emp_id);
                                                                if ($empOvertime->has($date)) {
                                                                    $overtimeForDate = $empOvertime->get($date);
                                                                }
                                                            }
                                                            $overtimeValue = $overtimeForDate ? $overtimeForDate->total_time : '0:0';

                                                            $cellClass = 'attendance-cell';
                                                            $cellStatus = '';
                                                            $cellLabel = '';
                                                            $tooltipData = '';
                                                            $cellStyle = '';

                                                            if ($shiftData) {
                                                                if($shiftData->Status == "Present" && !empty($shiftData->CheckingTime)) {
                                                                    $cellClass .= ' bg-present';
                                                                    $cellLabel = 'P';
                                                                    $cellStatus = 'PRESENT';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'PRESENT',
                                                                        'punchIn' => $shiftData->CheckingTime ?? '--:--',
                                                                        'punchOut' => $shiftData->CheckingOutTime ?? '--:--',
                                                                        'overtime' => $overtimeValue
                                                                    ]);
                                                                } elseif($shiftData->Status == "Present") {
                                                                    $cellClass .= ' bg-incomplete';
                                                                    $cellLabel = 'I';
                                                                    $cellStatus = 'INCOMPLETE (No check-in)';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'INCOMPLETE',
                                                                        'punchIn' => $shiftData->CheckingTime ?? '--:--',
                                                                        'punchOut' => $shiftData->CheckingOutTime ?? '--:--',
                                                                        'overtime' => $overtimeValue
                                                                    ]);
                                                                } elseif($shiftData->Status == "Absent") {
                                                                    $cellClass .= ' bg-absent';
                                                                    $cellLabel = 'A';
                                                                    $cellStatus = 'ABSENT';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'ABSENT'
                                                                    ]);
                                                                } elseif($shiftData->LeaveFirstName != null) {
                                                                    $leaveColor = $shiftData->LeaveColor ?? '#FFC107';
                                                                    $cellClass .= ' bg-leave';
                                                                    $cellLabel = substr($shiftData->LeaveFirstName, 0, 1);
                                                                    $cellStatus = strtoupper($shiftData->LeaveFirstName);
                                                                    $cellStyle = 'background-color: ' . $leaveColor . ' !important;';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => $shiftData->LeaveFirstName,
                                                                        'color' => $leaveColor
                                                                    ]);
                                                                } elseif($shiftData->Status == "DayOff") {
                                                                    $cellClass .= ' bg-dayoff';
                                                                    $cellLabel = '-';
                                                                    $cellStatus = 'DAY OFF';
                                                                    $tooltipData = json_encode([
                                                                        'date' => $date,
                                                                        'status' => 'DAY OFF'
                                                                    ]);
                                                                } else {
                                                                    $cellClass .= ' bg-empty';
                                                                    $cellLabel = '-';
                                                                }
                                                            } else {
                                                                $cellClass .= ' bg-empty';
                                                                $cellLabel = '-';
                                                            }
                                                        @endphp
                                                        <td class="{{ $cellClass }} {{ $isPublicHoliday ? 'public-holiday-cell' : '' }}"
                                                            data-tooltip="{{ htmlspecialchars($tooltipData) }}"
                                                            data-date="{{ $date }}"
                                                            data-employee="{{ $a->EmployeeName }}"
                                                            data-status="{{ $cellStatus }}"
                                                            @if($cellStyle) style="{{ $cellStyle }}" @endif>
                                                            {{ $cellLabel }}
                                                        </td>
                                                    @endforeach
                                                    <td class="leave-stat-cell"><span>{{ $presentCountRow }}</span></td>
                                                    <td class="leave-stat-cell"><span>{{ $absentCountRow }}</span></td>
                                                    <td class="leave-stat-cell"><span>{{ $dayOffCountRow }}</span></td>
                                                    @if($LeaveCategory->isNotEmpty())
                                                        @foreach ($LeaveCategory as $l)
                                                            @if(in_array((int)$l->id, $leaveCategoriesWithData))
                                                            @php
                                                                $leaveTypeCount = 0;
                                                                foreach ($RosterInternalDataMonth as $shiftData) {
                                                                    if (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData)) {
                                                                        foreach ($shiftData->LeaveData as $leaveData) {
                                                                            $lid = is_array($leaveData) ? ($leaveData['leave_cat_id'] ?? null) : (isset($leaveData->leave_cat_id) ? $leaveData->leave_cat_id : null);
                                                                            if ($lid !== null && (int)$lid == (int)$l->id) {
                                                                                $leaveTypeCount++;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            @endphp
                                                            <td class="leave-stat-cell"><span>{{ $leaveTypeCount }}</span></td>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
        <nav aria-label="Page navigation example">
            {!! $attandanceregister->appends(['view' => request('view', 'monthly')])->links('pagination::bootstrap-4') !!}
        </nav>
    </div>
</div>
</div>
<!-- Detailed List View - toggled with .view-normal-container -->
<div class="view-detailed-container @if($sendclass == 'Detailed') d-block @else d-none @endif">
    <div class="employee-list-view">
        @if($attandanceregister->isNotEmpty())
            @foreach ($attandanceregister as $a)
                @php
                    $RosterInternalDataMonth = Common::GetAttandanceRegister($resort_id, $a->duty_roster_id, $a->emp_id, $WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth, "Monthwise");
                    $presentCount = 0;
                    $absentCount = 0;
                    $leaveCount = 0;
                    $totalOtHours = 0;
                    if ($overtimeData->has($a->emp_id)) {
                        $empOvertime = $overtimeData->get($a->emp_id);
                        foreach ($empOvertime as $otEntry) {
                            list($Othours, $Otminutes) = explode(':', $otEntry->total_time ?? '0:0');
                            $totalOtHours += (int)$Othours + ((int)$Otminutes / 60);
                        }
                    }
                    foreach ($RosterInternalDataMonth as $shiftData) {
                        if ($shiftData->Status == "Present" && !empty($shiftData->CheckingTime)) {
                            $presentCount++;
                        } elseif ($shiftData->Status == "Absent") {
                            $absentCount++;
                        } elseif (isset($shiftData->LeaveData) && is_array($shiftData->LeaveData) && !empty($shiftData->LeaveData)) {
                            $leaveCount++;
                        }
                    }
                @endphp
                <div class="employee-card mb-3" data-employee-id="{{ $a->emp_id }}">
                    <div class="employee-card-header" onclick="toggleEmployeeDetails({{ $a->emp_id }})">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="employee-avatar">
                                    <img src="{{ $a->profileImg }}" alt="{{ $a->EmployeeName }}">
                                </div>
                            </div>
                            <div class="col">
                                <h6 class="mb-0">{{ $a->EmployeeName }}</h6>
                                <small class="text-muted"><i class="fa-regular fa-briefcase me-1"></i>{{ $a->Position }}</small>
                            </div>
                            <div class="col-auto">
                                <span class="stat-badge stat-present">{{ $presentCount }} Present</span>
                                <span class="stat-badge stat-absent">{{ $absentCount }} Absent</span>
                                <span class="stat-badge stat-leave">{{ $leaveCount }} Leave</span>
                                <span class="stat-badge stat-ot">{{ round($totalOtHours) }}h OT</span>
                            </div>
                            <div class="col-auto"><i class="fa-regular fa-chevron-down toggle-icon"></i></div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>