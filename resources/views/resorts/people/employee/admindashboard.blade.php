@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

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
                            <span>People</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                    <!-- <div class="col-xl-2 col-auto ms-auto"><select class="form-select select2t-none"
                            aria-label="Default select example">
                            <option selected="">Monthly</option>

                        </select></div> -->
                    <div class="col-auto">
                        <a class="btn btn-theme" href="{{route('people.employees')}}">View Employees</a>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total Active Employee</p>
                                <strong>{{$total_active_employees ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.employees')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total Inactive Employee</p>
                                <strong>{{$total_inactive_employees ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.employees')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total New Hires</p>
                                <strong>{{$total_new_hired ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.employees')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Expected Employee</p>
                                <strong>{{$expected_employees ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.employees')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-title">
                            <h3>Employee Type</h3>
                        </div>
                        <div class="incident-chart mb-md-3 mb-2">
                            <canvas id="myDoughnutChart" data-male="{{$male_emp}}" data-female="{{$female_emp}}"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Female
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Male
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Distribution by department</h3>
                                </div>
                                <div class="col-auto">
                                    <select class="form-select select2t-none" aria-label="Default select example" id="divisionFilter">
                                        <option selected="">Select Division</option>
                                        @if($resort_divisions)
                                            @foreach($resort_divisions as $division)
                                                <option value="{{$division->id}}">{{$division->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <canvas id="myBarChart" width="1244" height="263"></canvas>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Location-Wise Employee</h3>
                        </div>
                        <div class="locationEmp-chart mb-md-3 mb-2">
                            <canvas id="locationEmpChart" width="328" height="328"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center ">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Resort
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Male
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card card-localPeopleEmp">
                        <div class="card-title">
                            <h3>Local vs Xpat employees</h3>
                        </div>
                        <div class="two-progressbar mb-3">
                            <div class="progress-container blue" id="localProgress" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Local Staff">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                            </div>
                            <div class="progress-container skyblue" id="xpatProgress" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Xpat Staff">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                            </div>
                        </div>
                        <div class="row g-2 justify-content-center">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Local
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeSkyblue"></span>Xpat
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- <div class="col-xl-3 col-md-6">
                    <div class="card card-activePeopleEmp">
                        <div class="card-title">
                            <h3>Active Employees on the Island</h3>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-bgBlock">
                                <h6>Employees Currently Working On The Island</h6>
                                <strong>250</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Employees Who Are Off The Island</h6>
                                <strong>06</strong>
                            </div>
                        </div>
                    </div>
                </div> -->
                <!-- <div class="col-xl-3 col-md-6">
                    <div class="card card-wiINsight">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>WI Insight's</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-block">
                                <div class="img">
                                    <img src="assets/images/wisdom-ai-small.svg" alt="image">
                                </div>
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                    </P>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div class="img">
                                    <img src="assets/images/wisdom-ai-small.svg" alt="image">
                                </div>
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                    </P>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div> -->
                <div class="col-xl-9 col-lg-8 col-md-7">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Announcement</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.announcement.create')}}" class="btn btn-themeBlue btn-sm">
                                        <i class="fa-solid fa-plus me-2"></i>
                                        Create Announcement
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-lableNew table-totalAnnPeopleEmp w-100">
                                <thead>
                                    <tr>
                                        <th>Emp ID</th>
                                        <th>Employee Name</th>
                                        <th>Category</th>
                                        <th>Date Published</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($announcements && count($announcements))
                                        @foreach ($announcements as $announcement)
                                            <tr>
                                                <td>#{{ $announcement->employee->Emp_id }}</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle">
                                                            <img src="{{ Common::getResortUserPicture($announcement->employee->Admin_Parent_id ?? null); }}" alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">
                                                            {{ $announcement->employee->resortAdmin->full_name ?? 'N/A' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>{{ $announcement->category->name ?? 'N/A' }}</td>
                                                <td>{{ $announcement->published_date ? Carbon\Carbon::parse($announcement->published_date)->format('d M Y') : 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = match($announcement->status) {
                                                            'Published' => 'badge-blue',
                                                            'Scheduled' => 'badge-themeYellow',
                                                            'Draft' => 'badge-themeSkyblue',
                                                            default => 'badge-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">{{ $announcement->status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center">No announcements found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-5">
                    <div class="card card-annoPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Announcements</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.announcements')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-bgBlock">
                                <h6>Total Announcements Published</h6>
                                <strong>{{ $totalPublished }}</strong>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew table-totalAnnSPeopleEmp w-100">
                                    <tbody>
                                        @if($categoryCounts)
                                            @foreach($categoryCounts as $category)
                                                <tr>
                                                    <td>{{ $category->name }}</td>
                                                    <th>{{ $category->announcement_count }}</th>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card card-exitInterviewPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                        <h3>Exit Interview</h3>
                                </div>
                                <div class="col-auto ms-auto">
                                        <h6 class="fw-600" id="resignationCount"></h6>
                                </div>
                                <div class="col-auto"> 
                                        <input type="text"
                                        class="form-control form-control-small datepicker" id="depDate" placeholder="Select Duration" autocomplete="off" readonly style="background:#fff; cursor:pointer;">
                                </div>
                                <div class="col-auto">
                                        <select class="form-select form-select-large" aria-label="Default select example" id="exitDeptSelect">
                                        @foreach($departments as $department)
                                            <option value="{{$department->id}}">{{$department->name}}</option>
                                        @endforeach
                                        </select>
                                </div>

                            </div>
                        </div>

                        <div class="row g-md-3 g-2 g-xxl-4" id="exitInterviewCards">
                            {{-- <div class="col-xl-3 col-sm-6">
                                <div class="bg-themeGrayLight h-100">
                                        <h6 class="fw-600 mb-3">Top Reasons for Leaving</h6>
                                        <canvas id="myBarReasonsChart" width="349" height="199"></canvas>
                                </div>
                            </div>
                            <div class="col-xl-3 col-sm-6">
                                <div class="bg-themeGrayLight h-100">
                                        <h6 class="fw-600 mb-3">Turnover Trends</h6>
                                        <canvas id="myLineChart" width="365" height="199"></canvas>
                                        <!-- <canvas id="myBarTurnTrendChart" width="1244" height="263"></canvas> -->
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="bg-themeGrayLight h-100">
                                        <h6 class="fw-600 mb-3">Attrition Rates</h6>
                                        <canvas id="myBarAttrRateChart" width="824" height="199"></canvas>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card card-salaryIncrePeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Salary Increment & Alignment</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.salary-increment.grid-index')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="row w gx-xxl-4  g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Number Of Employees Shortlisted</h6>
                                    <strong>{{$totalSalaryIncrementShortListedEmp->count()}}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Current Basic Salary</h6>
                                    <strong>{{$SLE_basic_salary}}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Proposed Increment Amount</h6>
                                    <strong>{{$totalProposedIncrementAmount}}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Average Increment Percentage</h6>
                                    <strong>{{$averageIncrementPercentage}}%</strong>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-600 mb-3">Increment Status Breakdown</h6>
                        <canvas id="myBarsalaryIncreChart" width="800" height="199"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-promotionPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Promotion</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.promotion.list')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-xl-4 g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Promotions</h6>
                                    <strong>{{$total_promotions ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Avg. Salary Increase</h6>
                                    <strong>{{$average_salary_increase}}%</strong>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-600 mb-3">Recent Promotions</h6>
                        <div class="table-responsive">
                            <table class="table-lableNew table-recentPromoPeopleEmp w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee Name</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($recent_promotions) && count($recent_promotions)>0)
                                        @foreach($recent_promotions as $promotion)
                                            <tr>
                                                <td>{{$promotion->employee->Emp_id}}</td>
                                                <td>
                                                    <div class="tableUser-block">
                                                        <div class="img-circle">
                                                            <img src="{{Common::getResortUserPicture($promotion->employee->Admin_Parent_id ?? null)}}" alt="user">
                                                        </div>
                                                        <span class="userApplicants-btn">{{$promotion->employee->resortAdmin->full_name}}</span>
                                                    </div>
                                                </td>
                                                <td>{{$promotion->employee->department->name}}</td>
                                                <td>
                                                    @php
                                                        echo match ($promotion->status) {
                                                            'Approved' => '<span class="badge badge-themeSuccess">Approved</span>',
                                                            'Rejected' => '<span class="badge badge-themeDanger">Rejected</span>',
                                                            'On Hold'  => '<span class="badge badge-themeSkyblue">On Hold</span>',
                                                            default    => '<span class="badge badge-themeWarning">Pending</span>',
                                                        };
                                                    @endphp
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4">No Recent Promotions Found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-infoUpdatePeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Info Update Requests</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.info-update.index')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">

                            @foreach($employeeInfoUpdateRequest as $emp_info)
                                @php
                                    $profilePicture = App\Helpers\Common::GetAdminResortProfile($emp_info->employee->Admin_Parent_id);
                                @endphp
                                <div class="leaveUser-block">
                                        <div class="img-circle">
                                            <img src="{{$profilePicture}}" alt="user" class="img-fluid" />
                                        </div>
                                        <div>
                                            <h6 title="{{$emp_info->employee->resortAdmin->id}}">{{@$emp_info->employee->resortAdmin->full_name}} ({{$emp_info->employee->position->position_title}} - {{$emp_info->employee->department->name}}({{$emp_info->employee->department->code}}))</h6>
                                            <p>{{$emp_info->title}}</p>
                                        </div>
                                        <div>
                                            @if($emp_info->status == 'Pending')
                                                <a href="{{route('people.info-update.show',$emp_info->id)}}"  data-bs-toggle="modal" data-bs-target="#reqApproval-modal" class="a-linkTheme open-ajax-modal">Update</a>
                                                <a href="#" class="a-linkDanger"  data-bs-toggle="modal" data-id="{{$emp_info->id}}" data-bs-target="#reqReject-modal" >Reject</a>
                                            @else
                                                <a href="#" class="@if($emp_info->status == 'Approved') a-linkTheme @else a-linkDanger @endif" >{{$emp_info->status}}</a>
                                            @endif
                                        </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-probationPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Probation</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.probation')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="row g-xxl-4 g-md-3 g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock h-100">
                                    <div class="d-flex">
                                        <h6>Total Employees On Probation</h6>
                                        <strong>{{$probationalEmployees ?? 0}}</strong>
                                    </div>
                                    <div class="w-100 text-center">
                                        <div class="row  g-xxl-4 g-md-2 g-2 ">
                                            <div class="col">
                                                <p class="fw-500">Active</p>
                                                <h5><b>{{$activeProbationCount ?? 0}}</b></h5>
                                            </div>
                                            <div class="col">
                                                <p class="fw-500">Failed</p>
                                                <h5><b>{{$failedProbationCount ?? 0}}</b></h5>
                                            </div>
                                            <div class="col">
                                                <p class="fw-500">Completed</p>
                                                <h5><b>{{$completedProbationCount ?? 0}}</b></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock flex-nowrap">
                                    <h6>Ending In The Next 30 Days</h6>
                                    <strong>{{$count30 ?? 0}}</strong>
                                </div>
                                <div class="leaveUser-bgBlock flex-nowrap">
                                    <h6>Ending In The Next 15 Days</h6>
                                    <strong>{{$count15 ?? 0}}</strong>
                                </div>
                                <div class="leaveUser-bgBlock flex-nowrap">
                                    <h6>Ending In The Next 7 Days</h6>
                                    <strong>{{$count7 ?? 0}}</strong>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-600 mb-2">Training Completion Rate</h6>
                        <div>
                            <div class="d-flex align-items-center  mb-lg-2 mb-1">
                                <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                    <div class="progress-bar" role="progressbar" style="width: 65%" aria-valuenow="65"
                                        aria-valuemin="0" aria-valuemax="100">65%</div>
                                </div>
                                <span>65%</span>
                            </div>
                            <span>65% Complete</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-transferPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Transfer</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.transfer.list')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-xl-4 g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock" id="total_transfer">
                                    <h6>Total Transfer Requests</h6>
                                    <strong>50</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-lableNew table-transferPeopleEmp w-100" id="transferStat">
                                        <tbody>
                                            <tr>
                                                <td>Pending Approvals</td>
                                                <th>06</th>
                                            </tr>
                                            <tr>
                                                <td>Approved Transfers</td>
                                                <th>01</th>
                                            </tr>
                                            <tr>
                                                <td>Rejected/On-Hold Transfers</td>
                                                <th>01</th>
                                            </tr>
                                            <tr>
                                                <td>Pending For Letter Dispatch</td>
                                                <th>01</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="transferEmp-chart mb-md-3 mb-2">
                                    <canvas id="transferEmpChart" width="328" height="328"></canvas>
                                </div>
                                <div class="row g-2 justify-content-center ">
                                    <div class="col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>Temporary
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeSkyblue"></span>Permanent
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-salaryadvPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Salary advance and loan</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.advance-salary-repayment-tracker.index')}}" class="a-link">View Payroll Deduction Status</a>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-xl-4 g-2">
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Salary Advance/Loan Requests</h6>
                                    <strong>{{$advanceSalary->count()}}</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table-lableNew table-transferPeopleEmp w-100">
                                        <tbody>
                                            <tr>
                                                <td>Pending Guarantor Approval</td>
                                                <th>{{$guarantorCount}}</th>
                                            </tr>
                                            <tr>
                                                <td>Pending HR Approval</td>
                                                <th>{{$advanceSalary->where('hr_status','Pending')->count()}}</th>
                                            </tr>
                                            <tr>
                                                <td>Pending Approval By Finance</td>
                                                <th>{{$advanceSalary->where('hr_status','Approved')->where('finance_status','Pending')->count()}}</th>
                                            </tr>
                                            <tr>
                                                <td>Pending Approval By GM</td>
                                                <th>{{$advanceSalary->where('finance_status','Approved')->where('gm_status','Pending')->count()}}</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Amount Scheduled For Repayment</h6>
                                    <strong>{{ $advanceSalaryRescheduleAmount }}</strong>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-xxl-auto col-lg-12 col-sm-auto">
                                        <div class="salaryadvPeopleEmp-chart">
                                            <canvas id="mySalaryChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="row g-2 justify-content-center ">
                                            <div class="col-xxl-12 col-lg-auto col-sm-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-theme"></span>Salary Advance
                                                </div>
                                            </div>
                                            <div class="col-xxl-12 col-lg-auto col-sm-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span class="bg-themeSkyblue"></span>Loan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-repayTrackPeopleEmp">
                        <div class="card-title">
                            <h3>Repayment Tracking</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Total Active Repayment In Porgress</h6>
                            <strong>{{$totalRepayment->where('recovery_status','In Progress')->count()}}</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Total Active Repayment Scheduled</h6>
                            <strong>{{$totalRepayment->where('recovery_status','Scheduled')->count()}}</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Total Active Repayment Complete</h6>
                            <strong>{{$totalRepayment->where('recovery_status','Completed')->count()}}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="card card-exitClearPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3 class="text-nowrap">Exit Clearance</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.exit-clearance')}}" class="a-link">View Exit Clearance</a>
                                </div>
                            </div>
                        </div>
                        <div class="exitClearPeopleEmp-block mb-2">{{$exit_interviews->where('status','!=','Pending')->count()}} employees require exit interviews</div>
                        <div class="row gx-xl-4 g-3 mb-3">
                            <div class="col-lg-4 col-sm-6">
                                <div class="leaveUser-bgBlock">
                                    <h6>Total Exits Initiated</h6>
                                    <strong>{{ $totalExitInitiated }}</strong>
                                </div>
                                <div class="leaveUser-bgBlock">
                                    <h6>Pending Departmental Clearances</h6>
                                    <strong>{{$ExitClearanceFormAssignments->where('assigned_to_type','department')->where('status','Pending')->count()}}</strong>
                                </div>
                                <h6 class="fw-600 mb-2">Exit Interview Completion Rate</h6>
                                @php
                                    // Only count exit interviews that are not 'Completed' (case-insensitive)
                                    $completedExitInterviews = $exit_interviews->filter(function($item) {
                                        return $item->status == 'Completed';
                                    })->count();

                                    $totalExitInterviews = $exit_interviews->count();
                                    $exitInterviewCompletionRate = $totalExitInterviews > 0
                                        ? round((($totalExitInterviews - $completedExitInterviews) / $totalExitInterviews) * 100)
                                        : 0;
                                @endphp
                                <div class="d-flex align-items-center  mb-lg-2 mb-1">
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: {{$exitInterviewCompletionRate}}%"
                                            aria-valuenow="{{$exitInterviewCompletionRate}}" aria-valuemin="0" aria-valuemax="100">{{$exitInterviewCompletionRate}}%</div>
                                    </div>
                                    <span>{{$exitInterviewCompletionRate}}%</span>
                                </div>
                            </div>
                            <div class="col-lg-4 col-sm-6">
                                <div class="table-responsive">
                                    <table class="table-lableNew table- w-100">
                                        <tbody>
                                            <tr>
                                                <td>Total Exit Interview Requests</td>
                                                <th>{{$exit_interviews->where('status','!=','complete')->count()}}</th>
                                            </tr>
                                            <tr>
                                                <td>Pending Exit Interviews</td>
                                                <th>{{$exit_interviews->where('status','Pending')->count()}}</th>
                                            </tr>
                                            <tr>
                                                <td>Full And Final Settlements Generated</td>
                                                <th>{{$exitClearances->where('full_and_final_settlement','yes')->count()}}</th>
                                            </tr>
                                            <tr>
                                                <td>Employment Certificates Issued</td>
                                                <th>{{$exitClearances->where('certificate_issue','yes')->count()}}</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4 ">
                                <h6 class="fw-600  mb-lg-3 mb-2">Exit Clearance Completion Rate</h6>
                                @php
                                        $departments = \App\Models\ResortDepartment::where('resort_id', $resort->resort_id)->get();
                                        $assignmentsByDept = $ExitClearanceFormAssignments->groupBy('department_id');
                                    @endphp

                                        @foreach($departments as $department)
                                            @php
                                                $deptAssignments = $assignmentsByDept->get($department->id, collect());
                                                $total = $deptAssignments->count();
                                                $completed = $deptAssignments->where('status', 'Completed')->count();
                                                $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                                            @endphp
                                            @if($total > 0 )
                                                <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                                    <p>{{ $department->name }}</p>
                                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%"
                                                            aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $percentage }}%</div>
                                                    </div>
                                                    <span>{{ $percentage }}%</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    
                                
                                {{-- <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                    <p>Finance</p>
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 85%"
                                            aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">85%</div>
                                    </div>
                                    <span>85%</span>
                                </div>
                                <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                    <p>Home</p>
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 65%"
                                            aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65%</div>
                                    </div>
                                    <span>65%</span>
                                </div>
                                <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                    <p>Security</p>
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 85%"
                                            aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">85%</div>
                                    </div>
                                    <span>85%</span>
                                </div>
                                <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                    <p>HR</p>
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 65%"
                                            aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65%</div>
                                    </div>
                                    <span>65%</span>
                                </div> --}}
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card card-approvalsPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Approvals</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Total Pending Approvals</h6>
                            <strong>50</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Approved</h6>
                            <strong>25</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Held</h6>
                            <strong>15</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Rejected</h6>
                            <strong>10</strong>
                        </div>
                        <div class="approvalsPeopleEmp-block">
                            <p>Oldest Pending Request</p>
                            <p><i>2 Days Ago</i></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card card-resignationPeopleEmp">
                        <div class="card-title">
                            <h3>Resignation</h3>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Total Resignations</h6>
                            <strong>{{$total_resignations}}</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Pending Clearance</h6>
                            <strong>06</strong>
                        </div>
                        <div class="leaveUser-bgBlock">
                            <h6>Withdrew Resignation</h6>
                            <strong>{{$withdraw_resignations}}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-liabilityTrackPeopleEmp">
                        <div class="card-title">
                            <div class="row g-md-2 g-1 align-items-center">
                                <div class="col">
                                    <h3>Liability Tracker</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-bgBlock mb-md-3 mb-2">
                            <h6>Total Estimated Liability</h6>
                            <div>
                                <strong>$100,000</strong>
                                <span>(2026)</span>
                            </div>
                        </div>

                        <div class="row g-md-4 g-2">
                            <div class="col-sm-6">
                                <div class="bg-themeGrayLight">
                                    <h6 class="fw-600 mb-2">Monthly Deduction Trend</h6>
                                    <div class="table-responsive">
                                        <table class="table-lableNew  w-100">
                                            <tbody>
                                                <tr>
                                                    <td>January</td>
                                                    <th>$1,000</th>
                                                </tr>
                                                <tr>
                                                    <td>February</td>
                                                    <th>$2,000</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <h6 class="fw-600 mb-2">Actual Payments Made</h6>
                                <div class="d-flex align-items-center  mb-lg-3 mb-2">
                                    <div class="progress progress-custom progress-themeskyblue flex-grow-1 me-2">
                                        <div class="progress-bar" role="progressbar" style="width: 65%"
                                            aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span>$20,000</span>
                                </div>
                                <h6 class="fw-600 mb-2">Manual Adjustments</h6>
                                <p>3 Adjustments, Total: $1,500</p>
                            </div>
                            <div class="col-12">
                                <h6 class="fw-600 mb-md-2 mb-1">Estimation vs. Actual Comparison</h6>
                                <div class="table-responsive">
                                    <table class="table-lableNew table-liabilityTrackPeopleEmp w-100">
                                        <thead>
                                            <tr>
                                                <th>Cost Category</th>
                                                <th>Estimated Cost</th>
                                                <th>Actual Cost</th>
                                                <th>Remaining Liability</th>
                                                <th>Remaining Liability</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Overtime</td>
                                                <td>$5,000</td>
                                                <td>$4,500</td>
                                                <td>$500</td>
                                                <td><span class="text-themeSuccess">$500</span></td>
                                            </tr>
                                            <tr>
                                                <td>Loans</td>
                                                <td>$10,000</td>
                                                <td>$8,000</td>
                                                <td>$2,000</td>
                                                <td><span class="text-themeDanger">-$600</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    {{--START info Update And Reject Modal Code --}}
        
    <div class="modal fade" id="reqApproval-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-reqApproval">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Request Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="ajax-modal-body">
                    
                </div>
                
            </div>
        </div>
    </div>

    <div class="modal fade" id="reqReject-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-reqReject">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('people.info-update.request-rejected')}}" id="requestRejected">
                        @csrf
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="status" value="rejected">
                        <textarea id="rejectionReason" class="form-control" name="reject_reason" rows="3" placeholder="Enter a reason (required)" required></textarea>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
                </div>
                
            </div>
        </div>
    </div>
    {{--END info Update And Reject Modal Code --}}

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script> 
    let myBarChart = null;  // Declare chart variable globally
    let transferEmpChart = null; // store chart instance globally


    $(document).ready(function() {
        $('.select2t-none').select2(); 

                $(function() {  
                    let startOfMonth = moment().startOf('month').format('YYYY-MM-DD');
                    let endOfMonth = moment().endOf('month').format('YYYY-MM-DD');
                    $('#depDate').daterangepicker({
                        autoUpdateInput: true,
                        startDate: startOfMonth,
                        endDate: endOfMonth,
                        minDate: moment().subtract(1, 'year').format('YYYY-MM-DD'), // Allow dates from 1 year ago
                        maxDate: moment().add(1, 'year').format('YYYY-MM-DD'), // Allow dates up to 1 year in future
                        showDropdowns: true, // Enable month/year dropdowns
                        locale: {
                            cancelLabel: 'Clear',
                            format: 'YYYY-MM-DD'
                        },
                        ranges: {
                           'Today': [moment(), moment()],
                           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                           'This Month': [moment().startOf('month'), moment().endOf('month')],
                           'Next Month': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')],
                           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    });
                    $('#depDate').val(startOfMonth + ' to ' + endOfMonth);

                    let $deptSelect = $('#exitDeptSelect');
                    if ($deptSelect.find('option').length > 0) {
                        $deptSelect.val($deptSelect.find('option:first').val());
                    }

                    // Fetch data on load
                    fetchExitInterviewData();

                    $('#depDate').on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
                        fetchExitInterviewData();
                    });
                    $('#depDate').on('cancel.daterangepicker', function(ev, picker) {
                        $(this).val('');
                        fetchExitInterviewData();
                    });
                    $('#exitDeptSelect').on('change', function() {
                        fetchExitInterviewData();
                    });

                    function renderExitInterviewCharts(response) {
                        if (document.getElementById('myBarReasonsChart')) {
                            const ctz = document.getElementById('myBarReasonsChart').getContext('2d');
                            new Chart(ctz, {
                                type: 'bar',
                                data: {
                                    labels: response.reasonLabels,
                                    datasets: [{
                                        data: response.reasonCounts,
                                        backgroundColor: '#014653',
                                        borderColor: '#014653',
                                        borderWidth: 1,
                                        borderRadius: 5,
                                        barThickness: 32
                                    }]
                                },
                                options: {
                                    plugins: {
                                        legend: { display: false },
                                        layout: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
                                        tooltip: {
                                            enabled: true,
                                            callbacks: {
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
                                            border: { display: true }
                                        },
                                        y: {
                                            beginAtZero: true,
                                            grid: { display: false },
                                            ticks: { stepSize: 1 },
                                            border: { display: true }
                                        }
                                    }
                                }
                            });
                        }

                        // Bar Chart: Attrition Rates
                        if (document.getElementById('myBarAttrRateChart')) {
                            const cta = document.getElementById('myBarAttrRateChart').getContext('2d');
                            new Chart(cta, {
                                type: 'bar',
                                data: {
                                    labels: response.attritionLabels,
                                    datasets: [{
                                        data: response.attritionCounts,
                                        backgroundColor: '#2EACB3',
                                        borderColor: '#2EACB3',
                                        borderWidth: 1,
                                        borderRadius: 6,
                                        barThickness: 36
                                    }]
                                },
                                options: {
                                    plugins: {
                                        legend: { display: false },
                                        layout: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
                                        tooltip: {
                                            enabled: true,
                                            callbacks: {
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
                                            border: { display: true }
                                        },
                                        y: {
                                            beginAtZero: true,
                                            grid: { display: false },
                                            ticks: { stepSize: 1 },
                                            border: { display: true }
                                        }
                                    }
                                }
                            });
                        }

                        // Line Chart: Turnover Trends
                        if (document.getElementById('myLineChart')) {
                            const ctp = document.getElementById('myLineChart').getContext('2d');
                            new Chart(ctp, {
                                type: 'line',
                                data: {
                                    labels: response.lineLabels,
                                    datasets: [{
                                        label: 'Resignations',
                                        data: response.lineData,
                                        borderColor: '#014653',
                                        backgroundColor: '#014653',
                                        borderWidth: 1,
                                        fill: false,
                                        tension: 0.4,
                                        pointRadius: 0
                                    }]
                                },
                                options: {
                                    plugins: { legend: { display: false } },
                                    layout: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            grid: { display: false },
                                            border: { display: true }
                                        },
                                        y: {
                                            grid: { display: false },
                                            beginAtZero: true,
                                            ticks: { stepSize: 1 }
                                        }
                                    }
                                }
                            });
                        }
                    }

                    function fetchExitInterviewData() {
                        var departmentId = $('#exitDeptSelect').val();
                        var dateRange = $('#depDate').val();

                        $.ajax({
                            url: "{{ route('people.exit-interview.staticstics') }}",
                            type: 'GET',
                            data: {
                                department_id: departmentId,
                                date_range: dateRange
                            },
                            success: function(response) {
                                $('#exitInterviewCards').html(response.html);
                                $('#resignationCount').text('Total Resignation - ' + response.resignation_count);
                                renderExitInterviewCharts(response);
                            },
                            error: function() {
                                toastr.error("Failed to fetch exit interview data.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                    }
                });
                
        // Initialize chart data when page loads
        getBarChartData();
        getEmployeeStats();
        loadTransferStats();
        loadTransferTypeChart();
        // Optionally, add event listener for other interactions, like filtering or selection
        $('#divisionFilter').on('change', function() {
            const divisionId = $(this).val();

            if (!divisionId) return; // If no division selected, do nothing

            const url = `{{ route('get.division-by-dept', ['id' => '::id::']) }}`.replace('::id::', divisionId);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    const departments = data.map(item => item.department);
                    const counts = data.map(item => item.count);

                    // Create or update the chart with filtered data
                    createChart(departments, counts);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading filtered department data:', error);
                }
            });
        });

    });

    // Function to create the chart
    function createChart(labels, data) {
        // Destroy the existing chart if it exists
        // If the chart exists, destroy it before creating a new one
        if (myBarChart) {
            myBarChart.destroy();
        }

        const ctx = document.getElementById('myBarChart').getContext('2d');
        myBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels, // Dynamic labels
                datasets: [
                    {
                        label: 'Departments',
                        data: data, // Dynamic data
                        backgroundColor: '#2EACB3',
                        borderColor: '#2EACB3',
                        borderWidth: 1,
                        borderRadius: 6,
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
                            label: function(tooltipItem) {
                                const value = tooltipItem.raw.toLocaleString(); // Format the value with commas
                                return `${value}`; // Custom tooltip format
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
                        },
                        ticks: {
                            stepSize: 20,
                        },
                        border: {
                            display: true // Show the y-axis border
                        },
                    }
                }
            }
        });
    }

    // Fetch data for the chart
    function getBarChartData() {
        $.ajax({
            url: '{{ route('get.division-by-dept') }}', // Update this URL to fetch your dynamic data
            method: 'GET',
            success: function(data) {
                // Assume `data` is an array of objects like [{ department: 'Dept 1', count: 80 }, ...]
                const departments = data.map(item => item.department);
                const counts = data.map(item => item.count);

                // Create or update the chart with dynamic data
                createChart(departments, counts);
            },
            error: function(xhr, status, error) {
                console.error('Error loading department data:', error);
            }
        });
    }

    function getEmployeeStats(){
        $.ajax({
            url: '{{route("get.employeeStats")}}', // Route to get stats
            method: 'GET',
            success: function (data) {
                const localPercentage = data.localPercentage;
                const expatPercentage = data.expatPercentage;

                // Update the progress circles with the fetched percentages
                updateProgressCircle('#localProgress', localPercentage);
                updateProgressCircle('#xpatProgress', expatPercentage);

                // Set tooltip content dynamically
                $('#localProgress').attr('title', `Local Staff ${localPercentage}%`);
                $('#xpatProgress').attr('title', `Xpat Staff ${expatPercentage}%`);

                // Initialize Bootstrap tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
            error: function (xhr, status, error) {
                console.error('Error fetching employee stats:', error);
            }
        });
    }

    // Function to update progress circle
    function updateProgressCircle(element, percentage) {
        const progressCircle = $(element).find('.progress');
        const offset = 2 * Math.PI * 54;  // Circumference of the circle (2 * PI * radius)
        const offsetValue = offset - (percentage / 100) * offset;

        // Update the stroke-dasharray and stroke-dashoffset to animate the circle
        progressCircle.css({
            'stroke-dasharray': offset,
            'stroke-dashoffset': offsetValue
        });
    }

    function loadTransferStats() {
        $.ajax({
            url: '{{ route("employee.transfer.stats") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res.success) {
                    $('#total_transfer strong').text(res.data.total);
                    $('#transferStat').html(`
                        <tr>
                            <td>Pending Approvals</td>
                            <th>${res.data.pending}</th>
                        </tr>
                        <tr>
                            <td>Approved Transfers</td>
                            <th>${res.data.approved}</th>
                        </tr>
                        <tr>
                            <td>Rejected/On-Hold Transfers</td>
                            <th>${res.data.rejected_on_hold}</th>
                        </tr>
                        <tr>
                            <td>Pending For Letter Dispatch</td>
                            <th>${res.data.pending_letter}</th>
                        </tr>
                    `);
                }
            }
        });
    }

    function loadTransferTypeChart() {
        $.ajax({
            url: '{{ route("employee.transfer.type.chart") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res.success) {
                    const temp = res.data.temporary;
                    const perm = res.data.permanent;

                    const chartData = {
                        labels: ['Temporary', 'Permanent'],
                        datasets: [{
                            data: [temp, perm],
                            backgroundColor: ['#014653', '#2EACB3'],
                            borderWidth: 0
                        }]
                    };

                    if (transferEmpChart) {
                        // update existing chart
                        transferEmpChart.data = chartData;
                        transferEmpChart.update();
                    } else {
                        // create new chart
                        const ctx = document.getElementById('transferEmpChart').getContext('2d');
                        const transferPieLabelsInside = {
                            id: 'transferPieLabelsInside',
                            afterDraw: function (chart) {
                                var ctx = chart.ctx;
                                chart.data.datasets.forEach(function (dataset, i) {
                                    var meta = chart.getDatasetMeta(i);
                                    if (!meta.hidden) {
                                        meta.data.forEach(function (element, index) {
                                            var dataValue = dataset.data[index];
                                            var total = dataset.data.reduce((acc, val) => acc + val, 0);
                                            var percentage = total ? ((dataValue / total) * 100).toFixed(0) + '%' : '0%';
                                            var position = element.tooltipPosition();
                                            ctx.fillStyle = '#fff';
                                            ctx.font = 'bold 18px Arial';
                                            ctx.textAlign = 'center';
                                            ctx.textBaseline = 'middle';
                                            ctx.fillText(percentage, position.x, position.y);
                                        });
                                    }
                                });
                            }
                        };

                        transferEmpChart = new Chart(ctx, {
                            type: 'pie',
                            data: chartData,
                            options: {
                                responsive: true,
                                plugins: {
                                    transferPieLabelsInside: true,
                                    legend: { display: false }
                                },
                                layout: {
                                    padding: { top: 10, bottom: 10, left: 0, right: 0 }
                                }
                            },
                            plugins: [transferPieLabelsInside]
                        });
                    }
                }
            }
        });
    }

</script>
<script type="module">
    // Get the canvas and its data attributes
    var canvas = document.getElementById('myDoughnutChart');
    var ctx = canvas.getContext('2d');
    var maleCount = parseInt(canvas.getAttribute('data-male')) || 0;
    var femaleCount = parseInt(canvas.getAttribute('data-female')) || 0;

    // Custom plugin for inside labels
    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce((acc, val) => acc + val, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition();
                        ctx.fillStyle = '#fff';
                        ctx.font = 'normal 14px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };

    // Chart config
    var myDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: ['#2EACB3', '#014653'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutLabelsInside: true,
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10
                }
            }
        },
        plugins: [doughnutLabelsInside]
    });

    var cte = document.getElementById('locationEmpChart').getContext('2d');
    // Custom plugin to display percentage labels inside the pie chart
    const pieLabelsInside = {
        id: 'pieLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition(); // Position for the label

                        ctx.fillStyle = '#fff'; // Label color
                        ctx.font = 'bold 18px Arial'; // Font style
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        // Draw the percentage label at the center of each slice
                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };
    // Create the pie chart
    var locationEmpChart = new Chart(cte, {
        type: 'pie', // Change to 'pie' for pie chart
        data: {
            // labels: ['January 2024', 'February 2024', 'March 2024', 'April 2024', 'May 2024', 'June 2024'],
            datasets: [{
                data: [7, 15,],
                backgroundColor: ['#014653', '#2EACB3',],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                pieLabelsInside: true, // Enable the custom plugin
                legend: {
                    display: false
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
        plugins: [pieLabelsInside] // Attach the plugin to this chart only
    });

    const ctq = document.getElementById('myBarsalaryIncreChart').getContext('2d');
    const myBarsalaryIncreChart = new Chart(ctq, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Approved', 'Rejected', 'On Hold'],
            datasets: [
                {
                    data: [{{$pandingIncrement}}, {{$approvedIncrement}}, {{$rejectedIncrement}}, {{$onHoldIncrement}}],
                    backgroundColor: ['#F0B919', '#27CF86', '#C80000', '#43B4BA'],
                    borderColor: ['#F0B919', '#27CF86', '#C80000', '#43B4BA'],
                    borderWidth: 1,
                    borderRadius: 6,
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
                    enabled: true,
                    callbacks: {
                        label: function (tooltipItem) {
                            const value = tooltipItem.raw.toLocaleString();
                            return `${value}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    },
                    border: {
                        display: true
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        stepSize: 5,
                    },
                    border: {
                        display: true
                    },
                }
            }
        }
    });

    
    var ctr = document.getElementById('mySalaryChart').getContext('2d');
    // Custom plugin only registered for this chart
    const doughnutSalaryLabelsInside = {
        id: 'doughnutSalaryLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx; // Corrected
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce(function (acc, val) {
                            return acc + val;
                        }, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition();

                        ctx.fillStyle = '#fff';
                        ctx.font = 'normal 14px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };
    // Custom plugin for center text
    var mySalaryChart = new Chart(ctr, {
        type: 'doughnut',
        data: {
            labels: ['Salary Advance', 'Loan'],
            datasets: [{
                data: [{{$totalAdvanceRequests}}, {{$totalLoanRequests}}],
                backgroundColor: ['#2EACB3', '#014653',],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                doughnutSalaryLabelsInside: true, // Enable the custom plugin
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 0,
                    right: 0
                }
            },
            // hoverOffset: 30
        },
        plugins: [doughnutSalaryLabelsInside] // Attach the plugin to this chart only
    });

    // Update And Reject info Update request
        $(document).on('click', '.open-ajax-modal', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if(response.status == 'success'){
                        $('#ajax-modal-body').html(response.html);
                    }
                },
                error: function() {
                    alert('Failed to load content.');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var rejectModal = document.getElementById('reqReject-modal');

            rejectModal.addEventListener('show.bs.modal', function (event) {

                var button = event.relatedTarget; 
                var empId = button.getAttribute('data-id'); 

                var approvalModal = bootstrap.Modal.getInstance(document.getElementById('reqApproval-modal'));
                if (approvalModal) {
                    approvalModal.hide();
                }

                var idInput = rejectModal.querySelector('input[name="id"]');
                if (idInput) {
                    idInput.value = empId;
                }
            });
        });
        
        $(document).on('click', '#confirmRejectBtn', function () {
            var $form = $('#requestRejected');

            var reason = $form.find('#rejectionReason').val().trim();
            if (!reason) {
                toastr.error("Rejection reason is required.","Error",{
                        positionClass: 'toast-bottom-right'
                    });
                return;
            }

            var formData = new FormData($form[0]);
            var url = $form.attr('action');

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
            
                success: function (result) {
                
                    $('#reqReject-modal').modal('hide');
                loadUpdateRequests();
                    toastr.success("Request rejected successfully.", "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                error: function () {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });
</script>

@endsection