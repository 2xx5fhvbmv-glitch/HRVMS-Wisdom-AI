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
            <div class="dwb-card">
                <div class="dwb-hd">
                    <div class="dwb-dept">{{$department->name}}</div>
                    <input type="hidden" class="grand_total" value="0">
                    <input type="hidden" id="hdn_grand_total" value="0">
                    <input type="hidden" id="hdn_budget_id" value="{{ $Budget_id }}">
                    <input type="hidden" id="hdn_department_id" value="{{ $dept_id }}">
                    <span class="dwb-badge">
                        <span class="k">Budget</span>
                        <b id="grand_total">00.00</b>
                    </span>
                    <span class="dwb-sp"></span>
                    {{-- "WSB : $11,985" was a hardcoded mockup leftover that confused
                         every resort into thinking it was their actual Wisdom Suggested
                         Budget. Removed until the dynamic value is wired through
                         (the real WSB lives on /resort/budget/compare-budget/{dept}/{budget}). --}}
                    {{-- Bulk Increment button commented out per request — the bulk-incrementView-modal
                         still exists below but is unreachable from the UI until this is restored. --}}
                    {{-- <a href="#bulk-incrementView-modal" data-bs-toggle="modal" class="btn btn-xs btn-themeBlue mx-2">Bulk Increment</a> --}}
                    <a href="{{ route('resort.budget.comparebudget', ['id' => $department->id,'budgetid'=>$Budget_id]) }}" class="btn btn-xs dwb-btn-compare" @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.comparebudget',config('settings.resort_permissions.view')) == false) d-none @endif>
                        Compare
                    </a>
                </div>
                @php
                    // Get the current year and increment it to get the next year
                    $nextYear = date('Y', strtotime('+1 year'));
                @endphp
                <div class="dwb-wrap">
                    <table id="dwb-positions-table" class="dwb-table">
                        <thead>
                            <tr>
                                <th class="col-act"></th>
                                <th class="col-pos">Position</th>
                                <th>No.</th>
                                <th>Employee</th>
                                <th>Rank</th>
                                <th>Nation</th>
                                <th class="num">Current Basic</th>
                                <th class="num">Proposed Basic {{$nextYear}}</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    @php
                                        // Get the current year and increment it to get the next year
                                        $nextYear = date('Y', strtotime('+1 year'));

                                        // Format as abbreviated month and year (e.g., Jan-2025)
                                        $yearMonth = date("M-Y", mktime(0, 0, 0, $i, 1, $nextYear));
                                    @endphp
                                    <th class="num mstart">{{ $yearMonth }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($getPositions as $pos)
                                @php $dwbGroupHead = false; @endphp
                                @if(isset($pos->employees) && !empty($pos->employees) && count($pos->employees) > 0)
                                    @foreach($pos->employees as $employee)
                                        <tr class="dwb-row-emp {{ !$dwbGroupHead ? 'dwb-gf' : '' }}" data-employee-id="{{$employee->Empid}}" data-child-id="{{ $employee->vacantData->smrp_child_id ?? '' }}">
                                            <td class="col-act">
                                                <span class="dwb-act-view">
                                                    <button type="button" class="dwb-iconbtn ed dwb-edit-btn" title="Edit">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="" width="14" height="14">
                                                    </button>
                                                    <button type="button" class="dwb-iconbtn x dwb-del-btn" title="Delete">
                                                        <img src="{{ URL::asset('resorts_assets/images/trash-red.svg')}}" alt="" width="14" height="14">
                                                    </button>
                                                </span>
                                                <span class="dwb-act-edit">
                                                    <button type="button" class="dwb-iconbtn ok dwb-save-btn" title="Save">
                                                        <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg')}}" alt="" width="14" height="14">
                                                    </button>
                                                    <button type="button" class="dwb-iconbtn x dwb-cancel-btn" title="Cancel">
                                                        <img src="{{ URL::asset('resorts_assets/images/cancel.svg')}}" alt="" width="14" height="14">
                                                    </button>
                                                </span>
                                            </td>
                                            <td class="col-pos">
                                                @if(!$dwbGroupHead)
                                                    <div class="dwb-pos-t">{{ $pos->position_title }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$dwbGroupHead)
                                                    <span class="dwb-no-pos">{{ $pos->headcount ?? '00' }}</span>
                                                @endif
                                            </td>
                                            <td class="dwb-emp">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                            <td>
                                                @php
                                                    $Rank = config('settings.Position_Rank');
                                                    $AvailableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                                @endphp
                                                <span class="dwb-rank">{{$AvailableRank}}</span>
                                            </td>
                                            <td class="dwb-nat">{{ $employee->nationality }}</td>
                                            <td class="num dwb-cur-cell">
                                                <span class="dwb-money soft dwb-view {{ $employee->basic_salary == 0 ? 'zero' : '' }}">{{ number_format($employee->basic_salary, 2) }}</span>
                                                <input type="number" class="dwb-cin num dwb-edit dwb-in-current" value="{{ $employee->basic_salary }}" min="0" step="0.01" max="9999999999.99">
                                            </td>
                                            <td class="num dwb-prop-cell">
                                                <span class="dwb-money soft dwb-view {{ $employee->Proposed_Basic_salary == 0 ? 'zero' : '' }}">{{ number_format($employee->Proposed_Basic_salary, 2) }}</span>
                                                <input type="number" class="dwb-cin num dwb-edit dwb-in-proposed" value="{{ $employee->Proposed_Basic_salary }}" min="0" step="0.01" max="9999999999.99">
                                            </td>

                                            {{-- Monthly Budget Data for Employee --}}
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
                                                            // No base salary → budget $0, not fabricated overhead.
                                                            // (Operational/expat costs on a $0 salary were rendering
                                                            // as a dummy $180.) Filling the role is recommended via
                                                            // the AI "justified reason" on compare-budget, not a
                                                            // made-up salary here.
                                                            $totalMothwisecost = ((float) $monthlySalary <= 0)
                                                                ? 0
                                                                : (float) Common::CheckemployeeBudgetCost($employee->nationality, $employee->resort_id, $monthlySalary);
                                                        }
                                                        $ak++;
                                                @endphp

                                                <td class="num mstart dwb-month-cell">
                                                    {{-- Salary Increment Details flow disabled per request. The trigger icon is hidden here; the modal markup further below is also wrapped in a Blade comment so it does not render. Re-enable by uncommenting both. --}}
                                                    {{-- <a href="#incrementView-modal" data-bs-toggle="modal" class="btn-tableIcon btnIcon-skyblue month-{{$i}}">
                                                        <img src="{{ URL::asset('resorts_assets/images/increment.svg') }}"/>
                                                    </a> --}}
                                                    <span class="dwb-money soft dwb-view {{ $totalMothwisecost == 0 ? 'zero' : '' }}">{{ number_format($totalMothwisecost,2) }}</span>
                                                    <input type="number" class="dwb-cin num dwb-edit dwb-in-month" value="{{ $totalMothwisecost }}" min="0" step="0.01" max="9999999999.99">
                                                </td>
                                            @endfor
                                        </tr>
                                        @php $dwbGroupHead = true; @endphp
                                    @endforeach
                                @endif
                                {{-- Aggregated (read-only) vacant-slot summary row for this position --}}
                                @if($pos->vacantcount)
                                    @php
                                        $maxVacantCount = 0;
                                    @endphp
                                    <tr class="dwb-row-vac {{ !$dwbGroupHead ? 'dwb-gf' : '' }}">
                                        <td class="col-act"></td>
                                        <td class="col-pos">
                                            @if(!$dwbGroupHead)
                                                <div class="dwb-pos-t">{{ $pos->position_title }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$dwbGroupHead)
                                                <span class="dwb-no-pos">{{ $pos->headcount ?? '00' }}</span>
                                            @endif
                                        </td>
                                        <td colspan="3"><span class="dwb-vac"><span class="d"></span>Vacant position</span></td>
                                        <td class="num"></td>
                                        <td class="num"></td>
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
                                                    $maxVacantCount = $vacantcount;
                                                }
                                                else
                                                {
                                                    $vacantCost = null;
                                                }
                                            @endphp
                                            <td class="num mstart dwb-month-cell">
                                                @if($vacantCost !== null)
                                                    <input type="hidden" class="vacant" value="{{ number_format($vacantCost, 2) }}">
                                                    <span class="dwb-est">{{ Common::GetResortCurrencySymbol() }}{{ number_format($vacantCost, 2) }}</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                    @php $dwbGroupHead = true; @endphp
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="col-act"></th>
                                <th class="col-pos lbl">Total</th>
                                <th id="dwb-total-positions">0</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th class="num" id="dwb-total-current">{{ Common::GetResortCurrencySymbol() }}0.00</th>
                                <th class="num" id="dwb-total-proposed">{{ Common::GetResortCurrencySymbol() }}0.00</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="num mstart dwb-total-month">{{ Common::GetResortCurrencySymbol() }}0.00</th>
                                @endfor
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

