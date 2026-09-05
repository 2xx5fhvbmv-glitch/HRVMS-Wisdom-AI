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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <form id="edit-draft-vacancy">
                    @method('PUT')
                    <div class="card">
                        <div class="row g-md-4 g-3 mb-4">
                            <div class="col-sm-6 ">
                                <label for="select-budgeted" class="form-label">BUDGETED OR OUT OF BUDGET?</label>
                                <select id="vacancy_status" class="form-select dd-native-select" name="budgeted">
                                    <option value="Budgeted" {{ $vacancy->budgeted == 'Budgeted' ? 'selected' : '' }}>Budgeted</option>
                                    <option value="Out of Budget" {{ $vacancy->budgeted == 'Out of Budget' ? 'selected' : '' }}>Out of Budget</option>
                                </select>
                                <div class="dd" data-target="#vacancy_status">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $vacancy->budgeted == 'Out of Budget' ? 'Out of Budget' : 'Budgeted' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Budget Status">
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $vacancy->budgeted == 'Out of Budget' ? '' : ' active' }}" role="option" data-value="Budgeted"><span class="dd-nm">Budgeted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ $vacancy->budgeted == 'Out of Budget' ? ' active' : '' }}" role="option" data-value="Out of Budget"><span class="dd-nm">Out of Budget</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 d-md-inline-block d-none">

                            </div>
                            <div class="col-sm-6 ">
                                <label for="txt-department" class="form-label">DEPARTMENT</label>
                                <input type="text" class="form-control" name="department" id="txt-department" placeholder="DEPARTMENT" value="{{$department_details[0]->name}}" disabled>
                                <input type="hidden" class="form-control" name="dept_id" id="dept_id"  value="{{$department_details[0]->id}}" readonly>
                            </div>
                            <div class="col-sm-6 ">
                                <label for="txt-required-starting-date" class="form-label">REQUIRED STARTING DATE</label>
                                <input type="text" class="form-control" name="required_starting_date" id="txt-required-starting-date" placeholder="REQUIRED STARTING DATE" value="{{ $vacancy->required_starting_date ? \Carbon\Carbon::parse($vacancy->required_starting_date)->format('d/m/Y') : '' }}">
                            </div>
                        </div>

                        <div>
                            <div class="col-12">
                                <div class="card-title ">
                                    <div class="row justify-content-start align-items-center g-">
                                        <div class="col">
                                            <h3>Position Details</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-md-4 g-3">
                                <div class="col-sm-6 ">
                                    <label for="txt-position-title" class="form-label">POSITION TITLE</label>
                                    <select name="position" id="position" class="form-control form-select dd-native-select">
                                        @if($resort_positions)
                                            <option value="">Select Position</option>
                                            @foreach($resort_positions as $position)
                                                <option value="{{$position->id}}" data-budgeted="{{ in_array($position->id, $budgetedPositionIds) ? '1' : '0' }}" data-available="{{ $positionAvailableSlots[$position->id] ?? 0 }}" {{ $vacancy->position == $position->id ? 'selected' : '' }}>{{$position->position_title}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @php $selectedPosition = $resort_positions ? $resort_positions->firstWhere('id', $vacancy->position) : null; @endphp
                                    <div class="dd" data-target="#position">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">{{ $selectedPosition->position_title ?? 'Select Position' }}</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Position">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item{{ $selectedPosition ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @if($resort_positions)
                                                    @foreach($resort_positions as $position)
                                                    <div class="dd-item{{ ($vacancy->position == $position->id) ? ' active' : '' }}" role="option" data-value="{{ $position->id }}"><span class="dd-nm">{{ $position->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-position-title" class="form-label">Required No of Vacancy</label>
                                    <input type="number" name="Total_position_required" id="Total_position_required" class="form-control" min="1" value="{{ $vacancy->Total_position_required }}"/>
                                    <div id="vacancy-validation-msg" style="display:none; margin-top:5px;"></div>
                                    <small id="vacancy-manning-info" class="text-muted" style="display:none; margin-top:3px;"></small>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-reporting-to" class="form-label">REPORTING TO</label>
                                    <select name="reporting_to" id="reporting_to" class="form-control form-select dd-native-select">
                                        @if($reportingEmployees)
                                            <option value="">Select Reporting To</option>
                                            @foreach($reportingEmployees as $emp)
                                                <option value="{{$emp->id}}" {{ $vacancy->reporting_to == $emp->id ? 'selected' : '' }}>{{$emp->first_name}}   {{$emp->last_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @php $selectedReportingTo = $reportingEmployees ? $reportingEmployees->firstWhere('id', $vacancy->reporting_to) : null; @endphp
                                    <div class="dd" data-target="#reporting_to">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">{{ $selectedReportingTo ? $selectedReportingTo->first_name.' '.$selectedReportingTo->last_name : 'Select Reporting To' }}</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Reporting To">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item{{ $selectedReportingTo ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Reporting To</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @if($reportingEmployees)
                                                    @foreach($reportingEmployees as $emp)
                                                    <div class="dd-item{{ ($vacancy->reporting_to == $emp->id) ? ' active' : '' }}" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ $emp->first_name }} {{ $emp->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="txt-rank" class="form-label">RANK</label>
                                    <input type="text" class="form-control" id="txt-rank" placeholder="RANK" name="rank" disabled value="{{ $rankName }}">
                                    <input type="hidden" class="form-control" id="rank_id" name="rank_id" value="{{ $vacancy->rank }}">
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="select-division" class="form-label">DIVISION</label>
                                    <input type="text" class="form-control" id="txt-division" name="division" placeholder="DIVISION" value="{{$resort_divisions[0]->name}}" disabled>
                                    <input type="hidden" class="form-control" id="division_id" name="division_id" value="{{$resort_divisions[0]->id}}">
                                </div>
                                <div class="col-sm-6 ">
                                    <label for="select-selection" class="form-label">SECTION</label>
                                    <input type="text" class="form-control" id="txt-section" name="section" placeholder="SECTION" value="{{ $sectionName }}" disabled>
                                    <input type="hidden" class="form-control" id="section_id" name="section_id" value="{{ $sectionId }}">
                                </div>
                                <div class="col-12">
                                    <label for="select-selection" class="form-label">EMPLOYEE TYPE</label>
                                    <ul class="nav mt-2 ">
                                        <li class="form-radio">
                                            <input class="form-radio-input" type="radio" value="Permanant" id="radio-permanant" name="employee_type" {{ $vacancy->employee_type == 'Permanant' ? 'checked' : '' }}>
                                            <label class="form-radio-label" for="radio-permanant">
                                                Permanant
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Casual/Agency" id="radio-casual-Agency" name="employee_type" {{ $vacancy->employee_type == 'Casual/Agency' ? 'checked' : '' }}>
                                            <label class="form-radio-label" for="radio-casual-Agency">
                                                Casual/Agency
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Trainee / Intern" id="radio-trainee-intern" name="employee_type" {{ $vacancy->employee_type == 'Trainee / Intern' ? 'checked' : '' }}>
                                            <label class="form-radio-label" for="radio-trainee-intern">
                                                Trainee / Intern
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Replacement" id="radio-replacement" name="employee_type" {{ $vacancy->employee_type == 'Replacement' ? 'checked' : '' }}>
                                            <label class="form-radio-label" for="radio-replacement">
                                                Replacement
                                            </label>
                                        </li>
                                        <li class="form-radio ">
                                            <input class="form-radio-input" type="radio" value="Temporary / Project"
                                                id="radio-temporary-project" name="employee_type" {{ $vacancy->employee_type == 'Temporary / Project' ? 'checked' : '' }}>
                                            <label class="form-radio-label" for="radio-temporary-project">
                                                Temporary / Project
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-12" id="temp-div" style="{{ in_array($vacancy->employee_type, ['Casual/Agency', 'Trainee / Intern', 'Temporary / Project']) ? '' : 'display: none;' }}">
                                    <div id="" class="row g-md-4 g-3 row-cols-xl-5 row-cols-md-3  row-cols-sm-2 row-cols-1">
                                        <div class="col txt-service-provider" id="service-provider-container">
                                            <div>
                                                <label for="new_service_provider">New Service Provider</label>
                                                <input type="text" name="new_service_provider" id="new_service_provider" placeholder="Enter new service provider" class="form-control">
                                            </div>

                                            <div>
                                                <label for="service_provider">Select Service Provider</label>
                                                <select name="service_provider" id="service_provider" class="form-select dd-native-select">
                                                    <option value="">-- Select a service provider --</option>
                                                    @foreach($serviceProviders as $provider)
                                                        <option value="{{ $provider->name }}" {{ $vacancy->service_provider_name == $provider->name ? 'selected' : '' }}>{{ $provider->name }}</option>
                                                    @endforeach
                                                </select>
                                                @php $selectedProvider = $serviceProviders->firstWhere('name', $vacancy->service_provider_name); @endphp
                                                <div class="dd" data-target="#service_provider">
                                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                        <span class="dd-lbl">{{ $selectedProvider->name ?? '-- Select a service provider --' }}</span>
                                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                    </button>
                                                    <div class="dd-panel" role="listbox" aria-label="Service Provider">
                                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a provider…"></div>
                                                        <div class="dd-scroll">
                                                            <div class="dd-item{{ $selectedProvider ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">-- Select a service provider --</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                            @foreach($serviceProviders as $provider)
                                                            <div class="dd-item{{ ($vacancy->service_provider_name == $provider->name) ? ' active' : '' }}" role="option" data-value="{{ $provider->name }}"><span class="dd-nm">{{ $provider->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col">
                                            <label for="txt-duration" class="form-label">DURATION</label>
                                            <input type="text" class="form-control" name="duration" id="txt-duration" placeholder="e.g. 3 Months, 6 Months, 1 Year" value="{{ $vacancy->duration }}">
                                        </div>

                                        <div class="col txt-salary">
                                            <label for="txt-budget-salary" class="form-label">Amount Unit <span class="req_span">*</span></label>
                                            <select name="amount_unit" id="amount_unit" required class="form-select dd-native-select">
                                                <option value="MVR" {{ $vacancy->amount_unit == 'MVR' ? 'selected' : '' }}>MVR</option>
                                                <option value="USD" {{ $vacancy->amount_unit == 'USD' ? 'selected' : '' }}>USD</option>
                                            </select>
                                            <div class="dd" data-target="#amount_unit">
                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="dd-lbl">{{ $vacancy->amount_unit == 'USD' ? 'USD' : 'MVR' }}</span>
                                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="dd-panel" role="listbox" aria-label="Amount Unit">
                                                    <div class="dd-scroll">
                                                        <div class="dd-item{{ $vacancy->amount_unit == 'USD' ? '' : ' active' }}" role="option" data-value="MVR"><span class="dd-nm">MVR</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item{{ $vacancy->amount_unit == 'USD' ? ' active' : '' }}" role="option" data-value="USD"><span class="dd-nm">USD</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col txt-salary">
                                            <label for="select-salary" class="form-label">SALARY</label>
                                            <input type="text" name="salary" id="salary" class="form-control" placeholder="SALARY" value="{{ $vacancy->salary }}"/>
                                        </div>
                                        <div class="col txt-food">
                                            <label for="select-food" class="form-label">FOOD</label>
                                            <input type="text" name="food" id="food" class="form-control" placeholder="FOOD" value="{{ $vacancy->food }}"/>
                                        </div>
                                        <div class="col txt-accommodation">
                                            <label for="txt-accommodation" class="form-label">ACCOMMODATION</label>
                                            <input type="text" class="form-control" name="accommodation" id="txt-accommodation" placeholder="ACCOMMODATION" value="{{ $vacancy->accomodation }}">
                                        </div>
                                        <div class="col txt-transporatation">
                                            <label for="txt-transporatation" class="form-label">TRANSPORTATION</label>
                                            <input type="text" class="form-control" name="transportation" id="txt-TRANSPORTATION" placeholder="TRANSPORTATION" value="{{ $vacancy->transportation }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="replacement-employee" style="{{ $vacancy->employee_type == 'Replacement' ? '' : 'display: none;' }}">
                            <div class="col-md-4 col-sm-6 mb-3">
                                <label for="txt-employee-name" class="form-label">Employee Name</label>
                                <select name="employee_name" id="txt-employee-name" class="form-control form-select dd-native-select">
                                    <option value="">Select Employee</option>
                                    @if(isset($departmentEmployees))
                                        @foreach($departmentEmployees as $emp)
                                            <option value="{{ $emp->first_name }} {{ $emp->last_name }}" {{ $vacancy->employee == $emp->first_name . ' ' . $emp->last_name ? 'selected' : '' }}>{{ $emp->first_name }} {{ $emp->last_name }} - {{ $emp->position_title ?? '' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @php $selectedReplacementEmp = isset($departmentEmployees) ? $departmentEmployees->first(function($emp) use ($vacancy) { return $vacancy->employee == $emp->first_name . ' ' . $emp->last_name; }) : null; @endphp
                                <div class="dd" data-target="#txt-employee-name">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedReplacementEmp ? $selectedReplacementEmp->first_name.' '.$selectedReplacementEmp->last_name.' - '.($selectedReplacementEmp->position_title ?? '') : 'Select Employee' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Employee">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedReplacementEmp ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @if(isset($departmentEmployees))
                                                @foreach($departmentEmployees as $emp)
                                                <div class="dd-item{{ ($vacancy->employee == $emp->first_name . ' ' . $emp->last_name) ? ' active' : '' }}" role="option" data-value="{{ $emp->first_name }} {{ $emp->last_name }}"><span class="dd-nm">{{ $emp->first_name }} {{ $emp->last_name }} - {{ $emp->position_title ?? '' }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="permanent-div" style="{{ in_array($vacancy->employee_type, ['Permanant', 'Replacement']) ? '' : 'display: none;' }}">
                            <div class="row g-md-3 g-2">
                                <div class="col-md-3 col-sm-6 ">
                                    <label for="txt-transport" class="form-label">For Local</label>
                                    <ul class="d-flex nav align-items-center">
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="is_required_local" value="Yes" id="is_local-yes" {{ $vacancy->is_required_local == 'Yes' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_local-yes">
                                                Yes
                                            </label>
                                        </li>
                                        <li class="form-check ">
                                            <input class="form-check-input" type="radio" name="is_required_local" value="No"  id="is_local-no" {{ $vacancy->is_required_local == 'No' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_local-no">
                                                No
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="card-title mt-md-4 mt-3">
                                <div class="row justify-content-start align-items-center g-">
                                    <div class="col">
                                        <h3>Recruitment</h3>
                                    </div>
                                </div>
                            </div>

                            @php $selectedRecruitment = $vacancy->recruitment ? explode(',', $vacancy->recruitment) : []; @endphp
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Online job posting" id="recruitment1" {{ in_array('Online job posting', $selectedRecruitment) ? 'checked' : '' }}>
                                <label class="form-check-label" for="recruitment1">
                                    Online job posting
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Recruiter" id="recruitment2" {{ in_array('Recruiter', $selectedRecruitment) ? 'checked' : '' }}>
                                <label class="form-check-label" for="recruitment2">
                                    Recruiter
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="recruitement[]" value="Agency" id="recruitment3" {{ in_array('Agency', $selectedRecruitment) ? 'checked' : '' }}>
                                <label class="form-check-label" for="recruitment3">
                                    Agency
                                </label>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="col-md-4 col-sm-6 ">
                                <label for="txt-rank" class="form-label">Status</label>
                                <ul class="d-flex nav align-items-center">
                                    <li class="form-check ">
                                        <input class="form-check-input" type="radio" name="status" value="Active" id="flexCheckstatus-active" checked>
                                        <label class="form-check-label" for="flexCheckstatus-active">
                                            Active
                                        </label>
                                    </li>
                                    <li class="form-check ">
                                        <input class="form-check-input" type="radio" name="status" value="Inactive"  id="flexCheckstatus-inactive">
                                        <label class="form-check-label" for="flexCheckstatus-inactive">
                                            Inactive
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer row justify-content-between g-3">
                            <div class="col-auto">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0)" class="btn ta-btn-neutral btn-sm" id="saveAsDraftBtn">Save As Draft</a>
                                </div>
                            </div>

                            <div class="col-auto ms-auto">
                                <button type="submit" class="btn ta-btn-primary btn-sm">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card mt-4">
                    <div class="card-title">
                        <h3>Job Advertisement Poster</h3>
                        <p class="text-muted mb-0">Optional. Overrides the resort's default poster for this vacancy only.</p>
                    </div>
                    <form id="VacancyAdTemplete" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="vacancy_id" value="{{ $vacancy->id }}">
                        <div class="uploadFileNew-block mb-md-3 mb-2" id="vacancyPosterDropzone">
                            <img src="{{ URL::asset('resorts_assets/images/upload.svg') }}" alt="icon">
                            <h5>Upload This Vacancy's Poster</h5>
                            <p>Browse or Drag the file here</p>
                            <input type="file" id="vacancyPosterFile" name="Jobadvimg" hidden>
                            <div id="vacancyPosterFileName" class="mt-2 text-primary"></div>
                        </div>
                        <div class="mb-2 text-center">
                            <p class="mb-1 text-muted"><small>Current Poster:</small></p>
                            <img src="{{ \App\Helpers\Common::resolveVacancyPosterImage($vacancy->Resort_id, $vacancy->id) }}" alt="Job Ad Poster" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn ta-btn-primary btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            flatpickr('#txt-required-starting-date', {
                dateFormat: 'd/m/Y',
                allowInput: true,
                appendTo: document.body
            });

            $('#service_provider').on('change', function() {
                toggleInput();
            });

            $('#new_service_provider').on('input', function() {
                toggleInput();
            });

            function toggleInput() {
                const inputField = $('#new_service_provider');
                const selectBox = $('#service_provider');

                if (inputField.length === 0 || selectBox.length === 0) {
                    console.error('Input or select element is missing.');
                    return;
                }

                const inputValue = inputField.val()?.trim();
                const selectValue = selectBox.val();

                if (inputValue) {
                    selectBox.val('').prop('disabled', true);
                    inputField.prop('disabled', false);
                } else if (selectValue) {
                    inputField.val('').prop('disabled', true);
                    selectBox.prop('disabled', false);
                } else {
                    inputField.prop('disabled', false);
                    selectBox.prop('disabled', false);
                }
            }

            $('#position').on('change', function() {
                var positionId = $(this).val();
                if (positionId) {
                    $.ajax({
                        url: '{{ route("resort.getRank") }}',
                        type: 'GET',
                        data: { positionId: positionId },
                        success: function(response) {
                            $('#txt-rank').val(response.rank || '');
                            $('#rank_id').val(response.rank_id)
                        },
                        error: function() {
                            console.error("An error occurred while fetching the rank.");
                        }
                    });
                } else {
                    $('#txt-rank').val('');
                    $('#rank_id').val('');
                }
            });

            document.querySelectorAll('input[name="employee_type"]').forEach((radio) => {
                radio.addEventListener('change', function() {
                    const employmentType = this.value;
                    const permanentDiv = document.getElementById('permanent-div');
                    const tempDiv = document.getElementById('temp-div');
                    const replacementEmployee = document.getElementById('replacement-employee');

                    permanentDiv.style.display = 'none';
                    tempDiv.style.display = 'none';
                    replacementEmployee.style.display = 'none';

                    if (employmentType === 'Permanant' || employmentType === 'Replacement') {
                        permanentDiv.style.display = 'block';
                    }
                    if (employmentType === 'Replacement') {
                        replacementEmployee.style.display = 'block';
                    }
                    if (employmentType === 'Casual/Agency' || employmentType === 'Trainee / Intern' || employmentType === 'Temporary / Project') {
                        tempDiv.style.display = 'block';
                        toggleInput();
                    }
                });
            });

            $('#edit-draft-vacancy').validate({
                rules: {
                    "budgeted": { required: true },
                    "department": { required: true },
                    "required_starting_date": { required: true },
                    "position": { required: true },
                    "reporting_to": { required: true },
                    "rank": { required: true },
                    "division": { required: true },
                    "section": { required: true },
                    "employee_type": { required: true },
                    "Total_position_required" :{ required: true },
                    "employee_name": {
                        required: function() {
                            return $("input[name='employee_type']:checked").val() === "Replacement";
                        }
                    }
                },
                messages: {
                    "budgeted": { required: "Budgeted field is required." },
                    "department": { required: "Department field is required." },
                    "required_starting_date": { required: "Required Starting date is required." },
                    "position": { required: "Position field is required." },
                    "reporting_to": { required: "Reporting To field is required." },
                    "rank": { required: "Rank field is required." },
                    "division": { required: "Division field is required." },
                    "section": { required: "Section field is required." },
                    "Total_position_required": { required: "Required No of Vacancy field is required." },
                    "employee_name": { required: "Employee Name is required when employee type is Replacement." }
                },
                submitHandler: function(form) {
                    var isDraft = $('#draft-status-input').length > 0;
                    if (isDraft) {
                        $('input[name="status"]').prop('checked', false);
                        $(form).append('<input type="hidden" name="status" value="Draft" id="draft-status-hidden">');
                    }

                    var formData = $(form).serialize();

                    $.ajax({
                        url: '{{ route("resort.vacancies.update", $vacancy->id) }}',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            $('#draft-status-input').remove();
                            $('#draft-status-hidden').remove();
                            $('input[name="status"][value="Active"]').prop('checked', true);

                            if (response.success) {
                                toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });
                                window.location.href = '{{ route("resort.recruitement.hoddashboard") }}';
                            } else {
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(response) {
                            $('#draft-status-input').remove();
                            $('#draft-status-hidden').remove();
                            $('input[name="status"][value="Active"]').prop('checked', true);

                            var errors = response.responseJSON;
                            var errs = '';
                            if (errors && errors.errors) {
                                $.each(errors.errors, function(key, error) {
                                    errs += error + '<br>';
                                });
                            }
                            toastr.error(errs || 'Failed to save.', "Error", { positionClass: 'toast-bottom-right' });
                        }
                    });
                },
                errorPlacement: function(error, element) {
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next('.select2'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                    } else {
                        $(element).addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                    } else {
                        $(element).removeClass('is-invalid');
                    }
                }
            });

            // Save As Draft
            $('#saveAsDraftBtn').on('click', function(e) {
                e.preventDefault();
                $('#draft-status-input').remove();
                var draftInput = $('<input>').attr({ type: 'hidden', name: 'is_draft', value: '1', id: 'draft-status-input' });
                $('#edit-draft-vacancy').append(draftInput);
                $('#edit-draft-vacancy').submit();
            });

            var vacancyValidationTimer = null;

            function updateVacancyStatus(positionId, requestedVacancy) {
                $.ajax({
                    url: '{{route("resort.vacancies.getstatus")}}',
                    method: 'POST',
                    data: {
                        position_id: positionId,
                        requested_vacancy: requestedVacancy,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        const selectBox = $('#vacancy_status');
                        selectBox.val(response.status).trigger('change.select2');

                        $('#txt-budget-salary').val(response.budgeted_salary);
                        $('#txt-proposed-salary').val(response.proposed_salary);
                        $('#txt-Pension').val(response.pension);
                        $('#txt-allowances').val(response.allowance);
                        $('#txt-Medical').val(response.medical);
                        $('#txt-acommocation2').val(response.accommodation);
                        $('#txt-Insurance').val(response.insurance);

                        var infoHtml = 'Approved: ' + response.headcount +
                            ' | Filled: ' + response.filledcount +
                            ' | Vacant: ' + response.vacantCount +
                            ' | Active Vacancies: ' + response.existingVacancies +
                            ' | Available: ' + response.availableSlots;
                        $('#vacancy-manning-info').html(infoHtml).show();

                        var msgDiv = $('#vacancy-validation-msg');
                        var input = $('#Total_position_required');
                        var requested = parseInt(requestedVacancy) || 0;

                        if (response.availableSlots <= 0 && response.status === 'Budgeted') {
                            msgDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> No available vacant slots for this position. All ' + response.vacantCount + ' vacant position(s) already have active vacancy requests.</span>').show();
                            input.addClass('is-invalid');
                        } else if (requested > response.availableSlots && response.status === 'Budgeted') {
                            msgDiv.html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Requested (' + requested + ') exceeds available slots (' + response.availableSlots + '). This will be marked as Out of Budget.</span>').show();
                            input.removeClass('is-invalid');
                        } else if (response.status === 'Out of Budget') {
                            msgDiv.html('<span class="text-warning"><i class="fas fa-info-circle"></i> This vacancy is Out of Budget. Approved headcount: ' + response.headcount + ', Vacant: ' + response.vacantCount + ', Available: ' + response.availableSlots + '</span>').show();
                            input.removeClass('is-invalid');
                        } else {
                            msgDiv.html('<span class="text-success"><i class="fas fa-check-circle"></i> Within budget. ' + response.availableSlots + ' slot(s) available.</span>').show();
                            input.removeClass('is-invalid');
                        }
                    },
                    error: function()
                    {
                        toastr.error('Error fetching vacancy status.', 'Error', { positionClass: 'toast-bottom-right'});
                    }
                });
            }

            $('#Total_position_required').on('input', function() {
                clearTimeout(vacancyValidationTimer);
                var self = this;
                vacancyValidationTimer = setTimeout(function() {
                    const positionId = $('#position').val();
                    const requestedVacancy = $(self).val();
                    if (positionId && requestedVacancy && parseInt(requestedVacancy) > 0) {
                        updateVacancyStatus(positionId, requestedVacancy);
                    } else {
                        $('#vacancy-validation-msg').hide();
                        $('#vacancy-manning-info').hide();
                        $(self).removeClass('is-invalid');
                    }
                }, 400);
            });

            $('#position').on('change', function() {
                const positionId = $(this).val();
                const requestedVacancy = $('#Total_position_required').val();
                if (positionId && requestedVacancy && parseInt(requestedVacancy) > 0) {
                    updateVacancyStatus(positionId, requestedVacancy);
                } else {
                    $('#vacancy-validation-msg').hide();
                    $('#vacancy-manning-info').hide();
                }
            });

            function filterPositionsByBudget(budgetStatus) {
                const positionSelect = $('#position');
                var currentVal = positionSelect.val();

                positionSelect.find('option').each(function() {
                    const option = $(this);
                    if (!option.val()) return;

                    if (budgetStatus === 'Budgeted') {
                        var available = parseInt(option.data('available')) || 0;
                        if (option.data('budgeted') == 1 && available > 0) {
                            option.prop('disabled', false).show();
                        } else {
                            option.prop('disabled', true).hide();
                        }
                    } else {
                        option.prop('disabled', false).show();
                    }
                });
                window.wisdomDD.rebuild('#position');
            }

            $('#vacancy_status').on('change', function() {
                filterPositionsByBudget($(this).val());
            });

            filterPositionsByBudget($('#vacancy_status').val());

        });

        $('#vacancyPosterFile').on('change', function() {
            if (this.files.length > 0) {
                $('#vacancyPosterFileName').text('📎 ' + this.files[0].name);
            }
        });

        $('#VacancyAdTemplete').on('submit', function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            if ($submitBtn.prop('disabled')) return false;

            var fileInput = document.getElementById('vacancyPosterFile');
            if (!fileInput.files || fileInput.files.length === 0) {
                toastr.error("Please select an image to upload.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }

            $submitBtn.prop('disabled', true).text('Uploading...');

            var formData = new FormData(this);
            $.ajax({
                url: "{{ route('resort.ta.jobadvertisment.upload') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        location.reload();
                    } else {
                        toastr.error(response.message || "Upload failed.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    var errs = '';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errs += value[0] + '<br>';
                        });
                    } else {
                        errs = 'An unexpected error occurred. Please try again.';
                    }
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Submit');
                }
            });
        });
    </script>
@include('resorts._dropdown_script')
@endsection
