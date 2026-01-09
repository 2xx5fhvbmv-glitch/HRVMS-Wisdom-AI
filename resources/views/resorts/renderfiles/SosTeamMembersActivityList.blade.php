<div class="sosEmpSafetyStatus-emp mb-4">
    @if($teamMembers->isNotEmpty())
        @foreach ($teamMembers as $teamMember)
            <div class="d-flex {{ $teamMember->status == 'Unacknowledged' ? 'unsafe' : '' }}">
                <div class="img-circle">
                @if ($teamMember->resortAdmin && $teamMember->resortAdmin->id)
                    <img src="{{ Common::getResortUserPicture($teamMember->resortAdmin->id) }}" alt="user">
                @else
                    <img src="{{ url(config('settings.default_picture')) }}" alt="No user">
                @endif
                </div>
                <div>
                    <h6>{{ $teamMember->resortAdmin->full_name }} <span class="badge badge-themeNew">
                    @php
                        $employee = $teamMember->resortAdmin->GetEmployee ?? null;
                    @endphp

                    @if($employee)
                        {{ $employee->Emp_id }} 
                    @endif
                    </span> </h6>
                    <p>{{ $teamMember->memberRole->name}} • {{ $teamMember->team->name ?? 'No Team Assigned' }}</p>
                </div>
                <div>
                    <ul>
                        <li><i class="fa-regular fa-location-dot"></i>{{ $teamMember->address }}</li>
                        
                        @if($teamMember->status == 'Acknowledged')
                        <li class="text-themeSuccess">
                            <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" alt="Check Circle" style="width: 16px; margin-right: 5px;" />
                            Acknowledged
                        </li>
                        @else
                        <li class="text-themeDanger">
                                <i class="fa-regular fa-circle-exclamation"></i> Not Acknowledged
                            </li>
                        @endif

                    </ul>
                    <span>{{ \Carbon\Carbon::parse($teamMember->updated_at)->diffForHumans() }}</span>
                </div>
            </div>
        @endforeach
    @else
        <div class="col-md-12">
            <p class="text-center"><b>No records Found...</b></p>
        </div>
    @endif
</div>
<div class="pagination-custom">
    <nav aria-label="Page navigation example">
        {!! $teamMembers->appends(['view' => request('view', 'grid')])->links('pagination::bootstrap-4') !!}
    </nav>
</div>