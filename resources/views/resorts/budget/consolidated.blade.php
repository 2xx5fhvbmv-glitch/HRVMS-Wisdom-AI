@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')

<style>
    /* Neutral/geometry tokens (--teal/--teal-2/--teal-3/--teal-soft/--lime/
       --ink/--muted/--faint/--line/--line-2/--card) now come from the
       shared :root palette (resorts/layouts/_design_tokens.blade.php),
       same as view_budget_hierarchical.blade.php — this block previously
       duplicated them (see old comment above) rather than sharing, which
       is exactly what the shared palette now replaces. --wb-bg and the
       semantic tokens below stay local. */
    :root {
        --wb-bg: #F2F6F6;
        --wb-vacant: #D98A00;
        --wb-vacant-bg: #FFF6E5;
        --wb-increase: #1F9D6B;
        --wb-increase-bg: #EAF7F0;
    }

    /* ---- Nav-row primitives — copied verbatim from View Budget's
       .wb-level-tag / .wb-group-row-name / .wb-group-row-meta /
       .wb-group-row-budget (view_budget_hierarchical.blade.php) so
       division/department/section/position rows here render with the
       exact same look, not a page-specific lookalike. ---- */
    .wb-level-tag {
        display: inline-block;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--teal);
        background: var(--teal-3);
        border-radius: 6px;
        padding: 2px 7px;
        margin-right: 8px;
        vertical-align: middle;
    }
    .wb-group-row-name { font-size: 13.5px; font-weight: 600; color: var(--ink); }
    .wb-group-row-meta { font-size: 11px; color: var(--muted); margin-top: 3px; }
    .wb-group-row-budget { font-size: 13px; font-weight: 700; color: var(--teal); flex-shrink: 0; }

    /* ---- Summary cards ---- */
    .cb-summary-row { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 16px; }
    .cb-summary-card {
        flex: 1 1 220px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 1px 3px rgba(1,70,83,0.06);
    }
    .cb-summary-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .cb-summary-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--ink);
        font-variant-numeric: tabular-nums;
    }
    .cb-summary-value.cb-money { color: var(--teal); }

    /* ---- Toolbar ---- */
    .cb-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }

    /* ---- Loading skeleton (shown until the initial auto-fetch resolves) ---- */
    .cb-skeleton-row {
        height: 52px;
        border-radius: 10px;
        margin-bottom: 8px;
        background: linear-gradient(90deg, var(--line-2) 25%, var(--teal-3) 37%, var(--line-2) 63%);
        background-size: 400% 100%;
        animation: cbSkeletonPulse 1.4s ease infinite;
    }
    @keyframes cbSkeletonPulse {
        0% { background-position: 100% 50%; }
        100% { background-position: 0 50%; }
    }