{{-- Salary Increment Details modal disabled per request alongside the icon trigger above. Left intact (just wrapped in a Blade comment block) so the field set, JS handlers and route wiring stay in place for restoration. To bring it back, remove the wrapping comment below AND uncomment the trigger icon in the table cell earlier in this file. --}}
{{--
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
--}}

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
@include('resorts.budget._department_wise_budget_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        function calculateTotals() {
            let totalPositions = 0;
            let totalCurrent = 0;
            let totalProposed = 0;
            let monthlyTotals = Array(12).fill(0);

            $('#dwb-positions-table tbody tr.dwb-gf .dwb-no-pos').each(function () {
                totalPositions += parseInt($(this).text()) || 0;
            });

            $('#dwb-positions-table tbody tr.dwb-row-emp').each(function () {
                const row = $(this);
                totalCurrent += parseFloat(row.find('.dwb-in-current').val()) || 0;
                totalProposed += parseFloat(row.find('.dwb-in-proposed').val()) || 0;
                row.find('.dwb-month-cell').each(function (i) {
                    monthlyTotals[i] += Math.round(parseFloat($(this).find('.dwb-in-month').val())) || 0;
                });
            });

            $('#dwb-positions-table tbody tr.dwb-row-vac').each(function () {
                $(this).find('.dwb-month-cell').each(function (i) {
                    const v = $(this).find('input.vacant').val();
                    if (v) monthlyTotals[i] += Math.round(parseFloat(v)) || 0;
                });
            });

            const grandTotal = monthlyTotals.reduce((sum, current) => sum + current, 0);

            $('#dwb-total-positions').text(totalPositions);
            $('#dwb-total-current').text(formatAmount(Math.round(totalCurrent), 'USD'));
            $('#dwb-total-proposed').text(formatAmount(Math.round(totalProposed), 'USD'));
            $('.dwb-total-month').each(function (i) {
                $(this).text(formatAmount(monthlyTotals[i], 'USD'));
            });

            $('.grand_total').val(grandTotal);
            $('#hdn_grand_total').val(grandTotal);
            $('#grand_total').text(formatAmount(grandTotal, 'USD'));

            // Update the parent table with the grand total
            updateParentTotal(grandTotal);
        }

        function updateParentTotal(grandTotal) {
            $.ajax({
                url: "{{ route('resort.budget.updateParentTotal') }}",
                method: 'PUT',
                data: {
                    Budget_id: $('#hdn_budget_id').val(),
                    Department_id: $('#hdn_department_id').val(),
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

        function dwbFormatDecimal(v) {
            return (Math.round(v * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function dwbCloseEditing() {
            $('#dwb-positions-table tr.dwb-editing').removeClass('dwb-editing');
        }

        function dwbSaveRow(row) {
            const childId = row.data('child-id');
            if (!childId) {
                toastr.error('This row cannot be saved — missing reference id.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }

            const current = parseFloat(row.find('.dwb-in-current').val()) || 0;
            const proposed = parseFloat(row.find('.dwb-in-proposed').val()) || 0;
            const monthData = [];
            row.find('.dwb-month-cell').each(function (i) {
                monthData.push({ month: i + 1, salary: Math.round(parseFloat($(this).find('.dwb-in-month').val()) || 0) });
            });

            const url = "{{ route('resort.budget.update', ['id' => '__ID__']) }}".replace('__ID__', childId);

            $.ajax({
                url: url,
                method: 'PUT',
                data: {
                    basic_salary: current,
                    proposed_basic_salary: proposed,
                    month_data: monthData,
                    "_token": "{{ csrf_token() }}"
                },
                success: function (response) {
                    row.find('.dwb-cur-cell .dwb-view').text(dwbFormatDecimal(current)).toggleClass('zero', current === 0);
                    row.find('.dwb-prop-cell .dwb-view').text(dwbFormatDecimal(proposed)).toggleClass('zero', proposed === 0);
                    row.find('.dwb-month-cell').each(function (i) {
                        const v = monthData[i].salary;
                        $(this).find('.dwb-view').text(dwbFormatDecimal(v)).toggleClass('zero', v === 0);
                    });
                    row.removeClass('dwb-editing');
                    calculateTotals();
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    let errorMessage = 'Failed to save data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        }

        // Initial calculation
        calculateTotals();

        $('#dwb-positions-table').on('click', '.dwb-edit-btn', function () {
            dwbCloseEditing();
            const row = $(this).closest('tr');
            row.find('.dwb-edit').each(function () {
                $(this).data('orig', $(this).val());
            });
            row.addClass('dwb-editing');
            row.find('.dwb-in-current').trigger('focus');
        });

        $('#dwb-positions-table').on('click', '.dwb-cancel-btn', function () {
            const row = $(this).closest('tr');
            row.find('.dwb-edit').each(function () {
                $(this).val($(this).data('orig'));
            });
            row.removeClass('dwb-editing');
        });

        $('#dwb-positions-table').on('click', '.dwb-save-btn', function () {
            dwbSaveRow($(this).closest('tr'));
        });

        $('#dwb-positions-table').on('keydown', '.dwb-edit', function (e) {
            const row = $(this).closest('tr');
            if (e.key === 'Enter') {
                e.preventDefault();
                dwbSaveRow(row);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                row.find('.dwb-edit').each(function () {
                    $(this).val($(this).data('orig'));
                });
                row.removeClass('dwb-editing');
            }
        });

        $('#dwb-positions-table').on('click', '.dwb-del-btn', function () {
            toastr.info('Removing a budget line item isn\'t available yet — please contact support.', 'Not available', {
                positionClass: 'toast-bottom-right'
            });
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
