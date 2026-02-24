<div class="createDuty-user mb-md-4 mb-3">
    <div class="img-circle"><img src="{{ Common::getResortUserPicture($employees->Parentid) }}" alt="user">
    </div>
    <div>
        <p><span class="fw-600">{{ ucfirst($employees->first_name .'  '.$employees->last_name) }}</span> <span
                class="badge badge-themeLight">{{ $employees->Emp_id }}</span></p>
        <span>{{ ucfirst($employees->position_title) }}</span>
    </div>
</div>

@if($EmployeeLeave->isNotEmpty())
<div class="card-themeSkyblue">
    <p class="fw-600 mb-1">Planned Leaves</p>
    @foreach ($EmployeeLeave as $leave)
    <div class="d-flex align-items-center mb-3">
        <div class="bg-white">
            {{ date("M", strtotime($leave->from_date)) }}
            <h5>{{ date("d", strtotime($leave->from_date)) }}</h5> {{ date("D", strtotime($leave->from_date)) }}
        </div>
        <div>
            <span class="badge badge-brown mb-2">{{ $leave->leave_type }}</span>
            <p>{{ $leave->from_date == $leave->to_date ? date("d/m/Y", strtotime($leave->from_date)) : date("d/m/Y", strtotime($leave->from_date)) .' to '.date("d/m/Y", strtotime($leave->to_date)) }}   {{ $leave->reason }}</p>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card-themeSkyblue">
    <p class="mb-0">No Leave Applied</p>
</div>
@endif
