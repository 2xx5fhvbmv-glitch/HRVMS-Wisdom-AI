<div class="monthly-main">
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
                            <i class="fas fa-building me-2"></i>
                            <h3>{{ $deptData['dept_name'] }}</h3>
                            <span class="badge badge-dark ms-2 small">
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
                                    {{-- Level 2: Section --}}
                                    <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                        aria-expanded="false" aria-controls="collapseSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                    <i class="fas fa-layer-group me-2"></i>
                                                    <span>{{ $sectionData['section_name'] }}</span>
                                                    <span class="badge badge-dark ms-2 small">Employees: {{ count($sectionData['employees']) }}</span>
                                                </button>
                                            </h2>
                                            <div id="collapseSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                 class="collapse"
                                                 aria-labelledby="headingSec{{ $deptIteration }}_{{ $sectionIteration }}"
                                                 data-bs-parent="#accordionSec{{ $deptIteration }}_{{ $sectionIteration }}">
                                                <div class="accordion-body p-2">
                                                    {{-- Employee Roster Table for Section --}}
                                                    <div class="table-responsive mb-4">
                                                        <table class="table table-bordered table-createDutymonthly mb-1">
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
                                                                    <th>Summary</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if(!empty($sectionData['employees']))
                                                                    @foreach ($sectionData['employees'] as $r)
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
                                                                                $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                                                $totalHoursMonth = 0;
                                                                            @endphp

                                                                            @foreach ($monthwiseheaders as $h)
                                                                            @php
                                                                                $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                                                $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                                                $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);

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
                                                                @else
                                                                    <tr>
                                                                        <td colspan="{{ count($monthwiseheaders) + 2 }}" style="text-align: center">No Records Found..</td>
                                                                    </tr>
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @php $sectionIteration++; @endphp
                                @endforeach
                            @endif

                            {{-- Direct Employees under Department (no section) --}}
                            @if(!empty($deptData['employees']))
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-createDutymonthly mb-1">
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
                                                <th>Summary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deptData['employees'] as $r)
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
                                                        $RosterInternalDataMonth = Common::GetRosterdata($resort_id, $r->duty_roster_id, $r->emp_id, $WeekstartDate, $WeekendDate, $startOfMonth, $endOfMonth, "Monthwise");
                                                        $totalHoursMonth = 0;
                                                    @endphp

                                                    @foreach ($monthwiseheaders as $h)
                                                    @php
                                                        $formattedDate = \Carbon\Carbon::parse($h['date'])->format('Y-m-d');
                                                        $isPublicHoliday = isset($publicHolidays) && in_array($formattedDate, $publicHolidays);
                                                        $shiftData = $RosterInternalDataMonth->firstWhere('date', $formattedDate);

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

