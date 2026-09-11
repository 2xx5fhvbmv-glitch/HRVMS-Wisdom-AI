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
                <form id="add-new-vacancy">
                    <div class="card">
                        <div class="av-flow">

                            {{-- 1. General information --}}
                            <div class="av-step">
                                <span class="av-num">1</span>
                                <div class="av-sh">General information</div>
                                <div class="av-grid">
                                    <div class="av-f">
                                        <label for="vacancy_status">Budgeted or out of budget <span class="av-req">*</span></label>
                                        <select id="vacancy_status" class="form-select dd-native-select" name="budgeted">
                                            <option value="Budgeted">Budgeted</option>
                                            <option value="Out of Budget">Out of Budget</option>
                                        </select>
                                        <div class="dd" data-target="#vacancy_status">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Budgeted</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Budget status">
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Budgeted"><span class="dd-nm">Budgeted</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="Out of Budget"><span class="dd-nm">Out of Budget</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="av-f">
                                        <label for="txt-department">Department <span class="av-req">*</span></label>
                                        <input type="text" class="av-inp" name="department" id="txt-department" placeholder="e.g. Human Resources" value="{{ $department_details[0]->name }}" disabled>
                                        <input type="hidden" name="dept_id" id="dept_id" value="{{ $department_details[0]->id }}" readonly>
                                    </div>
                                    <div class="av-f">
                                        <label for="txt-required-starting-date">Required starting date <span class="av-req">*</span></label>
                                        <div class="av-datewrap" id="dateWrap">
                                            <input type="text" class="av-inp av-datebtn" name="required_starting_date" id="txt-required-starting-date" placeholder="Select a date" readonly>
                                            <span class="av-date-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                                            <div class="av-datepop">
                                                <div class="wcal-card">
                                                    <div class="wcal-head">
                                                        <span class="wcal-m" id="avCalMonth"></span>
                                                        <div class="wcal-nav">
                                                            <button type="button" id="avCalPrev" aria-label="Previous month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
                                                            <button type="button" id="avCalNext" aria-label="Next month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>
                                                        </div>
                                                    </div>
                                                    <div class="wcal-grid" id="avCalGrid"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Position details --}}
                            <div class="av-step">
                                <span class="av-num">2</span>
                                <div class="av-sh">Position details</div>
                                <div class="av-grid">
                                    <div class="av-f">
                                        <label for="position">Position title <span class="av-req">*</span></label>
                                        <select name="position" id="position" class="form-control form-select dd-native-select">
                                            @if($resort_positions)
                                                <option value="">Select position</option>
                                                @foreach($resort_positions as $position)
                                                    <option value="{{$position->id}}" data-budgeted="{{ in_array($position->id, $budgetedPositionIds) ? '1' : '0' }}" data-available="{{ $positionAvailableSlots[$position->id] ?? 0 }}">{{$position->position_title}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="dd" data-target="#position">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select position</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Position">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($resort_positions)
                                                        @foreach($resort_positions as $position)
                                                        <div class="dd-item" role="option" data-value="{{ $position->id }}"><span class="dd-nm">{{ $position->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="av-f">
                                        <label for="Total_position_required">Required no. of vacancy <span class="av-req">*</span></label>
                                        <input type="number" name="Total_position_required" id="Total_position_required" class="av-inp" min="1" placeholder="e.g. 2">
                                        <div id="vacancy-validation-msg" style="display:none; margin-top:7px; font-size:12.5px;"></div>
                                        <small id="vacancy-manning-info" class="text-muted" style="display:none; margin-top:5px; font-size:12px;"></small>
                                    </div>
                                    <div class="av-f">
                                        <label for="reporting_to">Reporting to <span class="av-req">*</span></label>
                                        <select name="reporting_to" id="reporting_to" class="form-control form-select dd-native-select">
                                            @if($reportingEmployees)
                                                <option value="">Select manager</option>
                                                @foreach($reportingEmployees as $emp)
                                                    <option value="{{$emp->id}}" {{ $emp_details[0]->id == $emp->id ? 'selected' : '' }}>{{$emp->first_name}} {{$emp->last_name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @php $selectedReportingTo = $reportingEmployees ? $reportingEmployees->firstWhere('id', $emp_details[0]->id) : null; @endphp
                                        <div class="dd" data-target="#reporting_to">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">{{ $selectedReportingTo ? $selectedReportingTo->first_name.' '.$selectedReportingTo->last_name : 'Select manager' }}</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Reporting to">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item{{ $selectedReportingTo ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select manager</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($reportingEmployees)
                                                        @foreach($reportingEmployees as $emp)
                                                        <div class="dd-item{{ ($emp_details[0]->id == $emp->id) ? ' active' : '' }}" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ $emp->first_name }} {{ $emp->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="av-f">
                                        <label for="txt-rank">Rank</label>
                                        <input type="text" class="av-inp" id="txt-rank" placeholder="e.g. Line Workers" name="rank" disabled>
                                        <input type="hidden" id="rank_id" name="rank_id">
                                    </div>
                                    <div class="av-f">
                                        <label for="txt-division">Division</label>
                                        <input type="text" class="av-inp" id="txt-division" name="division" placeholder="e.g. Administrative &amp; General" value="{{ $resort_divisions[0]->name }}" disabled>
                                        <input type="hidden" id="division_id" name="division_id" value="{{ $resort_divisions[0]->id }}">
                                    </div>
                                    <div class="av-f">
                                        <label for="txt-section">Section</label>
                                        <input type="text" class="av-inp" id="txt-section" name="section" placeholder="e.g. Admin" value="{{ $sectionName }}" disabled>
                                        <input type="hidden" id="section_id" name="section_id" value="{{ $sectionId }}">
                                    </div>
                                    <div class="av-f" id="budgeted-salary-container" style="display:none;">
                                        <label for="txt-budgeted-salary-display">Budgeted salary</label>
                                        <input type="text" class="av-inp" id="txt-budgeted-salary-display" placeholder="&mdash;" disabled>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. Allowances --}}
                            <div class="av-step">
                                <span class="av-num">3</span>
                                <div class="av-sh">Allowances <span class="av-sub">&middot; auto-applied from position</span></div>
                                <div id="budgeted-allowance-container">
                                    <div id="allowance-list">
                                        <div class="av-emptybox">
                                            <div class="av-ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M8 4v16"/></svg></div>
                                            <p>Allowances load automatically once you <span class="av-hi">select a position</span> above.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. Employee type --}}
                            <div class="av-step">
                                <span class="av-num">4</span>
                                <div class="av-sh">Employee type</div>
                                <div class="av-pills">
                                    <input class="av-pill-input" type="radio" value="Permanant" id="radio-permanant" name="employee_type" checked>
                                    <label class="av-pill" for="radio-permanant">Permanent</label>

                                    <input class="av-pill-input" type="radio" value="Casual/Agency" id="radio-casual-Agency" name="employee_type">
                                    <label class="av-pill" for="radio-casual-Agency">Casual / Agency</label>

                                    <input class="av-pill-input" type="radio" value="Trainee / Intern" id="radio-trainee-intern" name="employee_type">
                                    <label class="av-pill" for="radio-trainee-intern">Trainee / Intern</label>

                                    <input class="av-pill-input" type="radio" value="Replacement" id="radio-replacement" name="employee_type">
                                    <label class="av-pill" for="radio-replacement">Replacement</label>

                                    <input class="av-pill-input" type="radio" value="Temporary / Project" id="radio-temporary-project" name="employee_type">
                                    <label class="av-pill" for="radio-temporary-project">Temporary / Project</label>
                                </div>

                                <div id="permanent-div">
                                    <div class="av-f" style="max-width:260px; margin-top:18px;">
                                        <label>For local</label>
                                        <div class="av-seg">
                                            <input class="av-seg-input" type="radio" name="is_required_local" value="Yes" id="is_local-yes">
                                            <label for="is_local-yes">Yes</label>
                                            <input class="av-seg-input" type="radio" name="is_required_local" value="No" id="is_local-no" checked>
                                            <label for="is_local-no">No</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="replacement-employee" style="display:none;">
                                    <div class="av-f" style="max-width:400px; margin-top:18px;">
                                        <label for="txt-employee-name">Employee name</label>
                                        <select name="employee_name" id="txt-employee-name" class="form-control form-select dd-native-select">
                                            <option value="">Select employee</option>
                                            @if(isset($departmentEmployees))
                                                @foreach($departmentEmployees as $emp)
                                                    <option value="{{ $emp->first_name }} {{ $emp->last_name }}">{{ $emp->first_name }} {{ $emp->last_name }} - {{ $emp->position_title ?? '' }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="dd" data-target="#txt-employee-name">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select employee</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Employee">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if(isset($departmentEmployees))
                                                        @foreach($departmentEmployees as $emp)
                                                        <div class="dd-item" role="option" data-value="{{ $emp->first_name }} {{ $emp->last_name }}"><span class="dd-nm">{{ $emp->first_name }} {{ $emp->last_name }} - {{ $emp->position_title ?? '' }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="av-sppanel" id="temp-div" style="display:none;">
                                    <div class="av-sph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Service provider &amp; details</div>
                                    <div class="av-subgrid">
                                        <div class="av-f" id="service-provider-container">
                                            <label for="service_provider">Select service provider</label>
                                            <select name="service_provider" id="service_provider" class="form-select dd-native-select">
                                                <option value="">Select a provider</option>
                                                @foreach($serviceProviders as $provider)
                                                    <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="dd" data-target="#service_provider">
                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="dd-lbl">Select a provider</span>
                                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="dd-panel" role="listbox" aria-label="Service provider">
                                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a provider…"></div>
                                                    <div class="dd-scroll">
                                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select a provider</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @foreach($serviceProviders as $provider)
                                                        <div class="dd-item" role="option" data-value="{{ $provider->name }}"><span class="dd-nm">{{ $provider->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="av-f"><label for="new_service_provider">Or add new provider</label><input type="text" class="av-inp" name="new_service_provider" id="new_service_provider" placeholder="Enter new service provider"></div>
                                        <div class="av-f"><label for="txt-duration">Duration</label><input type="text" class="av-inp" name="duration" id="txt-duration" placeholder="e.g. 3 months, 1 year"></div>
                                        <div class="av-f">
                                            <label for="amount_unit">Amount unit <span class="av-req">*</span></label>
                                            <select name="amount_unit" id="amount_unit" required class="form-select dd-native-select">
                                                <option value="MVR">MVR</option>
                                                <option value="USD">USD</option>
                                            </select>
                                            <div class="dd" data-target="#amount_unit">
                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                    <span class="dd-lbl">MVR</span>
                                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="dd-panel" role="listbox" aria-label="Amount unit">
                                                    <div class="dd-scroll">
                                                        <div class="dd-item active" role="option" data-value="MVR"><span class="dd-nm">MVR</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        <div class="dd-item" role="option" data-value="USD"><span class="dd-nm">USD</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="av-f"><label for="salary">Salary</label><input type="text" name="salary" id="salary" class="av-inp" placeholder="Salary"></div>
                                        <div class="av-f"><label for="food">Food</label><input type="text" name="food" id="food" class="av-inp" placeholder="Food"></div>
                                        <div class="av-f"><label for="txt-accommodation">Accommodation</label><input type="text" class="av-inp" name="accommodation" id="txt-accommodation" placeholder="Accommodation"></div>
                                        <div class="av-f"><label for="txt-TRANSPORTATION">Transportation</label><input type="text" class="av-inp" name="transportation" id="txt-TRANSPORTATION" placeholder="Transportation"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- 5. Recruitment & status --}}
                            <div class="av-step">
                                <span class="av-num">5</span>
                                <div class="av-sh">Recruitment &amp; status</div>
                                <div class="av-subrow">
                                    <div>
                                        <span class="av-gl">Recruitment</span>
                                        <div class="av-pills">
                                            <input class="av-chipc-input" type="checkbox" name="recruitement[]" value="Online job posting" id="recruitment1" checked>
                                            <label class="av-chipc" for="recruitment1"><span class="av-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Online job posting</label>

                                            <input class="av-chipc-input" type="checkbox" name="recruitement[]" value="Recruiter" id="recruitment2">
                                            <label class="av-chipc" for="recruitment2"><span class="av-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Recruiter</label>

                                            <input class="av-chipc-input" type="checkbox" name="recruitement[]" value="Agency" id="recruitment3">
                                            <label class="av-chipc" for="recruitment3"><span class="av-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Agency</label>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="av-gl">Status</span>
                                        <div class="av-seg">
                                            <input class="av-seg-input" type="radio" name="status" value="Active" id="flexCheckstatus-active" checked>
                                            <label for="flexCheckstatus-active">Active</label>
                                            <input class="av-seg-input" type="radio" name="status" value="Inactive" id="flexCheckstatus-inactive">
                                            <label for="flexCheckstatus-inactive">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="av-foot">
                            <a href="javascript:void(0)" class="av-draft" id="saveAsDraftBtn">Save as draft</a>
                            <button type="submit" class="av-submit">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts._datepicker_calendar_styles')
@include('resorts.talentacquisition.vacancies._add_vacancy_styles')
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $(document).ready(function () {

            // ---- Required starting date: canonical pop-up calendar ----
            // Replaces the old flatpickr instance. The visible field stays a
            // real, focusable <input readonly> (not type=hidden/display:none)
            // so jQuery Validate's default :hidden ignore rule doesn't skip
            // its required check.
            var avDateWrap = document.getElementById('dateWrap');
            var avDateInput = document.getElementById('txt-required-starting-date');
            var AV_MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            function avPad(n) { return n < 10 ? '0' + n : '' + n; }
            function fmtAvDate(dateObj) { return avPad(dateObj.getDate()) + '/' + avPad(dateObj.getMonth() + 1) + '/' + dateObj.getFullYear(); }
            var avDatepicker = window.wisdomDatepicker.create({
                monthEl: document.getElementById('avCalMonth'),
                gridEl: document.getElementById('avCalGrid'),
                prevEl: document.getElementById('avCalPrev'),
                nextEl: document.getElementById('avCalNext'),
                onSelect: function (isoDate, dateObj) {
                    avDateInput.value = fmtAvDate(dateObj);
                    $(avDateInput).valid();
                    avDateWrap.classList.remove('open');
                }
            });
            avDateInput.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.dd.open').forEach(function (d) { d.classList.remove('open'); });
                avDateWrap.classList.toggle('open');
            });
            document.addEventListener('click', function (e) {
                if (!avDateWrap.contains(e.target)) avDateWrap.classList.remove('open');
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') avDateWrap.classList.remove('open');
            });

            // ---- Allowances table (shared by both AJAX call sites below) ----
            function avEsc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }
            function avFmtAmt(n) { return (parseFloat(n) || 0).toFixed(2); }
            // Distinct from avRenderAllowances([]) below — that's "this
            // position genuinely has no allowances configured", shown after
            // a real lookup; this is "nothing to look up yet" (no position
            // chosen). Allowances are keyed by position only, not by the
            // vacancy quantity field, so this only needs to run when the
            // position itself is cleared.
            function avEmptyPositionBox() {
                return '<div class="av-emptybox"><div class="av-ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M8 4v16"/></svg></div><p>Allowances load automatically once you <span class="av-hi">select a position</span> above.</p></div>';
            }
            function avRenderAllowances(allowances) {
                if (!allowances || allowances.length === 0) {
                    return '<div class="av-emptybox"><div class="av-ei"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M8 4v16"/></svg></div><p>No allowances configured for this position.</p></div>';
                }
                var total = 0;
                var rows = allowances.map(function (item) {
                    total += parseFloat(item.amount) || 0;
                    var pct = item.unit === '%' ? ' <span class="av-pct">' + avEsc(item.raw_amount) + '%</span>' : '';
                    return '<tr><td>' + avEsc(item.name) + pct + '</td><td class="av-amt">' + avFmtAmt(item.amount) + '</td></tr>';
                }).join('');
                return '<table class="av-tbl"><thead><tr><th>Particulars</th><th class="av-amt">Amount (USD)</th></tr></thead><tbody>' + rows +
                    '<tr class="av-totrow"><td>Total allowances</td><td class="av-amt">' + avFmtAmt(total) + '</td></tr></tbody></table>';
            }

            $('#service_provider').on('change', function() {
                toggleInput();
            });

            $('#new_service_provider').on('input', function() {
                toggleInput();
            });

            // Function to toggle between select box and textbox
            function toggleInput() {
                const inputField = $('#new_service_provider');
                const selectBox = $('#service_provider');

                // Ensure elements exist
                if (inputField.length === 0 || selectBox.length === 0) {
                    console.error('Input or select element is missing.');
                    return;
                }

                const inputValue = inputField.val()?.trim(); // Safe navigation to prevent undefined
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

                    // Fetch budgeted salary and allowances for selected position
                    $.ajax({
                        url: '{{route("resort.vacancies.getstatus")}}',
                        method: 'POST',
                        data: {
                            position_id: positionId,
                            requested_vacancy: 1,
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.budgeted_salary > 0) {
                                $('#txt-budgeted-salary-display').val(response.budgeted_salary);
                            } else {
                                $('#txt-budgeted-salary-display').val('—');
                            }
                            $('#budgeted-salary-container').show();
                            $('#allowance-list').html(avRenderAllowances(response.all_allowances));
                        }
                    });
                } else {
                    $('#txt-rank').val('');
                    $('#rank_id').val('');
                    $('#budgeted-salary-container').hide();
                    $('#allowance-list').html(avEmptyPositionBox());
                }
            });

            document.querySelectorAll('input[name="employee_type"]').forEach((radio) => {
                radio.addEventListener('change', function() {
                    const employmentType = this.value;

                    // Div elements
                    const permanentDiv = document.getElementById('permanent-div');
                    const tempDiv = document.getElementById('temp-div');
                    const replacementEmployee = document.getElementById('replacement-employee');

                    // Reset visibility
                    permanentDiv.style.display = 'none';
                    tempDiv.style.display = 'none';
                    replacementEmployee.style.display = 'none';

                    // Show/hide based on selection
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

            $('#add-new-vacancy').validate({
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
                    "required_starting_date": { required: "Required starting date is required." },
                    "position": { required: "Position field is required." },
                    "reporting_to": { required: "Reporting to field is required." },
                    "rank": { required: "Rank field is required." },
                    "division": { required: "Division field is required." },
                    "section": { required: "Section field is required." },
                    "Total_position_required": { required: "Required no. of vacancy field is required." },
                    "employee_name": { required: "Employee name is required when employee type is Replacement." }
                },
                submitHandler: function(form) {
                    // Check if Save As Draft was clicked
                    var isDraft = $('#draft-status-input').length > 0;

                    // Validate at least one recruitment checkbox is selected (skip for drafts)
                    if (!isDraft && $('input[name="recruitement[]"]:checked').length === 0) {
                        toastr.error('Please select at least one recruitment method.');
                        return false;
                    }

                    if (isDraft) {
                        // Override status to Draft
                        $('input[name="status"]').prop('checked', false);
                        $(form).append('<input type="hidden" name="status" value="Draft" id="draft-status-hidden">');
                    }

                    // Prepare data to submit
                    var formData = $(form).serialize();

                    // Perform AJAX request
                    $.ajax({
                        url: '{{ route("resort.vacancies.store") }}',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            // Clean up draft inputs
                            var wasDraft = $('#draft-status-input').length > 0 || $('#draft-status-hidden').length > 0;
                            $('#draft-status-input').remove();
                            $('#draft-status-hidden').remove();
                            $('input[name="status"][value="Active"]').prop('checked', true);

                            if (response.success) {
                                toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });
                                if (wasDraft) {
                                    window.location.href = '{{ route("resort.recruitement.hoddashboard") }}';
                                } else {
                                    window.location.href = '{{ route("resort.recruitement.hrdashboard") }}';
                                }
                            } else {
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(response) {
                            // Clean up draft inputs
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
                    // Correctly handle Select2 error placement
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next('.select2')); // Adjust this line
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    // Highlight the Select2 elements properly
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                    } else {
                        $(element).addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    // Remove highlight from Select2 elements
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                    } else {
                        $(element).removeClass('is-invalid');
                    }
                }
            });

            // Save As Draft - uses same validation as Submit, just sets status to Draft
            $('#saveAsDraftBtn').on('click', function(e) {
                e.preventDefault();
                // Add hidden input for draft status (overrides the radio buttons)
                $('#draft-status-input').remove(); // remove if already exists
                var draftInput = $('<input>').attr({ type: 'hidden', name: 'is_draft', value: '1', id: 'draft-status-input' });
                $('#add-new-vacancy').append(draftInput);
                // Trigger form validation and submit
                $('#add-new-vacancy').submit();
            });

            var vacancyValidationTimer = null;
            // Once the user manually picks Budgeted/Out of Budget, stop
            // auto-overwriting it — updateVacancyStatus() used to force
            // this field back to the server-computed status every time the
            // vacancy count changed, silently discarding an explicit
            // "Out of Budget" choice the moment the count was typed.
            // isProgrammaticBudgetUpdate distinguishes our own .val().trigger()
            // call below from a real user selection on the same 'change' event.
            var userChangedBudgetStatus = false;
            var isProgrammaticBudgetUpdate = false;

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
                        if (!userChangedBudgetStatus) {
                            isProgrammaticBudgetUpdate = true;
                            selectBox.val(response.status).trigger('change.select2');
                            isProgrammaticBudgetUpdate = false;
                        }

                        // Show budgeted salary and allowances for the selected position
                        if (response.budgeted_salary > 0) {
                            $('#txt-budgeted-salary-display').val(response.budgeted_salary);
                        } else {
                            $('#txt-budgeted-salary-display').val('—');
                        }
                        $('#budgeted-salary-container').show();
                        $('#allowance-list').html(avRenderAllowances(response.all_allowances));

                        // Show manning info
                        var infoHtml = 'Approved: ' + response.headcount +
                            ' | Filled: ' + response.filledcount +
                            ' | Vacant: ' + response.vacantCount +
                            ' | Active Vacancies: ' + response.existingVacancies +
                            ' | Available: ' + response.availableSlots;
                        $('#vacancy-manning-info').html(infoHtml).show();

                        // Validate requested vacancy against available slots
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

            // Real-time validation on keyup with debounce
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
                        $('#budgeted-salary-container').hide();
                        // Allowances are keyed by position, not quantity —
                        // only reset them if the position itself is also
                        // unset; clearing just the quantity shouldn't wipe
                        // an already-loaded allowances table.
                        if (!positionId) {
                            $('#allowance-list').html(avEmptyPositionBox());
                        }
                        $(self).removeClass('is-invalid');
                    }
                }, 400); // 400ms debounce
            });

            // Also trigger when position changes
            $('#position').on('change', function() {
                const positionId = $(this).val();
                const requestedVacancy = $('#Total_position_required').val();
                if (positionId && requestedVacancy && parseInt(requestedVacancy) > 0) {
                    updateVacancyStatus(positionId, requestedVacancy);
                } else {
                    $('#vacancy-validation-msg').hide();
                    $('#vacancy-manning-info').hide();
                    $('#budgeted-salary-container').hide();
                }
            });

            // Filter position dropdown based on budget status selection
            function filterPositionsByBudget(budgetStatus) {
                const positionSelect = $('#position');
                positionSelect.val('').trigger('change');

                positionSelect.find('option').each(function() {
                    const option = $(this);
                    if (!option.val()) return; // Skip "Select Position" placeholder

                    if (budgetStatus === 'Budgeted') {
                        // Show only budgeted positions with available slots
                        var available = parseInt(option.data('available')) || 0;
                        if (option.data('budgeted') == 1 && available > 0) {
                            option.prop('disabled', false).show();
                        } else {
                            option.prop('disabled', true).hide();
                        }
                    } else {
                        // Out of Budget - show all positions
                        option.prop('disabled', false).show();
                    }
                });
                window.wisdomDD.rebuild('#position');
            }

            // Trigger filter on budget status change
            $('#vacancy_status').on('change', function() {
                if (!isProgrammaticBudgetUpdate) {
                    userChangedBudgetStatus = true;
                }
                filterPositionsByBudget($(this).val());
            });

            // Apply filter on page load based on default selection
            filterPositionsByBudget($('#vacancy_status').val());

        });
    </script>
@include('resorts._dropdown_script')
@include('resorts._datepicker_calendar_script')
@endsection
