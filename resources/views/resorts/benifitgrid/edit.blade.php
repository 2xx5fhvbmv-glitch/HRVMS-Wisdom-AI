@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Configuration</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <form method="post" name="addBenifitGridForm" id="addBenifitGridForm" enctype="multipart/form-data" @if(empty($benefit_grid->id)) action="{{ route('resort.benifitgrid.store') }}" @else action="{{ route('resort.benifitgrid.update', $benefit_grid->id) }}" @endif data-parsley-validate>
                @csrf
                <div class=" row g-md-4 g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="emp-grade-select">Select Employee Grade <span class="req_span">*</span></label>

                            <select id="emp-grade-select" class="form-select select2t-none" name="emp_grade" 
                            data-parsley-errors-container="#div-emp_grade" 
                            required
                            data-parsley-required-message="Please Select Employee Grade"
                            @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Employee Grade</option>
                                @if(!empty($emp_grade))
                                    @foreach ($emp_grade as $key => $value)
                                        <option value="{{ $key }}" @if($benefit_grid->emp_grade == $key) selected @endif>{{ $value }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div id="div-emp_grade"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="salary-period-select">Salary Period <span class="req_span">*</span></label>
                            <select id="salary-period-select" name="salary_period"
                            
                            data-parsley-errors-container="#div-salary_period" 
                            required
                            data-parsley-required-message="Please Select Salary Period"
                            class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Salary Period</option>
                                <option value="hourly" @if($benefit_grid->salary_period == "hourly") selected @endif>Hourly</option>
                                <option value="daily" @if($benefit_grid->salary_period == "daily") selected @endif>Daily</option>
                                <option value="weekly" @if($benefit_grid->salary_period == "weekly") selected @endif>Weekly</option>
                                <option value="monthly" @if($benefit_grid->salary_period == "monthly") selected @endif>Monthly</option>
                                <option value="yearly" @if($benefit_grid->salary_period == "yearly") selected @endif>Yearly</option>
                            </select>
                            <div id="div-salary_period"></div>

                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="salary-paidin-select">Salary Paid In <span class="req_span">*</span></label>
                            <select id="salary-paidin-select" 
                            data-parsley-errors-container="#div-salary_paid_in" 
                            required
                            data-parsley-required-message="Please Select Salary Paid In"
                            name="salary_paid_in" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Salary Paid In</option>
                                <option value="USD" @if($benefit_grid->salary_paid_in == "USD") selected @endif>USD</option>
                                <option value="MVR" @if($benefit_grid->salary_paid_in == "MVR") selected @endif>MVR</option>
                            </select>
                            <div id="div-salary_paid_in"></div>

                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="contract-status-select">Contract Status <span class="req_span">*</span></label>
                            <select id="contract-status-select"
                            data-parsley-errors-container="#div-contract_status" 
                            required
                            data-parsley-required-message="Please Select Contract Status"
                             name="contract_status" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Contract Status</option>
                                <option value="single" @if($benefit_grid->contract_status == "single") selected @endif>Single</option>
                                <option value="married" @if($benefit_grid->contract_status == "married") selected @endif>Married</option>
                            </select>
                            <div id="div-contract_status"></div>

                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="effective_date">Effective Date</label>
                            <input type="text" 
                            id="effective_date" 
                            name="effective_date" 
                            class="form-control" 
                            required 
                            data-parsley-required-message="Please select effective date." 
                            value="{{ $benefit_grid->effective_date }}" 
                            @if(isset($isViewMode) && $isViewMode) disabled @endif />
                        </div>
                    </div>
                </div>

                <div class="card-title">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-auto">
                            <div class="d-flex justify-content-start align-items-center">
                                <h3>Leave and Holiday Policy</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-md-4 g-3 mb-4" id="Leave-categories">
                    @if($LeaveCategories)
                        @foreach($LeaveCategories as $key => $leave)
                        @php
                            $allocatedDays = $benefitGridChildMap[$leave->id]->allocated_days ?? 0;
                            $eligibleEmp = $benefitGridChildMap[$leave->id]->eligible_emp_type ?? 0;
                        @endphp
                            <div class="col-xxl-4 col-sm-6 mb-3">
                                <div class="leave-category-group border rounded p-3">
                                    <h5 class="mb-3">{{$leave->leave_type}}</h5>
                                    <div class="row">
                                        <div class="col-lg-6 form-group mb-2">
                                            <label class="form-label" for="{{str_replace(' ', '', $leave->leave_type)}}">Number of Days</label>
                                            <input type="number" min="0" 
                                                required 
                                                data-parsley-required-message="Please enter number of days." 
                                                id="{{str_replace(' ', '', $leave->leave_type)}}"  
                                                name="LeaveCat[{{$leave->id}}][{{$leave->eligibility}}][]" 
                                                class="form-control" value="{{ $allocatedDays }}"
                                                @if(isset($isViewMode) && $isViewMode) disabled @endif
                                                max="{{$leave->number_of_days}}" required/>
                                        </div>
                                        <div class="col-lg-6 form-group mb-2">
                                            <label class="form-label" for="eligible_emp_type_{{$key}}">Eligible Employee Type</label>
                                            <select name="eligible_emp_type[{{$leave->id}}]"
                                                data-parsley-errors-container="#div-eligible_emp_type_{{$key}}" 
                                                required
                                                data-parsley-required-message="Please Select Eligible Employee Type" 
                                                id="eligible_emp_type_{{$key}}" 
                                                @if(isset($isViewMode) && $isViewMode) disabled @endif 
                                                class="form-select select2t-none">
                                                <option value="all" @if($eligibleEmp == "all") selected @endif>All Employees</option>
                                                <option value="female" @if($eligibleEmp == "female") selected @endif>Females</option>
                                                <option value="male" @if($eligibleEmp == "male") selected @endif>Males</option>
                                                <option value="muslim" @if($eligibleEmp == "muslim") selected @endif>Muslims</option>
                                                <option value="non-muslim" @if($eligibleEmp == "non-muslim") selected @endif>Non-Muslims</option>
                                            </select>
                                            <div id="div-eligible_emp_type_{{$key}}"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                        <div class="col-xxl-4 col-sm-6 mb-3">
                            <div class="leave-category-group border rounded p-3">
                                <h5 class="mb-3">Ramadan Bonus</h5>
                                <div class="row">
                                    <div class="col-sm-6 form-group mb-2">
                                        <label class="form-label" for="ramadan_bonus">Amount</label>
                                        <input type="number" min="0" id="ramadan_bonus" name="ramadan_bonus" class="form-control" value="{{$benefit_grid->ramadan_bonus}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                                    </div>
                                    <div class="col-sm-6 form-group mb-2">
                                        <label class="form-label" for="ramadan_bonus_eligibility">Eligible Employee Type</label>
                                        <select name="ramadan_bonus_eligibility"
                                            data-parsley-errors-container="#div-ramadan_bonus_eligibility" 
                                            required
                                            data-parsley-required-message="Please Select Eligible Employee Type" 
                                            id="ramadan_bonus_eligibility" 
                                            @if(isset($isViewMode) && $isViewMode) disabled @endif 
                                            class="form-select select2t-none">
                                            <option value="all" @if($benefit_grid->ramadan_bonus_eligibility == "all") selected @endif>All Employees</option>
                                            <option value="all_muslim" @if($benefit_grid->ramadan_bonus_eligibility == "all_muslims") selected @endif>All Muslims</option>
                                            <option value="local_muslim" @if($benefit_grid->ramadan_bonus_eligibility == "local_muslims") selected @endif>All Local Muslims</option>
                                            <option value="all_local" @if($benefit_grid->ramadan_bonus_eligibility == "locals") selected @endif>All Local Employees</option>
                                        </select>
                                        <div id="div-ramadan_bonus_eligibility"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                <div class="row g-md-4 g-3 mb-4">
                    <!-- Existing fields... -->
                    <div class="col-sm-12 mt-3">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Custom Leave Types</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <button type="button"  id="addCustomLeave" class="btn btn-sm btn-theme">
                                            <i class="fa-solid fa-plus me-2"></i>Add More Leave
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- <h5>Custom Leave Types</h5> --}}
                        <div id="customLeaveContainer"></div>
                    </div>
                </div>

                @if($custom_leave)
                    @foreach($custom_leave as $leave)
                        <div class="row">
                            <div class="col-sm-4">
                                <label>{{ $leave->leave_name }}</label>
                                <input type="number" min="0" name="custom_leave[{{ $loop->index }}][days]" class="form-control" value="{{ $leave->leave_days }}" />
                            </div>
                        </div>
                    @endforeach
                @endif
                
                <div class="row g-md-4 g-3 mb-4">                   
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="day_off_per_week">Day Off Per Week (In Days)</label>
                            <input type="number" min="0" id="day_off_per_week" name="day_off_per_week"
                            required 
                            data-parsley-required-message="Please enter Day off per week." 
                             class="form-control" value="{{$benefit_grid->day_off_per_week}}" @if(isset($isViewMode) && $isViewMode) disabled @endif data-parsley-required="true" 
                            data-parsley-min="1" />
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="working_hrs_per_week"> Working Hours per week (In Hours)</label>
                            <input type="number" min="0"
                            required 
                            data-parsley-required-message="Please enter Working hours per week." 
                             id="working_hrs_per_week" name="working_hrs_per_week" class="form-control" value="{{$benefit_grid->working_hrs_per_week}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="public_holiday_per_year">Public Holiday Per Year (In Days)</label>
                            <input type="number" id="public_holiday_per_year" min="0"
                            required 
                            data-parsley-required-message="Please enter public holiday per year." 
                             name="public_holiday_per_year" class="form-control" value="{{$benefit_grid->public_holiday_per_year}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="overtime-select">Overtime</label>
                            <select id="overtime-select" name="overtime" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Overtime</option>
                                <option @if($benefit_grid->overtime == "yes") selected @endif value="yes">YES</option>
                                <option @if($benefit_grid->overtime == "n/a") selected @endif value="n/a">Not Applicable</option>
                            </select>
                        </div>
                    </div>
                    <!-- <div class="col-xxl-4  col-sm-6" id="holiday-rate-container" @if($benefit_grid->overtime == "n/a") style="display: none;" @endif>
                        <div class="form-group mb-2">
                            <label  class="form-label" for="annual_leave">Friday & Public Holiday Rate</label>
                            <input type="number" id="paid_worked_public_holiday_and_friday" min="0"
                            required 
                            data-parsley-required-message="Please enter Rate for friday & public holiday." 
                            name="paid_worked_public_holiday_and_friday" class="form-control" value="{{$benefit_grid->paid_worked_public_holiday_and_friday}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div> -->
                </div>
                
                {{-- <div class="col-auto">
                    <div class="d-flex justify-content-start align-items-center">
                        <h3>ADDITIONAL BENEFITS & ENTITLEMENTS</h3>
                    </div>
                </div>
                <hr> --}}
                <div class="card-title">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-auto">
                            <div class="d-flex justify-content-start align-items-center">
                                <h3>Additional Benefits & Entitlements</h3>
                            </div>
                        </div>
                        {{-- <div class="col-auto">
                            <div class="d-flex justify-content-sm-end align-items-center">
                                <button  id="addCustomLeave" class="btn btn-sm btn-theme">
                                    <i class="fa-solid fa-plus me-2"></i>Add More Leave
                                </button>
                            </div>
                        </div> --}}
                    </div>
                </div>
                <div class="row g-md-4 g-3 mb-4">
                    <!-- Existing Benefits -->
                    {{-- <div class="col-xxl-4  col-sm-6">
                       <div class="form-group mb-2">
                            <label  class="form-label" for="service_charge">Incentives Service Charge </label>
                            <input type="number" id="service_charge" min="0"
                            required 
                            data-parsley-required-message="Please enter incentives service charge." 
                            min="0"
                            name="service_charge" class="form-control" value="{{$benefit_grid->service_charge}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div> 
                    </div> --}}

                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="service_charge">Service Charge</label>
                            <select id="service_charge" name="service_charge"
                                    data-parsley-errors-container="#service-charge-error"
                                    required
                                    data-parsley-required-message="Please Select Service Charge"
                                    class="form-select select2t-none"
                                    @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="" disabled selected>Select Service Charge</option>
                                <option value="0" @if($benefit_grid->service_charge == "0") selected @endif>Eligible</option>
                                <option value="1" @if($benefit_grid->service_charge == "1") selected @endif>Not Eligible</option>
                            </select>

                            <div id="service-charge-error" class="text-danger mt-1"></div>
                              
                        </div>
                    </div>

                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="accommodation-status-select">Accommodation Type and Status</label>
                            <select id="accommodation-status-select" name="accommodation_status"
                                    data-parsley-errors-container="#accommodation-status-error"
                                    required
                                    data-parsley-required-message="Please Select Accommodation Type and Status"
                                    class="form-select select2t-none"
                                    @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="" disabled selected>Select Accommodation Status</option>
                                @if($accomodation_type)
                                    @foreach($accomodation_type as $type)
                                        <option value="{{ $type->AccommodationName }}" @if($benefit_grid->accommodation_status == $type->AccommodationName) selected @endif>{{ $type->AccommodationName }}</option>
                                    @endforeach
                                @endif
                            </select>

                            <div id="accommodation-status-error" class="text-danger mt-1"></div>
                              
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="furniture-and-fixtures-select">Furnitures and Fixtures</label>
                            <select id="furniture-and-fixtures-select" name="furniture_and_fixtures"
                            data-parsley-errors-container="#furniture_and_fixtures"
                            required
                            data-parsley-required-message="Please Select Accommodation Status"
                            class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Furnitures and Fixtures</option>
                                <option value="yes" @if($benefit_grid->furniture_and_fixtures == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->furniture_and_fixtures == "no") selected @endif>No</option>
                            </select>
                            <div id="furniture_and_fixtures" class="text-danger mt-1"></div>
                        </div>
                    </div>
                    {{-- <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="housekeeping">Housekeeping</label>
                            <input type="text" id="housekeeping" name="housekeeping" 
                            data-parsley-required-message="Please enter Housekeeping." 
                            min="0"
                            class="form-control" value="{{$benefit_grid->housekeeping}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div> --}}

                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="housekeeping">Housekeeping</label>
                            <select id="housekeeping" name="housekeeping"
                                    data-parsley-errors-container="#Housekeeping-status-error"
                                    required
                                    data-parsley-required-message="Please Select Housekeeping"
                                    class="form-select select2t-none"
                                    @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="" selected>Select Housekeeping</option>
                                <option value="once a week" @if($benefit_grid->housekeeping == "once a week") selected @endif>Once a week</option>
                                <option value="twice a week" @if($benefit_grid->housekeeping == "twice a week") selected @endif>Twice a week</option>
                                <option value="3 a week" @if($benefit_grid->housekeeping == "3 a week") selected @endif>3 a week</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="linen">Linen</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="linen1" class="form-check-input" name="linen[]" value="Bed sheet & pillow cover"
                                        @if(is_array($selected_linen_array) && in_array('Bed sheet & pillow cover', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <label class="form-check-label" for="linen1">Bed sheet & pillow cover</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="linen2" class="form-check-input" name="linen[]" value="Bath towel" @if(is_array($selected_linen_array) && in_array('Bath towel', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <label class="form-check-label" for="linen2">Bath towel</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="linen3" class="form-check-input" name="linen[]" value="Bath mat" @if(is_array($selected_linen_array) && in_array('Bath mat', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <label class="form-check-label" for="linen3">Bath mat</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="linen4" class="form-check-input" name="linen[]" value="Bedsheet" @if(is_array($selected_linen_array) && in_array('Bedsheet', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <label class="form-check-label" for="linen4">Bedsheet</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" id="linen5" class="form-check-input" name="linen[]" value="Blanket" @if(is_array($selected_linen_array) && in_array('Blanket', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <label class="form-check-label" for="linen5">Blanket</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="laundry-select">Laundry</label>
                            <select id="laundry-select" name="laundry[]" multiple class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Laundry Access</option>
                                <option value="once a week" @if($benefit_grid->laundry == "once a week") selected @endif>Once a week</option>
                                <option value="twice a week" @if($benefit_grid->laundry == "twice a week") selected @endif>Twice a week</option>
                                <option value="3 a week" @if($benefit_grid->laundry == "3 a week") selected @endif>3 a week</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="internet-access-select">Internet Access</label>
                            <select id="internet-access-select" name="internet_access" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Internet Access</option>
                                <option value="yes" @if($benefit_grid->internet_access == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->internet_access == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="telephone-select">Telephone</label>
                            <select id="telephone-select" name="telephone" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Telephone</option>
                                <option value="yes" @if($benefit_grid->telephone == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->telephone == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="loan-and-salary-advanced-select">Staff Loan & salary advance</label>
                            <select id="loan-and-salary-advanced-select" name="loan_and_salary_advanced" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Staff Loan & salary advance</option>
                                <option value="yes" @if($benefit_grid->loan_and_salary_advanced == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->loan_and_salary_advanced == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="uniform-select">Uniform</label>
                            <select id="uniform-select" name="uniform" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Uniform</option>
                                <option value="yes" @if($benefit_grid->uniform == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->uniform == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            {{-- <label  class="form-label" for="health_care_insurance">Health & Care Insurance</label> --}}
                            <label  class="form-label" for="health_care_insurance">Medical Insurance or Healthcare Insurance</label>
                            <select id="health_care_insurance" name="health_care_insurance" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Medical Insurance or Healthcare Insurance</option>
                                <option value="yes" @if($benefit_grid->health_care_insurance == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->health_care_insurance == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="relocation_ticket">Relocation Tickets to Maldives</label>
                            <select id="relocation_ticket" name="relocation_ticket" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Relocation Tickets to Maldives</option>
                                <option value="yes" @if($benefit_grid->relocation_ticket == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->relocation_ticket == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-8">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="max_excess_luggage_relocation_expense">Maximum Excess Luggage Relocation Allowance (In Dollars)</label>
                            <input min="0" type="number" id="max_excess_luggage_relocation_expense" name="max_excess_luggage_relocation_expense" class="form-control" value="{{$benefit_grid->max_excess_luggage_relocation_expense}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <!-- Custom Fields for Additional Benefits -->


                    <div class="col-sm-12 mt-3">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3> Custom Benefits</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <button type="button" type="button" id="add-custom-benefit" class="btn btn-sm btn-theme">
                                            <i class="fa-solid fa-plus me-2"></i>Add Another Benefit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- <h5> Custom Benefits:</h5> --}}
                        <div id="custom-benefits-container">
                            @if($custom_benefits)
                                @foreach($custom_benefits as $benefit)
                                    <div class="row custom-benefit mb-2">
                                        <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                                            <input type="text" name="custom_benefit_name[]" class="form-control" value="{{ $benefit->benefit_name }}" placeholder="Benefit Name" />
                                        </div>
                                        <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                                            <input type="text" name="custom_benefit_value[]" class="form-control" value="{{ $benefit->benefit_value }}" placeholder="Benefit Value" />
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-danger remove-benefit">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        {{-- <button type="button" id="add-custom-benefit" class="btn btn-primary">Add Another Benefit</button> --}}
                    </div>
                </div>
                {{-- <div class="col-auto">
                    <div class="d-flex justify-content-start align-items-center">
                        <h3>DISCOUNTS, CREDITS & ENTITLEMENTS</h3>
                    </div>
                </div>
                <hr> --}}
                <div class="card-title">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-auto">
                            <div class="d-flex justify-content-start align-items-center">
                                <h3> Discounts, Credits & Entitlements</h3>
                            </div>
                        </div>
                        {{-- <div class="col-auto">
                            <div class="d-flex justify-content-sm-end align-items-center">
                                <button type="button" id="add-custom-benefit" class="btn btn-sm btn-theme">
                                    <i class="fa-solid fa-plus me-2"></i>Add Another Benefit
                                </button>
                            </div>
                        </div> --}}
                    </div>
                </div>
                <div class="row g-md-4 g-3 mb-4">
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="paid_circumcision_leave_per_year">Meals Per Day</label>
                            <input type="number" min="0" id="meals_per_day" name="meals_per_day" class="form-control" value="{{$benefit_grid->meals_per_day}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="food_and_beverages_discount">Food And Beverages Discount(In %)</label>
                            <input type="number" min="0" id="food_and_beverages_discount" name="food_and_beverages_discount" class="form-control"  value="{{$benefit_grid->food_and_beverages_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="alchoholic_beverages_discount">Alchoholic Beverages Discount(In %)</label>
                            <input type="number" min="0" id="alchoholic_beverages_discount" name="alchoholic_beverages_discount" class="form-control" value="{{$benefit_grid->alchoholic_beverages_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="spa_discount">Spa Discount(In %)</label>
                            <input type="number" min="0" id="spa_discount" name="spa_discount" class="form-control" value="{{$benefit_grid->spa_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="dive_center_discount">Dive Center Discount(In %)</label>
                            <input type="number" min="0" id="dive_center_discount" name="dive_center_discount" class="form-control" value="{{$benefit_grid->dive_center_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="water_sports_discount">Water Sports Discount(In %)</label>
                            <input type="number" min="0" id="water_sports_discount" name="water_sports_discount" class="form-control" value="{{$benefit_grid->water_sports_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <!-- Custom Fields for Additional Discounts -->
                    <div class="card-title">
                        <div class="row g-3 align-items-center justify-content-between">
                            <div class="col-auto">
                                <div class="d-flex justify-content-start align-items-center">
                                    <h3>Custom Discounts</h3>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex justify-content-sm-end align-items-center">
                                    <button type="button" id="add-custom-discount" class="btn btn-sm btn-theme">
                                        <i class="fa-solid fa-plus me-2"></i>Add Another Discount
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 mt-3">
                       
                        <div id="custom-discount-container">
                            @if($custom_discounts)
                                @foreach($custom_discounts as $discount)
                                    <div class="row custom-discount mb-2">
                                        <div class="col-xxl-4  col-sm-6">
                                            <input type="text" name="custom_discount_name[]" class="form-control" value="{{ $discount->discount_name }}" placeholder="Discount Name" />
                                        </div>
                                        <div class="col-xxl-4  col-sm-6">
                                            <input type="text" name="custom_discount_value[]" class="form-control" value="{{ $discount->discount_rate }}" placeholder="Discount Value" />
                                        </div>
                                        <div class="col-xxl-4  col-sm-6">
                                            <button type="button" class="btn btn-danger remove-discount">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                       
                    </div>
                </div>
                <div class="card-title">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-auto">
                            <div class="d-flex justify-content-start align-items-center">
                                <h3>Sports, Recreation & Entertainment Facilities</h3>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                           

                            <input type="text"
                                id="custom_sport_input"
                                class="form-control sref-control"
                                placeholder="Add Custom Sport Name"
                                data-parsley-pattern="^[A-Za-z0-9,\.\'&quot;\-\!\?\s]{0,100}$"
                                data-parsley-pattern-message="Only letters, numbers, and symbols , . ' \" - ! ? are allowed."
                                data-parsley-maxlength="100"
                                data-parsley-trigger="keyup"
                                />

                            
                        </div>
                        <div class="col-auto">
                           

                                <button type="button" id="add-custom-sport" class="btn btn-sm btn-theme">
                                    <i class="fa-solid fa-plus me-2"></i>Add Custom Sport
                                </button>
                            
                        </div>


                    </div>
                </div>
              
                <div class="row g-md-4 g-3 mb-4">
                    <div class="col-sm-12">
                        <div id="custom-sports-container" class="sport-checkbox">
                            @foreach($sports as $key => $sport)
                            <div class="form-check form-check-inline">
                                <input type="checkbox" for="sport{{$key}}" class="form-check-input"
                                    name="sports_and_entertainment_facilities[]" value="{{$sport}}"
                                    @if(is_array($selected_sports) && in_array($sport, $selected_sports)) checked @endif
                                    @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <label class="form-check-label" for="sport{{$key}}">{{$sport}}</label>
                            </div>
                        @endforeach

                        <!-- Display custom sports -->
                        @foreach($selected_sports as $customSport)
                            @if(!in_array($customSport, $sports)) <!-- Only display if not predefined -->
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input" name="sports_and_entertainment_facilities[]"
                                        value="{{$customSport}}" checked>
                                    <label class="form-check-label">{{$customSport}}</label>
                                </div>
                            @endif
                        @endforeach
                        </div>

                       
                    </div>
                </div>
               

                <div class="card-title">
                    <div class="row g-3 align-items-center justify-content-between">
                        <div class="col-auto">
                            <div class="d-flex justify-content-start align-items-center">
                                <h3>Special Rates</h3>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex justify-content-sm-end align-items-center">
                                <button type="button" id="add-custom-benefit" class="btn btn-sm btn-theme">
                                    <i class="fa-solid fa-plus me-2"></i>Add Another Benefit
                                </button>
                            </div>
                        </div> 
                    </div>
                </div>
                <div class="row g-md-4 g-3  align-items-end">
                    <div class="col-xxl-8 ">
                        <label  class="form-label" for="standard_staff_rate">Staff Rate</label>
                        <div class="row g-md-4 g-3 ">
                            <div class="form-group mb-2 col-sm-6">
                                <label  class="form-label" for="standard_staff_rate_for_single">For Single(In Dollars)</label>
                                <input type="number" min="0" id="standard_staff_rate_for_single" name="standard_staff_rate_for_single" class="form-control" value="{{$benefit_grid->standard_staff_rate_for_single}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="form-group mb-2 col-sm-6">
                                <label  class="form-label" for="standard_staff_rate_for_double">For Double(In Dollars)</label>
                                <input type="number" min="0" id="standard_staff_rate_for_double" name="standard_staff_rate_for_double" class="form-control" value="{{$benefit_grid->standard_staff_rate_for_double}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="friends_with_benefit_discount">Friends With Benefit Discount(In %)</label>
                            <input type="number"  min="0" id="friends_with_benefit_discount" name="friends_with_benefit_discount" class="form-control" value="{{$benefit_grid->friends_with_benefit_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="staff_rate_for_seaplane_male">Staff rate (seaplane) to/from Male (In Dollars)</label>
                            <input type="number" min="0" id="staff_rate_for_seaplane_male" name="staff_rate_for_seaplane_male" class="form-control" value="{{$benefit_grid->staff_rate_for_seaplane_male}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" min="0" for="annual-leave-ticket-select">Annual Leave ticket to/from POH</label>
                            <select id="annual-leave-ticket-select" name="annual_leave_ticket" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Annual Leave ticket to/from POH</option>
                                <option value="yes" @if($benefit_grid->annual_leave_ticket == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->annual_leave_ticket == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="ticket-upon-termination-select">Ticket upon termination</label>
                            <select id="ticket-upon-termination-select" name="ticket_upon_termination" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Ticket upon termination</option>
                                <option value="yes" @if($benefit_grid->ticket_upon_termination == "yes") selected @endif>Yes</option>
                                <option value="no" @if($benefit_grid->ticket_upon_termination == "no") selected @endif>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="male_subsistence_allowance">MALE Subsistence Allowance(In Dollars)</label>
                            <input type="number" min="0" id="male_subsistence_allowance" name="male_subsistence_allowance" class="form-control" value="{{$benefit_grid->male_subsistence_allowance}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="free_return_flight_to_male_per_year">Free return flight to Male Per Year(In Number)</label>
                            <input type="number" min="0" id="free_return_flight_to_male_per_year" name="free_return_flight_to_male_per_year" class="form-control" value="{{$benefit_grid->free_return_flight_to_male_per_year}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div>
                    <div class="col-xxl-4  col-sm-6">
                        <div class="form-group mb-2">
                            <label  class="form-label" for="status-select">Status</label>
                            <select id="status-select" name="status" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <option value="">Select Status</option>
                                <option value="active" @if($benefit_grid->status == "active") selected @endif>Active</option>
                                <option value="inactive" @if($benefit_grid->status == "inactive") selected @endif>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>






                <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Custom Fields</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <button id="add-custom-field" type="button" class="btn btn-sm btn-theme">
                                            <i class="fa-solid fa-plus me-2"></i>Add Another Custom Field
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                <div class="col-sm-12 mt-3 mb-4">
                    {{-- <h5>Custom Fields:</h5> --}}
                    <div id="custom-fields-container">
                        @foreach($custom_fields as $key => $field)
                            <div class="row mb-2 g-md-4 g-3 ">
                                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                                    <input type="text" name="custom_field_names[]" class="form-control" value="{{ $field['name'] }}" placeholder="Field Name">
                                </div>
                                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                                    <input type="text" name="custom_field_values[]" class="form-control" value="{{ $field['value'] }}" placeholder="Field Value">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-danger remove-custom-field">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{-- <button type="button" id="add-custom-field" class="btn btn-primary">Add Another Custom Field</button> --}}
                </div>
                {{-- <hr> --}}
                <div class="modal-footer justify-content-end">
                    <a href="{{route('resort.benifitgrid.index')}}" type="button" class="btn btn-sm btn-themeGray me-2">Cancel</a>
                    @if($LeaveCategories->isNotEmpty())
                    <button type="submit" class="btn btn-sm btn-theme" @if(isset($isViewMode) && $isViewMode) disabled @endif>Submit</button>
                    @else
                    <button type="button" class="btn btn-sm btn-theme">Please Add Leave Categories in Leave Module (configation page)</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endSection

@section('import-css')

@endsection

@section('import-scripts')
    <script>
    $(document).ready(function(){
        $("#addBenifitGridForm").parsley();


        $("#effective_date").datepicker({
            dateFormat: 'dd-mm-yy'
        });
        var currentDate = new Date();
        $("#effective_date").datepicker("setDate",currentDate);
        var formSubmitted = false; // Flag to track form submission

        $('#emp-grade-select').on('change', function () {
            let empGrade = $(this).val(); // Get selected employee grade

            if (empGrade) {
                $.ajax({
                    url: '{{ route('leaves.getEligible') }}', // Your defined route
                    method: 'POST',
                    data: {
                        emp_grade: empGrade,
                        _token: '{{ csrf_token() }}' // Include CSRF token for security
                    },
                    success: function (response) {
                        if (response.success) {
                            let container = $('#Leave-categories');
                            container.empty(); // Clear existing content

                            response.data.forEach(function (leave, index) {
                                let leaveTypeId = leave.leave_type.replace(/ /g, '');

                                let html = `
                                    <div class="col-xxl-4 col-sm-6 mb-3">
                                        <div class="leave-category-group border rounded p-3">
                                            <h5 class="mb-3">${leave.leave_type}</h5>
                                            <div class="row">
                                                <div class="col-lg-6 form-group mb-2">
                                                    <label class="form-label" for="${leaveTypeId}">Number of Days</label>
                                                    <input type="number" min="0"
                                                        required 
                                                        id="${leaveTypeId}"
                                                        name="LeaveCat[${leave.id}][${leave.eligibility}][]"
                                                        class="form-control"
                                                        value="${leave.number_of_days}"
                                                        max="${leave.number_of_days}"
                                                        ${response.isViewMode ? 'disabled' : ''} />
                                                </div>
                                                <div class="col-lg-6 form-group mb-2">
                                                    <label class="form-label" for="eligible_emp_type_${index}">Eligible Employee Type</label>
                                                    <select name="eligible_emp_type[${leave.id}]"
                                                        id="eligible_emp_type_${index}"
                                                        class="form-select select2t-none"
                                                        ${response.isViewMode ? 'disabled' : ''}>
                                                        <option value="all" ${leave.eligible_emp_type === 'all' ? 'selected' : ''}>All Employees</option>
                                                        <option value="female" ${leave.eligible_emp_type === 'female' ? 'selected' : ''}>Females</option>
                                                        <option value="male" ${leave.eligible_emp_type === 'male' ? 'selected' : ''}>Males</option>
                                                        <option value="muslim" ${leave.eligible_emp_type === 'muslim' ? 'selected' : ''}>Muslims</option>
                                                    </select>
                                                    <div id="div-eligible_emp_type_${index}"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;

                                container.append(html);
                            });

                            // Optionally append Ramadan bonus block if needed again
                            let bonusHtml = `
                                <div class="col-xxl-4 col-sm-6 mb-3">
                                    <div class="leave-category-group border rounded p-3">
                                        <h5 class="mb-3">Ramadan Bonus</h5>
                                        <div class="row">
                                            <div class="col-sm-6 form-group mb-2">
                                                <label class="form-label" for="ramadan_bonus">Amount</label>
                                                <input type="number" min="0" id="ramadan_bonus" name="ramadan_bonus" class="form-control" value="${response.bonus_amount ?? 0}" ${response.isViewMode ? 'disabled' : ''} />
                                            </div>
                                            <div class="col-sm-6 form-group mb-2">
                                                <label class="form-label" for="ramadan_bonus_eligibility">Eligible Employee Type</label>
                                                <select name="ramadan_bonus_eligibility"
                                                    id="ramadan_bonus_eligibility"
                                                    class="form-select select2t-none"
                                                    ${response.isViewMode ? 'disabled' : ''}>
                                                    <option value="all" selected>All Employees</option>
                                                    <option value="all_muslim">All Muslims</option>
                                                    <option value="local_muslim">All Local Muslims</option>
                                                    <option value="all_local">All Local Employees</option>
                                                </select>
                                                <div id="div-ramadan_bonus_eligibility"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            container.append(bonusHtml);

                            $('.select2t-none').select2(); // Reinitialize select2
                        } else {
                            alert('No leave categories found.');
                        }
                    },

                    error: function (xhr) {
                        console.error(xhr.responseText);
                        alert('An error occurred while fetching eligible leaves.');
                    }
                });
            } else {
                // Clear the leave category dropdown if no grade is selected
                $('#leave-category-select').empty().append('<option value="">Select Leave Category</option>');
            }
        });

        $('#addBenifitGridForm').submit(function(e) {
            e.preventDefault();

            if (formSubmitted) return; // Prevent multiple form submissions

            var form = $(this);
            var dataString = form.serialize();
            var url = form.attr('action');

            // Initialize Parsley validation
            form.parsley();

            // Check if the form is valid
            if (form.parsley().isValid()) {
                formSubmitted = true;
                $.ajax({
                    url: url,
                    type: "POST",
                    data: $('#addBenifitGridForm').serialize(),
                    success: function(response) {
                        if(response.success == true) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            window.location.href = response.redirect_url;
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    complete: function() {
                        formSubmitted = false; // Reset flag after AJAX request completes
                    }
                });
            } else {
                // Trigger Parsley error messages if validation fails
                toastr.error("Please fix the validation errors before submitting.", "Validation Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });

         $('#overtime-select').on('change', function() {
            if ($(this).val() === 'n/a') {
                $('#holiday-rate-container').hide();
                $('#paid_worked_public_holiday_and_friday').removeAttr('required');
                $('#paid_worked_public_holiday_and_friday').val(0);
            } else {
                $('#holiday-rate-container').show();
                $('#paid_worked_public_holiday_and_friday').attr('required', 'required');
                if ($('#paid_worked_public_holiday_and_friday').val() == 0) {
                    $('#paid_worked_public_holiday_and_friday').val('');
                }
            }
        });
        
        // Trigger the change event on page load to set initial state
        $('#overtime-select').trigger('change');
    });
    </script>
    <script>
        let customLeaveIndex = 0; // To track the index of custom leaves
        document.getElementById('addCustomLeave').addEventListener('click', function() {
            customLeaveIndex++;
            const customLeaveHtml = `
                <div class="row custom-leave" id="custom-leave-${customLeaveIndex}">
                    <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                        <div class="form-group mb-2">
                            <input type="text" id="custom_leave_name_${customLeaveIndex}" name="custom_leave[${customLeaveIndex}][name]" class="form-control" required placeholder="Custom Leave Name"/>
                        </div>
                    </div>
                    <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                        <div class="form-group mb-2">
                            <input type="number" min="0" id="custom_leave_days_${customLeaveIndex}" name="custom_leave[${customLeaveIndex}][days]" class="form-control" required placeholder="Leave Days"/>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="form-group mb-2">
                            <button type="button" class="btn btn-danger removeCustomLeave" data-id="${customLeaveIndex}">Remove</button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('customLeaveContainer').insertAdjacentHTML('beforeend', customLeaveHtml);
        });
        // Remove custom leave field
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('removeCustomLeave')) {
                const customLeaveId = e.target.getAttribute('data-id');
                document.getElementById('custom-leave-' + customLeaveId).remove();
            }
        });
        document.getElementById('add-custom-benefit').addEventListener('click', function() {
            const container = document.getElementById('custom-benefits-container');
            const newBenefit = document.createElement('div');
            newBenefit.classList.add('row', 'custom-benefit', 'mb-2', 'g-md-4', 'g-3'); // Added classes here
            newBenefit.innerHTML = `
                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                    <input type="text" name="custom_benefit_name[]" class="form-control" placeholder="Benefit Name" />
                </div>
                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                    <input type="text" name="custom_benefit_value[]" class="form-control" placeholder="Benefit Value" />
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger remove-benefit">Remove</button>
                </div>`;
            container.appendChild(newBenefit);
        });
        document.getElementById('custom-benefits-container').addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-benefit')) {
                event.target.closest('.custom-benefit').remove();
            }
        });
        document.getElementById('add-custom-discount').addEventListener('click', function() {
            const container = document.getElementById('custom-discount-container');
            const newDiscount = document.createElement('div');
            newDiscount.classList.add('row', 'custom-discount', 'mb-2', 'g-md-4', 'g-3'); // Added classes here
            newDiscount.innerHTML = `
                <div class="col-xxl-4  col-sm-6">
                    <input type="text" name="custom_discount_name[]" class="form-control" placeholder="Discount Name" />
                </div>
                <div class="col-xxl-4  col-sm-6">
                    <input type="text" name="custom_discount_value[]" class="form-control" placeholder="Discount Value" />
                </div>
                <div class="col-xxl-4  col-sm-6">
                    <button type="button" class="btn btn-danger remove-discount">Remove</button>
                </div>`;
            container.appendChild(newDiscount);
        });
        document.getElementById('custom-discount-container').addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-discount')) {
                event.target.closest('.custom-discount').remove();
            }
        });
       document.getElementById('add-custom-sport').addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default anchor behavior if inside <a>

            const customSportInput = document.getElementById('custom_sport_input');
            const sportName = customSportInput.value.trim();
            // if (sportName.length > 20) {
            //     toastr.error("Sport name cannot exceed 20 characters.", "Error", {
            //         positionClass: 'toast-bottom-right'
            //     });
            //     return;
            // }

            // Define regex for allowed characters: letters, numbers, allowed symbols, and spaces
            const validPattern = /^[A-Za-z0-9,\.\'\"\-\!\?\s]{1,100}$/;

            if (!sportName) {
                toastr.error("Sport name cannot be empty.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            if (!validPattern.test(sportName)) {
                toastr.error("Only letters, numbers, and symbols , . ' \" - ! ? (max 100 characters) are allowed.", "Invalid Input", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Sanitize ID to avoid invalid characters in element IDs
            const safeId = sportName.replace(/[^a-zA-Z0-9_-]/g, "_");

            const newSportDiv = `
                <div class="SportsAddCheckbox">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" id="${safeId}" class="form-check-input" name="sports_and_entertainment_facilities[]" value="${sportName}" />
                        <label class="form-check-label" for="${safeId}">${sportName}</label>
                    </div>
                    <a href="#" class="btn-tableIcon btnIcon-danger remove-custom-sport">
                        <i class="fa-regular fa-trash-can"></i>
                    </a>
                </div>
            `;

            $("#custom-sports-container").append(newSportDiv);
            customSportInput.value = ''; // Clear input
        });

        // Remove custom sports when the remove button is clicked
        /* document.getElementById('custom-sports-container').addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-custom-sport')) {
                event.target.closest('.row').remove();
            }
        }); */

        // Fix for remove custom sports when the remove button is clicked
        $(document).on('click', '.remove-custom-sport', function(e) {
            e.preventDefault();
            $(this).closest('.SportsAddCheckbox').remove();
        });

        document.getElementById('add-custom-field').addEventListener('click', function() {
            const container = document.createElement('div');
            container.classList.add('row', 'mb-2','g-md-4','g-3');
           container.innerHTML = `
                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                    <input type="text" name="custom_field_names[]" class="form-control"
                        placeholder="Field Name"
                                            data-parsley-pattern="^[a-zA-Z0-9\s]+$"
                        data-parsley-pattern-message="Only letters, numbers, and spaces are allowed."

                    >
                </div>
                <div class="col-xxl-4 col-lg-5 col-md-4 col-sm-6">
                    <input type="text" name="custom_field_values[]" class="form-control"
                        placeholder="Field Value"
                        data-parsley-pattern="^[a-zA-Z0-9\s]+$"
                        data-parsley-pattern-message="Only letters, numbers, and spaces are allowed."
                    >
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger remove-custom-field">Remove</button>
                </div>
            `;

            document.getElementById('custom-fields-container').appendChild(container);
        });
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-custom-field')) {
                event.target.closest('.row').remove();
            }
        });
    </script>
@endsection
