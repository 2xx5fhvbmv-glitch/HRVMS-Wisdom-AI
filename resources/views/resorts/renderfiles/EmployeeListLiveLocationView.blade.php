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
                    <h6>{{ $employeeStatus->employee->resortAdmin->full_name }} <span class="badge badge-themeNew">
                        {{ $employeeStatus->employee->Emp_id }} 
                    </span> </h6>
                    <p>{{ $employeeStatus->employee->position->short_title }} • {{ $employeeStatus->employee->department->name }}</p>
                </div>
                <div>
                    @if($employeeStatus->status == 'Safe')
                    <span class="badge badge-themeSuccess">
                        {{ $employeeStatus->status }}
                    </span>
                    @else
                    <span class="badge badge-themeDanger">
                        {{ $employeeStatus->status }}
                    </span>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
@else
    <div class="col-md-12">
        <p class="text-center"><b>No records Found...</b></p>
    </div>
@endif
</div>