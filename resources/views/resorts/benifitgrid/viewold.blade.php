@extends('resorts.layouts.app')

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Configuration</span>
                        <h1>Benefit Grid</h1>
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
                <table id="benefit-grid-table" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Employee Grade</th>
                            <td class="text-nowrap">{{$benefit_grid->emp_grade}}</td>
                            <th class="text-nowrap">Salary Period</th>
                            <td class="text-nowrap">{{$benefit_grid->salary_period}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Salary Paid In</th>
                            <td class="text-nowrap">{{$benefit_grid->salary_paid_in}}</td>
                            <th class="text-nowrap">Contract Status</th>
                            <td class="text-nowrap">{{$benefit_grid->contract_status}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Effective Date</th>
                            <td class="text-nowrap">{{$benefit_grid->effective_date}}</td>
                            <th class="text-nowrap">Overtime</th>
                            <td class="text-nowrap">{{$benefit_grid->overtime}}</td>
                        </tr>
                        
                        <tr>
                            <th class="text-nowrap">Day Off Per Week (In Days)</th>
                            <td class="text-nowrap">{{$benefit_grid->day_off_per_week}}</td>
                            <th class="text-nowrap">Working Hours per week (In Hours)</th>
                            <td class="text-nowrap">{{$benefit_grid->working_hrs_per_week}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Public Holiday Per Year (In Days)</th>
                            <td class="text-nowrap">{{$benefit_grid->public_holiday_per_year}}</td> 
                        </tr>
                        @if($benefitGridChildren)
                            @foreach($benefitGridChildren as $index => $child)
                                <!-- Start a new row for every two items -->
                                @if($index % 2 == 0)
                                    <tr>
                                @endif
                                
                                <!-- First leave type and allocated days -->
                                <th class="text-nowrap">{{ $child->leave_type ?? 'N/A' }} (In Days)</th>
                                <td class="text-nowrap">{{ $child->allocated_days ?? 'N/A' }}</td>

                                <!-- If we reach the second item, close the row -->
                                @if($index % 2 == 1 || $index == count($benefitGridChildren) - 1)
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                       
                        <tr>
                            <th class="text-nowrap">Rate For Paid Worked Public Holiday and Friday</th>
                            <td class="text-nowrap">{{$benefit_grid->paid_worked_public_holiday_and_friday}}</td>
                            <th class="text-nowrap">Incentives Service Charge</th>
                            <td class="text-nowrap">{{$benefit_grid->service_charge}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Accommodation Status</th>
                            <td class="text-nowrap">{{$benefit_grid->accommodation_status}}</td>
                            <th class="text-nowrap">Furnitures and Fixtures</th>
                            <td class="text-nowrap">{{$benefit_grid->furniture_and_fixtures}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Housekeeping</th>
                            <td class="text-nowrap">{{$benefit_grid->housekeeping}}</td>
                            <th class="text-nowrap">Internet Access</th>
                            <td class="text-nowrap">{{$benefit_grid->internet_access}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap" >Linen</th>
                            <td class="text-nowrap" colspan="3">{{$benefit_grid->linen}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Laundry</th>
                            <td class="text-nowrap" colspan="3">{{$benefit_grid->laundry}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Telephone</th>
                            <td class="text-nowrap">{{$benefit_grid->telephone}}</td>
                            <th class="text-nowrap">Staff Loan & salary advance</th>
                            <td class="text-nowrap">{{$benefit_grid->loan_and_salary_advanced}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Uniform</th>
                            <td class="text-nowrap">{{$benefit_grid->uniform}}</td>
                            <th class="text-nowrap">Health Care & Insurance</th>
                            <td class="text-nowrap">{{$benefit_grid->health_care_insurance}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Ramadan Bonus (In MVR)</th>
                            <td class="text-nowrap">{{$benefit_grid->ramadan_bonus}}</td>
                            <th class="text-nowrap">Relocation Tickets to Maldives</th>
                            <td class="text-nowrap">{{$benefit_grid->relocation_ticket}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Maximum Excess luggage relocation expenses (In Dollars)</th>
                            <td class="text-nowrap">{{$benefit_grid->max_excess_luggage_relocation_expense}}</td>
                            <th class="text-nowrap">Meals Per Day</th>
                            <td class="text-nowrap">{{$benefit_grid->meals_per_day}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Food And Beverages Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->food_and_beverages_discount}}</td>
                            <th class="text-nowrap">Alchoholic Beverages Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->alchoholic_beverages_discount}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Spa Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->spa_discount}}</td>
                            <th class="text-nowrap">Dive Center Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->dive_center_discount}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Water Sports Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->water_sports_discount}}</td>
                            <th class="text-nowrap">Annual Leave ticket to/from POH</th>
                            <td class="text-nowrap">{{$benefit_grid->annual_leave_ticket}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">SPORTS, RECREATION & ENTERTAINMENT FACILITIES</th>
                            <td class="text-nowrap" colspan="3">{{$benefit_grid->sports_and_entertainment_facilities}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Staff Rate (For Single)</th>
                            <td class="text-nowrap">{{$benefit_grid->standard_staff_rate_for_single}}</td>
                            <th class="text-nowrap">Staff Rate (For Double)</th>
                            <td class="text-nowrap">{{$benefit_grid->standard_staff_rate_for_double}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Friends With Benefit Discount(In %)</th>
                            <td class="text-nowrap">{{$benefit_grid->friends_with_benefit_discount}}</td>
                            <th class="text-nowrap">Staff rate (seaplane) to/from Male (In Dollars)</th>
                            <td class="text-nowrap">{{$benefit_grid->staff_rate_for_seaplane_male}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Ticket upon termination</th>
                            <td class="text-nowrap">{{$benefit_grid->ticket_upon_termination}}</td>
                            <th class="text-nowrap">MALE Subsistence Allowance(In Dollars)</th>
                            <td class="text-nowrap">{{$benefit_grid->male_subsistence_allowance}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Free return flight to Male Per Year(In Number)</th>
                            <td class="text-nowrap">{{$benefit_grid->free_return_flight_to_male_per_year}}</td>
                            <th class="text-nowrap">Status</th>
                            <td class="text-nowrap">{{$benefit_grid->status}}</td>
                        </tr>
                        <tr>
                            <th class="text-nowrap">Relocation Tickets to Maldives</th>
                            <td class="text-nowrap">{{$benefit_grid->relocation_ticket}}</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
</div>
@endSection
@section('import-css')

@endsection

@section('import-scripts')
@endsection
