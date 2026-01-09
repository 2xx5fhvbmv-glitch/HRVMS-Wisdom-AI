@if(isset($getNotifications['FreshVacancies']) && $getNotifications['FreshVacancies']->isNotEmpty())
    @foreach ($getNotifications['FreshVacancies'] as $vacancy)

        <div class="hireReq-block">
            <div class="img-circle">
                <img src="{{ Common::getResortUserPicture($vacancy->resort_id)}}" alt="image">
            </div>
            <div>
                <h6>{{ $vacancy->Department }} ({{ $vacancy->rank_name }})  </h6>
                <p>Requested for Hire 1 {{ $vacancy->Position ?? 'Position' }}</p>
                {{-- <a href="#" class="a-link">Send Interview Request</a> --}}
            </div>
            <div class="icon">
                <a href="javascript:void(0)" class="respondOfFreshmodal"
                        data-images="{{ Common::getResortUserPicture($vacancy->resort_id) }}"
                        data-V_id="{{ $vacancy->V_id }}"
                        data-departmentName="{{ $vacancy->Department }}"
                        data-rank="{{ $vacancy->rank_name }}"
                        data-position="{{ $vacancy->Position }}"
                        data-ta_id ="{{ $vacancy->ta_id }}"
                        data-Child_ta_id ="{{ $vacancy->Child_ta_id }}">
                    Respond
        </a>
            </div>
        </div>
    @endforeach
@else
    <p>No new hire requests available.</p>
@endif
