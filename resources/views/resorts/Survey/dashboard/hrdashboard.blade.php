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
            <div class="row g-3 align-items-center justify-content-between">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('Survey.create') }}" class="btn btn-theme @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.create')) == false) d-none @endif">Create Survey</a>
                </div>
                <!-- <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div> -->
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth ">
            <div class="col-lg-3 col-sm-6 ">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Total Surveys</p>
                            <strong>{{ $total_Survey_count }}</strong>
                        </div>
                        <a href="{{ route('Survey.Surveylist') }}">
                            <img src="{{ asset('assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Open Surveys</p>
                            <strong>{{ $OngoingSurvey_count }}</strong>
                        </div>
                        <a href="{{ route('Survey.Surveylist') }}">
                            <img src="{{ asset('assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Draft Surveys</p>
                            <strong>{{ $DraftSurvey_count }}</strong>
                        </div>
                        <a href="{{ route('Survey.DarftSurvey') }}">
                            <img src="{{ asset('assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card dashboard-boxcard timeAttend-boxcard">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-0  fw-500">Complete Surveys</p>
                            <strong>{{ $CompleteSurvey_count }}</strong>
                        </div>
                        <a href="{{ route('Survey.CompleteSurvey') }}">
                            <img src="{{ asset('assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card  h-auto" id="card-surveyStatus">
                    <div class="card-title">
                        <h3>Survey Status</h3>
                    </div>
                    @if($OngoingSurvey->isEmpty())
                        <p class="text-muted mb-0 py-3">No open or ongoing surveys at the moment.</p>
                    @else
                    {{-- Bounded scroll list — long survey lists no longer stretch the
                         card and (via equalizeHeights) the Surveys-Nearing-Deadline
                         card next to it. --}}
                    <div class="surveyStatus-list">
                    @endif
                    @foreach($OngoingSurvey as $survey)
                        @php
                            $progress = ($survey->total_count > 0) ? round(($survey->completed_count / $survey->total_count) * 100) : 0;
                        @endphp
                        <div class="surveyStatus-block bg-themeGrayLight">
                            <div class="head">
                                <div>
                                    <h6>{{ $survey->title }}</h6>
                                    <p>Creation Date: {{ \Carbon\Carbon::parse($survey->Start_date)->format('d M Y') }} | 
                                        Closing Date: {{ \Carbon\Carbon::parse($survey->End_date)->format('d M Y') }}</p>                                </div>
                                @php
                                    $statusLabel = $survey->Status === 'OnGoing' ? 'Ongoing' : ($survey->Status === 'Publish' ? 'Published' : $survey->Status);
                                    $statusBadge = $survey->Status === 'OnGoing' ? 'badge-info' : 'badge-green';
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="body">
                                <div class="d-flex">
                                    <span>Participation Rate</span>
                                    <div class="progress progress-custom progress-themeskyblue">
                                        <div class="progress-bar" role="progressbar"   style="width: {{ $progress }}%;" 
                                        aria-valuenow="{{ $progress }}"  aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div>{{ $progress }}%</div>
                                </div>
                                @php
                                    $id = base64_encode($survey->id);
                                    $view = route('Survey.view',$id);
                                @endphp     
                                <div class="d-flex align-items-center">
                                    <a target="_blank" href="{{ $view}}" class="btn-tableIcon btnIcon-skyblue"><i
                                            class="fa-regular fa-eye"></i></a>
                                    <a href="javascript:void(0)" data-id="{{$id}}" class="SendNotification btn-tableIcon btnIcon-yellow"><i
                                            class="fa-regular fa-bell"></i></a>
                                    {{-- <a href="#" class="btn-tableIcon btnIcon-blue"><i class="fa-regular fa-pen"></i></a> --}}
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if(!$OngoingSurvey->isEmpty())
                    </div>
                    @endif

                </div>
            </div>
            @php
                $firstSurvey = isset($ParticipationRate[0]) ? $ParticipationRate[0] : null;
                $participationRate = $firstSurvey && $firstSurvey->participation_rate !== null
                    ? (float) $firstSurvey->participation_rate
                    : 0;
            @endphp

            {{-- WAI Insights — AI-narrated survey metrics (took the Participation Rate slot) --}}
            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-wiINsightSurvey wai-narrative h-100" id="card-wiINsightSurvey">
                    @php $sMeta = $surveyInsights['_meta'] ?? null; @endphp
                    <div class="wai-head">
                        <h2>WAI Insights</h2>
                        @if($sMeta)
                            <div class="wai-head-meta">
                                <span>Updated {{ $sMeta['generated_at']->diffForHumans() }}</span>
                                @if($sMeta['can_regenerate'])
                                    <a href="?regenerate_insights=1">Regenerate</a>
                                @else
                                    <span title="{{ $sMeta['next_available']->format('d M Y, H:i') }}">&middot; Regenerate {{ $sMeta['next_available']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="leaveUser-main wai-narrative-body">
                        @foreach([['key'=>'participation','modal'=>'surveyInsightParticipationModal'],['key'=>'activity','modal'=>'surveyInsightActivityModal'],['key'=>'sentiment','modal'=>'surveyInsightSentimentModal'],['key'=>'hotspots','modal'=>'surveyInsightHotspotsModal']] as $sc)
                            @php $hasRecommendation = !empty($surveyInsights[$sc['key']]['recommendation']); @endphp
                            <div class="wai-row">
                                <div class="wai-row-icon {{ $hasRecommendation ? 'is-flagged' : 'is-ok' }}">
                                    <i class="fa-solid {{ $hasRecommendation ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                                </div>
                                <div class="wai-row-body">
                                    <h6>{{ $surveyInsights[$sc['key']]['title'] ?? '' }}</h6>
                                    <p class="wai-row-text">{{ $surveyInsights[$sc['key']]['body'] ?? '' }}</p>
                                    @if($hasRecommendation)
                                        <p class="wai-row-recommendation"><strong>Recommendation:</strong> {{ $surveyInsights[$sc['key']]['recommendation'] }}</p>
                                    @endif
                                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#{{ $sc['modal'] }}" class="wai-row-link">View details &rarr;</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-surveysDeadline" id="card-surveysDeadline">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Surveys Nearing Deadline </h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('Survey.Getneartodeadlinesurvey')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        @if($NearingDeadline->isEmpty())
                            <p class="text-muted mb-0 py-3">No surveys nearing deadline with pending participants.</p>
                        @else
                            @foreach ($NearingDeadline as $n)
                            @php
                                $progress = ($n->total_count > 0) ? round(($n->completed_count / $n->total_count) * 100) : 0;
                            @endphp
                            <div class="leaveUser-block">
                                <div>
                                    <div class="date"><i class="fa-regular fa-calendar"></i>{{ $n->startDate }} - <span
                                            class="text-danger">{{ $n->endDate }}</span>
                                    </div>
                                    <h6>{{ $n->title }}</h6>
                                    <span>{{ $progress }}%</span>
                                    <a href="javascript:void(0)" class="a-link PendingParticipants" data-id="{{ $n->Newid }}">View Pending Participants</a>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class=" card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Recent Survey Results</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('Survey.CompleteSurvey') }}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew table-recentSurvey w-100">
                            <tr>
                                <th>Survey Name</th>
                                <th>No. of participants</th>
                                {{-- <th>Positive</th>
                                <th>Negative</th> --}}
                                <th>Action</th>
                            </tr>

                        @if($RecentSurveyResults->isNotEmpty())
                           @foreach ($RecentSurveyResults as $r)
                                <tr>
                                    <td>{{ ucfirst($r->title) }}</td>
                                    <td>{{ $r->count }}</td>
                                    <td><a href="{{ route('Survey.GetSurveyResults',  base64_encode($r->id)) }}" class="a-linkTheme">View Details</a></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-muted py-3">No completed survey results yet.</td>
                            </tr>
                        @endif
                        </table>
                    </div>
                </div>
            </div>
            {{-- Survey-wise Participation Rates now pairs with Recent Survey
                 Results above (6 + 6). Department-wise Participation has been
                 moved to the bottom row, after Draft Surveys. --}}
            <div class="col-xl-4 col-sm-12 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Survey-wise Participation Rates</h3>
                    </div>
                    @if($SurveyWiseParticipationRates->isEmpty())
                        <p class="text-muted mb-0 py-3">No survey participation data yet.</p>
                    @else
                        <div class="surveyWiseChart-wrap">
                            <canvas id="myAttendance"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Participation Rate moved here to pair with Recent Survey Results & Survey-wise Participation Rates --}}
            <div class="col-xl-4 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-participationRate h-100">
                    <div class="card-title mb-md-4">
                        <h3>Participation Rate</h3>
                    </div>
                    <div class="progressOneCenText-block mb-0">
                        <div class="progress-container blue"
                            data-progress="{{ $participationRate }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="bottom"
                            title="Participation Rate {{ $participationRate }}%">
                            <svg class="progress-circle" viewBox="0 0 120 120">
                                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                <circle class="progress" cx="60" cy="60" r="54"></circle>
                            </svg>
                        </div>
                        <div class="text">
                            <h5>{{ $participationRate }}%</h5>
                            <p>PARTICIPATION</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card card-comParticipation h-auto" id="card-comParticipation">
                    <div class="card-title mb-md-3">
                        <h3>Comparison Of Participation In Different Types Of Surveys</h3>
                    </div>
                    @if($SurveyComparison->isEmpty())
                        <p class="text-muted mb-0 py-3">No survey comparison data for the last 3 months.</p>
                    @else
                        <div class="row g-md-4 g-2 align-items-center">
                            <div class="col-xxl-9 col-xl-12 col-md-9"> <canvas id="myStackedBarChart" width="544"
                                    height="326"></canvas></div>
                            <div class="col-xxl-3 col-xl-auto col-lg-2 col-md-3 offset-lg-1 offset-xl-0 ">
                                <div class="row g-2 doughnut-labelTop">
                                    @foreach ($SurveyComparison as $com)
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label" title="{{ $com->title }}">
                                            <span style="background-color: {{ $com->color }}"></span>{{ $com->title }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class=" card " id="card-draftedSurveys">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center  g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Draft Surveys</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('Survey.DarftSurvey')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        @if($SaveAsDraft->isNotEmpty())
                            @foreach ($SaveAsDraft as $s)
                            <div class="leaveUser-block">
                                <div>
                                    <h6>{{ $s->Surevey_title }}</h6>
                                    <p>From :- {{ $s->Start_date }}  To :- {{ $s->End_date }}</p>
                                    <div>
                                        <a target="_blank" href="{{ $s->route }}" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                       @endif
                    </div>
                </div>
            </div>

            {{-- Department-wise Participation sits right after Draft Surveys on the
                 same bottom row (Comparison 6 + Draft 3 + Dept 3 = 12 cols). --}}
            <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card">
                    <div class="card-title">
                        <h3>Department-wise Participation</h3>
                    </div>
                    @if($departmentWise->isEmpty())
                        <p class="text-muted mb-0 py-3">No department-wise participation data yet.</p>
                    @else
                        <div class="departmentPart-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center ">
                            @foreach($departmentWise as  $d)
                                <div class="col-auto">
                                    <div class="doughnut-label" title="{{ $d->Department_name }}">
                                        <span style="background-color: {{ $d->color }}"></span>{{ $d->Department_name }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- <div class="col-xl-3 col-sm-6">
                <div class="card" id="card-wiInsightsSurvey">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">WI Insights</h3>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="leaveUser-main">
                        <div class="leaveUser-block">
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                    typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                    industry
                                    Lorem typesetting industry ipsum.</p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                    typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                    industry
                                    Lorem typesetting industry ipsum.</p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                    typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                    industry
                                    Lorem typesetting industry ipsum.</p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-block">
                            <div>
                                <h6>Lorem Ipsum is dummy text</h6>
                                <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem
                                    typesetting
                                    industry ipsum. Lorem ipsum is simply dummy text of the typesetting
                                    industry
                                    Lorem typesetting industry ipsum.</p>
                                <div>
                                    <a href="#" class="a-linkTheme">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</div>
<div class="modal fade" id="Surveyparticipant" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">


                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Pending  Participant  in survey</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="employee-name-content">
                        <div class="row g-3 AppendinRow">

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                </div>
        </div>
    </div>
</div>
@includeWhen(isset($surveyInsights), 'resorts.Survey.dashboard._insight_modals')
@endsection

@section('import-css')
<style>
    /* WAI Insights — same gradient-header treatment as the other modules'
       WAI Insights cards. Narrative (title + body + optional recommendation),
       not pass/fail counts, so no hero — icon is amber when a recommendation
       is present, teal tick otherwise. Fixed height, list scrolls inside. */
    .card-wiINsightSurvey {
        height: 100% !important;
        max-height: 420px !important;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow: hidden;
        border-radius: 16px;
    }
    .card-wiINsightSurvey .leaveUser-main {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

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

    /* Truncate long department / survey names in the chart legend pills.
       Full text remains in the title attribute so hover shows it. */
    .doughnut-label {
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .surveyStatus-block .head h6,
    .leaveUser-block h6 {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .table-recentSurvey td:first-child {
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Bounded-height scroll for the Survey Status / Nearing-Deadline / Draft
       cards so a long list doesn't push other cards taller via the
       equalizeHeights JS. Items inside scroll vertically. */
    .surveyStatus-list,
    #card-surveysDeadline .leaveUser-main,
    #card-draftedSurveys .leaveUser-main {
        max-height: 360px;
        overflow-y: auto;
        padding-right: 4px; /* leave room for the scrollbar */
    }

    /* Cap the Survey-wise chart so it doesn't dictate a tall row height.
       Pair this with maintainAspectRatio:false in the Chart.js options. */
    .surveyWiseChart-wrap {
        position: relative;
        height: 260px;
    }
    .surveyWiseChart-wrap > canvas {
        width: 100% !important;
        height: 100% !important;
    }
    /* Keep the Recent Survey Results table scrollable instead of growing. */
    .table-recentSurvey {
        display: block;
    }
    .table-recentSurvey tbody,
    .table-recentSurvey thead {
        display: table;
        width: 100%;
        table-layout: fixed;
    }
    /* Slim scrollbar so it doesn't dominate visually */
    .surveyStatus-list::-webkit-scrollbar,
    #card-surveysDeadline .leaveUser-main::-webkit-scrollbar,
    #card-draftedSurveys .leaveUser-main::-webkit-scrollbar {
        width: 6px;
    }
    .surveyStatus-list::-webkit-scrollbar-thumb,
    #card-surveysDeadline .leaveUser-main::-webkit-scrollbar-thumb,
    #card-draftedSurveys .leaveUser-main::-webkit-scrollbar-thumb {
        background: #c9d1d9;
        border-radius: 3px;
    }
</style>
@endsection

@section('import-scripts') <script type="text/javascript">
    // Generic function to equalize heights of two or more elements based on a reference element.
    // Skips when the reference card is hidden (offsetHeight === 0) — otherwise the surviving
    // card collapses to height:0 when permission gating hides one side.
    function equalizeHeights(referenceId, targetIds) {
        const reference = document.getElementById(referenceId);
        if (!reference) return;
        const referenceHeight = reference.offsetHeight;
        if (referenceHeight <= 0) return;

        targetIds.forEach(targetId => {
            const target = document.getElementById(targetId);
            if (target) {
                target.style.height = referenceHeight + 'px';
            }
        });
    }

    // Adjust heights on page load and window resize
    function adjustHeights() {
        equalizeHeights('card-surveyStatus', ['card-surveysDeadline']);
        equalizeHeights('card-comParticipation', ['card-draftedSurveys']);
    }

    window.onload = adjustHeights; // Initial height adjustment
    window.onresize = adjustHeights; // Adjust heights on window resize


    // progress 
    const radius = 54; // Circle radius
    const circumference = 2 * Math.PI * radius; // The circumference of the circle
    // Select all progress containers
    const progressContainers = document.querySelectorAll('.progress-container');

    progressContainers.forEach(container => {
        const progressCircle = container.querySelector('.progress');
        if (!progressCircle) return;
        const progressValue = parseFloat(container.getAttribute('data-progress')) || 0;
        const offset = circumference - (progressValue / 100 * circumference);

        progressCircle.style.strokeDashoffset = circumference;

        setTimeout(() => {
            progressCircle.style.strokeDashoffset = offset;

            // Update the text inside the circle
            // progressText.textContent = `${progressValue}%`;
        }, 100); // A small delay to trigger the animation smoothly
    });

    document.addEventListener("DOMContentLoaded", function () {
        const progressBars = document.querySelectorAll('.progress.progress-custom .progress-bar'); // Ensure parent has progress-custom class

        progressBars.forEach((progressBar) => {
            const valueNow = parseInt(progressBar.getAttribute('aria-valuenow'), 10);
            const parentProgress = progressBar.closest('.progress'); // Get the parent .progress element

            // Add specific classes to the parent based on aria-valuenow
            if (valueNow === 100) {
                parentProgress.classList.add('value-100');
            } else if (valueNow === 0) {
                parentProgress.classList.add('value-0');
            }
        });

    });
</script>
<script type="module">

   // Truncate long labels for chart axes / legends by character count.
   // Full text is preserved and shown in tooltips so nothing is lost.
   function truncateLabel(s, max) {
       if (s == null) return '';
       s = String(s);
       max = max || 18;
       return s.length > max ? s.slice(0, max - 1) + '…' : s;
   }

   // Truncate by WORD count — show first N words, then "…" if more remain.
   // Used for chart axis labels where character truncation chops mid-word.
   function truncateWords(s, maxWords) {
       if (s == null) return '';
       var words = String(s).trim().split(/\s+/);
       maxWords = maxWords || 2;
       if (words.length <= maxWords) return words.join(' ');
       return words.slice(0, maxWords).join(' ') + '…';
   }

   // Fetch data from Laravel (passed from controller)
   var surveyData = @json($SurveyComparison);
   function getLastThreeMonths() {
        let months = [];
        let date = new Date();
        
        for (let i = 2; i >= 0; i--) {
            let d = new Date(date.getFullYear(), date.getMonth() - i, 1);
            let monthYear = d.toLocaleString('default', { month: 'short' }) + " " + d.getFullYear();
            months.push(monthYear);
        }
        
        return months;
    }

// Dynamically generate last two months and current month labels
var labels = getLastThreeMonths();


var groupedData = {};
var surveyColors = {};
var surveyTitles = {};

// Group data by survey row (id + title) so two distinct surveys with the same
// title don't collapse into one stacked dataset on the chart.
surveyData.forEach(s => {
    var key = s.id + '::' + s.survey_type;
    if (!groupedData[key]) {
        groupedData[key] = Array(labels.length).fill(0);
        surveyColors[key] = s.color;
        surveyTitles[key] = s.survey_type;
    }
    let index = labels.indexOf(s.survey_month);
    if (index !== -1) {
        groupedData[key][index] = s.completed_count;
    }
});

// Create datasets dynamically. Keep the FULL title in `fullLabel` so the
// tooltip can show it even though the legend uses the truncated form.
var datasets = Object.keys(groupedData).map(key => ({
    label: truncateLabel(surveyTitles[key], 22),
    fullLabel: surveyTitles[key],
    data: groupedData[key],
    backgroundColor: surveyColors[key],
    borderColor: '#fff',
    borderWidth: 2,
    borderRadius: 10,
}));

// Create Chart (guard: canvas may be hidden by permission)
var ctxEl = document.getElementById('myStackedBarChart');
var myStackedBarChart = null;
if (ctxEl) {
var ctx = ctxEl.getContext('2d');
myStackedBarChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels, // Last two months and current month dynamically
        datasets: datasets // Dynamic datasets with assigned colors
    },
    options: {
        plugins: {
            // Hide Chart.js built-in legend — the side panel already renders
            // colored pills for each survey, so the in-chart legend duplicated
            // every survey name.
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        let index = tooltipItem.datasetIndex;
                        return (datasets[index].fullLabel || datasets[index].label) + ": " + tooltipItem.raw;
                    }
                }
            }
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false }
            },
            y: {
                stacked: true,
                beginAtZero: true,
                grid: { display: false },
                ticks: { stepSize: 5 } 
            }
        }
    }
});
}

    var departmentLabels = {!! json_encode($departmentWise->pluck('Department_name')) !!};
    var departmentData = {!! json_encode($departmentWise->pluck('completed_count')) !!};
    var departmentColors = {!! json_encode($departmentWise->pluck('color')) !!}; // Random colors

    var doughnutEl = document.getElementById('myDoughnutChart');
    var myDoughnutChart = null;
    if (doughnutEl) {
    var ctz = doughnutEl.getContext('2d');

    const doughnutLabelsInsideN = {
        id: 'doughnutLabelsInsideN',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce(function(acc, val) { return acc + val; }, 0);
                        var percentage = total === 0 ? '0%' : ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'bold 14px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y); // Show percentage inside
                    });
                }
            });
        }
    };


    var myDoughnutChart = new Chart(ctz, {
        type: 'doughnut',
        data: {
            labels: departmentLabels, // Department names (for hover only)
            datasets: [{
                data: departmentData, // Department-wise completion counts
                backgroundColor:departmentColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false // Hide legend labels
                },
                tooltip: {
                    enabled: true, // Show label names on hover
                    callbacks: {
                        label: function (tooltipItem) {
                            var label = departmentLabels[tooltipItem.dataIndex]; // Get department name
                            var value = departmentData[tooltipItem.dataIndex];
                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 0,
                    right: 0
                }
            }
        },
        plugins: [doughnutLabelsInsideN] // Attach custom plugin
    });
    }

    var surveyLabels = {!! json_encode($SurveyWiseParticipationRates->pluck('title')) !!}; // Full survey titles (kept for tooltip)
    // Show first 2 words of each survey name on the x-axis, then "…" if longer.
    var surveyLabelsTrunc = surveyLabels.map(function (s) { return truncateWords(s, 2); });
    var completedData = {!! json_encode($SurveyWiseParticipationRates->pluck('completed_count')) !!}; // Completed count

    var attendanceEl = document.getElementById('myAttendance');
    var myAttendance = null;
    if (attendanceEl) {
    var ctp = attendanceEl.getContext('2d');
    myAttendance = new Chart(ctp, {
        type: 'bar',
        data: {
            labels: surveyLabelsTrunc,
            datasets: [
                {
                    label: 'Completed',
                    data: completedData,
                    backgroundColor: '#014653',
                    borderColor: '#014653',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 25
                },
            ]
        },
        options: {
            // Let the canvas fill the .surveyWiseChart-wrap (260px) instead of
            // dictating its own size from width/height attrs.
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                layout: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        // Show the full survey title in the tooltip even though
                        // the axis label is truncated.
                        title: function (items) {
                            if (!items || !items.length) return '';
                            return surveyLabels[items[0].dataIndex] || items[0].label;
                        },
                        label: function (tooltipItem) {
                            const value = tooltipItem.raw.toLocaleString();
                            return ` ${value}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { display: false },
                    border: { display: true },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 30,
                        minRotation: 0
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { stepSize: 100 },
                    border: { display: true }
                }
            }
        }
    });
    }

    var participationRate = {!! json_encode($ParticipationRate->pluck('participation_rate')) !!}; // Participation rate %
    document.addEventListener("DOMContentLoaded", function() {
        var progressContainer = document.querySelector(".progress-container");
        var participationValue = parseFloat(progressContainer ? progressContainer.getAttribute("data-progress") : null) || 0;
        var progressCircle = progressContainer ? progressContainer.querySelector(".progress") : null;
        if (!progressCircle) return;
        var radius = 54;
        var circumference = 2 * Math.PI * radius;
        var progress = participationValue / 100;
        var offset = circumference * (1 - progress);

        progressCircle.style.strokeDasharray = circumference;
        progressCircle.style.strokeDashoffset = offset;
    });
    
    $(document).on("click",".SendNotification",function(){
        var $btn = $(this);
        if ($btn.data('busy')) return; // prevent double-clicks
        $btn.data('busy', true).css('pointer-events', 'none').css('opacity', 0.6);
        var id = $btn.data('id');
        $.ajax({
            url: "{{ route('Survey.notifyToParticipants') }}",
            type: "post",
            data: {"id":id,"_token":"{{ csrf_token() }}"},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function () {
                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.data('busy', false).css('pointer-events', '').css('opacity', '');
            }
        });
    });


    $(document).on("click",".PendingParticipants",function(){
    var id = $(this).data('id');
    $("#Surveyparticipant").modal('show');
    $('.AppendinRow').html('No Record Found.     ');
        $.ajax({
            url: "{{ route('Survey.getPendingParticipants') }}",
            type: "get",
            data: {"id":id,"_token":"{{ csrf_token() }}"},
            success: function (response) {
                    $('.AppendinRow').html(response);
            },
            error: function () {
                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

</script>
@endsection

