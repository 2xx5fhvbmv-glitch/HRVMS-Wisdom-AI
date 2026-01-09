<div class="sosteamActivity-emp mb-4">
    @if($employeesStatusList->isNotEmpty())
        @foreach ($employeesStatusList as $employeeStatus)
            @if ($employeeStatus->employee)
                <div class="d-flex {{ ($employeeStatus->status == 'Unsafe' || $employeeStatus->status == 'Unknown') ? 'unsafe' : '' }}">
                    <div class="img-circle">
                    @if ($employeeStatus->employee->Admin_Parent_id && $employeeStatus->employee->Admin_Parent_id != '')
                        <img src="{{ Common::getResortUserPicture($employeeStatus->employee->Admin_Parent_id) }}" alt="user">
                    @else
                        <img src="{{ url(config('settings.default_picture')) }}" alt="No user">
                    @endif
                    </div>
                    <div>
                        <h6>
                            @if (optional($employeeStatus->employee->resortAdmin)->full_name)
                                {{ $employeeStatus->employee->resortAdmin->full_name }}
                            @endif

                            @if (!empty($employeeStatus->employee->Emp_id))
                                <span class="badge badge-themeNew">
                                    {{ $employeeStatus->employee->Emp_id }}
                                </span> 
                            @endif
                        </h6>
                        <p> {{ optional(optional($employeeStatus->employee)->position)->short_title ?? 'N/A' }} • 
                            {{ optional(optional($employeeStatus->employee)->department)->name ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <ul>
                            <li><i class="fa-regular fa-location-dot"></i>{{ $employeeStatus->address }}</li>
                            
                            @if($employeeStatus->status == 'Safe')
                            <li class="text-themeSuccess">
                                <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" alt="Check Circle" style="width: 16px; margin-right: 5px;" />
                                {{ $employeeStatus->status }}
                            </li>
                            @else
                            <li class="text-themeDanger">
                                    <i class="fa-regular fa-circle-exclamation"></i> {{ $employeeStatus->status }}
                                </li>
                            @endif

                        </ul>
                        <span>{{ \Carbon\Carbon::parse($employeeStatus->updated_at)->diffForHumans() }}</span>
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
<div class="pagination-custom">
    <nav aria-label="Page navigation example">
        {!! $employeesStatusList->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}
    </nav>
</div>