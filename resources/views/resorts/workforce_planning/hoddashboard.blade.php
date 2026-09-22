@extends('resorts.layouts.app')
{{-- $page_header carries inline HTML for the styled <h1> below; the
     browser tab title would render those tags as literal text. strip_tags
     gives us a clean plain-text version for the <title>. --}}
@section('page_tab_title' , strip_tags($page_header ?? 'HOD') . " - Dashboard")

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
                            <span>WORKFORCE PLANNING</span>
                            <h1>{!! $page_header ?? '<span class="arca-font">HOD</span> Dashboard' !!}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="row g-4 mb-30">
                        <div class="col-md-6">
                            <div class="card card-hod ">
                                <div class="">
                                    <div class="card-title">
                                        <h3>Total Employees</h3>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong>{{$totalemployees}}</strong>
                                        <div class="user-ovImg ms-xxl-4 ms-xl-3 ms-2">
                                            @if($employees->isNotEmpty())
                                                @foreach ($employees as  $emp)
                                                    <div class="img-circle">
                                                        <img src="{{ Common::getResortUserPicture($emp->Admin_Parent_id);}}" alt="image">
                                                    </div>
                                                @endforeach
                                            @endif
                                            @if($LeftemployeesCount > 0 )
                                                <div class="num">{{$LeftemployeesCount}}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{route('resort.employeelist')}}">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-hod ">
                                <div class="">
                                    <div class="card-title">
                                        <h3>Positions</h3>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <strong>{{$resort_positions_count}}</strong>
                                    </div>
                                </div>
                                <div>
                                    <a href="{{route('resort.manning.positions')}}">
                                        <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card  mb-30">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-3">
                                <div class="col">
                                    <h3>{{$department_details[0]->name}}</h3>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <?php $Currentyear = date('Y');?>
                                        <select class="form-select HodDataset" aria-label="Default select example ">
                                            @for ($i = 0; $i < 2; $i++)
                                                @php  $year1 = date('Y') + $i; @endphp
                                                <option value="{{$year1}}" >Jan {{ $year1 }} - Dec {{$year1}}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-collapse table-food">
                                <thead>
                                    <tr>
                                        <th>Positions </th>
                                        <th>No. of Vacancy</th>
                                        <th>Rank</th>
                                        <th>Nation</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="DepartmentWisePositions">
                                    @if($vacant_positions)
                                        @foreach($vacant_positions as $pos)
                                            <tr>
                                                <td>{{ $pos->position_title }}</td>
                                                <td> {{ $pos->headcount ?? '00' }} <span class="badge bg-vac">{{ $pos->vacantcount ?? '00' }} Vacant Available</span></td>
                                                <td></td>
                                                <td></td>
                                                <td>
                                                    <button class="table-icon collapsed" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse-{{$pos->id}}" aria-expanded="false"
                                                        aria-controls="collapse-{{$pos->id}}">
                                                        <i class="fa-solid fa-angle-down"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Collapsed row for employees -->
                                            @if($pos->employees && count($pos->employees) > 0)
                                                @foreach($pos->employees as $employee)
                                                    <tr class="collapse" id="collapse-{{$pos->id}}">
                                                        <td></td>
                                                        <td>
                                                            <div class="user-block">
                                                                <div class="img-circle">
                                                                    <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id);}}" alt="image">
                                                                </div>
                                                                <h6>{{ $employee->first_name }} {{ $employee->last_name }}</h6>
                                                            </div>
                                                        </td>
                                                        <td>@php


                                                        $Rank = config( 'settings.Position_Rank');


                                                        $AvilableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';

                                                    @endphp
                                                    {{$AvilableRank}}
                                                </td>
                                                        <td>{{ $employee->nationality }}</td>
                                                        <td></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5">
                    <div class="card budget-e-box mb-30 AppendLifeCycleofRequest AppendRequestManningRequest">
                        @if( isset($getNotifications) && isset($getNotifications->loginid))
                            <div class="card-title d-flex justify-content-between">
                                <h3>Requests</h3>
                            </div>
                            <div class="requestsUser-block ">
                                <div class="">
                                    <div class="img-circle">
                                    <img src="{{Common::getResortUserPicture($getNotifications->loginid) }}" alt="image">
                                    </div>
                                    <div class="">
                                        <h6>{{ $getNotifications->first_name }} {{ $getNotifications->middle_name }}</h6>
                                        <p>{{ strtoupper($getNotifications->DepartmentName) }}</p>
                                    </div>
                                </div>
                                <div class="dfs">
                                    <input type="hidden" name="message_id" id="message_id" value="{{(isset( $getNotifications->message_id)?   $getNotifications->message_id :'') }}">
                                    <h5>{{ (isset($getNotifications->reminder_message_subject )) ? $getNotifications->reminder_message_subject : $getNotifications->message_subject }}</h5>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="#sendRespond-modal" data-bs-toggle="modal" class="btn btn-sm wfp-btn-primary">Send
                                    Response</a>
                            </div>
                        @elseif(!empty($BudgetStatus) && count($BudgetStatus))
                            <div class="card-title d-flex justify-content-between">
                                <h3>Manning {{ ($Year ?? date('Y')) + 1 }}</h3>
                            </div>
                            <ul class="manning-timeline">
                                @php
                                    // Defined all the possible steps for budget approval in sitesetting Array
                                    $allSteps = config('settings.manningRequestLifeCycle');
                                @endphp
                                @if (!empty($allSteps))
                                    @foreach ($allSteps as $stepKey => $stepName)
                                        <li class="@if(array_key_exists($stepKey, $BudgetStatus) && $BudgetStatus[$stepKey]['comments'] == $stepName)
                                                active
                                            @else
                                                complete
                                            @endif">
                                            <span>{{ $stepName }} </span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        @elseif($BudgetRejactedStatus->isNotEmpty())
                            <div class="card-title d-flex justify-content-between">
                                <h3>Requests</h3>
                            </div>
                            {{-- WP5 — was a single object (->first()), so a
                                 department with more than one category
                                 rejected at once only ever showed the
                                 latest one; the loop below now renders one
                                 block per still-rejected category. The
                                 fixed-id hidden inputs this used to read
                                 into (#budget/#BudgetRejacted_message_id/
                                 #BudgetRejacted_employment_type) would
                                 collide across entries, so the modal now
                                 reads the actual clicked button's data-*
                                 attributes via event.relatedTarget instead
                                 (see the show.bs.modal handler below). --}}
                            @foreach($BudgetRejactedStatus as $rejected)
                            <div class="requestsUser-block ">
                                <div class="">
                                    <div class="img-circle">
                                    <img src="{{Common::getResortUserPicture($rejected->loginid) }}" alt="image">
                                    </div>
                                    <div class="">
                                        <h6>{{ $rejected->first_name }} {{ $rejected->middle_name }}</h6>
                                        <p>{{ strtoupper($rejected->DepartmentName) }}</p>
                                    </div>
                                </div>
                                <div class="dfs">
                                    <p class="mb-1"><strong>{{ $rejected->employment_type ?? 'Permanent' }} budget {{ $rejected->year ?? '' }}</strong></p>
                                    <h5>{{ (isset($rejected->reminder_message_subject )) ? $rejected->reminder_message_subject : $rejected->message_subject }}</h5>
                                </div>
                            </div>
                            <div class="text-center mb-2">
                                <a href="#sendRespond-modal" data-message_id="{{ $rejected->message_id ?? '' }}" data-Budget_id="{{ $rejected->Budget_id ?? '' }}" data-employment_type="{{ $rejected->employment_type ?? 'Permanent' }}" data-bs-toggle="modal" class="btn btn-sm wfp-btn-primary">Revise
                                    Response</a>
                            </div>
                            @endforeach
                        @else
                            <p>No Requests</p>
                        @endif
                    </div>
                    <div class="card  mb-30">
                        <div class="card-title d-flex justify-content-between">
                            <h3>Vacant</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table  w-100">
                                <thead>
                                    <tr>
                                        <th>Positions</th>
                                        <th class="text-nowrap">No. of Vacancy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($vacant_positions)
                                        @foreach($vacant_positions as $pos)
                                            <tr>
                                                <td>{{ $pos->position_title }}</td>
                                                <td> {{ $pos->vacantcount ?? 00 }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- <div class="card  ">
                        <div class="card-title d-flex justify-content-between">
                            <h3>AI Insights</h3>
                        </div>
                        <p class="chart-title">Expected Staffing Needs</p>
                        <canvas id="myLineChart" class="mb-3"></canvas>
                        <div class="row g-2">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Occupancy Rates
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeLightBlue"></span>Seasonal Data
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeNeon"></span>Hiring Data
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sendRespond-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="manningResponseForm" method="POST" action="{{ route('manning.responses.store') }}">
                    @csrf

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-500 d-block mb-2">Manning Category</label>
                            <div class="mrf-seg" id="employment_type_seg">
                                <input class="mrf-seg-input" type="radio" name="employment_type" value="Permanent" id="employment_type-permanent" checked>
                                <label for="employment_type-permanent">Permanent</label>
                                <input class="mrf-seg-input" type="radio" name="employment_type" value="Casual" id="employment_type-casual">
                                <label for="employment_type-casual">Casual</label>
                                <input class="mrf-seg-input" type="radio" name="employment_type" value="Intern" id="employment_type-intern">
                                <label for="employment_type-intern">Intern</label>
                            </div>
                        </div>
                        <div class="form-check mb-3 fw-500">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked">
                            <label class="form-check-label" for="flexCheckChecked">
                                Same As This Year
                            </label>
                        </div>
                        @php
                            $currentYear = date('Y'); // Get the current year
                            $nextYear = $currentYear + 1; // Calculate the next year
                            $months = [
                                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                            ]; // List of months
                        @endphp
                        <input type="hidden" name="resort_id" id="resort_id" value="{{ $resort_id }}">
                        <input type="hidden" name="dept_id" id="dept_id" value="{{ $Dept_id }}">
                        <input type="hidden" name="year" id="year" value="{{ $nextYear }}">
                        <input type="hidden" name="total_headcount" id="total_headcount">
                        <input type="hidden" name="total_vacant_headcount" id="total_vacant_headcount">
                        <input type="hidden" name="total_filled_headcount" id="total_filled_headcount">
                        <input type="hidden" name="total_headcount_current_year" id="total_headcount_current_year" value="0">
                        <input type="hidden" name="message_id" id="Submit_message_id" value="{{(isset( $getNotifications->message_id)?   $getNotifications->message_id :'') }}">
                        <input type="hidden" name="Budget_id" id="Budget_id" value="">
                        <div class="position-count-summary">
                            <h4>Position Counts</h4>
                            <div>
                                Total Filled Positions:
                                <span id="overall_filled_positions" class="badge bg-info">0</span>
                            </div>
                            <div>
                                Total Vacant Positions:
                                <span id="overall_vacant_positions" class="badge bg-info">0</span>
                            </div>
                            <div>
                                Total Headcount:
                                <span id="total-headcount" class="badge bg-info">0</span>
                            </div>
                        </div>
                        <div class="card">
                            <div class="table-responsive">
                                <table class="table table-collapse table-respond">
                                    <thead>
                                        <tr>
                                            <th>Positions</th>
                                            @foreach($months as $index => $month)
                                                <th>{{ $month }} {{ $nextYear }}
                                                    @if($index > 0) <!-- Show copy icon starting from the second month -->
                                                        <span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Copy from {{ $months[$index-1] }} {{ $nextYear }}">
                                                            <img src="{{ URL::asset('resorts_assets/images/copy.svg') }}" alt="icon" onclick="copyColumn({{ $index }}, {{ $index + 1 }})">
                                                        </span>
                                                    @endif
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="grid-tbody">
                                        @if(isset($positions) && $positions->count() > 0)
                                            @foreach($positions as $pos)
                                                <input type="hidden" name="positions[]" id="pos-{{ $pos->id }}" value="{{ $pos->id }}">
                                                <tr>
                                                    <td>
                                                        {{$pos->position_title}} ({{$pos->no_of_positions}})
                                                        <button type="button" class="table-icon collapsed ms-2" data-bs-toggle="collapse" data-bs-target="#collapse-{{$pos->id}}" aria-expanded="false" aria-controls="collapse-{{$pos->id}}" data-position-id="{{ $pos->id }}">
                                                            <i class="fa-solid fa-angle-down"></i>
                                                        </button>

                                                    </td>
                                                    <!-- 12 months (columns) -->
                                                    @for($i = 0; $i < 12; $i++)
                                                        @php
                                                            $monthName = DateTime::createFromFormat('!m', $i + 1)->format('F');
                                                        @endphp
                                                        <td>
                                                            <div class="input-group inputCounter-group">
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-number" data-type="minus" disabled="disabled" onclick="decrementValue(this)">
                                                                        <i class="fa-solid fa-minus"></i>
                                                                    </button>
                                                                </span>

                                                                <input type="hidden" id="filled_positions_{{ $pos->id}}_{{ $i }}" name="filled_positions[{{ $pos->id}}][{{ $i }}]" value="0">

                                                                <input type="hidden" id="vacant_positions_{{ $pos->id }}_{{ $i }}" name="vacant_positions[{{$pos->id}}][{{ $i }}]" value="0">

                                                                <input type="text" class="form-control input-number" name="monthly_data[{{ $pos->id }}][{{ $i }}]" id="count-{{ $pos->id }}-{{ $i }}" value="0" min="0" max="10" data-month="{{ $monthName }}" data-month-index="{{ $i }}" data-position-id="{{ $pos->id }}">
                                                                <span class="input-group-btn">
                                                                    <button type="button" class="btn btn-number" data-type="plus" onclick="incrementValue(this)">
                                                                        <i class="fa-solid fa-plus"></i>
                                                                    </button>
                                                                </span>
                                                            </div>
                                                        </td>
                                                    @endfor
                                                </tr>
                                                <!-- Hidden details for positions -->
                                                <tr class="collapse" id="collapse-{{$pos->id}}">
                                                    <td><span class="badge-headcount" id="head-count">2024 HEADCOUNT = 00 <br/> 2025 HEADCOUNT <br/> 2025 Filled COUNT <br/> 2025 Vacant COUNT </span></td>
                                                    <td id="january-{{$pos->id}}" data-month="January"></td>
                                                    <td id="february-{{$pos->id}}" data-month="February"></td>
                                                    <td id="march-{{$pos->id}}" data-month="March"></td>
                                                    <td id="april-{{$pos->id}}" data-month="April"></td>
                                                    <td id="may-{{$pos->id}}" data-month="May"></td>
                                                    <td id="june-{{$pos->id}}" data-month="June"></td>
                                                    <td id="july-{{$pos->id}}" data-month="July"></td>
                                                    <td id="august-{{$pos->id}}" data-month="August"></td>
                                                    <td id="september-{{$pos->id}}" data-month="September"></td>
                                                    <td id="october-{{$pos->id}}" data-month="October"></td>
                                                    <td id="november-{{$pos->id}}" data-month="November"></td>
                                                    <td id="december-{{$pos->id}}" data-month="December"></td>
                                                </tr>
                                            @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="13">No positions available.</td>
                                                </tr>
                                            @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-start">
                        <button type="button" class="btn wfp-btn-neutral" id="saveDraftBtn">Save As Draft</button>
                        <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn wfp-btn-primary">Submit</button>
                    </div>
                </form>

                {{-- Review-before-submit — an HOD should never be unsure whether
                     they just submitted Permanent, Casual, or Intern, or which
                     department/year. Submit on the form above no longer fires the
                     save directly; it hides the form and shows this step, and only
                     "Confirm & Submit" here actually calls manning.responses.store.
                     This is a second step INSIDE the same modal (toggled with
                     show()/hide()), not a second Bootstrap modal — two stacked
                     modals broke the backdrop and made this button unclickable. --}}
                <div id="manning-review-step" style="display:none;">
                    <div class="modal-body">
                        <div id="manning-review-single">
                            <table class="table table-sm">
                                <tbody>
                                    <tr><th>Category</th><td id="mrv-category"></td></tr>
                                    <tr><th>Department</th><td id="mrv-department"></td></tr>
                                    <tr><th>Year</th><td id="mrv-year"></td></tr>
                                    <tr><th>Total Headcount</th><td id="mrv-headcount"></td></tr>
                                    <tr><th>Filled Positions</th><td id="mrv-filled"></td></tr>
                                    <tr><th>Vacant Positions</th><td id="mrv-vacant"></td></tr>
                                </tbody>
                            </table>
                            <p class="text-muted mb-0" style="font-size:13px;">
                                This submits the <strong id="mrv-category-inline"></strong> manning request only —
                                Casual and Intern (if applicable) are separate submissions, not included here.
                            </p>
                        </div>
                        {{-- §29 — more than one category has data waiting. Each
                             submits as its own independent manning_responses
                             row (unchanged store() call, once per category),
                             just reviewed and confirmed together so an HOD
                             never loses track of which they've already sent. --}}
                        <div id="manning-review-multi" style="display:none;">
                            <p class="text-muted mb-2" style="font-size:13px;">
                                More than one category has data ready — each submits as its own separate manning request.
                            </p>
                            <table class="table table-sm">
                                <thead><tr><th>Category</th><th>Total Headcount</th><th>Filled</th><th>Vacant</th></tr></thead>
                                <tbody id="mrv-multi-rows"></tbody>
                            </table>
                            <div id="mrv-multi-results" class="mt-2" style="font-size:13px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm wfp-btn-secondary" id="manningReviewBackBtn">Back, let me check</button>
                        <button type="button" class="btn wfp-btn-primary" id="manningReviewConfirmBtn">Confirm &amp; Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- modal -->

    <div class="modal fade" id="Manning-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-manning">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <!-- <h5 class="modal-title" id="staticBackdropLabel">Manning has been sent!</h5> -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h4>Manning has been sent!</h4>
                    <div class="row g-2 justify-content-center">
                        <div class="col-auto"><span class="manningHeadcount-block">2024 headcount = 00</span></div>
                        <div class="col-auto"><span class="manningHeadcount-block">2025 headcount = 23</span></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
<style>
    /* Manning category selector — hidden-radio + :checked sibling label
       technique, same as the av-seg pattern used for Employee Type /
       Status elsewhere (talentacquisition/vacancies/_add_vacancy_styles).
       Scoped under mrf- (Manning Response Form) so it can't leak onto any
       other page. */
    .mrf-seg { display: inline-flex; background: #F7F8F8; border: 1px solid var(--line, #EEF2F2); border-radius: 11px; padding: 3px; gap: 2px; }
    .mrf-seg-input { position: absolute; opacity: 0; width: 1px; height: 1px; overflow: hidden; }
    .mrf-seg label { border: none; background: none; font: inherit; font-size: 13.5px; font-weight: 600; color: #6B7378; padding: 9px 22px; border-radius: 8px; cursor: pointer; transition: background .14s, color .14s; }
    .mrf-seg-input:checked + label { background: var(--teal, #014653); color: #fff; }
    .mrf-seg-input:focus-visible + label { outline: 2px solid var(--teal, #014653); outline-offset: 2px; }
</style>
@endsection

@section('import-scripts')

    <script type="text/javascript">
        $(document).ready(function(){
            const currentYear = new Date().getFullYear(); // e.g., 2024

            // Calculate the next year
            const nextYear = currentYear + 1; // e.g., 2025
            var  resort_id  = "{{  Auth::guard('resort-admin')->user()->resort_id }}";
            var  Position_id  = "{{  Auth::guard('resort-admin')->user()->GetEmployee->Position_id }}";
            var  Dept_id  = "{{  Auth::guard('resort-admin')->user()->GetEmployee->Dept_id }}";
            var year = nextYear;

            // console.log(resort_id,Dept_id,year);

            $(document).on('change', '.HodDataset', function () {

                $.post('{{ route("hod.getYearBasePositions") }}',
                {"_token": "{{ csrf_token() }}",
                    "year": $(this).val(),
                    "ResortId":resort_id,
                    "Position_id":Position_id,
                    "Dept_id":Dept_id
                },function (response) {

                    if(response.success == true)
                    {
                        $(".DepartmentWisePositions").html(response.html);
                    }
                    else
                    {
                        $(".DepartmentWisePositions").html(response.html);
                    }

                });
            });

            $(".table-icon").click(function () {
                $(this).parents('tr').toggleClass("in");
            });


            $('#sendRespond-modal').on('show.bs.modal', function (e) {
                // Always reopen on the form step, never mid-review (the
                // modal can be dismissed via backdrop/Esc while on the
                // review step, without going through the Back button).
                $('#manning-review-step').hide();
                $('#manningResponseForm').show();

                // WP5 — the Requests card can now render one Revise button
                // per rejected category (was a single fixed-id set of
                // hidden inputs, which collided once more than one could
                // exist at a time — see the blade loop above). Read the
                // actual button that opened this modal instead — only
                // overwrite the defaults (pre-populated server-side for
                // the plain "Send Response" flow, line ~342) when this
                // really was a Revise button (has a Budget_id to revise).
                var $trigger = $(e.relatedTarget);
                if ($trigger.data('budget_id')) {
                    $("#Budget_id").val($trigger.data('budget_id'));
                    $("#Submit_message_id").val($trigger.data('message_id') || '');
                }

                // WP5 — preselect the tab the rejection was actually about,
                // instead of always opening on whatever tab was last active
                // (usually Permanent), which showed the wrong category's
                // numbers to revise.
                var revisedCategory = $trigger.data('employment_type');
                if (revisedCategory) {
                    $('input[name="employment_type"][value="' + revisedCategory + '"]').prop('checked', true);
                }

                fetchDraftData(resort_id, Dept_id, year, $('input[name="employment_type"]:checked').val());


            });

            // Switching Permanent/Casual/Intern must not leak one category's
            // numbers into another's submission (see build spec item 1.3).
            // headcounts is keyed by position id only — Casual/Intern
            // positions are entirely different rows from Permanent ones, so
            // clearing it here (not just zeroing the visible cells) is what
            // actually stops stale totals reappearing on switch-back.
            // Each category also has its own position list (§27), so the
            // grid's rows themselves get rebuilt from the server, not just
            // reset to zero — then whatever draft/submission actually
            // exists for the newly-selected category loads via the
            // existing fetchDraftData(), once the new rows exist to fill.
            $(document).on('change', 'input[name="employment_type"]', function () {
                var category = $(this).val();
                var leavingCategory = window.mrfPreviousCategory || 'Permanent';
                window.mrfPreviousCategory = category;

                function loadCategory() {
                    headcounts = {};
                    $.ajax({
                        url: `{{ route('manning.responses.getPositionsByCategory', ['deptId' => ':Dept_id', 'employmentType' => ':employment_type']) }}`
                            .replace(':Dept_id', Dept_id)
                            .replace(':employment_type', category || 'Permanent'),
                        type: 'GET',
                        success: function (response) {
                            renderPositionRows((response && response.positions) || []);
                            updateTotalHeadcount();
                            fetchDraftData(resort_id, Dept_id, year, category);
                        },
                        error: function () {
                            toastr.error('Error loading positions for this category.', 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }

                // Auto-save the category being LEFT before switching, so an
                // HOD filling in Permanent then tabbing to Casual doesn't
                // lose the Permanent numbers (§29). Only when it actually
                // has data — an untouched tab (headcount still 0) has
                // nothing worth persisting, and skipping it means a tab
                // nobody touched never counts as "has data" for the
                // multi-category submit review below.
                var leavingHeadcount = parseInt($('#total_headcount').val()) || 0;
                if (leavingHeadcount > 0 && leavingCategory !== category) {
                    var draftFormData = new FormData(document.getElementById('manningResponseForm'));
                    // The radio's own DOM state already reflects the NEW
                    // category by the time `change` fires — override so the
                    // saved draft is filed under the category being left,
                    // not the one being switched to.
                    draftFormData.set('employment_type', leavingCategory);
                    // WP4 — saveAsDraft() now actually rejects on failure
                    // (was silently always resolving), so this .catch() is
                    // reachable for the first time; still proceed to
                    // loadCategory() either way (don't block the tab
                    // switch), but the HOD needs to actually SEE that their
                    // last edits to the leaving category weren't saved —
                    // "silent" only ever meant "no blocking alert()", not
                    // "no visible error at all".
                    saveAsDraft(draftFormData, true)
                        .then(loadCategory)
                        .catch(function (err) {
                            toastr.error((err && (err.message || err.msg)) || 'Could not save ' + leavingCategory + "'s changes before switching tabs.", 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                            loadCategory();
                        });
                } else {
                    loadCategory();
                }
            });
        });
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
    <script type="module">
        // The #myLineChart canvas lives inside a commented-out HTML block;
        // calling getContext on a missing element throws and was killing
        // the rest of this module. Guard the init.
        const _myLineChartEl = document.getElementById('myLineChart');
        if (_myLineChartEl) {
        var ctz = _myLineChartEl.getContext('2d');
        var _pWfpHodLine = window.WaiChart ? window.WaiChart.palette().teal : '#014653';
        var myLineChart = new Chart(ctz, {
            type: 'line',
            data: {
                labels: ['', 'Sep 2024', 'Oct 2024', 'Nov 2024', 'Dec 2024'], // X-axis labels
                datasets: [
                    {
                        label: 'Occupancy Rates',
                        data: [7, 10, 15, 20, 30],
                        borderColor: _pWfpHodLine,
                        backgroundColor: _pWfpHodLine,
                        borderWidth: 1,
                        fill: false,
                        tension: 0.4, // Creates smooth curves
                        // cubicInterpolationMode: 'monotone', // Monotone interpolation
                        pointRadius: 0 // Remove dots
                    },
                    {
                        // #4C88BB has no exact SSOT token match — left literal.
                        label: 'Seasonal Data',
                        data: [4, 7, 20, 35, 25], // Data points for the dataset
                        borderColor: ' #4C88BB', // Line color
                        backgroundColor: '#4C88BB',
                        borderWidth: 1,
                        fill: false,
                        tension: 0.4, // Default cubic Bézier curve (smooth curve)
                        pointRadius: 0 // Remove dots
                    },
                    {
                        // #DFFF00 has no exact SSOT token match — left literal.
                        label: 'Hiring Data',
                        data: [10, 8, 6, 15, 30], // Data points for the dataset
                        borderColor: '#DFFF00 ', // Line color
                        backgroundColor: '#DFFF00', // Fill color under the line
                        borderWidth: 1, // Line width
                        fill: false,
                        tension: 0.4, // No tension for stepped lines
                        pointRadius: 0 // Remove dots
                        // stepped: true // Stepped line interpolation
                    }
                ]
            },
            options: {
                plugins: {
                    doughnutLabelsInside: true, // Enable the custom plugin
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        left: 0,
                        right: 0
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true, // Start x-axis at zero
                        grid: {
                            display: false // Hide grid lines on the x-axis
                        },
                        border: {
                            display: true // Hide the x-axis border
                        }
                    },
                    y: {
                        grid: {
                            display: false // Hide grid lines on the y-axis
                        },
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                        }
                    }
                }
            }
        });
        if (window.WaiChart) window.WaiChart.registerForTheme(myLineChart, function (c, p) {
            c.data.datasets[0].borderColor = c.data.datasets[0].backgroundColor = p.teal;
        });
        } // end _myLineChartEl guard
    </script>
    <script>
        // Global object to store headcounts per position and month
        let headcounts = {}; // headcounts[positionId][monthName] = count
        let filledCount = 0;
        let vacantCount = 0;
        let totalFilledCount = 0;
        let totalVacantCount = 0;
        // Global variables for overall totals
        let overallFilledCount = 0;
        let overallVacantCount = 0;
        const currentYear = new Date().getFullYear(); // e.g., 2024
        const nextYear = currentYear + 1; // e.g., 2025
        // Submit no longer fires the save directly — it populates and opens
        // the review recap; only #manningReviewConfirmBtn (below) calls
        // doManningSubmit(). An HOD should never be unsure whether they
        // just submitted Permanent, Casual, or Intern, or which
        // department/year.
        // §29 — an HOD can have more than one category's worth of unsaved-
        // but-real data waiting (thanks to the tab-switch auto-save above).
        // Submit checks the server for what's already saved under the
        // OTHER categories, adds in whatever's live in the DOM for the
        // CURRENT one, and shows either today's single-category recap
        // (0 or 1 category has data) or a combined list (2-3 do).
        window.mrfCategoriesToSubmit = null;

        $('#manningResponseForm').submit(function(e) {
            e.preventDefault();

            const categoryVal = $('input[name="employment_type"]:checked').val() || 'Permanent';
            const categoryLabel = $('label[for="employment_type-' + categoryVal.toLowerCase() + '"]').text().trim() || categoryVal;
            const liveHeadcount = parseInt($('#total_headcount').val()) || 0;
            const liveFilled = parseInt($('#total_filled_headcount').val()) || 0;
            const liveVacant = parseInt($('#total_vacant_headcount').val()) || 0;

            $.ajax({
                url: `{{ route('manning.responses.categoriesWithData', ['deptId' => ':Dept_id', 'year' => ':year']) }}`
                    .replace(':Dept_id', Dept_id)
                    .replace(':year', year),
                type: 'GET',
                success: function (res) {
                    const categories = (res && res.categories) || {};
                    // The current tab's live DOM values always win over
                    // whatever's saved for it server-side — it's the
                    // freshest, may include edits since the last auto-save.
                    if (liveHeadcount > 0) {
                        categories[categoryVal] = {
                            total_headcount: liveHeadcount,
                            total_filled_positions: liveFilled,
                            total_vacant_positions: liveVacant,
                        };
                    } else {
                        delete categories[categoryVal];
                    }

                    const categoryKeys = Object.keys(categories);

                    if (categoryKeys.length <= 1) {
                        // Today's flow, unchanged.
                        window.mrfCategoriesToSubmit = null;
                        $('#manning-review-multi').hide();
                        $('#manning-review-single').show();
                        $('#mrv-category').text(categoryLabel);
                        $('#mrv-category-inline').text(categoryLabel);
                        $('#mrv-department').text(@json($department_details[0]->name ?? ''));
                        $('#mrv-year').text($('#year').val());
                        $('#mrv-headcount').text(liveHeadcount);
                        $('#mrv-filled').text(liveFilled);
                        $('#mrv-vacant').text(liveVacant);
                    } else {
                        window.mrfCategoriesToSubmit = categories;
                        $('#mrv-multi-results').empty();
                        let rows = '';
                        categoryKeys.forEach(function (cat) {
                            const c = categories[cat];
                            rows += `<tr><td>${cat}</td><td>${c.total_headcount}</td><td>${c.total_filled_positions}</td><td>${c.total_vacant_positions}</td></tr>`;
                        });
                        $('#mrv-multi-rows').html(rows);
                        $('#manning-review-single').hide();
                        $('#manning-review-multi').show();
                    }

                    $('#manningResponseForm').hide();
                    $('#manning-review-step').show();
                },
                error: function () {
                    toastr.error('Could not check other categories for saved data.', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $('#manningReviewBackBtn').on('click', function() {
            $('#manning-review-step').hide();
            $('#manningResponseForm').show();
        });

        $('#manningReviewConfirmBtn').on('click', function() {
            if (window.mrfCategoriesToSubmit) {
                submitMultipleCategories(window.mrfCategoriesToSubmit);
            } else {
                doManningSubmit();
            }
        });

        // Submits each category as its own independent manning_responses
        // row (store() is unchanged, called once per category). The
        // CURRENT tab submits its live form; every other category submits
        // from its own saved draft (fetched fresh, not assumed from the
        // summary table above). Attempts all of them even if one fails, so
        // the HOD is told exactly which went through and which didn't
        // rather than being left unsure mid-sequence.
        function submitMultipleCategories(categories) {
            const activeCategory = $('input[name="employment_type"]:checked').val() || 'Permanent';
            const categoryKeys = Object.keys(categories);
            const results = [];

            function submitOne(index) {
                if (index >= categoryKeys.length) {
                    finishMultiSubmit(results);
                    return;
                }
                const cat = categoryKeys[index];

                if (cat === activeCategory) {
                    // WP5 — #Submit_message_id was already set correctly at
                    // modal-open time (show.bs.modal handler above, from the
                    // clicked Revise button's data-message_id, or the
                    // server-populated default for a plain Send). No
                    // per-category hidden input to re-read anymore now that
                    // the Requests card can show more than one at once.
                    if (!$("#Submit_message_id").val()) {
                        $("#Submit_message_id").val($("#message_id").val());
                    }
                    // WP4 — every call in a multi-category batch skips the
                    // notification close; finishMultiSubmit() closes it
                    // once, itself, only if every category succeeded.
                    let formData = $('#manningResponseForm').serialize() + '&skip_notification_close=1';
                    $.ajax({
                        url: "{{ route('manning.responses.store') }}",
                        type: "POST",
                        data: formData,
                        success: function (response) {
                            results.push({ category: cat, success: !!(response && response.success), message: response && response.msg });
                            submitOne(index + 1);
                        },
                        error: function () {
                            results.push({ category: cat, success: false, message: 'Server error' });
                            submitOne(index + 1);
                        }
                    });
                } else {
                    // Not the active tab — pull its saved draft fresh
                    // rather than trusting the totals shown in the recap,
                    // then submit that.
                    $.ajax({
                        url: `{{ route('manning.responses.getDraft', ['resortId' => ':resort_id', 'deptId' => ':Dept_id', 'year' => ':year', 'employmentType' => ':employment_type']) }}`
                            .replace(':resort_id', resort_id)
                            .replace(':Dept_id', Dept_id)
                            .replace(':year', year)
                            .replace(':employment_type', cat),
                        type: 'GET',
                        success: function (draft) {
                            if (!draft || draft.success === false) {
                                results.push({ category: cat, success: false, message: 'No saved draft found' });
                                submitOne(index + 1);
                                return;
                            }
                            const monthly_data = {};
                            const filled_positions = {};
                            const vacant_positions = {};
                            for (const positionId in draft) {
                                if (!draft.hasOwnProperty(positionId)) continue;
                                monthly_data[positionId] = {};
                                filled_positions[positionId] = {};
                                vacant_positions[positionId] = {};
                                for (let month = 1; month <= 12; month++) {
                                    const monthData = draft[positionId][month];
                                    if (!monthData) continue;
                                    monthly_data[positionId][month - 1] = monthData.headcount || 0;
                                    filled_positions[positionId][month - 1] = monthData.filledcount || 0;
                                    vacant_positions[positionId][month - 1] = monthData.vacantcount || 0;
                                }
                            }
                            const totals = categories[cat];
                            $.ajax({
                                url: "{{ route('manning.responses.store') }}",
                                type: "POST",
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    resort_id: resort_id,
                                    dept_id: Dept_id,
                                    year: year,
                                    employment_type: cat,
                                    monthly_data: monthly_data,
                                    filled_positions: filled_positions,
                                    vacant_positions: vacant_positions,
                                    total_headcount: totals.total_headcount,
                                    total_filled_headcount: totals.total_filled_positions,
                                    total_vacant_headcount: totals.total_vacant_positions,
                                    message_id: $("#Submit_message_id").val(),
                                    skip_notification_close: 1,
                                },
                                success: function (response) {
                                    results.push({ category: cat, success: !!(response && response.success), message: response && response.msg });
                                    submitOne(index + 1);
                                },
                                error: function () {
                                    results.push({ category: cat, success: false, message: 'Server error' });
                                    submitOne(index + 1);
                                }
                            });
                        },
                        error: function () {
                            results.push({ category: cat, success: false, message: 'Could not load saved draft' });
                            submitOne(index + 1);
                        }
                    });
                }
            }

            submitOne(0);
        }

        function finishMultiSubmit(results) {
            const succeeded = results.filter(r => r.success).map(r => r.category);
            const failed = results.filter(r => !r.success);

            if (succeeded.length) {
                toastr.success('Submitted: ' + succeeded.join(', '), 'Success', { positionClass: 'toast-bottom-right' });
            }
            failed.forEach(function (r) {
                toastr.error(r.category + ': ' + (r.message || 'Submission failed'), 'Error', { positionClass: 'toast-bottom-right' });
            });

            // WP4 — the HOD's request is only "answered" once every
            // intended category went through. If ANY category failed, the
            // notification stays open so they can see it's still pending
            // and retry the failed one(s) — closing it here would leave
            // them with no way to resend a category that never actually
            // reached HR.
            function reloadPage() {
                $('#sendRespond-modal').modal('hide');
                setTimeout(function () { location.reload(); }, 1500);
            }

            if (failed.length === 0 && succeeded.length > 0) {
                $.ajax({
                    url: "{{ route('manning.responses.closeNotification') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        dept_id: Dept_id,
                        message_id: $("#Submit_message_id").val(),
                    },
                    complete: reloadPage,
                });
            } else {
                // Multiple categories/rows changed at once — reload so
                // every affected part of the page (lifecycle panel,
                // headcount badges, per-category state) reflects the
                // final DB state, rather than hand-syncing each from N
                // separate responses.
                reloadPage();
            }
        }

        function doManningSubmit() {
            // WP5 — #Submit_message_id was already set correctly at
            // modal-open time (show.bs.modal handler, from the clicked
            // Revise button's data-message_id, or the server-populated
            // default for a plain Send) — no per-category hidden input to
            // re-read anymore now that the Requests card can show more
            // than one rejected category at once.
            if (!$("#Submit_message_id").val()) {
                $("#Submit_message_id").val($("#message_id").val());
            }



            // Serialize form data
            let formData = $('#manningResponseForm').serialize();
            $.ajax({
                url: "{{ route('manning.responses.store') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if(response.success) {
                        // Update headcount display dynamically
                        document.querySelector('.manningHeadcount-block').innerText = `${currentYear} headcount = ${response.currentYearHeadcount}`;
                        document.querySelectorAll('.manningHeadcount-block')[1].innerText = `${nextYear} headcount = ${response.nextYearHeadcount}`;

                        // B11 — calling .modal('hide') immediately followed by
                        // .modal('show') on a different modal is a Bootstrap
                        // stacked-modal race: the second modal can open before
                        // the first's hide transition/backdrop cleanup
                        // finishes, leaving both visible together. Wait for
                        // #sendRespond-modal to actually finish hiding before
                        // opening #Manning-modal.
                        $('#sendRespond-modal').one('hidden.bs.modal', function () {
                            $('#Manning-modal').modal('show');
                        });
                        $('#sendRespond-modal').modal('hide');

                        $('#total_headcount_current_year').val(response.currentYearHeadcount);

                        $('.AppendLifeCycleofRequest').html(response.html);

                        // Reset form fields
                        // $('#sendRespond-modal')[0].reset();

                        // Display success notification
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // Handle validation or other errors
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    // Handle server-side error (status code 500 or validation errors)
                    let errorMessage = xhr.responseJSON.message || "An error occurred!";
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

        // Call this function on page load if needed to set initial state of minus button
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.input-number').forEach(function(input) {
                updateMinusButtonState(input);
            });
        });

        // Function to handle input changes
        function handleInputChange(input) {
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            // Validate input value
            if (isNaN(currentValue) || currentValue < 0) {
                input.value = 0; // Reset to 0 if the value is invalid
                currentValue = 0;
            } else if (currentValue > maxValue) {
                input.value = maxValue; // Cap at max value
                currentValue = maxValue;
            }

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index');

            // Make an AJAX request to get employee data
            $.ajax({
                url: '{{ route('manning.fetch.employees') }}',
                type: 'POST',
                data: {
                    position_id: positionId,
                    count: currentValue, // Use the updated count
                    "_token": "{{ csrf_token() }}",
                    employment_type: $('input[name="employment_type"]:checked').val(),
                },
                success: function(response) {
                    let monthCell = document.querySelector(`#${monthName}-${positionId}`);

                    let filledCount = 0;
                    let vacantCount = 0;

                    // Process the employee data response
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(employee => {
                            if (employee.name === "Vacant") {
                                vacantCount++;
                            } else {
                                filledCount++;
                            }
                        });

                        monthCell.innerHTML = response.map((employee, index) =>
                            employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                        ).join('');
                    } else {
                        monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        vacantCount = currentValue; // Assume all positions are vacant
                    }

                    // Update the hidden fields for filled and vacant positions
                    let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                    let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                    if (filledInput) {
                        filledInput.value = filledCount;
                    } else {
                        console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                    }

                    if (vacantInput) {
                        vacantInput.value = vacantCount;
                    } else {
                        console.error(`Vacant positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                    }

                    // Update the headcount for the month
                    updateHeadcountForMonth(positionId, monthName, currentValue, filledCount, vacantCount);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching employees:', error);
                }
            });

            updateMinusButtonState(input); // Ensure the state of the minus button is updated
        }

        function copyColumn(fromIndex, toIndex) {
            // Get all rows in the tbody
            let rows = document.querySelectorAll('tbody tr:not(.collapse)'); // Select non-collapsed rows

            rows.forEach(row => {
                // Get all columns (td) within each row
                let cells = row.querySelectorAll('td');
                // Ensure that both the source and target columns exist before proceeding
                if (cells[fromIndex] && cells[toIndex]) {
                    // Find the input field in the source column (fromIndex)
                    let sourceInput = cells[fromIndex].querySelector('input.input-number');
                    // Find the input field in the target column (toIndex)
                    let targetInput = cells[toIndex].querySelector('input.input-number');

                    // If both inputs exist, copy the value from the source to the target
                    if (sourceInput && targetInput) {
                        // Copy value from source to target
                        targetInput.value = sourceInput.value;

                        // Get position ID and month names from data attributes
                        let positionId = targetInput.getAttribute('data-position-id');
                        let fromMonth = sourceInput.getAttribute('data-month').toLowerCase();
                        let toMonth = targetInput.getAttribute('data-month').toLowerCase();
                        let monthIndex = targetInput.getAttribute('data-month-index');

                        // Update headcounts global object
                        if (!headcounts[positionId]) {
                            headcounts[positionId] = {};
                        }
                        headcounts[positionId][toMonth] = parseInt(targetInput.value);

                        // Make an AJAX request to get employee data for the target month (new month)
                        $.ajax({
                            url: '{{ route('manning.fetch.employees') }}',
                            type: 'POST',
                            data: {
                                position_id: positionId,
                                count: targetInput.value,
                                "_token": "{{ csrf_token() }}",
                                employment_type: $('input[name="employment_type"]:checked').val(),
                            },
                            success: function(response) {
                                filledCount = 0;
                                vacantCount = 0;
                                // Find the correct month cell based on the position ID and target month
                                let targetMonthCell = document.querySelector(`#${toMonth}-${positionId}`);

                                if (Array.isArray(response) && response.length > 0) {
                                    response.forEach(employee => {
                                        if (employee.name === "Vacant") {
                                            vacantCount++;
                                        } else {
                                            filledCount++;
                                        }
                                    });
                                    // If employees are found, display their names or "Vacant"
                                    let employeeNames = response.map((employee, index) => {
                                        if (employee.name === "Vacant") {
                                            return `<div><span class="badge bg-vac">Vacant</span></div>`;
                                        }
                                        return `<div>${index + 1}. ${employee.name}</div>`;
                                    }).join('');

                                    // Update the target month cell with employee names or vacant badges
                                    targetMonthCell.innerHTML = employeeNames;
                                } else {
                                    // If no employees found, display "Vacant"
                                    targetMonthCell.innerHTML = `<div><span class="badge bg-vac">Vacant</span></div>`;
                                }

                                // Expand the collapsed row for the specific position
                                $(`#collapse-${positionId}`).collapse('show');
                                let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                                let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                                if (filledInput) {
                                    filledInput.value = filledCount;
                                } else {
                                    console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                                }

                                if (vacantInput) {
                                    vacantInput.value = vacantCount;
                                } else {
                                    console.error(`Vacant positions input not found for position ID: ${positionId}`);
                                }

                                // Recalculate and update headcount for the affected position
                                // updateHeadcountForMonth(positionId, toMonth, parseInt(targetInput.value));
                                updateHeadcountForMonth(positionId, toMonth, parseInt(targetInput.value),filledCount,vacantCount);
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching employees:', error);
                            }
                        });
                    }
                }
            });

            // After copying is done, recalculate the total headcount across all positions
            updateTotalHeadcount();

            // Update the minus button state for each target input
            rows.forEach(row => {
                let cells = row.querySelectorAll('td');
                if (cells[toIndex]) {
                    let targetInput = cells[toIndex].querySelector('input.input-number');
                    if (targetInput) {
                        updateMinusButtonState(targetInput);
                    }
                }
            });

            event.stopPropagation();
        }

        function incrementValue(button) {
            let inputGroup = button.closest('.inputCounter-group');
            let input = inputGroup.querySelector('.input-number');
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index'); // Get the month index here

            if (currentValue < maxValue) {
                input.value = currentValue + 1;

                // Make an AJAX request to get employee data
                $.ajax({
                    url: '{{ route('manning.fetch.employees') }}',
                    type: 'POST',
                    data: {
                        position_id: positionId,
                        count: input.value,
                        "_token": "{{ csrf_token() }}",
                        employment_type: $('input[name="employment_type"]:checked').val(),
                    },
                    success: function(response) {
                        let monthCell = document.querySelector(`#${monthName}-${positionId}`);

                        let filledCount = 0;
                        let vacantCount = 0;

                        if (Array.isArray(response) && response.length > 0) {
                            response.forEach(employee => {
                                if (employee.name === "Vacant") {
                                    vacantCount++;
                                } else {
                                    filledCount++;
                                }
                            });

                            monthCell.innerHTML = response.map((employee, index) =>
                                employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                            ).join('');
                        } else {
                            monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                            vacantCount = input.value; // Assume all positions are vacant
                        }

                        let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                        let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                        if (filledInput) {
                            filledInput.value = filledCount;
                        } else {
                            console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                        }

                        if (vacantInput) {
                            vacantInput.value = vacantCount;
                        } else {
                            console.error(`Vacant positions input not found for position ID: ${positionId}`);
                        }

                        event.stopPropagation();
                        updateHeadcountForMonth(positionId, monthName, parseInt(input.value),filledCount,vacantCount);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching employees:', error);
                    }
                });
            }

            updateMinusButtonState(input);
            event.stopPropagation();
        }

        // Function to decrement headcount. Floor is 0 (matches the input's
        // min="0"), not 1 — otherwise users get stuck at 1 and can't reset
        // a position back to "no headcount this month".
        function decrementValue(button) {
            let inputGroup = button.closest('.inputCounter-group');
            let input = inputGroup.querySelector('.input-number');
            let currentValue = parseInt(input.value);

            if (currentValue > 0) {
                input.value = currentValue - 1;

                let positionId = input.getAttribute('data-position-id');
                let monthName = input.getAttribute('data-month').toLowerCase();
                let monthIndex = input.getAttribute('data-month-index'); // Get the month index here

                // Make an AJAX request to get employee data
                $.ajax({
                    url: '{{ route('manning.fetch.employees') }}',
                    type: 'POST',
                    data: {
                        position_id: positionId,
                        count: input.value,
                        "_token": "{{ csrf_token() }}",
                        employment_type: $('input[name="employment_type"]:checked').val(),
                    },
                    success: function(response) {
                        let monthCell = document.querySelector(`#${monthName}-${positionId}`);
                        filledCount = 0;
                        vacantCount = 0;

                        if (Array.isArray(response) && response.length > 0) {
                            response.forEach(employee => {
                                if (employee.name === "Vacant") {
                                    vacantCount++;
                                } else {
                                    filledCount++;
                                }
                            });
                            let employeeNames = response.map((employee, index) => {
                                if (employee.name === "Vacant") {
                                    return '<div><span class="badge bg-vac">Vacant</span></div>';
                                }
                                return `<div>${index + 1}. ${employee.name}</div>`;
                            }).join('');

                            monthCell.innerHTML = employeeNames;
                        } else {
                            monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        }

                        $(`#collapse-${positionId}`).collapse('show');
                        let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                        let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                        if (filledInput) {
                            filledInput.value = filledCount;
                        } else {
                            console.error(`Filled positions input not found for position ID: ${positionId} and month index: ${monthIndex}`);
                        }

                        if (vacantInput) {
                            vacantInput.value = vacantCount;
                        } else {
                            console.error(`Vacant positions input not found for position ID: ${positionId}`);
                        }
                        event.stopPropagation();
                        updateHeadcountForMonth(positionId, monthName, parseInt(input.value),filledCount,vacantCount);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching employees:', error);
                    }
                });
            }

            updateMinusButtonState(input);
            event.stopPropagation();
        }

        function updateHeadcountForMonth(positionId, monthName, newCount, filledCount, vacantCount) {
            // console.log(positionId, monthName, newCount);

            if (!headcounts[positionId]) {
                headcounts[positionId] = { employees: {}, months: {} }; // Ensure employees and months properties exist
            }

            // Update the headcount for the specific month
            headcounts[positionId].months[monthName] = newCount;
            // console.log(headcounts[positionId].months[monthName]);

            // Ensure filled and vacant counts are stored per month
            headcounts[positionId].filledCounts = headcounts[positionId].filledCounts || {};
            headcounts[positionId].vacantCounts = headcounts[positionId].vacantCounts || {};
            headcounts[positionId].filledCounts[monthName] = filledCount;
            headcounts[positionId].vacantCounts[monthName] = vacantCount;

            // Ensure counts are valid numbers
            if (isNaN(filledCount) || isNaN(vacantCount)) {
                console.error(`Invalid filledCount or vacantCount: filledCount=${filledCount}, vacantCount=${vacantCount}`);
                console.error(`Headcount data for position ID ${positionId}:`, headcounts[positionId]);
            } else {
                // Update total counts for all months
                const totalFilledCount = calculateTotalCount(headcounts[positionId].filledCounts);
                const totalVacantCount = calculateTotalCount(headcounts[positionId].vacantCounts);

                // Get the max headcount for all months for this position
                const totalHeadcountForPosition = Math.max(...Object.values(headcounts[positionId].months)) || 0;

                // Get the max filled count for all months for this position
                const totalFilledCountForPosition = Math.max(...Object.values(headcounts[positionId].filledCounts)) || 0;
                const totalVacantCountForPosition = Math.max(...Object.values(headcounts[positionId].vacantCounts)) || 0;

                // console.log(totalFilledCountForPosition, "Max Vacant count for position");

                // Update the total headcount display for the position
                updateHeadcountDisplay(positionId, totalHeadcountForPosition, totalFilledCountForPosition, totalVacantCountForPosition);
            }
        }

        function calculateTotalCount(counts) {
            return Object.values(counts).reduce((total, count) => total + count, 0);
        }

        function updateHeadcountDisplay(positionId, totalHeadcount, totalFilledCount, totalVacantCount) {
            let headcountElement = document.querySelector(`#collapse-${positionId} .badge-headcount`);

            if (headcountElement) {
                const currentYear = new Date().getFullYear(); // e.g., 2024
                const nextYear = currentYear + 1; // e.g., 2025

                // Update the headcount display
                headcountElement.innerHTML = `
                    ${currentYear} HEADCOUNT = <br/>
                    ${nextYear} HEADCOUNT = ${totalHeadcount} <br/>
                    ${nextYear} Filled COUNT = ${totalFilledCount} <br/>
                    ${nextYear} Vacant COUNT = ${totalVacantCount}
                `;
            }

            // Update the total headcount across all positions
            updateTotalHeadcount();
        }

        function updateTotalHeadcount() {
            let totalHeadcount = 0;
            let totalFilledCount = 0;
            let totalVacantCount = 0;

            // Loop through each position in the headcounts object
            for (let positionId in headcounts) {
                if (headcounts.hasOwnProperty(positionId)) {
                    // Get the max headcount for all months for this position
                    let totalHeadcountForPosition = Math.max(...Object.values(headcounts[positionId].months)) || 0;

                    // Add to the overall total headcount
                    totalHeadcount += totalHeadcountForPosition;

                    // Get the max filled count for this position
                    let maxFilledCountForPosition = Math.max(...Object.values(headcounts[positionId].filledCounts)) || 0;

                    // Get the max vacant count for this position
                    let maxVacantCountForPosition = Math.max(...Object.values(headcounts[positionId].vacantCounts)) || 0;

                    // Ensure counts are valid numbers
                    if (isNaN(maxFilledCountForPosition) || isNaN(maxVacantCountForPosition)) {
                        console.error(`Invalid filledCount or vacantCount for position ID: ${positionId}. filledCount=${maxFilledCountForPosition}, vacantCount=${maxVacantCountForPosition}`);
                        console.error(`Headcount data for position ID ${positionId}:`, headcounts[positionId]);
                    } else {
                        // Add the max filled and vacant counts to the overall totals
                        totalFilledCount += maxFilledCountForPosition;
                        totalVacantCount += maxVacantCountForPosition;
                    }
                }
            }

            // Update the DOM elements for displaying the totals
            document.getElementById('total-headcount').textContent = totalHeadcount;
            $('#total_headcount').val(totalHeadcount); // Set hidden input value if needed
            $('#total_vacant_headcount').val(totalVacantCount);
            $('#total_filled_headcount').val(totalFilledCount);
            document.getElementById('overall_filled_positions').textContent = totalFilledCount;
            document.getElementById('overall_vacant_positions').textContent = totalVacantCount;
        }

        //Function to update the minus button state — disable only at the
        // actual floor (0), not at 1, so the user can decrement 1 → 0.
        function updateMinusButtonState(input) {
            let currentValue = parseInt(input.value);
            let decrementButton = input.closest('.inputCounter-group').querySelector('[data-type="minus"]');
            if (currentValue <= 0) {
                decrementButton.setAttribute('disabled', true);
            } else {
                decrementButton.removeAttribute('disabled');
            }
        }

        function handleInputChange(input) {
            let currentValue = parseInt(input.value);
            let maxValue = parseInt(input.getAttribute('max'));

            // Validate input value
            if (isNaN(currentValue) || currentValue < 0) {
                input.value = 0;
                currentValue = 0;
            } else if (currentValue > maxValue) {
                input.value = maxValue;
                currentValue = maxValue;
            }

            let positionId = input.getAttribute('data-position-id');
            let monthName = input.getAttribute('data-month').toLowerCase();
            let monthIndex = input.getAttribute('data-month-index');

            $.ajax({
                url: '{{ route('manning.fetch.employees') }}',
                type: 'POST',
                data: {
                    position_id: positionId,
                    count: currentValue,
                    "_token": "{{ csrf_token() }}",
                    employment_type: $('input[name="employment_type"]:checked').val(),
                },
                success: function(response) {
                    let monthCell = document.querySelector(`#${monthName}-${positionId}`);
                    let filledCount = 0;
                    let vacantCount = 0;

                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(employee => {

                            console.log(employee.name);
                            if (employee.name === "Vacant") {
                                vacantCount++;
                            } else {
                                filledCount++;
                            }
                        });

                        monthCell.innerHTML = response.map((employee, index) =>
                            employee.name === "Vacant" ? '<div><span class="badge bg-vac">Vacant</span></div>' : `<div>${index + 1}. ${employee.name}</div>`
                        ).join('');
                    } else {
                        // monthCell.innerHTML = '<div><span class="badge bg-vac">Vacant</span></div>';
                        monthCell.innerHTML = '';
                        vacantCount = currentValue; // Assume all positions are vacant
                    }

                    // Update hidden fields
                    let filledInput = document.querySelector(`#filled_positions_${positionId}_${monthIndex}`);
                    let vacantInput = document.querySelector(`#vacant_positions_${positionId}_${monthIndex}`);

                    if (filledInput) filledInput.value = filledCount;
                    if (vacantInput) vacantInput.value = vacantCount;

                    updateHeadcountForMonth(positionId, monthName, currentValue, filledCount, vacantCount);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching employees:', error);
                }
            });

            updateMinusButtonState(input);
        }

        // Add event listener to all input fields to handle direct changes
        $('.input-number').on('change', function() {
            handleInputChange(this);
        });

        // Make sure to initialize the state of minus buttons on page load
        document.querySelectorAll('.input-number').forEach(input => {
            updateMinusButtonState(input); // Call this for each input to set initial state
        });

        // Event listener for input change
        document.querySelectorAll('.input-number').forEach(input => {
            input.addEventListener('change', function() {
                handleInputChange(this);
            });
        });

        // Event listener for the checkbox
        document.getElementById('flexCheckChecked').addEventListener('change', function() {
            if (this.checked) {
                let deptID = $('#dept_id').val();
                let resort_id = $('#resort_id').val();

                $.ajax({
                    url: '{{ route('manning.fetch.currentYearData') }}',
                    type: 'POST',
                    data: {
                        dept_id: deptID,
                        resort_id: resort_id,
                        employment_type: $('input[name="employment_type"]:checked').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response) {
                            for (let positionId in response) {
                                if (response.hasOwnProperty(positionId)) {
                                    for (let month = 1; month <= 12; month++) {
                                        let monthData = response[positionId][month];
                                        if (monthData) {
                                            let input = $(`#count-${positionId}-${month - 1}`);
                                            if (input.length) {
                                                input.val(monthData.headcount || 0);
                                                handleInputChange(input[0]); // Call with DOM element
                                            }
                                        }
                                    }
                                }
                            }
                            updateTotalHeadcount(); // Update the total headcount display
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching current year data:', error);
                    }
                });
            } else {
                // Optionally reset the input values when the checkbox is unchecked
                $('.input-number').val(0); // Resetting all input numbers
                updateTotalHeadcount(); // Update total headcount display after reset
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Save As Draft button click event
            document.getElementById('saveDraftBtn').addEventListener('click', function(e) {
                e.preventDefault();

                // Serialize the form data
                let formData = new FormData(document.getElementById('manningResponseForm'));

                // Append 'draft' status
                formData.append('status', 'draft');

                // Send the form data via AJAX to save as draft
                saveAsDraft(formData);
            });
        });

        // Function to handle AJAX request. `silent` skips the alert()s —
        // used by the tab-switch auto-save (§29, called from the OTHER
        // <script> block above — must stay a top-level declaration here,
        // not nested inside the DOMContentLoaded wrapper above, or it's
        // unreachable from there). Returns the fetch promise so a caller
        // can chain on it.
        // WP4 — resolves ONLY on a real success (including the no-op
        // "already submitted, not modified" case), rejects on everything
        // else (network error, 403, validation failure) so a caller's
        // .catch() actually fires instead of this swallowing it and
        // always resolving regardless of what the server said.
        function saveAsDraft(formData, silent) {
            return fetch("{{ route('manning.responses.saveDraft') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    if (!silent) alert(data.message || data.msg || 'Error saving draft. Please try again.');
                    return Promise.reject(data);
                }
                if (!silent) alert('Draft saved successfully!');
                return data;
            })
            .catch(error => {
                console.error('Error:', error);
                if (!silent && !(error && error.success === false)) alert('An unexpected error occurred.');
                return Promise.reject(error);
            });
        }

        // Rebuilds #grid-tbody from a category's own position list (§27) —
        // replaces the old "one static shared grid, tabs just relabel it"
        // setup, which is what let stale values leak between categories.
        // Mirrors the server-rendered markup exactly (same ids/names) so
        // fetchDraftData()'s `#count-${positionId}-${month}` lookups and
        // incrementValue/decrementValue's inline onclick keep working
        // unchanged on these dynamically-inserted rows.
        function renderPositionRows(positions) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            if (!positions || positions.length === 0) {
                $('#grid-tbody').html('<tr><td colspan="13">No positions available.</td></tr>');
                return;
            }

            let html = '';
            positions.forEach(function (pos) {
                html += `<input type="hidden" name="positions[]" id="pos-${pos.id}" value="${pos.id}">`;
                html += `<tr><td>${$('<div>').text(pos.position_title).html()} (${pos.no_of_positions || 0})
                    <button type="button" class="table-icon collapsed ms-2" data-bs-toggle="collapse" data-bs-target="#collapse-${pos.id}" aria-expanded="false" aria-controls="collapse-${pos.id}" data-position-id="${pos.id}">
                        <i class="fa-solid fa-angle-down"></i>
                    </button>
                </td>`;

                for (let i = 0; i < 12; i++) {
                    const monthName = monthNames[i];
                    html += `<td>
                        <div class="input-group inputCounter-group">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-number" data-type="minus" disabled="disabled" onclick="decrementValue(this)">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                            </span>
                            <input type="hidden" id="filled_positions_${pos.id}_${i}" name="filled_positions[${pos.id}][${i}]" value="0">
                            <input type="hidden" id="vacant_positions_${pos.id}_${i}" name="vacant_positions[${pos.id}][${i}]" value="0">
                            <input type="text" class="form-control input-number" name="monthly_data[${pos.id}][${i}]" id="count-${pos.id}-${i}" value="0" min="0" max="10" data-month="${monthName}" data-month-index="${i}" data-position-id="${pos.id}">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-number" data-type="plus" onclick="incrementValue(this)">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </span>
                        </div>
                    </td>`;
                }
                html += '</tr>';

                html += `<tr class="collapse" id="collapse-${pos.id}">
                    <td><span class="badge-headcount" id="head-count">2024 HEADCOUNT = 00 <br/> 2025 HEADCOUNT <br/> 2025 Filled COUNT <br/> 2025 Vacant COUNT </span></td>`;
                monthNames.forEach(function (m) {
                    html += `<td id="${m.toLowerCase()}-${pos.id}" data-month="${m}"></td>`;
                });
                html += '</tr>';
            });

            $('#grid-tbody').html(html);

            // Direct typing (not the +/- buttons, which call
            // incrementValue/decrementValue directly) needs this bound —
            // the page-load binding at $('.input-number').on('change', ...)
            // only ever covered the elements that existed at that point.
            $('#grid-tbody .input-number').on('change', function () {
                handleInputChange(this);
            });
            document.querySelectorAll('#grid-tbody .input-number').forEach(function (input) {
                updateMinusButtonState(input);
            });
        }

        function fetchDraftData(resort_id, Dept_id, year, employment_type) {
            // console.log(resort_id, Dept_id, year, employment_type);
            $.ajax({
                // Use JavaScript string interpolation to pass the dynamic values in the URL
                url: `{{ route('manning.responses.getDraft', ['resortId' => ':resort_id', 'deptId' => ':Dept_id', 'year' => ':year', 'employmentType' => ':employment_type']) }}`
                    .replace(':resort_id', resort_id)
                    .replace(':Dept_id', Dept_id)
                    .replace(':year', year)
                    .replace(':employment_type', employment_type || 'Permanent'),
                type: 'GET',
                success: function(response) {
                    // console.log("AJAX Response:", response); // Log the response regardless of success or failure

                    if (response) {
                        // Handle successful response
                        for (let positionId in response) {
                            // console.log(positionId);
                            if (response.hasOwnProperty(positionId)) {
                                for (let month = 1; month <= 12; month++) {
                                    let monthData = response[positionId][month];
                                    // console.log(monthData, "monthData");
                                    if (monthData) {
                                        let input = $(`#count-${positionId}-${month - 1}`);
                                        // console.log(input, "INPUT");
                                        if (input.length) {
                                            input.val(monthData.headcount || 0);

                                            handleInputChange(input[0]);
                                        }
                                    }
                                }
                            }
                        }
                        updateTotalHeadcount(); // Update the total headcount display
                    } else {
                        // console.warn("Response did not indicate success:", response.msg);
                        toastr.warning(response.msg, "Warning", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // console.error("AJAX Error: Status -", status, "Error -", error);
                    // console.error("Response:", xhr.responseText); // Log the response text for more details
                    toastr.error("Error fetching draft data.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

    </script>

@endsection
