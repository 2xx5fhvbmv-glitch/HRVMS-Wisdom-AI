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
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                @if($available_rank == "HR")
                    <div class="col-auto">


                        <div class="d-flex justify-content-end">
                            <a href="#revise-budgetmodal " data-bs-toggle="modal" class="btn btn-white ms-3 revise-budgetmodal">Revise
                                Budget</a>
                        </div>

                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="card">
                    <div class="card-title">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-start align-items-center">
                                    <h3>{{$department->name}}</h3>
                                    <input type="hidden" class="grand_total" value="0" >

                                    <span class="badge badge-dark ms-3" id="grand_total">
                                        Budget: 00.00
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="d-flex justify-content-sm-end align-items-center">
                                    <a href="#" class="text-lightblue me-sm-3  fw-500 fs-13">WSB : $11,985</a>
                                    <a href="#bulk-incrementView-modal" data-bs-toggle="modal" class="btn btn-xs btn-themeBlue mx-2">Bulk Increment</a>
                                     <a href="{{ route('resort.budget.comparebudget', ['id' => $department->id,'budgetid'=>$Budget_id]) }}" class="btn btn-xs btn-coolblue order-sm-last me-sm-0 me-3" @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.comparebudget',config('settings.resort_permissions.view')) == false) d-none @endif>
                                        Compare
                                    </a> 
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        // Get the current year and increment it to get the next year
                        $nextYear = date('Y', strtotime('+1 year'));

                    @endphp
                    <div class="table-responsive">
                        <table id="filled-positions-table" class="table  w-100">

                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-nowrap">Positions</th>
                                    <th class="text-nowrap">No. of position</th>
                                    <th class="text-nowrap">Employee Name</th>
                                    <th class="text-nowrap w-120">Rank</th>
                                    <th class="text-nowrap">Nation</th>
                                    <th>Current Basic salary</th>
                                    <th>Proposed Basic Salary {{$nextYear}}</th>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            // Get the current year and increment it to get the next year
                                            $nextYear = date('Y', strtotime('+1 year'));

                                            // Format the month and year (e.g., 01-2025)
                                            $yearMonthValue = date("m-Y", mktime(0, 0, 0, $i, 1, $nextYear));

                                            // Format as abbreviated month and year (e.g., Jan-2025)
                                            $yearMonth = date("M-Y", mktime(0, 0, 0, $i, 1, $nextYear));
                                        @endphp
                                        <th class="text-nowrap w-120">{{ $yearMonth }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>

                                    @foreach($getPositions as $pos)


                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 editBudget-icon"
                                                        data-smrp-child-id="{{$pos->Position_id}}">

                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="" class="img-fluid" />
                                                    </a>
                                                    <a href="#" class="btn-lg-icon icon-bg-red" data-smrp-child-id="{{$pos->Position_id}}">
                                                        <img src="{{ URL::asset('resorts_assets/images/trash-red.svg')}}" alt="" class="img-fluid" />
                                                    </a>
                                                </div>
                                                <a href="#" class="btn btn-theme update-row-btn" data-smrp-child-id="{{$pos->Position_id}}">Submit</a>
                                            </td>
                                            <input type="hidden" id="hdn_grand_total" class="form-control">
                                            <input type="hidden" id="hdn_budget_id" value="{{ $Budget_id }}">
                                            <input type="hidden" id="hdn_department_id" value="{{ $dept_id }}">
                                            <td>{{ $pos->position_title }}</td>
                                            <td>{{ $pos->headcount ?? '00' }}</td>
                                            <td colspan="15" class="p-0">
                                                <table class="table m-0">
    

                                                    @if(isset($pos->employees) && !empty($pos->employees) && count($pos->employees) > 0)
                                                        @foreach($pos->employees as $employee)
                                                            <tr data-employee-id="{{$employee->Empid}}">
                                                                <!-- Employee Name, Rank, Nationality -->
                                                                <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                                                <td class="w-120">
                                                                    @php
                                                                        $Rank = config('settings.Position_Rank');
                                                                        $AvailableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                                                    @endphp
                                                                    {{$AvailableRank}}
                                                                </td>
                                                                <td>{{ $employee->nationality }}</td>
                                                                <td class="current-basic-salary">
                                                                    <div class="inputValue">{{ number_format($employee->basic_salary, 2) }}</div>
                                                                    <input type="number" class="form-control" name="BasicSalary[{{ $pos->Position_id}}][{{ $employee->vacantData->smrp_child_id}}][]"  value="{{ number_format($employee->basic_salary, 2) }}" min="0" step="0.01" max="9999999999.99">
                                                                </td>
                                                                <td class="proposed-basic-salary">
                                                                    <div class="inputValue">{{ number_format($employee->Proposed_Basic_salary, 2) }}</div>
                                                                    <input type="number" class="form-control" name="ProposedBasicsalary[{{ $pos->Position_id}}][{{ $employee->vacantData->smrp_child_id}}][]" value="{{ $employee->Proposed_Basic_salary }}" min="0" step="0.01" max="9999999999.99">
                                                                </td>

                                                                <!-- Monthly Budget Data for Employee -->
                                                                @php
                                                                    $lastIncrementMonth = (new DateTime($employee->incremented_date))->format('m');
                                                                    $basicSalary = $employee->basic_salary;
                                                                    $proposedSalary = $employee->Proposed_Basic_salary > 0 ? $employee->Proposed_Basic_salary : $basicSalary;
                                                                            if(isset($employee->vacantData->Months))
                                                                            {
                                                                                $monthdataCollection =  json_decode($employee->vacantData->Months);
                                                                            }
                                                                            else 
                                                                            {
                                                                                $monthdataCollection=array();
                                                                            }
                                                                            $ak=0;
                                                                @endphp

                                                                @for ($i = 1; $i <= 12; $i++)
                                                                    @php
                                                                        $monthlyData = DB::table('position_monthly_data')
                                                                                        ->where('position_id', $employee->Position_id)
                                                                                        ->where('month', $i)
                                                                                        ->where('manning_response_id', $pos->Budget_id)
                                                                                        ->first();

                                                                        $headcount = $monthlyData->headcount ?? 0;
                                                                        $vacantcount = $monthlyData->vacantcount ?? 0;
                                                                        $filledcount = $monthlyData->filledcount ?? 0;
                                                                        $monthlySalary = ($i < $lastIncrementMonth) ? $basicSalary : $proposedSalary;
                                                                            if(!empty($monthdataCollection) && $monthdataCollection[$ak]->month == $i)
                                                                            {
                                                                                $totalMothwisecost = (float)$monthdataCollection[$ak]->salary;
                                                                            
                                                                            }
                                                                            else 
                                                                            {
                                                                                $totalMothwisecost = (float)    Common::CheckemployeeBudgetCost($employee->nationality, $employee->resort_id, $monthlySalary);
                                                                            }
                                                                            $ak++;
                                                                    @endphp

                                                                    <td class="w-120 month-{{$i}}">
                                                                        <div class="inputValue">
                                                                            {{number_format($totalMothwisecost,2)}}
                                                                            <a href="#incrementView-modal" data-bs-toggle="modal" class="btn-tableIcon btnIcon-skyblue">
                                                                                <img src="{{ URL::asset('resorts_assets/images/increment.svg') }}"/>
                                                                            </a>
                                                                        </div>
                                                                        <input type="number" name= "manning_child[{{ $pos->Position_id}}][{{ $employee->vacantData->smrp_child_id }}][]"class="form-control" value="{{ $totalMothwisecost }}" min="0" step="0.01" max="9999999999.99">
                                                                    </td>
                                                                @endfor
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    <!-- Separate Row for Vacant Position Costs in Specific Months -->
                                                    @if($pos->vacantcount)
                                                        @php
                                                            $maxVacantCount = 0;
                                                        @endphp

                                                        <tr>
                                                            <td colspan="5">Vacant Positions</td>
                                                            @for($i = 1; $i <= 12; $i++)
                                                                @php
                                                                    if(isset($employee))
                                                                    {
                                                                        $monthlyData = DB::table('position_monthly_data')
                                                                         ->where('position_id', $employee->Position_id)
                                                                            ->where('month', $i)
                                                                            ->where('manning_response_id', $pos->Budget_id)
                                                                            ->first();
                                                                            
                                                                        $vacantcount = $monthlyData->vacantcount ?? 0;
                                                                    }
                                                                    else {
                                                                        $vacantcount = 0; // Default to 0 if no employee data
                                                                    }
                                                                   
                                                                    if ($vacantcount > $maxVacantCount)
                                                                    {
                                                                        // Calculate the difference from the max count
                                                                        $vacantDifference = $vacantcount - $maxVacantCount;
                                                                        $vacantCostArray = Common::CheckVacantBudgetCost($vacantDifference);
                                                                        $vacantCost = $vacantCostArray['total_cost'] ?? 0;
                                                                        echo "<td class='w-120 month-{$i}'>
                                                                            <input type='hidden' class='vacant' name='vacant[]' value='" . number_format($vacantCost, 2) . "'>
                                                                            <span class='badge badge-success'>Vacant  ({$vacantcount}) - {$vacantCost}</span>
                                                                        </td>";

                                                                        $maxVacantCount = $vacantcount;
                                                                    }
                                                                    else
                                                                    {
                                                                        echo "<td class='w-120 month-{$i}'></td>";
                                                                    }
                                                                @endphp
                                                            @endfor
                                                        </tr>

                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach

                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th>Total:</th>
                                    <th id="total-positions">0</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th id="total-current-basic-salary">$0</th>
                                    <th id="total-proposed-basic-salary">$0</th>
                                    <th id="total-jan-2024">$0</th>
                                    <th id="total-feb-2024">$0</th>
                                    <th id="total-mar-2024">$0</th>
                                    <th id="total-apr-2024">$0</th>
                                    <th id="total-may-2024">$0</th>
                                    <th id="total-jun-2024">$0</th>
                                    <th id="total-jul-2024">$0</th>
                                    <th id="total-aug-2024">$0</th>
                                    <th id="total-sep-2024">$0</th>
                                    <th id="total-oct-2024">$0</th>
                                    <th id="total-nov-2024">$0</th>
                                    <th id="total-dec-2024">$0</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>


            </div>
        </div>
    </div>
</div>

<!-- modal -->
<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ReviseBudget" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-20">
                        <p class="mb-0 fw-500 departmentBudget">
                        <input type="hidden" class="Budget_id" name="Budget_id" value="{{ $Budget_id }}"  >
                        <input type="hidden"  class="Budget_id" name="resort_id" value="{{ $resortId }}"  >
                        <input type="hidden"  class="Budget_id" name="Department_id" value="{{ $dept_id }}"  >
                        <input type="hidden"  class="Message_id" name="Message_id" value="{{ $Message_id }}"  >
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

<div class="modal fade" id="incrementView-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Salary Increment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="incrementForm">
                    <input type="hidden" id="Budget_id" value="{{ $Budget_id }}"  >
                    <input type="hidden" id="resortId" value="{{ $resortId }}"  >
                    <input type="hidden" id="dept_id" value="{{ $dept_id }}"  >
                    <div class="mb-3">
                        <label class="form-label">Current Salary</label>
                        <input type="text" class="form-control" id="current_salary" readonly>
                        <input type="hidden" class="form-control" id="employee_id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Increment Date</label>
                        <input type="text" class="form-control" id="date" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Increment Amount</label>
                        <input type="text" class="form-control" id="inAmound" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Increment Date</label>
                        <input type="text" class="form-control datepicker" id="nextIndate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Increment Amount</label>
                        <input type="number" step="0.01" class="form-control" id="nextInAmound">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Increment Percentage</label>
                        <input type="number" step="0.01" class="form-control" id="increment_percentage">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Salary</label>
                        <input type="text" class="form-control" id="new_salary" readonly>
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
                <form id="incrementForm">
                    <input type="hidden" id="Budget_id" value="{{ $Budget_id }}">
                    <input type="hidden" id="resortId" value="{{ $resortId }}">
                    <input type="hidden" id="dept_id" value="{{ $dept_id }}">

                    <div class="mb-3">
                        <label class="form-label">Increment Percentage</label>
                        <input type="number" step="0.01" class="form-control" id="bulk_increment_percentage" placeholder="Enter percentage">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Increment Amount</label>
                        <input type="number" step="0.01" class="form-control" id="bulk_increment_amount" placeholder="Enter amount">
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        function parseCurrency(value) {
            return parseFloat(value.replace(/,/g, '')); // Remove commas and convert to float
        }

        function calculateTotals() {
            let totalPositions = 0;
            let totalCurrentBasicSalary = 0;
            let totalProposedBasicSalary = 0;
            let monthlyTotals = Array(12).fill(0); // Array to hold total costs per month
            let grandTotal = 0;

            // Loop through each main position row
            $('#filled-positions-table tbody > tr').each(function () {
                const positionCount = parseInt($(this).find('td:eq(2)').text()) || 0;
                totalPositions += positionCount;

                $(this).find('table tr').each(function () {
                    const badgeExists = $(this).find('.badge-success').length;

                    if (badgeExists) {
                        for (let i = 1; i <= 12; i++) {
                            const monthCell = $(this).find(`.month-${i}`).find('.vacant');
                            if (monthCell.length) {
                                const vacantCost = parseCurrency(monthCell.val()) || 0;
                                monthlyTotals[i - 1] += Math.round(vacantCost);
                            }
                        }
                    } else {
                        const currentBasicSalary = parseCurrency($(this).find('.current-basic-salary .inputValue').text()) || 0;
                        const proposedBasicSalary = parseCurrency($(this).find('.proposed-basic-salary .inputValue').text()) || 0;

                        totalCurrentBasicSalary += currentBasicSalary;
                        totalProposedBasicSalary += proposedBasicSalary;

                        for (let i = 1; i <= 12; i++) {
                            const monthCell = $(this).find(`.month-${i} .inputValue`);
                            if (monthCell.length) {
                                const employeeCost = parseCurrency(monthCell.text()) || 0;
                                monthlyTotals[i - 1] += Math.round(employeeCost);
                            }
                        }
                    }
                });
            });

            // Calculate grand total as the sum of all monthly totals
            grandTotal = monthlyTotals.reduce((sum, current) => sum + current, 0);

            // Update footer totals
            $('#total-positions').text(totalPositions);
            totalCurrentBasicSalary = Math.round(totalCurrentBasicSalary);
            totalProposedBasicSalary = Math.round(totalProposedBasicSalary);
            $('#total-current-basic-salary').text('$' + totalCurrentBasicSalary.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            $('#total-proposed-basic-salary').text('$' + totalProposedBasicSalary.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            // Update monthly totals in the footer
            const months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            months.forEach((month, index) => {
                $(`#total-${month}-2024`).text('$' + monthlyTotals[index].toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            });

            $('.grand_total').val( grandTotal);

            $('#grand_total').text('$' + grandTotal.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            $('#hdn_grand_total').val(grandTotal);

            // Update the parent table with the grand total
            updateParentTotal(grandTotal);
        }

        function updateParentTotal(grandTotal) {
            $.ajax({
                url: "{{ route('resort.budget.updateParentTotal') }}", // You'll need to create this route
                method: 'PUT',
                data: {
                    Budget_id: $('#hdn_budget_id').val(), // Add this hidden input to your HTML
                    Department_id: $('#hdn_department_id').val(), // Add this hidden input to your HTML
                    Total_Department_budget: grandTotal
                },
                success: function(response) {
                    console.log('Parent total updated successfully');
                },
                error: function(xhr, status, error) {
                    console.error('Failed to update parent total:', error);
                }
            });
        }

        // Initial calculation
        calculateTotals();

        // Recalculate on input change
        $('#filled-positions-table').on('input', 'input', function() {
            calculateTotals();
        });

        $(".editBudget-icon").click(function () {
            $(this).parents("tr").addClass("inputShow");
        });

        $(".update-row-btn").click(function () {

            let grand_total = $(".grand_total").val();
            let position_of_row = $(this).data('smrp-child-id');

            let basic_salary = [];
            let ProposedBasicsalary = [];
            let month_data = {};
            let i = 1;

            $(`input[name^="BasicSalary[${position_of_row}]"]`).each(function() {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());
                basic_salary.push({ smrpChildId: smrpChildId, value: value });
            });

            $(`input[name^="ProposedBasicsalary[${position_of_row}]"]`).each(function() {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());
                ProposedBasicsalary.push({ smrpChildId: smrpChildId, value: value });
            });

            $(`input[name^="manning_child[${position_of_row}]"]`).each(function() {
                let smrpChildId = $(this).attr('name').match(/\[([0-9]+)\]\[\]/)[1];
                let value = Math.round($(this).val());

                if (!month_data[smrpChildId]) {
                    month_data[smrpChildId] = [];
                    i = 1;
                }

                month_data[smrpChildId].push({
                    month: i,
                    salary: Math.round(value)
                });

                i++;
            });

                $.ajax({
                    url: "{{ route('resort.UpdateResortPositionWise') }}",
                    method: 'post',
                    data: {
                        basic_salary: basic_salary,
                        ProposedBasicsalary: ProposedBasicsalary,
                        month_data: month_data,
                        grand_total:grand_total,
                    },
                    success: function (response) {

                        // // Update the values in the row for current and proposed basic salary
                        // row.find('.current-basic-salary').text(basic_salary);
                        // row.find('.proposed-basic-salary').text(proposed_basic_salary);
                        // row.find('#hdn_grand_total').val(grand_total);

                        // Loop through the month data and update the relevant columns
                        // month_data.forEach(function(monthData, index) {
                        //     row.find('.month-' + (index + 1)).text(monthData.salary);  // Assuming each month has a column with class 'month-1', 'month-2', etc.
                        // });

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        window.setTimeout(function() {
                            window.location.reload();  // This reloads the current page
                        }, 2000);

                    },
                    error: function (xhr, status, error) {
                        // Log detailed error for debugging
                        console.error('AJAX Error:', status, error);
                        
                        // Extract error message from response if available
                        let errorMessage = 'Failed to save data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Display user-friendly error notification
                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            // }
        });

        let currentEmployeeId = 0;
        let currentSalary = 0;
        let currentMonth = 0;

        // Initialize datepicker for increment dates
        $('.datepicker').datepicker({
            format: dt_format,
            autoclose: true,
            minDate: 0
        });

        function formatDate(isoDateString) {
            if(isoDateString){
                const date = new Date(isoDateString);
                console.log(date);
                // Get year, month, and day without timezone adjustment
                const year = date.getUTCFullYear();
                const month = String(date.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-indexed
                const day = String(date.getUTCDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }
        }

        // When increment icon is clicked
        $('.btnIcon-skyblue').on('click', function(e) {
            e.preventDefault();

            const row = $(this).closest('tr');
            currentEmployeeId = row.data('employee-id');
            currentSalary = parseFloat(row.find('.current-basic-salary .inputValue').text());
            currentMonth = $(this).closest('td').attr('class').split(' ')[1].split('-')[1];

            // Reset form
            $('#incrementView-modal form')[0].reset();

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
                        // console.log(formattedDate);
                        $('#date').val(formattedDate);
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
            let newSalary = 0;

            if ($(this).attr('id') === 'nextInAmound') {
                const calculatedPercentage = ((incrementAmount / currentSalary) * 100).toFixed(2);
                $('#increment_percentage').val(calculatedPercentage);
                newSalary = (currentSalary + incrementAmount).toFixed(2);
            } else if ($(this).attr('id') === 'increment_percentage') {
                const calculatedAmount = ((incrementPercentage / 100) * currentSalary).toFixed(2);
                $('#nextInAmound').val(calculatedAmount);
                newSalary = (currentSalary + parseFloat(calculatedAmount)).toFixed(2);
            }
            $('#new_salary').val(newSalary);
        });

        $('#incrementSubmit').on('click', function(e) {
            e.preventDefault();
            const incrementData = {
                employee_id: $('#employee_id').val(),
                previous_salary: currentSalary,
                new_salary: $('#new_salary').val(),
                increment_amount: $('#nextInAmound').val(),
                increment_percentage: $('#increment_percentage').val(),
                reason: $('#increment_reason').val(),
                effective_date: $('#nextIndate').val(),
                notes: $('#increment_notes').val(),
                Budget_id : $('#Budget_id').val(),
                dept_id : $('#dept_id').val(),
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
                        toastr.success('Salary increment saved successfully!',  "Success", {
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
        });

        // Disable one field if the other is filled
        $('#bulk_increment_percentage').on('input', function() {
            if ($(this).val()) {
                $('#bulk_increment_amount').prop('disabled', true).val(''); // Disable amount input
            } else {
                $('#bulk_increment_amount').prop('disabled', false); // Enable amount input
            }
        });

        // Disable one input when the other is filled
        $('#bulk_increment_amount').on('input', function() {
            if ($(this).val()) {
                $('#bulk_increment_percentage').prop('disabled', true).val(''); // Disable percentage input
            } else {
                $('#bulk_increment_percentage').prop('disabled', false); // Enable percentage input
            }
        });

        $('#bulk_increment_percentage').on('input', function() {
            if ($(this).val()) {
                $('#bulk_increment_amount').prop('disabled', true).val(''); // Disable amount input
            } else {
                $('#bulk_increment_amount').prop('disabled', false); // Enable amount input
            }
        });

        // Handle form submission
        $('#bulkincrementSubmit').on('click', function(e) {
            e.preventDefault();

            const incrementPercentage = parseFloat($('#bulk_increment_percentage').val());
            const incrementAmount = parseFloat($('#bulk_increment_amount').val());
            const budgetId = $('#Budget_id').val();
            const resortId = $('#resortId').val();
            const deptId = $('#dept_id').val();

            // Validate input
            if (!incrementPercentage && !incrementAmount) {
                toastr.error("Please enter either an increment percentage or an increment amount.","Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            if (incrementPercentage && incrementAmount) {
                toastr.error("Please enter only one of increment percentage or amount.","Error", {
                    positionClass: 'toast-bottom-right'
                });           
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
                        toastr.success(response.message,"Success", {
                            positionClass: 'toast-bottom-right'
                        });                         
                        $('#bulk-incrementView-modal').modal('hide');
                        // Optionally reload the page or refresh data
                    } else {
                        toastr.error(response.message,"Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred while applying increments.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status) {
                        errorMsg += ' Status: ' + xhr.status + ' ' + xhr.statusText;
                    }
                    
                    toastr.error(errorMsg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    console.log(xhr.responseText);
                }
            });
        });
    });
</script>
@endsection
