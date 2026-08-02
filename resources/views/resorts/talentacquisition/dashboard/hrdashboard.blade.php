@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('resort.vacancies.create') }}" class="btn ta-btn-accent @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.create')) == false) d-none @endif">New Hire</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 g-xxl-4 recHR-main ">
            <div class="@if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) col-xl-12 @else col-xl-9 @endif col-lg-12 ">
                <div class="row g-3 g-xxl-4 ">
                    @include('resorts.talentacquisition.dashboard._kpi_strip')
                    <div class="col-lg-8 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        @include('resorts.talentacquisition.dashboard._open_vacancies_table')
                    </div>
                    <div class="col-lg-4 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card" id="card-todoList">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>To Do List</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('resort.ta.alltodolist') }}" class="a-link">View all</a>
                                    </div>
                                </div>

                            </div>
                            <div class="todoList-main" id="todoList-main">
                                <div class="octodoList-blk">
                                    @php
                                        $employee = Auth::guard('resort-admin')->user()->GetEmployee ?? null;
                                        $todoUserDeptId = $employee ? $employee->Dept_id : null;
                                        $todoUserDeptName = $todoUserDeptId ? \App\Models\ResortDepartment::where('id', $todoUserDeptId)->value('name') : '';
                                        $isHrUser = stripos($todoUserDeptName ?? '', 'Human Resources') !== false;
                                        $positionRankConfig = config('settings.Position_Rank');
                                    @endphp

                                    @if(isset($TodoData) && $TodoData->isNotEmpty())

                                        @foreach ($TodoData as $t)

                                            <div class="todoList-block">
                                                @if(!isset($t->ApplicantID) )
                                                    <div class="img-circle">
                                                        <img src="{{ Common::getResortUserPicture($t->user_id)}}" alt="image">
                                                    </div>
                                                    <div>

                                                        <p>{{ $t->rank_name }} approved the vacancy for {{ $t->Position ?? '' }}</p>
                                                        @if($t->LinkShareOrNot =="No")
                                                            <a  href="{{route('resort.ta.add.Questionnaire')}}"
                                                            target="_blank"
                                                               class="a-link">Before you create a job advertisement, you must first add a questionnaire</a>


                                                        @else
                                                        <a  href="javascript:void(0)"
                                                            data-Resort_id="{{ $t->Resort_id }}"
                                                            data-ta_childid="{{ $t->ta_childid }}"
                                                            data-ExpiryDate ="{{ $t->ExpiryDate}}" data-jobadvertisement="{{$t->JobAdvertisement}}" data-link="{{$t->adv_link}}"  data-applicationUrlshow="{{$t->applicationUrlshow}}" data-applicant_link="{{$t->applicant_link}}"
                                                            data-source_links="{{ json_encode($t->source_links) }}" data-position="{{ $t->Position }}" data-alljobimages="{{ json_encode($t->allJobAdImages) }}" data-bs-toggle="modal" class="a-link jobAD-modal">Create Job Advertisement</a>

                                                        @endif
                                                        </div>
                                                        <a href="{{ route('resort.ta.Applicants', base64_encode($t->V_id)) }}" class="ms-auto" title="View Applicants">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>

                                                    @elseif( $t->ApplicationStatus=="Sortlisted By Wisdom AI" && isset($t->ApplicantID) )
                                                        <div class="img-circle">
                                                            <img src="{{ $t->profileImg}}" alt="image">
                                                        </div>
                                                        <div>
                                                            <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} has applied for {{ $t->Position ?? '' }} &mdash; needs HR review</p>
                                                            <a href="{{ route('resort.ta.Applicants', base64_encode($t->V_id)) }}" class="a-link">Review Applicant</a>
                                                        </div>

                                                    @elseif( $t->ApplicationStatus=="Sortlisted" &&  $t->As_ApprovedBy != 0  &&  $t->InterviewLinkStatus == null )
                                                        <div class="img-circle">
                                                            <img src="{{ $t->profileImg}}" alt="image">
                                                        </div>
                                                        <div>
                                                            <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} is shortlisted for {{ $t->Position ?? '' }}</p>
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
                                                                <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} completed the {{ $completedRoundName }} round for {{ $t->Position ?? '' }} &mdash; ready for selection</p>
                                                            @else
                                                                <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} completed the {{ $completedRoundName }} round for {{ $t->Position ?? '' }} &mdash; ready for the {{ $nextRoundName }} round</p>
                                                            @endif
                                                            <a href="{{ route('resort.ta.Applicants', base64_encode($t->V_id)) }}" class="a-link">View Applicant</a>
                                                        </div>

                                                    @elseif( $isHrUser && $t->InterviewLinkStatus == 'Slot Booked' && empty($t->InterviewMeetingLink) )
                                                        <div class="img-circle">
                                                            <img src="{{ $t->profileImg}}" alt="image">
                                                        </div>
                                                        <div>
                                                            <p>{{ ucfirst($t->first_name).'  '.ucfirst($t->last_name) }} has accepted the interview invitation for {{ $t->Position ?? '' }}</p>
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
                                            <p>No pending items</p>

                                        </div>
                                    @endif
                                </div>

                            </div>

                        </div>
                    </div>
                    {{-- Top Hiring Sources / Top Countries / WAI Insights / New Hire
                         Requests moved out of this col-xl-9 wrapper — see the
                         full-width row right after row.recHR-main closes below.
                         Nested in here, they were capped at 75% of the page
                         width (col-xl-9's share), leaving a permanent blank
                         gap on the right where the Interview Calendar's
                         reserved col-xl-3 column ends up empty everywhere
                         except alongside the very top of the page. --}}
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card h-auto" id="card-interviewCalendar">
                    <div class="mb-4 overflow-hidden">
                        <div id="calendar"></div>
                    </div>
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-3">
                            <div class="col">
                                <h3>Upcoming Interviews</h3>
                            </div>
                        </div>
                    </div>
                    <div id="upinterviews" style="max-height: 320px; overflow-y: auto;">
                    </div>
                </div>
            </div>
                    <div class="col-lg-3 col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card" style="height: 450px; overflow: auto;">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>Talent Pool</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('resort.ta.TalentPool') }}" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="talentPool-main">
                                @if(isset($talentPool) &&   $talentPool->isNotEmpty())

                                    @foreach ($talentPool as $t)

                                        <div class="talentPool-block" id="talentPool_{{$t->id}}">
                                            <div class="img-circle">
                                                <img src="{{ URL::asset($t->passport_photo)}}" alt="image">
                                            </div>
                                            <div>
                                                <h6>{{ $t->first_name }} {{ $t->last_name }}</h6>
                                                <p>{{ $t->Comments }} </p>
                                                <a href="mailto:{{ $t->email }}" class="a-link">Consent Request</a>
                                            </div>
                                            <div class="icon">
                                                <a href="javascript:void(0);" class="delete-icon">
                                                        <i class="fa-regular destoryApplicant fa-trash-can" data-location="{{$t->id}}" data-id="{{ base64_encode($t->id) }}"></i>
                                                </a>
                                            </div>

                                        </div>
                                    @endforeach
                                @else
                                    <div>
                                        <p>No Data Reacord</p>

                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                    {{-- No d-none gate here. Finance (rank 7) and GM
                         (rank 8) land on this dashboard via the middleware
                         (RedirectIfNotCorrectDashboard) but DON'T hold the
                         HR-only `resort.vacancies.FreshApplicant` view
                         permission, so the gate used to hide the approval
                         card for them entirely — they'd see the dashboard
                         minus the column. Rank filtering on the controller
                         already ensures each user sees only the vacancies
                         that need THEIR action (Common::GetTheFreshVacancies). --}}
            @if(isset($approvalHistoryChains) && $approvalHistoryChains->count() > 0)
            @php
                // Presentation-only grouping of the SAME already-fetched
                // $approvalHistoryChains rows — no new query beyond the one
                // small, already-added lookup that fetches the complete
                // HR/Finance/GM chain (including still-"Active"/pending
                // stages) for whichever requisitions are recent. Grouped by
                // vacancy_id (the actual hiring requisition), NOT by
                // position_title/department_name text — two SEPARATE
                // requisitions can share the same position (e.g. two
                // different "Waitress" hires raised independently) and must
                // stay as two separate rows with their own approval chains,
                // never merged just because the position name matches.
                $groupedApprovalHistory = $approvalHistoryChains->groupBy('vacancy_id');
            @endphp
            <div class="col-lg-9 col-md-12">
                <div class="card h-auto appr-history-v2">
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-3">
                            <div class="col">
                                <h3>Approval History</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('resort.ta.ViewVacancies') }}" class="a-link">View all</a>
                            </div>
                        </div>
                    </div>
                    <div class="appr-history-list">
                        @foreach($groupedApprovalHistory as $historyRows)
                            @php
                                // Fixed HR -> Finance -> GM order (by rank),
                                // not by date — pending stages have no
                                // meaningful date to sort by.
                                $historyRows = $historyRows->sortBy('Approved_By')->values();
                                $historyFirst = $historyRows->first();
                            @endphp
                            <div class="appr-row">
                                <div class="appr-row-heading">
                                    <h6>{{ $historyFirst->position_title }}</h6>
                                    <span>{{ $historyFirst->department_name }}</span>
                                </div>
                                <div class="appr-chain">
                                    @foreach($historyRows as $history)
                                        @php
                                            $isMissing = !empty($history->is_missing);
                                            $isPending = $history->action_label === 'Pending' && !$isMissing;
                                            $pillClass = 'appr-chain-pill-approved';
                                            $pillIcon = 'fa-check';
                                            if ($isMissing) {
                                                $pillClass = 'appr-chain-pill-missing';
                                                $pillIcon = 'fa-triangle-exclamation';
                                            } elseif ($isPending) {
                                                $pillClass = 'appr-chain-pill-pending';
                                                $pillIcon = 'fa-clock';
                                            } elseif ($history->badge_class === 'bg-danger') {
                                                $pillClass = 'appr-chain-pill-rejected';
                                                $pillIcon = 'fa-xmark';
                                            } elseif ($history->badge_class === 'bg-warning') {
                                                $pillClass = 'appr-chain-pill-hold';
                                                $pillIcon = 'fa-pause';
                                            }
                                        @endphp
                                        <div class="appr-chain-item">
                                            @if($isMissing)
                                                {{-- No t_anotification_children row exists at all for
                                                     this rank on this requisition — a genuine gap in
                                                     the workflow, not a normal queued/pending stage.
                                                     Flagged distinctly (red) so it reads as "needs
                                                     attention" rather than a routine pending step. --}}
                                                <span class="appr-chain-pill {{ $pillClass }}"><i class="fa-solid {{ $pillIcon }}"></i> {{ $history->rank_name }} &mdash; No record</span>
                                                <span class="appr-chain-date appr-chain-date-missing">Missing from workflow</span>
                                            @elseif($isPending)
                                                {{-- No name shown for a pending stage — nobody has
                                                     acted on it yet, so there's no real approver to
                                                     attribute it to. --}}
                                                <span class="appr-chain-pill {{ $pillClass }}"><i class="fa-solid {{ $pillIcon }}"></i> {{ $history->rank_name }} &mdash; Pending</span>
                                                <span class="appr-chain-date">Awaiting action</span>
                                            @else
                                                <span class="appr-chain-pill {{ $pillClass }}"><i class="fa-solid {{ $pillIcon }}"></i> {{ $history->rank_name }} &mdash; {{ $history->action_by ?? 'N/A' }}</span>
                                                <span class="appr-chain-date">{{ $history->action_date ? \Carbon\Carbon::parse($history->action_date)->format('d M, h:i A') : 'N/A' }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- Full page-width row (not nested inside the col-xl-9 main column
             above, so it isn't capped at 75% width the way the row above
             is — that's what was leaving a permanent blank gap on the
             right where the Interview Calendar's reserved column ends up
             empty everywhere except near the very top of the page). Still
             inside container-fluid (sibling of row.recHR-main above) so it
             keeps the same side padding as the rest of the page. --}}
        <div class="row g-3 g-xxl-4 ta-toprow-section">
        <div class="col-lg-3 col-md-6">
            @include('resorts.talentacquisition.dashboard._top_hiring_sources')
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card ta-toprow-card">
                <div class="card-title">
                    <div class="row justify-content-between align-items-center g-3">
                        <div class="col">
                            <h3>Top Countries</h3>
                        </div>
                    </div>
                </div>
                <div class="table-responsive ta-toprow-scroll">
                    <table class="table table-collapse table-topCoun">
                        <tbody id="topCountriesWiseCount">
                            @if(isset($topCountries) && $topCountries->count() > 0)
                                @foreach($topCountries as $country)
                                    @php
                                        // countries.flag_url is empty in the prod DB, so derive
                                        // a CDN flag URL from countries.shortname (ISO-2).
                                        // w80 (80px wide source) rather than the old fixed
                                        // 24x18 — that was being upscaled to the 30px display
                                        // size in CSS (and further on any high-DPI/retina
                                        // screen), which is what made it look blurry/low-res.
                                        // Requesting a higher-res source and letting CSS scale
                                        // it down looks crisp at any pixel density.
                                        $flagSrc = !empty($country->flag_url)
                                            ? $country->flag_url
                                            : (!empty($country->country_code)
                                                ? 'https://flagcdn.com/w80/' . strtolower($country->country_code) . '.png'
                                                : asset('admin_assets/files/user-image.png'));
                                    @endphp
                                    <tr>
                                        <td><img src="{{ $flagSrc }}" alt="flag" class="flag" onerror="this.style.display='none';"> {{ $country->country }}</td>
                                        <td>{{ $country->total_count }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="3" class="text-center">No Data Found</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-wiINsight card-wiINsightTa ta-toprow-card wai-narrative" id="card-wiINsightTa">
                @php $taMeta = $taInsights['_meta'] ?? null; @endphp
                <div class="wai-head">
                    <h2>WAI Insights</h2>
                    @if($taMeta)
                        <div class="wai-head-meta">
                            <span>Updated {{ $taMeta['generated_at']->diffForHumans() }}</span>
                            @if($taMeta['can_regenerate'])
                                <a href="?regenerate_insights=1">Regenerate</a>
                            @else
                                <span title="{{ $taMeta['next_available']->format('d M Y, H:i') }}">&middot; Regenerate {{ $taMeta['next_available']->diffForHumans() }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="leaveUser-main wai-narrative-body">
                    @foreach([['key'=>'rejection','modal'=>'taInsightRejectionModal'],['key'=>'funnel','modal'=>'taInsightFunnelModal'],['key'=>'acceptance','modal'=>'taInsightAcceptanceModal'],['key'=>'tth','modal'=>'taInsightTthModal'],['key'=>'demand','modal'=>'taInsightDemandModal']] as $tc)
                        @php $hasRecommendation = !empty($taInsights[$tc['key']]['recommendation']); @endphp
                        <div class="wai-row">
                            <div class="wai-row-icon {{ $hasRecommendation ? 'is-flagged' : 'is-ok' }}">
                                <i class="fa-solid {{ $hasRecommendation ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                            </div>
                            <div class="wai-row-body">
                                <h6>{{ $taInsights[$tc['key']]['title'] ?? '' }}</h6>
                                <p class="wai-row-text">{{ $taInsights[$tc['key']]['body'] ?? '' }}</p>
                                @if($hasRecommendation)
                                    <p class="wai-row-recommendation"><strong>Recommendation:</strong> {{ $taInsights[$tc['key']]['recommendation'] }}</p>
                                @endif
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#{{ $tc['modal'] }}" class="wai-row-link">View details &rarr;</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            @include('resorts.talentacquisition.dashboard._new_hire_requests_card')
        </div>
    </div>
    </div>
</div>
<div class="modal fade" id="FreshRespond-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="respond-main"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#respond-HoldModel" id="holdResponseModel" data-bs-toggle="modal"  data-bs-dismiss="modal" class="btn ta-btn-attention">On Hold</a>
                <a href="#respond-rejectModal" id="RejectResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn ta-btn-attention">Reject</a>
                <a href="javascript:void(0)" id="ApprovedResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn ta-btn-positive">Approved</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="respond-HoldModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="HoldNewVacanciyForm">
                @csrf
                <div class="modal-body">
                    <label class="form-label mb-8">Select date</label>
                    <div class="modalCalendar-block">
                        <div id="calendarModal"></div>
                        <input type="date" style="display:none" id="HoldDate" name="HoldDate">
                        <input type="hidden" id="Calender_ta_id" name="ta_id">


                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <button type="submit" class="btn ta-btn-primary">Submit</button>
                </div>
            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="respond-rejectModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectionNewVacanciyForm">
                    @csrf
                    <div class="modal-body">
                        <textarea class="form-control" rows="7" name="New_Vacancy_Rejected" placeholder="Reason for Rejection"></textarea>
                    </div>
                    <input type="hidden" id="Rejectio_ta_id" name="Rejectio_ta_id">

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                        <button type="submit"  class="btn ta-btn-primary">Submit</button>
                    </div>
                </form>
            </div>

    </div>
</div>

<div class="modal fade" id="respond-approvalModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered  modal-small modal-respondApp">
        <div class="modal-content">
            <div class="modal-header border-0">
                <!-- <h5 class="modal-title" id="staticBackdropLabel">Manning has been sent!</h5> -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ URL::asset('resorts_assets/images/check-circle.svg')}}" alt="icon">
                <h4>submission confirmation</h4>
                <p id="rejaction_msg"></p>
                <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary">Close</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="InterviewRequestSentForm">
                @csrf
                <div class="modal-body">
                    <label class="form-label mb-8">Select date</label>
                    <div class="modalCalendar-block">
                        <div id="calendarModalSendInterView"></div>


                        <input type="date" class="InterviewDateModel"  id="InterviewDate" name="InterviewDate">

                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <button type="submit" class="btn ta-btn-primary">Submit</button>
                </div>
            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="TimeSlots-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered modal-small modal-timeSlotsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="TimeSlotsForm">
                @csrf
                <div class="modal-body">
                    <label>Select Email Template </label>
                    <select class="form-control EmailTemplate" name='EmailTemplate'>
                        <option selected disabled value="">Select Email Template </option>
                        @if(isset($EmailTamplete))
                        @foreach ($EmailTamplete as $e)
                            <option value="{{ $e->id}}">{{ $e->TempleteName }}</option>
                        @endforeach
                        @endif
                    </select>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Meeting Link</label>
                        <input type="text" class="form-control" name="MeetingLink" placeholder="Enter Meeting Link (Google Meet, Zoom, etc.)">
                    </div>
                    <label class="form-label mb-sm-4 mb-3">SELECT TIME SLOTS</label>
                    <div class="sendRequestTime-main">
                    </div>
                    <input type="hidden" id="Resort_id" name="Resort_id">
                    <input type="hidden" id="ApplicantID" name="ApplicantID">
                    <input type="hidden" id="ApplicantStatus_id" name="ApplicantStatus_id">
                    <input type="hidden" id="Calender_ta_id" name="ta_id">
                    <input type="date" style="display: none;"  id="TimeSlotsFormdate" name="TimeSlotsFormdate">

                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <button type="submit" class="btn ta-btn-primary">Submit</button>

                </div>
            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="sendRequestFinal-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
            </div>
            <div class="modal-body pb-0">
                <div class="table-responsive">
                    <table class="table table-sendRequestFinal w-100">
                        <tbody id="Final_response_data">

                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="review_interview_id" value="">
                <input type="hidden" id="review_email_template_id" value="">
            </div>
            <div class="modal-footer justify-content-center">
                <a href="javascript:void(0)" id="cancelPendingInterview" class="btn ta-btn-secondary ms-auto">Cancel</a>
                <a href="javascript:void(0)" id="confirmSendInterviewEmail" class="btn ta-btn-attention">Submit</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelSlot-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Interview Slot</h5>
            </div>
            <div class="modal-body">
                <p>If you cancel, all saved slot information will be deleted and you will need to book a slot again.</p>
                <p><strong>Are you sure?</strong></p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="javascript:void(0)" id="cancelSlotNo" class="btn ta-btn-secondary ms-auto">No, Go Back</a>
                <a href="javascript:void(0)" id="cancelSlotYes" class="btn ta-btn-critical">Yes, Delete Slot</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMeetingLink-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Meeting Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addMeetingLinkForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Please provide the meeting link for interview</label>
                        <input type="text" class="form-control" name="MeetingLink" placeholder="Meeting Link" required>
                    </div>
                    <input type="hidden" name="Interview_id" id="MeetingLink_Interview_id">
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <button type="submit" class="btn ta-btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="jobAD-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-jobAD">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Job Advertisement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jobAD-form">
                @csrf
                <div class="modal-body">
                <p>Would you like to advertise below poster for job post for <strong id="jobAdPositionName"></strong>?</p>
                <div id="jobAdCarousel" class="carousel slide mb-sm-4 mb-3" data-bs-interval="false">
                    <div class="carousel-inner text-center" id="jobAdCarouselInner">
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#jobAdCarousel" data-bs-slide="prev" id="jobAdPrevBtn" style="display:none;">
                        <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 10px;"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#jobAdCarousel" data-bs-slide="next" id="jobAdNextBtn" style="display:none;">
                        <span class="carousel-control-next-icon" aria-hidden="true" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 10px;"></span>
                    </button>
                </div>
                <div class="text-center mb-sm-4 mb-3">
                    <a href="javascript:void(0)" class="DowloadAdvertisement btn ta-btn-secondary btn-sm">Download</a>
                </div>
                <div class="input-group mb-sm-4 mb-3">
                    <input type="text" class="form-control datepicker" name="link_Expiry_date" id="link_Expiry_date" placeholder="Expiry Date" />
                </div>
                <div class="text-center mb-sm-3 mb-2">
                    <input type="hidden" class="form-control link_Job" name="link" placeholder="Job Advertisement Link" />
                    <input type="hidden" class="form-control Resort_id" name="Resort_id" value="{{$resort_id}}"/>
                    <input type="hidden" class="form-control ta_child_id" name="ta_child_id" placeholder="Job Advertisement Link" />
                    <a href="javascript:void(0)" target="blank" class="a-link AppendJobAdvLink"></a>
                </div>
                <div class="source-links-container">
                    <h6>Source Links:</h6>
                    <ul id="sourceLinksList">
                        <!-- Links will be appended dynamically -->
                    </ul>
                </div>
            </div>

            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                <button  class="btn ta-btn-primary JdSumit">Submit</button>
            </div>

        </div>
        </form>
    </div>
</div>
<input type="hidden" name="Dasboard_resort_id" value="{{$resort_id}}" id="Dasboard_resort_id" >
@includeWhen(isset($taInsights), 'resorts.talentacquisition.dashboard._insight_modals')
@include('resorts.talentacquisition.dashboard._ta_widgets_v2_styles')
@endsection

@section('import-css')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
<style>
    /* WAI Insights — third column alongside Talent Pool & New Hire Requests.
       Fixed height (aligns with the 450px Talent Pool card) with the insight
       list scrolling inside its own space rather than stretching the row. */
    .card-wiINsightTa {
        height: 450px !important;
        max-height: 450px !important;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
        border-radius: 16px;
    }
    .card-wiINsightTa .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    /* WAI Insights — same gradient header as the other WAI Insights cards
       (Time and Attendance, Payroll). This card's own 5 checks (rejection,
       funnel, acceptance, time-to-hire, demand) are narrative AI insights —
       title + descriptive body + optional recommendation — not pass/fail
       compliance counts, so no hero/count here either, same reasoning as
       Payroll's version: icon is amber when there's a recommendation worth
       acting on, teal tick when the insight is purely informational. */
    .wai-narrative .wai-head { position: relative; overflow: hidden; padding: 17px 18px; flex-shrink: 0; }
    .wai-narrative .wai-head::before {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, #014653 0%, #0e8a9e 40%, #7fa61e 70%, #e0ff02 100%);
    }
    .wai-narrative .wai-head::after {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, rgba(1,40,48,.35), transparent 55%);
    }
    .wai-narrative .wai-head h2 { position: relative; color: #fff; font-size: 15px; font-weight: 800; margin: 0; }
    .wai-narrative .wai-head-meta { position: relative; margin-top: 4px; font-size: 11.5px; color: rgba(255,255,255,.75); display: flex; gap: 6px; }
    .wai-narrative .wai-head-meta a { color: #fff; font-weight: 600; text-decoration: underline; }

    .wai-narrative-body { padding: 16px; }
    .wai-narrative .wai-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 2px; border-bottom: 1px solid #F2F6F6; }
    .wai-narrative .wai-row:last-child { border-bottom: none; }
    .wai-narrative .wai-row-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; margin-top: 2px; }
    .wai-narrative .wai-row-icon.is-ok { background: #E9F7F0; color: #1F9D6B; }
    .wai-narrative .wai-row-icon.is-flagged { background: #FBF0DC; color: #D98A00; }
    .wai-narrative .wai-row-body { flex: 1 1 auto; min-width: 0; }
    .wai-narrative .wai-row-body h6 { margin: 0 0 4px; font-size: 13.5px; font-weight: 700; color: #14232A; }
    .wai-narrative .wai-row-text { margin: 0 0 4px; font-size: 12.5px; color: #5D6F75; line-height: 1.5; }
    .wai-narrative .wai-row-recommendation { margin: 0 0 4px; font-size: 12.5px; color: #0e8a9e; line-height: 1.5; }
    .wai-narrative .wai-row-link { display: inline-block; margin-top: 2px; font-size: 12px; font-weight: 600; color: #014653; }
    .th-upcoming-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 14px 0 4px;
        margin-top: 4px;
        border-top: 1px dashed #E7E7E7;
        font-size: 12px;
        color: #93A4A9;
    }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  var isDateSelected = false;
    $(".table-icon").click(function () {
        $(this).parents('tr').toggleClass("in");
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // full-calendar
    $(function () {
        var todayDate = moment().startOf('day');
        var YM = todayDate.format('YYYY-MM');
        var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
        var TODAY = todayDate.format('YYYY-MM-DD');
        var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

        // #card-interviewCalendar's outer height is forced (via
        // equalizeHeights() below) to match the Vacancies/To Do row's
        // bottom edge. The calendar grid + #upinterviews list often don't
        // fill that whole height on their own, which left a large blank
        // gap under a short interview list. Appending a small "end of
        // list" note makes that leftover space read as intentional
        // instead of looking broken.
        function thAppendUpcomingFooter() {
            var $list = $('#upinterviews');
            $list.find('.th-upcoming-footer').remove();
            var $blocks = $list.find('.upInterviews-block');
            var isNoRecordState = $blocks.length === 1 && $blocks.first().text().trim() === 'No Record Found';
            if ($blocks.length === 0 || isNoRecordState) return;
            $list.append('<div class="th-upcoming-footer"><i class="fa-regular fa-circle-check"></i> That\'s everything scheduled for now</div>');
        }

        var cal = $('#calendar').fullCalendar({
            header: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            editable: true,
            eventLimit: 0, // Allow "more" link when too many events
            navLinks: false,
            // Render at natural content height (no internal vertical scrollbar).
            height: 'auto',
            contentHeight: 'auto',
            events: function(start, end, timezone, callback) {
                let Resort_id = $("#Dasboard_resort_id").val();

                $.ajax({
                    url: "{{ route('resort.ta.GetDateclickWiseUpcomingInterview') }}",
                    type: "POST",
                    data: {
                        start: start.format('YYYY-MM-DD'),
                        end: end.format('YYYY-MM-DD'),
                        Resort_id: Resort_id,
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        $("#upinterviews").html(response.view);
                        thAppendUpcomingFooter();
                        $('.fc-day').removeClass('custom-dot');

                        response.dates.forEach(function(date) {
                            let formattedDate = moment(date).format('YYYY-MM-DD');
                            let dayCell = $(`.fc-day[data-date="${formattedDate}"]`);
                            if (dayCell.length)
                            {
                                dayCell.addClass('custom-dot');
                            }
                        });
                        callback([]);
                    },
                    error: function(xhr) {
                        console.error("Error fetching interview dates", xhr);
                    }
                });
            },
            dayClick: function(date, jsEvent, view) {

                    let Resort_id = $("#Dasboard_resort_id").val();
                    $.ajax({
                        url: "{{ route('resort.ta.GetDateclickWiseUpcomingInterview') }}",
                        type: "POST",
                        data: {
                            date: date.format('YYYY-MM-DD'), // Format the date properly
                            Resort_id: Resort_id,
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {

                            if (response.success) {


                                $("#upinterviews").html(response.view);
                                thAppendUpcomingFooter();

                            } else {
                                // Display error message if success is false
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';

                            // Adjust based on response format
                            if (errors && errors.errors) {
                                $.each(errors.errors, function(key, error) {
                                    console.log(error);
                                    errs += error + '<br>';
                                });
                            } else {
                                errs = "An unexpected error occurred.";
                            }

                            // Display errors
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
        });
    });


        $('#respond-HoldModel').on('shown.bs.modal', function () {
            $('#calendarModal').fullCalendar('render');
        });

        $('#sendRequest-modal').on('shown.bs.modal', function () {
            $('#calendarModalSendInterView').fullCalendar('render');
        });

        $(function () {
            var todayDate = moment().startOf('day');
            var YM = todayDate.format('YYYY-MM');
            var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
            var TODAY = todayDate.format('YYYY-MM-DD');
            var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

            // Calendar for respond modal
            $('#calendarModal').fullCalendar({
                header: {
                        left: 'prev',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0,
                    navLinks: false,
                    selectable: true,
                    select: function(start, end) {
                      var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                      $("#HoldDate").val(selectedStartDate);
                      isDateSelected = true;
                      $("#respond-HoldModel").modal("show");
                    },
            });

            // Calendar for send request modal
            $('#calendarModalSendInterView').fullCalendar({
                header: {
                        left: 'prev',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0,
                    navLinks: false,
                    selectable: true,
                    select: function(start, end) {
                      var selectedStartDate = start.format('YYYY-MM-DD');  // Format as you need
                      $("#InterviewDate").val(selectedStartDate);
                      $("#TimeSlotsFormdate").val(selectedStartDate);
                      $("#sendRequest-modal").modal("show");
                    }
            });
        });


        //    equal heigth js
        function equalizeHeights() {
            // Get the elements
            const block1 = document.getElementById('card-vac');
            const block2 = document.getElementById('card-todoList');
            const block3 = document.getElementById('card-interviewCalendar');
            if (!block1) return;

            // Get the height of block1
            const block1Height = block1.offsetHeight;
            if (!block1Height) return;

            // Set the height of block2 to match block1's height. Using
            // setProperty(..., "important") since these cards carry the
            // global .h-auto class (height:auto) — a plain .style.height
            // assignment already wins the cascade against a non-important
            // class rule, but forcing "important" removes any doubt.
            if (block2) block2.style.setProperty('height', block1Height + 'px', 'important');

            // Interview Calendar sits in a column that's a sibling of the
            // WHOLE main content column (top-aligned with the KPI strip),
            // while Vacancies/To Do List are in a separate inner row that
            // starts further down (below the KPI strip). So giving the
            // calendar the SAME height as Vacancies (like block2 above)
            // still left its bottom edge well short of Vacancies/To Do's
            // bottom edge — same height, but a higher starting point.
            // Instead, size it using actual on-screen positions so its own
            // bottom edge lines up with Vacancies' bottom edge exactly.
            if (block3) {
                const vacRect = block1.getBoundingClientRect();
                const calRect = block3.getBoundingClientRect();
                const targetHeight = vacRect.bottom - calRect.top;
                if (targetHeight > 0) {
                    block3.style.setProperty('height', targetHeight + 'px', 'important');
                }
            }
        }

        // addEventListener (not window.onload = ...) so this can never be
        // silently clobbered by another handler assigned later, and re-runs
        // on load, on resize, AND whenever #card-vac's own size changes
        // (e.g. its table finishes rendering/wrapping a tick after "load") —
        // a plain one-shot window.onload measurement was found to sometimes
        // run before #card-vac's true height had settled.
        document.addEventListener('DOMContentLoaded', equalizeHeights);
        window.addEventListener('load', equalizeHeights);
        window.addEventListener('resize', equalizeHeights);
        setTimeout(equalizeHeights, 500);

        if (window.ResizeObserver) {
            var cardVacEl = document.getElementById('card-vac');
            if (cardVacEl) {
                new ResizeObserver(equalizeHeights).observe(cardVacEl);
            }
        }

        //New Code End

    $(document).ready(function() {

        $(document).on("click", ".respondOfFreshmodal", function() {

            // FreshRespond-modal
            $('#FreshRespond-modal').modal('show');
            var image= $(this).attr("data-images");
            var name = $(this).attr("data-name");
            var position = $(this).attr("data-position");
            var department = $(this).attr("data-departmentname");
            var NoOfVacnacy = $(this).attr("data-NoOfVacnacy");
            var rank = $(this).attr('data-rank');
            var ta_id= $(this).attr('data-ta_id');
            var Child_ta_id= $(this).attr('data-Child_ta_id');
            var createdBy = $(this).attr('data-createdby');
            var creatorRank = $(this).attr('data-creatorrank');

            $("#holdResponseModel").attr("data-ta_id",ta_id);
            $("#RejectResponseModel").attr("data-ta_id",ta_id);
            $("#ApprovedResponseModel").attr("data-ta_id",ta_id);
            $("#ApprovedResponseModel").attr("data-Child_ta_id",Child_ta_id);

            $("#holdResponseModel").attr("data-Child_ta_id",Child_ta_id);
            $("#RejectResponseModel").attr("data-Child_ta_id",Child_ta_id);


            let hm =`<div class="respond-block">
                                <div class="img-circle">
                                    <img src="${image}" alt="image">
                                </div>
                                <div>
                                    <h6>${department} (${rank})</h6>
                                    <p><strong>${createdBy} (${creatorRank})</strong> Requested for Hire ${NoOfVacnacy} ${position}</p>
                                </div>

                    </div>`;
                $(".respond-main").html(hm);
        });

        // Hold Request Start

        $(document).on("click", "#holdResponseModel", function() {
            var Child_ta_id= $(this).attr('data-Child_ta_id');

            $("#Calender_ta_id").val(Child_ta_id);


        });
        $(document).on("click", ".destoryApplicant", function() {
            var base64_id= $(this).attr('data-id');
            var location= $(this).attr('data-location');
                 $.ajax({
                    url: "{{ route('resort.ta.destoryApplicant') }}",
                    type: "POST",
                    data: {base64_id:base64_id,"_token":"{{ csrf_token() }}" },

                    success: function(response) {
                        $('#respond-rejectModal').modal('hide');
                        if (response.success)
                        {

                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#talentPool_"+location).remove();

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            console.log(error);
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });

        });

        $('#HoldNewVacanciyForm').validate({
            rules: {
                HoldDate: {
                    required: true,
                }
            },
            messages: {
                HoldDate: {
                    required: "Please select Hold Date.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);
                if (!isDateSelected) {

                    toastr.error("Please select a date from the calendar.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return false;
                }

                $.ajax({
                    url: "{{ route('resort.ta.HiringNotification') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#respond-HoldModel').modal('hide');
                        if (response.success)
                        {

                            $("#FreshHiringRequest").html(response.view);



                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                    // error: function(response) {
                    //     var errors = response.responseJSON;
                    //     var errs = '';
                    //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                    //         errs += error + '<br>';
                    //     });
                    //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    // }
                });
            }
        });

        // End of Hold Request.

        // Reject Vacanciy form
        $(document).on("click", "#RejectResponseModel", function() {
            var Child_ta_id= $(this).attr('data-Child_ta_id');

            $("#Rejectio_ta_id").val(Child_ta_id);

        });

        $('#rejectionNewVacanciyForm').validate({
            rules: {
                New_Vacancy_Rejected: {
                    required: true,
                }
            },
            messages :
            {
                New_Vacancy_Rejected: {
                    required: "Please Enter Reason.",
                }
            },
            submitHandler: function(form) {

                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.ta.RejectionVcancies') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#respond-rejectModal').modal('hide');
                        if (response.success)
                        {

                            $("#FreshHiringRequest").html(response.view);
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                    // ,
                    // error: function(response) {
                    //     var errors = response.responseJSON;
                    //     var errs = '';
                    //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                    //         console.log(error);
                    //         errs += error + '<br>';
                    //     });
                    //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    // }
                });
            }
        });

        // End of Reject Vacanciy form
        //  Approval

        $('#link_Expiry_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $("#ApprovedResponseModel").on("click",function(){
            var ta_id= $(this).attr('data-ta_id');
            var Child_ta_id = $(this).attr('data-Child_ta_id');
            $.ajax({
                    url: "{{ route('resort.ta.ApprovedVcancies') }}",
                    type: "POST",
                    data: {ta_id:ta_id,Child_ta_id:Child_ta_id,"_token":"{{ csrf_token() }}" },

                    success: function(response) {
                        $('#respond-rejectModal').modal('hide');
                        if (response.success)
                        {
                            $('#respond-approvalModal').modal('show');
                            $("#FreshHiringRequest").html(response.view);
                            $(".todoList-main").html(response.Todolistview);
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            console.log(error);
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
        });

        $(document).on("click", ".jobAD-modal", function () {
            $("#jobAD-modal").modal("show");

            // Set position name in modal
            let positionName = $(this).data("position");
            $("#jobAdPositionName").text(positionName);

            // Fetch data attributes
            let applicationUrlShow = $(this).data("applicationurlshow");
            let applicantLink = $(this).data("applicant_link");
            let jobAdv = $(this).data("jobadvertisement");
            let jobLink = $(this).data("link");
            let childId = $(this).data("ta_childid");
            let expiryDate = $(this).data("expirydate");
            let sourceLinks = $(this).data("source_links");
            let allJobImages = $(this).data("alljobimages") || [];

            // Build carousel images
            let carouselInner = $("#jobAdCarouselInner");
            carouselInner.empty();
            if (allJobImages.length > 0) {
                $.each(allJobImages, function(i, imgUrl) {
                    let activeClass = i === 0 ? 'active' : '';
                    carouselInner.append('<div class="carousel-item ' + activeClass + '"><img src="' + imgUrl + '" alt="Job Advertisement" style="max-width:100%;"></div>');
                });
                // Show/hide arrows based on image count
                if (allJobImages.length > 1) {
                    $("#jobAdPrevBtn, #jobAdNextBtn").show();
                } else {
                    $("#jobAdPrevBtn, #jobAdNextBtn").hide();
                }
                // Set download link to first image
                $(".DowloadAdvertisement").attr("data-hrefLink", allJobImages[0]);
            } else {
                carouselInner.append('<div class="carousel-item active"><img src="' + jobAdv + '" alt="Job Advertisement" style="max-width:100%;"></div>');
                $("#jobAdPrevBtn, #jobAdNextBtn").hide();
                $(".DowloadAdvertisement").attr("data-hrefLink", jobAdv);
            }

            $(".JdSumit").show();
            // Set values in the modal
            $(".ta_child_id").val(childId);
            $(".AppendJobAdvLink").attr("href", applicantLink).text(applicationUrlShow);

            if (jobLink === "") {
                $(".AppendJobAdvLink")
                    .attr("href", applicantLink)
                    .text(applicationUrlShow)
                    .attr("data-disabled", "true")
                    .addClass("ta-adv-disabled");
                $(".Resort_id").val($(this).attr("data-Resort_id"));
                $(".JdSumit").show();
            } else {
                $(".AppendJobAdvLink")
                    .attr("href", applicantLink)
                    .text(applicationUrlShow)
                    .attr("data-disabled", "false")
                    .removeClass("ta-adv-disabled");
                $("#link_Expiry_date").addClass("link_Expiry_date_" + childId);
                $(".link_Expiry_date_" + childId).attr("disabled", "true");
                $(".JdSumit").hide();
            }

            if (expiryDate) {
                var parts = expiryDate.split("-");
                var formattedDate = parts[2] + "/" + parts[1] + "/" + parts[0];
                $("#link_Expiry_date").datepicker("setDate", formattedDate);
            }

            $(".link_Job").val(applicantLink).addClass("link_Job_");

            // Handle Source Links
            let sourceLinksList = $("#sourceLinksList");
            let sourceLinksHidden = $("#sourceLinksHidden");
            sourceLinksList.empty(); // Clear previous links

            if (sourceLinks && sourceLinks.length) {
                sourceLinksHidden.val(JSON.stringify(sourceLinks)); // Save links in hidden input
                sourceLinks.forEach((link) => {
                    let listItem = $("<li></li>");
                    let anchor = $("<a></a>")
                        .attr("href", link)
                        .attr("target", "_blank")
                        .text(link);
                    listItem.append(anchor);
                    sourceLinksList.append(listItem);
                });
            } else {
                sourceLinksHidden.val(applicantLink); // Save default applicant link in hidden input
                sourceLinksList.append(`<li><a href="${applicantLink}" target="_blank">${applicantLink}</a></li>`); // Display the default applicant link
            }
        });

        $(".AppendJobAdvLink").on('click', function (e) {
            if ($(this).attr('data-disabled') === 'true')
             {
                e.preventDefault();
            }
        });

        // Update download link when carousel slides
        $('#jobAdCarousel').on('slid.bs.carousel', function () {
            var activeImg = $(this).find('.carousel-item.active img').attr('src');
            $(".DowloadAdvertisement").attr("data-hrefLink", activeImg);
        });

        $(document).on("click", ".DowloadAdvertisement", function() {

            var fileName = $(this).attr('data-hrefLink');

            var link = document.createElement('a');

            link.href = fileName;

            link.download = fileName.split('/').pop();  // This extracts the file name from the URL

            document.body.appendChild(link);

            link.click();

            document.body.removeChild(link);

        });

      
        $('#jobAD-form').validate({
            rules: {
                link_Expiry_date: {
                    required: true,
                }
            },
            messages :
            {
                link_Expiry_date: {
                    required: "Please Select Expiry Date.",
                }
            },
            submitHandler: function(form) {

                var formData = new FormData(form);


                $.ajax({
                    url: "{{ route('resort.ta.GenrateAdvLink') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#respond-rejectModal').modal('hide');
                        if (response.success)
                        {

                            $(form)
                            .find('a')
                            .removeClass('ta-adv-disabled')
                            .attr('data-disabled', 'false')
                            if(response.view) {
                                $("#FreshHiringRequest").html(response.view);
                            }
                            if(response.Todolistview) {
                                $(".todoList-main").html(response.Todolistview);
                            }
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                                 $("#jobAD-modal").modal("hide");


                        } 
                        else
                        {

                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                            
                                 $("#jobAD-modal").modal("hide");
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            console.log(error);
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });

        //SortListed Employee
        $(document).on("click", ".SortlistedEmployee", function()
        {


                let resort_id= $(this).data('resort_id');
                let ApplicantID= $(this).data('applicantid');
                let ApplicantStatus_id= $(this).data('applicantstatus_id');
                $("#Resort_id").val(resort_id);
                $("#ApplicantID").val(ApplicantID);
                $("#ApplicantStatus_id").val(ApplicantStatus_id);
                $("#sendRequest-modal").modal("show");

        });

        $('#InterviewRequestSentForm').validate({
            rules: {
                InterviewDate: {
                    required: true,
                }
            },
            messages :
            {
                InterviewDate: {
                    required: "Please Select Inteview Date.",
                }
            },
            submitHandler: function(form) {
                let Resort_id = $("#Resort_id").val();
                let ApplicantID = $("#ApplicantID").val();
                let ApplicantStatus_id = $("#ApplicantStatus_id").val();
                let InterviewDate = $('#InterviewDate').val();

                $.ajax({
                    url: "{{ route('resort.ta.ApplicantTimeZoneget') }}",
                    type: "POST",
                    data:{InterviewDate:InterviewDate,Resort_id:Resort_id, ApplicantID:ApplicantID, ApplicantStatus_id:ApplicantStatus_id,"_token":"{{ csrf_token()}}"},

                    success: function(response) {
                        if (response.success)
                        {

                            toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                            });
                            InterViewDate = response.InterviewDate;
                            $("#sendRequest-modal").modal("hide");
                            $("#TimeSlots-modal").modal("show");
                            $(".sendRequestTime-main").html(response.view);

                        }
                        else
                        {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                    // ,
                    // error: function(response) {
                    //     var errors = response.responseJSON;
                    //     var errs = '';
                    //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                    //         console.log(error);
                    //         errs += error + '<br>';
                    //     });
                    //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    // }
                });
            }
        });

        // Multi-select time slots - click on row for Safari compatibility
        $(document).on("click", ".row_time:not(.disable)", function(e) {
            if ($(e.target).is('input[type="hidden"]')) return;

            var $row = $(this);
            var $checkbox = $row.find(".Timezone_checkBox");

            // Toggle this row
            $row.toggleClass("active");
            $checkbox.prop("checked", $row.hasClass("active"));

            // Clear manual time fields when selecting slots
            $('[name="MalidivanManualTime"]').val('');
            $('[name="ApplicantManualTime"]').val('');
            $('[name="MalidivanManualTime1"]').val('');
            $('[name="ApplicantManualTime1"]').val('');

            // Collect all selected slot times
            var resortTimes = [];
            var applicantTimes = [];
            $(".row_time.active .Timezone_checkBox").each(function() {
                resortTimes.push($(this).data('resortinterviewtime'));
                applicantTimes.push($(this).data('applicantinterviewtime'));
            });
            $("#ResortInterviewtime_collected").val(resortTimes.join(', '));
            $("#ApplicantInterviewtime_collected").val(applicantTimes.join(', '));
        });

        // Clear selected slots when manual time is focused
        $(document).on("focus", '[name="MalidivanManualTime"]', function () {
            $(".row_time").removeClass("active");
            $(".row_time .Timezone_checkBox").prop("checked", false);
            $("#ResortInterviewtime_collected").val('');
            $("#ApplicantInterviewtime_collected").val('');
        });

        $(document).on("change", '[name="MalidivanManualTime"]', function () {
            const timeValue = $(this).val();
            if (timeValue) {
                const resortTz = $('#resortTimezone').val();
                const applicantTz = $('#applicantTimezone').val();

                const [hours, minutes] = timeValue.split(":");
                const period = hours >= 12 ? "PM" : "AM";
                const formattedHours = hours % 12 || 12;
                let MalidivanManualTime1 = formattedHours + ":" + minutes + " " + period;
                $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1);

                var resortMoment = moment.tz(timeValue, 'HH:mm', resortTz);
                var applicantMoment = resortMoment.clone().tz(applicantTz);
                var applicantTime24 = applicantMoment.format('HH:mm');
                var applicantTime12 = applicantMoment.format('h:mm A');

                $('[name="ApplicantManualTime"]').val(applicantTime24);
                $('[name="ApplicantManualTime1"]').val(applicantTime12);
            } else {
                $('[name="ApplicantManualTime"]').val('');
                $('[name="ApplicantManualTime1"]').val('');
                $('[name="MalidivanManualTime1"]').val('');
            }
        });

        $('#TimeSlotsForm').validate({
            rules: {
                MeetingLink: {
                    required: true,
                },
                "SlotBook[]": {
                    required: function () {
                        return $('[name="MalidivanManualTime"]').val().trim() === "";
                    },
                },
                MalidivanManualTime: {
                    required: function () {
                        return $('[name="SlotBook[]"]:checked').length === 0;
                    },
                },
            },
            messages: {
                MeetingLink: {
                    required: "Please enter a Meeting Link.",
                },
                "SlotBook[]": {
                    required: "Please select a valid time slot or enter a manual time.",
                },
                MalidivanManualTime: {
                    required: "Please enter your time or select a valid time slot.",
                },
            },
            errorPlacement: function(error, element) {
                if (element.hasClass("Timezone_checkBox")) {
                    element.closest(".sendRequestTime-main").find(".block").after(error);
                } else {
                    error.insertAfter(element);
                }
            },
                submitHandler: function(form) {
                    var $submitBtn = $(form).find('button[type="submit"]');
                    $submitBtn.prop('disabled', true).text('Submitting...');
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.InterviewRequest') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            if (response.success) {
                                $("#sendRequest-modal").modal("hide");
                                $("#TimeSlots-modal").modal("hide");
                                $("#todoList-main").html(response.TodoDataview);
                                $("#Final_response_data").html(response.Final_response_data);
                                $("#review_interview_id").val(response.interview_id);
                                $("#review_email_template_id").val(response.email_template_id);
                                $("#sendRequestFinal-modal").modal("show");
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function() {
                            toastr.error("Something went wrong. Please try again.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $submitBtn.prop('disabled', false).text('Submit');
                        }
                });
            }
        });

        // Review modal - Confirm and send interview email
        $(document).on("click", "#confirmSendInterviewEmail", function() {
            var $btn = $(this);
            $btn.addClass('disabled').text('Sending...');

            $.ajax({
                url: "{{ route('resort.ta.SendInterviewEmail') }}",
                type: "POST",
                data: {
                    interview_id: $("#review_interview_id").val(),
                    email_template_id: $("#review_email_template_id").val(),
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $("#sendRequestFinal-modal").modal("hide");
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function() {
                    $("#sendRequestFinal-modal").modal("hide");
                    toastr.error("Something went wrong. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function() {
                    $btn.removeClass('disabled').text('Submit');
                }
            });
        });

        // Review modal - Cancel button opens confirmation modal
        $(document).on("click", "#cancelPendingInterview", function() {
            $("#sendRequestFinal-modal").modal("hide");
            setTimeout(function() {
                $("#confirmCancelSlot-modal").modal("show");
            }, 300);
        });

        // Cancel confirmation - No, go back to review modal
        $(document).on("click", "#cancelSlotNo", function() {
            $("#confirmCancelSlot-modal").modal("hide");
            setTimeout(function() {
                $("#sendRequestFinal-modal").modal("show");
            }, 300);
        });

        // Cancel confirmation - Yes, delete slot data
        $(document).on("click", "#cancelSlotYes", function() {
            var $btn = $(this);
            $btn.addClass('disabled').text('Deleting...');

            $.ajax({
                url: "{{ route('resort.ta.DeletePendingInterview') }}",
                type: "POST",
                data: {
                    interview_id: $("#review_interview_id").val(),
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $("#confirmCancelSlot-modal").modal("hide");
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function() {
                    $("#confirmCancelSlot-modal").modal("hide");
                    toastr.error("Something went wrong.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function() {
                    $btn.removeClass('disabled').text('Yes, Delete Slot');
                }
            });
        });

        // Add Meeting Link - click handler
        $(document).on("click", ".AddMeetingLink", function() {
            let interview_id = $(this).data("interview_id");
            $("#MeetingLink_Interview_id").val(interview_id);
            $("#addMeetingLink-modal").modal("show");
        });

        // Add Meeting Link - form submission
        $('#addMeetingLinkForm').validate({
            rules: {
                MeetingLink: {
                    required: true,
                }
            },
            messages: {
                MeetingLink: {
                    required: "Please enter a Meeting Link.",
                }
            },
            submitHandler: function(form) {
                var $submitBtn = $(form).find('button[type="submit"]');
                $submitBtn.prop('disabled', true).text('Submitting...');
                var formData = new FormData(form);
                $.ajax({
                    url: "{{ route('resort.ta.AddInterViewLink') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#addMeetingLink-modal").modal("hide");
                            if (response.TodoDataview) {
                                $("#todoList-main").html(response.TodoDataview);
                            } else {
                                location.reload();
                            }
                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function() {
                        toastr.error("Something went wrong. Please try again.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('Submit');
                    }
                });
            }
        });

    });


</script>
@endsection

