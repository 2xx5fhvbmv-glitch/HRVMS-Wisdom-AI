@if($vacant_positions->isNotEmpty())
    @foreach($vacant_positions as $pos)
        <tr>
            <td>{{ $pos->position_title }}</td>
            <td> {{ $pos->headcount ?? '00' }} <span class="badge bg-vac">{{ $pos->vacantcount ?? '00' }} Vacant Available</span></td>
            <td></td>
            <td></td>
            <td>
                <button class="table-icon collapsed" data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{$pos->id}}" aria-expanded="false"
                    aria-controls="collapse-{{$pos->id}}">
                    <i class="fa-solid fa-angle-down"></i>
                </button>
            </td>
        </tr>

        <!-- Collapsed row for employees -->
        @if($pos->employees && count($pos->employees) > 0)
            @foreach($pos->employees as $employee)
                <tr class="collapse" id="collapse-{{$pos->id}}">
                    <td></td>
                    <td>
                        <div class="user-block">
                            <div class="img-circle">
                                <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id);}}" alt="image">
                            </div>
                            <h6>{{ $employee->first_name }} {{ $employee->last_name }}</h6>
                        </div>
                    </td>
                    @php
                        // Initial page load maps rank -> label (hoddashboard.blade.php),
                        // but this AJAX-refreshed partial (year dropdown) never did,
                        // so switching years showed raw numbers instead of HOD/XCOM/GM/etc.
                        $RankLabels = config('settings.Position_Rank');
                        $RankLabel = array_key_exists($employee->rank, $RankLabels) ? $RankLabels[$employee->rank] : $employee->rank;
                    @endphp
                    <td>{{ $RankLabel }}</td>
                    <td>{{ $employee->nationality }}</td>
                    <td></td>
                </tr>
            @endforeach
        @endif
    @endforeach
@else

    <tr>
        <td colspan="5" style="text-align: center;">No positions available.</td>
    </tr>

@endif
