<div class="row g-md-4 g-3 mb-4">
    @if($employees->isNotEmpty())
        @foreach ($employees as $e)
            <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                <div class="employeesGrid-block">
                    <div class="img-circle"><img src=" {{$e->profile_picture}}" alt="image"></div>
                    <h6>{{ $e->name }}</h6>
                    <span class="badge badge-themeLight">{{ $e->Emp_id }}</span>
                    <p>{{ $e->Position }}</p>
                    <div class="bg">
                        <div>
                            <p>Leave</p>
                            <p>{{ $e->Leave }}</p>
                        </div>
                        <div>
                            <p>Absent</p>
                            <p>{{ $e->Absent }}</p>
                        </div>
                        <div>
                            <p>Present</p>
                            <p>{{ $e->Present }}</p>
                        </div>
                        <div>
                            <p>Dayoff</p>
                            <p>{{ $e->Dayoff }}</p>
                        </div>
                    </div>
                    <div class="employees-progress">
                        <span>Working Days</span>
                        <span>{{ $e->Present }}/{{ $e->TotalWorkingDays - $e->TotalDayoff }}</span>
                        <div class="progress progress-custom progress-themeBlue">
                            @php
                                // Calculate percentage for working days
                                $workingDaysProgress = $e->TotalWorkingDays - $e->TotalDayoff > 0
                                    ? ($e->Present / ($e->TotalWorkingDays - $e->TotalDayoff)) * 100
                                    : 0;
                            @endphp
                            <div class="progress-bar" role="progressbar" style="width: {{ $workingDaysProgress }}%"
                                aria-valuenow="{{ $workingDaysProgress }}"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="employees-progress">
                        <span>Day Offs</span>
                        <span>{{ $e->Dayoff }}/{{ $e->TotalDayoff }}</span>
                        <div class="progress progress-custom progress-themeBlue">
                            @php
                                // Calculate percentage for day offs
                                $dayOffProgress = $e->TotalDayoff > 0
                                    ? ($e->Dayoff / $e->TotalDayoff) * 100
                                    : 0;
                            @endphp
                            <div class="progress-bar" role="progressbar" style="width: {{ $dayOffProgress }}%"
                                aria-valuenow="{{ $dayOffProgress }}"
                                aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div>
                          <a target="_blank" href="{{ route('resort.timeandattendance.employee.details', [ base64_encode($e->employee_id)]); }}" class="btn btn-themeSkyblue btn-sm" data-id="' . $row->id . '">Details</a>
                    </div>

                </div>
            </div>
        @endforeach
    @else
        <div class="col-md-12">
            <p class="text-center"><b>No Data Found...</b></p>
        </div>
    @endif

</div>
<div class="pagination-custom">
    <nav aria-label="Page navigation example">
        {!! $employees->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}
    </nav>
</div>
