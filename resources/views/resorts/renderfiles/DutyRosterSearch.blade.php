<div class="weekly-main @if($sendclass =="Weekly") d-block @else d-none @endif" >
    <div class="table-responsive mb-4">
        <table id="createDutyWeeklyTable" class="table table-createDutyWeekly mb-1">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    @foreach ($headers as $d)
                        @php
                            $currentDate = isset($d['full_date']) ? $d['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($d['date']));
                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                        @endphp
                        <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $d['date'] }} <span>{{ $d['day'] }}</span></th>
                    @endforeach
                    <th>Summary</th>
                </tr>
            </thead>
            <tbody>
                @if($Rosterdata->isNotEmpty())
                    @foreach ($Rosterdata as $r)
                        <tr>
                            <td>
                                <div class="createDuty-user">
                                    <div class="img-circle">
                                        <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                    </div>
                                    <div>
                                        <p>
                                            <span class="fw-600">{{ ucfirst($r->first_name .' '. $r->last_name) }}</span>
                                            <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                        </p>
                                        <span>{{ ucfirst($r->position_title) }}</span>
                                    </div>
                                </div>
                            </td>

                            @php

                                $RosterInternalData = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate , $startOfMonth,$endOfMonth,"weekly");
                            //    dd( $RosterInternalData,$WeekstartDate, $WeekendDate);
                                $totalHours = 0;
                                $dataCount = $RosterInternalData->count();
                                $minColumns = 7; // Minimum number of columns to account for all days of the week
                            @endphp

                            @foreach ($headers as $header)
                                @php
                                    $currentDate = isset($header['full_date']) ? $header['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($header['date']));
                                    $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                    $shiftData = $RosterInternalData->firstWhere('date', $currentDate);

                                    $toatalHoursForDay = 0;
                                    if ($shiftData) {
                                        if($shiftData->Status!= 'DayOff'){
                                            $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                            $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                            $hours_abc = $startTime->diffInHours($endTime);
                                            // list($hours, $minutes) = explode(':', $shiftData->DayWiseTotalHours);
                                            // $toatalHoursForDay = (int)$hours + ((int)$minutes / 60); // Convert to decimal hours
                                            $toatalHoursForDay = $hours_abc;
                                            $totalHours += $toatalHoursForDay; // Update total hours worked
                                        }else{
                                            $toatalHoursForDay = 0;
                                        }
                                    }
                                @endphp

                                <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }}">
                                    <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }}">
                                        <div class="d-flex">
                                            @if ($shiftData)
                                                <div>
                                                    <p>{{ $startTime->format('h:i A') }} - {{ $endTime->format('h:i A') }}</p>
                                                    <span>{{ $shiftData->ShiftName }}</span>
                                                </div>
                                                <div class="badge">{{ $toatalHoursForDay }} {{ $shiftData->color }} hrs</div>
                                            @else
                                                <div class="createDuty-empty">No Shift Assigned</div>
                                            @endif
                                        </div>
                                        @if ($shiftData)
                                            <div class="d-flex ot-details">
                                                @if ($shiftData)
                                                    <p>OT: {{ $shiftData->OverTime ?? 0 }} hr</p>
                                                @endif
                                                <p>
                                                    @if($shiftData->Status!= 'DayOff')
                                                        <button class="editIcon-btn editdutyRoster"
                                                                data-date="{{ date('d/m/Y', strtotime($header['date'])) }}"
                                                                data-Shift_id="{{ $shiftData->Shift_id ?? '' }}"
                                                                data-OverTime="{{ $shiftData->OverTime ?? 0 }}"
                                                                data-DayOfDate="{{ $shiftData->DayOfDate ?? '' }}"
                                                                data-Attd_id="{{ $shiftData->Attd_id ?? '' }}"
                                                                data-DayWiseTotalHours="{{ $toatalHoursForDay ?? '' }}">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                    @else
                                                        DayOff
                                                    @endif
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach



                            <td>Total Hrs: <span>{{  $totalHours }}</span></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($headers) + 2 }}" style="text-align: center">No Records Found..</td>
                    </tr>
                @endif
            </tbody>
        </table>

    </div>
    {{-- <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-end">
            <li class="page-item "><a class="page-link" href="#"><i
                        class="fa-solid fa-angle-left"></i></a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item"><a class="page-link" href="#"><i
                        class="fa-solid fa-angle-right"></i></a>
            </li>

        </ul>

    </nav> --}}
    <div class="pagination-custom"> {{ $Rosterdata->links() }}</div>

</div>
<div class="monthly-main  @if($sendclass !="Weekly") d-block @else d-none @endif" >
    <div class="table-responsive mb-4">
        <table id="" class="table table-bordered table-createDutymonthly mb-1">


            <thead>
                <tr>
                    <th>Employee Name</th>
                    @if(!empty($monthwiseheaders))
                        @foreach ($monthwiseheaders as $h)
                            @php
                                $currentDate = isset($h['date']) ? $h['date'] : date('Y-m-d', strtotime($h['day']));
                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                            @endphp
                            <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                        @endforeach
                    @endif


                    @if($LeaveCategory->isNotEmpty())
                        @foreach ($LeaveCategory as $l)
                            <th class="col-total">
                                <span class="badge" style="background-color:{{ $l->color }}">{{ substr($l->leave_type,0,1) }}</span>
                            </th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($Rosterdata->isNotEmpty())
                    @foreach ($Rosterdata as $r)
                        <tr>
                            <td>
                                <div class="createDuty-user">
                                    <div class="img-circle">
                                        <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                    </div>
                                    <div>
                                        <p>
                                            <span class="fw-600">{{ ucfirst($r->first_name . ' ' . $r->last_name) }}</span>
                                            <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                        </p>
                                        <span>{{ ucfirst($r->position_title) }}</span>
                                    </div>
                                </div>
                            </td>

                            @php

                                $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate,  $startOfMonth,$endOfMonth,"Monthwise");

                            @endphp

                            <!-- Loop through each monthwise header for the status per day -->
                            @foreach ($monthwiseheaders as $h)
                                @php
                                // $date = date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT);

                                $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);

                                $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);


                                @endphp
                                <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }} @if(isset($shiftData) && $shiftData->Status == "DayOff" ) col-leave @endif">
                                    @if ($shiftData && $shiftData->LeaveFirstName != null)
                                        @if($shiftData->Status == "Present")
                                            P
                                        @elseif($shiftData->Status == "DayOff" )

                                            <i class="fa-solid fa-xmark"></i>
                                        @else
                                            <span class="badge" style="background-color:{{ $shiftData->LeaveColor }}">
                                                {{ $shiftData->LeaveFirstName }}
                                            </span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                            @endforeach


                            @foreach ($LeaveCategory as $l)

                                @php
                                    $leaveTypeCount = $RosterInternalDataMonth->sum(function($shiftData) use ($l) {


                                        return isset($shiftData->StatusCount[$l->leave_type]) ? $shiftData->StatusCount[$l->leave_type] : 1;
                                    });
                                @endphp

                                <td>
                                    {{ $leaveTypeCount }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>



        </table>
    </div>
    <div class="pagination-custom">
        <nav aria-label="Page navigation example">
            {{ $Rosterdata->links() }}
        </nav>
    </div>


</div>
