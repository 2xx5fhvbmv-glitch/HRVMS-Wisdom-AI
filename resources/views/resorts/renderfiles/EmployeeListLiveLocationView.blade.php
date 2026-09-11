<div class="sosResortMap-block">
@if($employeesStatusList->isNotEmpty())
    @foreach ($employeesStatusList as $employeeStatus)
        @if ($employeeStatus->employee)
            <div class="d-flex {{ ($employeeStatus->status == 'Unsafe' || $employeeStatus->status == 'Unknown') ? 'unsafe' : '' }}">
                <div class="img-circle me-2">
                @if ($employeeStatus->employee->Admin_Parent_id && $employeeStatus->employee->Admin_Parent_id != '')
                    <img src="{{ Common::getResortUserPicture($employeeStatus->employee->Admin_Parent_id) }}" alt="user">
                @else
                    <img src="{{ url(config('settings.default_picture')) }}" alt="No user">
                @endif
                </div>
                <div>
                    <h6>{{ optional($employeeStatus->employee->resortAdmin)->full_name }} <span class="badge badge-themeNew">
                        {{ $employeeStatus->employee->Emp_id }}
                    </span> </h6>
                    <p>{{ optional(optional($employeeStatus->employee)->position)->short_title ?? 'N/A' }} • {{ optional(optional($employeeStatus->employee)->department)->name ?? 'N/A' }}</p>
                </div>
                <div>
                    {{-- 3-way status (Safe/Unsafe/Unknown) — was 2-way, Unsafe and
                         Unknown shared the same red badge, making "hasn't responded
                         yet" visually identical to "in danger". --}}
                    @if($employeeStatus->status == 'Safe')
                    <span class="badge badge-themeSuccess">
                        {{ $employeeStatus->status }}
                    </span>
                    @elseif($employeeStatus->status == 'Unsafe')
                    <span class="badge badge-themeDanger">
                        {{ $employeeStatus->status }}
                    </span>
                    @else
                    <span class="badge badge-themeWarning">
                        {{ $employeeStatus->status }}
                    </span>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
@else
    <div class="col-md-12">
        <p class="text-center"><b>No Records Found...</b></p>
    </div>
@endif
</div>