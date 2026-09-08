@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #benifitgrid-edit-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #benifitgrid-edit-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="benifitgrid-edit-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Configuration</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.budget.config') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                    </a>
                </div>
            </div>
        </div>
        <div class="bg-scope">
            <form method="post" name="addBenifitGridForm" id="addBenifitGridForm" enctype="multipart/form-data" @if(empty($benefit_grid->id)) action="{{ route('resort.benifitgrid.store') }}" @else action="{{ route('resort.benifitgrid.update', $benefit_grid->id) }}" @endif data-parsley-validate>
                @csrf
                <div class="bg-layout">
                    <nav class="bg-snav" id="bgSnav">
                        <a href="#bg-s-general" class="active"><span class="bg-n">01</span>General Information</a>
                        <a href="#bg-s-leave"><span class="bg-n">02</span>Leave &amp; Holiday</a>
                        <a href="#bg-s-schedule"><span class="bg-n">03</span>Work Schedule</a>
                        <a href="#bg-s-benefits"><span class="bg-n">04</span>Benefits &amp; Entitlements</a>
                        <a href="#bg-s-discounts"><span class="bg-n">05</span>Discounts &amp; Credits</a>
                        <a href="#bg-s-sports"><span class="bg-n">06</span>Sports &amp; Recreation</a>
                        <a href="#bg-s-special"><span class="bg-n">07</span>Special Rates</a>
                    </nav>

                    <div class="bg-forms">

                    <!-- 1. General Information -->
                    <section class="bg-card" id="bg-s-general">
                        <div class="bg-sec-h"><div class="bg-sec-t">General Information</div></div>
                        <div class="bg-fgrid">
                            <div class="bg-f">
                                <label for="emp-grade-select">Employee Grade<span class="bg-req">*</span></label>
                                <input type="text" id="emp-grade-select" class="bg-inp" name="emp_grade"
                                value="{{ $currentGradeName ?? '' }}"
                                placeholder="e.g. HOD L1"
                                data-parsley-errors-container="#div-emp_grade"
                                required
                                data-parsley-required-message="Please enter an Employee Grade"
                                @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                <div id="div-emp_grade"></div>
                                <span class="bg-help">Type a new grade name (e.g. "HOD L1") or the name of an existing one.</span>
                            </div>
                            <div class="bg-f">
                                <label for="grade-ranks-select">Applies to Rank(s)<span class="bg-req">*</span></label>
                                <select id="grade-ranks-select" name="ranks[]" multiple class="form-select select2t-none"
                                data-parsley-errors-container="#div-ranks"
                                required
                                data-parsley-required-message="Please select at least one rank"
                                @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    @foreach($rankConfig ?? [] as $rankValue => $rankLabel)
                                        @php $rankUsage = ($rankPositionSummary ?? collect())->get((int) $rankValue); @endphp
                                        <option value="{{ $rankValue }}"
                                            @if(in_array($rankValue, $currentGradeRanks ?? [])) selected @endif
                                            @if($rankUsage) title="{{ $rankUsage['names'] }}" @endif>{{ $rankLabel }}@if($rankUsage) ({{ $rankUsage['count'] }} position{{ $rankUsage['count'] == 1 ? '' : 's' }})@endif</option>
                                    @endforeach
                                </select>
                                <div id="div-ranks"></div>
                                <span class="bg-help">A rank can belong to more than one grade. When it does, an employee's own assigned grade (if set) wins; otherwise the oldest grade this rank was assigned to applies by default.</span>
                            </div>
                            <div class="bg-f">
                                <label for="salary-period-select">Salary Period<span class="bg-req">*</span></label>
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
                            <div class="bg-f">
                                <label for="salary-paidin-select">Salary Paid In<span class="bg-req">*</span></label>
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
                            <div class="bg-f">
                                <label for="contract-status-select">Contract Status<span class="bg-req">*</span></label>
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
                            <div class="bg-f">
                                <label for="effective_date">Effective Date</label>
                                <input type="text"
                                id="effective_date"
                                name="effective_date"
                                class="bg-inp"
                                required
                                data-parsley-required-message="Please select effective date."
                                value="{{ $benefit_grid->effective_date }}"
                                @if(isset($isViewMode) && $isViewMode) disabled @endif />
                            </div>
                        </div>
                    </section>

                    <!-- 2. Leave & Holiday -->
                    <section class="bg-card" id="bg-s-leave">
                        <div class="bg-sec-h"><div class="bg-sec-t">Leave and Holiday Policy</div></div>
                        <table class="bg-ltbl">
                            <thead><tr><th>Leave Type</th><th class="bg-c-mid">Number of Days</th><th class="bg-c-elig">Eligible Employee Type</th></tr></thead>
                            <tbody id="Leave-categories">
                                @if($LeaveCategories)
                                    @foreach($LeaveCategories as $key => $leave)
                                    @php
                                        $allocatedDays = $benefitGridChildMap[$leave->id]->allocated_days ?? 0;
                                        $eligibleEmp = $benefitGridChildMap[$leave->id]->eligible_emp_type ?? 0;
                                    @endphp
                                    <tr>
                                        <td><span class="bg-lt">{{ $leave->leave_type }}</span></td>
                                        <td class="bg-c-mid">
                                            <input type="number" min="0" step="any"
                                                required
                                                data-parsley-required-message="Please enter number of days."
                                                id="{{str_replace(' ', '', $leave->leave_type)}}"
                                                name="LeaveCat[{{$leave->id}}][{{$leave->eligibility}}][]"
                                                class="bg-inp bg-sm" value="{{ $allocatedDays }}"
                                                @if(isset($isViewMode) && $isViewMode) disabled @endif
                                                required/>
                                        </td>
                                        <td class="bg-c-elig">
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
                                        </td>
                                    </tr>
                                    @endforeach
                                @endif
                                <tr>
                                    <td><span class="bg-lt">Ramadan Bonus</span><span class="bg-amt">· amount</span></td>
                                    <td class="bg-c-mid">
                                        <input type="number" min="0" step="any" id="ramadan_bonus" name="ramadan_bonus" class="bg-inp bg-sm" value="{{$benefit_grid->ramadan_bonus}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                                    </td>
                                    <td class="bg-c-elig">
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
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                {{--
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
                                        <button type="button"  id="addCustomLeave" class="btn btn-sm wfp-btn-positive">
                                            <i class="fa-solid fa-plus me-2"></i>Add More Leave
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="customLeaveContainer"></div>
                    </div>
                </div>

                @if($custom_leave)
                    @foreach($custom_leave as $leave)
                        <div class="row">
                            <div class="col-sm-4">
                                <label>{{ $leave->leave_name }}</label>
                                <input type="number" min="0" step="any" name="custom_leave[{{ $loop->index }}][days]" class="form-control" value="{{ $leave->leave_days }}" />
                            </div>
                        </div>
                    @endforeach
                @endif
                --}}
                
                    <!-- 3. Work Schedule -->
                    <section class="bg-card" id="bg-s-schedule">
                        <div class="bg-sec-h"><div class="bg-sec-t">Work Schedule</div></div>
                        <div class="bg-fgrid bg-c3">
                            <div class="bg-f">
                                <label for="day_off_per_week">Day Off Per Week (In Days)</label>
                                <input type="number" min="0" step="any" id="day_off_per_week" name="day_off_per_week"
                                required
                                data-parsley-required-message="Please enter Day off per week."
                                 class="bg-inp" value="{{$benefit_grid->day_off_per_week}}" @if(isset($isViewMode) && $isViewMode) disabled @endif data-parsley-required="true"
                                data-parsley-min="1" />
                            </div>
                            <div class="bg-f">
                                <label for="working_hrs_per_week">Working Hours per week (In Hours)</label>
                                <input type="number" min="0" step="any"
                                required
                                data-parsley-required-message="Please enter Working hours per week."
                                 id="working_hrs_per_week" name="working_hrs_per_week" class="bg-inp" value="{{$benefit_grid->working_hrs_per_week}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            {{-- Public Holiday Per Year field hidden temporarily --}}
                            {{-- <div class="col-xxl-4  col-sm-6">
                                <div class="form-group mb-2">
                                    <label  class="form-label" for="public_holiday_per_year">Public Holiday Per Year (In Days)</label>
                                    <input type="number" id="public_holiday_per_year" min="0" step="any"
                                    required
                                    data-parsley-required-message="Please enter public holiday per year."
                                     name="public_holiday_per_year" class="form-control" value="{{$benefit_grid->public_holiday_per_year}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                                </div>
                            </div> --}}
                            <div class="bg-f">
                                <label for="overtime-select">Overtime</label>
                                <select id="overtime-select" name="overtime" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Overtime</option>
                                    <option @if($benefit_grid->overtime == "yes") selected @endif value="yes">YES</option>
                                    <option @if($benefit_grid->overtime == "n/a") selected @endif value="n/a">Not Applicable</option>
                                </select>
                            </div>
                            <!-- <div class="col-xxl-4  col-sm-6" id="holiday-rate-container" @if($benefit_grid->overtime == "n/a") style="display: none;" @endif>
                                <div class="form-group mb-2">
                                    <label  class="form-label" for="annual_leave">Friday & Public Holiday Rate</label>
                                    <input type="number" id="paid_worked_public_holiday_and_friday" min="0" step="any"
                                    required
                                    data-parsley-required-message="Please enter Rate for friday & public holiday."
                                    name="paid_worked_public_holiday_and_friday" class="form-control" value="{{$benefit_grid->paid_worked_public_holiday_and_friday}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                                </div>
                            </div> -->
                        </div>
                    </section>

                    <!-- 4. Additional Benefits & Entitlements -->
                    <section class="bg-card" id="bg-s-benefits">
                        <div class="bg-sec-h"><div class="bg-sec-t">Additional Benefits &amp; Entitlements</div></div>
                        <div class="bg-fgrid bg-c3">
                    {{-- <div class="col-xxl-4  col-sm-6">
                       <div class="form-group mb-2">
                            <label  class="form-label" for="service_charge">Incentives Service Charge </label>
                            <input type="number" id="service_charge" min="0" step="any"
                            required
                            data-parsley-required-message="Please enter incentives service charge."
                            min="0"
                            name="service_charge" class="form-control" value="{{$benefit_grid->service_charge}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                        </div>
                    </div> --}}

                            <div class="bg-f">
                                <label for="service_charge">Service Charge</label>
                                <select id="service_charge" name="service_charge"
                                        data-parsley-errors-container="#service-charge-error"
                                        required
                                        data-parsley-required-message="Please Select Service Charge"
                                        class="form-select select2t-none"
                                        @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="" disabled selected>Select Service Charge</option>
                                    {{-- Labels were inverted vs. every consumer (EmployeeController,
                                         PayrollController, pdf.blade.php), which all correctly treat
                                         service_charge == 1 as eligible. Fixed the labels to match what's
                                         actually stored/consumed — values are unchanged, so existing rows
                                         now display their TRUE current effective eligibility instead of
                                         the label lying about it. --}}
                                    <option value="1" @if($benefit_grid->service_charge == "1") selected @endif>Eligible</option>
                                    <option value="0" @if($benefit_grid->service_charge == "0") selected @endif>Not Eligible</option>
                                </select>
                                <div id="service-charge-error" class="text-danger mt-1"></div>
                            </div>

                            <div class="bg-f">
                                <label for="accommodation-status-select">Accommodation Type and Status</label>
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
                            <div class="bg-f">
                                <label for="furniture-and-fixtures-select">Furniture and Fixtures</label>
                                <select id="furniture-and-fixtures-select" name="furniture_and_fixtures"
                                data-parsley-errors-container="#furniture_and_fixtures"
                                required
                                data-parsley-required-message="Please Select Accommodation Status"
                                class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Furniture and Fixtures</option>
                                    <option value="yes" @if($benefit_grid->furniture_and_fixtures == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->furniture_and_fixtures == "no") selected @endif>No</option>
                                </select>
                                <div id="furniture_and_fixtures" class="text-danger mt-1"></div>
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

                            <div class="bg-f">
                                <label for="housekeeping">Housekeeping</label>
                                <select id="housekeeping" name="housekeeping"
                                        data-parsley-errors-container="#Housekeeping-status-error"
                                        required
                                        data-parsley-required-message="Please Select Housekeeping"
                                        class="form-select select2t-none"
                                        @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="" selected>Select Housekeeping</option>
                                    <option value="once a week" @if($benefit_grid->housekeeping == "once a week") selected @endif>Once a week</option>
                                    <option value="twice a week" @if($benefit_grid->housekeeping == "twice a week") selected @endif>Twice a week</option>
                                    <option value="thrice a week" @if(in_array($benefit_grid->housekeeping, ['thrice a week', '3 a week'])) selected @endif>Thrice a week</option>
                                    <option value="not eligible" @if($benefit_grid->housekeeping == "not eligible") selected @endif>Not Eligible</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="laundry-select">Laundry</label>
                                <select id="laundry-select" name="laundry[]" multiple class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Laundry Access</option>
                                    <option value="once a week" @if($benefit_grid->laundry == "once a week") selected @endif>Once a week</option>
                                    <option value="twice a week" @if($benefit_grid->laundry == "twice a week") selected @endif>Twice a week</option>
                                    <option value="thrice a week" @if(in_array($benefit_grid->laundry, ['thrice a week', '3 a week'])) selected @endif>Thrice a week</option>
                                    <option value="not eligible" @if($benefit_grid->laundry == "not eligible") selected @endif>Not Eligible</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="internet-access-select">Internet Access</label>
                                <select id="internet-access-select" name="internet_access" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Internet Access</option>
                                    <option value="yes" @if($benefit_grid->internet_access == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->internet_access == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="telephone-select">Telephone</label>
                                <select id="telephone-select" name="telephone" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Telephone</option>
                                    <option value="yes" @if($benefit_grid->telephone == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->telephone == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="loan-and-salary-advanced-select">Staff Loan &amp; salary advance</label>
                                <select id="loan-and-salary-advanced-select" name="loan_and_salary_advanced" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Staff Loan & salary advance</option>
                                    <option value="yes" @if($benefit_grid->loan_and_salary_advanced == "yes") selected @endif>Yes</option>
                                    {{-- Column is enum('yes','n/a') — "no" isn't a valid member, so
                                         selecting it silently coerced to '' on save (same bug shape
                                         as the Overtime field, which already uses n/a correctly). --}}
                                    <option value="n/a" @if($benefit_grid->loan_and_salary_advanced == "n/a") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="uniform-select">Uniform</label>
                                <select id="uniform-select" name="uniform" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Uniform</option>
                                    <option value="yes" @if($benefit_grid->uniform == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->uniform == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                {{-- <label  class="form-label" for="health_care_insurance">Health & Care Insurance</label> --}}
                                <label for="health_care_insurance">Medical Insurance or Healthcare Insurance</label>
                                <select id="health_care_insurance" name="health_care_insurance" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Medical Insurance or Healthcare Insurance</option>
                                    <option value="yes" @if($benefit_grid->health_care_insurance == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->health_care_insurance == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="relocation_ticket">Relocation Tickets</label>
                                <select id="relocation_ticket" name="relocation_ticket" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Relocation Tickets</option>
                                    <option value="yes" @if($benefit_grid->relocation_ticket == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->relocation_ticket == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="max_excess_luggage_relocation_expense">Maximum Excess Luggage Relocation Allowance (In Dollars)</label>
                                <input min="0" step="any" type="number" id="max_excess_luggage_relocation_expense" name="max_excess_luggage_relocation_expense" class="bg-inp" value="{{$benefit_grid->max_excess_luggage_relocation_expense}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                        </div>

                        <div class="bg-subblock">
                            <label class="bg-blocklbl2">Linen</label>
                            <div class="bg-checkgrid">
                                <label class="bg-check"><input type="checkbox" id="linen1" name="linen[]" value="Bed sheet & pillow cover"
                                    @if(is_array($selected_linen_array) && in_array('Bed sheet & pillow cover', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>Bed sheet &amp; pillow cover</label>
                                <label class="bg-check"><input type="checkbox" id="linen5" name="linen[]" value="Blanket" @if(is_array($selected_linen_array) && in_array('Blanket', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>Blanket</label>
                                <label class="bg-check"><input type="checkbox" id="linen2" name="linen[]" value="Bath towel" @if(is_array($selected_linen_array) && in_array('Bath towel', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>Bath towel</label>
                                <label class="bg-check"><input type="checkbox" id="linen3" name="linen[]" value="Bath mat" @if(is_array($selected_linen_array) && in_array('Bath mat', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>Bath mat</label>
                                <label class="bg-check"><input type="checkbox" id="linen4" name="linen[]" value="Bedsheet" @if(is_array($selected_linen_array) && in_array('Bedsheet', $selected_linen_array)) checked @endif @if(isset($isViewMode) && $isViewMode) disabled @endif>Bedsheet</label>
                            </div>
                        </div>

                        <div class="bg-customhdr">
                            <div class="bg-blocklbl">Custom Benefits</div>
                            <button type="button" id="add-custom-benefit" class="bg-addbtn2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>Add Another Benefit
                            </button>
                        </div>
                        <div class="bg-addwrap" id="custom-benefits-container">
                            @if($custom_benefits)
                                @foreach($custom_benefits as $benefit)
                                    <div class="bg-addedrow custom-benefit">
                                        <input type="text" name="custom_benefit_name[]" class="bg-inp" value="{{ $benefit->benefit_name }}" placeholder="Benefit Name" />
                                        <input type="text" name="custom_benefit_value[]" class="bg-inp" value="{{ $benefit->benefit_value }}" placeholder="Benefit Value" />
                                        <button type="button" class="bg-rmbtn remove-benefit" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <!-- 5. Discounts, Credits & Entitlements -->
                    <section class="bg-card" id="bg-s-discounts">
                        <div class="bg-sec-h"><div class="bg-sec-t">Discounts, Credits &amp; Entitlements</div></div>
                        <div class="bg-fgrid bg-c3">
                            <div class="bg-f">
                                <label for="meals_per_day">Meals Per Day</label>
                                <input type="number" min="0" step="any" id="meals_per_day" name="meals_per_day" class="bg-inp" value="{{$benefit_grid->meals_per_day}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="food_and_beverages_discount">Food And Beverages Discount(In %)</label>
                                <input type="number" min="0" step="any" id="food_and_beverages_discount" name="food_and_beverages_discount" class="bg-inp"  value="{{$benefit_grid->food_and_beverages_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="alchoholic_beverages_discount">Alcoholic Beverages Discount(In %)</label>
                                <input type="number" min="0" step="any" id="alchoholic_beverages_discount" name="alchoholic_beverages_discount" class="bg-inp" value="{{$benefit_grid->alchoholic_beverages_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="spa_discount">Spa Discount(In %)</label>
                                <input type="number" min="0" step="any" id="spa_discount" name="spa_discount" class="bg-inp" value="{{$benefit_grid->spa_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="dive_center_discount">Dive Center Discount(In %)</label>
                                <input type="number" min="0" step="any" id="dive_center_discount" name="dive_center_discount" class="bg-inp" value="{{$benefit_grid->dive_center_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="water_sports_discount">Water Sports Discount(In %)</label>
                                <input type="number" min="0" step="any" id="water_sports_discount" name="water_sports_discount" class="bg-inp" value="{{$benefit_grid->water_sports_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                        </div>
                        <div class="bg-customhdr">
                            <div class="bg-blocklbl">Custom Discounts</div>
                            <button type="button" id="add-custom-discount" class="bg-addbtn2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>Add Another Discount
                            </button>
                        </div>
                        <div class="bg-addwrap" id="custom-discount-container">
                            @if($custom_discounts)
                                @foreach($custom_discounts as $discount)
                                    <div class="bg-addedrow custom-discount">
                                        <input type="text" name="custom_discount_name[]" class="bg-inp" value="{{ $discount->discount_name }}" placeholder="Discount Name" />
                                        <input type="text" name="custom_discount_value[]" class="bg-inp" value="{{ $discount->discount_rate }}" placeholder="Discount Value" />
                                        <button type="button" class="bg-rmbtn remove-discount" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <!-- 6. Sports, Recreation & Entertainment Facilities -->
                    <section class="bg-card" id="bg-s-sports">
                        <div class="bg-sec-h bg-rowh">
                            <div class="bg-sec-t">Sports, Recreation &amp; Entertainment Facilities</div>
                            <div class="bg-addsport">
                                <input type="text"
                                    id="custom_sport_input"
                                    class="bg-inp"
                                    placeholder="Add Custom Sport"
                                    data-parsley-pattern="^[A-Za-z0-9,\.\'&quot;\-\!\?\s]{0,100}$"
                                    data-parsley-pattern-message="Only letters, numbers, and symbols , . ' \" - ! ? are allowed."
                                    data-parsley-maxlength="100"
                                    data-parsley-trigger="keyup"
                                    />
                                <button type="button" id="add-custom-sport" class="bg-addbtn2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>Add Custom Sport
                                </button>
                            </div>
                        </div>
                        <div id="custom-sports-container" class="bg-checkgrid">
                            @foreach($sports as $key => $sport)
                            <label class="bg-check">
                                <input type="checkbox" for="sport{{$key}}"
                                    name="sports_and_entertainment_facilities[]" value="{{$sport}}"
                                    @if(is_array($selected_sports) && in_array($sport, $selected_sports)) checked @endif
                                    @if(isset($isViewMode) && $isViewMode) disabled @endif>{{$sport}}
                            </label>
                        @endforeach

                        {{-- Display custom sports (already-saved ones, edit mode only) --}}
                        @foreach($selected_sports as $customSport)
                            @if(!in_array($customSport, $sports))
                                <div class="SportsAddCheckbox">
                                    <label class="bg-check">
                                        <input type="checkbox" name="sports_and_entertainment_facilities[]"
                                            value="{{$customSport}}" checked>{{$customSport}}
                                    </label>
                                </div>
                            @endif
                        @endforeach
                        </div>
                    </section>

                    <!-- 7. Special Rates -->
                    <section class="bg-card" id="bg-s-special">
                        <div class="bg-sec-h bg-rowh">
                            <div class="bg-sec-t">Special Rates</div>
                            {{-- Pre-existing: this button shares id="add-custom-benefit" with the
                                 Custom Benefits button in section 4, so getElementById() has always
                                 bound the click handler to that one — this button has never had a
                                 working handler, and there's no results container for it either.
                                 Kept visually as-is (own id now, for valid markup), still inert —
                                 not wiring new behavior into a payroll form without sign-off. Flagged
                                 to the user in the task's final report. --}}
                            <button type="button" id="add-special-rate-benefit" class="bg-addbtn2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>Add Another Benefit
                            </button>
                        </div>
                        <div class="bg-fgrid bg-c3">
                            <div class="bg-f">
                                <label for="standard_staff_rate_for_single">Staff Rate - For Single (In Dollars)</label>
                                <input type="number" min="0" step="any" id="standard_staff_rate_for_single" name="standard_staff_rate_for_single" class="bg-inp" value="{{$benefit_grid->standard_staff_rate_for_single}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="standard_staff_rate_for_double">Staff Rate - For Double (In Dollars)</label>
                                <input type="number" min="0" step="any" id="standard_staff_rate_for_double" name="standard_staff_rate_for_double" class="bg-inp" value="{{$benefit_grid->standard_staff_rate_for_double}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="friends_with_benefit_discount">Friends With Benefit Discount(In %)</label>
                                <input type="number" min="0" step="any" id="friends_with_benefit_discount" name="friends_with_benefit_discount" class="bg-inp" value="{{$benefit_grid->friends_with_benefit_discount}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="staff_rate_for_seaplane_male">Staff rate (seaplane) to/from Male (In Dollars)</label>
                                <input type="number" min="0" step="any" id="staff_rate_for_seaplane_male" name="staff_rate_for_seaplane_male" class="bg-inp" value="{{$benefit_grid->staff_rate_for_seaplane_male}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="annual-leave-ticket-select">Annual Leave ticket to/from POH</label>
                                <select id="annual-leave-ticket-select" name="annual_leave_ticket" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Annual Leave ticket to/from POH</option>
                                    <option value="yes" @if($benefit_grid->annual_leave_ticket == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->annual_leave_ticket == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="ticket-upon-termination-select">Ticket upon termination</label>
                                <select id="ticket-upon-termination-select" name="ticket_upon_termination" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Ticket upon termination</option>
                                    <option value="yes" @if($benefit_grid->ticket_upon_termination == "yes") selected @endif>Yes</option>
                                    <option value="no" @if($benefit_grid->ticket_upon_termination == "no") selected @endif>No</option>
                                </select>
                            </div>
                            <div class="bg-f">
                                <label for="male_subsistence_allowance">MALE Subsistence Allowance(In Dollars)</label>
                                <input type="number" min="0" step="any" id="male_subsistence_allowance" name="male_subsistence_allowance" class="bg-inp" value="{{$benefit_grid->male_subsistence_allowance}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="free_return_flight_to_male_per_year">Free return flight to Male Per Year(In Number)</label>
                                <input type="number" min="0" step="any" id="free_return_flight_to_male_per_year" name="free_return_flight_to_male_per_year" class="bg-inp" value="{{$benefit_grid->free_return_flight_to_male_per_year}}" @if(isset($isViewMode) && $isViewMode) disabled @endif/>
                            </div>
                            <div class="bg-f">
                                <label for="status-select">Status</label>
                                <select id="status-select" name="status" class="form-select select2t-none" @if(isset($isViewMode) && $isViewMode) disabled @endif>
                                    <option value="">Select Status</option>
                                    <option value="active" @if($benefit_grid->status == "active") selected @endif>Active</option>
                                    <option value="inactive" @if($benefit_grid->status == "inactive") selected @endif>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-customhdr">
                            <div class="bg-blocklbl">Custom Fields</div>
                            <button id="add-custom-field" type="button" class="bg-addbtn2">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>Add Another Custom Field
                            </button>
                        </div>
                        <div class="bg-addwrap" id="custom-fields-container">
                            @foreach($custom_fields as $key => $field)
                                <div class="bg-addedrow">
                                    <input type="text" name="custom_field_names[]" class="bg-inp" value="{{ $field['name'] }}" placeholder="Field Name">
                                    <input type="text" name="custom_field_values[]" class="bg-inp" value="{{ $field['value'] }}" placeholder="Field Value">
                                    <button type="button" class="bg-rmbtn remove-custom-field" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                            @endforeach
                        </div>

                        {{-- Cancel/Submit live inside the Special Rates card
                             itself (not a separate floating bar below it) —
                             same white surface, no second box. --}}
                        <div class="bg-actionbar">
                            <a href="{{route('resort.benifitgrid.index')}}" class="btn btn-sm wfp-btn-secondary">Cancel</a>
                            @if($LeaveCategories->isNotEmpty())
                                <button type="submit" class="btn btn-sm wfp-btn-primary" @if(isset($isViewMode) && $isViewMode) disabled @endif>Submit</button>
                            @else
                                <button type="button" class="btn btn-sm wfp-btn-primary" disabled>Please add leave categories in the Leave module's configuration page first</button>
                            @endif
                        </div>
                    </section>

                    {{-- Sentinel the bottom-of-page nav-highlight observer
                         watches — see import-scripts. The band-based
                         observer alone never marks the LAST section active,
                         since its top never re-enters the narrow trigger
                         band once the page runs out of room to scroll. --}}
                    <div id="bg-scroll-sentinel" aria-hidden="true" style="height:1px;"></div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endSection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@include('resorts.benifitgrid._benefit_grid_form_styles')
@endsection

@section('import-scripts')
    <script>
    $(document).ready(function(){
        $("#addBenifitGridForm").parsley();

        // Sticky section-nav active-on-scroll highlight
        (function () {
            var navLinks = document.querySelectorAll('#bgSnav a');
            var sections = document.querySelectorAll('.bg-card[id]');
            if (!navLinks.length || !sections.length || !('IntersectionObserver' in window)) return;

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    navLinks.forEach(function (link) {
                        link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
                    });
                });
            }, { rootMargin: '-15% 0px -70% 0px', threshold: 0 });

            sections.forEach(function (section) { observer.observe(section); });

            // The band-based observer above never marks the LAST section
            // active — there isn't enough page left to scroll for its top
            // to re-enter that narrow trigger band before scrolling maxes
            // out, so the highlight gets stuck on the second-to-last
            // section no matter how far down you go. A sentinel right at
            // the true end of the form, watched with a plain (unshrunk)
            // observer, catches "user has reached the bottom" directly and
            // forces the last link active.
            var sentinel = document.getElementById('bg-scroll-sentinel');
            if (sentinel) {
                var lastLink = navLinks[navLinks.length - 1];
                var bottomObserver = new IntersectionObserver(function (entries) {
                    if (entries[0].isIntersecting) {
                        navLinks.forEach(function (link) { link.classList.toggle('active', link === lastLink); });
                    }
                }, { threshold: 0 });
                bottomObserver.observe(sentinel);
            }
        })();

        var effective_date_fp = flatpickr('#effective_date', {
            dateFormat: 'm/d/Y',
            allowInput: true,
            appendTo: document.body
        });
        var currentDate = new Date();
        effective_date_fp.setDate(currentDate, true);
        var formSubmitted = false; // Flag to track form submission

        function fetchEligibleLeaves(payload) {
                $.ajax({
                    url: '{{ route('leaves.getEligible') }}', // Your defined route
                    method: 'POST',
                    data: Object.assign({ _token: '{{ csrf_token() }}' }, payload),
                    success: function (response) {
                        if (response.success) {
                            let container = $('#Leave-categories');
                            container.empty(); // Clear existing content

                            response.data.forEach(function (leave, index) {
                                let leaveTypeId = leave.leave_type.replace(/ /g, '');

                                let html = `
                                    <tr>
                                        <td><span class="bg-lt">${leave.leave_type}</span></td>
                                        <td class="bg-c-mid">
                                            <input type="number" min="0" step="any"
                                                required
                                                id="${leaveTypeId}"
                                                name="LeaveCat[${leave.id}][${leave.eligibility}][]"
                                                class="bg-inp bg-sm"
                                                value="${leave.number_of_days}"
                                                ${response.isViewMode ? 'disabled' : ''} />
                                        </td>
                                        <td class="bg-c-elig">
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
                                        </td>
                                    </tr>
                                `;

                                container.append(html);
                            });

                            // Optionally append Ramadan bonus row if needed again
                            let bonusHtml = `
                                <tr>
                                    <td><span class="bg-lt">Ramadan Bonus</span><span class="bg-amt">· amount</span></td>
                                    <td class="bg-c-mid">
                                        <input type="number" min="0" step="any" id="ramadan_bonus" name="ramadan_bonus" class="bg-inp bg-sm" value="${response.bonus_amount ?? 0}" ${response.isViewMode ? 'disabled' : ''} />
                                    </td>
                                    <td class="bg-c-elig">
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
                                    </td>
                                </tr>
                            `;
                            container.append(bonusHtml);

                            $('.select2t-none').select2();
                        } else {
                            alert('No leave categories found.');
                        }
                    },

                    error: function (xhr) {
                        console.error(xhr.responseText);
                        alert('An error occurred while fetching eligible leaves.');
                    }
                });
        }

        function clearEligibleLeaves() {
            $('#Leave-categories').empty();
            $('#leave-category-select').empty().append('<option value="">Select Leave Category</option>');
        }

        $('#emp-grade-select').on('change', function () {
            let empGrade = $(this).val();
            if (empGrade) {
                fetchEligibleLeaves({ emp_grade: empGrade });
            } else {
                clearEligibleLeaves();
            }
        });

        // Leave eligibility is keyed by RANK (leave_categories.eligibility),
        // not the grade name — the rank multi-select is what should drive
        // this preview. Split out of the grade text field when "Applies to
        // Rank(s)" was added; this listener was missing entirely, so
        // picking a rank here never refreshed the Leave and Holiday Policy
        // section.
        $('#grade-ranks-select').on('change', function () {
            let ranks = $(this).val(); // array of selected rank values, or null
            if (ranks && ranks.length) {
                fetchEligibleLeaves({ ranks: ranks });
            } else {
                clearEligibleLeaves();
            }
        });

        function submitBenifitGridForm() {
            var form = $('#addBenifitGridForm');
            var url = form.attr('action');

            form.parsley();

            if (form.parsley().isValid()) {
                formSubmitted = true;
                $.ajax({
                    url: url,
                    type: "POST",
                    data: form.serialize(),
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
        }

        $('#addBenifitGridForm').submit(function(e) {
            e.preventDefault();

            if (formSubmitted) return; // Prevent multiple form submissions

            // emp_grade is now the typed grade NAME (e.g. "HOD L1") — the
            // controller resolves/creates the matching resort_benefit_grade_levels
            // row server-side, so nothing extra needs to happen here.
            submitBenifitGridForm();
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
        // Custom Leave Types section is commented out above — guard so this
        // doesn't throw on a missing element and break every handler after
        // it in this same <script> block (custom-benefits, custom-discount).
        const addCustomLeaveBtn = document.getElementById('addCustomLeave');
        if (addCustomLeaveBtn) addCustomLeaveBtn.addEventListener('click', function() {
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
                            <input type="number" min="0" step="any" id="custom_leave_days_${customLeaveIndex}" name="custom_leave[${customLeaveIndex}][days]" class="form-control" required placeholder="Leave Days"/>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="form-group mb-2">
                            <button type="button" class="btn wfp-btn-critical removeCustomLeave" data-id="${customLeaveIndex}">Remove</button>
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
            newBenefit.classList.add('bg-addedrow', 'custom-benefit');
            newBenefit.innerHTML = `
                <input type="text" name="custom_benefit_name[]" class="bg-inp" placeholder="Benefit Name" />
                <input type="text" name="custom_benefit_value[]" class="bg-inp" placeholder="Benefit Value" />
                <button type="button" class="bg-rmbtn remove-benefit" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>`;
            container.appendChild(newBenefit);
        });
        document.getElementById('custom-benefits-container').addEventListener('click', function(event) {
            if (event.target.closest('.remove-benefit')) {
                event.target.closest('.custom-benefit').remove();
            }
        });
        document.getElementById('add-custom-discount').addEventListener('click', function() {
            const container = document.getElementById('custom-discount-container');
            const newDiscount = document.createElement('div');
            newDiscount.classList.add('bg-addedrow', 'custom-discount');
            newDiscount.innerHTML = `
                <input type="text" name="custom_discount_name[]" class="bg-inp" placeholder="Discount Name" />
                <input type="text" name="custom_discount_value[]" class="bg-inp" placeholder="Discount Value" />
                <button type="button" class="bg-rmbtn remove-discount" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>`;
            container.appendChild(newDiscount);
        });
        document.getElementById('custom-discount-container').addEventListener('click', function(event) {
            if (event.target.closest('.remove-discount')) {
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
                    <label class="bg-check">
                        <input type="checkbox" id="${safeId}" name="sports_and_entertainment_facilities[]" value="${sportName}" />${sportName}
                    </label>
                    <button type="button" class="bg-rmbtn remove-custom-sport" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
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
            container.classList.add('bg-addedrow');
            container.innerHTML = `
                <input type="text" name="custom_field_names[]" class="bg-inp"
                    placeholder="Field Name"
                    data-parsley-pattern="^[a-zA-Z0-9\s]+$"
                    data-parsley-pattern-message="Only letters, numbers, and spaces are allowed."
                >
                <input type="text" name="custom_field_values[]" class="bg-inp"
                    placeholder="Field Value"
                    data-parsley-pattern="^[a-zA-Z0-9\s]+$"
                    data-parsley-pattern-message="Only letters, numbers, and spaces are allowed."
                >
                <button type="button" class="bg-rmbtn remove-custom-field" aria-label="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            `;

            document.getElementById('custom-fields-container').appendChild(container);
        });
        document.addEventListener('click', function(event) {
            if (event.target.closest('.remove-custom-field')) {
                event.target.closest('.bg-addedrow').remove();
            }
        });
    </script>
@endsection
