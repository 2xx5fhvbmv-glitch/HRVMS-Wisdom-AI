<div class="userApplicants-title">
    <div class="d-flex">

        @php
            $progress = 0;
            $roundKeys = array_keys($InterViewRound);
            $totalRounds = count($InterViewRound);
            // Steps: AI Shortlisted(1) + HR Shortlisted(1) + per round: Round+Complete (2*N) + Selected(1)
            $totalSteps = 2 + ($totalRounds * 2) + 1;
            $currentStep = 0;

            if(in_array($Applicant_form_data->ApplicantStatus, ['Rejected', 'Rejected By Wisdom AI'])) {
                if($Applicant_form_data->As_ApprovedBy == 0) {
                    $currentStep = 1;
                } elseif(isset($InterViewRound) && array_key_exists($Applicant_form_data->As_ApprovedBy, $InterViewRound)) {
                    $roundIndex = array_search($Applicant_form_data->As_ApprovedBy, $roundKeys);
                    $currentStep = 2 + ($roundIndex * 2) + 1;
                } else {
                    $currentStep = 2;
                }
            } elseif($Applicant_form_data->ApplicantStatus == 'Selected' || in_array($Applicant_form_data->ApplicantStatus, ['Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected'])) {
                $currentStep = $totalSteps;
            } elseif($Applicant_form_data->As_ApprovedBy == 0) {
                $currentStep = 1; // AI Shortlisted
            } else {
                $currentStep = 2; // HR Shortlisted at minimum
                foreach ($roundKeys as $index => $rankCode) {
                    $rankCode = (int) $rankCode;
                    if ($Applicant_form_data->As_ApprovedBy == $rankCode && $Applicant_form_data->ApplicantStatus == 'Round') {
                        $currentStep = 2 + ($index * 2) + 1;
                        break;
                    } elseif ($Applicant_form_data->As_ApprovedBy == $rankCode && $Applicant_form_data->ApplicantStatus == 'Complete') {
                        $currentStep = 2 + ($index * 2) + 2;
                        break;
                    } elseif ($Applicant_form_data->As_ApprovedBy == $rankCode && $Applicant_form_data->ApplicantStatus == 'Sortlisted') {
                        $currentStep = 2;
                        break;
                    }
                }
            }
            $progress = round(($currentStep / $totalSteps) * 100, 2);
        @endphp
        <div class="progress-container {{ in_array($Applicant_form_data->ApplicantStatus, ['Rejected', 'Rejected By Wisdom AI']) ? 'danger' : 'skyblue' }}" data-progress="{{ $progress }}">
            <svg class="progress-circle" viewBox="0 0 120 120">
                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                <circle class="progress" cx="60" cy="60" r="54"></circle>
            </svg>
            <div class="img-obj cover"> <img src="{{ $getFileapplicant }}" alt="image"></div>
        </div>
        <div>

            <h4>
                {{ ucfirst($Applicant_form_data->first_name) }} {{ ucfirst($Applicant_form_data->last_name) }}

                @if($Applicant_form_data->As_ApprovedBy == 0)
                    <span class="badge badge-themeSkyblue">{{ $Applicant_form_data->ApplicantStatus }}</span>
                @elseif($Applicant_form_data->As_ApprovedBy != 0 &&  $Applicant_form_data->ApplicantStatus  == 'Sortlisted')
                    <span class="badge badge-themeBlue">HR {{ $Applicant_form_data->ApplicantStatus }}</span>
                @elseif(in_array($Applicant_form_data->ApplicantStatus, ['Round', 'Complete']))
                    @php
                        $badgeClass = 'badge-themeBlue';
                        if ($Applicant_form_data->As_ApprovedBy == 2) $badgeClass = 'badge-themePurple';
                        elseif ($Applicant_form_data->As_ApprovedBy == 8) $badgeClass = 'badge-themePink';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{  $Applicant_form_data->rank_name }}  {{ $Applicant_form_data->ApplicantStatus }}</span>
                @elseif($Applicant_form_data->ApplicantStatus  == 'Selected')
                    <span class="badge badge-themeSuccess">{{ $Applicant_form_data->ApplicantStatus }}</span>
                @elseif(in_array($Applicant_form_data->ApplicantStatus, ['Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected']))
                    @php
                        $badgeStyle = 'badge-themeSuccess';
                        if(str_contains($Applicant_form_data->ApplicantStatus, 'Rejected')) $badgeStyle = 'badge-themeDanger';
                        elseif(str_contains($Applicant_form_data->ApplicantStatus, 'Sent')) $badgeStyle = 'badge-themeBlue';
                    @endphp
                    <span class="badge {{ $badgeStyle }}">{{ $Applicant_form_data->ApplicantStatus }}</span>
                @elseif( $Applicant_form_data->ApplicantStatus  == 'Rejected')
                    <span class="badge badge-themeDanger">{{  $Applicant_form_data->rank_name }}  {{ $Applicant_form_data->ApplicantStatus }}</span>
                @endif
            </h4>

            <p>{{ $Applicant_form_data->position_title}}</p>
        </div>
    </div>
    <div>
        <!-- <a href="#" class="btn btn-themeBlue btn-sm">Send Message</a>
        <a href="#" class="btn btn-themeSkyblue btn-sm">Send Email</a> -->
        <a href="#" class="btn btn-danger btn-sm closeSlider">close</a>
    </div>
