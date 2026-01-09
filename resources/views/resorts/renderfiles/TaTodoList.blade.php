@if($TodoData->isNotEmpty())

@foreach ($TodoData as $t)

    <div class="todoList-block">
        @if(!isset($t->ApplicantID) )
            <div class="img-circle">
                <img src="{{ Common::getResortUserPicture($t->user_id)}}" alt="image">
            </div>
            <div>

                <p>{{ $t->rank_name }} Is Approved Vacancy For {{ $t->Position ?? '' }} </p>
                @if($t->LinkShareOrNot =="No")
                    <a  href="{{route('resort.ta.add.Questionnaire')}}"
                    target="_blank"
                        class="a-link">Before You Create  Job Advertisement You must be add Questioners</a>


                @else
                <a  href="javascript:void(0)"
                    data-Resort_id="{{ $t->Resort_id }}"
                    data-ta_childid="{{ $t->ta_childid }}"
                    data-ExpiryDate ="{{ $t->ExpiryDate}}" data-jobadvertisement="{{$t->JobAdvertisement}}" data-link="{{$t->adv_link}}"  data-applicationUrlshow="{{$t->applicationUrlshow}}" data-applicant_link="{{$t->applicant_link}}" data-bs-toggle="modal" class="a-link jobAD-modal">Create Job Advertisement</a>

                @endif

                </div>
            {{-- elseif($t->InterviewLinkStatus=="Active"  ||  $t->ApplicationStatus=="Sortlisted" || $t->As_ApprovedBy == 3 ) --}}

            @elseif( $t->ApplicationStatus=="Sortlisted" &&  $t->As_ApprovedBy == 3  &&  $t->InterviewLinkStatus == null )
                <div class="img-circle">
                    <img src="{{ $t->profileImg}}" alt="image">
                </div>
                <div>
                    <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} Is Shortlisted f {{ $t->Position ?? '' }} </p>
                    <a
                    href="javascript:void(0)"
                    data-Resort_id="{{$t->Resort_id}}"
                    data-ApplicantID="{{base64_encode($t->ApplicantID)}}"
                    data-ApplicantStatus_id="{{base64_encode($t->ApplicantStatus_id)}}"
                    class="a-link SortlistedEmployee">Send Interview Request </a>
                </div>

        @endif
    </div>
@endforeach
@else
<div>
    <p>No Data Reacord</p>

</div>
@endif
