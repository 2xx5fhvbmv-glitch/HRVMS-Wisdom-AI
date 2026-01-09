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
            <div class="card-header">
                <div class="row g-4">
                    <div class="col-xxl-4 col-xl-5 col-md-6 ">
                        <a href="{{ route('resort.benefitgrid.pdf', $benefit_grid->id) }}" target="_blank" class="btn btn-theme btn-small">Download</a>
                        <div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-lg-5 g-3 mb-3">
                            <div class="col-lg-4">
                                <ul class=" list-group">
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Employee Grade</p>
                                        <span>{{$benefit_grid->emp_grade}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Salary Period</p>
                                        <span>{{$benefit_grid->salary_period}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Salary Paid In</p>
                                        <span>{{$benefit_grid->salary_paid_in}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Contract Status</p>
                                        <span>{{$benefit_grid->contract_status}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Effective Date</p>
                                        <span>{{$benefit_grid->effective_date}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Overtime</p><span>{{$benefit_grid->overtime}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Day Off Per Week (In Days)</p>
                                        <span>{{$benefit_grid->day_off_per_week}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Working Hours per week (In Hours)</p>
                                        <span>{{$benefit_grid->working_hrs_per_week}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Public Holiday Per Year (In Days)</p>
                                        <span>{{$benefit_grid->public_holiday_per_year}}</span>
                                    </li>
                                     <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Rate For Paid Worked Public Holiday and Friday</p>
                                        <span>{{$benefit_grid->paid_worked_public_holiday_and_friday}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Incentives Service Charge</p>
                                        <span>{{$benefit_grid->service_charge}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Maximum Excess luggage relocation expenses (In
                                            Dollars)</p>
                                        <span>{{$benefit_grid->max_excess_luggage_relocation_expense}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Meals Per Day</p>
                                        <span>{{$benefit_grid->meals_per_day}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Food And Beverages Discount(In %)</p>
                                        <span>{{$benefit_grid->food_and_beverages_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Alchoholic Beverages Discount(In %)</p>
                                        <span>{{$benefit_grid->alchoholic_beverages_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Spa Discount(In %)</p>
                                        <span>{{$benefit_grid->spa_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Dive Center Discount(In %)</p>
                                        <span>{{$benefit_grid->dive_center_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Water Sports Discount(In %)</p>
                                        <span>{{$benefit_grid->water_sports_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Ticket upon termination</p>
                                        <span>{{$benefit_grid->ticket_upon_termination}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">MALE Subsistence Allowance(In Dollars)</p>
                                        <span>{{$benefit_grid->male_subsistence_allowance}}</span>
                                    </li>

                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Free return flight to Male Per Year(In Number)</p>
                                        <span>{{$benefit_grid->free_return_flight_to_male_per_year}}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-4">
                                <ul class=" list-group">
                                    @if($benefitGridChildren)
                                        @foreach($benefitGridChildren as $index => $child)
                                            <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                                <p class="mb-sm-0 mb-1 fw-500">{{ $child->leave_category_name ?? 'N/A' }} (In Days)</p>
                                                <span>{{ $child->allocated_days ?? 'N/A' }}</span>
                                            </li>
                                        @endforeach
                                    @endif
                                   
                                </ul>
                            </div>
                            <div class="col-lg-4">
                                <ul class=" list-group">
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Accommodation Status</p>
                                        <span>{{$benefit_grid->accommodation_status}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Furnitures and Fixtures</p>
                                        <span>{{$benefit_grid->furniture_and_fixtures}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Housekeeping</p>
                                        <span>{{$benefit_grid->housekeeping}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Internet Access</p>
                                        <span>{{$benefit_grid->internet_access}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Linen</p><span>{{$benefit_grid->linen}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Laundry</p><span>{{$benefit_grid->laundry}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Telephone</p>
                                        <span>{{$benefit_grid->telephone}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Staff Loan & salary advance</p>
                                        <span>{{$benefit_grid->loan_and_salary_advanced}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Uniform</p>
                                        <span>{{$benefit_grid->uniform}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Health Care & Insurance</p>
                                        <span>{{$benefit_grid->health_care_insurance}}</span>
                                    </li>

                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Ramadan Bonus (In MVR)</p>
                                        <span>{{$benefit_grid->ramadan_bonus}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Relocation Tickets to Maldives</p>
                                        <span>{{$benefit_grid->relocation_ticket}}</span>
                                    </li>

                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Annual Leave ticket to/from POH</p>
                                        <span>{{$benefit_grid->annual_leave_ticket}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">SPORTS, RECREATION & ENTERTAINMENT FACILITIES</p>
                                        <span>{{$benefit_grid->sports_and_entertainment_facilities}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Staff Rate (For Single)</p>
                                        <span>{{$benefit_grid->standard_staff_rate_for_single}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Staff Rate (For Double)</p>
                                        <span>{{$benefit_grid->standard_staff_rate_for_double}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Friends With Benefit Discount(In %)</p>
                                        <span>{{$benefit_grid->friends_with_benefit_discount}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Staff rate (seaplane) to/from Male (In Dollars)</p>
                                        <span>{{$benefit_grid->staff_rate_for_seaplane_male}}</span>
                                    </li>

                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Status</p>
                                        <span>{{$benefit_grid->status}}</span>
                                    </li>
                                    <li class="d-sm-flex align-items-center justify-content-between list-group-item">
                                        <p class="mb-sm-0 mb-1 fw-500">Relocation Tickets to Maldives</p>
                                        <span>{{$benefit_grid->relocation_ticket}}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endSection
        @section('import-css')

        @endsection

        @section('import-scripts')
        @endsection