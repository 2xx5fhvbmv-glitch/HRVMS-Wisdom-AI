@extends('resorts.layouts.app')
@section('page_tab_title' ,"Performance Dashboard")

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance </span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">New Hire</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 g-xxl-4 recHR-main">
            <div class="col-xl-8 col-lg-12">
                <div class="row g-3 g-xxl-4 ">
                    <div class="col-md-4">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Total Applicants</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$total_applicants ?? 0}}</strong>
                                </div>
                            </div>
                            <div>
                                <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Interviews</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$total_interviews ?? 0}}</strong>
                                </div>
                            </div>
                            <div>
                                <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Hired</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{$total_hired ?? 0}}</strong>
                                </div>
                            </div>
                            <div>
                                <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card h-auto" id="card-vac">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Vacancies</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{route('resort.vacancies.view')}}" class="a-link">View all</a>
                                    </div>
                                </div>

                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse table-vacRec">
                                    <thead>
                                        <tr>
                                            <th>Positions</th>
                                            <th>Department</th>
                                            <th>No. of Vacancy</th>
                                            <th>No. of Applicant</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Assistant Front Desk Manager
                                                <span class="badge badge-themeLight"> 521454 </span>
                                            </td>
                                            <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                            <td>02</td>
                                            <td>56</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Chef Concierge <span class="badge badge-themeLight"> 521454 </span>
                                            </td>
                                            <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                            <td>05</td>
                                            <td>103</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Night Desk Clerk <span class="badge badge-themeLight"> 521454
                                                </span></td>
                                            <td>Front Office <span class="badge badge-themeLight">M-415</span></td>
                                            <td>03</td>
                                            <td>29</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Guest Services Coordinator <span class="badge badge-themeLight">
                                                    521454
                                                </span></td>
                                            <td>Housekeeping <span class="badge badge-themeLight">M-415</span></td>
                                            <td>01</td>
                                            <td>200</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Assistant Front Desk Manager <span class="badge badge-themeLight">
                                                    521454
                                                </span></td>
                                            <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                            <td>01</td>
                                            <td>50</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Chef Concierge <span class="badge badge-themeLight"> 521454 </span>
                                            </td>
                                            <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                            <td>01</td>
                                            <td>34</td>
                                            <td><a href="#" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card" id="card-todoList">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>To Do List</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-link">View all</a>
                                    </div>
                                </div>

                            </div>
                            <div class="todoList-main">
                                <div class="todoList-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <p>John Doe has been sortlisted for Manager position</p>
                                        <a href="#sendRequest-modal" data-bs-toggle="modal" class="a-link">Send
                                            Interview Request</a>
                                    </div>
                                </div>
                                <div class="todoList-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <p>GM is Approved vacancy for Assistant Front Desk Manager</p>
                                        <a href="#jobAD-modal" data-bs-toggle="modal" class="a-link">Create Job
                                            Advertisement</a>
                                    </div>
                                </div>
                                <div class="todoList-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <p>John Doe has been sortlisted for Manager position</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card ">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>Top Countries</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="h-45 d-none d-lg-block"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse table-topCoun">
                                    <tbody>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp')}}" alt="flag"
                                                    class="flag">Maldives</td>
                                            <td>87</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/up-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-india.webp')}}" alt="flag"
                                                    class="flag">India</td>
                                            <td>58</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/down-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-bangladesh.webp')}}" alt="flag"
                                                    class="flag">Bangladesh
                                            </td>
                                            <td>26</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/up-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-us.webp')}}" alt="flag" class="flag">US
                                            </td>
                                            <td>48</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/up-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-canada.webp')}}" alt="flag"
                                                    class="flag">Canada</td>
                                            <td>152</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/down-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-australia.webp')}}" alt="flag"
                                                    class="flag">Australia
                                            </td>
                                            <td>26</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/up-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                        <tr>
                                            <td><img src="{{ URL::asset('resorts_assets/images/flag-sweden.webp')}}" alt="flag"
                                                    class="flag">Sweden</td>
                                            <td>15</td>
                                            <td><img src="{{ URL::asset('resorts_assets/images/down-chart.svg')}}" alt="icon"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-lg-8">
                        <div class="card">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Top Hiring Sources</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <select class="form-select" aria-label="Default select example">
                                                <option selected="">Jan 2024 - Dec 2024</option>
                                                <option value="1">Jan 2025 - Dec 2025</option>
                                                <option value="2">Jan 2026 - Dec 2026</option>

                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class=" mb-3">
                                <canvas id="myStackedBarChart"></canvas>
                            </div>
                            <div class="row g-2 justify-content-center">
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-theme"></span>Loremipsum.Com
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeLightBlue"></span>Lorem Ipsum
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg bg-themeYellow"></span>Direct
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblueLight"></span>Loremipsum.Com
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeGray"></span>Lorem Ipsum
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="col-lg-8">
                        <div class="card card-topHiring">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Top Hiring Sources</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <select class="form-select" aria-label="Default select example">
                                                <option selected="">Jan 2024 - Dec 2024</option>
                                                <option value="1">Jan 2025 - Dec 2025</option>
                                                <option value="2">Jan 2026 - Dec 2026</option>

                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="row align-items-center g-2">
                                <div class="col-xxl-9 col-xl-12 col-md-9">
                                    <canvas id="myStackedBarChart" width="544"
                                        height="326"></canvas></div>
                                <div class="col-xxl-3 col-xl-auto col-lg-3 col-md-3  offset-xl-0 ">
                                    <div class="row g-2">
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-theme"></span>Loremipsum.Com
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-themeLightBlue"></span>Lorem Ipsum
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg bg-themeYellow"></span>Direct
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-themeSkyblueLight"></span>Loremipsum.Com
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                            <div class="doughnut-label">
                                                <span class="bg-themeGray"></span>Lorem Ipsum
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- <div class=" mb-3">
                                <canvas id="myStackedBarChart"></canvas>
                            </div>
                            <div class="row g-2 justify-content-center">
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-theme"></span>Loremipsum.Com
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeLightBlue"></span>Lorem Ipsum
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg bg-themeYellow"></span>Direct
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblueLight"></span>Loremipsum.Com
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeGray"></span>Lorem Ipsum
                                    </div>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>Talent Pool</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="talentPool-main">
                                <div class="talentPool-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter</h6>
                                        <p>This person is matching 80% for Kitchen Manager Position</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#" class="delete-icon"><i
                                                class="fa-regular fa-trash-can"></i></a>
                                    </div>
                                </div>
                                <div class="talentPool-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter</h6>
                                        <p>This person is matching 80% for Kitchen Manager Position</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#" class="delete-icon"><i
                                                class="fa-regular fa-trash-can"></i></a>
                                    </div>
                                </div>
                                <div class="talentPool-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter</h6>
                                        <p>This person is matching 80% for Kitchen Manager Position</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#" class="delete-icon"><i
                                                class="fa-regular fa-trash-can"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>New Hire Requests</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="hireReq-main">
                                <div class="hireReq-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter (HOD - Management)</h6>
                                        <p>Requested for Hire 1 Executive Chef</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#respondmodal" data-bs-toggle="modal"
                                            class="a-link">Respond</a>
                                    </div>
                                </div>
                                <div class="hireReq-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter (HOD - Management)</h6>
                                        <p>Requested for Hire 1 Executive Chef</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#respondmodal" data-bs-toggle="modal"
                                            class="a-link">Respond</a>
                                    </div>
                                </div>
                                <div class="hireReq-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter (HOD - Management)</h6>
                                        <p>Requested for Hire 1 Executive Chef</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#respondmodal" data-bs-toggle="modal"
                                            class="a-link">Respond</a>
                                    </div>
                                </div>
                                <div class="hireReq-block">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                                    </div>
                                    <div>
                                        <h6>Christian Slatter (HOD - Management)</h6>
                                        <p>Requested for Hire 1 Executive Chef</p>
                                        <a href="#" class="a-link">Send Interview Request</a>
                                    </div>
                                    <div class="icon"><a href="#respondmodal" data-bs-toggle="modal"
                                            class="a-link">Respond</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <div class="col-xl-4 col-lg-6">
                <div class="card h-auto">
                    <div class="mb-4 overflow-hidden">
                        <div id="calendar"></div>
                    </div>
                    <div class="card-title">
                        <div class="row justify-content-between align-items-center g-3">
                            <div class="col">
                                <h3>Upcoming Interviews</h3>
                            </div>
                            <!-- <div class="col-auto">
                                <a href="#" class="a-link">View all</a>
                            </div> -->
                        </div>
                    </div>
                    <div>
                        <div class="upInterviews-block">
                            <div class="img-circle">
                                <img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="image">
                            </div>
                            <div>
                                <h6>Shreya Kapoor</h6>
                                <p>Director Of Guest Services</p>
                                <span class="badge badge-theme">Management</span>
                            </div>
                            <div>
                                <div class="date">11 SEP</div>
                                <div class="time">9:00 AM</div>
                            </div>
                        </div>
                        <div class="upInterviews-block">
                            <div class="img-circle">
                                <img src="{{ URL::asset('resorts_assets/images/user-3.svg')}}" alt="image">
                            </div>
                            <div>
                                <h6>Christian Slatter</h6>
                                <p>Kitchen Manager</p>
                                <span class="badge badge-theme">Management - Kitchen</span>
                            </div>
                            <div>
                                <div class="date">12 SEP</div>
                                <div class="time">11:00 AM</div>
                            </div>
                        </div>
                        <div class="upInterviews-block">
                            <div class="img-circle">
                                <img src="{{ URL::asset('resorts_assets/images/user-4.svg')}}" alt="image">
                            </div>
                            <div>
                                <h6>Max Willson</h6>
                                <p>Director Of Guest Services</p>
                                <span class="badge badge-theme">Management </span>
                            </div>
                            <div>
                                <div class="date">19 SEP</div>
                                <div class="time">9:00 AM</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="respond-dateModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label mb-8">Select date</label>
                <div class="modalCalendar-block">
                    <div id="calendarModal"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>

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
            <div class="modal-body">
                <textarea class="form-control" rows="7" placeholder="Reason for Rejection"></textarea>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="respond-appModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered  modal-small modal-respondApp">
        <div class="modal-content">
            <div class="modal-header border-0">
                <!-- <h5 class="modal-title" id="staticBackdropLabel">Manning has been sent!</h5> -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="assets/images/check-circle.svg" alt="icon">
                <h4>submission confirmation</h4>
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been
                    the industry's standard dummy text ever since the 1500</p>
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeBlue">Close</a>
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
            <div class="modal-body">
                <label class="form-label mb-8">Select date</label>
                <div class="modalCalendar-block">
                    <div id="calendarModalSend"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#sendRequestSend-modal" data-bs-toggle="modal" data-bs-dismiss="modal"
                    class="btn btn-theme">Submit</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="sendRequestSend-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label mb-sm-4 mb-3">SELECT TIME SLOTS</label>
                <div class="sendRequestTime-main">
                    <div class="head">
                        <div>Your Clock</div>
                        <div>Applicant’s O’clock</div>
                    </div>
                    <div class="block">
                        <div>
                            <div><img src="assets/images/flag-maldives.webp'}}" alt="flag">9:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">9:30am</div>
                        </div>
                        <div class="active">
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">10:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">10:30am</div>
                        </div>
                        <div class="active">
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">11:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">11:30am</div>
                        </div>
                        <div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">12:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">12:30am</div>
                        </div>
                        <div class="disable">
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">2:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">2:30am</div>
                        </div>
                        <div class="disable">
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">3:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-India.webp'}}" alt="flag">3:30am</div>
                        </div>
                        <div>
                            <div><img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp'}}" alt="flag">4:00am</div>
                            <div><img src="{{ URL::asset('resorts_assets/flag-India.webp'}}" alt="flag">4:30am</div>
                        </div>
                    </div>
                    <p>or</p>
                    <input type="text" class="form-control" placeholder="Add Manual Time" />
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#sendRequestFinal-modal" data-bs-toggle="modal" data-bs-dismiss="modal"
                    class="btn btn-theme">Submit</a>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="sendRequestFinal-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-0">
                <div class="table-responsive">
                    <table class="table table-sendRequestFinal w-100">
                        <tbody>
                            <tr>
                                <th>Name:</th>
                                <td>John Doe</td>
                            </tr>
                            <tr>
                                <th>Position:</th>
                                <td>Manager</td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td>Lorem ipsum</td>
                            </tr>
                            <tr>
                                <th>Department:</th>
                                <td>Food & Beverages</td>
                            </tr>
                            <tr>
                                <th>Interview Date:</th>
                                <td>11/09/2024</td>
                            </tr>
                            <tr>
                                <th>Time:</th>
                                <td>10:30am & 11:00am</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-theme">Submit</a>
            </div>

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
            <div class="modal-body ">
                <p>Would you like to advertise below poster for job post for Assistant Front Desk Manager?</p>
                <div class="text-center mb-sm-4 mb-3">
                    <img src="{{ URL::asset('resorts_assets/images/job-advertisement.webp')}}" alt="image">
                </div>
                <div class="text-center mb-sm-4 mb-3">
                    <a href="#" class="btn btn-themeSkyblue btn-sm">Download</a>
                </div>
                <div class="input-group mb-sm-4 mb-3">
                    <input type="text" class="form-control " placeholder="Expiry Date" />
                    <i class="fa-regular fa-calendar"></i>
                </div>
                <div class="text-center mb-sm-3 mb-2">
                    <a href="#" class="a-link">https://loremipsum.com</a>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-theme">Submit</a>
            </div>

        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
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

        var cal = $('#calendar').fullCalendar({
            header: {
                left: 'prev ',
                center: 'title',
                right: 'next'
            },
            editable: true,
            eventLimit: 0, // allow "more" link when too many events
            navLinks: true,
            dayRender: function (a) {
                //console.log(a)
            }
        });
    });

    // Trigger full calendar initialization after the modal is fully shown
    $('#respond-dateModal').on('shown.bs.modal', function () {
            // Ensure full calendar is re-rendered inside the modal after it's visible
            $('#calendarModal').fullCalendar('render');
        });

        $('#sendRequest-modal').on('shown.bs.modal', function () {
            $('#calendarModalSend').fullCalendar('render');
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
                    left: 'prev ',
                    center: 'title',
                    right: 'next'
                },
                editable: true,
                eventLimit: 0,
                navLinks: true,
                dayRender: function (date) {
                    console.log(date.format());
                }
            });

            // Calendar for send request modal
            $('#calendarModalSend').fullCalendar({
                header: {
                    left: 'prev ',
                    center: 'title',
                    right: 'next'
                },
                editable: true,
                eventLimit: 0,
                navLinks: true,
                dayRender: function (a) {
                    console.log(a);
                }
            });
        });


        var ctx = document.getElementById('myStackedBarChart').getContext('2d');
        var myStackedBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jun 2024', 'Jul 2024', 'Aug 2024', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024'],
                datasets: [
                    {
                        label: 'Loremipsum.Com',
                        data: [8, 20, 25, 10, 10, 20, 10],
                        backgroundColor: '#014653',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Lorem Ipsum',
                        data: [5, 10, 4, 20, 2, 5, 10],
                        backgroundColor: '#2EACB3',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Direct',
                        data: [20, 5, 20, 40, 42, 5, 20],
                        backgroundColor: '#FED049',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Another Source',
                        data: [5, 20, 15, 5, 5, 5, 10],
                        backgroundColor: '#8DC9C9',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                    {
                        label: 'Lorem Ipsum',
                        data: [5, 20, 4, 20, 2, 5, 5],
                        backgroundColor: '#333333',
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 10,
                    },
                ]
            },
            options: {
                plugins: {
                    legend: {
                        display: false // Hide legend
                    },
                    layout: {
                        padding: 0 // Remove padding
                    },
                    tooltip: {
                        enabled: false // Disable tooltips
                    }
                },
                hover: {
                    mode: null // Disable hover effects
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false // Hide x-axis grid lines
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            display: false // Hide y-axis grid lines
                        },
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });

        //    equal heigth js
        function equalizeHeights() {
            // Get the elements
            const block1 = document.getElementById('card-vac');
            const block2 = document.getElementById('card-todoList');

            // Get the height of block1
            const block1Height = block1.offsetHeight;

            // Set the height of block2 to match block1's height
            block2.style.height = block1Height + 'px';
        }

        window.onload = equalizeHeights; // Initial height adjustment

        // Adjust heights on window resize
        window.onresize = equalizeHeights;
</script>
@endsection
