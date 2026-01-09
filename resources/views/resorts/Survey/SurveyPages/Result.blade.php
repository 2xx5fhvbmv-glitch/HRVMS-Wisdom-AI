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
                    <div class="col-auto ms-auto"><a href="javascript:void(0)" class="ClickToSubmit btn btn-theme">Download</a></div>
                </div>
            </div>

            <div class="card serveyResult-card">
                <div class="bg-themeGrayLight">
                    <div class="card-title mb-md-4">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap">{{ $ParentSurvey->Surevey_title }}</h3>
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
                                <select class="form-select select2t-none" name="respondent"  id="respondent">
                                    <option value="{{ base64_encode('All') }}">All Respondents</option>
                                    @if($ResponedEmp->isNotEmpty())
                                        @foreach ($ResponedEmp as $item)
                                            <option value="{{ $item->emp_id }}">{{ $item->EmployeeName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-themeBlue">Export Results</button>
                            </div>
                        </form>
                    </div>
                    {{-- <div class="bg-white">
                        <div class="row g-md-4 g-3 align-items-center">
                            <div class="col-xxl-6 col-xl-7 col-lg-8">
                                <canvas id="barchart"></canvas>
                                <!-- <canvas id="barchart" width="906" height="293"></canvas> -->
                            </div>
                            <div class="col-xxl-5  col-lg-4 offset-xl-1 ">
                                <div class="row g-2 doughnut-labelTop justify-content-center justify-content-lg-start">
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">Strongly Disagree</span><br>10%</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">Disagree</span> <br>26%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">Neutral</span> <br>37%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">Agree</span> <br>52%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>
                                            <div><span class="fw-600">Strongly Agree</span> <br>80%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>

        </div>
    </div>
    @endsection

    @section('import-css')
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
                        required: "Please Select Any respondent."
                    }
                },
               
            });

        });
        $(document).on("click", ".ClickToSubmit", function () {
            $('#ExportResult').trigger('submit');
        });

    </script>
    <script type="module">
        const ctp = document.getElementById('barchart').getContext('2d');
        const barchart = new Chart(ctp, {
            type: 'bar',
            data: {
                labels: ['Strongly Disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly Agree'],
                datasets: [
                    {
                        label: 'Preplannned OT',
                        data: [10, 30, 50, 80, 90],
                        backgroundColor: '#014653',
                        borderColor: '#014653',
                        borderWidth: 1,
                        borderRadius: 5,
                        barThickness: 36
                    },
                ]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    },
                    layout: {
                        padding: {
                            top: 0,
                            bottom: 0,
                            left: 0,
                            right: 0
                        }
                    },
                    tooltip: {
                        enabled: true, // Enable tooltips
                        callbacks: {
                            label: function (tooltipItem) {
                                // const datasetLabel = tooltipItem.dataset.label || '';
                                const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                                return ` $${value}`; // Custom tooltip format
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true, // Start x-axis at zero
                        grid: {
                            display: false // Hide grid lines on the x-axis
                        },
                        border: {
                            display: true // Show the x-axis border
                        }
                    },
                    y: {
                        beginAtZero: true, // Do not start y-axis at zero
                        grid: {
                            display: false // Hide grid lines on the y-axis
                        }, ticks: {
                            stepSize: 20,
                        },
                        border: {
                            display: true // Show the y-axis border
                        },
                    }
                }
            }
        });


    </script>
    @endsection