</style>

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
                                <button type="submit" class="btn wfp-btn-primary SendToFinance" id="SendToFinanceButton" >Send To Finance</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'Finance')
                                <button type="submit" class="btn wfp-btn-primary SendToGM" id="SendToGMButton" >Send To GM</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'GM')
                                {{-- <button type="submit" class="btn btn-theme SendToCorporateOffice" >Send To Corporate Office</button> --}}
                                <button type="submit" class="btn wfp-btn-celebrate SendToCorporateOffice" >Approve Budget</button>
                                {{-- Revise Budget was removed from this page per explicit
                                     decision: revisions now happen only on View Budget. --}}
                            @endif
                            {{-- @if($employeeRankPosition['position'] == 'Corporate Office')
                                <button type="submit" class="btn wfp-btn-primary SendToHR" >Send To HR</button>
                            @endif --}}
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center justify-content-between">
                    <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <select class="form-select" name="year" id="year" onchange="fetchConsolidatedBudget(this.value)">
                            <option>Select Year</option>
                            {{-- Years will be dynamically populated --}}
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-5 col-sm-6 col-12">
                        {{-- Same input-group / form-control.search / .card-header
                             pattern used site-wide (View Budget, Talent Pool) —
                             reusing the shared CSS rather than a page-specific
                             look. --}}
                        <div class="input-group" id="cbSearchGroup">
                            <input type="search" class="form-control search" id="cbSearchInput" placeholder="Search department or position" autocomplete="off">
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="cb-summary-row">
                    <div class="cb-summary-card">
                        <div class="cb-summary-label">Total Annual Budget</div>
                        <div class="cb-summary-value cb-money" id="cbSummaryTotalBudget">—</div>
                    </div>
                    <div class="cb-summary-card">
                        <div class="cb-summary-label">Departments</div>
                        <div class="cb-summary-value" id="cbSummaryDepartments">—</div>
                    </div>
                    <div class="cb-summary-card">
                        <div class="cb-summary-label">Positions</div>
                        <div class="cb-summary-value" id="cbSummaryPositions">—</div>
                    </div>
                </div>
                <div class="cb-toolbar">
                    <button type="button" class="btn btn-sm wfp-btn-secondary" id="cbExpandAllBtn">Expand all</button>
                    <button type="button" class="btn btn-sm wfp-btn-secondary" id="cbCollapseAllBtn">Collapse all</button>
                </div>
            </div>
            <div class="viewBudget-accordion" id="accordionViewBudget">
                {{-- Loading skeleton — the real Division/Department/Section/
                     Position hierarchy always replaces this via the
                     DOMContentLoaded fetchConsolidatedBudget() call below.
                     Deliberately not rendering the flat legacy $MainArray
                     table here anymore: it briefly flashed a different,
                     older-looking layout (with a hardcoded fake WSB badge)
                     before that AJAX call swapped it out a moment later. --}}
                <div class="cb-skeleton-row"></div>
                <div class="cb-skeleton-row"></div>
                <div class="cb-skeleton-row"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
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
                    $('#cbSearchInput').val('');
                    cbUpdateSummaryCards();
                    if (response.isBudgetCompleted === true) {
                        $("#SendToFinanceButton").prop("disabled", true);
                        $("#SendToGMButton").prop("disabled", true);
                    } else {
                        $("#SendToFinanceButton").prop("disabled", false);
                        $("#SendToGMButton").prop("disabled", false);
                    }

                    // NOTE: do NOT call recalculateAllTotals here. The server
                    // already rendered each level's `calculated_total` using
                    // Common::annualBudgetForEmployee / annualBudgetForVacantSlot
                    // (the canonical aggregator that includes per-month salary
                    // overrides + cost-template live fallback + per-employee
                    // allowances). The client-side recalculation only sees
                    // configured_basic + configured_current + saved cost
                    // configs in the `data-value` cells, which excludes the
                    // allowance leg, so re-running it here would silently
                    // discard the allowance numbers and diverge from
                    // /resort/budget/view-budget.
                },
                error: function(xhr) {
                    console.error('Error fetching data:', xhr);
                    alert('Failed to load budget data. Please try again.');
                }
            });
        }
    }

    // ---- Summary cards (Section 1) ----
    // Computed purely from what's already rendered inside #accordionViewBudget
    // after fetchConsolidatedBudget() injects the AJAX partial — no new
    // endpoint, no new query. Re-run after every load (initial + year change)
    // so the cards always match whatever's currently on screen.
    function cbParseMoney(text) {
        var n = parseFloat(String(text).replace(/[^0-9.\-]/g, ''));
        return isFinite(n) ? n : 0;
    }

    function cbUpdateSummaryCards() {
        var $depts = $('#accordionViewBudget .department-accordion');
        var $positions = $('#accordionViewBudget .position-accordion');
        var totalBudget = 0;
        $depts.each(function () {
            var $badge = $(this).find('.departmentGrandTotal').first();
            totalBudget += cbParseMoney($badge.text());
        });
        $('#cbSummaryTotalBudget').text(formatAmount ? formatAmount(totalBudget, 'USD') : totalBudget.toFixed(2));
        $('#cbSummaryDepartments').text($depts.length);
        $('#cbSummaryPositions').text($positions.length);
    }

    // ---- Toolbar: Expand all / Collapse all (Section 2) ----
    // Same bootstrap.Collapse API already used (and fixed) on View Budget's
    // own drill-down this session.
    $(document).on('click', '#cbExpandAllBtn', function () {
        document.querySelectorAll('#accordionViewBudget .collapse').forEach(function (el) {
            bootstrap.Collapse.getOrCreateInstance(el).show();
        });
    });
    $(document).on('click', '#cbCollapseAllBtn', function () {
        document.querySelectorAll('#accordionViewBudget .collapse').forEach(function (el) {
            bootstrap.Collapse.getOrCreateInstance(el).hide();
        });
    });

    // ---- Toolbar: Search (Section 2) ----
    // Whole hierarchy is already present in the DOM once the AJAX partial
    // loads (unlike View Budget's lazily-loaded nav card), so this is pure
    // client-side text filtering — no endpoint needed.
    $(document).on('input', '#cbSearchInput', function () {
        var term = $(this).val().trim().toLowerCase();

        if (!term) {
            // Clearing search restores the complete hierarchy — remove every
            // filter-added inline display:none, leave expand/collapse state
            // as-is (don't force a re-collapse).
            $('#accordionViewBudget .division-accordion, #accordionViewBudget .department-accordion, #accordionViewBudget .section-accordion, #accordionViewBudget .position-accordion').css('display', '');
            return;
        }

        $('#accordionViewBudget .department-accordion').each(function () {
            var $dept = $(this);
            var deptName = $dept.find('> .accordion-item > h2 .wb-group-row-name').first().text().toLowerCase();
            var deptNameMatches = deptName.indexOf(term) !== -1;
            var anyPositionMatches = false;

            $dept.find('.position-accordion').each(function () {
                var $pos = $(this);
                var posName = $pos.find('.accordion-header .wb-group-row-name').first().text().toLowerCase();
                var matches = deptNameMatches || posName.indexOf(term) !== -1;
                $pos.css('display', matches ? '' : 'none');
                if (matches) {
                    anyPositionMatches = true;
                    bootstrap.Collapse.getOrCreateInstance($pos.find('.accordion-collapse')[0]).show();
                }
            });

            var deptVisible = deptNameMatches || anyPositionMatches;
            $dept.css('display', deptVisible ? '' : 'none');
            if (deptVisible) {
                var $deptCollapse = $dept.find('> .accordion-item [id^="collapseDept"]');
                if ($deptCollapse.length) bootstrap.Collapse.getOrCreateInstance($deptCollapse[0]).show();
                var $section = $dept.find('.section-accordion');
                $section.css('display', '');
                var $sectionCollapse = $section.find('[id^="collapseSec"]');
                if ($sectionCollapse.length) bootstrap.Collapse.getOrCreateInstance($sectionCollapse[0]).show();
                var $division = $dept.closest('.division-accordion');
                $division.css('display', '');
                var $divCollapse = $division.find('[id^="collapseDiv"]').first();
                if ($divCollapse.length) bootstrap.Collapse.getOrCreateInstance($divCollapse[0]).show();
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        populateYears();

        // Always load the rich Division/Department/Section/Position/Employee
        // structure by default instead of waiting for a year-dropdown touch —
        // closes the gap where a fresh page load showed a different, flatter
        // legacy table than every subsequent interaction.
        var $yearSelect = $('#year');
        var defaultYear = @json((int) ($year ?? now()->year));
        $yearSelect.val(String(defaultYear));
        fetchConsolidatedBudget(defaultYear);
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

                    // Refresh badges via the canonical AJAX path instead of
                    // running the client-side recalc, which would exclude
                    // per-employee allowances and the live cost-template
                    // fallback. See the canonical totals note in
                    // fetchConsolidatedBudget's success handler.
                    const yearForReload =
                        document.getElementById('year')?.value ||
                        document.getElementById('SendToFinanceYear')?.value ||
                        new Date().getFullYear();
                    if (yearForReload) {
                        fetchConsolidatedBudget(yearForReload);
                    }
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
                basicSalaryCell.textContent = formatAmount(parseFloat(data.basic_salary), 'USD');
            }

            const currentSalaryCell = row.querySelector('.current-salary-cell');
            if (currentSalaryCell && data.current_salary) {
                currentSalaryCell.setAttribute('data-value', data.current_salary);
                currentSalaryCell.textContent = formatAmount(parseFloat(data.current_salary), 'USD');
            }

            if (data.costs && Array.isArray(data.costs)) {
                data.costs.forEach(cost => {
                    const costCell = row.querySelector(`.cost-cell[data-cost-id="${cost.resort_budget_cost_id}"]`);
                    if (costCell) {
                        // MVRtoDoller field stores: 1 MVR = X USD, so multiply
                        const valueInUSD = cost.currency === 'MVR' ? cost.value * data.mvr_to_dollar_rate : cost.value;
                        costCell.setAttribute('data-value', valueInUSD);
                        costCell.setAttribute('data-currency', cost.currency);
                        // Always render in the SYSTEM currency via formatAmount
                        // (was hardcoded '$' / 'MVR ' regardless of resort setting).
                        costCell.textContent = formatAmount(valueInUSD, 'USD');
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

                if (totalBasicCell) totalBasicCell.textContent = formatAmount(totalBasicSalary, 'USD');
                if (totalCurrentCell) totalCurrentCell.textContent = formatAmount(totalCurrentSalary, 'USD');

                const costCells = totalRow.querySelectorAll('.scrollable-col');
                const firstRowCostCells = table.querySelector('tr:not(.table-secondary)')?.querySelectorAll('.cost-cell');

                if (firstRowCostCells) {
                    costCells.forEach((cell, index) => {
                        if (firstRowCostCells[index]) {
                            const costId = firstRowCostCells[index].getAttribute('data-cost-id');
                            if (costId && costTotals[costId] !== undefined) {
                                cell.textContent = formatAmount(costTotals[costId], 'USD');
                            }
                        }
                    });
                }
            }

            const grandTotal = totalBasicSalary + totalCurrentSalary + Object.values(costTotals).reduce((a, b) => a + b, 0);
            const badge = positionElement.querySelector('.accordion-button .positionGrandTotal');
            if (badge) {
                badge.textContent = 'Budget: ' + formatAmount(grandTotal, 'USD');
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
                badge.textContent = 'Budget: ' + formatAmount(sectionTotal, 'USD');
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
                badge.textContent = 'Budget: ' + formatAmount(departmentTotal, 'USD');
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
                badge.textContent = 'Budget: ' + formatAmount(divisionTotal, 'USD');
            }

            return divisionTotal;
        }
    });
</script>
@endsection
