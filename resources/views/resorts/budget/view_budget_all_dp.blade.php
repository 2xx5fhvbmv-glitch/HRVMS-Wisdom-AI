@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

{{-- DEBUG INDICATOR - If you can see this, the page has reloaded --}}
<style>.debug-indicator { position: fixed; top: 10px; right: 10px; background: #28a745; color: white; padding: 10px; border-radius: 5px; z-index: 9999; font-weight: bold; }</style>
<div class="debug-indicator">✅ Updated: {{ now()->format('Y-m-d H:i:s') }}</div>

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
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                {{-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#revise-budgetmodal" data-bs-toggle="modal" class="btn btn-white ms-3">Revise
                            Budget</a>
                    </div>
                </div> --}}
            </div>
        </div>

        <div>

            <div class="card">
                <div class="row g-3 justify-content-end mb-4">
                    <div class="col-auto">
                        <div class="d-flex align-items-center justify-content-sm-end">
                            {{-- Year Filter --}}
                             <div class="form-group me-2">
                                <form method="GET" action="{{ route('resort.budget.viewbudget') }}" id="yearFilterForm">
                                    <select class="form-select ManningBudgetYearWise" id="yearFilter" name="year" onchange="document.getElementById('yearFilterForm').submit();">
                                        @php
                                            $currentYear = date('Y');
                                            $startYear = $currentYear - 10;
                                            $endYear = $currentYear + 1;
                                            $selectedYear = request()->get('year', $currentYear);
                                        @endphp
                                        @for ($loopyear = $startYear; $loopyear <= $endYear; $loopyear++)
                                            <option value="{{ $loopyear }}" @if ($loopyear == $selectedYear) selected @endif>{{ $loopyear }}</option>
                                        @endfor
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <a href="#" class="btn btn-themeBlue btn-sm btn-allShow">All Department Show </a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn  btn-themeBlue btn-sm btn-allHIde">All Department Hide </a>
                    </div> -->
                    <!-- <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">All Department
                                Show</label>
                            <div class="form-check form-switch form-switchTheme department-switch">
                                <input disabled    class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for=""></label>
                            </div>
                        </div>
                    </div> -->
                </div>

                @php $incrementKey= 1;

                $findingId=array();  @endphp

                <div class="viewBudget-accordion viewBudget-accordion-alldp" id="accordionViewBudget">
                    @if($departments->isNotEmpty())
                        <input type="hidden" id="resortId" value="{{ $resortId }}"  >


                        @foreach($departments as $key => $deptData)

                        <div class="accordion-item">
                            <div class="accordion-header p-1" id="heading{{ $incrementKey}}">
                                <div class="row g-3 align-items-center">
                                    <div class="col-lg-5">
                                        <button class="accordion-button accordion-arrow-none" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $incrementKey}}" aria-expanded="true" aria-controls="collapse{{ $incrementKey}}">

                                            <h3>{{$deptData->department->name }}</h3>
                                            <input type="hidden" id="grand_total_department_{{$incrementKey}}" value="0" >
                                                <span class="badge badge-dark grand_total_department_{{$incrementKey}}">

                                                    Budget: $ 0
                                                </span>



                                        </button>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="d-flex align-items-center justify-content-lg-end">
                                            <a href="#" class="text-lightblue fw-500 fs-13 text-nowrap">WSB : $11,985</a>
                                            @if($employeeRankPosition['position'] == "HR")
                                                <a href="#revise-budgetmodal" data-bs-toggle="modal"
                                                data-dept_id ="{{ $deptData->dept_id}}"
                                                data-Budget_id ="{{$deptData->Budget_id}}"
                                                data-Message_id="{{$deptData->Message_id}}"
                                                class="btn btn-xs revisebudgetmodal btn-themeBlue ms-2">Revise
                                                    Budget</a>

                                                            <form id="SendToFinance" method="POST"  >
                                                                    @csrf
                                                                    <input type="hidden" class="Revise_Budget_id" name="Budget_id" value="{{ $deptData->Budget_id }}" >
                                                                    <input type="hidden" class="Revise_resort_id" name="resort_id" value="{{ $deptData->resort_id }}" >
                                                                    <input type="hidden" class="Revise_Department_id" name="Department_id  " value="{{ $deptData->dept_id }}" >
                                                                    <input type="hidden" class="Revise_Message_id" name="Message_id" value="{{ $deptData->Message_id }}" >
                                                                    <p class="mb-0 fw-500 departmentBudget"></p>
                                                                    <button type="submit" class=" ms-2 btn btn-xs  btn-theme SendToFinance" >Sent To Finance</button>
                                                                </form>
                                            @endif

                                        <a href="#bulk-incrementView-modal" data-bs-toggle="modal"
                                        data-dept_id ="{{ $deptData->dept_id}}"
                                        data-Budget_id ="{{ $deptData->Budget_id}}"
                                        class="btn btn-xs incrementView btn-themeBlue ms-2">Bulk Increment</a>
                                        <a href="{{ route('resort.budget.comparebudget', ['id' => $deptData->id , 'budgetid' => $deptData->Budget_id ]) }}" class="btn btn-xs btn-coolblue ms-2 @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.comparebudget',config('settings.resort_permissions.view')) == false) d-none @endif"> compare </a>


                                        <button class="accordion-button ms-lg-2 ms-auto w-auto" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $incrementKey}}" aria-expanded="true" aria-controls="collapse{{ $incrementKey}}">
                                        </button>
                                    </div>
                                </div>
                                </div>

                            </div>
                            <div id="collapse{{ $incrementKey}}" class="accordion-collapse collapse {{  $incrementKey == 1 ? 'show' : 'edit' }}"
                                data-bs-parent="#accordionViewBudget">
                                <div class="table-responsive">
                                    <table id="filled-positions-table" class="table  w-100  Find-Table_{{ $incrementKey}}" >
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="text-nowrap">Positions</th>
                                                <th class="text-nowrap">No. of position</th>
                                                <th class="text-nowrap">Employee Name</th>
                                                <th class="text-nowrap w-120">Rank</th>
                                                <th class="text-nowrap">Nation</th>
                                                <th>Current Basic salary</th>
                                               <th>Proposed Basic Salary {{ $year }}</th>
                                                @for ($i = 1; $i <= 12; $i++)
                                                    @php
                                                        // Correct: use controller-passed $year

                                                        $yearMonthValue = date("m-Y", mktime(0, 0, 0, $i, 1, $year));
                                                        $yearMonth = date("M-Y", mktime(0, 0, 0, $i, 1, $year));
                                                    @endphp
                                                    <th class="text-nowrap w-120">{{ $yearMonth }}</th>
                                                @endfor
                                            </tr>
                                        </thead>
                                        <tbody>

                                            @if( $deptData->departmentPositions->isNotEmpty())
                                                @foreach ($deptData->departmentPositions as  $Positions)

                                                <tr>
                                                    <td>

                                                        <div class="d-flex align-items-center">
                                                            <a href="javascript:void(0)"
                                                                class="btn-lg-icon icon-bg-green me-1 editBudget-icon @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.viewbudget',config('settings.resort_permissions.edit')) == false) d-none @endif">
                                                                <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt=""
                                                                    class="img-fluid" />
                                                            </a>
                                                            <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.viewbudget',config('settings.resort_permissions.delete')) == false) d-none @endif">
                                                                <img src="{{ URL::asset('resorts_assets/images/trash-red.svg')}}" alt=""
                                                                    class="img-fluid" />
                                                            </a>
                                                        </div>
                                                        <a href="#" data-id="{{$Positions->Position_id}}" data-position="{{$incrementKey}}" class=" updateBudgetData  btn  btn-theme btn-sm">Submit</a>
                                                    </td>

                                                    <td>{{ $Positions->position_title}}</td>
                                                    <td>{{ $Positions->headcount }}</td>
                                                    <td colspan="36" class="p-table">
                                                        <table class="table m-0">

                                                            @if(isset($Positions->employees) && $Positions->employees->isNotEmpty())

                                                               @foreach ($Positions->employees as $e)


                                                                    @php
                                                                        $salary = (isset($e->Proposed_Basic_salary) && $e->Proposed_Basic_salary > 0 ) ?  $e->Proposed_Basic_salary :$e->basic_salary ;
                                                                    @endphp
                                                                    <tr>
                                                                            <td> {{ $e->first_name }}  {{ $e->last_name }}  </td>
                                                                            <td class="w-120">
                                                                            @php
                                                                                $Rank = config( 'settings.Position_Rank');


                                                                                $AvilableRank = array_key_exists($e->rank, $Rank) ? $Rank[$e->rank] : '';

                                                                            @endphp
                                                                                {{$AvilableRank }}
                                                                            </td>
                                                                            <td>{{ $e->nationality }}</td>

                                                                        <td class="current-basic-salary-{{$incrementKey }}">
                                                                            <div class="inputValue">{{ number_format($e->basic_salary,2) }}</div>
                                                                            <input type="number" class="form-control" name="BasicSalary[{{ $Positions->Position_id}}][{{ $e->vacantData->smrp_child_id}}][]" value="{{ $e->basic_salary }}" min="0" step="0.01" max="9999999999.99">
                                                                        </td>
                                                                        <td class="proposed-basic-salary-{{$incrementKey }}">
                                                                            <div class="inputValue">{{number_format($e->vacantData->Proposed_Basic_salary,2)}}</div>
                                                                            <input type="number" class="form-control" name="ProposedBasicsalary[{{ $Positions->Position_id}}][{{ $e->vacantData->smrp_child_id}}][]" value="{{$e->vacantData->Proposed_Basic_salary}}" min="0" step="0.01" max="9999999999.99">
                                                                        </td>

                                                                        @php
                                                                            $lastIncrementMonth = (new DateTime($e->incremented_date))->format('m');
                                                                            $basicSalary = $salary;
                                                                            $proposedSalary = $e->vacantData->Proposed_Basic_salary > 0 ? $e->vacantData->Proposed_Basic_salary: $salary;

                                                                            if(isset($e->vacantData->Months))
                                                                            {
                                                                                $monthdataCollection =  json_decode($e->vacantData->Months);
                                                                            }
                                                                            else {
                                                                                $monthdataCollection=array();
                                                                            }

                                                                            $ak=0;
                                                                        @endphp
                                                                        @for ($i = 1; $i <= 12; $i++)
                                                                            @php
                                                                                $nextYear = date('Y', strtotime('+1 year'));

                                                                                $monthlyData = DB::table('position_monthly_data')
                                                                                                ->where('position_id', $Positions->Position_id)
                                                                                                ->where('month', $i)
                                                                                                ->where('manning_response_id', $Positions->Budget_id)
                                                                                                ->first();

                                                                                $headcount = $monthlyData->headcount ?? 0;
                                                                                $vacantcount = $monthlyData->vacantcount ?? 0;
                                                                                $filledcount = $monthlyData->filledcount ?? 0;
                                                                                $monthlySalary = ($i < $lastIncrementMonth) ? $basicSalary : $proposedSalary;

                                                                                if(!empty($monthdataCollection) && $monthdataCollection[$ak]->month == $i)
                                                                                {
                                                                                    $totalMothwisecost =$monthdataCollection[$ak]->salary;
                                                                                }
                                                                                else {
                                                                                    $totalMothwisecost =   Common::CheckemployeeBudgetCost($e->nationality, $e->resort_id, $monthlySalary);
                                                                                }
                                                                                $ak++;
                                                                            @endphp
                                                                            <td class="w-120   {{$incrementKey}}-month-{{$i}}">
                                                                                <div class="inputValue">
                                                                                            {{ number_format($totalMothwisecost,2) }}

                                                                                        <a href="#incrementView-modal" data-bs-toggle="modal" class="btn-tableIcon btnIcon-skyblue"
                                                                                        data-dept_id ="{{ $deptData->dept_id}}"
                                                                                        data-Budget_id ="{{ $deptData->Budget_id}}"
                                                                                        data-emp_id = "{{ $e->Empid }}"
                                                                                        data-current-basic-salary = "{{ $e->basic_salary }}"
                                                                                        >
                                                                                            <img src="{{ URL::asset('resorts_assets/images/increment.svg') }}"/>
                                                                                        </a>
                                                                                </div>

                                                                                @php

                                                                                    $name= "manning_child[".$Positions->Position_id."][".$e->vacantData->smrp_child_id."][]";

                                                                                @endphp

                                                                                <input type="number"  min="0" step="0.01" max="9999999999.99"
                                                                                name="{{ $name }}"

                                                                                class="form-control inputShow" value="{{$totalMothwisecost}}">
                                                                            </td>


                                                                        @endfor
                                                                </tr>



                                                                </tr>


                                                              @endforeach
                                                              
                                                              {{-- DEBUG: Only show vacants if data exists in database --}}
                                                              {{-- Updated: Dec 1, 2025 - Fixed vacant display logic --}}
                                                              @php
                                                                  // Debug: Log what we have
                                                                  if (isset($Positions->vacant_details)) {
                                                                      echo "<!-- Position: {$Positions->position_title} | Vacant Count: " . count($Positions->vacant_details) . " | In Request: " . ($Positions->is_in_manning_request ? 'Yes' : 'No') . " -->";
                                                                  }
                                                              @endphp
                                                              @if(isset($Positions->vacant_details) && !empty($Positions->vacant_details) && $Positions->is_in_manning_request)
                                                                @foreach($Positions->vacant_details as $vacantIndex => $vacantDetail)
                                                                    <tr>
                                                                        <td colspan="5">
                                                                            <span class="badge badge-secondary">Vacant Position {{ $vacantIndex }}</span>
                                                                        </td>
                                                                        
                                                                        @for($month = 1; $month <= 12; $month++)
                                                                            @php
                                                                                // Get vacant budget cost configuration for this month
                                                                                $vacantCost = 0;
                                                                                
                                                                                // Get monthly vacant budget cost configuration
                                                                                $monthlyVacantConfig = DB::table('resort_vacant_budget_cost_configurations')
                                                                                    ->where('vacant_budget_cost_id', $vacantDetail->id)
                                                                                    ->where('month', $month)
                                                                                    ->sum('value');
                                                                                
                                                                                $vacantCost = $monthlyVacantConfig ?? 0;
                                                                                
                                                                                // If no monthly config, use CheckVacantBudgetCost as fallback
                                                                                if ($vacantCost == 0) {
                                                                                    $vacantCostArray = Common::CheckVacantBudgetCost(1);
                                                                                    $vacantCost = $vacantCostArray['total_cost'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            
                                                                            <td class="w-120 {{ $incrementKey }}-month-{{ $month }}">
                                                                                @if($vacantCost > 0)
                                                                                    <input type="hidden" class="vacant" name="vacant[{{ $incrementKey }}][{{ $month }}][{{ $vacantIndex }}]" value="{{ $vacantCost }}">
                                                                                    <span style="word-wrap: break-word; white-space: break-spaces;" class="badge badge-success">
                                                                                        ${{ number_format($vacantCost, 2) }}
                                                                                    </span>
                                                                                @endif
                                                                            </td>
                                                                        @endfor
                                                                    </tr>
                                                                @endforeach
                                                              @endif

                                                            @endif



                                                        </table>
                                                    </td>

                                                </tr>
                                                @endforeach
                                            @endif

                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th></th>
                                                <th>Total:</th>
                                                <th id="total-positions-{{ $incrementKey}}">0</th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th id="{{ $incrementKey}}-total-current-basic-salary">$0</th>
                                                <th id="{{ $incrementKey}}-total-proposed-basic-salary">$0</th>
                                                <th id="{{ $incrementKey}}-total-jan">$0</th>
                                                <th id="{{ $incrementKey}}-total-feb">$0</th>
                                                <th id="{{ $incrementKey}}-total-mar">$0</th>
                                                <th id="{{ $incrementKey}}-total-apr">$0</th>
                                                <th id="{{ $incrementKey}}-total-may">$0</th>
                                                <th id="{{ $incrementKey}}-total-jun">$0</th>
                                                <th id="{{ $incrementKey}}-total-jul">$0</th>
                                                <th id="{{ $incrementKey}}-total-aug">$0</th>
                                                <th id="{{ $incrementKey}}-total-sep">$0</th>
                                                <th id="{{ $incrementKey}}-total-oct">$0</th>
                                                <th id="{{ $incrementKey}}-total-nov">$0</th>
                                                <th id="{{ $incrementKey}}-total-dec">$0</th>
                                            </tr>
                                        </tfoot>

                                    </table>
                                </div>

                            </div>
                        </div>
                        @php $findingId[]=  $incrementKey++; @endphp
                        @endforeach
                    @else

                        <div id="collapse{{ $incrementKey}}" class="accordion-collapse collapse {{  $incrementKey == 1 ? 'show' : 'edit' }}"
                        data-bs-parent="#accordionViewBudget">
                            <div class="table-responsive">
                                    <span class="text-danger" style="text-align: center">No Record Found..</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal fade" id="incrementView-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Salary Increment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="incrementForm">
                    <input type="hidden" id="Inc_Budget_id" value=""  >
                    <input type="hidden" id="Inc_dept_id" value=""  >
                    <div class="mb-3">
                        <label class="form-label">Current Salary</label>
                        <input type="number" class="form-control" id="current_salary" readonly>
                        <input type="hidden" class="form-control" id="employee_id" min="0" step="0.01" max="9999999999.99">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Increment Date</label>
                        <input type="text" class="form-control" id="date" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Increment Amount</label>
                        <input type="number" class="form-control" id="inAmound" readonly min="0" step="0.01" max="9999999999.99">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Increment Date</label>
                        <input type="text" class="form-control datepicker" id="nextIndate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Increment Amount</label>
                        <input type="number" step="0.01" class="form-control" id="nextInAmound" min="0" max="99999999.99">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Increment Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="increment_percentage">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Salary</label>
                        <input type="number" class="form-control" id="new_salary" readonly min="0" step="0.01" max="9999999999.99">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" id="increment_reason" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="increment_notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="incrementSubmit" class="btn btn-sm btn-theme">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulk-incrementView-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Bulk Increment for Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkincrementForm" >
                    <input type="hidden" id="BI_Budget_id" value="">
                    <input type="hidden" id="BI_dept_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Increment Percentage<span class="req_span">*</span></label>
                        <input type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="form-control"
                            id="bulk_increment_percentage"
                            name="bulk_increment_percentage"
                            placeholder="Enter percentage"
                            data-parsley-either="#bulk_increment_amount"
                            data-parsley-either-message="Please fill either Increment Amount or Percentage, not both or none.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Increment Amount<span class="req_span">*</span></label>
                        <input type="number"
                            min="0"
                            max="99999999.99"
                            step="0.01"
                            class="form-control"
                            id="bulk_increment_amount"
                            name="bulk_increment_amount"
                            placeholder="Enter amount"
                            data-parsley-either="#bulk_increment_percentage"
                            data-parsley-either-message="Please fill either Increment Amount or Percentage, not both or none.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="bulk_increment_notes" rows="2"></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="bulkincrementSubmit" class="btn btn-sm btn-theme">Submit</button>
            </div>
        </div>
    </div>
</div>

{{-- //Revise Budget Modal --}}
<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ReviseBudget">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-20">
                        <p class="mb-0 fw-500 departmentBudget">
                        <input type="hidden" class="Revise_Budget_id" name="Budget_id" >
                        <input type="hidden"  class="Revise_resort_id" name="resort_id" >
                        <input type="hidden"  class="Revise_Department_id" name="Department_id"  >
                        <input type="hidden"  class="Revise_Message_id" name="Message_id" >
                            @php
                                $manning_request =  config('settings.manning_request');
                                $manning_request = array_key_exists('msg3', $manning_request) ? $manning_request['msg3'] : '' ;
                            @endphp
                        <textarea class="form-control" name="ReviseBudgetComment" rows="7" placeholder="Add Comment Regarding Revision">{{ $manning_request }}</textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        window.Parsley.addValidator('either', {
            validateString: function (value, otherSelector) {
                const $thisVal = parseFloat(value);
                const $otherVal = parseFloat($(otherSelector).val());

                const hasThis = !isNaN($thisVal) && $thisVal > 0;
                const hasOther = !isNaN($otherVal) && $otherVal > 0;

                return (hasThis && !hasOther) || (!hasThis && hasOther);
            },
            messages: {
                en: 'Please fill either Increment Amount or Percentage, not both or none.'
            }
        });

        const $percentage = $('#bulk_increment_percentage');
        const $amount = $('#bulk_increment_amount');

        // Trigger revalidation on input
        $percentage.add($amount).on('input', function () {
            $percentage.parsley().validate();
            $amount.parsley().validate();
        });


        $('#incrementForm').parsley();
        $('#bulkincrementForm').parsley();

        $(".editBudget-icon").click(function () {
            $(this).parents("tr").addClass("inputShow");
        });
        $(".btn-allShow").click(function () {
            $(".accordion-collapse").addClass("collapse show");
            $(".accordion-collapse").removeClass("collapse");
            $(".accordion-button ").removeClass("collapsed");
        });
        $(".btn-allHIde").click(function () {
            $(".accordion-collapse").removeClass("collapse show");
            $(".accordion-collapse").addClass("collapse");
            $(".accordion-button ").addClass("collapsed");
        });
        $('.department-switch input').change(function () {
            if ($(this).is(':checked')) {
                $(".accordion-collapse").addClass("collapse show");
                $(".accordion-collapse").removeClass("collapse");
                $(".accordion-button ").removeClass("collapsed");  // Add class when checked
            } else {
                $(".accordion-collapse").removeClass("collapse show");
                $(".accordion-collapse").addClass("collapse");
                $(".accordion-button ").addClass("collapsed");  // Remove class and hide when unchecked
            }
        });
        $(".incrementView").on("click",function () {
            $("#BI_Budget_id").val($(this).data("budget_id"));
            $("#BI_dept_id").val($(this).data("dept_id"));
        });
        $(document).on('click', '.revisebudgetmodal', function() {
            $(".Revise_Budget_id").val($(this).data("budget_id"));
            $(".Revise_resort_id").val($("#resortId").val());
            $(".Revise_Department_id").val($(this).data("dept_id"));
            $(".Revise_Message_id").val($(this).data("message_id"));
        });
        // Disable one field if the other is filled
        $('#bulk_increment_percentage').on('input', function() {
            if ($(this).val()) {
                $('#bulk_increment_amount').prop('disabled', true).val(''); // Disable amount input
            } else {
                $('#bulk_increment_amount').prop('disabled', false); // Enable amount input
            }
        });

        $('#bulkincrementSubmit').on('click', function(e) {
             e.preventDefault();

            if ($('#bulkincrementForm').parsley().validate())
            {

                const incrementPercentage = parseFloat($('#bulk_increment_percentage').val());
                const incrementAmount = parseFloat($('#bulk_increment_amount').val());
                const budgetId = $('#BI_Budget_id').val();
                const resortId = $('#resortId').val();
                const deptId = $('#BI_dept_id').val();

                // Validate input
                if (!incrementPercentage && !incrementAmount) {
                    toastr.error('Please enter either an increment percentage or an increment amount.','Error',{positionClass: 'toast-bottom-right'});
                    return;
                }

                if (incrementPercentage && incrementAmount) {
                    toastr.error('Please enter only one of increment percentage or amount.','Error',{positionClass: 'toast-bottom-right'});
                    return;
                }

                // Send data to the server via AJAX
                $.ajax({
                    url: "{{ route('employee.bulksalaryincrement.save') }}",  // Ensure this route is correct
                    method: 'POST',
                    data: {
                        increment_percentage: incrementPercentage || null,
                        increment_amount: incrementAmount || null,
                        budget_id: budgetId,
                        resort_id: resortId,
                        dept_id: deptId,
                        notes: $('#bulk_increment_notes').val(),
                        effective_date: new Date().toISOString().slice(0, 10),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {

                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#bulk-incrementView-modal').modal('hide');
                            // Optionally reload the page or refresh data
                        } else {

                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = "An error occurred while applying increments.";

                        // Check if the response is JSON and has a message property
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });
        // Initialize datepicker for increment dates
        $('.datepicker').datepicker({
            format: dt_format,
            autoclose: true,
            minDate: 0
        });
        function formatDate(isoDateString) {
            if(isoDateString){
                const date = new Date(isoDateString);
                // Get year, month, and day without timezone adjustment
                const year = date.getUTCFullYear();
                const month = String(date.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-indexed
                const day = String(date.getUTCDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }
        }
        $('.btnIcon-skyblue').on('click', function(e) {
            e.preventDefault();
            const row = $(this).closest('tr');
            currentEmployeeId =$(this).data("emp_id");
            currentSalary =$(this).data('current-basic-salary');
            currentMonth = $(this).closest('td').attr('class').split(' ')[1].split('-')[1];
            // Reset form
            $('#incrementView-modal form')[0].reset();

            $('#Inc_Budget_id').val($(this).data("budget_id"));
            $('#Inc_dept_id').val($(this).data("dept_id"));
            // Fetch current increment details
            $.ajax({
                url: "{{ route('employee.salaryincrement.get')}}",
                type: 'GET',
                data: {
                    employee_id: currentEmployeeId
                },
                success: function(response) {
                    if(response.success) {
                        const formattedDate = formatDate(response.last_increment.effective_date);
                        $('#date').val(formattedDate,response.last_increment.increment_amount);
                        $('#inAmound').val(response.last_increment.increment_amount);
                        $('#current_salary').val(currentSalary);
                        $('#employee_id').val(currentEmployeeId);
                    }

                    // Show the modal
                    $('#incrementView-modal').modal('show');
                },
                 error: function(xhr) {
                    let errorMessage = "An error occurred while applying increments.";

                    // Check if the response is JSON and has a message property
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }

            });
        });
        // Calculate percentage and new salary when amount changes
        $('#nextInAmound, #increment_percentage').on('input', function() {
            const incrementAmount = parseFloat($('#nextInAmound').val()) || 0;
            const incrementPercentage = parseFloat($('#increment_percentage').val()) || 0;
            console.log('Increment Amount:', incrementAmount, 'Increment Percentage:', incrementPercentage);
            let newSalary = 0;

            if ($(this).attr('id') === 'nextInAmound') {
                const calculatedPercentage = ((incrementAmount / currentSalary) * 100).toFixed(2);
                $('#increment_percentage').val(calculatedPercentage);
                newSalary = (parseFloat(currentSalary) + parseFloat(incrementAmount)).toFixed(2);
            } else if ($(this).attr('id') === 'increment_percentage') {
                const calculatedAmount = ((parseFloat(incrementPercentage) / 100) * parseFloat(currentSalary)).toFixed(2);
                $('#nextInAmound').val(calculatedAmount);
                newSalary = (parseFloat(currentSalary) + parseFloat(calculatedAmount)).toFixed(2);
            }
            $('#new_salary').val(newSalary);
        });
        $('#incrementSubmit').on('click', function(e) {
            e.preventDefault();
            if ($('#incrementForm').parsley().validate())
            {
                const incrementData = {
                    employee_id: $('#employee_id').val(),
                    previous_salary: currentSalary,
                    new_salary: $('#new_salary').val(),
                    increment_amount: $('#nextInAmound').val(),
                    increment_percentage: $('#increment_percentage').val(),
                    reason: $('#increment_reason').val(),
                    effective_date: $('#nextIndate').val(),
                    notes: $('#increment_notes').val(),
                    Budget_id : $('#Inc_Budget_id').val(),
                    dept_id : $('#Inc_dept_id').val(),
                    resortId : $('#resortId').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: "{{ route('employee.salaryincrement.save') }}",
                    type: 'POST',
                    data: incrementData,
                    success: function(response) {
                        if(response.success) {
                            // updateSalaryDisplay(currentEmployeeId, currentMonth, response.new_salary);
                            $('#incrementView-modal').modal('hide');
                            toastr.success('Salary increment saved successfully!', "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.message || 'Error saving increment details', "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                        window.setTimeout(function() {
                            window.location.reload();  // This reloads the current page
                        }, 2000);
                    },
                    error: function(xhr) {
                        let errorMessage = "An error occurred while applying increments.";

                        // Check if the response is JSON and has a message property
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });
        function GetTotalValue()
        {
            const findingIdArray = @json($findingId);
            $.each(findingIdArray, function (key, Finding_id) {
            // Reset totals for each department
            let totalPositions = 0;
            let totalCurrentBasicSalary = 0;
            let totalProposedBasicSalary = 0;
            const monthlyTotals = Array(12).fill(0);
            const vacantMonthlyTotals = Array(12).fill(0); // Track monthly vacant totals
            let vacantTotal = 0;

            // Loop through each row within the department table
            $('.Find-Table_' + Finding_id + ' tbody > tr').each(function () {
                // Get position count
                const positionCount = parseInt($(this).find('td:eq(2)').text()) || 0;
                totalPositions += positionCount;


                $(this).find('table tr').each(function () {
                    const badgeExists = $(this).find('.badge-success, .badge-secondary').length;

                    if (badgeExists)
                    {
                        // Handle vacant positions - sum all vacant inputs for each month
                        for (let i = 1; i <= 12; i++)
                        {
                            // Find all vacant inputs for this month (handles multiple vacant positions)
                            $(this).find(`input.vacant[name^="vacant[${Finding_id}][${i}]"]`).each(function() {
                                const vacantValue = $(this).val();
                                if(!isNaN(vacantValue) && vacantValue !== '')
                                {
                                    const vacantCost = parseFloat(vacantValue) || 0;
                                    monthlyTotals[i - 1] += Math.round(vacantCost);
                                }
                            });
                        }
                    } else {
                        // Get current and proposed salaries
                        const currentBasicSalary = parseFloat($(this).find('.current-basic-salary-' + Finding_id + ' .inputValue').text().replace(/[^0-9.-]+/g, '')) || 0;
                        const proposedBasicSalary = parseFloat($(this).find('.proposed-basic-salary-' + Finding_id + ' .inputValue').text().replace(/[^0-9.-]+/g, '')) || 0;
                        totalCurrentBasicSalary += currentBasicSalary;
                        totalProposedBasicSalary += proposedBasicSalary;

                        for (let i = 1; i <= 12; i++) {
                            const monthCell = $(this).find(`.${Finding_id}-month-${i}`);

                            const monthInput = monthCell.find('input');
                            const monthInputValue = monthInput.length > 0
                                ? parseFloat((monthInput.val() || '0').replace(/[^0-9.-]+/g, ''))
                                : 0;

                            const monthDiv = monthCell.find('.inputValue');
                            const monthDivValue = monthDiv.length > 0
                                ? parseFloat((monthDiv.text() || '0').replace(/[^0-9.-]+/g, ''))
                                : 0;

                            const monthValue = monthInputValue || monthDivValue;


                            monthlyTotals[i - 1] += monthValue;
                            for (let i = 1; i <= 12; i++) {
                                    const monthCell = $(this).find(`.month-${i} .inputValue`);
                                    if (monthCell.length) {
                                        const employeeCost = parseCurrency(monthCell.text()) || 0;
                                        monthlyTotals[i - 1] +=  Math.round(employeeCost);
                                    }
                                }
                        }
                    }
                });
            });

            // Combine monthly totals with vacant monthly totals
            const combinedMonthlyTotals = monthlyTotals.map((total, index) => total + vacantMonthlyTotals[index]);

            // Update footer totals for the department
            $(`#total-positions-${Finding_id}`).text(totalPositions);
            $(`#${Finding_id}-total-current-basic-salary`).text('$' + totalCurrentBasicSalary.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            $(`#${Finding_id}-total-proposed-basic-salary`).text('$' + totalProposedBasicSalary.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            months.forEach((month, index) => {
                $(`#${Finding_id}-total-${month}`).text('$' +  Math.round(combinedMonthlyTotals[index]).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            });

            // Update grand total display and hidden input
            let grandTotal = combinedMonthlyTotals.reduce((sum, current) => sum + current, 0) + vacantTotal;
            grandTotal= Math.round(grandTotal);
            $(`#grand_total_department_${Finding_id}`).val(grandTotal); // grand_total_department_

            $(`.grand_total_department_${Finding_id}`).text('$' + grandTotal.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $(`#grand_total_department_${Finding_id}`).val(grandTotal);


            });

        }
        GetTotalValue();
        $(document).on('click', '.updateBudgetData', function(e) {
            e.preventDefault();

            let position_of_row = $(this).data('id');
            let inc_position = $(this).data('position');

            let hasNegative = false;

            // Check Basic Salary
            $(`input[name^="BasicSalary[${position_of_row}]"]`).each(function() {
                if (parseFloat($(this).val()) < 0) {
                    hasNegative = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Check Proposed Salary
            $(`input[name^="ProposedBasicsalary[${position_of_row}]"]`).each(function() {
                if (parseFloat($(this).val()) < 0) {
                    hasNegative = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Check Manning Month-wise Salaries
            $(`input[name^="manning_child[${position_of_row}]"]`).each(function() {
                if (parseFloat($(this).val()) < 0) {
                    hasNegative = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (hasNegative) {
                toastr.error('Negative values are not allowed in salary fields.', 'Validation Error', {
                    positionClass: 'toast-bottom-right'
                });
                return false; // Prevent submission
            }

            // Proceed with your AJAX here (unchanged)
            let basic_salary = [];
            let ProposedBasicsalary = [];
            let month_data = {};
            let i = 1;
            let grand_total = $("#grand_total_department_" + inc_position).val();

            $(`input[name^="BasicSalary[${position_of_row}]"]`).each(function () {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());
                basic_salary.push({ smrpChildId: smrpChildId, value: value });
            });

            $(`input[name^="ProposedBasicsalary[${position_of_row}]"]`).each(function () {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());
                ProposedBasicsalary.push({ smrpChildId: smrpChildId, value: value });
            });

            $(`input[name^="manning_child[${position_of_row}]"]`).each(function () {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());

                if (!month_data[smrpChildId]) {
                    month_data[smrpChildId] = [];
                    i = 1;
                }

                month_data[smrpChildId].push({
                    month: i,
                    salary: value
                });

                i++;
            });

            $.ajax({
                url: "{{ route('resort.UpdateResortPositionWise') }}",
                method: 'POST',
                data: {
                    basic_salary: basic_salary,
                    ProposedBasicsalary: ProposedBasicsalary,
                    month_data: month_data,
                    grand_total: grand_total,
                    "_token": $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    let errorMessage = "An error occurred while updating the budget.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

    });
</script>
@endsection
