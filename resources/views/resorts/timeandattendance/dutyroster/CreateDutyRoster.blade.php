@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <div class="body-wrapper pb-5 drc-page">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance </span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <form id="DutyRosterForm" class="@if(Common::checkRouteWisePermission('resort.timeandattendance.CreateDutyRoster',config('settings.resort_permissions.create')) == false) d-none @endif">
                    @csrf()
                    <div class="row g-xl-4 g-3 mb-3 drc-panels">
                        <div class="col-lg-4 col-md-6 createDuty-emp">
                            <div class="col-stack">
                                <div class="drc-card">
                                    <div class="drc-card-header">
                                        <span class="drc-step-badge">1</span>
                                        <span class="drc-card-title">Select Employee</span>
                                        <span class="drc-count-chip" id="drcEmpCountChip">0 selected</span>
                                    </div>
                                    <div class="drc-card-body">
                                        <label class="drc-label">All Employees</label>
                                        <div class="drc-search">
                                            <i class="fa fa-search"></i>
                                            <input type="text" id="drcEmpSearch" placeholder="Search employees...">
                                        </div>
                                        <div class="drc-emp-list" id="drcEmpList">
                                            @if($employees->isNotEmpty())
                                                @foreach ($employees as $e)
                                                    <label class="drc-emp-row" data-emp-name="{{ strtolower($e->first_name . ' ' . $e->last_name) }}">
                                                        <input type="checkbox" class="drc-emp-checkbox" value="{{ $e->id }}">
                                                        <span class="drc-avatar drc-avatar-{{ $e->id % 6 }}">
                                                            <img src="{{ Common::getResortUserPicture($e->Admin_Parent_id) }}" alt="">
                                                        </span>
                                                        <span class="drc-emp-info">
                                                            <span class="drc-emp-name">{{ ucfirst($e->first_name . ' ' . $e->last_name) }}</span>
                                                            @if($e->position_title)
                                                                <span class="drc-emp-position">{{ ucfirst($e->position_title) }}</span>
                                                            @endif
                                                            <span class="drc-emp-code">{{ $e->Emp_id }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            @endif
                                        </div>

                                        {{-- Real, submitted field — kept exactly as before (existing
                                             change/leave-lookup/validation logic all binds to this),
                                             just visually hidden in favour of the checkbox list above,
                                             which stays in sync with it via JS. --}}
                                        <select class="form-select d-none" name="Emp_id[]" id="Employee" multiple>
                                            @if($employees->isNotEmpty())
                                                @foreach ($employees as $e)
                                                    <option value="{{ $e->id }}" data-rank="{{ $e->rank ?? '' }}">{{ ucfirst($e->first_name . ' ' . $e->last_name) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="drc-card">
                                    <div class="drc-card-header">
                                        <span class="badge-sm">&#10003;</span>
                                        <span class="drc-card-title">Selected Employees</span>
                                        <span class="drc-count-chip" id="drcEmpCountChip2">0 selected</span>
                                    </div>
                                    <div class="drc-card-body">
                                        <div class="drc-selected-pills" id="drcSelectedPills"></div>
                                        <div class="createduty-Append drc-emp-detail-scroll mt-2"></div>
                                        <p id="rosterDisabledDatesMsg" class="small text-muted mt-2 mb-0 d-none" role="alert">
                                            <i class="fa fa-info-circle me-1"></i><span id="rosterDisabledDatesList"></span><br><span id="rosterDisabledDatesNote">The selected employee(s) already have scheduled shifts on these days.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="drc-card h-100">
                                <div class="drc-card-header">
                                    <span class="drc-step-badge">2</span>
                                    <span class="drc-card-title">Select Date Range</span>
                                    <span id="dutyRosterDayCountBadge" class="duty-roster-daycount-badge d-none"></span>
                                </div>
                                <div class="drc-card-body">
                                    <div class="cal-top">
                                        <div class="mon" id="drcCalMonthLabel">&nbsp;</div>
                                        <div class="cal-nav">
                                            <button type="button" id="drcCalPrev" title="Previous">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M15 18l-6-6 6-6"/>
                                                </svg>
                                            </button>
                                            <button type="button" id="drcCalNext" title="Next">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M9 18l6-6-6-6"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="datapicker" class="configTimeAtten-dateRange">
                                        <!-- Hidden input field to attach the calendar to -->
                                        <input type="hidden" name="hiddenInput" id="hiddenInput">
                                        <input type="hidden" name="s" id="s">
                                    </div>
                                    <p id="startDate" class="d-none">Start Date:</p>
                                    <p id="endDate" class="d-none">End Date:</p>
                                    <div class="drc-legend">
                                        <span><span class="drc-legend-dot drc-legend-selected"></span>Selected range</span>
                                        <span><span class="drc-legend-dot drc-legend-inrange"></span>In range</span>
                                        <span><span class="drc-legend-dot drc-legend-off"></span>Day off</span>
                                        <span><span class="drc-legend-dot drc-legend-leave"></span>Leave applied</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 createDuty-date">
                            <div class="drc-card">
                                <div class="drc-card-header">
                                    <span class="drc-step-badge">3</span>
                                    <span class="drc-card-title">Shift Configuration</span>
                                </div>
                                <div class="drc-card-body">
                                    <div class="row g-lg-4 g-sm-3 g-2">
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-toggle">
                                            <input class="form-check-input" type="checkbox" id="DefaultShiftTime" value="All" name="DefaultShiftTime" checked="">
                                            <span class="drc-toggle-track"><span class="drc-toggle-thumb"></span></span>
                                            <span class="drc-toggle-label">Set default shift time</span>
                                        </label>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-label">Applied Date Range</label>
                                        <div class="drc-readonly-field">
                                            <i class="fa-regular fa-calendar"></i>
                                            <input type="text" class="form-control datepicker" name="MakeShift" disabled id="MakeShift" placeholder="10 Sep - 14 Sep">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-label">Shift</label>
                                        <select class="form-select select2t-none" id="Shift"
                                            aria-label="Default select example" name="Shift">
                                            <option></option> <!-- Leave this blank for the placeholder -->
                                            @if($ShiftSettings->isNotEmpty())
                                                @foreach ($ShiftSettings as $s)
                                                    @php
                                                        $shiftTimeLabel = '';
                                                        if ($s->StartTime && $s->EndTime) {
                                                            $shiftTimeLabel = ' (' . \Carbon\Carbon::parse($s->StartTime)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($s->EndTime)->format('g:i A') . ')';
                                                        }
                                                        $shiftHoursLabel = '';
                                                        if ($s->TotalHours) {
                                                            [$shiftHrsPart, $shiftMinPart] = array_pad(explode(':', $s->TotalHours), 2, 0);
                                                            $shiftHoursLabel = ' · ' . (int) $shiftHrsPart . 'h' . ((int) $shiftMinPart ? ' ' . (int) $shiftMinPart . 'm' : '');
                                                        }
                                                    @endphp
                                                    <option value="{{ $s->id }}" data-totalHrs="{{ $s->TotalHours ?? '' }}" data-startTime="{{ $s->StartTime ?? '' }}" data-endTime="{{ $s->EndTime ?? '' }}">{{ ucfirst($s->ShiftName) . $shiftTimeLabel . $shiftHoursLabel }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 hideoverTimeTr">
                                        <a href="javascript:void(0)" class="a-link addOvertime-modal d-block mb-3 drc-overtime-link drc-ot-link-disabled"><i class="fa fa-plus-circle me-1"></i>Add Overtime</a>
                                        <div id="overtimeSummary" class="mb-2"></div>
                                    </div>
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-label">Day-Off Dates</label>
                                        <div class="drc-dayoff-dropzone" id="drcDayOffDropzone">
                                            <span class="drc-dayoff-dropzone-title">Mark day off in the calendar</span>
                                            <span class="drc-dayoff-dropzone-sub" id="drcDayOffDropzoneSub">Click a date inside your selected range in Select Date Range</span>
                                        </div>
                                        {{-- No longer opens a picker of its own — day-off dates are
                                             toggled by clicking in-range days directly on the Select
                                             Date Range calendar (#datapicker). This stays a plain
                                             hidden field, kept in sync from JS, purely so the existing
                                             name="DayOffDates" required-field validation and form
                                             submission keep working unchanged. --}}
                                        <input type="text" class="form-control drc-visually-hidden" id="DayOffDates" name="DayOffDates" placeholder="Click to select day off dates" readonly>
                                        <div class="drc-dayoff-chips" id="drcDayOffChips"></div>
                                    </div>

                                    {{-- Geofence Zone Selection --}}
                                    @if(isset($geofenceZones) && $geofenceZones->count())
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-label">Assign Geo-Fence Zone</label>
                                        <div class="drc-search drc-zone-search">
                                            <i class="fa fa-search"></i>
                                            <input type="text" id="gfZoneSearch" placeholder="Search zones...">
                                        </div>
                                        <div class="drc-zone-list">
                                            <div id="gfZoneItems">
                                                @foreach($geofenceZones as $zone)
                                                <label class="drc-zone-row gf-zone-item" data-name="{{ strtolower($zone->name) }}">
                                                    <input type="checkbox" name="geofence_zone_ids[]" value="{{ $zone->id }}" class="drc-zone-checkbox gf-zone-checkbox">
                                                    <span class="drc-zone-dot" style="background:{{ $zone->color }};"></span>
                                                    <span class="drc-zone-name">{{ $zone->name }}</span>
                                                    <span class="drc-zone-radius">{{ $zone->radius ?? '' }}{{ isset($zone->radius) ? 'm radius' : '' }}</span>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <small class="text-muted">Employees can only check in/out inside selected zones.</small>
                                    </div>
                                    @else
                                    <div class="col-lg-12 col-sm-6">
                                        <label class="drc-label text-muted">Geo-Fence Zone</label>
                                        <p class="small text-muted mb-0">No active zones configured. <a href="{{ route('resort.timeandattendance.Configration') }}">Configure zones</a></p>
                                    </div>
                                    @endif

                                    <div class="col-12">
                                        <div class="drc-summary-box">
                                            <div class="drc-summary-row">
                                                <div>
                                                    <p class="drc-summary-label">Net Shift Hours</p>
                                                    <small class="drc-summary-sublabel">After day-off deduction</small>
                                                </div>
                                                <input type="hidden" name="TotalHours" id="TotalHoursInput" value="">
                                                <h5 id="TotalHours" class="drc-summary-value">0</h5>
                                            </div>
                                            <div class="drc-summary-row d-none">
                                                <p class="drc-summary-label">Day Off Deduction</p>
                                                <h5 id="DayOffDeduction" class="drc-summary-value">0</h5>
                                            </div>
                                            <div class="drc-summary-row">
                                                <p class="drc-summary-label">Overtime Total</p>
                                                <h5 id="OvertimeTotalHours" class="drc-summary-value">0</h5>
                                            </div>
                                            <div class="drc-summary-final">
                                                <p class="drc-summary-label">Final Total Hours</p>
                                                <input type="hidden" name="FinalTotalHours" id="FinalTotalHoursInput" value="">
                                                <h5 id="FinalTotalHours">0</h5>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="resort_id" value="{{$resort_id}}" >

                    </div>
                </form>
            </div>
            <div class="drc-footer">
                <span class="drc-footer-summary" id="drcFooterSummary">Rostering 0 employees &middot; 0 work days &middot; 0 zones</span>
                <button type="submit" form="DutyRosterForm" class="drc-submit-btn">Submit</button>
            </div>
        </div>
    </div>
                    <!-- <div class="card bg mt-4">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                                    <div class="input-group">
                                        <input type="search" class="form-control search" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                                {{-- <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text" class="form-control " placeholder="Management">
                                </div> --}}
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <select class="form-select" name="Position" id="Position">
                                        <option ></option>
                                        @if($ResortPosition->isNotEmpty())

                                            @foreach ($ResortPosition as $p)
                                                <option value="{{ $p->id }}">{{ ucfirst($p->position_title) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                    <input type="text"  class="form-control  datepicker" id="DutyRosterCreateDatePickerFilter" placeholder="Select Date">

                                </div>

                                <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                   <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                                </div>

                                <div class="col-auto ms-auto">
                                    <a href="javascript:void(0)" class="btn btn-weekly active">Weekly</a>
                                    <a href="javascript:void(0)" class="btn btn-monthly ">Monthly</a>
                                </div>
                            </div>
                        </div>
                        <div class="appendData">
                            <div class="weekly-main">
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
                                                                    @if(!empty($r->geofence_zone_id))
                                                                        @php
                                                                            $zoneIds = json_decode($r->geofence_zone_id, true) ?? [];
                                                                            $zones = $geofenceZones->whereIn('id', $zoneIds);
                                                                        @endphp
                                                                        @foreach($zones as $zone)
                                                                            <span class="badge me-1" style="background:{{ $zone->color }}22; color:{{ $zone->color }}; border:1px solid {{ $zone->color }}; font-size:10px;">
                                                                                <i class="fa-solid fa-{{ $zone->shape_type === 'circle' ? 'circle' : 'draw-polygon' }} me-1"></i>{{ $zone->name }}
                                                                            </span>
                                                                        @endforeach
                                                                    @endif
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
                                                                // Use full_date if available (Carbon object), otherwise parse date string
                                                                $currentDate = isset($header['full_date']) ? $header['full_date']->format('Y-m-d') : date('Y-m-d', strtotime($header['date']));
                                                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                                $shiftData = $RosterInternalData->firstWhere('date', $currentDate);

                                                                // Common::GetRosterdata() already resolved approved-leave
                                                                // data for this date (batched, not per-day) — reuse it
                                                                // instead of re-querying EmployeeLeave here per row per day.
                                                                $employeeLeave = ($shiftData && !empty($shiftData->LeaveFromDate))
                                                                    ? (object) [
                                                                        'color' => $shiftData->LeaveColor ?? null,
                                                                        'leave_type' => $shiftData->LeaveType ?? null,
                                                                        'leave_category' => $shiftData->LeaveCategory ?? null,
                                                                    ]
                                                                    : null;
                                                                
                                                                $toatalHoursForDay = 0;
                                                                if ($shiftData && !$employeeLeave)
                                                                {
                                                                    if($shiftData->Status!= 'DayOff')
                                                                    {
                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                        $hours_abc = $startTime->diffInHours($endTime);
                                                                        $toatalHoursForDay = $hours_abc;
                                                                        $totalHours += $toatalHoursForDay; // Update total hours worked
                                                                    }else{
                                                                        $toatalHoursForDay = 0;
                                                                    }
                                                                }
                                                            @endphp

                                                            <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }}">
                                                                @if($employeeLeave)
                                                                    {{-- Display Leave --}}
                                                                    <div class="createDuty-tableBlock" style="border-color: {{ $employeeLeave->color ?? '#ccc' }}; border-width: 2px;">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <p class="fw-600">{{ $employeeLeave->leave_type ?? 'Leave' }}</p>
                                                                                @if($employeeLeave->leave_category)
                                                                                    <span class="small">{{ $employeeLeave->leave_category }}</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @elseif($shiftData)
                                                                    {{-- Display Roster Entry --}}
                                                                    <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }}">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                @php
                                                                                    $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                    $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                @endphp
                                                                                <p>{{ $startTime->format('h:i A') }} - {{ $endTime->format('h:i A') }}</p>
                                                                                <span>{{ $shiftData->ShiftName }}</span>
                                                                            </div>
                                                                            <div class="badge">{{ $toatalHoursForDay }} {{ $shiftData->color ?? '' }} hrs</div>
                                                                        </div>
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
                                                                    </div>
                                                                @else
                                                                    {{-- No Leave and No Roster Entry --}}
                                                                    <div class="createDuty-tableBlock">
                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                    </div>
                                                                @endif
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
                            <div class="monthly-main  d-none">
                                <div class="table-responsive mb-4">
                                    <table id="" class="table table-bordered table-createDutymonthly mb-1">


                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                @if(!empty($monthwiseheaders))
                                                    @foreach ($monthwiseheaders as $h)
                                                        @php
                                                            // Ensure date is in Y-m-d format
                                                            if (isset($h['date'])) {
                                                                $currentDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                            } else {
                                                                $currentDate = \Carbon\Carbon::createFromDate($startOfMonth->year, $startOfMonth->month, $h['day'])->format('Y-m-d');
                                                            }
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                        @endphp
                                                        <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                                                    @endforeach
                                                @endif


                                                <th>Summary</th>
                                                @if($LeaveCategory->isNotEmpty())

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

                                                        @php
                                                            $totalHoursMonth = 0;
                                                        @endphp
                                                        @foreach ($monthwiseheaders as $h)
                                                        @php
                                                            // Ensure date is in Y-m-d format
                                                            if (isset($h['date'])) {
                                                                $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                            } else {
                                                                $formattedDate = \Carbon\Carbon::createFromDate($startOfMonth->year, $startOfMonth->month, $h['day'])->format('Y-m-d');
                                                            }
                                                            $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                            $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);

                                                            // Common::GetRosterdata() already resolved approved-leave
                                                            // data for this date (batched, not per-day) — reuse it
                                                            // instead of re-querying EmployeeLeave here per row per day.
                                                            $employeeLeave = ($shiftData && !empty($shiftData->LeaveFromDate))
                                                                ? (object) [
                                                                    'color' => $shiftData->LeaveColor ?? null,
                                                                    'leave_type' => $shiftData->LeaveType ?? null,
                                                                    'leave_category' => $shiftData->LeaveCategory ?? null,
                                                                ]
                                                                : null;

                                                            $toatalHoursForDay = 0;
                                                            $startTime = null;
                                                            $endTime = null;
                                                            if ($shiftData && !$employeeLeave)
                                                            {
                                                                if($shiftData->Status != 'DayOff')
                                                                {
                                                                    $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                    $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                    $hours_abc = $startTime->diffInHours($endTime);
                                                                    $toatalHoursForDay = $hours_abc;
                                                                    $totalHoursMonth += $toatalHoursForDay;
                                                                }else{
                                                                    $toatalHoursForDay = 0;
                                                                    // Still show shift times for DayOff if available
                                                                    if(isset($shiftData->StartTime) && isset($shiftData->EndTime)) {
                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                    }
                                                                }
                                                            }
                                                        @endphp

                                                            <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }}">
                                                                @if($employeeLeave)
                                                                    {{-- Display Leave --}}
                                                                    <div class="createDuty-tableBlock" style="border-color: {{ $employeeLeave->color ?? '#ccc' }}; border-width: 2px;">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                <p class="fw-600">{{ $employeeLeave->leave_type ?? 'Leave' }}</p>
                                                                                @if($employeeLeave->leave_category)
                                                                                    <span class="small">{{ $employeeLeave->leave_category }}</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @elseif($shiftData)
                                                                    {{-- Display Roster Entry --}}
                                                                    <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }}">
                                                                        <div class="d-flex">
                                                                            <div>
                                                                                @php
                                                                                    if(!$startTime && isset($shiftData->StartTime)) {
                                                                                        $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                    }
                                                                                    if(!$endTime && isset($shiftData->EndTime)) {
                                                                                        $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                    }
                                                                                @endphp
                                                                                <p>@if($startTime && $endTime){{ $startTime->format('h:i A') }} - {{ $endTime->format('h:i A') }}@endif</p>
                                                                                <span>{{ $shiftData->ShiftName ?? '' }}</span>
                                                                            </div>
                                                                            <div class="badge">{{ $toatalHoursForDay }} hrs</div>
                                                                        </div>
                                                                        <div class="d-flex ot-details">
                                                                            @if ($shiftData)
                                                                                <p>OT: {{ $shiftData->OverTime ?? 0 }} hr</p>
                                                                            @endif
                                                                            <p>
                                                                                @if($shiftData->Status != 'DayOff')
                                                                                    <button class="editIcon-btn editdutyRoster"
                                                                                            data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
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
                                                                    </div>
                                                                @else
                                                                    {{-- No Leave and No Roster Entry --}}
                                                                    <div class="createDuty-tableBlock">
                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        @endforeach

                                                        <td>Total Hrs: <span>{{ $totalHoursMonth }}</span></td>


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
                        </div>
                    </div> -->

    <div class="modal fade" id="editdutyRoster-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Duty Roster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="UpdateDutyRoster">
                    @csrf
                    <div class="modal-body">
                        <div class="row mt-3">
                            <div class="col-md-12 mt-3">
                                <lable>Shift Date </lable>
                                <input type="text" readonly class="form-control" id="shiftdate" name="shiftdate" placeholder="Shift Date">
                            </div>

                            <div class="col-md-12 mt-3">
                                <lable>Shift </lable>
                                <select class="form-select select2t-none" id="Shiftpopup"  aria-label="Default select example" name="Shiftpopup">
                                    <option></option>
                                    @if($ShiftSettings->isNotEmpty())
                                        @foreach ($ShiftSettings as $s)

                                            <option value="{{ $s->id }}"  data-totalHrs="{{ $s->TotalHours }}"> {{ ucfirst($s->ShiftName) }} </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-12 mt-3 ShiftOverTimetr">
                                <lable>Over Time </lable>
                                <input type="text" class="form-control ShiftOverTime" id="ShiftOverTime" name="ShiftOverTime" placeholder="Over Time">
                            </div>
                            <div class="col-md-12 mt-3">
                                <label>Select Day Off Dates</label>
                                <input type="text" class="form-control" id="DayOffDatesModel" name="DayOffDatesModel" placeholder="Click to select day off dates" readonly style="background-color: white; cursor: pointer;">
                                <small class="text-muted">Click to select multiple dates</small>
                            </div>

                            <div class="col-12 mt-3">
                                <hr class="mt-0 ">
                                <div class="bg-white text-end">
                                    <p>Total Hours:</p>
                                    <input type="hidden" name="TotalHoursModel" id="TotalHoursModelInput" value="">
                                    <h5 id="TotalHoursModel">0</h5>
                                </div>
                            </div>
                            <input type="hidden" id="Attd_id" name="Attd_id">
                        </div>

                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit"   class="btn btn-theme" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Overtime Modal - Manage OT hours per date for each employee -->
    <div class="modal fade drc-ot-modal" id="addOvertimeModal" tabindex="-1" aria-labelledby="addOvertimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="drc-ot-header">
                    <h5 class="drc-ot-title" id="addOvertimeModalLabel">Manage Overtime by Date</h5>
                    <button type="button" class="drc-ot-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body drc-ot-body">
                    <div class="drc-ot-emp-section">
                        <label class="drc-label">Select employees for overtime</label>
                        <select class="form-select" id="OvertimeEmployees" multiple>
                            <!-- Will be populated dynamically -->
                        </select>
                        <p class="drc-ot-note"><i class="fa fa-info-circle me-1"></i>Note: select only Line Workers and Supervisor roles.</p>
                    </div>
                    <div class="drc-ot-legend">
                        <span class="drc-ot-legend-item"><span class="drc-ot-lock-marker drc-ot-lock-off">O</span>Day off</span>
                        <span class="drc-ot-legend-item"><span class="drc-ot-lock-marker drc-ot-lock-leave">L</span>On leave</span>
                    </div>
                    <div id="overtimeEmployeesList" class="drc-ot-grid-wrap">
                        <!-- Dynamic: per-employee rows with per-date OT inputs -->
                    </div>
                </div>
                <div class="modal-footer drc-ot-footer">
                    <span class="drc-ot-footer-summary" id="drcOvertimeFooterSummary">0 employees &middot; Total OT 00:00</span>
                    <div class="drc-ot-footer-actions">
                        <button type="button" class="drc-ot-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="drc-ot-btn-save" id="saveOvertimeBtn">Save Overtime</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('import-css')
