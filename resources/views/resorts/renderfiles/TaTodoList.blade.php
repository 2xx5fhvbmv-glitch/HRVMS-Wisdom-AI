@php
    $employee = Auth::guard('resort-admin')->user()->GetEmployee ?? null;
    $userDeptId = $employee ? $employee->Dept_id : null;
    $userDeptName = $userDeptId ? \App\Models\ResortDepartment::where('id', $userDeptId)->value('name') : '';
    $isHrUser = stripos($userDeptName ?? '', 'Human Resources') !== false;
    $positionRankConfig = config('settings.Position_Rank');
@endphp
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

            @elseif( $t->ApplicationStatus=="Sortlisted" &&  $t->As_ApprovedBy != 0  &&  $t->InterviewLinkStatus == null )
                <div class="img-circle">
                    <img src="{{ $t->profileImg}}" alt="image">
                </div>
                <div>
                    <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} Is Shortlisted for {{ $t->Position ?? '' }} </p>
                    <a
                    href="javascript:void(0)"
                    data-Resort_id="{{$t->Resort_id}}"
                    data-ApplicantID="{{base64_encode($t->ApplicantID)}}"
                    data-ApplicantStatus_id="{{base64_encode($t->ApplicantStatus_id)}}"
                    class="a-link SortlistedEmployee">Send Interview Request </a>
                </div>

            @elseif( $t->ApplicationStatus == "Complete" && isset($t->ApplicantID) )
                @php
                    $roundsForPosition = \App\Helpers\Common::getInterviewRoundsForPosition($t->vacancy_rank ?? null);
                    $roundKeysList = array_keys($roundsForPosition);
                    $currentRoundIndex = array_search((int)$t->As_ApprovedBy, $roundKeysList);
                    $isLastRound = ($currentRoundIndex === count($roundKeysList) - 1);
                    $nextRoundName = '';
                    if (!$isLastRound && $currentRoundIndex !== false) {
                        $nextRoundKey = $roundKeysList[$currentRoundIndex + 1];
                        $nextRoundName = $roundsForPosition[$nextRoundKey] ?? '';
                    }
                    $completedRoundName = $positionRankConfig[$t->As_ApprovedBy] ?? 'Unknown';
                @endphp
                <div class="img-circle">
                    <img src="{{ $t->profileImg}}" alt="image">
                </div>
                <div>
                    @if($isLastRound)
                        <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} - {{ $completedRoundName }} Round Completed for {{ $t->Position ?? '' }}, Ready for Selection</p>
                    @else
                        <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} - {{ $completedRoundName }} Round Completed for {{ $t->Position ?? '' }}, Ready for {{ $nextRoundName }} Round</p>
                    @endif
                    <a href="{{ route('resort.ta.Applicants', base64_encode($t->V_id)) }}" class="a-link">View Applicant</a>
                </div>

            @elseif( $isHrUser && $t->InterviewLinkStatus == 'Slot Booked' && empty($t->InterviewMeetingLink) )
                <div class="img-circle">
                    <img src="{{ $t->profileImg}}" alt="image">
                </div>
                <div>
                    <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} has accepted interview invitation for {{ $t->Position ?? '' }} </p>
                    <a
                    href="javascript:void(0)"
                    data-interview_id="{{ base64_encode($t->InterviewId) }}"
                    class="a-link AddMeetingLink">Add Meeting Link </a>
                </div>

        @elseif(isset($t->is_upcoming_interview) && $t->is_upcoming_interview)
            <div class="img-circle">
                <img src="{{ $t->profileImg }}" alt="image">
            </div>
            <div>
                <p><i class="fa-regular fa-calendar me-1"></i> {{ ucfirst($t->first_name) . ' ' . ucfirst($t->last_name) }} - Interview for {{ $t->Position ?? '' }} on {{ \Carbon\Carbon::parse($t->InterViewDate)->format('d M Y') }} at {{ $t->ResortInterviewtime }}</p>
                <a href="{{ route('resort.ta.Applicants', base64_encode($t->V_id)) }}" class="a-link">View Applicant</a>
            </div>
        @endif
    </div>
@endforeach
@else
<div>
    <p>No Data Reacord</p>

</div>
@endif
