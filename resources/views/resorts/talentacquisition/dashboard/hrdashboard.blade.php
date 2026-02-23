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
                        <a href="{{ route('resort.vacancies.create') }}" class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.create')) == false) d-none @endif">New Hire</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3 g-xxl-4 recHR-main ">
            <div class="col-xl-8 col-lg-12 ">
                <div class="row g-3 g-xxl-4 ">
                    <div class="col-md-4 @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.shortlistedapplicants',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Total Applicants</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{ $TotalApplicants ?? 0 }}</strong>

                                </div>
                            </div>
                            <div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card card-hod ">
                            <div class="">
                                <div class="card-title">
                                    <h3>Interviews</h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <strong>{{ $Interviews ?? 0 }}</strong>

                                </div>
                            </div>
                            <div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg') }}" alt="" class="img-fluid">
                                </a> -->
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
                                    <strong>{{ $Hired ?? 0 }}</strong>

                                </div>
                            </div>
                            <div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card h-auto" id="card-vac">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-">
                                    <div class="col">
                                        <h3>Vacancies</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{route('resort.vacancies.FreshApplicant')}}" class="a-link">View all</a>
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
                                    @if(isset($NewVacancies) && $NewVacancies->isNotEmpty())
                                        @foreach ($NewVacancies as $vac)
                                                <tr>
                                                    <td> {{ $vac->positionTitle }}
                                                        <span class="badge badge-themeLight"> {{ $vac->PositonCode }} </span>
                                                    </td>
                                                    <td> {{ $vac->Department }} <span class="badge badge-themeLight"> {{ $vac->DepartmentCode }}</span></td>
                                                    <td>{{ $vac->NoOfVacnacy }}</td>
                                                    <td>{{ $vac->NoOfApplication }}</td>
                                                    <td><a href="{{ route("resort.ta.Applicants",    base64_encode($vac->vacancy_id)) }}" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                                                    </td>
                                                </tr>
                                        @endforeach
                                    @endif


                                    </tbody>
                                </table>
                            </div>
                        </div>
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

                                    @if(isset($TodoData) && $TodoData->isNotEmpty())

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
                                                            data-ExpiryDate ="{{ $t->ExpiryDate}}" data-jobadvertisement="{{$t->JobAdvertisement}}" data-link="{{$t->adv_link}}"  data-applicationUrlshow="{{$t->applicationUrlshow}}" data-applicant_link="{{$t->applicant_link}}"
                                                            data-source_links="{{ json_encode($t->source_links) }}" data-bs-toggle="modal" class="a-link jobAD-modal">Create Job Advertisement</a>

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
                                        <select class="form-control select2" name="ResortPosition" id="ResortPosition">
                                            @if( isset($Resort_Position) &&  $Resort_Position->isNotEmpty())
                                                @foreach ($Resort_Position as $position)
                                                    <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <div class="h-45 d-none d-lg-block"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-collapse table-topCoun">
                                    <tbody id="topCountriesWiseCount">


                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card card-topHiring">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Top Hiring Sources</h3>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <select class="form-select YearWiseTopSource" aria-label="Default select example">
                                                <?php
                                                $currentYear = date('Y');

                                                for ($i = 0; $i < 3; $i++) {
                                                    $startYear = $currentYear - $i;
                                                    $endYear = $startYear + 1;

                                                    echo "<option value=\"$startYear\"";

                                                    if ($i == 0)
                                                    {
                                                        echo " selected";
                                                    }

                                                    echo ">Jan $startYear - Dec $startYear</option>";
                                                }
                                                ?>
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
                                        @if(isset($HiringSource) &&  $HiringSource->isNotEmpty())
                                            @foreach ( $HiringSource as  $h)
                                            <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                                <div class="doughnut-label">
                                                    <span style="background-color: {{ $h->colour }};"></span>{{ $h->source_name }}
                                                </div>
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                          
                        </div>
                    </div>
                    <div class="col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.view')) == false) d-none @endif">
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
                    <div class="col-md-6 @if(App\Helpers\Common::checkRouteWisePermission('resort.vacancies.FreshApplicant',config('settings.resort_permissions.view')) == false) d-none @endif">
                        <div class="card">
                            <div class="card-title">
                                <div class="row justify-content-between align-items-center g-3">
                                    <div class="col">
                                        <h3>New Hire Requests</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('resort.ta.ViewVacancies') }}" class="a-link">View all</a>
                                    </div>
                                </div>
                            </div>
                            <div class="hireReq-main"  id="FreshHiringRequest">
                                @if(isset($Vacancies) &&  $Vacancies->count() > 0)
                                    @foreach ($Vacancies->take(5) as $vacancy)
                                        <div class="hireReq-block">
                                            <div class="img-circle">
                                                <img src="{{ Common::getResortUserPicture($vacancy->resort_id)}}" alt="image">
                                            </div>
                                            <div>
                                                <h6>{{ $vacancy->Department }} ({{ $vacancy->rank_name }})  </h6>
                                                <p>Requested for Hire {{ $vacancy->NoOfVacnacy }} {{ $vacancy->Position ?? 'Position' }}</p>           
                                            </div>
                                            <div class="icon">
                                                <a href="javascript:void(0)" class="respondOfFreshmodal"
                                                        data-images="{{ Common::getResortUserPicture($vacancy->resort_id) }}"
                                                        data-ta_id="{{ $vacancy->ta_id }}"
                                                        data-departmentName="{{ $vacancy->Department }}"
                                                        data-rank="{{ $vacancy->rank_name }}"
                                                        data-position="{{ $vacancy->Position }}"
                                                        data-NoOfVacnacy="{{ $vacancy->NoOfVacnacy }}"
                                                        data-Child_ta_id ="{{ $vacancy->Child_ta_id }}">
                                                        Respond
                                                </a>

                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p>No new hire requests available.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($approvalHistory) && $approvalHistory->count() > 0)
            <div class="col-xl-8 col-lg-6">
                <div class="card h-auto">
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
                    <div class="table-responsive">
                        <table class="table table-collapse">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Action</th>
                                    <th>Level</th>
                                    <th>By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approvalHistory as $history)
                                    <tr>
                                        <td>{{ $history->position_title }}</td>
                                        <td>{{ $history->department_name }}</td>
                                        <td><span class="badge {{ $history->badge_class }}">{{ $history->action_label }}</span></td>
                                        <td>{{ $history->rank_name }}</td>
                                        <td>{{ $history->action_by ?? 'N/A' }}</td>
                                        <td>{{ $history->action_date ? \Carbon\Carbon::parse($history->action_date)->format('d M Y, h:i A') : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-xl-4 col-lg-6   @if(App\Helpers\Common::checkRouteWisePermission('interview-assessment.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                <div class="card h-auto">
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
                    <div id="upinterviews">
                    </div>
                </div>
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
                <a href="#respond-HoldModel" id="holdResponseModel" data-bs-toggle="modal"  data-bs-dismiss="modal" class="btn btn-themeSkyblue">On Hold</a>
                <a href="#respond-rejectModal" id="RejectResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-danger">Reject</a>
                <a href="javascript:void(0)" id="ApprovedResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-themeBlue">Approved</a>
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
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
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
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit"  class="btn btn-themeBlue">Submit</button>
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
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-theme">Submit</button>
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
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-theme">Submit</button>

                </div>
            </form>

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
                        <tbody id="Final_response_data">

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="javascript:void(0)"  data-bs-dismiss="modal"class="btn btn-theme" >Submit</a>
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
            <form id="jobAD-form">
                @csrf
                <div class="modal-body">
                <p>Would you like to advertise below poster for job post for Assistant Front Desk Manager?</p>
                <div class="text-center mb-sm-4 mb-3">
                    <img id="JobAdvertisementImage" alt="image">
                </div>
                <div class="text-center mb-sm-4 mb-3">
                    <a href="javascript:void(0)" class="DowloadAdvertisement btn btn-themeSkyblue btn-sm">Download</a>
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
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <button  class="btn btn-theme JdSumit">Submit</button>
            </div>

        </div>
        </form>
    </div>
</div>
<input type="hidden" name="Dasboard_resort_id" value="{{$resort_id}}" id="Dasboard_resort_id" >
@endsection

@section('import-css')
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
        var cal = $('#calendar').fullCalendar({
            header: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            editable: true,
            eventLimit: 0, // Allow "more" link when too many events
            navLinks: true,
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


        var ctx = document.getElementById('myStackedBarChart').getContext('2d');
        var myStackedBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        min: 0,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
        GetHiringSource();

        $(document).on("change",".YearWiseTopSource",function(){

            GetHiringSource();
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

        function GetHiringSource()
        {
            $.ajax({
                url: "{{ route('resort.ta.topHiringSources') }}", // Replace with your route
                type: "post",
                data: {"_token":"{{ csrf_token() }}","YearWiseTopSource":$(".YearWiseTopSource").val()},
                success: function (response) {
                    myStackedBarChart.data.labels = response.labels;
                    myStackedBarChart.data.datasets = response.datasets;
                    myStackedBarChart.update();
                },
                error: function (xhr) {
                    console.error("Failed to fetch chart data", xhr);
                }
            });
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
                                    <p>Requested for Hire ${NoOfVacnacy} ${position}</p>
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

            // Fetch data attributes
            let applicationUrlShow = $(this).data("applicationurlshow");
            let applicantLink = $(this).data("applicant_link");
            let jobAdv = $(this).data("jobadvertisement");
            let jobLink = $(this).data("link");
            let childId = $(this).data("ta_childid");
            let expiryDate = $(this).data("expirydate");
            let sourceLinks = $(this).data("source_links"); // This is the new data attribute
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
            $("#JobAdvertisementImage").attr("src", jobAdv);
            $(".DowloadAdvertisement").attr("data-hrefLink", jobAdv);

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

        $(document).on("focus", '[name^="MalidivanManualTime"], [name^="ApplicantManualTime"]', function () {
            $(".row_time").removeClass("active").find("input").prop("disabled", false);
            $('[name^="ApplicantInterviewtime"]').val('');
            $('[name^="ResortInterviewtime"]').val('');
        });
        $(document).on("change", '[name="MalidivanManualTime"]', function () {
            const timeValue = $(this).val(); // Get the value of the time input
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                let MalidivanManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="MalidivanManualTime1"]').val(MalidivanManualTime1); // Display in console
            } else {
                console.log("No time selected");
            }
        });
        $(document).on("change", '[name="ApplicantManualTime"]', function () {
            const timeValue = $(this).val(); // Get the value of the time input
            if (timeValue) {
                const [hours, minutes] = timeValue.split(":"); // Split the time into hours and minutes
                const period = hours >= 12 ? "PM" : "AM"; // Determine AM or PM
                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                let ApplicantManualTime1 = formattedHours +":"+minutes+" "+period;
                $('[name="ApplicantManualTime1"]').val(ApplicantManualTime1); // Display in console
            } else {
                console.log("No time selected");
            }
        });

        $(document).on("click", ".Timezone_checkBox", function() {
            // Remove 'Active' class and enable all other rows
            $(".row_time").not($(this).closest(".row_time")).removeClass("active").find("input").prop("disabled", false);

            // Toggle 'active' class on the clicked row
            $(this).closest(".row_time").toggleClass("active");
            let location = $(this).data('id');

            if ($(this).closest(".row_time").hasClass("active")) {
                // Disable other rows and clear all input values
                $(".row_time").not($(this).closest(".row_time")).find("input").prop("disabled", true);
                $('[name^="ApplicantInterviewtime"]').val('');
                $('[name^="ResortInterviewtime"]').val('');

                // Retrieve and set the data attributes for the selected row
                let ApplicantInterviewtime = $(this).data('applicantinterviewtime');
                let ResortInterviewtime = $(this).data('resortinterviewtime');

                // Check if data attributes are undefined or null before setting
                if (ApplicantInterviewtime) {
                    $("#ApplicantInterviewtime_" + location).val(ApplicantInterviewtime);
                }

                if (ResortInterviewtime) {
                    $("#ResortInterviewtime_" + location).val(ResortInterviewtime);
                }
            } else {
                // Enable all rows if no row is active
                $(".row_time").find("input").prop("disabled", false);
            }
        });


        $('#TimeSlotsForm').validate({
            rules: {
                    SlotBook: {
                        required: function (element) {
                            // Require SlotBook only if both ManualTime fields are empty
                            return (
                                $('[name="MalidivanManualTime"]').val().trim() === "" &&
                                $('[name="ApplicantManualTime"]').val().trim() === ""
                            );
                        },
                    },
                    MalidivanManualTime: {
                        required: function (element) {
                            // Require ManualTime fields only if SlotBook is not selected
                            return $('[name="SlotBook"]:checked').length === 0;
                        },
                    },
                    ApplicantManualTime: {
                        required: function (element) {
                            // Same condition for the second ManualTime field
                            return $('[name="SlotBook"]:checked').length === 0;
                        },
                    },
                },
                messages: {
                    SlotBook: {
                        required: "Please select a valid time slot or enter a manual time.",
                    },
                    MalidivanManualTime: {
                        required: "Please enter Malidivan Manual Time or select a valid time slot.",
                    },
                    ApplicantManualTime: {
                        required: "Please enter Applicant Manual Time or select a valid time slot.",
                    },
                },
            errorPlacement: function(error, element) {
                if (element.hasClass("Timezone_checkBox")) {
                    // Append error message after the .row_time element
                    element.closest(".row_time").after(error);
                    } else {
                        error.insertAfter(element); // Default behavior
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.ta.InterviewRequest') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });


                                $("#sendRequest-modal").modal("hide");
                                $("#TimeSlots-modal").modal("hide");
                                $(".sendRequestTime-main").html(response.view);
                                $("#todoList-main").html( response.TodoDataview);
                                console.log(response,response.Final_response_data);
                                $("#Final_response_data").html(response.Final_response_data);
                                $("#sendRequestFinal-modal").modal("show");
                                // datatablelist();
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                });
            }
        });

    });

$(document).on("change", "#ResortPosition", function () {
    let PositionId = $(this).val();
    $.ajax({
        url: "{{ route('resort.ta.GePositionWiseTopAppliants') }}",
        type: "POST",
        data: {
            PositionId: PositionId,
            _token: "{{ csrf_token() }}"
        },
        success: function (response) {
            let string1 = '';

            // Check if response contains the applicant trends
            if (response && response.applicantTrends) {
                // Loop through the trends and construct rows
                $.each(response.applicantTrends, function (i, v) {
                    string1 += `
                        <tr>
                            <td><img src="${v.flag_url}" alt="flag" class="flag">${v.country}</td>
                            <td>${v.latest_count}</td>
                            <td><img src="${v.trend}" alt="icon"></td>
                        </tr>`;
                });


                $("#topCountriesWiseCount").html(string1);
            } else {

            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
            alert("An error occurred while fetching data.");
        }
    });
});

</script>
@endsection

