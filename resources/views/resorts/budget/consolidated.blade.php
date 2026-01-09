@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <form id="SendToFinance" method="POST"  >
                            @csrf
                            <input type="hidden" name="year" id="SendToFinanceYear" value="">
                            <p class="mb-0 fw-500 departmentBudget"></p>
                            @if($employeeRankPosition['position'] == 'HR')
                                <button type="submit" class="btn btn-theme SendToFinance" id="SendToFinanceButton" >Send To Finance</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'Finance')
                                <button type="submit" class="btn btn-theme SendToGM" id="SendToGMButton" >Send To GM</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'GM')
                                {{-- <button type="submit" class="btn btn-theme SendToCorporateOffice" >Send To Corporate Office</button> --}}
                                <button type="submit" class="btn btn-theme SendToCorporateOffice" >Aproove Budget</button>

                                <a href="#revise-budgetmodal"
                                    class="open-revise-modal btn btn-white ms-3"
                                    style="background: #F5F8F8;"
                                    data-budget_id="{{ $deptData->Budget_id }}"
                                    data-dept_id="{{ $deptData->department->id }}"
                                    data-bs-toggle="modal">
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                        </span>
                                </a>
                            @endif
                            {{-- @if($employeeRankPosition['position'] == 'Corporate Office')
                                <button type="submit" class="btn btn-theme SendToHR" >Send To HR</button>
                            @endif --}}
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <select class="form-select" name="year" id="year" onchange="fetchConsolidatedBudget(this.value)">
                            <option>Select Year</option>
                            {{-- Years will be dynamically populated --}}
                        </select>
                    </div>
                    <!-- <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                        <div class="input-group">
                            <input type="sh" class="form-control searchPermission" placeholder="Search">
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="viewBudget-accordion" id="accordionViewBudget">
                @if(!empty($MainArray))
                    @php $iteation=1; @endphp
                        @foreach ($MainArray as $key => $value)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $iteation }}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapse{{ $iteation }}" aria-expanded="true" aria-controls="collapse{{ $iteation }}">
                                        <h3>{{ $key }}</h3>
                                        <span class="badge badge-dark">Budget: $ @if(array_key_exists($key,$DepartmentTotal))  {{ $DepartmentTotal[$key]}} @endif</span>
                                        <a href="#" class="text-lightblue fw-500 fs-13">WSB: $11,985</a>
                                        <!-- <a href="#" class="btn btn-xs btn-coolblue">compare</a> -->
                                    </button>
                                </h2>
                                <div id="collapse{{ $iteation }}" class="accordion-collapse collapse {{ ($iteation == 1)  ? 'show':''}} " aria-labelledby="heading{{ $iteation }}"
                                    data-bs-parent="#accordionViewBudget">
                                    <div class="table-responsive">
                                        <table id="department-budget-table" class="table table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th class="text-nowrap">Positions</th>
                                                    <th class="text-nowrap">No. of position</th>
                                                    <th class="text-nowrap w-120">Rank</th>
                                                    <th class="text-nowrap">Nation</th>
                                                    <th>Current Basic Salary</th>
                                                    @foreach ($header as $h)
                                                        <th>{{ $h }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($value as $item)
                                                    <tr>
                                                        @foreach ($item as $key=>$i)
                                                            <td class="text-nowrap">
                                                                {{ $i }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @php $iteation++; @endphp
                    @endforeach
                @endif
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
            <form id="ReviseBudget">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-20">
                        <input type="hidden" id="budget_id" name="budget_id" value="">
                        <input type="hidden" id="department_id" name="department_id" value="">
                        @php
                            $manning_request =  config('settings.manning_request');
                            $manning_request = array_key_exists('msg3', $manning_request) ? $manning_request['msg3'] : '' ;
                        @endphp
                        <textarea class="form-control" name="ReviseBudgetComment" id="ReviseBudgetComment" rows="7" placeholder="Add Comment Regarding Revision">{{ $manning_request }}</textarea>
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
    // Store MVR to Dollar rate globally
    @php
        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        // MVRtoDoller field stores: 1 MVR = X USD (e.g., 0.065 means 1 MVR = 0.065 USD)
        $mvrToDollarRate = $resortSettings->MVRtoDoller ?? 0.065;
    @endphp
    window.mvrToDollarRate = {{ $mvrToDollarRate }};

    function populateYears() {
        const yearSelect = document.getElementById('year');
        const currentYear = new Date().getFullYear() + 1;
        const startYear = currentYear - 20;

        while (yearSelect.options.length > 1) {
            yearSelect.remove(1);
        }

        for (let year = currentYear; year >= startYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
    }

    function fetchConsolidatedBudget(selectedYear) {

        document.getElementById('SendToFinanceYear').value = selectedYear;
        const resortId = @json($resortId); // Ensure this is set correctly
        const url = "{{ route('resort.budget.viewconsolidated', ':resortId') }}".replace(':resortId', resortId);

        if (selectedYear) {
            $.ajax({
                url: url, // Use the generated URL
                type: 'GET',
                data: { year: selectedYear },
                success: function(response) {
                    $('#accordionViewBudget').html(response.html); // Update this to match your HTML structure
                    if (response.isBudgetCompleted === true) {
                        $("#SendToFinanceButton").prop("disabled", true);
                        $("#SendToGMButton").prop("disabled", true);
                    } else {
                        $("#SendToFinanceButton").prop("disabled", false);
                        $("#SendToGMButton").prop("disabled", false);
                    }

                    // Calculate all totals after content loads
                    setTimeout(function() {
                        if (typeof window.recalculateAllTotals === 'function') {
                            window.recalculateAllTotals();
                        }
                    }, 500);
                },
                error: function(xhr) {
                    console.error('Error fetching data:', xhr);
                    alert('Failed to load budget data. Please try again.');
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        populateYears();
        
        // Handle revise budget modal button click
        $(document).on('click', '.open-revise-modal', function() {
            const budgetId = $(this).data('budget_id');
            const deptId = $(this).data('dept_id');
            
            $('#budget_id').val(budgetId);
            $('#department_id').val(deptId);
        });
        
        // Revise Budget form submission handler
        $('#ReviseBudget').validate({
            rules: {
                ReviseBudgetComment: {
                    required: true,
                }
            },
            messages: {
                ReviseBudgetComment: {
                    required: "Please Add Revise Budget Comment.",
                }
            },
            submitHandler: function(form, event) {
                if (event) {
                    event.preventDefault();
                }
                
                $.ajax({
                    url: "{{ route('resort.ReviseBudget.manning.notification') }}",
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#revise-budgetmodal').modal('hide');
                            $(".open-revise-modal").prop('disabled', true);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // Reload the page to reflect changes
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(response) {
                        let errors = response.responseJSON;
                        let errs = '';
                        
                        if (errors && errors.errors) {
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                        } else {
                            errs = 'An unexpected error occurred. Please try again.';
                        }
                        
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });


    // Define budget URLs globally
    const budgetSaveUrl = '{{ route("resort.budget.saveCostAssignment", $resortId) }}';
    const budgetGetConfigUrl = '{{ route("resort.budget.getConfiguration", $resortId) }}';

    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit button click using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.editBudget-icon')) {
                e.preventDefault();
                const btn = e.target.closest('.editBudget-icon');

                const departmentId = btn.getAttribute('data-department-id');
                const departmentName = btn.getAttribute('data-department-name');
                const positionName = btn.getAttribute('data-position-name');
                const positionId = btn.getAttribute('data-position-id');
                const tableType = btn.getAttribute('data-table-type') || 'employee';
                const employeeId = btn.getAttribute('data-employee-id') || '';
                const vacantIndex = btn.getAttribute('data-vacant-index') || '';

                const modalDept = document.getElementById('modalDepartmentName');
                const modalPos = document.getElementById('modalPositionName');
                const modalType = document.getElementById('modalTableType');

                if (modalDept) modalDept.textContent = departmentName;
                if (modalPos) modalPos.textContent = positionName;
                if (modalType) modalType.textContent = tableType.charAt(0).toUpperCase() + tableType.slice(1);

                document.getElementById('formDepartmentId').value = departmentId;
                document.getElementById('formPositionId').value = positionId;
                document.getElementById('formTableType').value = tableType;
                document.getElementById('formEmployeeId').value = employeeId;
                document.getElementById('formVacantIndex').value = vacantIndex;

                const mvrRateInput = document.getElementById('mvrToDollarRate');
                if (mvrRateInput) {
                    mvrRateInput.value = window.mvrToDollarRate;
                }

                const basicSalaryInput = document.getElementById('formBasicSalary');
                const currentSalaryInput = document.getElementById('formCurrentSalary');
                if (basicSalaryInput) basicSalaryInput.value = '';
                if (currentSalaryInput) currentSalaryInput.value = '';

                document.querySelectorAll('.budget-cost-checkbox').forEach(function(checkbox) {
                    checkbox.checked = false;
                });

                loadExistingConfiguration(departmentId, positionId, tableType, employeeId, vacantIndex);
                calculateTotal();
            }

            if (e.target && e.target.id === 'submitBudgetCostAssignment') {
                e.preventDefault();
                submitBudgetConfiguration();
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('budget-cost-checkbox') ||
                e.target.classList.contains('budget-cost-amount') ||
                e.target.classList.contains('budget-cost-currency')) {
                calculateTotal();
            }
        });

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('budget-cost-amount')) {
                calculateTotal();
            }
        });

        function calculateTotal() {
            let total = 0;
            // MVRtoDoller field stores: 1 MVR = X USD (e.g., 0.065 means 1 MVR = 0.065 USD)
            const mvrToDollarRate = window.mvrToDollarRate || 0.065;

            document.querySelectorAll('.budget-cost-checkbox').forEach(function(checkbox) {
                if (checkbox.checked) {
                    const costId = checkbox.getAttribute('data-cost-id');
                    const amountInput = document.querySelector('.budget-cost-amount[data-cost-id="' + costId + '"]');
                    const currencySelect = document.querySelector('.budget-cost-currency[data-cost-id="' + costId + '"]');

                    if (amountInput && currencySelect) {
                        const amount = parseFloat(amountInput.value) || 0;
                        const currency = currencySelect.value;

                        let amountInUSD = amount;
                        if (currency === 'MVR') {
                            // MVRtoDoller field stores: 1 MVR = X USD
                            // Formula: USD = MVR × MVRtoDoller
                            amountInUSD = amount * mvrToDollarRate;
                        }

                        total += amountInUSD;
                    }
                }
            });

            const totalElement = document.getElementById('totalSelectedAmount');
            if (totalElement) {
                totalElement.textContent = total.toFixed(2);
            }
        }

        function submitBudgetConfiguration() {
            const form = document.getElementById('budgetCostAssignmentForm');
            if (!form) {
                console.error('Form not found');
                return;
            }

            const formData = new FormData(form);

            const checkedItems = [];
            document.querySelectorAll('.budget-cost-checkbox:checked').forEach(function(checkbox) {
                const costId = checkbox.getAttribute('data-cost-id');
                const amountInput = document.querySelector('.budget-cost-amount[data-cost-id="' + costId + '"]');
                const currencySelect = document.querySelector('.budget-cost-currency[data-cost-id="' + costId + '"]');

                if (amountInput && currencySelect) {
                    checkedItems.push({
                        cost_id: costId,
                        value: amountInput.value,
                        currency: currencySelect.value
                    });
                }
            });

            if (checkedItems.length === 0) {
                alert('Please select at least one budget cost item.');
                return;
            }

            const basicSalary = document.getElementById('formBasicSalary')?.value || '';
            const currentSalary = document.getElementById('formCurrentSalary')?.value || '';
            const selectedYear = document.getElementById('year')?.value || document.getElementById('SendToFinanceYear')?.value || new Date().getFullYear();

            const submitData = {
                _token: formData.get('_token'),
                department_id: formData.get('department_id'),
                position_id: formData.get('position_id'),
                table_type: formData.get('table_type'),
                employee_id: formData.get('employee_id'),
                vacant_index: formData.get('vacant_index'),
                basic_salary: basicSalary,
                current_salary: currentSalary,
                year: selectedYear,
                budget_costs: checkedItems
            };

            console.log('Submitting data:', submitData);

            fetch(budgetSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': formData.get('_token')
                },
                body: JSON.stringify(submitData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    alert('Budget cost configuration saved successfully!');
                    const modalElement = document.getElementById('budgetCostModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }

                    if (data.data) {
                        updateBudgetTableRow(data.data);
                    }

                    setTimeout(function() {
                        window.recalculateAllTotals();
                    }, 100);
                } else {
                    alert('Error: ' + (data.message || 'Failed to save budget cost configuration'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the budget cost configuration: ' + error.message);
            });
        }

        function loadExistingConfiguration(departmentId, positionId, tableType, employeeId, vacantIndex) {
            const selectedYear = document.getElementById('year')?.value || document.getElementById('SendToFinanceYear')?.value || new Date().getFullYear();

            fetch(budgetGetConfigUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    position_id: positionId,
                    table_type: tableType,
                    employee_id: employeeId,
                    vacant_index: vacantIndex,
                    year: selectedYear
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.configuration) {
                    const config = data.configuration;

                    if (config.basic_salary) {
                        document.getElementById('formBasicSalary').value = config.basic_salary;
                    }
                    if (config.current_salary) {
                        document.getElementById('formCurrentSalary').value = config.current_salary;
                    }

                    if (config.costs && Array.isArray(config.costs)) {
                        config.costs.forEach(cost => {
                            const checkbox = document.querySelector('.budget-cost-checkbox[data-cost-id="' + cost.resort_budget_cost_id + '"]');
                            const amountInput = document.querySelector('.budget-cost-amount[data-cost-id="' + cost.resort_budget_cost_id + '"]');
                            const currencySelect = document.querySelector('.budget-cost-currency[data-cost-id="' + cost.resort_budget_cost_id + '"]');

                            if (checkbox) checkbox.checked = true;
                            if (amountInput) amountInput.value = cost.value;
                            if (currencySelect) currencySelect.value = cost.currency;
                        });

                        calculateTotal();
                    }
                }
            })
            .catch(error => {
                console.error('Error loading configuration:', error);
            });
        }

        function updateBudgetTableRow(data) {
            if (!data) return;

            const rowIdentifier = data.table_type === 'employee'
                ? `employee-${data.employee_id}`
                : `vacant-${data.position_id}-${data.vacant_index}`;

            const row = document.querySelector(`tr[data-row-id="${rowIdentifier}"]`);
            if (!row) {
                console.log('Row not found:', rowIdentifier);
                return;
            }

            const basicSalaryCell = row.querySelector('.basic-salary-cell');
            if (basicSalaryCell && data.basic_salary) {
                basicSalaryCell.setAttribute('data-value', data.basic_salary);
                basicSalaryCell.textContent = '$' + parseFloat(data.basic_salary).toFixed(2);
            }

            const currentSalaryCell = row.querySelector('.current-salary-cell');
            if (currentSalaryCell && data.current_salary) {
                currentSalaryCell.setAttribute('data-value', data.current_salary);
                currentSalaryCell.textContent = '$' + parseFloat(data.current_salary).toFixed(2);
            }

            if (data.costs && Array.isArray(data.costs)) {
                data.costs.forEach(cost => {
                    const costCell = row.querySelector(`.cost-cell[data-cost-id="${cost.resort_budget_cost_id}"]`);
                    if (costCell) {
                        // MVRtoDoller field stores: 1 MVR = X USD, so multiply
                        const valueInUSD = cost.currency === 'MVR' ? cost.value * data.mvr_to_dollar_rate : cost.value;
                        const displaySymbol = cost.currency === 'MVR' ? 'MVR ' : '$';
                        const displayValue = cost.currency === 'MVR' ? cost.value : valueInUSD;

                        costCell.setAttribute('data-value', valueInUSD);
                        costCell.setAttribute('data-currency', cost.currency);
                        costCell.textContent = displaySymbol + parseFloat(displayValue).toFixed(2);
                    }
                });
            }
        }

        window.recalculateAllTotals = function() {
            document.querySelectorAll('.position-accordion').forEach(position => {
                recalculatePositionTotal(position);
            });

            document.querySelectorAll('.section-accordion').forEach(section => {
                recalculateSectionTotal(section);
            });

            document.querySelectorAll('.department-accordion').forEach(department => {
                recalculateDepartmentTotal(department);
            });

            document.querySelectorAll('.division-accordion').forEach(division => {
                recalculateDivisionTotal(division);
            });
        };

        function recalculatePositionTotal(positionElement) {
            const table = positionElement.querySelector('.table-sticky tbody');
            if (!table) return 0;

            let totalBasicSalary = 0;
            let totalCurrentSalary = 0;
            const costTotals = {};

            const rows = table.querySelectorAll('tr:not(.table-secondary)');
            rows.forEach(row => {
                const basicSalaryCell = row.querySelector('.basic-salary-cell');
                const currentSalaryCell = row.querySelector('.current-salary-cell');

                if (basicSalaryCell) {
                    totalBasicSalary += parseFloat(basicSalaryCell.getAttribute('data-value')) || 0;
                }

                if (currentSalaryCell) {
                    totalCurrentSalary += parseFloat(currentSalaryCell.getAttribute('data-value')) || 0;
                }

                row.querySelectorAll('.cost-cell').forEach(costCell => {
                    const costId = costCell.getAttribute('data-cost-id');
                    const value = parseFloat(costCell.getAttribute('data-value')) || 0;
                    if (costId) {
                        costTotals[costId] = (costTotals[costId] || 0) + value;
                    }
                });
            });

            const totalRow = table.querySelector('tr.table-secondary');
            if (totalRow) {
                const totalBasicCell = totalRow.querySelector('.sticky-col-5');
                const totalCurrentCell = totalRow.querySelector('.sticky-col-6');

                if (totalBasicCell) totalBasicCell.textContent = '$' + totalBasicSalary.toFixed(2);
                if (totalCurrentCell) totalCurrentCell.textContent = '$' + totalCurrentSalary.toFixed(2);

                const costCells = totalRow.querySelectorAll('.scrollable-col');
                const firstRowCostCells = table.querySelector('tr:not(.table-secondary)')?.querySelectorAll('.cost-cell');

                if (firstRowCostCells) {
                    costCells.forEach((cell, index) => {
                        if (firstRowCostCells[index]) {
                            const costId = firstRowCostCells[index].getAttribute('data-cost-id');
                            if (costId && costTotals[costId] !== undefined) {
                                cell.textContent = '$' + costTotals[costId].toFixed(2);
                            }
                        }
                    });
                }
            }

            const grandTotal = totalBasicSalary + totalCurrentSalary + Object.values(costTotals).reduce((a, b) => a + b, 0);
            const badge = positionElement.querySelector('.accordion-button .positionGrandTotal');
            if (badge) {
                badge.textContent = 'Budget: $' + grandTotal.toFixed(2);
            }

            return grandTotal;
        }

        function recalculateSectionTotal(sectionElement) {
            let sectionTotal = 0;
            const sectionBody = sectionElement.querySelector('.accordion-body');
            if (!sectionBody) return 0;

            const positions = sectionBody.querySelectorAll(':scope > .ms-3 > .position-accordion, :scope > .position-accordion');
            positions.forEach(position => {
                sectionTotal += recalculatePositionTotal(position);
            });

            const badge = sectionElement.querySelector('.accordion-button .sectionGrandTotal');
            if (badge) {
                badge.textContent = 'Budget: $' + sectionTotal.toFixed(2);
            }

            return sectionTotal;
        }

        function recalculateDepartmentTotal(departmentElement) {
            let departmentTotal = 0;
            const deptBody = departmentElement.querySelector(':scope > .accordion-item > [id^="collapseDept"]');
            if (!deptBody) return 0;

            const sections = deptBody.querySelectorAll('.section-accordion');
            sections.forEach(section => {
                departmentTotal += recalculateSectionTotal(section);
            });

            const directPositions = deptBody.querySelectorAll(':scope > .accordion-body > .ms-3 > .position-accordion, :scope > .accordion-body > .position-accordion');
            directPositions.forEach(position => {
                departmentTotal += recalculatePositionTotal(position);
            });

            const badge = departmentElement.querySelector('.accordion-button .departmentGrandTotal');
            if (badge) {
                badge.textContent = 'Budget: $' + departmentTotal.toFixed(2);
            }

            return departmentTotal;
        }

        function recalculateDivisionTotal(divisionElement) {
            let divisionTotal = 0;
            const divBody = divisionElement.querySelector(':scope > [id^="collapseDiv"]');
            if (!divBody) return 0;

            const departments = divBody.querySelectorAll('.department-accordion');
            departments.forEach(department => {
                divisionTotal += recalculateDepartmentTotal(department);
            });

            const badge = divisionElement.querySelector('.accordion-button .divisionGrandTotal');
            if (badge) {
                badge.textContent = 'Budget: $' + divisionTotal.toFixed(2);
            }

            return divisionTotal;
        }
    });
</script>
@endsection
