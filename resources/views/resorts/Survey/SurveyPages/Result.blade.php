@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Survey</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto"><a href="javascript:void(0)" class="ClickToSubmit btn eb-btn-secondary">Download</a></div>
                </div>
            </div>

            <div class="card serveyResult-card">
                <div class="bg-themeGrayLight">
                    <div class="card-title mb-md-4">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">{{ $ParentSurvey->Surevey_title }}</h3>
                                @php
                                    $badgeClass = 'badge-success';
                                    $privacyNote = 'Respondent identities are visible to all viewers.';
                                    if (($privacy ?? null) === 'Confidential') {
                                        $badgeClass = 'badge-info';
                                        $privacyNote = $showRespondentIdentity
                                            ? 'Identities are visible to you because you are an authorised admin (HR / GM).'
                                            : 'Respondent identities are hidden. Only authorised admins (HR / GM) can see who responded.';
                                    } elseif (($privacy ?? null) === 'Anonymous') {
                                        $badgeClass = 'badge-secondary';
                                        $privacyNote = 'Respondent identities are hidden for everyone, including admins.';
                                    }
                                @endphp
                                <div class="mt-1">
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($privacy ?? 'Neutral') }}</span>
                                    <small class="text-muted ms-2">{{ $privacyNote }}</small>
                                </div>
                            </div>
                            <div class="col-auto">
                                <ul class="userDetailList-wrapper">
                                    <li><span>CREATED BY:</span>
                                        <div class="d-flex">
                                            <div class="img-circle"><img src="{{ $ParentSurvey->profileImg }}" alt="user">
                                            </div>
                                            {{ $ParentSurvey->EmployeeName }}
                                        </div>
                                    </li>
                                    <li><span>START DATE:</span>{{ $ParentSurvey->startDate}}</li>
                                    <li><span>END DATE:</span>{{ $ParentSurvey->endDate}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-md-4 col-sm-6">
                            <div class="bg-white servey-boxCard">
                                <p>Total Respondents</p>
                                <h3>{{ $TotalResponed }}</h3>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="bg-white servey-boxCard">
                                <p>Response Rate</p>
                                <h3>{{ $responseRate }}%</h3>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="bg-white servey-boxCard">
                                <p>Avg. Completion Time</p>
                                <h3>{{ $formattedTime }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-md-4 mb-3">
                        <form id="ExportResult" method="get" action="{{ route('Survey.SurveyReultExport') }}">
                            @csrf
                            <input type="hidden" name="id"  value="{{$id}}"
                            <div class="col-lg-4 col-md-6">
                                <select class="form-select dd-native-select" name="respondent"  id="respondent">
                                    <option value="{{ base64_encode('All') }}">All Respondents</option>
                                    @if($ResponedEmp->isNotEmpty())
                                        @foreach ($ResponedEmp as $item)
                                            <option value="{{ $item->emp_id }}">{{ $item->EmployeeName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="dd" data-target="#respondent">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">All Respondents</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Respondent">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a respondent…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value="{{ base64_encode('All') }}"><span class="dd-nm">All Respondents</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @if($ResponedEmp->isNotEmpty())
                                                @foreach ($ResponedEmp as $item)
                                                <div class="dd-item" role="option" data-value="{{ $item->emp_id }}"><span class="dd-nm">{{ $item->EmployeeName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if(!$showRespondentIdentity)
                                    <small class="text-muted">Respondent names are masked for {{ $privacy }} surveys — any selection exports all responses combined, to avoid identifying an individual by elimination.</small>
                                @endif
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn eb-btn-primary">Export Results</button>
                            </div>
                        </form>
                    </div>
                    @if(!empty($respondentAnswers))
                    <div class="bg-white mb-3">
                        <p class="fw-600 mb-2">Responses</p>
                        <div class="accordion" id="respondentAnswersAccordion">
                            @foreach($respondentAnswers as $idx => $respondent)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#respondentAnswer{{ $idx }}">
                                            {{ $respondent['name'] }}
                                        </button>
                                    </h2>
                                    <div id="respondentAnswer{{ $idx }}" class="accordion-collapse collapse" data-bs-parent="#respondentAnswersAccordion">
                                        <div class="accordion-body">
                                            @foreach($respondent['answers'] as $qa)
                                                <div class="mb-2">
                                                    <div class="fw-600">{{ $qa['question'] }}</div>
                                                    <div class="text-muted">{{ $qa['answer'] !== '' ? $qa['answer'] : 'No answer' }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if(!empty($ratingChartLabels) || !empty($optionChartLabels))
                    <div class="bg-white">
                        <div class="row g-md-4 g-3 align-items-center">
                            @if(!empty($ratingChartLabels))
                            <div class="col-xxl-6 col-xl-7 col-lg-8">
                                <p class="fw-600 mb-2">Average rating per question</p>
                                <div style="max-height:220px;">
                                    <canvas id="barchart"></canvas>
                                </div>
                            </div>
                            @endif
                            @if(!empty($optionChartLabels))
                            <div class="col-xxl-5 col-lg-4 offset-xl-1">
                                <p class="fw-600 mb-2">{{ $optionChartQuestion }}</p>
                                <div style="max-height:220px;">
                                    <canvas id="doughnutchart"></canvas>
                                </div>
                                <div class="row g-2 doughnut-labelTop justify-content-center justify-content-lg-start mt-2">
                                    @foreach($optionChartLabels as $idx => $label)
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">{{ $label }}</span><br>{{ $optionChartData[$idx] }}%</div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
    @include('resorts._emotional_buttons_v2_styles')
@endsection

    @section('import-css')
    @include('resorts._dropdown_styles')
    @endsection

    @section('import-scripts')
    <script>
        $(document).ready(function () {
            $('#ExportResult').validate({
                rules: {
                    respondent: {
                        required: true
                    }
                },
                messages: {
                    respondent: {
                        required: "Please select a respondent."
                    }
                },
               
            });

        });
        $(document).on("click", ".ClickToSubmit", function () {
            $('#ExportResult').trigger('submit');
        });

    </script>
    <script type="module">
        const barCanvas = document.getElementById('barchart');
        if (barCanvas) {
            var _pSurv1 = window.WaiChart ? window.WaiChart.palette().teal : '#014653';
            var _surveyBarChart = new Chart(barCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($ratingChartLabels ?? []),
                    datasets: [
                        {
                            label: 'Average rating',
                            data: @json($ratingChartData ?? []),
                            backgroundColor: _pSurv1,
                            borderColor: _pSurv1,
                            borderWidth: 1,
                            borderRadius: 5,
                            barThickness: 36
                        },
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: true }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { display: false },
                            border: { display: true }
                        }
                    }
                }
            });
            if (window.WaiChart) window.WaiChart.registerForTheme(_surveyBarChart, function (c, p) {
                c.data.datasets[0].backgroundColor = c.data.datasets[0].borderColor = p.teal;
            });
        }

        const doughnutCanvas = document.getElementById('doughnutchart');
        if (doughnutCanvas) {
            // backgroundColor: only 1 of 6 matches an SSOT token — left
            // literal as a whole categorical set.
            var _surveyDoughnutChart = new Chart(doughnutCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: @json($optionChartLabels ?? []),
                    datasets: [
                        {
                            data: @json($optionChartData ?? []),
                            backgroundColor: ['#014653', '#2E86AB', '#5CB85C', '#F0AD4E', '#D9534F', '#9E5CF7'],
                        },
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw + '%';
                                }
                            }
                        }
                    }
                }
            });
            if (window.WaiChart) window.WaiChart.registerForTheme(_surveyDoughnutChart);
        }
    </script>
    @include('resorts._dropdown_script')
    @endsection
