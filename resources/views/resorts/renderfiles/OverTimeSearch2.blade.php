<div class="monthly-main">
    <div class="table-responsive mb-4">
        <table id="" class="table table-bordered table-overtimemonthly mb-1">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    @if(!empty($monthwiseheaders))
                        @foreach ($monthwiseheaders as $h)
                            @php
                                $currentDate = isset($h['date']) ? $h['date'] : (date('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                $isPublicHoliday = isset($publicHolidays) && in_array($currentDate, $publicHolidays);
                            @endphp
                            <th class="{{ $isPublicHoliday ? 'public-holiday-header' : '' }}">{{ $h['day'] }} <span>{{ $h['dayname'] }}</span></th>
                        @endforeach
                    @endif
                    <th>Holiday OT</th>
                    <th>Regular OT</th>
                    <th>Total OT</th>
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
                            // Fetch overtime data from employee_overtimes table
                             $overtimeData = DB::table('duty_roster_entries')
                        ->where('Emp_id', $r->emp_id)
                        ->where('resort_id', $resort_id)
                        ->whereBetween('date', [
                            $startOfMonth->format('Y-m-d'),
                            $endOfMonth->format('Y-m-d')
                        ])
                        ->where('OverTime', '!=', '00:00')
                        ->orderBy('date', 'asc')
                        ->get();
                            
                           // Group overtime by date
                            $overtimeByDate = $overtimeData->groupBy(function($item) {
                                return $item->date;
                            });
                            
                            $totalMonthWiseHours = 0;
                            $holidayOtMonthly = 0;
                            $regularOtMonthly = 0;
                            
                            foreach($overtimeData as $ot) {
                               $dayName = \Carbon\Carbon::parse($ot->date)->format('D');
                                list($hours, $minutes) = explode(':', $ot->OverTime ?? '0:0');
                                $totalOtHours = (int)$hours + ((int)$minutes / 60);
                                
                                if($dayName == "Fri" || (isset($publicHolidays) && in_array($ot->date, $publicHolidays))) {
                                    $holidayOtMonthly += $totalOtHours;
                                } else {
                                    $regularOtMonthly += $totalOtHours;
                                }
                                $totalMonthWiseHours += $totalOtHours;
                            }
                        @endphp

                        <!-- Loop through each monthwise header for the status per day -->
                        @foreach ($monthwiseheaders as $h)
                            @php
                                $date = isset($h['date']) ? $h['date'] : ($startOfMonth->format('Y-m') . '-' . str_pad($h['day'], 2, '0', STR_PAD_LEFT));
                                $isPublicHoliday = isset($publicHolidays) && in_array($date, $publicHolidays);
                                $dayOvertimes = $overtimeByDate->get($date, collect());
                                $overtimeCount = $dayOvertimes->count();
                                
                                // Calculate total overtime for the day
                                $dayTotalMinutes = 0;
                                $hasPending = false;
                                $hasRejected = false;
                                $hasApproved = false;
                                
                                foreach($dayOvertimes as $ot) {
                                    list($hours, $minutes) = explode(':', $ot->total_time ?? '0:0');
                                    $dayTotalMinutes += (int)$hours * 60 + (int)$minutes;
                                    
                                    if($ot->OTStatus == 'pending') $hasPending = true;
                                    if($ot->OTStatus == 'rejected') $hasRejected = true;
                                    if($ot->OTStatus == 'approved') $hasApproved = true;
                                }
                                
                                $dayTotalHours = floor($dayTotalMinutes / 60);
                                $dayTotalMins = $dayTotalMinutes % 60;
                                $dayTotalTime = sprintf('%02d:%02d', $dayTotalHours, $dayTotalMins);
                                
                                // Determine status color priority: pending > rejected > approved
                                $statusColor = '';
                                if($hasPending) {
                                    $statusColor = 'status-pending';
                                } elseif($hasRejected) {
                                    $statusColor = 'status-rejected';
                                } elseif($hasApproved) {
                                    $statusColor = 'status-approved';
                                }
                            @endphp

                            <td class="{{ $isPublicHoliday ? 'public-holiday-cell' : '' }} @if($overtimeCount > 0) has-overtime {{ $statusColor }} @endif"
                                data-date="{{ $date }}"
                                data-emp-id="{{ $r->emp_id }}"
                                style="cursor: pointer;"
                                >
                                @if($overtimeCount > 0)
                                    <span class="overtime-total">{{ $ot->OverTime }}</span>
                                @else
                                    <span class="no-overtime">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td>{{ number_format($holidayOtMonthly, 2) }}</td>
                        <td>{{ number_format($regularOtMonthly, 2) }}</td>

                        <td><span>{{ number_format($totalMonthWiseHours, 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            @endif

            </tbody>
        </table>
    </div>
    <div class="pagination-custom">
        {!! $Rosterdata->links('pagination::bootstrap-4') !!}
      </div>
</div>
