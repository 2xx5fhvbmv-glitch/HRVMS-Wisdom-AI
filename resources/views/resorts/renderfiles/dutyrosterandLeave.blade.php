<div class="drc-emp-detail-card">
    <div class="drc-emp-detail-header">
        <span class="drc-avatar drc-avatar-{{ $employees->id % 6 }}">
            <img src="{{ Common::getResortUserPicture($employees->Parentid) }}" alt="user">
        </span>
        <div class="drc-emp-detail-info">
            <p class="drc-emp-detail-name">{{ ucfirst($employees->first_name . ' ' . $employees->last_name) }} <span class="drc-emp-code-badge">{{ $employees->Emp_id }}</span></p>
            <span class="drc-emp-detail-role">{{ ucfirst($employees->position_title) }}</span>
        </div>
        <button type="button" class="drc-emp-detail-remove" data-emp-id="{{ $employees->id }}" aria-label="Remove employee">&#10005;</button>
    </div>

    @if($EmployeeLeave->isNotEmpty())
        @foreach ($EmployeeLeave as $leave)
        <div class="drc-leave-box drc-leave-box-amber">
            <div class="drc-leave-box-top">
                <span class="drc-leave-type">{{ $leave->leave_type }} applied</span>
                @if(($leave->leave_status ?? null) === 'Pending')
                    <span class="drc-leave-pending">Pending Approval</span>
                @endif
            </div>
            <span class="drc-leave-dates">{{ $leave->from_date == $leave->to_date ? date("d M", strtotime($leave->from_date)) : date("d M", strtotime($leave->from_date)) . ' - ' . date("d M", strtotime($leave->to_date)) }}</span>
            @if($leave->reason)
                <span class="drc-leave-reason">{{ $leave->reason }}</span>
            @endif
        </div>
        @endforeach
    @else
        <div class="drc-leave-box drc-leave-box-none">No Leave Applied</div>
    @endif
</div>
