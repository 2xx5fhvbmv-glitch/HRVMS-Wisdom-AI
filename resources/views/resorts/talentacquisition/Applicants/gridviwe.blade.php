@if($Applicant_form_data->isNotEmpty())
    @foreach($Applicant_form_data as $a)
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="applicantsGrid-block">
                @if($isHrDepartment)
                <div class="dropdown table-dropdown ">
                    <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item gridview-link"
                        data-row-id="{{ $a->id }}"
                        data-status="{{ $a->ApplicantStatus }}"
                        data-applicant_id="{{ $a->applicant_id }}"
                        href="javascript:void(0)" >Interview Details</a></li>
                    </ul>
                </div>
                @endif
                @php
                $progress = 0;
                $gridRounds = \App\Helpers\Common::getInterviewRoundsForPosition($a->vacancy_rank ?? null);
                $gridRoundKeys = array_keys($gridRounds);
                $gridTotalRounds = count($gridRounds);
                $gridTotalSteps = 2 + ($gridTotalRounds * 2) + 1;
                $gridCurrentStep = 0;

                if($a->ApplicantStatus == 'Selected') {
                    $gridCurrentStep = $gridTotalSteps;
                } elseif($a->As_ApprovedBy == 0) {
                    $gridCurrentStep = 1;
                } else {
                    $gridCurrentStep = 2;
                    foreach ($gridRoundKeys as $index => $rankCode) {
                        $rankCode = (int) $rankCode;
                        if ($a->As_ApprovedBy == $rankCode && $a->ApplicantStatus == 'Round') {
                            $gridCurrentStep = 2 + ($index * 2) + 1;
                            break;
                        } elseif ($a->As_ApprovedBy == $rankCode && $a->ApplicantStatus == 'Complete') {
                            $gridCurrentStep = 2 + ($index * 2) + 2;
                            break;
                        } elseif ($a->As_ApprovedBy == $rankCode && $a->ApplicantStatus == 'Sortlisted') {
                            $gridCurrentStep = 2;
                            break;
                        }
                    }
                }
                $progress = round(($gridCurrentStep / $gridTotalSteps) * 100, 2);
            @endphp
                <!-- <a href="#" class="dotsV-link"><i class="fa-solid fa-ellipsis-vertical"></i></a> -->
                <div class="progress-container skyblue" data-progress="{{ $progress  }}">
                    <svg class="progress-circle" viewBox="0 0 120 120">
                        <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                        <circle class="progress" cx="60" cy="60" r="54"></circle>
                    </svg>
                    <div class="img-obj cover"> <img src="{{ $a->profileImg }}" alt="image"></div>
                </div>
                <h6>{{ ucfirst($a->first_name) }} {{ ucfirst($a->last_name) }}</h6>
                <p>{{ ucfirst($a->position_title) }} </p>
                {{-- HR SortListed --}}
                {{-- Status --}}
                    @if($a->As_ApprovedBy == 0)
                    <span class="badge badge-themeSkyblue">{{ $a->ApplicantStatus }}</span>
                    @elseif($a->As_ApprovedBy != 0 &&  $a->ApplicantStatus  == 'Sortlisted')
                        <span class="badge badge-themeBlue">{{  $a->rank_name }} {{ $a->ApplicantStatus }}</span>
                    @elseif(in_array($a->ApplicantStatus, ['Round', 'Complete']))
                        @php
                            $gridBadgeClass = 'badge-themeBlue';
                            if ($a->As_ApprovedBy == 2) $gridBadgeClass = 'badge-themePurple';
                            elseif ($a->As_ApprovedBy == 8) $gridBadgeClass = 'badge-themePink';
                        @endphp
                        <span class="badge {{ $gridBadgeClass }}">{{  $a->rank_name }}  {{ $a->ApplicantStatus }}</span>
                    @elseif($a->ApplicantStatus  == 'Selected')
                        <span class="badge badge-themeSuccess">{{ $a->ApplicantStatus }}</span>
                    @elseif( $a->ApplicantStatus  == 'Rejected')
                        <span class="badge badge-themeDanger">{{  $a->rank_name }}  {{ $a->ApplicantStatus }}</span>
                    @endif
                <div class="bg">
                    <p><i class="fa-regular fa-envelope"></i>{{ $a->email }}</p>
                    <p><i class="fa-regular fa-phone"></i>{{ $a->contact }}</p>
                </div>
                <div class="bg bg-table">
                    <table>
                        <tr>
                            <th>Experience:</th>
                            <td>{{ $a->total_work_exp_sum }}</td>
                        </tr>
                        <tr>
                            <th>Applied On:</th>
                            <td>{{ $a->Application_date  }}</td>
                        </tr>
                        <tr>
                            <th>Passport No.</th>
                            <td>{{ $a->passport_no  }}</td>
                        </tr>
                        <tr>
                            <th>Current Employment Status :</th>
                            <td>{{ ($a->employment_status  =="Available") ?"Not Employed" :"Employed"}}</td>
                        </tr>
                        <tr>
                            <th>Current position:</th>
                            <td>{{ $a->job_title  }}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm ApplicantsNotes ApplicantsNotes_{{ $a->applicant_id }}" data-notes="{{ $a->Notes }}"  data-id="{{base64_encode($a->id) }}" data-id="{{ $a->id }}">Notes</a>
                    <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm userApplicants-btn"  data-id="{{ $a->applicant_id }}">View</a>
                </div>
            </div>
        </div>
    @endforeach
<nav aria-label="Page navigation example">
    {!! $pagination !!}

</nav>
@else
<div class="row g-4">
    <div class="col-sm-12">
        <div class="vacanciesGrid-block">
                <h6>No Record Found</h6>
        </div>
    </div>
</div>
@endif

