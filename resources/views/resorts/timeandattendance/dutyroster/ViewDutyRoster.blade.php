@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <div class="body-wrapper pb-5">
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
            <div class="card">
                    <div class="appendData">
                            <div class="monthly-main">
                                @php
                                    // Display label only — the week selector
                                    // toolbar was removed in favour of a
                                    // single full-month calendar layout.
                                    $monthLabel = !empty($startOfMonth) ? \Carbon\Carbon::parse($startOfMonth)->format('F Y') : '';
                                    // Grid column (1 = Sun ... 7 = Sat) that day 1 of the
                                    // month falls on, so the CSS grid can offset the first
                                    // day-cell and every date lines up under its real
                                    // weekday instead of always starting in column 1.
                                    $firstDayOfMonthColumn = !empty($monthwiseheaders) ? (\Carbon\Carbon::parse($monthwiseheaders[0]['date'])->dayOfWeek + 1) : 1;

                                    // Sun–Sat calendar grid for Department View: groups every
                                    // date of the month into [week index => [0=>Sun date, ...,
                                    // 6=>Sat date]] so a single week's columns can be paged
                                    // through client-side (prev/next) without another server
                                    // round-trip — the controller already fetches the whole
                                    // month, this just re-groups it by real calendar week.
                                    // Built once here, not per employee, since it's identical
                                    // for everyone in the same month.
                                    $calendarWeeks = [];
                                    foreach ($monthwiseheaders as $h) {
                                        $dow = \Carbon\Carbon::parse($h['date'])->dayOfWeek;
                                        $dayNum = (int) date('j', strtotime($h['date']));
                                        $weekIdx = (int) intdiv(($firstDayOfMonthColumn - 1) + ($dayNum - 1), 7);
                                        $calendarWeeks[$weekIdx][$dow] = $h;
                                    }
                                    // One label per week (e.g. "Week 2 · 6 – 12 Jul"), built from
                                    // whichever dates actually exist in that week for this month
                                    // (a leading/trailing partial week shows its real short
                                    // range, not a full Sun–Sat span) — read by JS on week
                                    // change instead of recomputing date math client-side.
                                    $weekLabels = [];
                                    $initialWeek = 0;
                                    $todayStr = now()->toDateString();
                                    foreach ($calendarWeeks as $wIdx => $week) {
                                        $validDates = array_filter($week, fn($d) => $d !== null);
                                        if (empty($validDates)) {
                                            continue;
                                        }
                                        $firstD = \Carbon\Carbon::parse(reset($validDates)['date']);
                                        $lastD = \Carbon\Carbon::parse(end($validDates)['date']);
                                        $weekLabels[$wIdx] = 'Week ' . ($wIdx + 1) . ' · ' . $firstD->format('j M') . ' – ' . $lastD->format('j M');
                                        foreach ($validDates as $d) {
                                            if ($d['date'] === $todayStr) {
                                                $initialWeek = $wIdx;
                                            }
                                        }
                                    }
                                @endphp
                                @if($monthLabel)
                                    <div class="d-flex align-items-center gap-2 mb-3 px-2 pt-2 flex-wrap">
                                        <strong class="me-2">{{ $monthLabel }}</strong>
                                        <div class="duty-roster-view-toggle ms-auto" role="group" aria-label="Duty roster view">
                                            <button type="button" class="duty-roster-view-btn active" data-duty-view="individual">Individual view</button>
                                            <button type="button" class="duty-roster-view-btn" data-duty-view="department">Department view</button>
                                        </div>
                                    </div>
                                    <div class="duty-roster-week-nav d-none"
                                         id="dutyRosterWeekNav"
                                         data-week-labels="{{ json_encode($weekLabels) }}"
                                         data-week-count="{{ !empty($calendarWeeks) ? (max(array_keys($calendarWeeks)) + 1) : 1 }}"
                                         data-initial-week="{{ $initialWeek }}">
                                        <button type="button" class="duty-roster-week-arrow" id="dutyRosterWeekPrev" aria-label="Previous week">&#8249;</button>
                                        <span class="duty-roster-week-label" id="dutyRosterWeekLabel"></span>
                                        <button type="button" class="duty-roster-week-arrow" id="dutyRosterWeekNext" aria-label="Next week">&#8250;</button>
                                    </div>
                                @endif
                                {{-- Legend: same shift-color mapping as Common::shiftNameColor()
                                     and the same cell states used below, so this key stays accurate
                                     without needing to be kept in sync by hand elsewhere. --}}
                                <div class="duty-roster-legend">
                                    <span><span class="dot dot-blue"></span>Morning</span>
                                    <span><span class="dot dot-yellow"></span>Afternoon</span>
                                    <span><span class="dot dot-skyblue"></span>Evening</span>
                                    <span><span class="dot dot-purple"></span>Night</span>
                                    <span><span class="dot dot-off"></span>Day off</span>
                                    <span><span class="dot dot-unassigned"></span>Unassigned</span>
                                    <span><span class="dot dot-holiday"></span>Off-day / public holiday worked</span>
                                    <span><span class="dot dot-today"></span>Today</span>
                                </div>
                                {{-- Accordion Structure for Department and Section --}}
                                <div class="viewBudget-accordion" id="accordionDutyRoster">
                                    @if(!empty($groupedRosterData))
                                        @php $deptIteration = 1; @endphp
                                        @foreach ($groupedRosterData as $deptId => $deptData)
                                            {{-- Level 1: Department --}}
                                            <div class="accordion-item mb-2 department-accordion">
                                                <h2 class="accordion-header" id="headingDept{{ $deptIteration }}">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#collapseDept{{ $deptIteration }}" aria-expanded="false"
                                                            aria-controls="collapseDept{{ $deptIteration }}">
                                                        <span class="duty-roster-accordion-icon"><i class="fas fa-building"></i></span>
                                                        <h3>{{ $deptData['dept_name'] }}</h3>
                                                        <span class="badge badge-themeBlue ms-2 small">
                                                            Employees: {{ count($deptData['employees']) + array_sum(array_map(function($section) { return count($section['employees']); }, $deptData['sections'])) }}
                                                        </span>
                                                    </button>
                                                </h2>
                                                <div id="collapseDept{{ $deptIteration }}" class="collapse"
                                                     aria-labelledby="headingDept{{ $deptIteration }}" data-bs-parent="#accordionDutyRoster">
                                                    <div class="accordion-body p-2">
                                                        @php $sectionIteration = 1; @endphp
                                                        {{-- Sections under Department --}}
                                                        @if(!empty($deptData['sections']))
                                                            @foreach($deptData['sections'] as $sectionId => $sectionData)
                                                                {{-- Skip sections that have no employees so we don't render
                                                                     a duplicate empty "No Records Found" table next to the
                                                                     dept-direct employees table. --}}
                                                                @continue(empty($sectionData['employees']))
                                                                {{-- Level 2: Section --}}
                                                                <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                    <div class="accordion-item">
                                                                        <h2 class="accordion-header" id="headingSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                                    data-bs-target="#collapseSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                                    aria-expanded="false" aria-controls="collapseSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                                <span class="duty-roster-accordion-icon duty-roster-accordion-icon-sky"><i class="fas fa-layer-group"></i></span>
                                                                                <span class="duty-roster-section-name">{{ $sectionData['section_name'] }}</span>
                                                                                <span class="badge badge-themeSkyblue ms-2 small">Employees: {{ count($sectionData['employees']) }}</span>
                                                                            </button>
                                                                        </h2>
                                                                        <div id="collapseSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                             class="collapse"
                                                                             aria-labelledby="headingSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                             data-bs-parent="#accordionSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                            <div class="accordion-body p-2">
                                                                                {{-- Employee Roster Table for Section --}}
                                                                                <div class="table-responsive mb-4 duty-roster-individual-view">
                                                                                    <table class="table table-bordered table-createDutymonthly mb-1">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th>Employee Name</th>
                                                                                                @if(!empty($monthwiseheaders))
                                                                                                    @foreach ($monthwiseheaders as $h)
                                                                                                        @php
                                                                                                            $currentDate = isset($h['date']) ? $h['date'] : date('Y-m-d', strtotime($h['day']));
                                                                                                            $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                                                                            $weekNum = (int) ceil(((int) date('d', strtotime($currentDate))) / 7);
                                                                                                        @endphp
                                                                                                        <th data-week="{{ $weekNum }}" class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                                                                                                    @endforeach
                                                                                                @endif
                                                                                                <th>Summary</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @php
                                                                                                // Collected alongside the existing per-day loop below (reusing
                                                                                                // the same already-fetched $RosterInternalDataMonth per employee)
                                                                                                // so Department View doesn't need its own GetRosterdata() call.
                                                                                                $departmentViewEmployees = [];
                                                                                            @endphp
                                                                                            @if(!empty($sectionData['employees']))
                                                                                                @foreach ($sectionData['employees'] as $r)
                                                                                                    <tr id="duty-roster-emp-{{ $r->emp_id }}" class="emp-collapsed">
                                                                                                        <td>
                                                                                                            <div class="createDuty-user d-flex justify-content-between align-items-center">
                                                                                                                <div class="d-flex align-items-center">
                                                                                                                    <div class="img-circle">
                                                                                                                        <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                                                                                                    </div>
                                                                                                                    <div class="ms-2">
                                                                                                                        <p>
                                                                                                                            <span class="fw-600">{{ ucfirst($r->first_name .' '. $r->last_name) }}</span>
                                                                                                                            <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                                                                                                        </p>
                                                                                                                        <span>{{ ucfirst($r->position_title) }}</span>
                                                                                                                    @php
                                                                                                                        $zoneIds = !empty($r->geofence_zone_id) ? (json_decode($r->geofence_zone_id, true) ?: []) : [];
                                                                                                                        $zones = !empty($zoneIds) ? \App\Models\ResortGeofence::whereIn('id', $zoneIds)->get() : collect();
                                                                                                                    @endphp
                                                                                                                    @if($zones->count())
                                                                                                                        <div class="mt-1">
                                                                                                                        @foreach($zones as $zone)
                                                                                                                            <span class="badge me-1" style="background:{{ $zone->color }}22; color:{{ $zone->color }}; border:1px solid {{ $zone->color }}; font-size:9px;">
                                                                                                                                <i class="fa-solid fa-{{ $zone->shape_type === 'circle' ? 'circle' : 'draw-polygon' }} me-1"></i>{{ $zone->name }}
                                                                                                                            </span>
                                                                                                                        @endforeach
                                                                                                                            <a href="javascript:void(0)" class="editIcon-btn editGeofenceZone" data-roster_id="{{ $r->duty_roster_id }}" data-zone_ids="{{ json_encode($zoneIds) }}" title="Change geo-fence zone"><i class="fa-solid fa-pen fa-2xs"></i></a>
                                                                                                                        </div>
                                                                                                                    @elseif(isset($geofenceZones) && $geofenceZones->count())
                                                                                                                        <div class="mt-1">
                                                                                                                            <a href="javascript:void(0)" class="editGeofenceZone" data-roster_id="{{ $r->duty_roster_id }}" data-zone_ids="[]"><small class="text-muted"><i class="fa-solid fa-plus fa-2xs"></i> Assign zone</small></a>
                                                                                                                        </div>
                                                                                                                    @endif
                                                                                                                </div>
                                                                                                                </div>
                                                                                                                {{-- Per-employee collapse — same idea as the dept/section
                                                                                                                     accordion above. CSS hides every <td> except the
                                                                                                                     header when this <tr> has class .emp-collapsed. --}}
                                                                                                                <button type="button" class="btn btn-sm btn-link emp-collapse-toggle p-1 text-decoration-none" aria-label="Toggle employee">
                                                                                                                    <i class="fa-solid fa-chevron-up"></i>
                                                                                                                    <i class="fa-solid fa-chevron-down"></i>
                                                                                                                </button>
                                                                                                            </div>
                                                                                                            {{-- Weekday key for the calendar grid below — dates align
                                                                                                                 under these via $firstDayOfMonthColumn, so this needs
                                                                                                                 to be shown once per employee, not per day-cell. --}}
                                                                                                            <div class="duty-roster-dow-header">
                                                                                                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                                                                                                            </div>
                                                                                                        </td>

                                                                                                        @php
                                                                                                            $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                                                                            $totalHoursMonth = 0;
                                                                                                            $totalOTMinutesMonth = 0;
                                                                                                            $deptViewDays = [];
                                                                                                        @endphp

                                                                                                        @foreach ($monthwiseheaders as $h)
                                                                                                        @php
                                                                                                            $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                                                                            $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                                                                            $isToday = $formattedDate === now()->toDateString();
                                                                                                            $entriesForDate = $RosterInternalDataMonth->where('date', $formattedDate);
                                                                                                            $shiftData = $entriesForDate->first(fn($e) => isset($e->roster_id) && (int)$e->roster_id === (int)$r->duty_roster_id)
                                                                                                                ?? $entriesForDate->first(fn($e) => !empty(trim((string)($e->OverTime ?? ''))) && !in_array(trim($e->OverTime ?? ''), ['00:00', '0:00', '0'], true))
                                                                                                                ?? $entriesForDate->first();

                                                                                                            // Check for leave on this date
                                                                                                            $employeeLeave = \App\Models\EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                                                                                                ->where('employees_leaves.emp_id', $r->emp_id)
                                                                                                                ->where('employees_leaves.status', 'Approved')
                                                                                                                ->whereDate('employees_leaves.from_date', '<=', $formattedDate)
                                                                                                                ->whereDate('employees_leaves.to_date', '>=', $formattedDate)
                                                                                                                ->first(['t4.color', 't4.leave_type', 't4.leave_category', 'employees_leaves.from_date', 'employees_leaves.to_date']);

                                                                                                            $toatalHoursForDay = 0;
                                                                                                            $startTime = null;
                                                                                                            $endTime = null;
                                                                                                            if ($shiftData && !$employeeLeave)
                                                                                                            {
                                                                                                                if($shiftData->Status != 'DayOff')
                                                                                                                {
                                                                                                                    $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                                                    $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                                                    // Overnight shifts (e.g. 08:00 PM-04:00 AM) have an
                                                                                                                    // EndTime that's numerically earlier than StartTime —
                                                                                                                    // diffInHours() on two same-day Carbon times took the
                                                                                                                    // "long way round" (16h instead of the real 8h) unless
                                                                                                                    // EndTime is rolled onto the next day first.
                                                                                                                    if ($endTime->lte($startTime)) {
                                                                                                                        $endTime->addDay();
                                                                                                                    }
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

                                                                                                            <td data-week="{{ (int) ceil(((int) date('d', strtotime($formattedDate))) / 7) }}" class="day-cell {{ $isPublicHoliday ? 'public-holiday-cell' : '' }} {{ $isToday ? 'today-cell' : '' }}" @if($loop->first) style="grid-column-start: {{ $firstDayOfMonthColumn }};" @endif>
                                                                                                                <div class="day-cell-date">
                                                                                                                    <span class="day-num">{{ (int) date('d', strtotime($formattedDate)) }}</span>
                                                                                                                    <span class="day-name">{{ date('D', strtotime($formattedDate)) }}</span>
                                                                                                                    @if($isToday)<span class="today-tag">Today</span>@endif
                                                                                                                </div>
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
                                                                                                                    @if($shiftData->Status == 'DayOff')
                                                                                                                        {{-- Day Off: show on whole column, no shift, but
                                                                                                                             expose edit so HR can change it back to a
                                                                                                                             working shift. --}}
                                                                                                                        <div class="createDuty-tableBlock dayoff-cell">
                                                                                                                            <div class="createDuty-dayoff">Day Off</div>
                                                                                                                            <p class="text-end mb-0 mt-1">
                                                                                                                                <button class="editIcon-btn taa-btn-secondary editdutyRoster"
                                                                                                                                        data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
                                                                                                                                        data-Shift_id="{{ $shiftData->Shift_id ?? '' }}"
                                                                                                                                        data-OverTime="00:00"
                                                                                                                                        data-DayOfDate="{{ $shiftData->DayOfDate ?? '' }}"
                                                                                                                                        data-Attd_id="{{ $shiftData->Attd_id ?? '' }}"
                                                                                                                                        data-zone_ids="{{ json_encode($zoneIds ?? []) }}"
                                                                                                                                        data-DayWiseTotalHours="">
                                                                                                                                    <i class="fa fa-edit"></i>
                                                                                                                                </button>
                                                                                                                            </p>
                                                                                                                        </div>
                                                                                                                    @else
                                                                                                                    {{-- Display Roster Entry --}}
                                                                                                                    <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }} {{ $isPublicHoliday ? 'holiday-worked' : '' }}">
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
                                                                                                                            @php
                                                                                                                                // Sanitize overtime for display and modal
                                                                                                                                $rawOverTime = $shiftData->OverTime ?? '00:00';
                                                                                                                                // Treat null/zero/empty as no overtime
                                                                                                                                if ($rawOverTime === null || $rawOverTime === '' || $rawOverTime === '0' || $rawOverTime === 0 || $rawOverTime === '0:00' || $rawOverTime === '00:0') {
                                                                                                                                    $rawOverTime = '00:00';
                                                                                                                                }
                                                                                                                                // Normalize to HH:MM: number without colon is treated as MINUTES when < 24 (so 20 -> 00:20, not 20:00)
                                                                                                                                if (strpos((string)$rawOverTime, ':') === false) {
                                                                                                                                    $num = (int) $rawOverTime;
                                                                                                                                    if ($num <= 0) {
                                                                                                                                        $rawOverTime = '00:00';
                                                                                                                                    } elseif ($num < 24) {
                                                                                                                                        $rawOverTime = '00:' . str_pad($num, 2, '0', STR_PAD_LEFT);
                                                                                                                                    } else {
                                                                                                                                        $rawOverTime = str_pad($num, 2, '0', STR_PAD_LEFT) . ':00';
                                                                                                                                    }
                                                                                                                                }
                                                                                                                                // Clamp unrealistic big overtime (>= 12 hours) to 00:00
                                                                                                                                $parts = array_map('intval', explode(':', $rawOverTime));
                                                                                                                                $oH = $parts[0] ?? 0;
                                                                                                                                $oM = $parts[1] ?? 0;
                                                                                                                                $oMinutes = $oH * 60 + $oM;
                                                                                                                                if ($oMinutes >= 12 * 60) {
                                                                                                                                    $rawOverTime = '00:00';
                                                                                                                                }

                                                                                                                                $displayOverTime = $rawOverTime;
                                                                                                                            @endphp
                                                                                                                            @if ($displayOverTime !== '00:00')
                                                                                                                                @php
                                                                                                                                    $otParts = explode(':', $displayOverTime);
                                                                                                                                    $otHours = isset($otParts[0]) ? (int)$otParts[0] : 0;
                                                                                                                                    $otMinutes = isset($otParts[1]) ? (int)$otParts[1] : 0;
                                                                                                                                    $totalOTMinutesMonth += ($otHours * 60) + $otMinutes;
                                                                                                                                    $otDisplay = $otHours > 0 ? $otHours . ' hr' : '';
                                                                                                                                    if ($otMinutes > 0) {
                                                                                                                                        $otDisplay .= ($otDisplay ? ' ' : '') . $otMinutes . ' min';
                                                                                                                                    }
                                                                                                                                    $otDisplay = $otDisplay ?: '0 hr';
                                                                                                                                @endphp
                                                                                                                                <p class="ot-chip">OT: {{ $otDisplay }}</p>
                                                                                                                            @else
                                                                                                                                <p class="ot-none">OT: 0 hr</p>
                                                                                                                            @endif
                                                                                                                            <p>
                                                                                                                                <button class="editIcon-btn taa-btn-secondary editdutyRoster"
                                                                                                                                        data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
                                                                                                                                        data-Shift_id="{{ $shiftData->Shift_id ?? '' }}"
                                                                                                                                        data-OverTime="{{ $displayOverTime }}"
                                                                                                                                        data-DayOfDate="{{ $shiftData->DayOfDate ?? '' }}"
                                                                                                                                        data-Attd_id="{{ $shiftData->Attd_id ?? '' }}"
                                                                                                                                        data-zone_ids="{{ json_encode($zoneIds ?? []) }}"
                                                                                                                                        data-DayWiseTotalHours="{{ $toatalHoursForDay ?? '' }}">
                                                                                                                                    <i class="fa fa-edit"></i>
                                                                                                                                </button>
                                                                                                                            </p>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    @endif
                                                                                                                @else
                                                                                                                    {{-- No Leave and No Roster Entry — edit pencil
                                                                                                                         carries emp_id + roster_id so the create-on-edit
                                                                                                                         flow knows which employee this empty cell
                                                                                                                         belongs to. --}}
                                                                                                                    <div class="createDuty-tableBlock createDuty-unassigned">
                                                                                                                        <div class="createDuty-empty">No Shift Assigned</div>
                                                                                                                        <p class="text-end mb-0 mt-1">
                                                                                                                            <button class="editIcon-btn taa-btn-secondary editdutyRoster"
                                                                                                                                    data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
                                                                                                                                    data-Shift_id=""
                                                                                                                                    data-OverTime="00:00"
                                                                                                                                    data-DayOfDate="{{ $h['date'] ?? '' }}"
                                                                                                                                    data-Attd_id=""
                                                                                                                                    data-emp_id="{{ $r->emp_id ?? '' }}"
                                                                                                                                    data-roster_id="{{ $r->duty_roster_id ?? '' }}"
                                                                                                                                    data-zone_ids="{{ json_encode($zoneIds ?? []) }}"
                                                                                                                                    data-DayWiseTotalHours="">
                                                                                                                                <i class="fa fa-edit"></i>
                                                                                                                            </button>
                                                                                                                        </p>
                                                                                                                    </div>
                                                                                                                @endif
                                                                                                            </td>
                                                                                                            @php
                                                                                                                // Compact per-day state for Department View's cell — built from
                                                                                                                // only the variables above that are unconditionally reset every
                                                                                                                // iteration (never left stale from a previous day).
                                                                                                                $deptViewOTRaw = trim((string) ($shiftData->OverTime ?? ''));
                                                                                                                $deptViewDays[$formattedDate] = [
                                                                                                                    'isToday' => $isToday,
                                                                                                                    'isHoliday' => $isPublicHoliday,
                                                                                                                    'leave' => $employeeLeave ? [
                                                                                                                        'type' => $employeeLeave->leave_type ?? 'Leave',
                                                                                                                        'color' => $employeeLeave->color ?? '#ccc',
                                                                                                                    ] : null,
                                                                                                                    'isDayOff' => (bool) ($shiftData && !$employeeLeave && $shiftData->Status == 'DayOff'),
                                                                                                                    'shift' => ($shiftData && !$employeeLeave && $shiftData->Status != 'DayOff') ? [
                                                                                                                        'name' => $shiftData->ShiftName ?? '',
                                                                                                                        'color' => $shiftData->ShiftNameColor ?? '',
                                                                                                                        'start' => $startTime,
                                                                                                                        'end' => $endTime,
                                                                                                                        'hours' => $toatalHoursForDay,
                                                                                                                    ] : null,
                                                                                                                    'hasOT' => $shiftData && $deptViewOTRaw !== '' && !in_array($deptViewOTRaw, ['00:00','0:00','0','00:0'], true),
                                                                                                                ];
                                                                                                            @endphp
                                                                                                        @endforeach

                                                                                                        @php
                                                                                                            $totalOTHoursMonth = intdiv($totalOTMinutesMonth, 60);
                                                                                                            $totalOTMinsRemainderMonth = $totalOTMinutesMonth % 60;
                                                                                                            $totalOTDisplayMonth = $totalOTHoursMonth . ' hr' . ($totalOTMinsRemainderMonth > 0 ? ' ' . $totalOTMinsRemainderMonth . ' min' : '');
                                                                                                        @endphp
                                                                                                        <td class="month-summary-cell">
                                                                                                            <div>Total Hrs: <span>{{ $totalHoursMonth }}</span></div>
                                                                                                            <div>Total OT: <span>{{ $totalOTDisplayMonth }}</span></div>
                                                                                                        </td>
                                                                                                        @php
                                                                                                            $departmentViewEmployees[] = [
                                                                                                                'r' => $r,
                                                                                                                'days' => $deptViewDays,
                                                                                                            ];
                                                                                                        @endphp
                                                                                                    </tr>
                                                                                                @endforeach
                                                                                            @else
                                                                                                <tr>
                                                                                                    <td colspan="{{ count($monthwiseheaders) + 2 }}" style="text-align: center">No Records Found.</td>
                                                                                                </tr>
                                                                                            @endif
                                                                                        </tbody>
                                                                                    </table>
                                                                            </div>
                                                                                {{-- Department View: one row per employee, one column per weekday, --}}
                                                                                {{-- one week at a time (paged client-side via data-cal-week + --}}
                                                                                {{-- #accordionDutyRoster[data-active-week]) — reuses $departmentViewEmployees --}}
                                                                                {{-- collected above, no extra GetRosterdata() calls. --}}
                                                                                <div class="table-responsive mb-4 duty-roster-department-view d-none">
                                                                                    <table class="table table-bordered duty-roster-dept-table mb-1">
                                                                                        <thead>
                                                                                            @foreach ($calendarWeeks as $wIdx => $week)
                                                                                                <tr data-cal-week="{{ $wIdx }}">
                                                                                                    <th class="dept-emp-col">Employee</th>
                                                                                                    @for ($dow = 0; $dow < 7; $dow++)
                                                                                                        @php
                                                                                                            $dowCell = $week[$dow] ?? null;
                                                                                                        @endphp
                                                                                                        <th class="{{ ($dowCell && $dowCell["date"] === now()->toDateString()) ? "today-col" : "" }}">
                                                                                                            {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$dow] }}
                                                                                                            @if($dowCell)<span class="dnum">{{ (int) $dowCell['day'] }}</span>@endif
                                                                                                        </th>
                                                                                                    @endfor
                                                                                                </tr>
                                                                                            @endforeach
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @forelse ($departmentViewEmployees as $de)
                                                                                                <tr>
                                                                                                    <td class="dept-emp-col duty-roster-dept-emp-link" data-jump-emp="{{ $de['r']->emp_id }}" role="button" tabindex="0">
                                                                                                        <div class="createDuty-user d-flex align-items-center">
                                                                                                            <div class="img-circle">
                                                                                                                <img src="{{ Common::getResortUserPicture($de['r']->Parentid) }}" alt="user">
                                                                                                            </div>
                                                                                                            <div class="ms-2">
                                                                                                                <p class="mb-0"><span class="fw-600">{{ ucfirst($de['r']->first_name .' '. $de['r']->last_name) }}</span></p>
                                                                                                                <span>{{ ucfirst($de['r']->position_title) }}</span>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </td>
                                                                                                    @foreach ($calendarWeeks as $wIdx => $week)
                                                                                                        @for ($dow = 0; $dow < 7; $dow++)
                                                                                                            @php
                                                                                                                $dCell = $week[$dow] ?? null;
                                                                                                                $dState = $dCell ? ($de['days'][$dCell['date']] ?? null) : null;
                                                                                                            @endphp
                                                                                                            <td class="duty-roster-dept-cell {{ ($dState && $dState['isToday']) ? 'today-cell' : '' }} {{ ($dState && $dState['isHoliday']) ? 'public-holiday-cell' : '' }}" data-cal-week="{{ $wIdx }}">
                                                                                                                @if ($dState && $dState['leave'])
                                                                                                                    <div class="dept-cell-inner dept-cell-leave" style="border-color: {{ $dState['leave']['color'] }};" title="{{ $dState['leave']['type'] }}">
                                                                                                                        <span>{{ \Illuminate\Support\Str::limit($dState['leave']['type'], 8) }}</span>
                                                                                                                    </div>
                                                                                                                @elseif ($dState && $dState['isDayOff'])
                                                                                                                    <div class="dept-cell-inner dept-cell-off">Off</div>
                                                                                                                @elseif ($dState && $dState['shift'])
                                                                                                                    <div class="dept-cell-inner {{ $dState['shift']['color'] }} {{ $dState['isHoliday'] ? 'holiday-worked' : '' }}">
                                                                                                                        <span class="dept-cell-type">{{ $dState['shift']['name'] }}</span>
                                                                                                                        @if($dState['shift']['start'] && $dState['shift']['end'])
                                                                                                                            <span class="dept-cell-time">{{ $dState['shift']['start']->format('g:iA') }}&ndash;{{ $dState['shift']['end']->format('g:iA') }}</span>
                                                                                                                        @endif
                                                                                                                        @if($dState['hasOT'])<span class="dept-cell-ot-dot" title="Overtime"></span>@endif
                                                                                                                    </div>
                                                                                                                @elseif ($dCell)
                                                                                                                    <div class="dept-cell-inner dept-cell-unassigned">&mdash;</div>
                                                                                                                @endif
                                                                                                            </td>
                                                                                                        @endfor
                                                                                                    @endforeach
                                                                                                </tr>
                                                                                            @empty
                                                                                                <tr><td colspan="8" style="text-align:center">No Records Found.</td></tr>
                                                                                            @endforelse
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @php $sectionIteration++; @endphp
                                                            @endforeach
                                                        @endif

                                                        {{-- Direct Employees under Department (no section) --}}
                                                        @if(!empty($deptData['employees']))
                                                            <div class="table-responsive mb-4 duty-roster-individual-view">
                                                                <table class="table table-bordered table-createDutymonthly mb-1">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Employee Name</th>
                                                                            @if(!empty($monthwiseheaders))
                                                                                @foreach ($monthwiseheaders as $h)
                                                                                    @php
                                                                                        $currentDate = isset($h['date']) ? $h['date'] : date('Y-m-d', strtotime($h['day']));
                                                                                        $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                                                                                        $weekNum = (int) ceil(((int) date('d', strtotime($currentDate))) / 7);
                                                                                    @endphp
                                                                                    <th data-week="{{ $weekNum }}" class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                                                                                @endforeach
                                                                            @endif
                                                                            <th>Summary</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    @php
                                                                        // Collected alongside the existing per-day loop below (reusing
                                                                        // the same already-fetched $RosterInternalDataMonth per employee)
                                                                        // so Department View doesn't need its own GetRosterdata() call.
                                                                        $departmentViewEmployees = [];
                                                                    @endphp
                                                                        @foreach ($deptData['employees'] as $r)
                                                                            <tr id="duty-roster-emp-{{ $r->emp_id }}" class="emp-collapsed">
                                                                                <td>
                                                                                    <div class="createDuty-user d-flex justify-content-between align-items-center">
                                                                                        <div class="d-flex align-items-center">
                                                                                            <div class="img-circle">
                                                                                                <img src="{{ Common::getResortUserPicture($r->Parentid) }}" alt="user">
                                                                                            </div>
                                                                                            <div class="ms-2">
                                                                                                <p>
                                                                                                    <span class="fw-600">{{ ucfirst($r->first_name .' '. $r->last_name) }}</span>
                                                                                                    <span class="badge badge-white">{{ $r->Emp_id }}</span>
                                                                                                </p>
                                                                                                <span>{{ ucfirst($r->position_title) }}</span>
                                                                                                @php
                                                                                                    $zoneIds = !empty($r->geofence_zone_id) ? (json_decode($r->geofence_zone_id, true) ?: []) : [];
                                                                                                    $zones = !empty($zoneIds) ? \App\Models\ResortGeofence::whereIn('id', $zoneIds)->get() : collect();
                                                                                                @endphp
                                                                                                @if($zones->count())
                                                                                                    <div class="mt-1">
                                                                                                    @foreach($zones as $zone)
                                                                                                        <span class="badge me-1" style="background:{{ $zone->color }}22; color:{{ $zone->color }}; border:1px solid {{ $zone->color }}; font-size:9px;">
                                                                                                            <i class="fa-solid fa-{{ $zone->shape_type === 'circle' ? 'circle' : 'draw-polygon' }} me-1"></i>{{ $zone->name }}
                                                                                                        </span>
                                                                                                    @endforeach
                                                                                                        <a href="javascript:void(0)" class="editIcon-btn editGeofenceZone" data-roster_id="{{ $r->duty_roster_id }}" data-zone_ids="{{ json_encode($zoneIds) }}" title="Change geo-fence zone"><i class="fa-solid fa-pen fa-2xs"></i></a>
                                                                                                    </div>
                                                                                                @elseif(isset($geofenceZones) && $geofenceZones->count())
                                                                                                    <div class="mt-1">
                                                                                                        <a href="javascript:void(0)" class="editGeofenceZone" data-roster_id="{{ $r->duty_roster_id }}" data-zone_ids="[]"><small class="text-muted"><i class="fa-solid fa-plus fa-2xs"></i> Assign zone</small></a>
                                                                                                    </div>
                                                                                                @endif
                                                                                            </div>
                                                                                        </div>
                                                                                        <button type="button" class="btn btn-sm btn-link emp-collapse-toggle p-1 text-decoration-none" aria-label="Toggle employee">
                                                                                            <i class="fa-solid fa-chevron-up"></i>
                                                                                            <i class="fa-solid fa-chevron-down"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                    {{-- Weekday key for the calendar grid below — dates align
                                                                                         under these via $firstDayOfMonthColumn, so this needs to
                                                                                         be shown once per employee, not per day-cell. --}}
                                                                                    <div class="duty-roster-dow-header">
                                                                                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                                                                                    </div>
                                                                                </td>

                                                                                @php
                                                                                    $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                                                    $totalHoursMonth = 0;
                                                                                    $totalOTMinutesMonth = 0;
                                                                                    $deptViewDays = [];
                                                                                @endphp

                                                                                @foreach ($monthwiseheaders as $h)
                                                                                @php
                                                                                    $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                                                    $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                                                    $isToday = $formattedDate === now()->toDateString();
                                                                                    $entriesForDate = $RosterInternalDataMonth->where('date', $formattedDate);
                                                                                    $shiftData = $entriesForDate->first(fn($e) => isset($e->roster_id) && (int)$e->roster_id === (int)$r->duty_roster_id)
                                                                                        ?? $entriesForDate->first(fn($e) => !empty(trim((string)($e->OverTime ?? ''))) && !in_array(trim($e->OverTime ?? ''), ['00:00', '0:00', '0'], true))
                                                                                        ?? $entriesForDate->first();

                                                                                    // Check for leave on this date
                                                                                    $employeeLeave = \App\Models\EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                                                                        ->where('employees_leaves.emp_id', $r->emp_id)
                                                                                        ->where('employees_leaves.status', 'Approved')
                                                                                        ->whereDate('employees_leaves.from_date', '<=', $formattedDate)
                                                                                        ->whereDate('employees_leaves.to_date', '>=', $formattedDate)
                                                                                        ->first(['t4.color', 't4.leave_type', 't4.leave_category', 'employees_leaves.from_date', 'employees_leaves.to_date']);

                                                                                    $toatalHoursForDay = 0;
                                                                                    $startTime = null;
                                                                                    $endTime = null;
                                                                                    if ($shiftData && !$employeeLeave)
                                                                                    {
                                                                                        if($shiftData->Status != 'DayOff')
                                                                                        {
                                                                                            $startTime = \Carbon\Carbon::parse($shiftData->StartTime);
                                                                                            $endTime = \Carbon\Carbon::parse($shiftData->EndTime);
                                                                                            // Overnight shift — see the identical fix above.
                                                                                            if ($endTime->lte($startTime)) {
                                                                                                $endTime->addDay();
                                                                                            }
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

                                                                                    <td data-week="{{ (int) ceil(((int) date('d', strtotime($formattedDate))) / 7) }}" class="day-cell {{ $isPublicHoliday ? 'public-holiday-cell' : '' }} {{ $isToday ? 'today-cell' : '' }}" @if($loop->first) style="grid-column-start: {{ $firstDayOfMonthColumn }};" @endif>
                                                                                        <div class="day-cell-date">
                                                                                            <span class="day-num">{{ (int) date('d', strtotime($formattedDate)) }}</span>
                                                                                            <span class="day-name">{{ date('D', strtotime($formattedDate)) }}</span>
                                                                                            @if($isToday)<span class="today-tag">Today</span>@endif
                                                                                        </div>
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
                                                                                            @if($shiftData->Status == 'DayOff')
                                                                                                {{-- Day Off: show on whole column, no shift --}}
                                                                                                <div class="createDuty-tableBlock dayoff-cell">
                                                                                                    <div class="createDuty-dayoff">Day Off</div>
                                                                                                </div>
                                                                                            @else
                                                                                            {{-- Display Roster Entry --}}
                                                                                            <div class="createDuty-tableBlock {{ $shiftData->ShiftNameColor ?? '' }} {{ $isPublicHoliday ? 'holiday-worked' : '' }}">
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
                                                                                                    @php
                                                                                                        // Sanitize overtime for display and modal
                                                                                                        $rawOverTime2 = $shiftData->OverTime ?? '00:00';
                                                                                                        if ($rawOverTime2 === null || $rawOverTime2 === '' || $rawOverTime2 === '0' || $rawOverTime2 === 0 || $rawOverTime2 === '0:00' || $rawOverTime2 === '00:0') {
                                                                                                            $rawOverTime2 = '00:00';
                                                                                                        }
                                                                                                        if (strpos((string)$rawOverTime2, ':') === false) {
                                                                                                            $num2 = (int) $rawOverTime2;
                                                                                                            if ($num2 <= 0) {
                                                                                                                $rawOverTime2 = '00:00';
                                                                                                            } elseif ($num2 < 24) {
                                                                                                                $rawOverTime2 = '00:' . str_pad($num2, 2, '0', STR_PAD_LEFT);
                                                                                                            } else {
                                                                                                                $rawOverTime2 = str_pad($num2, 2, '0', STR_PAD_LEFT) . ':00';
                                                                                                            }
                                                                                                        }
                                                                                                        $parts2 = array_map('intval', explode(':', $rawOverTime2));
                                                                                                        $oH2 = $parts2[0] ?? 0;
                                                                                                        $oM2 = $parts2[1] ?? 0;
                                                                                                        $oMinutes2 = $oH2 * 60 + $oM2;
                                                                                                        if ($oMinutes2 >= 12 * 60) {
                                                                                                            $rawOverTime2 = '00:00';
                                                                                                        }
                                                                                                        $displayOverTime2 = $rawOverTime2;
                                                                                                    @endphp
                                                                                                    @if ($displayOverTime2 !== '00:00')
                                                                                                        @php
                                                                                                            $otParts = explode(':', $displayOverTime2);
                                                                                                            $otHours = isset($otParts[0]) ? (int)$otParts[0] : 0;
                                                                                                            $otMinutes = isset($otParts[1]) ? (int)$otParts[1] : 0;
                                                                                                            $totalOTMinutesMonth += ($otHours * 60) + $otMinutes;
                                                                                                            $otDisplay = $otHours > 0 ? $otHours . ' hr' : '';
                                                                                                            if ($otMinutes > 0) {
                                                                                                                $otDisplay .= ($otDisplay ? ' ' : '') . $otMinutes . ' min';
                                                                                                            }
                                                                                                            $otDisplay = $otDisplay ?: '0 hr';
                                                                                                        @endphp
                                                                                                        <p class="ot-chip">OT: {{ $otDisplay }}</p>
                                                                                                    @else
                                                                                                        <p class="ot-none">OT: 0 hr</p>
                                                                                                    @endif
                                                                                                    <p>
                                                                                                        <button class="editIcon-btn taa-btn-secondary editdutyRoster"
                                                                                                                data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
                                                                                                                data-Shift_id="{{ $shiftData->Shift_id ?? '' }}"
                                                                                                                data-OverTime="{{ $displayOverTime2 }}"
                                                                                                                data-DayOfDate="{{ $shiftData->DayOfDate ?? '' }}"
                                                                                                                data-Attd_id="{{ $shiftData->Attd_id ?? '' }}"
                                                                                                                data-zone_ids="{{ json_encode($zoneIds ?? []) }}"
                                                                                                                data-DayWiseTotalHours="{{ $toatalHoursForDay ?? '' }}">
                                                                                                            <i class="fa fa-edit"></i>
                                                                                                        </button>
                                                                                                    </p>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endif
                                                                                        @else
                                                                                            {{-- No Leave and No Roster Entry — edit pencil carries
                                                                                                 emp_id + roster_id so create-on-edit knows which
                                                                                                 employee this empty cell belongs to. --}}
                                                                                            <div class="createDuty-tableBlock createDuty-unassigned">
                                                                                                <div class="createDuty-empty">No Shift Assigned</div>
                                                                                                <p class="text-end mb-0 mt-1">
                                                                                                    <button class="editIcon-btn taa-btn-secondary editdutyRoster"
                                                                                                            data-date="{{ date('d/m/Y', strtotime($h['date'])) }}"
                                                                                                            data-Shift_id=""
                                                                                                            data-OverTime="00:00"
                                                                                                            data-DayOfDate="{{ $h['date'] ?? '' }}"
                                                                                                            data-Attd_id=""
                                                                                                            data-emp_id="{{ $r->emp_id ?? '' }}"
                                                                                                            data-roster_id="{{ $r->duty_roster_id ?? '' }}"
                                                                                                            data-zone_ids="{{ json_encode($zoneIds ?? []) }}"
                                                                                                            data-DayWiseTotalHours="">
                                                                                                        <i class="fa fa-edit"></i>
                                                                                                    </button>
                                                                                                </p>
                                                                                            </div>
                                                                                        @endif
                                                                                    </td>
                                                                                    @php
                                                                                        // Compact per-day state for Department View's cell — built from
                                                                                        // only the variables above that are unconditionally reset every
                                                                                        // iteration (never left stale from a previous day).
                                                                                        $deptViewOTRaw = trim((string) ($shiftData->OverTime ?? ''));
                                                                                        $deptViewDays[$formattedDate] = [
                                                                                            'isToday' => $isToday,
                                                                                            'isHoliday' => $isPublicHoliday,
                                                                                            'leave' => $employeeLeave ? [
                                                                                                'type' => $employeeLeave->leave_type ?? 'Leave',
                                                                                                'color' => $employeeLeave->color ?? '#ccc',
                                                                                            ] : null,
                                                                                            'isDayOff' => (bool) ($shiftData && !$employeeLeave && $shiftData->Status == 'DayOff'),
                                                                                            'shift' => ($shiftData && !$employeeLeave && $shiftData->Status != 'DayOff') ? [
                                                                                                'name' => $shiftData->ShiftName ?? '',
                                                                                                'color' => $shiftData->ShiftNameColor ?? '',
                                                                                                'start' => $startTime,
                                                                                                'end' => $endTime,
                                                                                                'hours' => $toatalHoursForDay,
                                                                                            ] : null,
                                                                                            'hasOT' => $shiftData && $deptViewOTRaw !== '' && !in_array($deptViewOTRaw, ['00:00','0:00','0','00:0'], true),
                                                                                        ];
                                                                                    @endphp
                                                                                @endforeach

                                                                                @php
                                                                                    $totalOTHoursMonth = intdiv($totalOTMinutesMonth, 60);
                                                                                    $totalOTMinsRemainderMonth = $totalOTMinutesMonth % 60;
                                                                                    $totalOTDisplayMonth = $totalOTHoursMonth . ' hr' . ($totalOTMinsRemainderMonth > 0 ? ' ' . $totalOTMinsRemainderMonth . ' min' : '');
                                                                                @endphp
                                                                                <td class="month-summary-cell">
                                                                                    <div>Total Hrs: <span>{{ $totalHoursMonth }}</span></div>
                                                                                    <div>Total OT: <span>{{ $totalOTDisplayMonth }}</span></div>
                                                                                </td>
                                                                                @php
                                                                                    $departmentViewEmployees[] = [
                                                                                        'r' => $r,
                                                                                        'days' => $deptViewDays,
                                                                                    ];
                                                                                @endphp
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            {{-- Department View: one row per employee, one column per weekday, --}}
                                                            {{-- one week at a time (paged client-side via data-cal-week + --}}
                                                            {{-- #accordionDutyRoster[data-active-week]) — reuses $departmentViewEmployees --}}
                                                            {{-- collected above, no extra GetRosterdata() calls. --}}
                                                            <div class="table-responsive mb-4 duty-roster-department-view d-none">
                                                                <table class="table table-bordered duty-roster-dept-table mb-1">
                                                                    <thead>
                                                                        @foreach ($calendarWeeks as $wIdx => $week)
                                                                            <tr data-cal-week="{{ $wIdx }}">
                                                                                <th class="dept-emp-col">Employee</th>
                                                                                @for ($dow = 0; $dow < 7; $dow++)
                                                                                    @php
                                                                                        $dowCell = $week[$dow] ?? null;
                                                                                    @endphp
                                                                                    <th class="{{ ($dowCell && $dowCell["date"] === now()->toDateString()) ? "today-col" : "" }}">
                                                                                        {{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$dow] }}
                                                                                        @if($dowCell)<span class="dnum">{{ (int) $dowCell['day'] }}</span>@endif
                                                                                    </th>
                                                                                @endfor
                                                                            </tr>
                                                                        @endforeach
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse ($departmentViewEmployees as $de)
                                                                            <tr>
                                                                                <td class="dept-emp-col duty-roster-dept-emp-link" data-jump-emp="{{ $de['r']->emp_id }}" role="button" tabindex="0">
                                                                                    <div class="createDuty-user d-flex align-items-center">
                                                                                        <div class="img-circle">
                                                                                            <img src="{{ Common::getResortUserPicture($de['r']->Parentid) }}" alt="user">
                                                                                        </div>
                                                                                        <div class="ms-2">
                                                                                            <p class="mb-0"><span class="fw-600">{{ ucfirst($de['r']->first_name .' '. $de['r']->last_name) }}</span></p>
                                                                                            <span>{{ ucfirst($de['r']->position_title) }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                                @foreach ($calendarWeeks as $wIdx => $week)
                                                                                    @for ($dow = 0; $dow < 7; $dow++)
                                                                                        @php
                                                                                            $dCell = $week[$dow] ?? null;
                                                                                            $dState = $dCell ? ($de['days'][$dCell['date']] ?? null) : null;
                                                                                        @endphp
                                                                                        <td class="duty-roster-dept-cell {{ ($dState && $dState['isToday']) ? 'today-cell' : '' }} {{ ($dState && $dState['isHoliday']) ? 'public-holiday-cell' : '' }}" data-cal-week="{{ $wIdx }}">
                                                                                            @if ($dState && $dState['leave'])
                                                                                                <div class="dept-cell-inner dept-cell-leave" style="border-color: {{ $dState['leave']['color'] }};" title="{{ $dState['leave']['type'] }}">
                                                                                                    <span>{{ \Illuminate\Support\Str::limit($dState['leave']['type'], 8) }}</span>
                                                                                                </div>
                                                                                            @elseif ($dState && $dState['isDayOff'])
                                                                                                <div class="dept-cell-inner dept-cell-off">Off</div>
                                                                                            @elseif ($dState && $dState['shift'])
                                                                                                <div class="dept-cell-inner {{ $dState['shift']['color'] }} {{ $dState['isHoliday'] ? 'holiday-worked' : '' }}">
                                                                                                    <span class="dept-cell-type">{{ $dState['shift']['name'] }}</span>
                                                                                                    @if($dState['shift']['start'] && $dState['shift']['end'])
                                                                                                        <span class="dept-cell-time">{{ $dState['shift']['start']->format('g:iA') }}&ndash;{{ $dState['shift']['end']->format('g:iA') }}</span>
                                                                                                    @endif
                                                                                                    @if($dState['hasOT'])<span class="dept-cell-ot-dot" title="Overtime"></span>@endif
                                                                                                </div>
                                                                                            @elseif ($dCell)
                                                                                                <div class="dept-cell-inner dept-cell-unassigned">&mdash;</div>
                                                                                            @endif
                                                                                        </td>
                                                                                    @endfor
                                                                                @endforeach
                                                                            </tr>
                                                                        @empty
                                                                            <tr><td colspan="8" style="text-align:center">No Records Found.</td></tr>
                                                                        @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @php $deptIteration++; @endphp
                                        @endforeach
                                    @else
                                        <div class="alert alert-info">No duty roster data found.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

                            {{-- Geo-fence zone is roster-level (one duty_rosters
                                 row = one employee's schedule block, per the
                                 whole-week scope this modal already edits), not
                                 per-day — same zones apply regardless of which
                                 day cell was clicked to open this modal.
                                 Submitted separately to
                                 UpdateDutyRosterGeofence so the existing,
                                 already-tenant-scoped endpoint doesn't need
                                 duplicating. --}}
                            @if(isset($geofenceZones) && $geofenceZones->count())
                            <div class="col-md-12 mt-3">
                                <label>Geo-Fence Zone(s)</label>
                                <div class="drc-zone-list">
                                    @foreach($geofenceZones as $zone)
                                    <label class="drc-zone-row gf-zone-item">
                                        <input type="checkbox" value="{{ $zone->id }}" class="drc-zone-checkbox dr-modal-zone-checkbox">
                                        <span class="drc-zone-dot" style="background:{{ $zone->color }};"></span>
                                        <span class="drc-zone-name">{{ $zone->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <small class="text-muted">Employee can only check in/out inside selected zones.</small>
                            </div>
                            @endif

                            <div class="col-12 mt-3">
                                <hr class="mt-0 ">
                                <div class="bg-white text-end">
                                    <p>Total Hours:</p>
                                    <input type="hidden" name="TotalHoursModel" id="TotalHoursModelInput" value="">
                                    <h5 id="TotalHoursModel">0</h5>
                                </div>
                            </div>
                            <input type="hidden" id="Attd_id" name="Attd_id">
                            {{-- Carry the employee + roster context for cells that
                                 don't yet have a DutyRosterEntry — without these,
                                 create-on-edit inserts a row with NULL Emp_id and
                                 the downstream EmployeeOvertime insert crashes. --}}
                            <input type="hidden" id="EditEmpId" name="emp_id">
                            <input type="hidden" id="EditRosterId" name="roster_id">
                        </div>

                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn taa-btn-secondary ms-auto">Cancel</a>
                        <button type="submit"   class="btn taa-btn-primary" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editGeofenceZone-modal" tabindex="-1" aria-labelledby="editGeofenceZoneLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGeofenceZoneLabel">Assign Geo-Fence Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="UpdateDutyRosterGeofence">
                    @csrf
                    <div class="modal-body">
                        @if(isset($geofenceZones) && $geofenceZones->count())
                        <div class="drc-zone-list">
                            @foreach($geofenceZones as $zone)
                            <label class="drc-zone-row gf-zone-item">
                                <input type="checkbox" name="geofence_zone_ids[]" value="{{ $zone->id }}" class="drc-zone-checkbox edit-gf-zone-checkbox">
                                <span class="drc-zone-dot" style="background:{{ $zone->color }};"></span>
                                <span class="drc-zone-name">{{ $zone->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        <small class="text-muted">Employee can only check in/out inside selected zones.</small>
                        @else
                        <p class="small text-muted mb-0">No active zones configured.</p>
                        @endif
                        <input type="hidden" id="EditGeofenceRosterId" name="roster_id">
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @endsection

@section('import-css')
@include('resorts.timeandattendance._taa_buttons_v2_styles')
<style>
    /* Day Off cell: show "Day Off" for whole column, no shift details */
    .createDuty-tableBlock.dayoff-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60px;
    }
    .createDuty-dayoff {
        font-weight: 600;
        color: #6c757d;
    }

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

    /* Calendar layout — fits the entire month at once without horizontal
       scroll. The roster table is rendered as a 7-column wrapping grid
       (one row per week) instead of a 31-column matrix. The Employee
       Name + Summary cells stay full-width above each employee block. */
    .table-createDutymonthly { table-layout: fixed; width: 100%; }

    /* Re-flow each employee row from a single 33-cell <tr> into a
       2-column header (name + summary) followed by a 7-column day grid.
       We do this purely with CSS so the existing PHP loop doesn't need
       to be rewritten. */
    .table-createDutymonthly thead { display: none; }
    .table-createDutymonthly,
    .table-createDutymonthly tbody,
    .table-createDutymonthly tr { display: block; width: 100%; }

    .table-createDutymonthly tbody tr {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 18px;
        padding: 8px;
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        background: #fff;
    }
    .table-createDutymonthly tbody tr > td {
        display: block;
        border: 1px solid #ebedf0;
        border-radius: 10px;
        padding: 9px 8px;
        min-height: 90px;
        font-size: 11px;
        background: #fafbfc;
        overflow: hidden;
    }
    /* First TD = employee header — span all 7 cols. */
    .table-createDutymonthly tbody tr > td:first-child {
        grid-column: 1 / -1;
        background: #f5f8fb;
        min-height: auto;
        padding: 8px 10px;
    }
    /* Last TD = summary — also full width. */
    .table-createDutymonthly tbody tr > td:last-child {
        grid-column: 1 / -1;
        background: #fff7e6;
        min-height: auto;
        padding: 8px 10px;
        font-weight: 600;
        text-align: right;
    }
    /* Total Hrs + Total OT, one consistent size — a shared default.css
       rule (`.table-createDutymonthly td:last-child span { font-size:
       18px; }`) previously made the number much bigger than its label,
       which read as two different sizes on the same line. !important
       here guarantees this page's own sizing wins regardless of
       stylesheet load order. */
    .table-createDutymonthly .month-summary-cell {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
    }
    .table-createDutymonthly .month-summary-cell div,
    .table-createDutymonthly .month-summary-cell span {
        font-size: 13px !important;
        font-weight: 600;
    }
    /* The day-cell itself carries the shift-type tint/border now (see the
       :has()-based rules below), so the inner card is flattened to blend
       into it — one unified colored square per day, not a card nested
       inside a card. */
    .table-createDutymonthly .createDuty-tableBlock {
        font-size: 10px;
        line-height: 1.25;
        background: transparent;
        border: none;
        padding: 0;
        width: auto;
        height: auto;
    }
    .table-createDutymonthly .createDuty-tableBlock p { margin: 0; font-size: 10px; }
    .table-createDutymonthly .createDuty-tableBlock .badge {
        font-size: 9px;
        padding: 2px 5px;
        background: color-mix(in srgb, var(--createDuty) 22%, transparent);
        color: var(--createDuty);
    }
    /* Shift type name first (bold, colored) then time below (muted) —
       flip the DOM order (time <p> then name <span>) purely visually so
       the template loop doesn't need to change. */
    .table-createDutymonthly .createDuty-tableBlock .d-flex:first-child > div:first-child {
        display: flex;
        flex-direction: column-reverse;
    }
    .table-createDutymonthly .createDuty-tableBlock .d-flex:first-child > div:first-child span {
        font-style: normal;
        font-size: 11px;
        font-weight: 700;
    }
    .table-createDutymonthly .createDuty-tableBlock .d-flex:first-child > div:first-child p {
        color: #6c757d;
        font-size: 9.5px;
        margin-top: 1px;
    }
    /* The edit pencil was disappearing from every shift cell because its
       wrapper (.ot-details) was display:none in the compact monthly grid.
       Fix: force .ot-details visible and pin the button absolute-bottom-right
       of each cell so it always renders regardless of the OT text's width.
       An earlier version of this fix also hid the "OT: Xhr" text entirely
       to make room for the button — no longer needed now that the button
       is taken out of flow via absolute positioning, and hiding it meant
       real planned OT was invisible on the calendar. Left-align the OT
       text and reserve space on the right so it doesn't run under the
       pinned button. */
    .table-createDutymonthly .day-cell { position: relative !important; }
    .table-createDutymonthly .ot-details {
        display: flex !important;
        justify-content: flex-start;
        align-items: center;
        margin: 0;
        padding: 0;
        padding-right: 18px;
    }
    .table-createDutymonthly .ot-details > p:last-child { margin: 0; }
    .table-createDutymonthly .editIcon-btn {
        position: absolute !important;
        bottom: 4px !important;
        right: 4px !important;
        padding: 2px !important;
        font-size: 12px !important;
        line-height: 1 !important;
        background: rgba(255,255,255,0.85) !important;
        border-radius: 3px !important;
        z-index: 5 !important;
    }
    .table-createDutymonthly .editIcon-btn:hover { background: #fff !important; }

    /* Per-employee collapse — when the toggle is clicked, the <tr> gets
       .emp-collapsed and we hide every <td> except the header (first
       child) so only the employee name strip stays visible, like a
       collapsed accordion item. */
    .table-createDutymonthly tbody tr.emp-collapsed > td:not(:first-child) { display: none !important; }
    .table-createDutymonthly .emp-collapse-toggle { color: #014653; font-size: 14px; }
    .table-createDutymonthly tbody tr.emp-collapsed .emp-collapse-toggle .fa-chevron-up { display: none; }
    .table-createDutymonthly tbody tr:not(.emp-collapsed) .emp-collapse-toggle .fa-chevron-down { display: none; }

    /* Date label inside each calendar day cell. Day number is the
       prominent figure; weekday abbreviation sits next to it. Without
       this the cell only showed "No Shift Assigned" with no way to
       tell which day it was. */
    .table-createDutymonthly .day-cell-date {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
        padding-bottom: 3px;
        border-bottom: 1px dashed #d8dde3;
    }
    .table-createDutymonthly .day-cell-date .day-num {
        font-weight: 700;
        font-size: 13px;
        color: #014653;
    }
    /* Redundant now that dates align under a Sun–Sat header row per
       employee (see .duty-roster-dow-header below) — column position
       already says which weekday this is. */
    .table-createDutymonthly .day-cell-date .day-name {
        display: none;
    }
    /* Overrides a site-wide rule (resorts/layouts/css.blade.php:
       `.public-holiday-header, .public-holiday-cell { background-color:
       rgba(255,90,87,.45) !important; }`, shared by several other duty
       roster pages) that otherwise washes every holiday-date cell in a
       loud solid red regardless of whether anyone actually worked that
       day. Scoped to this page only — a red border on the shift card is
       the "worked on a holiday" signal (see .holiday-worked below,
       matching the reference mockup); merely being a holiday date isn't
       itself notable, so it only gets this quiet header accent. */
    .table-createDutymonthly .day-cell.public-holiday-cell {
        background-color: transparent !important;
    }
    .table-createDutymonthly .public-holiday-cell .day-cell-date {
        border-bottom-color: #f1aeb5;
    }
    .table-createDutymonthly .public-holiday-cell .day-cell-date .day-num {
        color: #c92a2a;
    }
    /* Quieter "No Shift Assigned" text — the empty-state shouldn't
       shout in every cell of an unscheduled employee. */
    .table-createDutymonthly .createDuty-empty {
        font-size: 9px;
        color: #adb5bd;
        font-style: italic;
        text-align: center;
        padding: 4px 0;
    }
    .table-createDutymonthly .createDuty-tableBlock {
        margin-top: 2px;
    }

    /* "No Records Found" cell that spanned 33 cols originally — let it
       span full grid so it doesn't squish into one column. */
    .table-createDutymonthly tbody tr > td[colspan] { grid-column: 1 / -1; min-height: auto; text-align: center; }

    /* ---- Weekday header (Sun–Sat), once per employee, above their day
       grid — paired with grid-column-start on each grid's first day-cell
       (set from $firstDayOfMonthColumn in the template) so day 1 lands
       under its real weekday and every date stays in that same column
       for the rest of the month, instead of the grid always starting
       day 1 in column 1 regardless of what weekday it falls on. ---- */
    .table-createDutymonthly .duty-roster-dow-header {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed #e6e6e6;
    }
    .table-createDutymonthly .duty-roster-dow-header span {
        text-align: center;
        font-size: 9px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #6c757d;
    }

    /* ---- Legend: one static key above the accordion, shared by every
       department/section table below it rather than repeated per-table. ---- */
    .duty-roster-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 18px;
        font-size: 11px;
        color: #6c757d;
        padding: 2px 4px 16px;
    }
    .duty-roster-legend .dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: -1px;
    }
    .duty-roster-legend .dot-blue { background: #014653; }
    .duty-roster-legend .dot-yellow { background: #FED049; }
    .duty-roster-legend .dot-skyblue { background: #2EACB3; }
    .duty-roster-legend .dot-purple { background: #9E5CF7; }
    .duty-roster-legend .dot-off { background: #495057; }
    .duty-roster-legend .dot-unassigned { background: transparent; border: 1.5px dashed #adb5bd; }
    .duty-roster-legend .dot-holiday { background: transparent; border: 1.5px solid #dc3545; }
    .duty-roster-legend .dot-today { background: transparent; border: 1.5px solid #014653; }

    /* ==================================================================
       Department / Section accordion — scoped to #accordionDutyRoster
       (the page's own instance) rather than editing the shared
       .viewBudget-accordion rules in default.css, which several other
       pages also use. Reuses the app's existing badge-themeBlue /
       badge-themeSkyblue utility classes instead of introducing new
       color tokens. ================================================== */
    #accordionDutyRoster .department-accordion {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }
    #accordionDutyRoster .department-accordion .accordion-button {
        background: #fff;
        padding: 14px 16px;
        gap: 4px;
    }
    #accordionDutyRoster .department-accordion .accordion-button:hover {
        background: #f8fafa;
    }
    #accordionDutyRoster .department-accordion .accordion-button:not(.collapsed) {
        background: #f5f9f9;
        border-bottom: 1px solid #e9ecef;
    }
    #accordionDutyRoster .department-accordion .accordion-button h3 {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.3;
        margin: 0;
        color: #1a1a1a;
    }
    /* Bootstrap's chevron is disabled on the open state by the shared
       .viewBudget-accordion rule (background-image:none) — re-enabled
       here so an expanded department still shows a (flipped) chevron
       instead of no indicator at all. */
    #accordionDutyRoster .accordion-button:not(.collapsed)::after {
        background-image: var(--bs-accordion-btn-active-icon, var(--bs-accordion-btn-icon));
        transform: rotate(-180deg);
    }
    #accordionDutyRoster .accordion-body {
        background: #fcfcfd;
        padding: 14px !important;
    }

    /* Circular icon badge in front of the department/section name —
       brand teal for departments, skyblue for the nested section level
       so the hierarchy reads at a glance. */
    .duty-roster-accordion-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 50%;
        background: #01465314;
        color: #014653;
        font-size: 13px;
        margin-right: 10px;
    }
    .duty-roster-accordion-icon-sky {
        background: #2EACB31A;
        color: #2EACB3;
    }

    /* Section level (nested inside a department) — same card language,
       one size down, indented via the existing ms-3 utility already on
       the wrapper. */
    #accordionDutyRoster .section-accordion .accordion-item {
        border: 1px solid #edf0f2;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    #accordionDutyRoster .section-accordion .accordion-button {
        background: #fff;
        padding: 10px 14px;
    }
    #accordionDutyRoster .section-accordion .accordion-button:hover {
        background: #f8fafa;
    }
    #accordionDutyRoster .section-accordion .accordion-button:not(.collapsed) {
        background: #f5fafa;
        border-bottom: 1px solid #edf0f2;
    }
    #accordionDutyRoster .duty-roster-section-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a1a1a;
    }
    #accordionDutyRoster .section-accordion .accordion-body {
        background: #fff;
        padding: 10px !important;
    }

    /* ==================================================================
       Unify each day-cell with its shift card into ONE colored square —
       matching the reference mockup's single flat `.cell`, rather than a
       white card nested inside a separate outer cell. Uses :has() so the
       PHP loop stays untouched: the color classes already sit on the
       inner .createDuty-tableBlock (from Common::shiftNameColor()); we
       just also react to their presence on the ancestor day-cell.
       ================================================================== */
    /* border-bottom-color !important below: a pre-existing global rule
       (`.table td { border-bottom: 1px solid #e7e7e7 !important; }`,
       for DataTables styling elsewhere) otherwise pins every cell's
       bottom edge to gray regardless of what's set here. */
    /* background: ... !important below: needs to win over the
       .public-holiday-cell transparent override above (equal
       specificity, so it's a source-order tie-break) so a colored work
       shift keeps its own tint even when it also falls on a holiday —
       the holiday-worked ring layers on top separately. */
    .table-createDutymonthly .day-cell:has(.createDuty-blue) {
        background: color-mix(in srgb, #014653 16%, #fff) !important;
        border-color: color-mix(in srgb, #014653 40%, transparent);
        border-bottom-color: color-mix(in srgb, #014653 40%, transparent) !important;
    }
    .table-createDutymonthly .day-cell:has(.createDuty-yellow) {
        background: color-mix(in srgb, #FED049 28%, #fff) !important;
        border-color: color-mix(in srgb, #FED049 55%, transparent);
        border-bottom-color: color-mix(in srgb, #FED049 55%, transparent) !important;
    }
    .table-createDutymonthly .day-cell:has(.createDuty-skyBlue) {
        background: color-mix(in srgb, #2EACB3 20%, #fff) !important;
        border-color: color-mix(in srgb, #2EACB3 45%, transparent);
        border-bottom-color: color-mix(in srgb, #2EACB3 45%, transparent) !important;
    }
    .table-createDutymonthly .day-cell:has(.createDuty-purple) {
        background: color-mix(in srgb, #9E5CF7 16%, #fff) !important;
        border-color: color-mix(in srgb, #9E5CF7 45%, transparent);
        border-bottom-color: color-mix(in srgb, #9E5CF7 45%, transparent) !important;
    }

    /* ---- Day Off — must read as louder/more solid than a work shift,
       not muted gray text on an empty card (that was the prior flawed
       design). The whole day square goes solid-filled, like the mockup's
       Day Off block, not just an inner card. ---- */
    .table-createDutymonthly .day-cell:has(.dayoff-cell) {
        background: #495057 !important;
        border-color: #495057;
        border-bottom-color: #495057 !important;
    }
    .table-createDutymonthly .day-cell:has(.dayoff-cell) .day-num,
    .table-createDutymonthly .day-cell:has(.dayoff-cell) .day-name {
        color: #fff;
    }
    .table-createDutymonthly .day-cell:has(.dayoff-cell) .day-cell-date {
        border-bottom-color: rgba(255,255,255,0.3);
    }
    .table-createDutymonthly .createDuty-dayoff { color: #fff; font-weight: 600; }
    .table-createDutymonthly .day-cell:has(.dayoff-cell) .editIcon-btn { color: #fff; }

    /* ---- Unassigned — distinct from Day Off: dashed border, no fill, so
       a scheduling gap never reads as "planned rest." ---- */
    .table-createDutymonthly .day-cell:has(.createDuty-unassigned) {
        background: transparent;
        border-style: dashed;
        border-color: #ced4da;
        border-bottom: 1px dashed #ced4da !important;
    }

    /* ---- Off-day/holiday worked — a shift landing on a gazetted public
       holiday is an exception worth surfacing. Layered as a ring on top
       of whichever shift-type tint/background is already present
       (including the site-wide public-holiday-cell tint on the date
       header), not replacing it. ---- */
    .table-createDutymonthly .day-cell:has(.holiday-worked) {
        box-shadow: 0 0 0 1.5px #dc3545 inset;
    }

    /* ---- Today — ring + tag, entirely missing from the original design.
       White gap + brand-color ring mirrors the mockup's dark-theme
       double-ring translated to a light background. ---- */
    .table-createDutymonthly .today-cell {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #014653;
    }
    /* Inline, not a corner overlay — the header row already fills both
       top corners with day-num (left) and day-name (right), so an
       absolute badge here would sit on top of that text. */
    .table-createDutymonthly .today-tag {
        font-size: 7px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
        background: #014653;
        padding: 1.5px 5px;
        border-radius: 3px;
    }

    /* ---- Shift time must never wrap to two lines (prior regression);
       truncate instead if the cell is too narrow. ---- */
    .table-createDutymonthly .createDuty-tableBlock .d-flex p {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ---- OT chip — floating corner badge like the mockup, only for the
       branch with actual planned overtime (> 0). The "0 hr" branch is
       hidden entirely rather than shown as plain text, so an ordinary
       shift with no OT doesn't carry a redundant "OT: 0 hr" line the
       mockup never had. ---- */
    /* Inline pill in its existing row (below the shift type/time), not a
       corner overlay — the day-cell's top-right corner is already the
       day-name text from the header row above. */
    .table-createDutymonthly .ot-chip {
        display: inline-block;
        background: #FED049;
        color: #4a3b09;
        font-weight: 700;
        font-size: 9px;
        padding: 1.5px 6px;
        border-radius: 5px;
    }
    .table-createDutymonthly .ot-none { display: none; }

    /* ==================================================================
       Individual / Department view toggle + Department View table.
       Client-side only — no new routes/controller calls. The controller
       already fetches the whole month; Department View just re-displays
       the same $departmentViewEmployees data (collected alongside the
       existing per-day loop above) as one compact row per employee,
       paged one calendar week at a time via #accordionDutyRoster's
       data-active-week attribute. ================================== */
    .duty-roster-view-toggle {
        display: flex;
        background: #f1f3f5;
        border-radius: 10px;
        padding: 3px;
        gap: 2px;
    }
    .duty-roster-view-btn {
        border: none;
        background: transparent;
        color: #6c757d;
        font-size: 12.5px;
        font-weight: 600;
        padding: 7px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-family: inherit;
    }
    .duty-roster-view-btn.active {
        background: #fff;
        color: #014653;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.08);
    }
    .duty-roster-week-nav {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 0 4px 14px;
        color: #1a1a1a;
        font-size: 13px;
        font-weight: 600;
    }
    .duty-roster-week-arrow {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid #e9ecef;
        background: #fff;
        color: #495057;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 15px;
        line-height: 1;
    }
    .duty-roster-week-arrow:hover { background: #f8fafa; }
    .duty-roster-week-arrow:disabled { opacity: 0.35; cursor: not-allowed; }

    /* ---- Department table shell ---- */
    .duty-roster-dept-table { table-layout: fixed; width: 100%; }
    .duty-roster-dept-table thead tr[data-cal-week] { display: none; }
    .duty-roster-dept-table tbody td[data-cal-week] { display: none; }
    /* One pair of rules per possible calendar week (0–5 covers every
       month regardless of which weekday it starts on) — toggled by
       setting data-active-week on the shared #accordionDutyRoster
       ancestor, so every department/section table on the page pages
       together from one control. */
    #accordionDutyRoster[data-active-week="0"] .duty-roster-dept-table thead tr[data-cal-week="0"] { display: table-row; }
    #accordionDutyRoster[data-active-week="0"] .duty-roster-dept-table tbody td[data-cal-week="0"] { display: table-cell; }
    #accordionDutyRoster[data-active-week="1"] .duty-roster-dept-table thead tr[data-cal-week="1"] { display: table-row; }
    #accordionDutyRoster[data-active-week="1"] .duty-roster-dept-table tbody td[data-cal-week="1"] { display: table-cell; }
    #accordionDutyRoster[data-active-week="2"] .duty-roster-dept-table thead tr[data-cal-week="2"] { display: table-row; }
    #accordionDutyRoster[data-active-week="2"] .duty-roster-dept-table tbody td[data-cal-week="2"] { display: table-cell; }
    #accordionDutyRoster[data-active-week="3"] .duty-roster-dept-table thead tr[data-cal-week="3"] { display: table-row; }
    #accordionDutyRoster[data-active-week="3"] .duty-roster-dept-table tbody td[data-cal-week="3"] { display: table-cell; }
    #accordionDutyRoster[data-active-week="4"] .duty-roster-dept-table thead tr[data-cal-week="4"] { display: table-row; }
    #accordionDutyRoster[data-active-week="4"] .duty-roster-dept-table tbody td[data-cal-week="4"] { display: table-cell; }
    #accordionDutyRoster[data-active-week="5"] .duty-roster-dept-table thead tr[data-cal-week="5"] { display: table-row; }
    #accordionDutyRoster[data-active-week="5"] .duty-roster-dept-table tbody td[data-cal-week="5"] { display: table-cell; }

    .duty-roster-dept-table th,
    .duty-roster-dept-table td { vertical-align: middle; }
    .duty-roster-dept-table thead th {
        background: #f5f8f8;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        font-weight: 600;
        text-align: center;
        padding: 16px 6px;
    }
    .duty-roster-dept-table thead th .dnum {
        display: block;
        font-size: 13px;
        text-transform: none;
        letter-spacing: 0;
        color: #1a1a1a;
        margin-top: 2px;
    }
    .duty-roster-dept-table thead th.today-col { background: #e9f4f4; }
    .duty-roster-dept-table thead th.today-col .dnum { color: #014653; }

    /* Sticky employee column, per the reference spec ("first column is
       sticky/frozen ... so it stays visible when scrolling horizontally"). */
    .duty-roster-dept-table .dept-emp-col {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #fff;
        min-width: 220px;
        text-align: left;
        /* Matches .duty-roster-dept-cell's padding (the gap between the
           table border and a shift-color box, e.g. the Afternoon Shift
           card) so the employee column reads as part of the same row
           rhythm as every day cell next to it. */
        padding: 4px;
    }
    .duty-roster-dept-table thead .dept-emp-col { z-index: 3; background: #f5f8f8; }
    .duty-roster-dept-table .dept-emp-col .img-circle {
        width: 32px;
        height: 32px;
        min-width: 32px;
        margin-right: 8px;
    }
    .duty-roster-dept-table .dept-emp-col p { margin-bottom: 1px; }
    .duty-roster-dept-table .dept-emp-col span { font-size: 11px; }
    .duty-roster-dept-emp-link { cursor: pointer; }
    .duty-roster-dept-emp-link:hover { background: #f8fafa; }

    /* ---- Compact day cell — smaller, denser version of the same
       states used in Individual View (colors/off/unassigned/leave),
       appropriate for scanning many rows at once. ---- */
    .duty-roster-dept-cell { padding: 4px; min-width: 108px; }
    .duty-roster-dept-cell.today-cell { box-shadow: 0 0 0 1.5px #014653 inset; }
    .duty-roster-dept-cell.public-holiday-cell { background: #ff5a5712; }
    .dept-cell-inner {
        border-radius: 6px;
        padding: 5px 6px;
        min-height: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        font-size: 10px;
        border: 1px solid transparent;
    }
    .dept-cell-inner.createDuty-blue { background: color-mix(in srgb, #014653 14%, #fff); border-color: color-mix(in srgb, #014653 35%, transparent); }
    .dept-cell-inner.createDuty-yellow { background: color-mix(in srgb, #FED049 26%, #fff); border-color: color-mix(in srgb, #FED049 50%, transparent); }
    .dept-cell-inner.createDuty-skyBlue { background: color-mix(in srgb, #2EACB3 18%, #fff); border-color: color-mix(in srgb, #2EACB3 40%, transparent); }
    .dept-cell-inner.createDuty-purple { background: color-mix(in srgb, #9E5CF7 14%, #fff); border-color: color-mix(in srgb, #9E5CF7 40%, transparent); }
    .dept-cell-inner.holiday-worked { box-shadow: 0 0 0 1.5px #dc3545 inset; }
    .dept-cell-type { font-weight: 700; font-size: 10px; }
    .dept-cell-time { font-size: 9px; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dept-cell-ot-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #FED049;
        border: 1px solid #4a3b09;
        margin-top: 2px;
    }
    .dept-cell-off {
        background: #495057;
        color: #fff;
        font-weight: 600;
        font-size: 10px;
    }
    .dept-cell-unassigned {
        border: 1px dashed #ced4da;
        color: #adb5bd;
        font-style: italic;
    }
    .dept-cell-leave {
        background: #fff;
        border-width: 1.5px;
        font-weight: 600;
        color: #495057;
    }

    /* Brief highlight when Department View's employee name jumps the
       page to that person's Individual View card. */
    .duty-roster-jump-highlight {
        animation: dutyRosterJumpFlash 1.6s ease-out;
    }
    @keyframes dutyRosterJumpFlash {
        0% { box-shadow: 0 0 0 3px #014653; }
        100% { box-shadow: 0 0 0 0 transparent; }
    }
</style>
@endsection

@section('import-scripts')

<script type="text/javascript">
    // new DataTable('#example');

    // (Removed: the week-1..5 / Full Month pager. The roster now renders
    // as a single full-month calendar grid via CSS, so no JS is needed
    // to toggle which days are visible.)

    // tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });


    // Declare day off picker for modal at global scope so it's accessible to all functions
    var dayOffPickerModel = null;

    // date range picker
    $(document).ready(function ()
    {
        document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Overtime inputs are now handled in the modal, so this is removed
        var shiftOverTimePicker =  flatpickr(".shiftdate", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i", // 12-hour format without AM/PM
            time_24hr: false,  // Ensures 12-hour format
            minuteIncrement: 1, // Allows 1-minute steps

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

        // Initialize day off picker for modal
    });
        </script>
        @include('resorts.renderfiles.dutyRosterSharedScripts')
        <script type="text/javascript">

        // Initialize model picker on load
        initializeDayOffPickerModel();

        // Ensure day off picker model opens on click
        $(document).on('click', '#DayOffDatesModel', function() {
            if(dayOffPickerModel && dayOffPickerModel.isOpen !== true) {
                dayOffPickerModel.open();
            }
        });

        // Per-employee collapse: clicking the chevron flips .emp-collapsed
        // on the surrounding <tr>; CSS hides the day cells. Identical UX
        // to the dept/section accordion above, just adapted for the
        // grid-row layout we use for the monthly view.
        $(document).on("click", ".emp-collapse-toggle", function (e) {
            e.preventDefault();
            $(this).closest('tr').toggleClass('emp-collapsed');
        });

        $(document).on("click", ".editdutyRoster", function() {

            let date = $(this).attr('data-date');
            let Shift_id = $(this).attr('data-Shift_id');
            let overtime = $(this).attr('data-OverTime');
            let Attd_id = $(this).attr('data-Attd_id');
            let DayWiseTotalHours = $(this).attr('data-DayWiseTotalHours');
            $("#shiftdate").val(date);
            $("#Shiftpopup").val(Shift_id).trigger('change');

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
            // Empty cells (No Shift Assigned / Day Off) carry emp_id +
            // roster_id so the controller can create the DutyRosterEntry
            // properly. For cells that already have a roster entry, these
            // are blank — the controller falls back to the existing
            // DutyRosterEntry's own Emp_id/roster_id via Attd_id.
            $("#EditEmpId").val($(this).attr('data-emp_id') || '');
            $("#EditRosterId").val($(this).attr('data-roster_id') || '');

            // Pre-check this employee's currently-assigned geo-fence
            // zone(s) — same value every day cell for this employee
            // carries (zone assignment is roster-level, not per-day).
            var zoneIds = [];
            try { zoneIds = JSON.parse($(this).attr('data-zone_ids') || '[]'); } catch (e) { zoneIds = []; }
            $(".dr-modal-zone-checkbox").prop('checked', false);
            zoneIds.forEach(function(id) {
                $(".dr-modal-zone-checkbox[value='" + id + "']").prop('checked', true);
            });

            if (!$("#ShiftOverTime").data("flatpickr")) {
                flatpickr("#ShiftOverTime", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i", // 24-hour format (HH:MM)
                    time_24hr: true,  // Use 24-hour format to match database
                    minuteIncrement: 1, // Allows 1-minute steps
                });
            }

            // Normalize and validate overtime value coming from the cell
            // Handle cases where overtime might be "0", "00:00", "0:00", or empty
            if (!overtime || overtime === '0' || overtime === 0 || overtime === '0:00' || overtime === '00:0') {
                overtime = '00:00';
            }

            // Normalize DayWiseTotalHours to HH:MM for comparison
            let normalizedDayHours = DayWiseTotalHours || '';
            if (normalizedDayHours && normalizedDayHours.indexOf(':') === -1) {
                let dh = parseInt(normalizedDayHours) || 0;
                normalizedDayHours = String(dh).padStart(2, '0') + ':00';
            }

            // Ensure overtime is in HH:MM (24-hour). Bare number < 24 = minutes (so 20 -> 00:20, not 20:00)
            if (overtime && overtime.indexOf(':') === -1) {
                let num = parseInt(overtime, 10) || 0;
                if (num <= 0) {
                    overtime = '00:00';
                } else if (num < 24) {
                    overtime = '00:' + String(num).padStart(2, '0');
                } else {
                    overtime = String(num).padStart(2, '0') + ':00';
                }
            }

            // If stored overtime looks invalidly large compared to shift hours, treat it as "no overtime"
            if (normalizedDayHours && overtime) {
                const [sH, sM] = normalizedDayHours.split(':').map(v => parseInt(v) || 0);
                const [oH, oM] = overtime.split(':').map(v => parseInt(v) || 0);
                const shiftMinutes = sH * 60 + sM;
                const overtimeMinutes = oH * 60 + oM;

                // If overtime is equal to or greater than shift minutes (e.g., 20:00 vs 08:00), reset to 00:00
                if (shiftMinutes > 0 && overtimeMinutes >= shiftMinutes) {
                    overtime = '00:00';
                }
            }

            $("#ShiftOverTime").val(overtime);
            let fp = $("#ShiftOverTime")[0]._flatpickr;
            if (fp) {
                // Set time in local to avoid UTC->local showing 20:00 for 00:00
                const [h, m] = overtime.split(':').map(v => parseInt(v, 10) || 0);
                const d = new Date();
                d.setHours(h, m, 0, 0);
                fp.setDate(d, false);
            }
            $("#editdutyRoster-modal").modal('show');
            $("#ShiftOverTime").attr('data-DayWiseTotalHours', DayWiseTotalHours);
            calculateTotalTime(overtime,DayWiseTotalHours,flag="Modal");

        });

        $(document).on("click", ".editGeofenceZone", function() {
            var rosterId = $(this).data('roster_id');
            var zoneIds = $(this).data('zone_ids') || [];
            $("#EditGeofenceRosterId").val(rosterId);
            $(".edit-gf-zone-checkbox").prop('checked', false);
            zoneIds.forEach(function(id) {
                $(".edit-gf-zone-checkbox[value='" + id + "']").prop('checked', true);
            });
            $("#editGeofenceZone-modal").modal('show');
        });

        $('#UpdateDutyRosterGeofence').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "{{ route('resort.timeandattendance.UpdateDutyRosterGeofence') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $("#editGeofenceZone-modal").modal('hide');
                        setTimeout(function() { window.location.reload(); }, 1500);
                    } else {
                        toastr.error(response.message, "error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function() {
                    toastr.error("Something went wrong", "error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#UpdateDutyRoster').validate({
                rules: {
                    shiftdate: {
                        required: true,
                    },
                    Shiftpopup: {
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

                                // Save geo-fence zone selection alongside the
                                // shift/day-off update — response.roster_id
                                // is the reliable one (the create-on-edit
                                // path only resolves a real roster_id here,
                                // not necessarily from the form's own hidden
                                // field).
                                if ($(".dr-modal-zone-checkbox").length && response.roster_id) {
                                    var zoneIds = $(".dr-modal-zone-checkbox:checked").map(function() {
                                        return $(this).val();
                                    }).get();
                                    $.ajax({
                                        url: "{{ route('resort.timeandattendance.UpdateDutyRosterGeofence') }}",
                                        type: "POST",
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            roster_id: response.roster_id,
                                            geofence_zone_ids: zoneIds
                                        }
                                    });
                                }

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

    // calculateTotalTime() (used by the modal / edit duty roster flow) now
    // comes from the shared dutyRosterSharedScripts partial included above.

</script>

<script>
(function () {
    'use strict';

    var accordion = document.getElementById('accordionDutyRoster');
    var weekNav = document.getElementById('dutyRosterWeekNav');
    var weekLabelEl = document.getElementById('dutyRosterWeekLabel');
    var weekPrevBtn = document.getElementById('dutyRosterWeekPrev');
    var weekNextBtn = document.getElementById('dutyRosterWeekNext');
    if (!accordion || !weekNav) { return; }

    var weekLabels = {};
    try { weekLabels = JSON.parse(weekNav.dataset.weekLabels || '{}'); } catch (e) { weekLabels = {}; }
    var weekCount = parseInt(weekNav.dataset.weekCount, 10) || 1;
    var currentWeek = parseInt(weekNav.dataset.initialWeek, 10) || 0;

    function renderWeekNav() {
        accordion.setAttribute('data-active-week', String(currentWeek));
        weekLabelEl.textContent = weekLabels[String(currentWeek)] || ('Week ' + (currentWeek + 1));
        weekPrevBtn.disabled = currentWeek <= 0;
        weekNextBtn.disabled = currentWeek >= weekCount - 1;
    }

    weekPrevBtn.addEventListener('click', function () {
        if (currentWeek > 0) { currentWeek--; renderWeekNav(); }
    });
    weekNextBtn.addEventListener('click', function () {
        if (currentWeek < weekCount - 1) { currentWeek++; renderWeekNav(); }
    });

    // Individual view / Department view toggle — purely client-side,
    // swaps which already-rendered tables are visible. No new requests.
    var viewButtons = document.querySelectorAll('.duty-roster-view-btn');
    function setView(view) {
        viewButtons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-duty-view') === view);
        });
        document.querySelectorAll('.duty-roster-individual-view').forEach(function (el) {
            el.classList.toggle('d-none', view === 'department');
        });
        document.querySelectorAll('.duty-roster-department-view').forEach(function (el) {
            el.classList.toggle('d-none', view !== 'department');
        });
        weekNav.classList.toggle('d-none', view !== 'department');
    }
    viewButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setView(btn.getAttribute('data-duty-view'));
        });
    });

    // Department View employee name -> jump to that person's Individual
    // View card: switch view, expand every ancestor accordion, scroll,
    // briefly highlight. Per the reference spec, this is the primary way
    // a manager moves from "scanning the week" to "investigating one
    // person's month" after spotting something.
    function jumpToEmployee(empId) {
        setView('individual');
        var target = document.getElementById('duty-roster-emp-' + empId);
        if (!target) { return; }

        var ancestors = [];
        var el = target.closest('.collapse');
        while (el) {
            ancestors.push(el);
            var parent = el.parentElement;
            el = parent ? parent.closest('.collapse') : null;
        }
        ancestors.forEach(function (collapseEl) {
            if (!collapseEl.classList.contains('show') && window.bootstrap && window.bootstrap.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
            }
        });

        setTimeout(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('duty-roster-jump-highlight');
            setTimeout(function () { target.classList.remove('duty-roster-jump-highlight'); }, 1700);
        }, ancestors.length ? 350 : 0);
    }

    document.querySelectorAll('.duty-roster-dept-emp-link').forEach(function (el) {
        el.addEventListener('click', function () {
            jumpToEmployee(el.getAttribute('data-jump-emp'));
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                jumpToEmployee(el.getAttribute('data-jump-emp'));
            }
        });
    });

    renderWeekNav();
})();
</script>
@endsection