</div>
<div class="userApplicants-body">
    <div class="tab-userApplicants">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#tabPane1"
                    type="button" role="tab" aria-controls="tabPane1" aria-selected="true">DETAILS</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="#tab2" data-bs-toggle="tab" data-bs-target="#tabPane2"
                    type="button" role="tab" aria-controls="tabPane2" aria-selected="false">DOCUMENTS</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#tabPane3" type="button"
                    role="tab" aria-controls="tabPane3" aria-selected="false">PROGRESS</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#tabPane4" type="button"
                    role="tab" aria-controls="tabPane4" aria-selected="false">INTERVIEW</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab5" data-bs-toggle="tab" data-bs-target="#tabPane5" type="button"
                    role="tab" aria-controls="tabPane5" aria-selected="false">QUESTION</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="tabPane1" role="tabpanel" aria-labelledby="tab1"
                tabindex="0">
                <div class="table-responsive">
                    <table class="table table-lable mb-0">
                        <tbody>
                            <tr>
                                <th>Name:</th>
                                <td>{{ ucfirst($Applicant_form_data->first_name) }} {{ ucfirst($Applicant_form_data->last_name) }}  </td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $Applicant_form_data->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $Applicant_form_data->mobile_number }}</td>
                            </tr>
                            <tr>
                                <th>Nationality:</th>
                                <td>{{ $Applicant_form_data->CountryName }}</td>
                            </tr>
                            <tr>
                                <th>DOB:</th>
                                <td>{{ $Applicant_form_data->dob ? date('d/M/Y', strtotime($Applicant_form_data->dob)) : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Experience:</th>
                                <td>{{ $Applicant_form_data->Total_Experiance }}</td>
                            </tr>
                            <tr>
                                <th>Passport No.:</th>
                                <td>{{ $Applicant_form_data->passport_no }}</td>
                            </tr>
                            <tr>
                                <th>Applied Date:</th>
                                <td>{{ date('d/m/Y',strtotime($Applicant_form_data->Application_date)) }}</td>
                            </tr>
                            <tr>
                                <th>Education:</th>
                                <td>{{ $Applicant_form_data->Education }}</td>
                            </tr>
                            <tr>
                                <th>Current Address:</th>
                                <td>{{ $Applicant_form_data->address_line_one }} {{ $Applicant_form_data->state}} {{ $Applicant_form_data->city}} {{$Applicant_form_data->pin_code }}</td>
                            </tr>
                            <tr>
                                <th>Permanent Address:</th>
                                <td>{{ $Applicant_form_data->address_line_two }} {{ $Applicant_form_data->state}} {{ $Applicant_form_data->city}} {{$Applicant_form_data->pin_code }}</td>
                            </tr>
                            <tr>
                                <th>Current Employment Status:</th>
                                <td>{{ $Applicant_form_data->employment_status }}</td>
                            </tr>
                            <tr>
                                <th>Notice Period:</th>
                                <td>{{ $Applicant_form_data->NotiesPeriod }}  days</td>
                            </tr>
                            <tr>
                                <th>Salary Expectation:</th>
                                <td>{{ $Applicant_form_data->SalaryExpectation }} $ </td>
                            </tr>
                            <tr>
                                <th>Languages Spoken:</th>

                                <td>
                                    @if($ApplicantLanguage->isNotEmpty())
                                        @foreach ($ApplicantLanguage as $a)
                                            <b>Lanugage</b>:  {{ $a->language }}   - <b>Level</b>:  {{ $a->level }}<br>
                                        @endforeach

                                    @endif
                                </td>
                            </tr>
                            @if(isset($WorkExperiences) && $WorkExperiences->isNotEmpty())
                            <tr>
                                <th colspan="2" style="padding-top: 16px; font-size: 15px; color: #004552;">Work Experience</th>
                            </tr>
                            @foreach ($WorkExperiences as $exp)
                            <tr>
                                <th>{{ $exp->job_title ?? 'N/A' }} <br><small style="font-weight:normal;color:#888;">{{ $exp->employer_name ?? '' }}</small></th>
                                <td>
                                    @if($exp->work_start_date || $exp->work_end_date)
                                        <small><b>Period:</b> {{ $exp->work_start_date ?? '' }} - {{ $exp->currently_working ? 'Present' : ($exp->work_end_date ?? '') }}</small><br>
                                    @endif
                                    @if($exp->job_description_work)
                                        <b>Job Description:</b><br>
                                        {!! nl2br(e($exp->job_description_work)) !!}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="tabPane2" role="tabpanel" aria-labelledby="#tab2" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-lable table-docUserApp">
                        <tbody>
                            <tr>

                                <th>Curriculum Vitae</th>
                                <td><a  data-id="{{ base64_encode($Applicant_form_data->id) }}" data-flag="curriculum_vitae" href="javascript:void(0)" class="a-link DownloadFile">Download</a></td>
                            </tr>
                            <tr>
                                <th>Passport Image </th>
                                <td><a  data-id="{{ base64_encode($Applicant_form_data->id) }}" data-flag="passport_img" href="javascript:void(0)" class="a-link DownloadFile">Download</a></td>
                            </tr>
                            <tr>
                                <th>Passport Photos</th>
                                <td><a   data-id="{{ base64_encode($Applicant_form_data->id) }}" data-flag="passport_photo" href="javascript:void(0)" class="a-link DownloadFile">Download</a></td>
                            </tr>
                            <tr>
                                <th>Full Length Photo</th>
                                <td><a  data-id="{{ base64_encode($Applicant_form_data->id) }}" data-flag="full_length_photo" href="javascript:void(0)" class="a-link DownloadFile">Download</a></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm DownloadAllFiles" data-id="{{ base64_encode($Applicant_form_data->id) }}">Download All</a>
                </div>
            </div>
            <div class="tab-pane fade" id="tabPane3" role="tabpanel" aria-labelledby="tab3" tabindex="0">
                <div class="overflow-hidden">
                    <div class="row g-lg-4 g-3 mb-lg-4 mb-3">
                        <div class="col-lg-8 col-md-7">
                            @php
                                $isRejected = in_array($Applicant_form_data->ApplicantStatus, ['Rejected', 'Rejected By Wisdom AI']);
                                $rejectedStage = null;
                                if ($isRejected) {
                                    if ($Applicant_form_data->As_ApprovedBy == 0) {
                                        $rejectedStage = 'ai';
                                    } elseif (isset($InterViewRound) && array_key_exists($Applicant_form_data->As_ApprovedBy, $InterViewRound)) {
                                        $rejectedStage = 'interview';
                                    } else {
                                        $rejectedStage = 'hr';
                                    }
                                }
                            @endphp
                            <div class="userApplicants-accordion" id="accordionExample">
                                <div class="accordion-item {{ $isRejected && $rejectedStage == 'ai' ? 'rejected' : 'active' }}" >
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseOne" aria-expanded="true"
                                            aria-controls="collapseOne">
                                            {{ $isRejected && $rejectedStage == 'ai' ? 'Rejected' : 'Shortlisted by Wisdom AI' }}
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show"
                                        aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            @if($Applicant_form_data->ApplicantStatus =="Sortlisted By Wisdom AI" &&   $Applicant_form_data->As_ApprovedBy ==0 && $isHrDepartment)
                                                <a href="javascript:void(0)" class="btn btn-themeSkyblue ApprovedOrSortListed btn-sm"
                                                    data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                    data-Progress_ApplicantStatusID = "{{ $Applicant_form_data->ApplicantStatusID }}"
                                                    data-Progress_As_ApprovedBy = "{{ $Applicant_form_data->As_ApprovedBy }}"
                                                    data-Progress_Rank="Sortlisted" data-interviewRound="HRShortlisted" > HR Shortlisted
                                                </a>
                                                <a href="javascript:void(0)" class="btn btn-danger ApprovedOrSortListed btn-sm"
                                                    data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                    data-Progress_ApplicantStatusID = "{{ $Applicant_form_data->ApplicantStatusID }}"
                                                    data-Progress_As_ApprovedBy = "{{ $Applicant_form_data->As_ApprovedBy }}"
                                                    data-Progress_Rank="Rejected" data-interviewRound="HRShortlisted">Rejected
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item @if($isRejected && $rejectedStage == 'hr') rejected @elseif($isRejected && $rejectedStage == 'interview') active @elseif(isset($HrSortlisted)) active @endif ">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                            aria-expanded="false" aria-controls="collapseTwo">
                                            {{ $isRejected && $rejectedStage == 'hr' ? 'Rejected' : 'HR Shortlisted' }}
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse"
                                        aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            {{-- dsfsdf  --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item
                                    @if($isRejected && $rejectedStage == 'interview')
                                        rejected
                                    @else
                                        @foreach($ApplicantWiseStatusFinal as $status)
                                            @if(in_array($status->status, ['Round', 'Complete', 'Selected', 'Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected']))
                                                active
                                            @endif
                                        @endforeach
                                    @endif
                                ">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseThree"
                                            aria-expanded="false" aria-controls="collapseThree">
                                            {{ $isRejected && $rejectedStage == 'interview' ? 'Rejected' : 'Interview' }}
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse"
                                        aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>
                                                    Complete Rounds
                                                    <ol>
                                                        @php
                                                        $rank='';
                                                        $interviewer='';
                                                        $isApprovedByHR=false;
                                                        $roundKeys = array_keys($InterViewRound);

                                                        // Show complete rounds
                                                        foreach ($InterViewRound as $k => $r) {
                                                            foreach($completeRound as $k1=>$c) {
                                                                if($k==$k1) {
                                                                    echo "<li>".$r." Round </li>";
                                                                }
                                                            }
                                                        }

                                                        // Determine current action button dynamically
                                                        foreach ($ApplicantWiseStatusFinal as $a) {
                                                            // Shortlisted -> show the round matching As_ApprovedBy
                                                            if($a->As_ApprovedBy != 0 && $a->status=="Sortlisted" && $Applicant_form_data->ApplicantStatus =="Sortlisted") {
                                                                $currentRoundName = $InterViewRound[$a->As_ApprovedBy] ?? $InterViewRound[$roundKeys[0]] ?? 'HR';
                                                                $interviewer = $currentRoundName . " Round";
                                                                $isApprovedByHR=true;
                                                                $rank='Round';
                                                                break;
                                                            }

                                                            // Dynamic round progression based on position
                                                            foreach ($roundKeys as $index => $roundRankCode) {
                                                                $roundName = $InterViewRound[$roundRankCode];
                                                                $isLastRound = ($index == count($roundKeys) - 1);

                                                                // Current round status is "Round" -> offer "Complete" button
                                                                if($a->As_ApprovedBy == $roundRankCode && $a->status=="Round" && $Applicant_form_data->ApplicantStatus =="Round") {
                                                                    $interviewer = $roundName . " Complete";
                                                                    $isApprovedByHR=true;
                                                                    $rank='Complete';
                                                                    break 2;
                                                                }
                                                                // Current round is "Complete" -> offer next round or select
                                                                elseif($a->As_ApprovedBy == $roundRankCode && $a->status=="Complete" && $Applicant_form_data->ApplicantStatus =="Complete" && $Applicant_form_data->As_ApprovedBy == $roundRankCode) {
                                                                    if($isLastRound) {
                                                                        $interviewer = "select";
                                                                        $isApprovedByHR=true;
                                                                        $rank='Selected';
                                                                    } else {
                                                                        $nextRoundName = $InterViewRound[$roundKeys[$index + 1]];
                                                                        $interviewer = $nextRoundName . " Round";
                                                                        $isApprovedByHR=true;
                                                                        $rank='Round';
                                                                    }
                                                                    break 2;
                                                                }
                                                            }
                                                        }

                                                        $remainingRounds = array_diff($InterViewRound, $completeRound);
                                                        @endphp
                                                    </ol>
                                                </li>
                                                @if(count($remainingRounds)>0)
                                                <li>
                                                    Pending Rounds
                                                    <ol>
                                                        @foreach ($remainingRounds as $item)
                                                        <li >{{ $item }} Round </li>

                                                        @endforeach
                                                    </ol>
                                                    {{-- $this->resort->GetEmployee->rank; --}}
                                                </li>
                                                @endif
                                            </ol>
                                            <?php
                                                $CompleteArray = ["select"];
                                                $RoundArray = [];
                                                foreach ($InterViewRound as $rName) {
                                                    $CompleteArray[] = $rName . " Complete";
                                                    $RoundArray[] = $rName . " Round";
                                                }
                                            ?>
                                            @if ($interviewer )
                                                @php
                                                    // Determine which round this button belongs to
                                                    $buttonRoundRank = null;
                                                    foreach ($InterViewRound as $rk => $rn) {
                                                        if (str_starts_with($interviewer, $rn . ' ')) {
                                                            $buttonRoundRank = (int) $rk;
                                                            break;
                                                        }
                                                    }
                                                    if ($interviewer == "select") $buttonRoundRank = $finalRoundRank;
                                                    // Only HR can select/reject; for other actions, HR can act on all rounds, others only on their own round
                                                    $canAct = ($interviewer == "select") ? $isHrDepartment : ($isHrDepartment || $CurrentRankOFUser == $buttonRoundRank);
                                                @endphp

                                                @if($canAct)
                                                    @if(in_array($interviewer,$CompleteArray) && $Applicant_form_data->MeetingLink!="")

                                                        <a href="javascript:void(0)" class="btn btn-themeSkyblue ApprovedOrSortListed btn-sm"
                                                            data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                            data-Progress_ApplicantStatusID="{{ $Applicant_form_data->ApplicantStatusID }}"
                                                            data-Progress_As_ApprovedBy="{{ $CurrentRank }}"
                                                            data-Progress_Rank="{{ $rank }}" data-interviewRound ="{{$interviewer }}"
                                                            >
                                                            {{ $interviewer }}
                                                        </a>

                                                        <a href="javascript:void(0)"
                                                            data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                            data-Progress_ApplicantStatusID = "{{ $Applicant_form_data->ApplicantStatusID }}"
                                                            data-Progress_As_ApprovedBy = "{{ $Applicant_form_data->As_ApprovedBy }}"
                                                            data-Progress_Rank="Rejected" data-interviewRound ="{{$interviewer }}"
                                                            class="btn btn-danger ApprovedOrSortListed btn-sm"
                                                            > Rejected
                                                        </a>
                                                    @elseif(in_array($interviewer,$RoundArray))
                                                        <a href="javascript:void(0)" class="btn btn-themeSkyblue ApprovedOrSortListed btn-sm"
                                                            data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                            data-Progress_ApplicantStatusID="{{ $Applicant_form_data->ApplicantStatusID }}"
                                                            data-Progress_As_ApprovedBy="{{ $CurrentRank }}"
                                                            data-Progress_Rank="{{ $rank }}" data-interviewRound ="{{$interviewer }}"
                                                            >{{ $interviewer }}</a>

                                                        <a href="javascript:void(0)"
                                                            data-Progress_ApplicantID="{{ base64_encode($Applicant_form_data->ApplicantID) }}"
                                                            data-Progress_ApplicantStatusID = "{{ $Applicant_form_data->ApplicantStatusID }}"
                                                            data-Progress_As_ApprovedBy = "{{ $Applicant_form_data->As_ApprovedBy }}"
                                                            data-Progress_Rank="Rejected" data-interviewRound ="{{$interviewer }}"
                                                            class="btn btn-danger ApprovedOrSortListed btn-sm"
                                                            > Rejected
                                                        </a>
                                                    @else
                                                        <a href="javascript:void(0)" class="btn btn-themeSkyblue  btn-sm"> Please generate Interview Link</a>
                                                    @endif
                                                @endif
                                            @endif

                                        </div>

                                    </div>
                                </div>
                                <div class="accordion-item  @if(in_array($Applicant_form_data->ApplicantStatus, ['Selected', 'Offer Letter Sent', 'Offer Letter Accepted', 'Offer Letter Rejected', 'Contract Sent', 'Contract Accepted', 'Contract Rejected']))active @endif ">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseFour"
                                            aria-expanded="false" aria-controls="collapseFour">
                                            Selected
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse"
                                        aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            {{-- Lorem ipsum dolor sit amet consectetur adipisicing elit. Repellendus
                                            molestiae perspiciatis doloribus porro, recusandae ipsum totam
                                            dignissimos incidunt vero harum et omnis autem quaerat consequatur a
                                            voluptates quam reiciendis odit. --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-5">
                            <div class="card bg userApplicants-bg">
                                <div class="card-title">
                                    <h3>Add Notes</h3>
                                </div>
                                <form id="ApplicantNoteForm">
                                    @csrf
                                    <input type="hidden" value="{{ base64_encode($Applicant_form_data->ApplicantID) }}" id="Applicant_id" name="Applicant_id">
                                    <div class="textarea-icon mt-2 mb-3">
                                        <textarea rows="9" name="ApplicantNote" class="form-control" placeholder="Type Here">{{ $Applicant_form_data->notes }}</textarea>
                                        <img src="{{ URL::asset('resorts_assets/images/textarea-icon.svg') }}" alt="icon">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-themeSkyblue btn-sm">Submit</button>
                                    </div>
                                </form>

                                <div class="card-title">
                                    <h3> Comments</h3>
                                </div>
                                <form id="RoundWiseForm">
                                    @csrf
                                    <div class=" mb-3">
                                    <input type="hidden" name="Applicant_id" value="{{base64_encode($Applicant_form_data->ApplicantStatusID) }}">
                                        @if($interviewer=="HOD Round" ||  $interviewer=="HR Round" || $interviewer=="GM Round" || $interviewer=="Interviewer")

                                        <?php $comments ='' ; ?>
                                        @else

                                        <?php  $comments =$Applicant_form_data->Comments ; ?>
                                        @endif

                                        <textarea rows="4" name="Comment" class="form-control"  placeholder="Type Here">{{ $comments }}</textarea>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-themeSkyblue btn-sm">Submit</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <!-- <a target="_blank" href="{{ route('interview-assessment.show', ['position_id' => $Applicant_form_data->position_id, 'applicant_id' => $Applicant_form_data->id]) }}" class="a-link">View Interview Assessment Form</a> -->
                        <a target="_blank"
                            href="{{ route('interview-assessment.show', [
                                'position_id' => base64_encode($Applicant_form_data->position_id),
                                'applicant_id' => base64_encode($Applicant_form_data->id)
                            ]) }}"
                            class="a-link">
                            View Interview Assessment Form
                        </a>
                    </div>
            </div>
            <div class="tab-pane fade" id="tabPane4" role="tabpanel" aria-labelledby="tabPane4" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-lable table-intUserApp">
                        <tbody>
                            <tr>
                                <th>Position:</th>
                                <td>{{ $Applicant_form_data->position_title}}</td>
                            </tr>
                            <tr>
                                <th>Department:</th>
                                <td>{{ $Applicant_form_data->DepartmentName}}</td>
                            </tr>
                            @if($Applicant_form_data->InterViewDate)
                            <tr>
                                <th>Interview Date & Time:</th>
                                <td>{{ date('d/M/Y',strtotime($Applicant_form_data->InterViewDate)) }} -  <b>Applicant Time </b> :-{{ $Applicant_form_data->ApplicantInterviewtime}}  :   <b>Maldivan Time</b>:-{{ $Applicant_form_data->ResortInterviewtime}} </td>
                            </tr>
                            @endif
                            @if($Applicant_form_data->MeetingLink)
                            <tr>
                                <th>Interview link:</th>
                                <td>
                                    @if($isHrDepartment)
                                        <a href="{{ $Applicant_form_data->MeetingLink}}" class="a-link">{{ $Applicant_form_data->MeetingLink}}</a>
                                    @else
                                        <span class="text-muted"><i class="fa-solid fa-lock me-1"></i>Available after your round is scheduled</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($InterviewComments->isNotEmpty())
                                @foreach($InterviewComments as $interview)
                                <tr>
                                    <th>Interviewer -  {{ $interview->rank_name }}  {{ $interview->ApplicantInterviewtime}}:</th>
                                    <td>{{ $interview->InterViewDate}}  - <b>Applicant Time </b> :-{{ $interview->ApplicantInterviewtime}}  <b>Maldivan Time</b>:-{{ $interview->ResortInterviewtime}}
                                        <div class="tableUser-block mt-2">
                                            <div class="img-circle"><img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="user">
                                            </div>
                                            <span class="userApplicants-btn">{{ ucfirst($interview->interviewer_first_name ?? '') }} {{ ucfirst($interview->interviewer_last_name ?? '') }}</span>
                                            <div class="ms-2"><img src="{{ URL::asset('resorts_assets/images/thumbs-up.svg')}}" alt="icon">
                                            </div>
                                            <div class="ms-auto">
                                                <a href="javascript:void(0)" class="a-link userAppInt-vCommBtn">View Comments</a>
                                                <a href="javascript:void(0)" class="a-link userAppInt-hCommBtn d-none">Hide
                                                    Comments</a>
                                            </div>
                                        </div>
                                        <div class="userAppInt-commBlock">{{ $interview->Comments}} </div>
                                        @if( $interview->status=="Round" || $interview->status=="Sortlisted" || $interview->As_ApprovedBy==3 && $interview->status=="Complete")
                                        <div class="mb-2">
                                            <span class="text-medium">Scheduled Interview:</span>
                                            {{ $interview->InterViewDate}} -
                                            {{ $interview->ApplicantInterviewtime}}
                                        </div>
                                        @if($interview->MeetingLink && $interview->MeetingLink != '0')
                                            @if($isHrDepartment || $interview->As_ApprovedBy == $CurrentRank)
                                                <div class="mb-2">
                                                    <a href="{{ $interview->MeetingLink}}" target="_blank" class="a-link">{{ $interview->MeetingLink}}</a>
                                                </div>
                                                <a href="{{ $interview->MeetingLink}}" target="_blank" class="btn btn-themeSkyblue btn-sm">Join The Interview</a>
                                            @else
                                                <div class="mb-2">
                                                    <span class="text-muted"><i class="fa-solid fa-lock me-1"></i>Meeting link not available for your round</span>
                                                </div>
                                            @endif
                                        @else
                                            <div class="mb-2">
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Meeting link pending from HR</span>
                                            </div>
                                        @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr>
                                <th colspan="2" >Interview Not Schedule at</th>
                            </tr>
                            @endif

                      </tbody>
                    </table>
                </div>
                @if($interview_assesment_response)
                    @foreach($interview_assesment_response as $response)

                        @php
                            $rankConfig = config('settings.Position_Rank');
                            $availableRank = $rankConfig[$response->rank] ?? '';
                        @endphp
                        <a target="_blank"
                            href="{{ route('interview-assessment.viewResponse', [
                                'formId' => base64_encode($response->form_id),
                                'responseId' => base64_encode($response->id)
                            ]) }}"
                            class="a-link">
                            {{ $availableRank }} Interview Assessment
                        </a>
                        <!-- <a target="_blank" href="{{ route('interview-assessment.viewResponse', ['formId' => $response->form_id, 'responseId' => $response->id]) }}" class="a-link mx-auto">{{ $availableRank }} Interview Assessment</a> -->
                    @endforeach
                @endif
                @if(!empty($Applicant_form_data->notes) && (is_null($Applicant_form_data->notes_by) || $Applicant_form_data->notes_by == $currentUserId))
                <div class="intUserApp-block mt-3">
                    <h6>Notes:</h6>
                    <p>{{ $Applicant_form_data->notes }}</p>
                </div>
                @endif

                @php
                    $hasComments = !empty($Applicant_form_data->Comments);
                    $hasInterviewComments = $InterviewComments->contains(function($c) { return !empty($c->Comments); });
                @endphp
                @if($hasComments || $hasInterviewComments)
                <div class="intUserApp-block mt-3">
                    <h6>Comments:</h6>
                    @if($hasComments)
                    <div class="mb-2 p-2" style="background:#f5f5f5; border-radius:6px;">
                        <p class="mb-0">{{ $Applicant_form_data->Comments }}</p>
                    </div>
                    @endif
                    @foreach($InterviewComments as $comment)
                        @if(!empty($comment->Comments))
                        <div class="mb-2 p-2" style="background:#f5f5f5; border-radius:6px;">
                            <span class="text-medium">{{ $comment->rank_name }}:</span>
                            <p class="mb-0">{{ $comment->Comments }}</p>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endif

                <div class="intUserApp-block mt-3">
                    <h6>Analyze Of AI:</h6>
                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum
                        has been the industry's standard dummy text ever since the 1500s, when an unknown
                        printer took a galley of type and scrambled it to make a type specimen book.</p>
                </div>
            </div>
            <div class="tab-pane fade" id="tabPane5" role="tabpanel" aria-labelledby="tabPane5" tabindex="0">
                <div class="table-responsive mb-mb-4 mb-4">
                    <table class="table table-lable table-queUserApp  mb-0">
                        <tbody>
                            @if( $SimpleQuestions->isNotEmpty() )
                            @foreach ($SimpleQuestions as $k => $question)
                            <tr>
                                <th>Question {{$loop->iteration }}</th>
                                <th>{{ $question->Question}}</th>
                                <td>
                                    @if(isset( $question->response))
                                    {{ $question->response }}
                                    @elseif(isset($question->multiple_responses))
                                    {{ implode(', ', json_decode($question->multiple_responses)) }}

                                    @endif


                                </td>
                            </tr>
                            @endforeach
                           @endif
                        </tbody>
                    </table>
                </div>
                <div class="card-title">
                    <h3>Video Question</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-lable table-videoQueUserApp  mb-0">
                        <tbody>

                            @if( $VideoQuestions->isNotEmpty() )
                            @foreach ($VideoQuestions as $k => $question1)

                            <tr>
                                <th>Question {{$loop->iteration }}</th>
                                <th>{{ $question1->VideoQuestion}}</th>

                                <td>
                                    <td>
                                        <div class="ratio ratio-16x9 cover">
                                            <video controls>

                                                <source src="{{URL::asset( $question1->video_path)}}" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        </div>
                                    </td>
                            </tr>
                            @endforeach
                           @endif

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        const radius = 54; // Circle radius
        const circumference = 2 * Math.PI * radius; // The circumference of the circle
        const progressContainers = document.querySelectorAll('.progress-container');

        progressContainers.forEach(container => {
            const progressCircle = container.querySelector('.progress');
            const progressValue = container.getAttribute('data-progress'); // Get the progress value from the container's data attribute
            const offset = circumference - (progressValue / 100 * circumference); // Calculate the offset

            progressCircle.style.strokeDashoffset = circumference;

           setTimeout(() => {
                progressCircle.style.strokeDashoffset = offset;


            }, 100); // A small delay to trigger the animation smoothly
        });


    });
</script>