@include('resorts.timeandattendance._taa_buttons_v2_styles')
<style>
    /* Flatpickr custom styling for selected dates in multiple mode */
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover,
    .flatpickr-day.selected:focus {
        background: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
        font-weight: bold;
    }

    .flatpickr-day.selected.startRange,
    .flatpickr-day.selected.endRange {
        background: #0056b3 !important;
        border-color: #0056b3 !important;
    }

    /* Available dates - make them clearly visible */
    .flatpickr-day:not(.disabled):not(.selected) {
        background: #fff;
        color: #333;
        cursor: pointer;
        font-weight: 500;
    }

    /* Hover effect for available dates */
    .flatpickr-day:hover:not(.disabled):not(.selected) {
        background: #e3f2fd !important;
        border-color: #90caf9 !important;
        cursor: pointer;
    }

    /* Styling for today's date */
    .flatpickr-day.today {
        border-color: #007bff !important;
        position: relative;
        font-weight: bold;
    }

    .flatpickr-day.today:not(.selected) {
        background: #fff;
        color: #007bff;
    }

    /* Disabled dates styling - make them obviously disabled */
    .flatpickr-day.disabled,
    .flatpickr-day.disabled:hover,
    .flatpickr-day.flatpickr-disabled {
        color: #e0e0e0 !important;
        cursor: not-allowed !important;
        background: #fafafa !important;
        text-decoration: line-through;
        opacity: 0.4;
    }

    /* Prev/next month dates */
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: #bdbdbd !important;
    }

    .flatpickr-day.prevMonthDay.disabled,
    .flatpickr-day.nextMonthDay.disabled {
        color: #e0e0e0 !important;
        opacity: 0.3;
    }

    /* Calendar container z-index */
    .flatpickr-calendar {
        z-index: 9999 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
    }

    /* Better visibility for month selector */
    .flatpickr-monthDropdown-months {
        background: white;
    }

    /* Style for the input field when picker is open */
    .flatpickr-input.active,
    #DayOffDates.active,
    #DayOffDatesModel.active {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }

    /* Month navigation arrows */
    .flatpickr-months .flatpickr-prev-month:hover,
    .flatpickr-months .flatpickr-next-month:hover {
        color: #007bff;
    }

    /* Current month highlight */
    .flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
        background: white;
    }

    /* ==================================================================
       Date-range picker redesign (per reference screenshot): rounded
       "chip" cells with real gaps between them instead of one connected
       flat bar, a day-count badge, and nicer nav buttons — same colors
       already used elsewhere on this page (#004552 dark teal), no new
       palette. Two earlier attempts at a perfect circle (fixed
       width/height, then aspect-ratio on a ::before layer) both fought
       the table's own cell-sizing algorithm and caused regressions
       (oval shapes, misaligned/invisible text). This version doesn't
       fight table layout at all — it styles the <td> itself directly
       (so the browser's normal text-centering via padding is never
       touched) and uses border-spacing for the gaps between cells,
       which is what table layout is actually built to do reliably.
       Scoped to this page's picker (#datapicker) rather than editing
       the shared default.css / daterangepicker.css rules, which other
       pages using the date-range picker also rely on. ================ */
    #datapicker .daterangepicker .calendar-table table {
        border-collapse: separate;
        border-spacing: 3px 5px;
    }
    #datapicker .daterangepicker .calendar-table td.in-range {
        background: #0045521a;
        color: #004552;
        border-radius: 8px;
        cursor: pointer;
    }
    /* Toggled by clicking an in-range day (see the #datapicker click
       handler below) — marks it as a day off instead of starting a new
       range selection. */
    #datapicker .daterangepicker .calendar-table td.in-range.drc-day-off {
        background: var(--off);
        color: #fff;
    }
    /* Brief highlight when the Day-Off dropzone in Shift Configuration
       is clicked, to point the user at this calendar. */
    @keyframes drcPulse {
        0%, 100% { box-shadow: none; }
        50% { box-shadow: 0 0 0 3px var(--lime); }
    }
    #datapicker.drc-pulse { animation: drcPulse 0.45s ease-in-out 2; border-radius: 10px; }
    #datapicker .daterangepicker .calendar-table td.start-date,
    #datapicker .daterangepicker .calendar-table td.end-date {
        background: #004552;
        color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 0 2px var(--lime);
    }

    /* The plugin's own month-header row (prev th / "Mon YYYY" th / next
       th, auto-rendered inside .calendar-table's <thead>) is replaced
       by the custom .cal-top bar above the calendar — see markup and
       JS below. Its month-navigation logic is untouched: #drcCalPrev/
       #drcCalNext just forward a mousedown to this row's own (hidden)
       prev/next cells, the same event the plugin itself listens for. */
    #datapicker .daterangepicker .calendar-table thead tr:first-child {
        display: none;
    }
    .cal-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .cal-top .mon {
        font-size: 15px;
        font-weight: 650;
    }
    .cal-nav {
        display: flex;
        gap: 6px;
    }
    .cal-nav button {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: #fff;
        cursor: pointer;
        color: var(--muted);
        display: grid;
        place-items: center;
        transition: background .12s, color .12s;
    }
    .cal-nav button:hover {
        background: var(--teal-soft);
        color: var(--ink);
    }
    .cal-nav button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: #fff;
        color: var(--muted);
    }

    /* Visually hidden but still present in the DOM — unlike
       display:none, so the element keeps participating in form
       submission/validation normally. */
    .drc-visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* "N days" badge above the calendar, kept in sync with the
       selected range by updateDateText() in the script below. */
    .duty-roster-daycount-badge {
        display: inline-block;
        background: #0045521a;
        color: #004552;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        margin-left: auto;
        white-space: nowrap;
    }

    /* ==================================================================
       Create Duty Roster — full visual redesign. View-layer only: every
       existing id/name (#Employee, #hiddenInput, #Shift, #DayOffDates,
       .gf-zone-item, #TotalHours, etc.), event handler, AJAX call, and
       validation rule is untouched — the real <select id="Employee"> is
       kept in the DOM (just visually hidden) and the new checkbox list
       below is wired to stay in sync with it via JS, so the existing
       change handler (leave lookup, hours recalc, occupied-dates fetch)
       fires exactly as it did before. ================================== */
    /* Neutral/geometry tokens (--teal/--teal-2/--teal-3/--teal-soft/--lime/
       --ink/--muted/--faint/--line/--line-2/--card) now come from the
       shared :root palette (resorts/layouts/_design_tokens.blade.php).
       --bg/--off/--leave stay local — page-specific/semantic, not part
       of the shared set. */
    .drc-page {
        --bg: #f2f6f6;
        --off: #e5573f;
        --leave: #d98a00;
        color: var(--ink);
    }

    /* ---- Cards / panels ---- */
    /* flex-start (not stretch) — each panel sizes to its own content
       instead of being forced to match the tallest sibling column,
       which was leaving a large empty gap under the shorter
       Select-Date-Range card's legend. */
    .drc-panels { align-items: flex-start; }
    .drc-card {
        background: var(--card);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(1,70,83,0.08);
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    /* Panel 1 split into two stacked cards (Select Employee / Selected
       Employees) — each sizes to its own content rather than stretching
       to fill the column, so the stack's total height can differ from
       the other panels. */
    .col-stack { display: flex; flex-direction: column; gap: 16px; height: 100%; }
    .col-stack .drc-card { height: auto; }
    .badge-sm {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: var(--lime);
        color: var(--teal);
        font-size: 12px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    /* Caps the AJAX-loaded leave-detail cards so selecting many
       employees scrolls within the card instead of stretching the
       whole page. */
    .drc-emp-detail-scroll {
        max-height: 320px;
        overflow-y: auto;
    }
    .drc-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--line-2);
    }
    .drc-step-badge {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: var(--teal);
        color: var(--lime);
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .drc-card-title {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--ink);
    }
    .drc-count-chip {
        margin-left: auto;
        background: var(--teal-3);
        color: var(--teal);
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }
    .drc-card-body { padding: 16px 18px 18px; flex: 1; }
    .drc-label {
        display: block;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--faint);
        margin-bottom: 8px;
    }
    .drc-divider { border-top: 1px solid var(--line-2); margin: 16px 0; }

    /* ---- Search inputs ---- */
    .drc-search {
        position: relative;
        margin-bottom: 12px;
    }
    .drc-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--faint);
        font-size: 12px;
    }
    .drc-search input {
        width: 100%;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 9px 12px 9px 32px;
        font-size: 13px;
        background: var(--bg);
        color: var(--ink);
    }
    .drc-search input:focus {
        outline: none;
        border-color: var(--teal);
        box-shadow: 0 0 0 3px var(--teal-3);
        background: #fff;
    }

    /* ---- Employee checkbox list ---- */
    .drc-emp-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--line-2);
        border-radius: 10px;
    }
    .drc-emp-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--line-2);
    }
    .drc-emp-row:last-child { border-bottom: none; }
    .drc-emp-row:hover { background: var(--teal-soft); }
    .drc-emp-row.selected { background: var(--teal-3); }
    .drc-emp-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 17px;
        height: 17px;
        border-radius: 5px;
        border: 1.5px solid var(--line);
        background: #fff;
        flex-shrink: 0;
        cursor: pointer;
        position: relative;
    }
    .drc-emp-checkbox:checked {
        background: var(--lime);
        border-color: var(--lime);
    }
    .drc-emp-checkbox:checked::after {
        content: '\2713';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -52%);
        font-size: 11px;
        font-weight: 700;
        color: var(--teal);
    }
    .drc-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--teal);
    }
    .drc-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .drc-avatar-0 { background: #014653; }
    .drc-avatar-1 { background: #6c4fd6; }
    .drc-avatar-2 { background: #c65b3f; }
    .drc-avatar-3 { background: #2e7d5b; }
    .drc-avatar-4 { background: #3f6fc6; }
    .drc-avatar-5 { background: #a8792f; }
    .drc-emp-info { display: flex; flex-direction: column; min-width: 0; }
    .drc-emp-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .drc-emp-position { font-size: 11px; color: var(--muted); }
    .drc-emp-code { font-size: 11px; color: var(--muted); }

    /* ---- Selected employee pills ---- */
    .drc-selected-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .drc-selected-pills:empty::before {
        content: 'No employees selected yet.';
        font-size: 12px;
        color: var(--faint);
        font-style: italic;
    }
    .drc-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--teal-3);
        border-radius: 20px;
        padding: 4px 8px 4px 4px;
        font-size: 12px;
        font-weight: 600;
        color: var(--teal);
    }
    .drc-pill .drc-avatar { width: 20px; height: 20px; }
    .drc-pill-remove {
        cursor: pointer;
        color: var(--teal);
        opacity: 0.6;
        font-size: 11px;
        border: none;
        background: none;
        padding: 0 2px;
    }
    .drc-pill-remove:hover { opacity: 1; }

    /* ---- Panel 2 legend ---- */
    .drc-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        font-size: 10.5px;
        color: var(--muted);
        padding-top: 14px;
        margin-top: 14px;
        border-top: 1px solid var(--line-2);
    }
    .drc-legend-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
        vertical-align: -1px;
    }
    .drc-legend-selected { background: var(--teal); }
    .drc-legend-inrange { background: #0045521a; border: 1px solid var(--teal); }
    .drc-legend-off { background: var(--off); }
    .drc-legend-leave { background: transparent; border: 1.5px solid var(--leave); }

    /* ---- Toggle switch (Set default shift time) ---- */
    .drc-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .drc-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
    .drc-toggle-track {
        width: 38px;
        height: 22px;
        border-radius: 20px;
        background: var(--line);
        position: relative;
        transition: background 0.15s ease;
        flex-shrink: 0;
    }
    .drc-toggle-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    .drc-toggle input:checked ~ .drc-toggle-track { background: var(--teal); }
    .drc-toggle input:checked ~ .drc-toggle-track .drc-toggle-thumb { transform: translateX(16px); }
    .drc-toggle-label { font-size: 13px; font-weight: 600; color: var(--ink); }

    /* ---- Read-only applied-range field ---- */
    .drc-readonly-field {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 9px 12px;
        background: var(--bg);
        color: var(--faint);
    }
    .drc-readonly-field input {
        border: none;
        background: transparent;
        padding: 0;
        color: var(--ink);
        font-size: 13px;
        font-weight: 600;
    }
    .drc-readonly-field input:disabled { color: var(--ink); -webkit-text-fill-color: var(--ink); opacity: 1; }

    .drc-overtime-link { color: var(--teal) !important; font-weight: 600; font-size: 13px; }

    /* ---- Day-off dropzone + chips ---- */
    .drc-dayoff-dropzone {
        border: 1.5px dashed var(--teal);
        border-radius: 10px;
        background: var(--teal-soft);
        padding: 14px;
        text-align: center;
        cursor: pointer;
        display: block;
    }
    .drc-dayoff-dropzone-title { display: block; font-size: 13px; font-weight: 600; color: var(--teal); }
    .drc-dayoff-dropzone-sub { display: block; font-size: 11px; color: var(--muted); margin-top: 2px; }
    .drc-dayoff-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .drc-dayoff-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fdece8;
        color: var(--off);
        border: 1px solid #f6c7bb;
        border-radius: 20px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
    }
    .drc-dayoff-chip button {
        border: none;
        background: none;
        color: inherit;
        opacity: 0.7;
        font-size: 10px;
        padding: 0;
        cursor: pointer;
    }
    .drc-dayoff-chip button:hover { opacity: 1; }

    /* ---- Geofence zone list ---- */
    .drc-zone-search input { width: 100%; }
    .drc-zone-list {
        max-height: 180px;
        overflow-y: auto;
        border: 1px solid var(--line-2);
        border-radius: 10px;
    }
    .drc-zone-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--line-2);
    }
    .drc-zone-row:last-child { border-bottom: none; }
    .drc-zone-row:hover { background: var(--teal-soft); }
    .drc-zone-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 1.5px solid var(--line);
        background: #fff;
        flex-shrink: 0;
        cursor: pointer;
        position: relative;
    }
    .drc-zone-checkbox:checked { background: var(--lime); border-color: var(--lime); }
    .drc-zone-checkbox:checked::after {
        content: '\2713';
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -52%);
        font-size: 10px;
        font-weight: 700;
        color: var(--teal);
    }
    .drc-zone-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .drc-zone-name { font-size: 12.5px; font-weight: 500; color: var(--ink); flex: 1; }
    .drc-zone-radius { font-size: 10.5px; color: var(--faint); }

    /* ---- Summary box ---- */
    .drc-summary-box {
        background: linear-gradient(180deg, var(--teal-3), var(--teal-soft));
        border-radius: 12px;
        padding: 14px 16px;
        margin-top: 4px;
    }
    .drc-summary-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid rgba(1,70,83,0.08);
    }
    .drc-summary-label { font-size: 12.5px; color: var(--ink); margin: 0; }
    .drc-summary-sublabel { font-size: 10px; color: var(--muted); display: block; }
    .drc-summary-value { font-size: 14px; font-weight: 700; color: var(--teal); margin: 0; }
    .drc-summary-final {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--teal);
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 10px;
    }
    .drc-summary-final .drc-summary-label { color: #fff; font-weight: 600; }
    .drc-summary-final h5 { color: var(--lime); font-size: 20px; font-weight: 800; margin: 0; }

    /* ---- Footer ---- */
    .drc-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 16px 4px 8px;
    }
    .drc-footer-summary { font-size: 12.5px; color: var(--muted); }
    .drc-submit-btn {
        background: var(--teal);
        color: var(--lime);
        border: none;
        border-radius: 10px;
        padding: 10px 28px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .drc-submit-btn:hover { background: var(--teal-2); }
    /* Disabled until an employee, a date range, and a shift are all set
       — day-off dates, overtime, and geofence zone stay optional. */
    .drc-submit-btn:disabled {
        background: var(--faint);
        color: #fff;
        cursor: not-allowed;
    }
    .drc-submit-btn:disabled:hover { background: var(--faint); }

    /* #Employee is still Select2-initialized elsewhere in this file
       (kept as-is, per "don't change existing JS calls") — Select2
       renders its own visible UI as a sibling of the (now d-none)
       original <select>, which d-none on the select itself doesn't
       reach. Hidden here rather than removing the .select2() call, so
       nothing about that existing init line has to change. Scoped to
       just #Employee's own container — other Select2 fields on this
       page (#Shift, #OvertimeEmployees, etc.) are untouched. */
    #Employee.select2-hidden-accessible + .select2-container { display: none !important; }

    /* ---- Selected-employee detail card (AJAX-loaded from
       resorts.renderfiles.dutyrosterandLeave, injected into
       .createduty-Append — these rules live here since the injected
       fragment has no <style> of its own). ---- */
    .drc-emp-detail-card {
        border: 1px solid var(--line-2);
        border-radius: 10px;
        padding: 12px;
        margin-top: 10px;
    }
    .drc-emp-detail-header { display: flex; align-items: center; gap: 10px; }
    .drc-emp-detail-info { flex: 1; min-width: 0; }
    .drc-emp-detail-name { font-size: 13px; font-weight: 700; color: var(--ink); margin: 0; }
    .drc-emp-code-badge {
        background: var(--line-2);
        color: var(--muted);
        font-size: 10px;
        font-weight: 600;
        padding: 1px 6px;
        border-radius: 8px;
        margin-left: 4px;
    }
    .drc-emp-detail-role { font-size: 11.5px; color: var(--muted); }
    .drc-emp-detail-remove {
        border: none;
        background: none;
        color: var(--faint);
        font-size: 12px;
        cursor: pointer;
        padding: 4px;
        flex-shrink: 0;
    }
    .drc-emp-detail-remove:hover { color: var(--off); }
    .drc-leave-box {
        border-radius: 8px;
        padding: 8px 10px;
        margin-top: 10px;
        font-size: 12px;
    }
    .drc-leave-box-none { background: var(--bg); color: var(--muted); }
    .drc-leave-box-amber {
        background: #fff6e5;
        border: 1px solid #ffe2ad;
        color: var(--leave);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 4px 10px;
    }
    .drc-leave-box-top { display: flex; align-items: center; gap: 8px; }
    .drc-leave-type { font-weight: 700; }
    .drc-leave-pending {
        background: rgba(217,138,0,0.15);
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 8px;
    }
    .drc-leave-dates { font-weight: 600; white-space: nowrap; }
    .drc-leave-reason { flex-basis: 100%; color: var(--muted); font-style: italic; }

    @media (max-width: 1080px) {
        .drc-panels > div { margin-bottom: 16px; }
    }

    /* ==================================================================
       Manage Overtime by Date modal — visual + interaction redesign
       only. Every existing id (#OvertimeEmployees, #saveOvertimeBtn,
       .overtime-emp-row, .overtime-date-input, .remove-overtime-emp),
       event handler, validation rule, and the calculateAllTotals()
       formula are untouched. The modal sits outside .drc-page in the
       DOM (a sibling, not a descendant — Bootstrap doesn't relocate
       modals), so the --teal/--lime/etc. custom properties defined
       there don't cascade here; literal brand hex values are used
       instead rather than redeclaring the palette on a second scope.
       ================================================================== */
    .drc-ot-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .drc-ot-header {
        background: linear-gradient(135deg, #035b6c, #014653);
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .drc-ot-title { color: #fff; font-size: 18px; font-weight: 700; margin: 0; }
    .drc-ot-close {
        border: none;
        background: rgba(255,255,255,0.1);
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-size: 20px;
        line-height: 1;
        flex-shrink: 0;
        cursor: pointer;
    }
    .drc-ot-close:hover { background: rgba(255,255,255,0.2); }
    /* One continuous grey surface for the whole body instead of white
       cards floating inside a grey frame — the emp-section and grid
       below span the full modal width themselves (no side padding here,
       no side radius on them), so there's no white/grey gutter beside
       the header or down the sides. */
    .drc-ot-body { padding: 16px 0; background: #f2f6f6; }
    .drc-ot-emp-section { background: #fff; padding: 14px 24px; margin-bottom: 12px; }
    .drc-ot-note { color: #5d6f75; font-size: 12px; font-weight: 400; margin: 8px 0 0; }
    .drc-ot-legend {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 0 24px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #5d6f75;
    }
    .drc-ot-legend-item { display: inline-flex; align-items: center; gap: 6px; }
    #OvertimeEmployees.select2-hidden-accessible { display: none !important; }
    .drc-ot-emp-section .select2-container .select2-selection--multiple {
        border: 1px solid #e2ebec;
        border-radius: 10px;
        min-height: 42px;
        padding: 4px;
    }
    /* Avatar → name → remove, remove pinned to the far right via flex
       order. Select2 4.1's choice markup is:
         <li class="select2-selection__choice">
           <button class="select2-selection__choice__remove">×</button>
           <span class="select2-selection__choice__display">{templateSelection output}</span>
         </li>
       — always in that DOM order regardless of what templateSelection
       returns — so order is set on __remove/__display (the actual flex
       children), not on the avatar/name spans nested a level deeper
       inside __display. inline-flex (not flex) keeps each choice
       shrink-to-fit and wrapping in a row like a normal chip, instead
       of stretching block-level to the full row width. */
    .drc-ot-emp-section .select2-selection__choice {
        display: inline-flex !important;
        align-items: center;
        background: #e6f0f1 !important;
        border: none !important;
        color: #014653 !important;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 20px !important;
        padding: 3px 10px 3px 4px !important;
        margin: 3px !important;
    }
    .drc-ot-emp-section .select2-selection__choice__display {
        order: 1;
        display: inline-flex;
        align-items: center;
    }
    .drc-ot-chip-avatar {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        display: inline-flex;
        background: #014653;
        margin-right: 6px;
    }
    .drc-ot-chip-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .drc-ot-emp-section .select2-selection__choice__remove {
        /* Select2's own default CSS absolutely-positions this button
           (top:0; left:0) as a top-left overlay — that takes it out of
           flex flow entirely, so `order` has no effect until position
           is put back to normal flow here. */
        position: static !important;
        order: 2;
        color: #014653 !important;
        border: none !important;
        background: none !important;
        margin: 0 0 0 8px !important;
        padding: 0 !important;
        font-size: 12.5px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
    }
    .drc-ot-emp-section .select2-selection--multiple:focus-within,
    .drc-ot-emp-section .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #014653;
        box-shadow: 0 0 0 3px #e6f0f1;
    }
    .drc-ot-grid-wrap {
        background: #fff;
        overflow-x: auto;
        max-height: 420px;
        overflow-y: auto;
    }
    .drc-ot-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 12.5px; }
    .drc-ot-table th, .drc-ot-table td { border-bottom: 1px solid #eef4f4; white-space: nowrap; }
    .drc-ot-emp-cell {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
        padding: 10px 14px 10px 24px;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 180px;
        box-shadow: 1px 0 0 #eef4f4;
    }
    .drc-ot-emp-head {
        display: table-cell;
        color: #93a4a9;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f8fafa;
    }
    .drc-ot-emp-name { font-weight: 600; color: #14232a; overflow: hidden; text-overflow: ellipsis; }
    .drc-ot-date-th {
        padding: 10px 8px;
        text-align: center;
        color: #5d6f75;
        font-size: 11px;
        font-weight: 700;
        background: #f8fafa;
        min-width: 78px;
    }
    .drc-ot-date-th small { display: block; font-weight: 500; color: #93a4a9; font-size: 10px; }
    .drc-ot-date-th-off { background: #fdece8; color: #e5573f; }
    .drc-ot-total-head, .drc-ot-action-head { background: #f8fafa; min-width: 70px; padding-right: 10px; }
    .drc-ot-action-head, .drc-ot-action-cell { padding-right: 20px; }
    .drc-ot-cell { padding: 5px; text-align: center; }
    .drc-ot-input {
        width: 76px;
        border: 1px solid #e2ebec;
        border-radius: 8px;
        padding: 6px 4px;
        text-align: center;
        font-size: 12.5px;
        color: #14232a;
        font-weight: 600;
    }
    .drc-ot-input:focus { outline: none; border-color: #014653; box-shadow: 0 0 0 3px #e6f0f1; }
    .drc-ot-locked { background: repeating-linear-gradient(135deg, #f8fafa, #f8fafa 4px, #eef4f4 4px, #eef4f4 8px); }
    .drc-ot-lock-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
    }
    .drc-ot-lock-off { background: #fdece8; color: #e5573f; }
    .drc-ot-lock-leave { background: #fff6e5; color: #d98a00; }
    .drc-ot-row-total-cell { text-align: center; background: #f8fafa; }
    .drc-ot-row-total { font-weight: 700; color: #014653; }
    .drc-ot-action-cell { text-align: center; }
    .drc-ot-remove-btn {
        border: none;
        background: none;
        color: #93a4a9;
        font-size: 12px;
        cursor: pointer;
        padding: 4px 8px;
    }
    .drc-ot-remove-btn:hover { color: #e5573f; }
    .drc-ot-footer {
        background: #f2f6f6;
        padding: 14px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-top: 1px solid #e2ebec;
    }
    .drc-ot-footer-summary { font-size: 12.5px; font-weight: 600; color: #5d6f75; }
    .drc-ot-footer-actions { display: flex; gap: 10px; }
    .drc-ot-btn-cancel {
        background: #fff;
        border: 1px solid #e2ebec;
        color: #5d6f75;
        border-radius: 10px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .drc-ot-btn-cancel:hover { background: #f2f6f6; }
    .drc-ot-btn-save {
        background: #014653;
        border: none;
        color: #e0ff02;
        border-radius: 10px;
        padding: 9px 22px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .drc-ot-btn-save:hover { background: #035b6c; }

    /* "Add Overtime" link dimmed when no roster employees are selected
       yet — the click handler already blocks opening with a toast in
       that case, this just previews that state visually. */
    .drc-ot-link-disabled { opacity: 0.45; cursor: not-allowed; }

    /* Post-save "Overtime by date" recap (#overtimeSummary, built by
       updateOvertimeSummary() — plain Bootstrap alert-info by default)
       restyled to match the brand instead of Bootstrap's default blue. */
    #overtimeSummary .alert-info {
        background: var(--teal-3);
        border: 1px solid var(--line);
        color: var(--ink);
        border-radius: 10px;
        font-size: 12.5px;
    }
    #overtimeSummary .alert-info strong { color: var(--teal); }

    /* ==================================================================
       Toastr notifications — restyled to match the brand instead of the
       plugin's default solid-color/base64-icon look. toastr.js itself
       (positioning, timing, show/hide, every toastr.success/.error/.info/
       .warning(...) call across this page) is completely untouched —
       these overrides only change how the same #toast-container markup
       is painted. Scoped by simply living in this page's own <style>
       block: a page's <style> tag only ever applies to that page's own
       document, so this can't leak onto other pages that also use
       toastr, without needing extra selector scoping. Literal hex
       values rather than this page's --teal/--ink/etc. custom
       properties, because toastr.js appends #toast-container straight
       to <body> — a sibling of .drc-page, not a descendant — so those
       custom properties don't cascade down to it (same reason the
       Manage Overtime modal's CSS above uses literal hex too). */
    #toast-container > div {
        background-image: none !important;
        background-color: #fff;
        color: #14232a;
        border-radius: 12px;
        padding: 14px 16px 14px 46px;
        width: 320px;
        box-shadow: 0 10px 28px rgba(1,70,83,0.16);
        opacity: 1;
        border-left: 4px solid #93a4a9;
        position: relative;
    }
    #toast-container > div:hover {
        box-shadow: 0 12px 32px rgba(1,70,83,0.22);
        opacity: 1;
    }
    #toast-container > div::before {
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        left: 16px;
        top: 15px;
        font-size: 15px;
    }
    .toast-success { border-left-color: #014653; }
    .toast-success::before { content: '\f058'; color: #014653; }
    .toast-error { border-left-color: #e5573f; }
    .toast-error::before { content: '\f057'; color: #e5573f; }
    .toast-warning { border-left-color: #d98a00; }
    .toast-warning::before { content: '\f071'; color: #d98a00; }
    .toast-info { border-left-color: #014653; }
    .toast-info::before { content: '\f05a'; color: #014653; }
    .toast-title { color: #14232a; font-weight: 700; font-size: 13px; margin-bottom: 2px; }
    .toast-message { color: #5d6f75; font-size: 12.5px; line-height: 1.4; }
    .toast-message a, .toast-message label { color: #014653; }
    .toast-close-button {
        color: #93a4a9;
        text-shadow: none;
        opacity: 1;
        font-size: 15px;
    }
    .toast-close-button:hover { color: #14232a; opacity: 1; }
    .toast-progress { background-color: #93a4a9; opacity: 0.3; }
</style>
@endsection

@section('import-scripts')

<script type="text/javascript">
    @php
        $overtimeRanks = config('settings.Position_Rank', []);
        $overtimeEligibleRanksArr = array_values(array_filter([
            array_search('SUP', $overtimeRanks),
            array_search('LINE WORKERS', $overtimeRanks),
        ], function ($v) { return $v !== false; }));
    @endphp
    // Overtime eligible ranks (SUP, LINE WORKERS) - from config
    var overtimeEligibleRanksConfig = @json($overtimeEligibleRanksArr);

    // tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });


    // Declare day off pickers at global scope so they're accessible to all functions
    var dayOffPicker = null;
    var dayOffPickerModel = null;

    // date range picker
    $(document).ready(function ()
    {
        // Day-off dates for the main roster form ('YYYY-MM-DD' strings) —
        // no longer a separate popup picker; these are toggled directly
        // on the Select Date Range calendar (#datapicker) by clicking an
        // in-range day. #DayOffDates stays a plain hidden field synced
        // from this array, purely so its existing name="DayOffDates"
        // required-field validation and form submission keep working
        // unchanged. Exposed on window since the pill/chip UI in the
        // separate script block further down the page needs to read and
        // update it too.
        window.dayOffDates = [];

        function setDayOffDates(dates) {
            window.dayOffDates = dates.slice().sort();
            $('#DayOffDates').val(window.dayOffDates.join(', ')).trigger('change');
            calculateAllTotals();
        }
        window.setDayOffDates = setDayOffDates;

        function toggleDayOffDate(dateStr) {
            var idx = window.dayOffDates.indexOf(dateStr);
            var next = window.dayOffDates.slice();
            if (idx === -1) {
                next.push(dateStr);
            } else {
                next.splice(idx, 1);
            }
            setDayOffDates(next);
            reapplyDayOffMarkers();
        }
        window.toggleDayOffDate = toggleDayOffDate;

        // The plugin's own <td> cells only carry a day-of-month number
        // and a "rXcX" grid-position attribute, not an actual date — so
        // the ISO date for a cell is derived from the
        // currently-displayed "Mon YYYY" header, adjusted a month either
        // way for the grayed-out lead/trail days (prevMonthDay /
        // nextMonthDay) the plugin renders at the edges of the grid when
        // a selected range crosses a month boundary.
        // Even with linkedCalendars:false, the plugin always renders two
        // .calendar-table elements (.drp-calendar.left, the one actually
        // shown, and .drp-calendar.right, kept in the DOM but
        // display:none) — every lookup below is scoped to .left only,
        // otherwise things like "is a prev button available" or a
        // month/next click can silently match the hidden table too.
        var DRC_VISIBLE_CALENDAR = '#datapicker .daterangepicker .drp-calendar.left .calendar-table';

        function getCalendarCellDate($td) {
            var day = parseInt($td.text(), 10);
            if (!day) return null;
            var monthText = $(DRC_VISIBLE_CALENDAR + ' th.month').first().text().trim();
            var parsed = moment(monthText, 'MMM YYYY');
            if (!parsed.isValid()) return null;
            var m = parsed.clone().startOf('month');
            if ($td.hasClass('prevMonthDay')) { m.subtract(1, 'month'); }
            else if ($td.hasClass('nextMonthDay')) { m.add(1, 'month'); }
            return m.format('YYYY-MM') + '-' + (day < 10 ? '0' + day : day);
        }

        // The daterangepicker plugin replaces .calendar-table's HTML on
        // every redraw (month navigation, applying a new range), which
        // wipes any classes added to its <td> cells directly — so
        // day-off markers are reapplied from the dayOffDates array
        // rather than relied on to persist by themselves.
        function reapplyDayOffMarkers() {
            $(DRC_VISIBLE_CALENDAR + ' td.in-range').each(function () {
                var $td = $(this);
                var cellDate = getCalendarCellDate($td);
                $td.toggleClass('drc-day-off', !!cellDate && window.dayOffDates.indexOf(cellDate) !== -1);
            });
        }
        window.reapplyDayOffMarkers = reapplyDayOffMarkers;

        // Clicking an in-range day on the Select Date Range calendar
        // toggles it as a day off instead of the plugin's own default
        // behaviour (which would start selecting a brand-new range).
        // The plugin reacts on mousedown, not click (bound internally as
        // `.on("mousedown.daterangepicker", "td.available", clickDate)`)
        // — so this listens for mousedown too, on #datapicker's capture
        // phase, so it runs (and, via stopPropagation(), can fully
        // cancel) before the event ever reaches the plugin's own
        // bubble-phase delegated handler on the <td> itself. Start/end
        // date cells and anything outside the current range are left
        // untouched, so picking a new range still works exactly as
        // before.
        document.getElementById('datapicker').addEventListener('mousedown', function (e) {
            var td = e.target.closest && e.target.closest('td.in-range');
            if (!td || td.classList.contains('off') || td.classList.contains('disabled')) return;
            var picker = $('#hiddenInput').data('daterangepicker');
            if (!picker || !picker.startDate || !picker.endDate) return;
            var cellDate = getCalendarCellDate($(td));
            if (!cellDate) return;
            e.preventDefault();
            e.stopPropagation();
            toggleDayOffDate(cellDate);
        }, true);

        // Mirrors the plugin's own auto-rendered month label/prev-next
        // state onto the custom .cal-top header (the plugin's own
        // month-header row is hidden via CSS — see thead tr:first-child
        // above). Doesn't touch the plugin's navigation logic at all,
        // just reads what it already rendered.
        function syncCalTopHeader() {
            var monthText = $(DRC_VISIBLE_CALENDAR + ' th.month').first().text().trim();
            if (monthText) { $('#drcCalMonthLabel').text(monthText); }
            var prevAvailable = $(DRC_VISIBLE_CALENDAR + ' th.prev').length > 0;
            $('#drcCalPrev').prop('disabled', !prevAvailable);
        }
        window.syncCalTopHeader = syncCalTopHeader;

        // Forward clicks on the custom nav buttons to the plugin's own
        // (now hidden) prev/next cells — same click event the plugin
        // itself binds internally (`.on("click.daterangepicker", ".prev"/
        // ".next", ...)`; date cells are the ones bound on mousedown, not
        // these), so its existing month-change/re-render logic runs
        // completely unchanged. Scoped to the visible calendar only —
        // the hidden second .calendar-table the plugin also keeps in
        // the DOM (see DRC_VISIBLE_CALENDAR above) has its own "next
        // available" cell too, and triggering both at once double-
        // advanced the month.
        $(document).on('click', '#drcCalPrev', function () {
            var $prev = $(DRC_VISIBLE_CALENDAR + ' th.prev');
            if ($prev.length) { $prev.trigger('click'); }
        });
        $(document).on('click', '#drcCalNext', function () {
            $(DRC_VISIBLE_CALENDAR + ' th.next').trigger('click');
        });

        // The plugin swaps in a fresh .calendar-table on month
        // navigation, which would otherwise silently drop the day-off
        // highlighting applied above until the next toggle — and would
        // leave .cal-top showing a stale month/prev-state too.
        new MutationObserver(function () {
            if (typeof window.reapplyDayOffMarkers === 'function') { window.reapplyDayOffMarkers(); }
            syncCalTopHeader();
        }).observe(document.getElementById('datapicker'), { childList: true, subtree: true });

        // Submit stays disabled until the three required steps are all
        // done — at least one employee, a date range, and a shift. Day
        // off, overtime, and the geofence zone remain optional, so
        // they're deliberately not checked here. Exposed on window
        // since it's also called from #Shift's change handler further
        // down this same script, and from updateFooterSummary() in the
        // separate script block further down the page (fires on every
        // employee-selection change).
        function updateSubmitButtonState() {
            var hasEmployee = ($('#Employee').val() || []).length > 0;
            var hasDateRange = !!$('#hiddenInput').val();
            var hasShift = !!$('#Shift').val();
            $('.drc-submit-btn').prop('disabled', !(hasEmployee && hasDateRange && hasShift));
        }
        window.updateSubmitButtonState = updateSubmitButtonState;
        updateSubmitButtonState();

        document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Geofence zone search filter
        $(document).on('keyup', '#gfZoneSearch', function() {
            var search = $(this).val().toLowerCase();
            $('.gf-zone-item').each(function() {
                var name = $(this).data('name');
                $(this).toggle(name.indexOf(search) > -1);
            });
        });

        // Overtime inputs are now handled in the modal, so this is removed
        var shiftOverTimePicker =  flatpickr(".shiftdate", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i", // 12-hour format without AM/PM
            time_24hr: false,  // Ensures 12-hour format
            minuteIncrement: 1, // Allows 1-minute steps

        });
        $('#Employee').select2({
            placeholder: "Select Employees", // Placeholder text
            allowClear: true, // Adds a clear (X) button to reset the dropdown
            multiple: true // Enable multiple selection
        });

        $('#OvertimeEmployees').select2({
            placeholder: "Select Employees for Overtime",
            allowClear: true,
            multiple: true,
            // Renders each selected chip as avatar + name (the remove
            // "x" Select2 always adds is repositioned to the end purely
            // via CSS order — see .drc-ot-emp-section .select2-selection__choice
            // rules — so this template only needs to supply the avatar/name).
            escapeMarkup: function (markup) { return markup; },
            templateSelection: function (data) {
                if (!data.id) { return data.text; }
                var avatarUrl = $(data.element).data('avatar') || '';
                var name = $('<div>').text(data.text).html();
                var avatarHtml = avatarUrl
                    ? '<img src="' + avatarUrl + '" alt="">'
                    : '';
                return '<span class="drc-ot-chip-avatar">' + avatarHtml + '</span><span class="drc-ot-chip-name">' + name + '</span>';
            }
        });
        $('#Shift').select2({
            placeholder: "Select a Shift", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });

        $('#Position').select2({
            placeholder: "Select a Position", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });
        $('#Shiftpopup').select2({
            placeholder: "Select a Shift", // Placeholder text
            allowClear: true // Adds a clear (X) button to reset the dropdown
        });

        $('.data-Table').dataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": false,
            "bAutoWidth": false,
            scrollX: true,
            "iDisplayLength": 10,
        });
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });

        // Dates that selected employee(s) already have duty roster - disable in calendar (per employee, not resort)
        var rosterOccupiedDates = [];

        function initDateRangePicker(occupiedDates) {
            rosterOccupiedDates = occupiedDates || [];
            var existingPicker = $("#hiddenInput").data('daterangepicker');
            var today = moment().startOf('day');
            var startDate = moment();
            var endDate = moment().add(7, 'days');
            if (existingPicker) {
                startDate = existingPicker.startDate.clone();
                endDate = existingPicker.endDate.clone();
                try { existingPicker.remove(); } catch (e) {}
                $("#hiddenInput").data('daterangepicker', null);
            }
            $("#hiddenInput").daterangepicker({
                autoApply: true,
                // The library's own updateElement() unconditionally does
                // this.endDate.format(...) whenever autoUpdateInput is true
                // (the default) — but clickDate() explicitly nulls endDate
                // right before calling setStartDate() every time a fresh
                // range selection starts (i.e. clicking a date again after
                // a complete range was already picked), which crashes on
                // that null .format() and freezes the calendar. We already
                // sync the visible text ourselves via updateDateText()
                // below, so the library never needs to touch #hiddenInput's
                // value at all.
                autoUpdateInput: false,
                startDate: startDate,
                endDate: endDate,
                minDate: today,
                opens: 'right',
                parentEl: '#datapicker',
                alwaysShowCalendars: true,
                linkedCalendars: false,
                isInvalidDate: function(date) {
                    var dateStr = date.format('YYYY-MM-DD');
                     // ✅ Disable past dates
                    if (date.isBefore(today)) {
                        return true;
                    }
                     // ✅ Disable occupied dates
                    if (rosterOccupiedDates.indexOf(dateStr) !== -1) {
                        return true;
                    }
                    // return rosterOccupiedDates.indexOf(dateStr) !== -1;
                     return false;
                }
            }, function (start, end) {
                updateDateText(start, end);
            });
            $("#hiddenInput").data('daterangepicker').show();
            updateDateText(startDate, endDate);
            // Global message hidden; unavailable dates are shown below each employee block
            $("#rosterDisabledDatesList").text('');
            $("#rosterDisabledDatesMsg").addClass("d-none");
        }
        window.initDateRangePicker = initDateRangePicker;

        initDateRangePicker([]);
            function updateDateText(start, end) {
                let startDate = start.format("YYYY-MM-DD").toString();
                let endDate = end.format("YYYY-MM-DD").toString();

                let startDate1 = start.format("DD MMM").toString();
                let endDate1 = end.format("DD MMM").toString();

                $("#MakeShift").val(startDate1 + " - " + endDate1);

                // autoUpdateInput:false (see comment above) stops the
                // library from ever writing #hiddenInput itself, but
                // getDateRange()/calculateAllTotals() all read the
                // selected range from #hiddenInput in
                // "MM/DD/YYYY - MM/DD/YYYY" format — so it has to be set
                // here too, or every one of those reads an empty value.
                $("#hiddenInput").val(start.format("MM/DD/YYYY") + " - " + end.format("MM/DD/YYYY"));

                let dayCount = end.diff(start, 'days') + 1;
                $("#dutyRosterDayCountBadge").text(dayCount + (dayCount === 1 ? ' day' : ' days')).removeClass('d-none');
                if (typeof window.updateFooterSummary === 'function') { window.updateFooterSummary(); }
                updateSubmitButtonState();

                // Drop any previously-marked day-off dates that no longer
                // fall inside the newly selected range, then refresh the
                // calendar's day-off highlighting and the chips list.
                setDayOffDates(window.dayOffDates.filter(function (d) {
                    return d >= startDate && d <= endDate;
                }));
                setTimeout(reapplyDayOffMarkers, 0);

                // Recalculate totals when date changes
                calculateAllTotals();



                let enabledDates = [];
                let startDate12 = new Date(startDate);
                let endDate12 = new Date(endDate);

                while (startDate12 <= endDate12) {
                        enabledDates.push(startDate12.toISOString().split('T')[0]);  // Store the formatted date (YYYY-MM-DD)

                        // Increment the date by one day
                        startDate12.setDate(startDate12.getDate() + 1);
                    }
             $('#MakeShift').datepicker('destroy');  // Destroy the previous datepicker instance

            // Initialize DatePicker with enabled dates
                $('#MakeShift').datepicker({

                    beforeShowDay: function (date) {
                        const formattedDate = date.toISOString().split('T')[0];
                        return enabledDates.includes(formattedDate);
                    }
                });

            }
        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            picker.show();
            // Recalculate totals when date range changes
            calculateAllTotals();
        });

        // Initialize day off picker for modal
    });
        </script>
        @include('resorts.renderfiles.dutyRosterSharedScripts')
        <script type="text/javascript">

        // Initialize model picker on load (main picker is initialized in updateDateText)
        initializeDayOffPickerModel();

        // Ensure day off picker model opens on click
        $(document).on('click', '#DayOffDatesModel', function() {
            if(dayOffPickerModel && dayOffPickerModel.isOpen !== true) {
                dayOffPickerModel.open();
            }
        });

        $('#DutyRosterForm').validate({
            rules: {
                'Emp_id[]': {
                    required: true,
                },
                MakeShift:{
                    required: true,
                },
                Shift:{
                    required: true,
                }
            },
            messages: {
                'Emp_id[]': {
                    required: "Please Select at least one Employee.",
                },
                MakeShift:{
                    required:"Please select Shift Date.",
                },
                Shift:{
                    required: "Please select Shift .",
                }
            },
            errorPlacement: function(error, element) {

                if (element.is(':radio') || element.is(':checkbox')) {
                    error.insertAfter(element.closest('div'));
                } else {
                    var nextElement = element.next('span');
                    if (nextElement.length > 0) {
                        error.insertAfter(nextElement);
                    } else {
                        error.insertAfter(element);
                    }
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form); // Use FormData to handle file inputs

                // Add overtime data as JSON (includes days for each employee)
                // Ensure employee IDs are strings for consistency
                let overtimeDataToSend = {};
                for(let empId in employeeOvertimeData) {
                    overtimeDataToSend[String(empId)] = employeeOvertimeData[empId];
                }

                formData.append('employeeOvertime', JSON.stringify(overtimeDataToSend));

                // Add final total hours
                formData.append('FinalTotalHours', $('#FinalTotalHoursInput').val());

                $.ajax({
                    url: "{{ route('resort.timeandattendance.StoreDutyRoster') }}", // Ensure route is correct
                    type: "POST",
                    data: formData,
                    contentType: false,  // Required for file uploads
                    processData: false,  // Required for file uploads
                    success: function(response) {
                        if (response.success) {


                            $('#sendReminder-modal').modal('hide');
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(function() {
                                window.location.href = "{{ route('resort.timeandattendance.ViewDutyRoster') }}";


                                // window.location.reload();
                            }, 3000);
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 422)
                        {
                            var responseData = xhr.responseJSON || {};
                            var errors = responseData.errors || {};
                            var errs = '';

                            if (typeof errors === 'object' && Object.keys(errors).length > 0) {
                                $.each(errors, function (field, messages) {
                                    if (Array.isArray(messages)) {
                                        $.each(messages, function (index, message) {
                                            errs += message + '<br>'; // Append each message
                                        });
                                    } else if (typeof messages === 'string') {
                                        errs += messages + '<br>';
                                    }
                                });
                            }
                            
                            // If no errors found in errors object, check for message
                            if (!errs && responseData.message) {
                                errs = responseData.message;
                            }
                            
                            // If still no error message, show generic message with details
                            if (!errs) {
                                errs = 'Validation failed. Please check your input.';
                                console.error('No error messages found in response:', responseData);
                            }
                            
                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                        else
                        {
                                toastr.error("An unexpected error occurred.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    }
                });
            }
        });

        $(document).on("click", ".editdutyRoster", function() {

            let date = $(this).attr('data-date');
            let Shift_id = $(this).attr('data-Shift_id');
            let overtime = $(this).attr('data-OverTime');
            let Attd_id = $(this).attr('data-Attd_id');
            let DayWiseTotalHours = $(this).attr('data-DayWiseTotalHours');
            $("#shiftdate").val(date);
            $("#Shiftpopup").val(Shift_id).trigger('change');
            $("#ShiftOverTime").val(overtime);

            // Handle day off dates - convert from old format if needed
            let DayOffDates = $(this).attr('data-DayOffDates') || '';
            if(dayOffPickerModel) {
                if(DayOffDates) {
                    // If it's a comma-separated list of dates
                    let datesArray = DayOffDates.split(',').map(d => d.trim());
                    dayOffPickerModel.setDate(datesArray, false);
                } else {
                    dayOffPickerModel.clear();
                }
            }
            $("#Attd_id").val(Attd_id);

            if (!$("#ShiftOverTime").data("flatpickr")) {
                flatpickr("#ShiftOverTime", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "h:i", // 12-hour format without AM/PM
                    time_24hr: false,  // Ensures 12-hour format
                    minuteIncrement: 1, // Allows 1-minute steps
                });
            }
            $("#ShiftOverTime").val(overtime);

            $("#ShiftOverTime")[0]._flatpickr.setDate(overtime, false);
            $("#editdutyRoster-modal").modal('show');
            $("#ShiftOverTime").attr('data-DayWiseTotalHours', DayWiseTotalHours);
            calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");

        });

        $('#UpdateDutyRoster').validate({
                rules: {
                    shiftdate: {
                        required: true,
                    },
                    Shiftpopup: {
                        required: true,
                    }
                   ,
                    DayOffDatesModel: {
                        required: true,
                    }
                },
                messages :
                {
                    shiftdate: {
                        required: "Please Add Shift time",
                    },
                    Shiftpopup: {
                        required: "Please Select Shift ",
                    }
                    ,
                    DayOffDatesModel: {
                        required: "Please Select Day Off dates",
                    }
                },
                errorPlacement: function(error, element) {

                    if (element.is(':radio') || element.is(':checkbox')) {
                        error.insertAfter(element.closest('div'));
                    } else {
                        var nextElement = element.next('span');
                        if (nextElement.length > 0) {
                            error.insertAfter(nextElement);
                        } else {
                            error.insertAfter(element);
                        }
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.UpdateDutyRoster') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-rejectModal').modal('hide');
                            if (response.success)
                            {

                                toastr.success(response.message, "Success",
                                        {
                                            positionClass: 'toast-bottom-right'
                                        });
                                $("#editdutyRoster-modal").modal('hide')
                                setTimeout(function() {
                                    window.location.reload();
                                }, 3000);
                            }
                            else
                            {
                                toastr.error(response.message,"error", { positionClass: 'toast-bottom-right'});

                            }

                    },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right'});
                        }
                    });
                }
        });
    // Overtime by date: { empId: { hoursByDate: { 'Y-m-d': 'HH:MM', ... } } }
    let employeeOvertimeData = {};
    let availableDates = []; // Store available dates from date range

    // Function to get all dates in the selected range
    function getDateRange() {
        let hiddenInput = $("#hiddenInput").val();
        if(!hiddenInput) return [];

        let hiddenInputArray = hiddenInput.split(' - ');
        let startDateStr = hiddenInputArray[0].trim();
        let endDateStr = hiddenInputArray[1].trim();

        // Parse dates - format is m/d/Y
        let startParts = startDateStr.split('/');
        let endParts = endDateStr.split('/');
        let startDate = new Date(parseInt(startParts[2]), parseInt(startParts[0]) - 1, parseInt(startParts[1]));
        let endDate = new Date(parseInt(endParts[2]), parseInt(endParts[0]) - 1, parseInt(endParts[1]));

        let dates = [];
        let currentDate = new Date(startDate);
        while(currentDate <= endDate) {
            dates.push(new Date(currentDate));
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return dates;
    }

    // Reuses the same avatar already rendered for this employee in the
    // Panel 1 checkbox list (real photo via Common::getResortUserPicture,
    // falling back to the initials-style colored circle) rather than
    // fetching it again — same lookup technique renderSelectedPills()
    // uses in the other script block.
    function getEmpAvatarHtml(empId) {
        var $row = $('.drc-emp-row').filter(function () {
            return $(this).find('.drc-emp-checkbox').val() === String(empId);
        });
        return $row.find('.drc-avatar').length ? $row.find('.drc-avatar').prop('outerHTML') : '<span class="drc-avatar"></span>';
    }

    // Same lookup as getEmpAvatarHtml(), but just the photo URL — used
    // for the #OvertimeEmployees Select2 chip template, which needs a
    // plain <img src> rather than the full .drc-avatar wrapper markup.
    function getEmpAvatarUrl(empId) {
        var $row = $('.drc-emp-row').filter(function () {
            return $(this).find('.drc-emp-checkbox').val() === String(empId);
        });
        var $img = $row.find('.drc-avatar img');
        return $img.length ? $img.attr('src') : '';
    }

    function normalizeOvertime(val) {
        if(!val || val === '00:00') return '00:00';
        var parts = String(val).split(':');
        if(parts.length >= 2) {
            var h = parseInt(parts[0], 10) || 0, m = parseInt(parts[1], 10) || 0;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
        }
        return '00:00';
    }

    $('.addOvertime-modal').on('click', function() {
        let makeshiftdate = $("#MakeShift").val();
        if(makeshiftdate=="")
        {
            toastr.error("Please Select Calendar", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        let selectedEmployees = $('#Employee').val();
        if(!selectedEmployees || selectedEmployees.length === 0) {
            toastr.error("Please Select Employees First", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        // Get available dates
        availableDates = getDateRange();
        if(availableDates.length === 0) {
            toastr.error("Please Select Date Range", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return false;
        }

        // Overtime is only for SUP and LINE WORKERS (from config)
        let overtimeEligibleRanks = (typeof overtimeEligibleRanksConfig !== 'undefined' && Array.isArray(overtimeEligibleRanksConfig)) ? overtimeEligibleRanksConfig : [5, 6];

        // Populate overtime employees dropdown with selected employees who are eligible (rank 5 or 6)
        let employeeOptions = '';
        let employeeData = {};
        let selectedEmployeeIds = [];
        let skippedIneligible = 0;

        $('#Employee option:selected').each(function() {
            let empId = $(this).val();
            let empName = $(this).text();
            let rank = parseInt($(this).data('rank'), 10);
            if (overtimeEligibleRanks.indexOf(rank) === -1) {
                skippedIneligible++;
                return;
            }
            employeeOptions += '<option value="' + empId + '" data-avatar="' + getEmpAvatarUrl(empId) + '">' + empName + '</option>';
            selectedEmployeeIds.push(empId);

            var hoursByDate = {};
            availableDates.forEach(function(d) {
                var dateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                if (employeeOvertimeData[empId] && employeeOvertimeData[empId].hoursByDate && employeeOvertimeData[empId].hoursByDate[dateStr] !== undefined) {
                    hoursByDate[dateStr] = employeeOvertimeData[empId].hoursByDate[dateStr];
                } else if (employeeOvertimeData[empId] && employeeOvertimeData[empId].days && employeeOvertimeData[empId].days.indexOf(dateStr) !== -1 && employeeOvertimeData[empId].overtime) {
                    hoursByDate[dateStr] = employeeOvertimeData[empId].overtime;
                } else {
                    hoursByDate[dateStr] = '00:00';
                }
            });
            employeeData[empId] = { name: empName, avatar: getEmpAvatarHtml(empId), hoursByDate: hoursByDate };
        });

        if (skippedIneligible > 0 || selectedEmployeeIds.length === 0) {
            toastr.warning("Overtime is applicable only for Line Workers and Supervisor roles. The selected employee(s) are not eligible.", "Notice", {
                positionClass: 'toast-bottom-right'
            });
            if (selectedEmployeeIds.length === 0) {
                return;
            }
        }

        // Set the dropdown options
        $('#OvertimeEmployees').html(employeeOptions);

        // Pre-select all employees in the dropdown (without triggering change to avoid filtering)
        $('#OvertimeEmployees').val(selectedEmployeeIds);

        // Manually update select2 to show selected values
        $('#OvertimeEmployees').trigger('change.select2');

        // Populate existing overtime data for all selected employees immediately
        updateOvertimeEmployeesList(employeeData);

        $('#addOvertimeModal').modal('show');
    });

    function minutesToHHMM(mins) {
        var h = Math.floor(mins / 60), m = mins % 60;
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    function otToMinutes(val) {
        if (!val || val === '00:00') return 0;
        var parts = String(val).split(':');
        return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
    }

    // Live per-row + grand OT totals shown in the modal footer, purely a
    // display aid — the committed totals used by calculateAllTotals()
    // (main page summary) are unaffected and only update on Save, same
    // as before.
    function updateOvertimeModalTotals() {
        var grandMinutes = 0;
        var empCount = $('.overtime-emp-row').length;
        $('.overtime-emp-row').each(function() {
            var rowMinutes = 0;
            $(this).find('.overtime-date-input').each(function() {
                rowMinutes += otToMinutes($(this).val());
            });
            grandMinutes += rowMinutes;
            $(this).find('.drc-ot-row-total').text(minutesToHHMM(rowMinutes));
        });
        $('#drcOvertimeFooterSummary').text(
            empCount + (empCount === 1 ? ' employee' : ' employees') + ' · Total OT ' + minutesToHHMM(grandMinutes)
        );
    }

    // Build modal: table with one row per employee, one column per date
    // with an OT input — day-off dates (roster-wide) and per-employee
    // leave dates are rendered as locked, non-editable cells instead, so
    // they're excluded from both entry and the totals above.
    function updateOvertimeEmployeesList(employeeData) {
        var dateStrings = [];
        availableDates.forEach(function(d) {
            dateStrings.push(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'));
        });
        var offDates = window.dayOffDates || [];

        var html = '<table class="drc-ot-table"><thead><tr><th class="drc-ot-emp-cell drc-ot-emp-head">Employee</th>';
        dateStrings.forEach(function(dateStr) {
            var d = new Date(dateStr + 'T12:00:00');
            var label = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' }) + '<small>' + d.toLocaleDateString('en-US', { weekday: 'short' }) + '</small>';
            var offCls = offDates.indexOf(dateStr) !== -1 ? ' drc-ot-date-th-off' : '';
            html += '<th class="drc-ot-date-th' + offCls + '">' + label + '</th>';
        });
        html += '<th class="drc-ot-total-head">Total</th><th class="drc-ot-action-head"></th></tr></thead><tbody>';

        for (var empId in employeeData) {
            var name = employeeData[empId].name;
            var avatar = employeeData[empId].avatar || '';
            var hoursByDate = employeeData[empId].hoursByDate || {};
            html += '<tr class="overtime-emp-row" data-emp-id="' + empId + '">';
            html += '<td class="drc-ot-emp-cell">' + avatar + '<span class="drc-ot-emp-name">' + name + '</span></td>';
            dateStrings.forEach(function(dateStr) {
                var val = hoursByDate[dateStr] !== undefined ? hoursByDate[dateStr] : '00:00';
                var isOff = offDates.indexOf(dateStr) !== -1;
                var isLeave = !isOff && isDateOnLeave(empId, dateStr);
                if (isOff || isLeave) {
                    var markerCls = isOff ? 'drc-ot-lock-off' : 'drc-ot-lock-leave';
                    var markerLetter = isOff ? 'O' : 'L';
                    var title = isOff ? 'Day off' : 'On leave';
                    html += '<td class="drc-ot-cell drc-ot-locked" title="' + title + '"><span class="drc-ot-lock-marker ' + markerCls + '">' + markerLetter + '</span></td>';
                } else {
                    html += '<td class="drc-ot-cell"><input type="text" class="drc-ot-input overtime-date-input" data-emp-id="' + empId + '" data-date="' + dateStr + '" value="' + val + '" placeholder="00:00" maxlength="5"></td>';
                }
            });
            html += '<td class="drc-ot-row-total-cell"><span class="drc-ot-row-total" data-emp-id="' + empId + '">00:00</span></td>';
            html += '<td class="drc-ot-action-cell"><button type="button" class="drc-ot-remove-btn remove-overtime-emp" data-emp-id="' + empId + '" aria-label="Remove"><i class="fa fa-times"></i></button></td>';
            html += '</tr>';
        }
        html += '</tbody></table>';
        $('#overtimeEmployeesList').html(html);

        $('.overtime-date-input').each(function() {
            var $input = $(this);
            var currentValue = $input.val() || '00:00';
            if ($input.data("flatpickr")) {
                try { $input[0]._flatpickr.destroy(); } catch(e) {}
            }
            var fp = flatpickr(this, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 5,
                defaultDate: currentValue,
                onChange: function() { setTimeout(function() { calculateAllTotals(); updateOvertimeModalTotals(); }, 100); }
            });
            if (currentValue && currentValue !== '00:00') {
                try {
                    var parts = currentValue.split(':');
                    var d = new Date();
                    d.setHours(parseInt(parts[0], 10) || 0, parseInt(parts[1], 10) || 0, 0, 0);
                    fp.setDate(d, false);
                } catch(e) {}
            }
        });

        updateOvertimeModalTotals();
    }

    $(document).on('change', '#OvertimeEmployees', function() {
        var selected = $(this).val() || [];
        var currentData = {};
        $('.overtime-emp-row').each(function() {
            var empId = $(this).data('emp-id');
            if (!selected.includes(String(empId))) return;
            var hoursByDate = {};
            $(this).find('.overtime-date-input').each(function() {
                var dateStr = $(this).data('date');
                var v = $(this).val();
                if ($(this).data('flatpickr') && $(this)[0]._flatpickr && $(this)[0]._flatpickr.selectedDates.length) {
                    var d = $(this)[0]._flatpickr.selectedDates[0];
                    v = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
                }
                hoursByDate[dateStr] = normalizeOvertime(v);
            });
            currentData[empId] = { name: $('#OvertimeEmployees option[value="' + empId + '"]').text(), avatar: getEmpAvatarHtml(empId), hoursByDate: hoursByDate };
        });
        selected.forEach(function(empId) {
            if (!currentData[empId]) {
                var hoursByDate = {};
                availableDates.forEach(function(d) {
                    var dateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    hoursByDate[dateStr] = (employeeOvertimeData[empId] && employeeOvertimeData[empId].hoursByDate && employeeOvertimeData[empId].hoursByDate[dateStr]) ? employeeOvertimeData[empId].hoursByDate[dateStr] : '00:00';
                });
                currentData[empId] = { name: $('#OvertimeEmployees option[value="' + empId + '"]').text(), avatar: getEmpAvatarHtml(empId), hoursByDate: hoursByDate };
            }
        });
        updateOvertimeEmployeesList(currentData);
    });

    $(document).on('click', '.remove-overtime-emp', function() {
        var empId = $(this).data('emp-id');
        $('#OvertimeEmployees option[value="' + empId + '"]').prop('selected', false);
        delete employeeOvertimeData[empId];
        $(this).closest('.overtime-emp-row').remove();
        $('#OvertimeEmployees').trigger('change');
        calculateAllTotals();
    });

    $('#saveOvertimeBtn').on('click', function() {
        var selectedEmployees = ($('#OvertimeEmployees').val() || []).map(function(id) { return String(id); });
        employeeOvertimeData = {};

        $('.overtime-emp-row').each(function() {
            var empId = String($(this).data('emp-id'));
            if (!selectedEmployees.includes(empId)) return;
            var hoursByDate = {};
            $(this).find('.overtime-date-input').each(function() {
                var dateStr = $(this).data('date');
                var v = $(this).val() || '00:00';
                if ($(this).data('flatpickr') && $(this)[0]._flatpickr && $(this)[0]._flatpickr.selectedDates.length > 0) {
                    var d = $(this)[0]._flatpickr.selectedDates[0];
                    v = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
                }
                hoursByDate[dateStr] = normalizeOvertime(v);
            });
            employeeOvertimeData[empId] = { hoursByDate: hoursByDate };
        });

        updateOvertimeSummary();
        calculateAllTotals();
        $('#addOvertimeModal').modal('hide');
        toastr.success("Overtime by date saved successfully", "Success", { positionClass: 'toast-bottom-right' });
    });

    function updateOvertimeSummary() {
        var summary = '';
        if (Object.keys(employeeOvertimeData).length > 0) {
            summary = '<div class="alert alert-info"><strong>Overtime by date:</strong><ul class="mb-0">';
            for (var empId in employeeOvertimeData) {
                var empName = $('#Employee option[value="' + empId + '"]').text();
                var hoursByDate = employeeOvertimeData[empId].hoursByDate || {};
                var daysWithOT = Object.keys(hoursByDate).filter(function(d) {
                    var v = hoursByDate[d];
                    return v && v !== '00:00' && v !== '';
                });
                summary += '<li>' + empName + ': ' + (daysWithOT.length ? daysWithOT.length + ' day(s) with OT' : 'No OT') + '</li>';
            }
            summary += '</ul></div>';
        }
        $('#overtimeSummary').html(summary);
    }
    // Removed - overtime is now handled in modal
    $(document).on("change", "#ShiftOverTime", function() {
        let overtime = $(this).val(); // Get the overtime time (HH:MM)
        let DayWiseTotalHours=$(this).attr('data-DayWiseTotalHours');
        calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");
    });
    $(document).on("change", "#Shiftpopup", function() {
        let overtime = "00:00";

        let DayWiseTotalHours= $("#Shiftpopup").find(":selected").data('totalhrs') || "00:00";
        let flag="Modal";
        calculateTotalTime(overtime,DayWiseTotalHours,flag);
    });
    $(document).on("change", "#Shift", function() {
        calculateAllTotals();
        if (typeof window.updateSubmitButtonState === 'function') { window.updateSubmitButtonState(); }
    });

    // Track which employees' leave info has been loaded
    let loadedEmployeeLeaves = {};
    // Per-employee roster occupied dates (from RosterOccupiedDates API)
    let rosterDatesByEmployee = {};
    // Per-employee leave date ranges (from DutyRosterandLeave API), used to
    // lock the matching cells in the Manage Overtime modal — { empId: [{from, to, status}, ...] }
    let leaveDatesByEmployee = {};

    // True if dateStr ('Y-m-d') falls within any of an employee's leave ranges.
    function isDateOnLeave(empId, dateStr) {
        var ranges = leaveDatesByEmployee[String(empId)] || [];
        for (var i = 0; i < ranges.length; i++) {
            if (dateStr >= ranges[i].from && dateStr <= ranges[i].to) return true;
        }
        return false;
    }

    function formatRosterDateStr(ymd) {
        var p = (ymd + '').split('-');
        if (p.length !== 3) return ymd;
        var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var d = parseInt(p[2], 10);
        var m = monthNames[parseInt(p[1], 10) - 1] || p[1];
        var y = p[0];
        return d + ' ' + m + ' ' + y;
    }

    function injectRosterUnavailableMessages(empIds) {
        if (!empIds || !empIds.length) return;
        var maxShow = 4;
        empIds.forEach(function(empId) {
            var sid = String(empId);
            var block = $(".createduty-Append").find('[data-emp-leave-id="' + sid + '"]');
            block.nextAll('.roster-unavailable-msg').first().remove();
            var dates = rosterDatesByEmployee[sid];
            if (dates && dates.length > 0) {
                var shown = dates.slice(0, maxShow).map(formatRosterDateStr);
                var rest = dates.length - maxShow;
                var listText = 'These dates are unavailable (' + shown.join(', ');
                if (rest > 0) listText += ' and ' + rest + ' more';
                listText += '): This employee already has scheduled shifts on these days.';
                var msgEl = $('<p class="small text-muted mt-2 mb-0 roster-unavailable-msg" role="alert"><i class="fa fa-info-circle me-1"></i>' + listText + '</p>');
                block.after(msgEl);
            }
        });
    }

    $(document).on("change", "#Employee", function() {
        // Clear overtime data when employees change
        employeeOvertimeData = {};
        updateOvertimeSummary();
        calculateAllTotals();

        // Update employee info display
        let selectedIds = $(this).val() || [];
        $('.drc-overtime-link').toggleClass('drc-ot-link-disabled', selectedIds.length === 0);
        let currentLoadedIds = Object.keys(loadedEmployeeLeaves).map(id => String(id));

        // Remove leave info for deselected employees (and their unavailable-dates message)
        currentLoadedIds.forEach(function(empId) {
            if(!selectedIds.includes(empId)) {
                var block = $(".createduty-Append").find('[data-emp-leave-id="' + empId + '"]');
                block.next('.roster-unavailable-msg').remove();
                block.remove();
                delete loadedEmployeeLeaves[empId];
                delete leaveDatesByEmployee[empId];
            }
        });

        // Load leave info for newly selected employees
        if(selectedIds.length > 0) {
            selectedIds.forEach(function(empId) {
                // Only load if not already loaded
                if(!loadedEmployeeLeaves[empId]) {
                    $.ajax({
                        url: "{{ route('resort.timeandattendance.DutyRosterandLeave') }}",
                        type: "POST",
                        data: {"_token":"{{ csrf_token() }}","id":empId},
                        success: function (response) {
                            if (response.success) {
                                // Wrap the response in a div with employee ID for identification
                                let leaveBlock = $('<div data-emp-leave-id="' + empId + '"></div>').html(response.view);
                                $(".createduty-Append").append(leaveBlock);
                                loadedEmployeeLeaves[empId] = true;
                                leaveDatesByEmployee[empId] = response.leaveDates || [];
                                injectRosterUnavailableMessages([empId]);
                            }
                        },
                        error: function() {
                            console.error('Error loading leave info for employee:', empId);
                        }
                    });
                }
            });
            // Fetch dates that selected employees already have roster - disable those in date range picker
            $.ajax({
                url: "{{ route('resort.timeandattendance.RosterOccupiedDates') }}",
                type: "GET",
                data: { emp_ids: selectedIds },
                success: function (response) {
                    rosterDatesByEmployee = response.dates_by_employee || {};
                    if (response.dates && response.dates.length) {
                        window.initDateRangePicker(response.dates);
                    } else {
                        window.initDateRangePicker([]);
                    }
                    injectRosterUnavailableMessages(selectedIds);
                },
                error: function(xhr) {
                    console.error('RosterOccupiedDates error', xhr);
                    rosterDatesByEmployee = {};
                    window.initDateRangePicker([]);
                }
            });
        } else {
            $(".createduty-Append").html('');
            loadedEmployeeLeaves = {};
            rosterDatesByEmployee = {};
            if (typeof window.initDateRangePicker === 'function') {
                window.initDateRangePicker([]);
            }
        }
    });
    $(".btn-weekly").click(function () {
        $(this).addClass("active");
        $(".weekly-main").addClass("d-block");
        $(".weekly-main").removeClass("d-none");
        $(".btn-monthly").removeClass("active");
        $(".monthly-main").addClass("d-none");
        $(".monthly-main").removeClass("d-block");
    });
    $(".btn-monthly").click(function () {
        $(this).addClass("active");
        $(".monthly-main").addClass("d-block");
        $(".monthly-main").removeClass("d-none");
        $(".btn-weekly").removeClass("active");
        $(".weekly-main").addClass("d-none");
        $(".weekly-main").removeClass("d-block");
    });
    $(document).on('click', '.addMore-addOvertime', function (e) {
        e.preventDefault();

        // Clone the first shift-block
        var newBlock = $('.addOvertime-block').first().clone();

        // Reset the select dropdowns to their default state (first option)
        newBlock.find('select').each(function () {
            $(this).prop('selectedIndex', 0); // Reset to first option
        });
        // Clear input values from cloned block
        newBlock.find('input').val('');

        // Append the new block
        $('.addOvertime-main').append(newBlock);
    });
    // Employee change handler is now in the earlier section
    $(document).on('click', '#DefaultShiftTime', function () {
        if ($(this).prop('checked')) {

            $("#MakeShift").attr('disabled',true);

            $("#MakeShift").val($("#hiddenInput").val());
        }
        else
        {
            $("#MakeShift").attr('disabled',false);
        }
    });
    // Calculate all totals: shift hours, overtime, day off deduction, and final total
    function calculateAllTotals() {
        try {
            let selectedEmployees = $('#Employee').val() || [];
            let employeeCount = selectedEmployees.length;

            if(employeeCount === 0) {
                $('#TotalHours').html('0');
                $('#OvertimeTotalHours').html('0');
                $('#DayOffDeduction').html('0');
                $('#FinalTotalHours').html('0');
                $('#TotalHoursInput').val('');
                $('#FinalTotalHoursInput').val('');
                return;
            }

            // Get shift hours from shift settings - use TotalHours field
            let selectedShift = $("#Shift").find(":selected");
            let shiftTotalHrs = selectedShift.data('totalhrs') || "";

            // If TotalHours is not available or empty, calculate from StartTime and EndTime
            if(!shiftTotalHrs || shiftTotalHrs === "" || shiftTotalHrs === "00:00") {
                let startTime = selectedShift.data('starttime') || "";
                let endTime = selectedShift.data('endtime') || "";

                if(startTime && endTime) {
                    // Calculate hours from StartTime and EndTime
                    let start = new Date('2000-01-01 ' + startTime);
                    let end = new Date('2000-01-01 ' + endTime);

                    // If end is before start, it's a next-day shift
                    if(end < start) {
                        end.setDate(end.getDate() + 1);
                    }

                    let diffMs = end - start;
                    let diffHours = Math.floor(diffMs / (1000 * 60 * 60));
                    let diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                    shiftTotalHrs = diffHours.toString().padStart(2, '0') + ':' + diffMinutes.toString().padStart(2, '0');
                } else {
                    shiftTotalHrs = "00:00";
                }
            }

            let [shiftHours, shiftMinutes] = shiftTotalHrs.split(':');
            shiftHours = parseInt(shiftHours) || 0;
            shiftMinutes = parseInt(shiftMinutes) || 0;

            // Get date range
            let hiddenInput = $("#hiddenInput").val();
            if(!hiddenInput) {
                $('#TotalHours').html('0');
                $('#OvertimeTotalHours').html('0');
                $('#DayOffDeduction').html('0');
                $('#FinalTotalHours').html('0');
                return;
            }

            let hiddenInputArray = hiddenInput.split(' - ');
            let startDateStr = hiddenInputArray[0].trim();
            let endDateStr = hiddenInputArray[1].trim();

            // Parse dates - format is m/d/Y (e.g., "01/09/2024")
            let startParts = startDateStr.split('/');
            let endParts = endDateStr.split('/');
            // Create dates: new Date(year, monthIndex, day) - monthIndex is 0-based
            let startDate = new Date(parseInt(startParts[2]), parseInt(startParts[0]) - 1, parseInt(startParts[1]));
            let endDate = new Date(parseInt(endParts[2]), parseInt(endParts[0]) - 1, parseInt(endParts[1]));

            // Calculate number of days
            let daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

            // Get selected day off dates
            let dayOffDates = [];

            try {
                if (window.dayOffDates && window.dayOffDates.length > 0) {
                    dayOffDates = window.dayOffDates.slice();
                }
                // Fallback: try to get from input value
                else if($('#DayOffDates').val()) {
                    let dayOffValue = $('#DayOffDates').val();
                    if(dayOffValue) {
                        dayOffDates = dayOffValue.split(',').map(d => d.trim()).filter(d => d);
                    }
                }
            } catch(e) {
                console.error('Error getting day off dates:', e);
                // If there's an error, just use empty array
                dayOffDates = [];
            }

            // Calculate GROSS shift total hours for all employees and all days (BEFORE deduction)
            let grossShiftTotalMinutes = (shiftHours * 60 + shiftMinutes) * employeeCount * daysDiff;

            // Calculate day off hours deduction
            let dayOffCount = dayOffDates.length;
            let dayOffDeductionMinutes = (shiftHours * 60 + shiftMinutes) * employeeCount * dayOffCount;

            // Calculate NET shift hours (AFTER day off deduction)
            let netShiftTotalMinutes = grossShiftTotalMinutes - dayOffDeductionMinutes;

            // Calculate overtime total from hoursByDate (per-date OT); exclude day-off dates
            let overtimeTotalMinutes = 0;
            for (let empId in employeeOvertimeData) {
                let overtimeInfo = employeeOvertimeData[empId];
                if (!overtimeInfo) continue;
                let hoursByDate = overtimeInfo.hoursByDate || {};
                // Legacy: support old format (overtime + days)
                if (!hoursByDate || Object.keys(hoursByDate).length === 0) {
                    let ot = overtimeInfo.overtime || '00:00';
                    let days = overtimeInfo.days || [];
                    if (ot === '00:00' || days.length === 0) continue;
                    let [oh, om] = ot.split(':');
                    oh = parseInt(oh, 10) || 0;
                    om = parseInt(om, 10) || 0;
                    days.forEach(function(dateStr) {
                        if (dayOffDates.indexOf(dateStr) === -1) overtimeTotalMinutes += oh * 60 + om;
                    });
                    continue;
                }
                for (let dateStr in hoursByDate) {
                    if (dayOffDates.indexOf(dateStr) !== -1) continue;
                    let v = hoursByDate[dateStr];
                    if (!v || v === '00:00') continue;
                    let parts = v.split(':');
                    let oh = parseInt(parts[0], 10) || 0, om = parseInt(parts[1], 10) || 0;
                    overtimeTotalMinutes += oh * 60 + om;
                }
            }

            // Calculate final total: Net Shift Hours + Overtime Hours
            let finalTotalMinutes = netShiftTotalMinutes + overtimeTotalMinutes;

            // Format and display
            let shiftTotal = formatTimeFromMinutes(netShiftTotalMinutes); // Show NET hours after deduction
            let overtimeTotal = formatTimeFromMinutes(overtimeTotalMinutes);
            let dayOffDeduction = formatTimeFromMinutes(dayOffDeductionMinutes);
            let finalTotal = formatTimeFromMinutes(finalTotalMinutes);

            $('#TotalHours').html(shiftTotal);
            $('#TotalHoursInput').val(shiftTotal);
            $('#OvertimeTotalHours').html(overtimeTotal);
            $('#DayOffDeduction').html(dayOffDeduction);
            $('#FinalTotalHours').html(finalTotal);
            $('#FinalTotalHoursInput').val(finalTotal);
        } catch(error) {
            console.error('Error in calculateAllTotals:', error);
            // Show user-friendly error
            toastr.error('Error calculating hours. Please try again.', 'Calculation Error', {
                positionClass: 'toast-bottom-right'
            });
        }
    }

    // Helper function to format minutes to HH:MM
    function formatTimeFromMinutes(totalMinutes) {
        if(totalMinutes < 0) totalMinutes = 0;
        let hours = Math.floor(totalMinutes / 60);
        let minutes = totalMinutes % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }

    // calculateTotalTime() (used by the modal / edit duty roster flow) now
    // comes from the shared dutyRosterSharedScripts partial included above.

    $(document).on('keyup', '.search', function() {
        updateFilterWiseTable();
    });
    $(document).on('change', '#Position', function() {
        updateFilterWiseTable();
    });

    $(document).on('change', '#DutyRosterCreateDatePickerFilter', function() {


        updateFilterWiseTable();
    });

    document.getElementById('DutyRosterCreateDatePickerFilter').addEventListener('input', function () {
            let rawDate = this.value; // Format: YYYY-MM-DD
            if (rawDate) {
                let parts = rawDate.split('-');
                this.value = `${parts[2]}-${parts[1]}-${parts[0]}`; // Converts to DD-MM-YYYY
            }
        });

    function updateFilterWiseTable()
    {
        var search = $(".search").val();
        var Position = $("#Position").val();
        var DatePickerFilter = $("#DutyRosterCreateDatePickerFilter").val();
        let monthly  = $(".btn-monthly").hasClass("active");
        let sendclass='';
        if(monthly == true)
        {
            sendclass='Monthly';
        }
        else{
            sendclass ="Weekly";
        }
        $.ajax({
                url: "{{ route('resort.timeandattendance.DutyRosterSearch') }}",
                type: "POST",
                data: {"_token":"{{ csrf_token() }}","search":search,"Position":Position,"date":DatePickerFilter,"monthly":monthly,"sendclass":sendclass},
                success: function (response) {

                    if (response.success)
                    {

                        $(".appendData").html(response.view);

                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });


    }

    $('#clearFilter').on('click', function() {
        $('.search').val('');
        $('#Position').val('').trigger('change');
        $('#DutyRosterCreateDatePickerFilter').val('');
        updateFilterWiseTable();
    });
</script>

<script>
(function () {
    'use strict';
    // Redesigned Panel 1/3 UI — entirely additive. The real submitted
    // field (#Employee, a <select multiple>) and every existing
    // handler/AJAX call/validation rule are untouched; this only keeps
    // the new checkbox list, pills, chips, and footer summary in sync
    // with it, via the same #Employee `change` event the existing code
    // already listens to (jQuery supports multiple handlers on one
    // event — this doesn't replace anything, just adds to it).

    var AVATAR_COLORS = 6;

    function renderSelectedPills() {
        var selected = $('#Employee').val() || [];
        var $pills = $('#drcSelectedPills');
        $pills.empty();
        selected.forEach(function (empId) {
            var $row = $('.drc-emp-row').filter(function () {
                return $(this).find('.drc-emp-checkbox').val() === String(empId);
            });
            var name = $row.find('.drc-emp-name').text() || 'Employee';
            var avatarHtml = $row.find('.drc-avatar').length ? $row.find('.drc-avatar').prop('outerHTML') : '';
            var $pill = $(
                '<span class="drc-pill" data-emp-id="' + empId + '">' +
                    avatarHtml +
                    '<span>' + $('<div>').text(name).html() + '</span>' +
                    '<button type="button" class="drc-pill-remove" data-emp-id="' + empId + '" aria-label="Remove">&#10005;</button>' +
                '</span>'
            );
            $pills.append($pill);
        });

        $('#drcEmpCountChip, #drcEmpCountChip2').text(selected.length + ' selected');

        $('.drc-emp-row').each(function () {
            var id = $(this).find('.drc-emp-checkbox').val();
            var isSelected = selected.indexOf(id) !== -1;
            $(this).toggleClass('selected', isSelected);
            $(this).find('.drc-emp-checkbox').prop('checked', isSelected);
        });

        updateFooterSummary();
    }

    // Checkbox list -> real <select>. Toggling a row updates the
    // matching <option>'s selected state and fires a real change event
    // on #Employee, so the existing leave-lookup/hours-recalc handler
    // runs exactly as if the old dropdown had been used.
    $(document).on('change', '.drc-emp-checkbox', function () {
        var empId = $(this).val();
        var checked = $(this).is(':checked');
        $('#Employee option[value="' + empId + '"]').prop('selected', checked);
        $('#Employee').trigger('change');
    });

    // Pill remove / detail-card remove (the ✕ on the AJAX-loaded leave
    // card) -> uncheck the matching row and sync the same way.
    $(document).on('click', '.drc-pill-remove, .drc-emp-detail-remove', function () {
        var empId = $(this).data('emp-id');
        $('.drc-emp-row .drc-emp-checkbox[value="' + empId + '"]').prop('checked', false);
        $('#Employee option[value="' + empId + '"]').prop('selected', false);
        $('#Employee').trigger('change');
    });

    // Re-render pills/chip whenever the real select's value changes —
    // covers both the checkbox list above and any other code path that
    // already updates #Employee's selection.
    $(document).on('change', '#Employee', renderSelectedPills);

    // Employee search filter.
    $(document).on('keyup', '#drcEmpSearch', function () {
        var q = $(this).val().toLowerCase();
        $('.drc-emp-row').each(function () {
            var name = $(this).data('emp-name') || '';
            $(this).toggle(String(name).indexOf(q) > -1);
        });
    });

    // Day-off dates are toggled directly on the Select Date Range
    // calendar now (see #datapicker's click handler) — clicking the
    // dropzone just scrolls/highlights that calendar as a hint, since
    // there's no popup of its own to open any more.
    $(document).on('click', '#drcDayOffDropzone', function () {
        var $calendar = $('#datapicker');
        if (!$calendar.length) return;
        $calendar[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $calendar.addClass('drc-pulse');
        setTimeout(function () { $calendar.removeClass('drc-pulse'); }, 900);
    });

    function formatChipDate(iso) {
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var parts = iso.split('-');
        return parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1];
    }

    function renderDayOffChips() {
        var $chips = $('#drcDayOffChips');
        $chips.empty();
        var dates = window.dayOffDates || [];
        dates.forEach(function (iso) {
            var $chip = $(
                '<span class="drc-dayoff-chip" data-iso="' + iso + '">' +
                    formatChipDate(iso) +
                    '<button type="button" aria-label="Remove">&#10005;</button>' +
                '</span>'
            );
            $chips.append($chip);
        });
        var count = dates.length;
        $('#drcDayOffDropzoneSub').text(count > 0 ? (count + (count === 1 ? ' date selected' : ' dates selected')) : 'Click a date inside your selected range in Select Date Range');
        updateFooterSummary();
    }

    // Remove a single day-off date from its chip — the same toggle used
    // by clicking the date on the calendar, just triggered from here
    // instead, so the calendar highlighting and hidden input stay in
    // sync either way.
    $(document).on('click', '.drc-dayoff-chip button', function () {
        var iso = $(this).closest('.drc-dayoff-chip').data('iso');
        if (typeof window.toggleDayOffDate === 'function') { window.toggleDayOffDate(String(iso)); }
    });

    // setDayOffDates() triggers a native 'change' event on #DayOffDates
    // on every toggle — independent hook, doesn't touch calculateAllTotals()
    // (already called by setDayOffDates itself) at all.
    $(document).on('change', '#DayOffDates', renderDayOffChips);

    // Declared as a hoisted function declaration (not a `window.x =
    // function(){}` expression) so every .on() reference above and
    // below it in this same block can safely call it regardless of
    // source order — an expression form here previously threw
    // "updateFooterSummary is not defined" on the .gf-zone-checkbox
    // binding, which silently aborted the rest of this script.
    function updateFooterSummary() {
        var empCount = ($('#Employee').val() || []).length;
        var zoneCount = $('.gf-zone-checkbox:checked').length;

        var workDays = 0;
        var hiddenVal = $('#hiddenInput').val();
        if (hiddenVal) {
            var parts = hiddenVal.split(' - ');
            var sp = parts[0].trim().split('/');
            var ep = parts[1].trim().split('/');
            var start = new Date(parseInt(sp[2], 10), parseInt(sp[0], 10) - 1, parseInt(sp[1], 10));
            var end = new Date(parseInt(ep[2], 10), parseInt(ep[0], 10) - 1, parseInt(ep[1], 10));
            var totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            var offCount = (window.dayOffDates || []).length;
            workDays = Math.max(0, totalDays - offCount);
        }

        $('#drcFooterSummary').text(
            'Rostering ' + empCount + (empCount === 1 ? ' employee' : ' employees') +
            ' · ' + workDays + (workDays === 1 ? ' work day' : ' work days') +
            ' · ' + zoneCount + (zoneCount === 1 ? ' zone' : ' zones')
        );
        if (typeof window.updateSubmitButtonState === 'function') { window.updateSubmitButtonState(); }
    }
    window.updateFooterSummary = updateFooterSummary;

    // Zone checkboxes already have their own search-filter handler
    // elsewhere; just also keep the footer summary current.
    $(document).on('change', '.gf-zone-checkbox', updateFooterSummary);

    // Initial render on page load (covers the default pre-selected
    // range/employees, if any).
    renderSelectedPills();
    renderDayOffChips();
})();
</script>
@endsection

