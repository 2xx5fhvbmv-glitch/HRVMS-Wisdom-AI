@extends('resorts.layouts.app')
@section('page_tab_title', 'View Budget')

@if ($message = Session::get('success'))
<div class="alert alert-success">
    <p>{{ $message }}</p>
</div>
@endif

@section('content')

<style>
    /* Enhanced Budget Table Styling */
    .budget-monthly-table tbody tr:hover {
        background-color: #f8f9fa !important;
        transform: translateX(2px);
    }

    .budget-monthly-table {
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        border-radius: 8px;
        overflow: hidden;
    }

    .budget-monthly-table th {
        font-size: 0.813rem;
        padding: 12px 8px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .budget-monthly-table td {
        padding: 10px 8px;
        vertical-align: middle;
    }

    .btn-edit-month-budget {
        transition: all 0.2s ease;
    }

    .btn-edit-month-budget:hover {
        transform: scale(1.1);
    }

    /* Month column styling */
    .budget-monthly-table tbody tr td:first-child {
        background-color: #f8f9fa;
        font-weight: 500;
    }

    /* Total row emphasis */
    .budget-monthly-table tbody tr:last-child {
        font-size: 0.875rem;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
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
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                        <form method="GET" action="{{ route('resort.budget.viewbudget') }}" id="yearFilterForm">
                            <select class="form-select" name="year" id="yearFilter" onchange="document.getElementById('yearFilterForm').submit();">
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

            <div class="viewBudget-accordion" id="accordionViewBudget">
                @if($divisions->isNotEmpty())
                    @php $divisionIteration = 1; @endphp
                    @foreach($divisions as $division)
                        {{-- Level 1: Division --}}
                        <div class="accordion-item mb-2 division-accordion">
                            <h2 class="accordion-header" id="headingDiv{{ $divisionIteration }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseDiv{{ $divisionIteration }}" aria-expanded="false"
                                        aria-controls="collapseDiv{{ $divisionIteration }}">
                                    <i class="fas fa-building me-2"></i>
                                    <h3>{{ $division->name }}</h3>
                                    <span class="badge badge-dark ms-2 small divisionGrandTotal">Budget: $ 0.00</span>
                                </button>
                            </h2>
                            <div id="collapseDiv{{ $divisionIteration }}" class="collapse"
                                 aria-labelledby="headingDiv{{ $divisionIteration }}" data-bs-parent="#accordionViewBudget">
                                <div class="accordion-body p-2">
                                    @php $deptIteration = 1; @endphp
                                    @foreach($division->departments as $department)
                                        @php
                                            $manningResponse = $manningResponses->where('dept_id', $department->id)->first();
                                        @endphp
                                        @if($manningResponse)
                                        {{-- Level 2: Department --}}
                                        <div class="accordion mb-2 ms-3 department-accordion" id="accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                            <div class="accordion-item">
                                                <div class="row g-0 align-items-center">
                                                    <div class="col-md-9">
                                                        <h2 class="accordion-header" id="headingDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                                    aria-expanded="false" aria-controls="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                                <i class="fas fa-sitemap me-2"></i>
                                                                <span>{{ $department->name }}</span>
                                                                <span class="badge badge-dark ms-2 small departmentGrandTotal">Budget: $ 0.00</span>
                                                            </button>
                                                        </h2>
                                                    </div>
                                                    <div class="col-md-3 text-end pe-2">
                                                        @if($available_rank == 'HR')
                                                        <a href="#revise-budgetmodal"
                                                           data-dept_id="{{ $department->id }}"
                                                           data-Budget_id="{{ $manningResponse->id }}"
                                                           class="btn btn-xs btn-themeBlue ms-2 revisebudgetmodal"
                                                           data-bs-toggle="modal">
                                                            Revise Budget
                                                        </a>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div id="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                     class="collapse"
                                                     aria-labelledby="headingDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                     data-bs-parent="#accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                    <div class="accordion-body p-2"
                                                         data-department-id="{{ $department->id }}"
                                                         data-division-iteration="{{ $divisionIteration }}"
                                                         data-dept-iteration="{{ $deptIteration }}"
                                                         data-year="{{ $year }}">
                                                        <!-- Content will be loaded via AJAX when expanded -->
                                                        <div class="text-center py-3">
                                                            <div class="spinner-border spinner-border-sm" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <span class="ms-2">Loading...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @php $deptIteration++; @endphp
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @php $divisionIteration++; @endphp
                    @endforeach
                @else
                    <div class="alert alert-info">
                        <p>No divisions found for this resort.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Revise Budget Modal --}}
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
                        <input type="hidden" class="Revise_Budget_id" name="budget_id" >
                        <input type="hidden" class="Revise_Department_id" name="department_id" >
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
    // Store MVR to Dollar rate globally
    @php
        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        // DollertoMVR field stores: 1 USD = X MVR (e.g., 15.42 means 1 USD = 15.42 MVR)
        $mvrToDollarRate = 1/$resortSettings->DollertoMVR ?? 1/15.42;
    @endphp
    window.mvrToDollarRate = {{ $mvrToDollarRate }};

$(document).ready(function() {
    const resortId = {{ $resortId }};
    const year = {{ $year }};
    const csrfToken = '{{ csrf_token() }}';

    // Track loaded departments
    const loadedDepartments = {};

    // Function to get current year from page
    function getCurrentYear() {
        // Try to get year from year filter select
        const yearSelect = $('#yearFilter');
        if (yearSelect.length && yearSelect.val()) {
            return parseInt(yearSelect.val());
        }
        // Fallback to current year
        return new Date().getFullYear();
    }

    // Function to get days in a month for a given year and month
    function getDaysInMonth(year, month) {
        // month is 1-12, but Date constructor expects 0-11 for month
        // So we use month (1-12) and day 0 to get the last day of the previous month
        // which gives us the last day of the current month
        return new Date(year, month, 0).getDate();
    }

    // Store budget totals for badge calculations
    window.budgetTotals = {
        positions: {},
        sections: {},
        departments: {},
        divisions: {}
    };

    // Load all budget data automatically on page load for badge calculations
    function loadAllBudgetTotalsOnPageLoad() {
        console.log('Loading all budget totals on page load...');

        // Collect all departments from the page
        const departments = [];
        $('[data-department-id]').each(function() {
            const deptId = $(this).data('department-id');
            const divisionIteration = $(this).data('division-iteration');
            const deptIteration = $(this).data('dept-iteration');
            const divisionAccordion = $(this).closest('.division-accordion');
            const divisionIndex = divisionAccordion.length ? divisionAccordion.index('.division-accordion') + 1 : 1;

            if (deptId && !departments.find(d => d.deptId === deptId)) {
                departments.push({
                    deptId: deptId,
                    divisionIteration: divisionIteration,
                    deptIteration: deptIteration,
                    divisionIndex: divisionIndex
                });
            }
        });

        // Load each department's budget data
        let loadedCount = 0;
        const totalDepartments = departments.length;

        if (totalDepartments === 0) {
            console.log('No departments found to load.');
            return;
        }

        departments.forEach(dept => {
            loadDepartmentBudgetTotals(dept.deptId, dept.divisionIteration, dept.deptIteration, dept.divisionIndex, function() {
                loadedCount++;
                if (loadedCount === totalDepartments) {
                    // All departments loaded, now calculate and update all badges
                    setTimeout(() => {
                        updateAllBadgesFromTotals();
                    }, 500);
                }
            });
        });
    }

    // Load budget totals for a department
    function loadDepartmentBudgetTotals(departmentId, divisionIteration, deptIteration, divisionIndex, callback) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.department') }}",
            method: 'GET',
            data: {
                department_id: departmentId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    const positionPromises = [];

                    // Process sections
                    if (response.sections && response.sections.length > 0) {
                        response.sections.forEach((section, sIdx) => {
                            if (response.positions_by_section[section.id]) {
                                response.positions_by_section[section.id].forEach(position => {
                                    positionPromises.push(loadPositionBudgetTotal(position.id, departmentId, divisionIndex, section.id));
                                });
                            }
                        });
                    }

                    // Process positions without section
                    if (response.positions_without_section && response.positions_without_section.length > 0) {
                        response.positions_without_section.forEach(position => {
                            positionPromises.push(loadPositionBudgetTotal(position.id, departmentId, divisionIndex, null));
                        });
                    }

                    Promise.all(positionPromises).then(() => {
                        if (callback) callback();
                    });
                } else {
                    if (callback) callback();
                }
            },
            error: function(xhr) {
                console.error('Error loading department totals:', xhr);
                if (callback) callback();
            }
        });
    }

    // Load budget total for a position
    function loadPositionBudgetTotal(positionId, departmentId, divisionIndex, sectionId) {
        return new Promise((resolve) => {
            $.ajax({
                url: "{{ route('resort.budget.hierarchy.position.employees') }}",
                method: 'GET',
                data: {
                    position_id: positionId,
                    year: year,
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        const employeePromises = [];

                        // Load employee totals
                        if (response.employees && response.employees.length > 0) {
                            response.employees.forEach(employee => {
                                employeePromises.push(loadEmployeeBudgetTotal(employee.Empid, positionId));
                            });
                        }

                        // Load vacant totals
                        if (response.total_vacant_positions && response.total_vacant_positions > 0) {
                            for (let v = 1; v <= response.total_vacant_positions; v++) {
                                employeePromises.push(loadVacantBudgetTotal(v, positionId));
                            }
                        }

                        Promise.all(employeePromises).then(() => {
                            // Calculate position total
                            let positionTotal = 0;

                            // Sum employee totals
                            if (window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].employees) {
                                Object.values(window.budgetTotals.positions[positionId].employees).forEach(empTotal => {
                                    positionTotal += empTotal;
                                });
                            }

                            // Sum vacant totals
                            if (window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].vacants) {
                                Object.values(window.budgetTotals.positions[positionId].vacants).forEach(vacTotal => {
                                    positionTotal += vacTotal;
                                });
                            }

                            // Store position total
                            if (!window.budgetTotals.positions[positionId]) {
                                window.budgetTotals.positions[positionId] = {};
                            }
                            window.budgetTotals.positions[positionId].total = positionTotal;
                            window.budgetTotals.positions[positionId].departmentId = departmentId;
                            window.budgetTotals.positions[positionId].divisionIndex = divisionIndex;
                            window.budgetTotals.positions[positionId].sectionId = sectionId;

                            resolve();
                        });
                    } else {
                        resolve();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading position totals:', xhr);
                    resolve();
                }
            });
        });
    }

    // Load employee budget total
    function loadEmployeeBudgetTotal(employeeId, positionId) {
        return new Promise((resolve) => {
            $.ajax({
                url: "{{ route('resort.budget.hierarchy.employee.monthly') }}",
                method: 'GET',
                data: {
                    employee_id: employeeId,
                    position_id: positionId,
                    year: year,
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        let total = 0;
                        const currentBasicSalary = parseFloat(response.current_basic_salary || 0) * 12;
                        const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0) * 12;

                        const monthCostData = response.month_cost_data || {};
                        const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;

                        for (let m = 1; m <= 12; m++) {
                            const monthData = monthCostData[m] || {};
                            Object.keys(monthData).forEach(costId => {
                                const costData = monthData[costId];
                                let costValue = parseFloat(costData.value || 0);
                                const currency = costData.currency || 'USD';

                                if (currency === 'MVR' && costValue > 0) {
                                    costValue = costValue * mvrToUsdRate;
                                }
                                total += costValue;
                            });
                        }

                        total += currentBasicSalary + proposedBasicSalary;

                        // Store employee total
                        if (!window.budgetTotals.positions[positionId]) {
                            window.budgetTotals.positions[positionId] = { employees: {}, vacants: {} };
                        }
                        if (!window.budgetTotals.positions[positionId].employees) {
                            window.budgetTotals.positions[positionId].employees = {};
                        }
                        window.budgetTotals.positions[positionId].employees[employeeId] = total;
                    }
                    resolve();
                },
                error: function() {
                    resolve();
                }
            });
        });
    }

    // Load vacant budget total
    function loadVacantBudgetTotal(vacantIndex, positionId) {
        return new Promise((resolve) => {
            $.ajax({
                url: "{{ route('resort.budget.hierarchy.vacant.monthly') }}",
                method: 'GET',
                data: {
                    vacant_index: vacantIndex,
                    position_id: positionId,
                    year: year,
                    _token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        let total = 0;
                        const currentBasicSalary = parseFloat(response.current_basic_salary || 0) * 12;
                        const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0) * 12;

                        const monthCostData = response.month_cost_data || {};
                        const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;

                        for (let m = 1; m <= 12; m++) {
                            const monthData = monthCostData[m] || {};
                            Object.keys(monthData).forEach(costId => {
                                const costData = monthData[costId];
                                let costValue = parseFloat(costData.value || 0);
                                const currency = costData.currency || 'USD';

                                if (currency === 'MVR' && costValue > 0) {
                                    costValue = costValue * mvrToUsdRate;
                                }
                                total += costValue;
                            });
                        }

                        total += currentBasicSalary + proposedBasicSalary;

                        // Store vacant total
                        if (!window.budgetTotals.positions[positionId]) {
                            window.budgetTotals.positions[positionId] = { employees: {}, vacants: {} };
                        }
                        if (!window.budgetTotals.positions[positionId].vacants) {
                            window.budgetTotals.positions[positionId].vacants = {};
                        }
                        window.budgetTotals.positions[positionId].vacants[vacantIndex] = total;
                    }
                    resolve();
                },
                error: function() {
                    resolve();
                }
            });
        });
    }

    // Update all badges from calculated totals
    function updateAllBadgesFromTotals() {
        console.log('Updating all badges from calculated totals...');

        // Clear existing section and department totals
        window.budgetTotals.sections = {};
        window.budgetTotals.departments = {};

        // Step 1: Calculate section totals first (from positions in sections)
        Object.keys(window.budgetTotals.positions).forEach(positionId => {
            const positionData = window.budgetTotals.positions[positionId];
            const total = positionData.total || 0;
            const sectionId = positionData.sectionId;

            // Update position badges if in DOM
            $(`.position-accordion[data-position-id="${positionId}"]`).find('.positionGrandTotal').text('Budget: $ ' + total.toFixed(2));
            $(`.accordion-body[data-position-id="${positionId}"]`).closest('.position-accordion').find('.positionGrandTotal').text('Budget: $ ' + total.toFixed(2));

            // Add to section total if position is in a section
            if (sectionId) {
                if (!window.budgetTotals.sections[sectionId]) {
                    window.budgetTotals.sections[sectionId] = 0;
                }
                window.budgetTotals.sections[sectionId] += total;
            }
        });

        // Step 2: Calculate department totals (from sections + direct positions)
        const departmentSectionMap = {}; // Track which sections belong to which departments
        const departmentDirectPositions = {}; // Track direct positions (not in sections) by department

        Object.keys(window.budgetTotals.positions).forEach(positionId => {
            const positionData = window.budgetTotals.positions[positionId];
            const total = positionData.total || 0;
            const deptId = positionData.departmentId;
            const sectionId = positionData.sectionId;

            if (!deptId) return;

            if (sectionId) {
                // Position is in a section
                if (!departmentSectionMap[deptId]) {
                    departmentSectionMap[deptId] = [];
                }
                if (departmentSectionMap[deptId].indexOf(sectionId) === -1) {
                    departmentSectionMap[deptId].push(sectionId);
                }
            } else {
                // Direct position (not in section)
                if (!departmentDirectPositions[deptId]) {
                    departmentDirectPositions[deptId] = 0;
                }
                departmentDirectPositions[deptId] += total;
            }
        });

        // Calculate department totals
        Object.keys(departmentSectionMap).forEach(deptId => {
            let deptTotal = 0;

            // Add section totals
            departmentSectionMap[deptId].forEach(sectionId => {
                if (window.budgetTotals.sections[sectionId]) {
                    deptTotal += window.budgetTotals.sections[sectionId];
                }
            });

            // Add direct positions
            if (departmentDirectPositions[deptId]) {
                deptTotal += departmentDirectPositions[deptId];
            }

            window.budgetTotals.departments[deptId] = deptTotal;
        });

        // Add departments that only have direct positions (no sections)
        Object.keys(departmentDirectPositions).forEach(deptId => {
            if (!window.budgetTotals.departments[deptId]) {
                window.budgetTotals.departments[deptId] = departmentDirectPositions[deptId];
            }
        });

        // Step 3: Update section badges from stored totals (if sections are in DOM)
        Object.keys(window.budgetTotals.sections).forEach(sectionId => {
            const sectionTotal = window.budgetTotals.sections[sectionId];
            // Note: Section badges will be updated when sections are rendered via updateSectionDepartmentDivisionBadges
            // But we can also try to find them by ID pattern
            $(`.section-accordion`).each(function() {
                // Check if this section contains positions that match our sectionId
                // Since we don't have direct section ID mapping, we'll rely on DOM-based calculation
            });
        });

        // Step 4: Update department badges from stored totals
        Object.keys(window.budgetTotals.departments).forEach(deptId => {
            const deptTotal = window.budgetTotals.departments[deptId];
            $(`[data-department-id="${deptId}"]`).closest('.department-accordion').find('.departmentGrandTotal').text('Budget: $ ' + deptTotal.toFixed(2));
        });

        // Step 5: Calculate and update division totals
        $('.division-accordion').each(function() {
            const $division = $(this);
            let divisionTotal = 0;

            $division.find('[data-department-id]').each(function() {
                const deptId = $(this).data('department-id');
                if (deptId && window.budgetTotals.departments[deptId]) {
                    divisionTotal += window.budgetTotals.departments[deptId];
                }
            });

            $division.find('.divisionGrandTotal').text('Budget: $ ' + divisionTotal.toFixed(2));
        });

        // Update section, department, and division badges by calculating from DOM (for when they're rendered)
        updateSectionDepartmentDivisionBadges();

        console.log('All badges updated.');
    }

    // Update section, department, and division badges from position totals
    function updateSectionDepartmentDivisionBadges() {
        // Calculate section totals
        $('.section-accordion').each(function() {
            const $section = $(this);
            let sectionTotal = 0;

            $section.find('.position-accordion').each(function() {
                const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                if (positionId && window.budgetTotals.positions[positionId]) {
                    sectionTotal += (window.budgetTotals.positions[positionId].total || 0);
                }
            });

            $section.find('.sectionGrandTotal').text('Budget: $ ' + sectionTotal.toFixed(2));
            window.budgetTotals.sections[$section.attr('id')] = sectionTotal;
        });

        // Calculate department totals
        $('.department-accordion').each(function() {
            const $dept = $(this);
            let deptTotal = 0;

            // Sum sections
            $dept.find('.section-accordion').each(function() {
                const sectionId = $(this).attr('id');
                if (window.budgetTotals.sections[sectionId]) {
                    deptTotal += window.budgetTotals.sections[sectionId];
                }
            });

            // Sum direct positions (not in sections)
            $dept.find('.position-accordion').each(function() {
                if ($(this).closest('.section-accordion').length === 0) {
                    const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                    if (positionId && window.budgetTotals.positions[positionId]) {
                        deptTotal += (window.budgetTotals.positions[positionId].total || 0);
                    }
                }
            });

            $dept.find('.departmentGrandTotal').text('Budget: $ ' + deptTotal.toFixed(2));

            const deptId = $dept.closest('[data-department-id]').data('department-id');
            if (deptId) {
                window.budgetTotals.departments[deptId] = deptTotal;
            }
        });

        // Calculate division totals
        $('.division-accordion').each(function() {
            const $division = $(this);
            let divisionTotal = 0;

            $division.find('.department-accordion').each(function() {
                const $dept = $(this);
                const deptId = $dept.closest('[data-department-id]').data('department-id');
                if (deptId && window.budgetTotals.departments[deptId]) {
                    divisionTotal += window.budgetTotals.departments[deptId];
                } else {
                    // Fallback: calculate directly from positions
                    let deptTotal = 0;
                    $dept.find('.position-accordion').each(function() {
                        const positionId = $(this).data('position-id') || $(this).find('[data-position-id]').first().data('position-id');
                        if (positionId && window.budgetTotals.positions[positionId]) {
                            deptTotal += (window.budgetTotals.positions[positionId].total || 0);
                        }
                    });
                    divisionTotal += deptTotal;
                }
            });

            $division.find('.divisionGrandTotal').text('Budget: $ ' + divisionTotal.toFixed(2));
        });
    }

    // Global function to recalculate all totals from rendered tables (similar to consolidated page)
    window.recalculateAllTotals = function() {
        console.log('Recalculating all totals from rendered tables...');

        // Recalculate position totals using the same function as updateBadgesHierarchy
        $('.position-accordion').each(function() {
            const $position = $(this);
            const positionId = $position.data('position-id');
            if (positionId) {
                const positionTotal = calculatePositionTotal($position);
                if (positionTotal > 0 || positionTotal === 0) {
                    $position.find('.positionGrandTotal').text('Budget: $ ' + positionTotal.toFixed(2));
                }
            }
        });

        // Recalculate section totals
        $('.section-accordion').each(function() {
            const sectionTotal = calculateSectionTotal($(this));
            $(this).find('.sectionGrandTotal').text('Budget: $ ' + sectionTotal.toFixed(2));
        });

        // Recalculate department totals
        $('.department-accordion').each(function() {
            const deptTotal = calculateDepartmentTotal($(this));
            $(this).find('.departmentGrandTotal').text('Budget: $ ' + deptTotal.toFixed(2));
        });

        // Recalculate division totals
        $('.division-accordion').each(function() {
            const divisionTotal = calculateDivisionTotal($(this));
            $(this).find('.divisionGrandTotal').text('Budget: $ ' + divisionTotal.toFixed(2));
        });

        console.log('All totals recalculated.');
    };

    // Calculate position total from rendered tables
    function calculatePositionTotalFromTable($positionElement) {
        let total = 0;

        // Find all budget monthly tables within this position
        $positionElement.find('.budget-monthly-table').each(function() {
            const $table = $(this);
            const $totalRow = $table.find('.table-total-row');

            if ($totalRow.length) {
                // Get current salary total
                const currentSalaryText = $totalRow.find('.total-current-salary').text();
                const currentSalary = parseFloat(currentSalaryText.replace('$', '').replace(',', '').trim() || 0);

                // Get proposed salary total
                const proposedSalaryText = $totalRow.find('.total-proposed-salary').text();
                const proposedSalary = parseFloat(proposedSalaryText.replace('$', '').replace(',', '').trim() || 0);

                // Sum all cost configuration totals
                let costTotal = 0;
                $totalRow.find('td[data-cost-id]').each(function() {
                    const costText = $(this).text();
                    const costValue = parseFloat(costText.replace('$', '').replace(',', '').trim() || 0);
                    if (!isNaN(costValue)) {
                        costTotal += costValue;
                    }
                });

                // Add to position total
                total += currentSalary + proposedSalary + costTotal;
            }
        });

        return total;
    }

    // Calculate section total from rendered tables
    function calculateSectionTotalFromTable($sectionElement) {
        let total = 0;
        $sectionElement.find('.position-accordion').each(function() {
            total += calculatePositionTotalFromTable($(this));
        });
        return total;
    }

    // Calculate department total from rendered tables
    function calculateDepartmentTotalFromTable($deptElement) {
        let total = 0;

        // Sum sections
        $deptElement.find('.section-accordion').each(function() {
            total += calculateSectionTotalFromTable($(this));
        });

        // Sum direct positions (not in sections)
        $deptElement.find('.position-accordion').each(function() {
            if ($(this).closest('.section-accordion').length === 0) {
                total += calculatePositionTotalFromTable($(this));
            }
        });

        return total;
    }

    // Calculate division total from rendered tables
    function calculateDivisionTotalFromTable($divisionElement) {
        let total = 0;
        $divisionElement.find('.department-accordion').each(function() {
            total += calculateDepartmentTotalFromTable($(this));
        });
        return total;
    }

    // Start loading all budget totals on page load
    setTimeout(() => {
        loadAllBudgetTotalsOnPageLoad();
    }, 1000);

    // Load department content when accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapseDept"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const departmentId = $accordionBody.data('department-id');
        const divisionIteration = $accordionBody.data('division-iteration');
        const deptIteration = $accordionBody.data('dept-iteration');
        const year = $accordionBody.data('year');

        // Only load once
        if (loadedDepartments[departmentId]) {
            return;
        }

        loadDepartmentHierarchy(departmentId, divisionIteration, deptIteration, year, $accordionBody);
        loadedDepartments[departmentId] = true;
    });

    function loadDepartmentHierarchy(departmentId, divisionIteration, deptIteration, year, $container) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.department') }}",
            method: 'GET',
            data: {
                department_id: departmentId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    let html = '';

                    // Add sections with positions
                    if (response.sections && response.sections.length > 0) {
                        let sectionIteration = 1;
                        response.sections.forEach(section => {
                            html += `
                                <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                                    aria-expanded="false" aria-controls="collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                                <i class="fas fa-layer-group me-2"></i>
                                                <span>${section.name}</span>
                                                <span class="badge badge-dark ms-2 small sectionGrandTotal">Budget: $ 0.00</span>
                                            </button>
                                        </h2>

                                        <div id="collapseSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                             class="collapse"
                                             aria-labelledby="headingSec${divisionIteration}_${deptIteration}_${sectionIteration}"
                                             data-bs-parent="#accordionSec${divisionIteration}_${deptIteration}_${sectionIteration}">
                                            <div class="accordion-body p-2">`;

                            if (response.positions_by_section[section.id]) {
                                let posSecIteration = 1;
                                response.positions_by_section[section.id].forEach(position => {
                                    html += createPositionHtml(position, divisionIteration, deptIteration, sectionIteration, posSecIteration);
                                    posSecIteration++;
                                });
                            }

                            html += `</div></div></div></div>`;
                            sectionIteration++;
                        });
                    }

                    // Add positions without section
                    if (response.positions_without_section && response.positions_without_section.length > 0) {
                        let positionIteration = 1;
                        response.positions_without_section.forEach(position => {
                            html += createPositionHtml(position, divisionIteration, deptIteration, 0, positionIteration);
                            positionIteration++;
                        });
                    }

                    $container.html(html || '<p class="text-muted">No positions found.</p>');

                    // Update badges from stored totals if available
                    setTimeout(() => {
                        updateBadgesFromStoredTotals($container);
                    }, 100);
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading department hierarchy.</p>');
                console.error(xhr);
            }
        });
    }

    // Update badges from stored totals in a container
    function updateBadgesFromStoredTotals($container) {
        // Update position badges
        $container.find('.position-accordion').each(function() {
            const positionId = $(this).data('position-id');
            if (positionId && window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].total) {
                const total = window.budgetTotals.positions[positionId].total;
                $(this).find('.positionGrandTotal').text('Budget: $ ' + parseFloat(total).toFixed(2));
            }
        });

        // Update section badges
        $container.find('.section-accordion').each(function() {
            const $section = $(this);
            let sectionTotal = 0;
            $section.find('.position-accordion').each(function() {
                const positionId = $(this).data('position-id');
                if (positionId && window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[positionId] && window.budgetTotals.positions[positionId].total) {
                    sectionTotal += parseFloat(window.budgetTotals.positions[positionId].total);
                }
            });
            if (sectionTotal > 0) {
                $section.find('.sectionGrandTotal').text('Budget: $ ' + sectionTotal.toFixed(2));
            }
        });
    }

    function createPositionHtml(position, divisionIteration, deptIteration, sectionIteration, positionIteration) {
        const accordionId = `pos${divisionIteration}_${deptIteration}_${sectionIteration}_${positionIteration}`;
        // Check if we have stored total for this position
        let badgeText = 'Budget: $ 0.00';
        if (window.budgetTotals && window.budgetTotals.positions && window.budgetTotals.positions[position.id] && window.budgetTotals.positions[position.id].total) {
            badgeText = 'Budget: $ ' + parseFloat(window.budgetTotals.positions[position.id].total).toFixed(2);
        }
        return `
            <div class="accordion mb-2 ms-3 position-accordion" id="accordion${accordionId}" data-position-id="${position.id}">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading${accordionId}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse${accordionId}"
                                aria-expanded="false" aria-controls="collapse${accordionId}">
                            <i class="fas fa-user-tie me-2"></i>
                            <span>${position.position_title}</span>
                            <span class="badge badge-info ms-2">${position.code || ''}</span>
                            <span class="badge badge-dark ms-2 small positionGrandTotal">${badgeText}</span>
                        </button>
                    </h2>

                    <div id="collapse${accordionId}"
                         class="collapse"
                         aria-labelledby="heading${accordionId}"
                         data-bs-parent="#accordion${accordionId}">
                        <div class="accordion-body p-2" data-position-id="${position.id}">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Loading employees...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Load position employees when position accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapse"][id*="pos"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const positionId = $accordionBody.data('position-id');

        // Only load once
        if ($accordionBody.data('loaded')) {
            return;
        }

        loadPositionEmployees(positionId, $accordionBody);
        $accordionBody.data('loaded', true);
    });

    function loadPositionEmployees(positionId, $container) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.position.employees') }}",
            method: 'GET',
            data: {
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    let html = '';

                    if (response.employees && response.employees.length > 0) {
                        let empIteration = 1;
                        response.employees.forEach(employee => {
                            const rankConfig = @json(config('settings.Position_Rank'));
                            const rankName = rankConfig[employee.rank] || employee.rank;
                            const employeeAccordionId = `empAccordion_${positionId}_${empIteration}`;

                            html += `
                                <div class="accordion mb-2 ms-3 employee-accordion" id="${employeeAccordionId}" data-employee-rank="${employee.rank}" data-employee-rank-name="${rankName}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${employeeAccordionId}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse${employeeAccordionId}"
                                                    aria-expanded="false" aria-controls="collapse${employeeAccordionId}">
                                                <i class="fas fa-user me-2"></i>
                                                <span>${employee.first_name} ${employee.last_name}</span>
                                                <span class="badge bg-secondary ms-2">${rankName}</span>
                                                <span class="badge bg-info ms-2">${employee.nationality}</span>
                                            </button>
                                        </h2>

                                        <div id="collapse${employeeAccordionId}"
                                             class="collapse"
                                             aria-labelledby="heading${employeeAccordionId}"
                                             data-bs-parent="#${employeeAccordionId}">
                                            <div class="accordion-body p-2"
                                                 data-employee-id="${employee.Empid}"
                                                 data-position-id="${positionId}"
                                                 data-type="employee">
                                                <div class="text-center py-3">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-2">Loading monthly budget...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            empIteration++;
                        });
                    }

                    // Add individual vacant positions as separate accordions
                    if (response.total_vacant_positions && response.total_vacant_positions > 0) {
                        for (let v = 1; v <= response.total_vacant_positions; v++) {
                            const vacantAccordionId = `vacantAccordion_${positionId}_${v}`;
                            html += `
                                <div class="accordion mb-2 ms-3 vacant-accordion" id="${vacantAccordionId}">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading${vacantAccordionId}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse${vacantAccordionId}"
                                                    aria-expanded="false" aria-controls="collapse${vacantAccordionId}">
                                                <i class="fas fa-user-slash me-2 text-warning"></i>
                                                <span>Vacant ${v}</span>
                                                <span class="badge bg-warning text-dark ms-2">Vacant Position</span>
                                            </button>
                                        </h2>

                                        <div id="collapse${vacantAccordionId}"
                                             class="collapse"
                                             aria-labelledby="heading${vacantAccordionId}"
                                             data-bs-parent="#${vacantAccordionId}">
                                            <div class="accordion-body p-2"
                                                 data-vacant-index="${v}"
                                                 data-position-id="${positionId}"
                                                 data-type="vacant">
                                                <div class="text-center py-3">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-2">Loading vacant position data...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    $container.html(html || '<p class="text-muted">No employees or vacant positions found.</p>');

                    // Auto-load all employee and vacant data immediately for badge calculation
                    let loadPromises = [];

                    // Load all employee data
                    if (response.employees && response.employees.length > 0) {
                        response.employees.forEach((employee, index) => {
                            const $empBody = $container.find(`[data-employee-id="${employee.Empid}"]`);
                            if ($empBody.length && !$empBody.data('loaded')) {
                                const promise = new Promise((resolve) => {
                                    loadEmployeeMonthlyData(employee.Empid, positionId, $empBody);
                                    $empBody.data('loaded', true);
                                    // Wait a bit for data to load
                                    setTimeout(resolve, 200);
                                });
                                loadPromises.push(promise);
                            }
                        });
                    }

                    // Load all vacant data
                    if (response.total_vacant_positions && response.total_vacant_positions > 0) {
                        for (let v = 1; v <= response.total_vacant_positions; v++) {
                            const $vacantBody = $container.find(`[data-vacant-index="${v}"]`);
                            if ($vacantBody.length && !$vacantBody.data('loaded')) {
                                const promise = new Promise((resolve) => {
                                    loadVacantMonthlyData(v, positionId, $vacantBody);
                                    $vacantBody.data('loaded', true);
                                    // Wait a bit for data to load
                                    setTimeout(resolve, 200);
                                });
                                loadPromises.push(promise);
                            }
                        }
                    }

                    // After all data is loaded, calculate badges
                    Promise.all(loadPromises).then(() => {
                        setTimeout(() => {
                            console.log('All position data loaded, updating badges for position:', positionId);
                            // Update badges for this position and all parents
                            updateBadgesHierarchy(positionId);
                            // Also recalculate all totals to ensure consistency
                            setTimeout(() => {
                                window.recalculateAllTotals();
                            }, 500);
                        }, 1500);
                    });

                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading employees.</p>');
                console.error(xhr);
            }
        });
    }

    // Load employee/vacant monthly data when accordion is shown
    $(document).on('shown.bs.collapse', '[id^="collapseempAccordion"], [id^="collapsevacantAccordion"]', function() {
        const $accordionBody = $(this).find('.accordion-body');
        const type = $accordionBody.data('type');
        const positionId = $accordionBody.data('position-id');

        // Only load once
        if ($accordionBody.data('loaded')) {
            return;
        }

        if (type === 'employee') {
            const employeeId = $accordionBody.data('employee-id');
            loadEmployeeMonthlyData(employeeId, positionId, $accordionBody);
        } else if (type === 'vacant') {
            const vacantIndex = $accordionBody.data('vacant-index');
            loadVacantMonthlyData(vacantIndex, positionId, $accordionBody);
        }

        $accordionBody.data('loaded', true);
    });

    function loadEmployeeMonthlyData(employeeId, positionId, $container) {
        $container.html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.employee.monthly') }}",
            method: 'GET',
            data: {
                employee_id: employeeId,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const resortCosts = response.resort_costs;
                    const monthCostData = response.month_cost_data;

                    // Get salaries from employees table (same for all 12 months)
                    const currentBasicSalary = parseFloat(response.current_basic_salary || 0);
                    const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0);

                    // Calculate totals
                    const totals = {
                        currentSalary: currentBasicSalary * 12, // Same salary for all 12 months
                        proposedSalary: proposedBasicSalary * 12, // Same salary for all 12 months
                        costs: {}
                    };

                    // Build table header
                    let html = `
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-hover align-middle budget-monthly-table" style="font-size: 0.875rem;">
                                <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <tr>
                                        <th class="text-center" style="width: 80px; font-weight: 600;">Month</th>
                                        <th class="text-center" style="min-width: 120px; font-weight: 600;">Current Basic<br>Salary</th>
                                        <th class="text-center" style="min-width: 120px; font-weight: 600;">Proposed Basic<br>Salary</th>
                                        <th class="text-center" style="width: 80px; font-weight: 600;">Action</th>`;

                    // Add all cost configuration columns
                    resortCosts.forEach(cost => {
                        html += `<th class="text-center" style="min-width: 100px; font-weight: 600;">${cost.particulars || cost.cost_title || 'N/A'}</th>`;
                        totals.costs[cost.id] = 0;
                    });

                    html += `</tr></thead><tbody>`;

                    // Add rows for each month (1-12)
                    for (let m = 1; m <= 12; m++) {
                        const monthData = monthCostData[m] || {};

                        // Use same salary for all months (from employees table)

                        html += `
                            <tr style="transition: all 0.2s;">
                                <td class="text-center" style="font-weight: 500; font-size: 0.813rem;">${months[m-1]}</td>
                                <td class="text-end" style="font-size: 0.813rem;">$${parseFloat(currentBasicSalary).toFixed(2)}</td>
                                <td class="text-end" style="font-size: 0.813rem;">$${parseFloat(proposedBasicSalary).toFixed(2)}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-month-budget"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-employee-id="${employeeId}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="employee"
                                            title="Edit ${months[m-1]} Budget"
                                            style="padding: 0.25rem 0.5rem;">
                                        <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                                    </button>
                                </td>`;

                        // Display cost configuration values (read-only)
                        resortCosts.forEach(cost => {
                            const costData = monthData && monthData[cost.id] ? monthData[cost.id] : null;
                            let costValue = costData && costData.value ? parseFloat(costData.value) : 0;
                            const currency = costData && costData.currency ? costData.currency : 'USD';
                            const originalValue = costValue;

                            // Convert MVR to USD if needed
                            // MVRtoDoller field stores the rate: 1 MVR = X USD
                            // Example: If MVRtoDoller = 1/15.42, then 1 MVR = 1/15.42 USD
                            // So: USD = MVR × MVRtoDoller
                            if (currency === 'MVR' && costValue > 0) {
                                try {
                                    const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;
                                    costValue = costValue * mvrToUsdRate;
                                    console.log(`Cost ${cost.particulars}: ${originalValue} MVR × ${mvrToUsdRate} = ${costValue.toFixed(2)} USD`);
                                } catch (e) {
                                    console.error('MVR conversion error:', e);
                                }
                            }

                            if (!isNaN(costValue)) {
                                totals.costs[cost.id] += parseFloat(costValue);
                            }

                            html += `
                                <td class="text-end"
                                    data-month="${m}"
                                    data-cost-id="${cost.id}"
                                    data-employee-id="${employeeId}"
                                    data-currency="${currency}"
                                    data-original-value="${originalValue}"
                                    data-usd-value="${costValue.toFixed(2)}"
                                    style="font-size: 0.813rem;">
                                    $${parseFloat(costValue).toFixed(2)}
                                </td>`;
                        });

                        html += `</tr>`;
                    }

                    // Add Total row
                    html += `
                        <tr class="table-total-row" style="background-color: #f8f9fa; font-weight: 600; border-top: 2px solid #dee2e6;">
                            <td class="text-center" style="font-weight: 700;">TOTAL</td>
                            <td class="text-end text-primary total-current-salary" style="font-weight: 700;">$${totals.currentSalary.toFixed(2)}</td>
                            <td class="text-end text-success total-proposed-salary" style="font-weight: 700;">$${totals.proposedSalary.toFixed(2)}</td>
                            <td></td>`;

                    resortCosts.forEach(cost => {
                        html += `<td class="text-end text-dark total-cost-${cost.id}" data-cost-id="${cost.id}" style="font-weight: 700;">$${totals.costs[cost.id].toFixed(2)}</td>`;
                    });

                    html += `</tr></tbody></table></div>`;

                    $container.html(html);

                    // After loading, recalculate badges for this position hierarchy
                    setTimeout(function() {
                        console.log('Triggering badge update after employee data load for position:', positionId);
                        // Update badges for this specific position and all parents
                        updateBadgesHierarchy(positionId);
                        // Also do a full recalculation to ensure all badges are accurate
                        window.recalculateAllTotals();
                    }, 800);
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading monthly data.</p>');
                console.error(xhr);
            }
        });
    }

    function calculateEmployeeTotals(employeeId, resortCosts) {
        let totalCurrentSalary = 0;
        let totalProposedSalary = 0;
        const costTotals = {};

        for (let m = 1; m <= 12; m++) {
            const currentSalary = parseFloat($(`.current-salary-${m}[data-employee-id="${employeeId}"]`).val() || 0);
            const proposedSalary = parseFloat($(`.proposed-salary-${m}[data-employee-id="${employeeId}"]`).val() || 0);

            totalCurrentSalary += currentSalary;
            totalProposedSalary += proposedSalary;

            resortCosts.forEach(cost => {
                const costValue = parseFloat($(`.cost-${m}-${cost.id}[data-employee-id="${employeeId}"]`).val() || 0);
                if (!costTotals[cost.id]) {
                    costTotals[cost.id] = 0;
                }
                costTotals[cost.id] += costValue;
            });
        }

        $('#total-current-salary').text('$' + totalCurrentSalary.toFixed(2));
        $('#total-proposed-salary').text('$' + totalProposedSalary.toFixed(2));

        resortCosts.forEach(cost => {
            $(`#total-cost-${cost.id}`).text('$' + (costTotals[cost.id] || 0).toFixed(2));
        });
    }

    function loadVacantMonthlyData(vacantIndex, positionId, $container) {
        $container.html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.monthly') }}",
            method: 'GET',
            data: {
                vacant_index: vacantIndex,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const resortCosts = response.resort_costs;
                    const monthCostData = response.month_cost_data || {};

                    // Get salaries from resort_vacant_budget_costs table (same for all 12 months)
                    const currentBasicSalary = parseFloat(response.current_basic_salary || 0);
                    const proposedBasicSalary = parseFloat(response.proposed_basic_salary || 0);

                    // Calculate totals
                    const totals = {
                        currentSalary: currentBasicSalary * 12, // Same salary for all 12 months
                        proposedSalary: proposedBasicSalary * 12, // Same salary for all 12 months
                        costs: {}
                    };

                    // Build table header
                    let html = `
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-hover align-middle budget-monthly-table" style="font-size: 0.875rem;">
                                <thead style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                    <tr>
                                        <th class="text-center" style="width: 80px; font-weight: 600;">Month</th>
                                        <th class="text-center" style="min-width: 120px; font-weight: 600;">Current Basic<br>Salary</th>
                                        <th class="text-center" style="min-width: 120px; font-weight: 600;">Proposed Basic<br>Salary</th>
                                        <th class="text-center" style="width: 80px; font-weight: 600;">Action</th>`;

                    // Add all cost configuration columns
                    resortCosts.forEach(cost => {
                        html += `<th class="text-center" style="min-width: 100px; font-weight: 600;">${cost.particulars || cost.cost_title || 'N/A'}</th>`;
                        totals.costs[cost.id] = 0;
                    });

                    html += `</tr></thead><tbody>`;

                    // Add rows for each month (1-12)
                    for (let m = 1; m <= 12; m++) {
                        const monthData = monthCostData[m] || {};

                        // Use same salary for all months (from resort_vacant_budget_costs table)

                        html += `
                            <tr style="transition: all 0.2s;">
                                <td class="text-center" style="font-weight: 500; font-size: 0.813rem;">${months[m-1]}</td>
                                <td class="text-end" style="font-size: 0.813rem;">$${parseFloat(currentBasicSalary).toFixed(2)}</td>
                                <td class="text-end" style="font-size: 0.813rem;">$${parseFloat(proposedBasicSalary).toFixed(2)}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning btn-edit-month-budget"
                                            data-month="${m}"
                                            data-month-name="${months[m-1]}"
                                            data-vacant-index="${vacantIndex}"
                                            data-vacant-budget-cost-id="${response.vacant_budget_cost_id}"
                                            data-position-id="${positionId}"
                                            data-department-id="${response.department_id}"
                                            data-type="vacant"
                                            title="Edit ${months[m-1]} Budget"
                                            style="padding: 0.25rem 0.5rem;">
                                        <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                                    </button>
                                </td>`;

                        // Display cost configuration values (read-only)
                        resortCosts.forEach(cost => {
                            const costData = monthData && monthData[cost.id] ? monthData[cost.id] : null;
                            let costValue = costData && costData.value ? parseFloat(costData.value) : 0;
                            const currency = costData && costData.currency ? costData.currency : 'USD';
                            const originalValue = costValue;

                            // Convert MVR to USD if needed
                            // MVRtoDoller field stores the rate: 1 MVR = X USD
                            // Example: If MVRtoDoller = 1/15.42, then 1 MVR = 1/15.42 USD
                            // So: USD = MVR × MVRtoDoller
                            if (currency === 'MVR' && costValue > 0) {
                                try {
                                    const mvrToUsdRate = window.mvrToDollarRate || 1/15.42;
                                    costValue = costValue * mvrToUsdRate;
                                    console.log(`Vacant Cost ${cost.particulars}: ${originalValue} MVR × ${mvrToUsdRate} = ${costValue.toFixed(2)} USD`);
                                } catch (e) {
                                    console.error('MVR conversion error:', e);
                                }
                            }

                            if (!isNaN(costValue)) {
                                totals.costs[cost.id] += parseFloat(costValue);
                            }

                            html += `
                                <td class="text-end"
                                    data-month="${m}"
                                    data-cost-id="${cost.id}"
                                    data-vacant-index="${vacantIndex}"
                                    data-currency="${currency}"
                                    data-original-value="${originalValue}"
                                    data-usd-value="${costValue.toFixed(2)}"
                                    style="font-size: 0.813rem;">
                                    $${parseFloat(costValue).toFixed(2)}
                                </td>`;
                        });

                        html += `</tr>`;
                    }

                    // Add Total row
                    html += `
                        <tr class="table-total-row" style="background-color: #f8f9fa; font-weight: 600; border-top: 2px solid #dee2e6;">
                            <td class="text-center" style="font-weight: 700;">TOTAL</td>
                            <td class="text-end text-primary total-current-salary" style="font-weight: 700;">$${totals.currentSalary.toFixed(2)}</td>
                            <td class="text-end text-success total-proposed-salary" style="font-weight: 700;">$${totals.proposedSalary.toFixed(2)}</td>
                            <td></td>`;

                    resortCosts.forEach(cost => {
                        html += `<td class="text-end text-dark total-cost-${cost.id}" data-cost-id="${cost.id}" style="font-weight: 700;">$${totals.costs[cost.id].toFixed(2)}</td>`;
                    });

                    html += `</tr></tbody></table></div>`;

                    $container.html(html);

                    // After loading, recalculate badges for this position hierarchy
                    setTimeout(function() {
                        console.log('Triggering badge update after vacant data load for position:', positionId);
                        // Update badges for this specific position and all parents
                        updateBadgesHierarchy(positionId);
                        // Also do a full recalculation to ensure all badges are accurate
                        window.recalculateAllTotals();
                    }, 800);
                } else {
                    $container.html('<p class="text-danger">' + response.message + '</p>');
                }
            },
            error: function(xhr) {
                $container.html('<p class="text-danger">Error loading vacant position data.</p>');
                console.error(xhr);
            }
        });
    }

    // Revise budget modal
    $(document).on('click', '.revisebudgetmodal', function() {
        $(".Revise_Budget_id").val($(this).data("budget_id"));
        $(".Revise_Department_id").val($(this).data("dept_id"));
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
                        $(".revisebudgetmodal").prop('disabled', true);
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

    // Edit month budget - Open modal
    $(document).on('click', '.btn-edit-month-budget', function() {
        const $btn = $(this);
        const month = $btn.data('month');
        const monthName = $btn.data('month-name');
        const type = $btn.data('type');
        const positionId = $btn.data('position-id');
        const departmentId = $btn.data('department-id');

        // Set modal title and context
        $('#budgetCostModalLabel').html(`<i class="fas fa-wallet me-2"></i>Budget Cost Assignment - ${monthName}`);
        $('#modalTableType').text(type === 'employee' ? 'Employee' : 'Vacant Position');

        // Store month and type in modal for later use
        $('#budgetCostModal').data('edit-month', month);
        $('#budgetCostModal').data('edit-type', type);
        $('#budgetCostModal').data('position-id', positionId);
        $('#budgetCostModal').data('department-id', departmentId);

        if (type === 'employee') {
            const employeeId = $btn.data('employee-id');
            $('#budgetCostModal').data('employee-id', employeeId);

            // Load employee data for this specific month
            loadEmployeeDataForMonth(employeeId, positionId, departmentId, month);
        } else {
            const vacantIndex = $btn.data('vacant-index');
            const vacantBudgetCostId = $btn.data('vacant-budget-cost-id');
            $('#budgetCostModal').data('vacant-index', vacantIndex);
            $('#budgetCostModal').data('vacant-budget-cost-id', vacantBudgetCostId);

            // Load vacant data for this specific month
            loadVacantDataForMonth(vacantIndex, vacantBudgetCostId, positionId, departmentId, month);
        }

        // Toggle Details select visibility based on type
        if (typeof window.toggleDetailsSelect === 'function') {
            setTimeout(function() {
                window.toggleDetailsSelect();
            }, 100);
        }

        // Show the modal
        $('#budgetCostModal').modal('show');
    });

    // Load employee data for a specific month into modal
    function loadEmployeeDataForMonth(employeeId, positionId, departmentId, month) {
        // Get employee data from the accordion
        const $employeeAccordion = $(`[data-employee-id="${employeeId}"]`).closest('.employee-accordion');
        const employeeName = $employeeAccordion.find('.accordion-button span').first().text().trim();

        // Get rank from data attribute (more reliable) or fallback to badge text
        let employeeRank = $employeeAccordion.data('employee-rank') || '';
        const employeeRankName = $employeeAccordion.data('employee-rank-name') || '';
        const rankBadge = $employeeAccordion.find('.badge.bg-secondary').text().trim();

        // Use the most reliable source for rank - prioritize raw rank value
        const finalRank = employeeRank || employeeRankName || rankBadge || '';

        console.log('Loading employee data:', {
            employeeId: employeeId,
            rawRank: employeeRank,
            rankName: employeeRankName,
            rankBadge: rankBadge,
            finalRank: finalRank
        });

        // Get current values from table
        const currentSalary = $(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).closest('tr').find('td:eq(1)').text().replace('$', '').replace(',', '').trim();
        const proposedSalary = $(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).closest('tr').find('td:eq(2)').text().replace('$', '').replace(',', '').trim();

        // Set salary fields
        $('#formBasicSalary').val(currentSalary || '0.00');
        $('#formCurrentSalary').val(proposedSalary || '0.00');

        // Set hidden fields
        $('#formDepartmentId').val(departmentId);
        $('#formPositionId').val(positionId);
        $('#formTableType').val('employee');
        $('#formEmployeeId').val(employeeId);

        // Load cost configurations with existing values FIRST (before auto-calculations)
        $('.budget-cost-checkbox').each(function() {
            const costId = $(this).data('cost-id');
            const $card = $(this).closest('.budget-cost-card');
            const isPercentageBased = $card.data('is-percentage') == '1';
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const $amountInput = $(`.budget-cost-amount[data-cost-id="${costId}"]`);
            const $hoursInput = $(`.budget-cost-hours[data-cost-id="${costId}"]`);
            const $currencySelect = $(`.budget-cost-currency[data-cost-id="${costId}"]`);

            // Try to get existing value from table
            const $tableCell = $(`td[data-month="${month}"][data-employee-id="${employeeId}"][data-cost-id="${costId}"]`);
            let value = 0;
            let currency = 'USD';

            if ($tableCell.length) {
                // Get currency from data attribute
                currency = $tableCell.data('currency') || 'USD';

                // If currency is MVR, use original MVR value from data attribute
                // Otherwise, use the displayed USD value
                if (currency === 'MVR') {
                    // Get original MVR value from data attribute (stored before conversion to USD)
                    value = parseFloat($tableCell.data('original-value') || 0);
                } else {
                    // For USD, get the displayed value
                    value = parseFloat($tableCell.text().replace('$', '').replace(',', '').trim() || 0);
                }
            }

            // If value exists, check and populate
            if (value > 0) {
                $(this).prop('checked', true);

                // Set currency select to match the currency from table
                if ($currencySelect.length && currency) {
                    $currencySelect.val(currency);
                }

                // For daily frequency items, the value in table is already the monthly total
                // So we store it as is, but we need to calculate the daily rate for future calculations
                if (!isPercentageBased && frequency === 'daily') {
                    // Get year and calculate days in month
                    const year = getCurrentYear();
                    const daysInMonth = new Date(year, month, 0).getDate();
                    if (daysInMonth > 0) {
                        // Calculate daily rate from monthly total
                        const dailyRate = value / daysInMonth;
                        $amountInput.data('original-amount', dailyRate.toFixed(2));
                        $amountInput.val(value.toFixed(2)); // Keep monthly total displayed
                    } else {
                        $amountInput.val(value.toFixed(2));
                    }
                } else {
                    $amountInput.val(value.toFixed(2));
                }

                // For percentage-based items, set hours if available from table
                if (isPercentageBased && $hoursInput.length) {
                    // Try to get hours from data attribute or default to saved value
                    const savedHours = $hoursInput.data('default-hours') || 0;
                    $hoursInput.val(savedHours);
                }
            } else {
                // No existing value - don't set anything yet, let auto-calculation handle it
                $(this).prop('checked', false);
                // Don't set amount yet - auto-calculation will set it
            }
        });

        // NOW set employee rank and salary for overtime and pension auto-calculations
        // This must happen AFTER loading existing values so auto-calc only applies to new items
        if (typeof window.setBudgetModalEmployeeData === 'function') {
            window.setBudgetModalEmployeeData(finalRank, currentSalary, proposedSalary, month);
        }

        // Apply daily frequency multiplier for items without existing values
        // This should happen after loading existing values
        setTimeout(function() {
            if (typeof window.applyDailyFrequencyMultiplier === 'function') {
                window.applyDailyFrequencyMultiplier();
            }
        }, 200);

        // Calculate initial total
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    }

    // Load vacant data for a specific month into modal
    function loadVacantDataForMonth(vacantIndex, vacantBudgetCostId, positionId, departmentId, month) {
        // Get current values from table
        const currentSalary = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).closest('tr').find('td:eq(1)').text().replace('$', '').replace(',', '').trim();
        const proposedSalary = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).closest('tr').find('td:eq(2)').text().replace('$', '').replace(',', '').trim();

        // Set salary fields
        $('#formBasicSalary').val(currentSalary || '0.00');
        $('#formCurrentSalary').val(proposedSalary || '0.00');

        // Set hidden fields
        $('#formDepartmentId').val(departmentId);
        $('#formPositionId').val(positionId);
        $('#formTableType').val('vacant');
        $('#formVacantIndex').val(vacantIndex);

        // Load details value from backend
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.monthly') }}",
            method: 'GET',
            data: {
                vacant_index: vacantIndex,
                position_id: positionId,
                year: year,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success && response.details) {
                    $('#vacantDetailsSelect').val(response.details);
                }
            },
            error: function(xhr) {
                console.error('Error loading details:', xhr);
            }
        });

        // Load cost configurations with existing values FIRST (before auto-calculations)
        $('.budget-cost-checkbox').each(function() {
            const costId = $(this).data('cost-id');
            const $card = $(this).closest('.budget-cost-card');
            const isPercentageBased = $card.data('is-percentage') == '1';
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const $amountInput = $(`.budget-cost-amount[data-cost-id="${costId}"]`);
            const $hoursInput = $(`.budget-cost-hours[data-cost-id="${costId}"]`);
            const $currencySelect = $(`.budget-cost-currency[data-cost-id="${costId}"]`);

            // Try to get existing value from table
            const $tableCell = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"][data-cost-id="${costId}"]`);
            let value = 0;
            let currency = 'USD';

            if ($tableCell.length) {
                // Get currency from data attribute
                currency = $tableCell.data('currency') || 'USD';

                // If currency is MVR, use original MVR value from data attribute
                // Otherwise, use the displayed USD value
                if (currency === 'MVR') {
                    // Get original MVR value from data attribute (stored before conversion to USD)
                    value = parseFloat($tableCell.data('original-value') || 0);
                } else {
                    // For USD, get the displayed value
                    value = parseFloat($tableCell.text().replace('$', '').replace(',', '').trim() || 0);
                }
            }

            // If value exists, check and populate
            if (value > 0) {
                $(this).prop('checked', true);

                // Set currency select to match the currency from table
                if ($currencySelect.length && currency) {
                    $currencySelect.val(currency);
                }

                // For daily frequency items, the value in table is already the monthly total
                // So we store it as is, but we need to calculate the daily rate for future calculations
                if (!isPercentageBased && frequency === 'daily') {
                    // Get year and calculate days in month
                    const year = getCurrentYear();
                    const daysInMonth = new Date(year, month, 0).getDate();
                    if (daysInMonth > 0) {
                        // Calculate daily rate from monthly total
                        const dailyRate = value / daysInMonth;
                        $amountInput.data('original-amount', dailyRate.toFixed(2));
                        $amountInput.val(value.toFixed(2)); // Keep monthly total displayed
                    } else {
                        $amountInput.val(value.toFixed(2));
                    }
                } else {
                    $amountInput.val(value.toFixed(2));
                }

                // For percentage-based items, set hours if available from table
                if (isPercentageBased && $hoursInput.length) {
                    const savedHours = $hoursInput.data('default-hours') || 0;
                    $hoursInput.val(savedHours);
                }
            } else {
                // No existing value - don't set anything yet, let auto-calculation handle it
                $(this).prop('checked', false);
                // Don't set amount yet - auto-calculation will set it
            }
        });

        // NOW set employee rank and salary for overtime and pension auto-calculations
        // For vacant positions, assume "Line Worker" rank for overtime eligibility
        if (typeof window.setBudgetModalEmployeeData === 'function') {
            window.setBudgetModalEmployeeData('Line Worker', currentSalary, proposedSalary, month);
        }

        // Apply daily frequency multiplier for items without existing values
        // This should happen after loading existing values
        setTimeout(function() {
            if (typeof window.applyDailyFrequencyMultiplier === 'function') {
                window.applyDailyFrequencyMultiplier();
            }
        }, 200);

        // Calculate initial total
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    }

    // Update modal total when cost items change
    function updateModalTotal() {
        let total = 0;
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        $('.budget-cost-checkbox:checked').each(function() {
            const costId = $(this).data('cost-id');
            const amount = parseFloat($(`.budget-cost-amount[data-cost-id="${costId}"]`).val() || 0);
            const currency = $(`.budget-cost-currency[data-cost-id="${costId}"]`).val() || 'USD';

            // Convert MVR to USD if needed
            // MVRtoDoller field stores: 1 MVR = X USD, so multiply
            if (currency === 'MVR') {
                total += amount * mvrToUsdRate;
            } else {
                total += amount;
            }
        });

        $('#totalSelectedAmount').text(total.toFixed(2));
    }

    // Submit budget cost assignment from modal
    $(document).on('click', '#submitBudgetCostAssignment', function() {
        const $modal = $('#budgetCostModal');
        const month = $modal.data('edit-month');
        const type = $modal.data('edit-type');
        const positionId = $modal.data('position-id');
        const departmentId = $modal.data('department-id');
        const basicSalary = $('#formBasicSalary').val();
        const currentSalary = $('#formCurrentSalary').val();

        // Collect cost configurations
        const costConfigurations = [];
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);
        const dollarToMvrRate = mvrToUsdRate > 0 ? (1 / mvrToUsdRate) : 15.42; // Inverse for USD to MVR conversion

        $('.budget-cost-checkbox:checked').each(function() {
            const costId = $(this).data('cost-id');
            const $card = $(this).closest('.budget-cost-card');
            let value = parseFloat($(`.budget-cost-amount[data-cost-id="${costId}"]`).val() || 0);
            const currency = $(`.budget-cost-currency[data-cost-id="${costId}"]`).val();
            const hours = $(`.budget-cost-hours[data-cost-id="${costId}"]`).val() || 0;

            // Data is stored in USD in the backend
            // If currency is MVR, convert MVR to USD before saving
            // DollertoMVR rate: 1 USD = X MVR, so USD = MVR / DollertoMVR
            // But we have MVRtoDoller rate: 1 MVR = X USD, so USD = MVR × MVRtoDoller
            if (currency === 'MVR' && value > 0) {
                value = value * mvrToUsdRate; // Convert MVR to USD
            }

            costConfigurations.push({
                resort_budget_cost_id: costId,
                value: value,
                currency: currency,
                hours: hours
            });
        });

        if (type === 'employee') {
            const employeeId = $modal.data('employee-id');
            saveEmployeeMonthBudget(employeeId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations);
        } else {
            const vacantIndex = $modal.data('vacant-index');
            const vacantBudgetCostId = $modal.data('vacant-budget-cost-id');
            saveVacantMonthBudget(vacantIndex, vacantBudgetCostId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations);
        }
    });

    // Save employee month budget via AJAX
    function saveEmployeeMonthBudget(employeeId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations) {
        $.ajax({
            url: "{{ route('resort.budget.hierarchy.employee.update') }}",
            method: 'POST',
            data: {
                employee_id: employeeId,
                position_id: positionId,
                department_id: departmentId,
                year: year,
                monthly_data: [{
                    month: month,
                    current_salary: basicSalary,
                    proposed_salary: currentSalary,
                    cost_configurations: costConfigurations
                }],
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#budgetCostModal').modal('hide');

                    // Update the specific month row immediately
                    updateEmployeeMonthRow(employeeId, month, basicSalary, currentSalary, costConfigurations);

                    // Recalculate totals for the employee table
                    recalculateEmployeeTableTotals(employeeId);

                    // Update badges hierarchically (position -> section -> department -> division)
                    updateBadgesHierarchy(positionId);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error saving employee budget.', 'Error');
                console.error(xhr);
            }
        });
    }

    // Update specific month row in employee table
    function updateEmployeeMonthRow(employeeId, month, basicSalary, currentSalary, costConfigurations) {
        const $row = $(`td[data-month="${month}"][data-employee-id="${employeeId}"]`).first().closest('tr');
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        // Update salary columns
        $row.find('td:eq(1)').text('$' + parseFloat(basicSalary).toFixed(2));
        $row.find('td:eq(2)').text('$' + parseFloat(currentSalary).toFixed(2));

        // Update cost configuration columns
        costConfigurations.forEach(config => {
            const $cell = $row.find(`td[data-cost-id="${config.resort_budget_cost_id}"]`);
            if ($cell.length) {
                // config.value is already in USD (converted before sending to backend)
                let valueInUSD = parseFloat(config.value);
                let originalMvrValue = valueInUSD;

                // If currency is MVR, convert USD back to MVR for data-original-value
                // This is needed so when modal opens again, it shows the MVR value
                // MVRtoDoller stores: 1 MVR = X USD, so MVR = USD / MVRtoDoller
                if (config.currency === 'MVR' && valueInUSD > 0 && mvrToUsdRate > 0) {
                    originalMvrValue = valueInUSD / mvrToUsdRate;
                }

                // Store both USD value and original MVR value as data attributes
                $cell.text('$' + valueInUSD.toFixed(2));
                $cell.attr('data-currency', config.currency);
                $cell.attr('data-original-value', originalMvrValue.toFixed(2));
                $cell.attr('data-usd-value', valueInUSD.toFixed(2));
            }
        });
    }

    // Recalculate employee table totals (all values should already be in USD)
    function recalculateEmployeeTableTotals(employeeId) {
        const $table = $(`td[data-employee-id="${employeeId}"]`).first().closest('table');
        const $totalRow = $table.find('.table-total-row');

        let totalCurrent = 0;
        let totalProposed = 0;
        const costTotals = {};

        // Sum all month rows (excluding total row)
        $table.find('tbody tr').not('.table-total-row').each(function() {
            const $row = $(this);
            totalCurrent += parseFloat($row.find('td:eq(1)').text().replace('$', '').replace(',', '') || 0);
            totalProposed += parseFloat($row.find('td:eq(2)').text().replace('$', '').replace(',', '') || 0);

            // Sum cost configurations (values are already converted to USD in the display)
            $row.find('td[data-cost-id]').each(function() {
                const costId = $(this).data('cost-id');
                const currency = $(this).data('currency') || 'USD';
                const usdValue = $(this).data('usd-value');

                // Use USD value if available, otherwise parse from display text
                const value = usdValue ? parseFloat(usdValue) : parseFloat($(this).text().replace('$', '').replace(',', '') || 0);

                costTotals[costId] = (costTotals[costId] || 0) + value;

                if (currency === 'MVR') {
                    console.log(`Cost ${costId}: MVR converted to USD: $${value.toFixed(2)}`);
                }
            });
        });

        // Update total row using class names
        $totalRow.find('.total-current-salary').text('$' + totalCurrent.toFixed(2));
        $totalRow.find('.total-proposed-salary').text('$' + totalProposed.toFixed(2));

        // Update cost configuration totals using data-cost-id
        Object.keys(costTotals).forEach(costId => {
            const $costCell = $totalRow.find(`td[data-cost-id="${costId}"]`);
            if ($costCell.length) {
                $costCell.text('$' + costTotals[costId].toFixed(2));
            }
        });

        console.log('Employee table totals recalculated (all in USD):', {
            totalCurrent,
            totalProposed,
            costTotals
        });

        // Return combined total for badge update (current + proposed + costs)
        const costTotalsSum = Object.values(costTotals).reduce((sum, val) => sum + val, 0);
        return totalCurrent + totalProposed + costTotalsSum;
    }

    // Save vacant month budget via AJAX
    function saveVacantMonthBudget(vacantIndex, vacantBudgetCostId, positionId, departmentId, month, basicSalary, currentSalary, costConfigurations) {
        const details = $('#vacantDetailsSelect').val() || '';

        $.ajax({
            url: "{{ route('resort.budget.hierarchy.vacant.update') }}",
            method: 'POST',
            data: {
                vacant_index: vacantIndex,
                vacant_budget_cost_id: vacantBudgetCostId,
                position_id: positionId,
                department_id: departmentId,
                year: year,
                details: details,
                monthly_data: [{
                    month: month,
                    current_salary: currentSalary,
                    proposed_salary: basicSalary,
                    cost_configurations: costConfigurations
                }],
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#budgetCostModal').modal('hide');

                    // Update the specific month row immediately
                    updateVacantMonthRow(vacantIndex, month, basicSalary, currentSalary, costConfigurations);

                    // Recalculate totals for the vacant table
                    recalculateVacantTableTotals(vacantIndex);

                    // Update badges hierarchically (position -> section -> department -> division)
                    updateBadgesHierarchy(positionId);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error saving vacant budget.', 'Error');
                console.error(xhr);
            }
        });
    }

    // Update specific month row in vacant table
    function updateVacantMonthRow(vacantIndex, month, basicSalary, currentSalary, costConfigurations) {
        const $row = $(`td[data-month="${month}"][data-vacant-index="${vacantIndex}"]`).first().closest('tr');
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 1/15.42);

        // Update salary columns
        $row.find('td:eq(1)').text('$' + parseFloat(basicSalary).toFixed(2));
        $row.find('td:eq(2)').text('$' + parseFloat(currentSalary).toFixed(2));

        // Update cost configuration columns
        costConfigurations.forEach(config => {
            const $cell = $row.find(`td[data-cost-id="${config.resort_budget_cost_id}"]`);
            if ($cell.length) {
                // config.value is already in USD (converted before sending to backend)
                let valueInUSD = parseFloat(config.value);
                let originalMvrValue = valueInUSD;

                // If currency is MVR, convert USD back to MVR for data-original-value
                // This is needed so when modal opens again, it shows the MVR value
                // MVRtoDoller stores: 1 MVR = X USD, so MVR = USD / MVRtoDoller
                if (config.currency === 'MVR' && valueInUSD > 0 && mvrToUsdRate > 0) {
                    originalMvrValue = valueInUSD / mvrToUsdRate;
                }

                // Store both USD value and original MVR value as data attributes
                $cell.text('$' + valueInUSD.toFixed(2));
                $cell.attr('data-currency', config.currency);
                $cell.attr('data-original-value', originalMvrValue.toFixed(2));
                $cell.attr('data-usd-value', valueInUSD.toFixed(2));
            }
        });
    }

    // Recalculate vacant table totals
    function recalculateVacantTableTotals(vacantIndex) {
        const $table = $(`td[data-vacant-index="${vacantIndex}"]`).first().closest('table');
        const $totalRow = $table.find('.table-total-row');

        let totalCurrent = 0;
        let totalProposed = 0;
        const costTotals = {};

        // Sum all month rows (excluding total row)
        $table.find('tbody tr').not('.table-total-row').each(function() {
            const $row = $(this);
            totalCurrent += parseFloat($row.find('td:eq(1)').text().replace('$', '').replace(',', '') || 0);
            totalProposed += parseFloat($row.find('td:eq(2)').text().replace('$', '').replace(',', '') || 0);

            // Sum cost configurations
            $row.find('td[data-cost-id]').each(function() {
                const costId = $(this).data('cost-id');
                const value = parseFloat($(this).text().replace('$', '').replace(',', '') || 0);
                costTotals[costId] = (costTotals[costId] || 0) + value;
            });
        });

        // Update total row using class names
        $totalRow.find('.total-current-salary').text('$' + totalCurrent.toFixed(2));
        $totalRow.find('.total-proposed-salary').text('$' + totalProposed.toFixed(2));

        // Update cost configuration totals using data-cost-id
        Object.keys(costTotals).forEach(costId => {
            const $costCell = $totalRow.find(`td[data-cost-id="${costId}"]`);
            if ($costCell.length) {
                $costCell.text('$' + costTotals[costId].toFixed(2));
            }
        });

        console.log('Vacant table totals recalculated:', {
            totalCurrent,
            totalProposed,
            costTotals
        });

        // Return combined total for badge update (current + proposed + costs)
        const costTotalsSum = Object.values(costTotals).reduce((sum, val) => sum + val, 0);
        return totalCurrent + totalProposed + costTotalsSum;
    }

    // Update badges hierarchically from position up to division
    function updateBadgesHierarchy(positionId) {
        console.log('Updating badges for position:', positionId);

        // Find the position accordion - try multiple methods
        let $positionAccordion = $(`.position-accordion[data-position-id="${positionId}"]`);

        // If not found, try finding by accordion-body
        if (!$positionAccordion.length) {
            $positionAccordion = $(`.accordion-body[data-position-id="${positionId}"]`).closest('.position-accordion');
        }

        // If still not found, try finding through employee/vacant accordions
        if (!$positionAccordion.length) {
            $positionAccordion = $(`.accordion-body[data-position-id="${positionId}"][data-type]`).closest('.position-accordion');
        }

        if ($positionAccordion.length) {
            console.log('Found position accordion');

            // Calculate position total from all tables
            const positionTotal = calculatePositionTotal($positionAccordion);
            console.log('Position total:', positionTotal);

            // Update position badge
            const $positionBadge = $positionAccordion.find('.positionGrandTotal');
            if ($positionBadge.length) {
                $positionBadge.text('Budget: $ ' + positionTotal.toFixed(2));
                console.log('Updated position badge to:', positionTotal);
            }

            // Find and update parent section (if exists)
            const $sectionAccordion = $positionAccordion.closest('.section-accordion');
            if ($sectionAccordion.length) {
                console.log('Found section accordion');
                const sectionTotal = calculateSectionTotal($sectionAccordion);
                console.log('Section total:', sectionTotal);

                const $sectionBadge = $sectionAccordion.find('.sectionGrandTotal');
                if ($sectionBadge.length) {
                    $sectionBadge.text('Budget: $ ' + sectionTotal.toFixed(2));
                    console.log('Updated section badge to:', sectionTotal);
                }
            }

            // Find and update parent department
            const $deptAccordion = $positionAccordion.closest('.department-accordion');
            if ($deptAccordion.length) {
                console.log('Found department accordion');
                const deptTotal = calculateDepartmentTotal($deptAccordion);
                console.log('Department total:', deptTotal);

                const $deptBadge = $deptAccordion.find('.departmentGrandTotal');
                if ($deptBadge.length) {
                    $deptBadge.text('Budget: $ ' + deptTotal.toFixed(2));
                    console.log('Updated department badge to:', deptTotal);
                }
            }

            // Find and update parent division
            const $divisionAccordion = $positionAccordion.closest('.division-accordion');
            if ($divisionAccordion.length) {
                console.log('Found division accordion');
                const divisionTotal = calculateDivisionTotal($divisionAccordion);
                console.log('Division total:', divisionTotal);

                const $divisionBadge = $divisionAccordion.find('.divisionGrandTotal');
                if ($divisionBadge.length) {
                    $divisionBadge.text('Budget: $ ' + divisionTotal.toFixed(2));
                    console.log('Updated division badge to:', divisionTotal);
                }
            }
        } else {
            console.error('Position accordion not found for position ID:', positionId);
        }
    }

    // Calculate position total from all employee and vacant tables
    function calculatePositionTotal($positionElement) {
        let total = 0;

        // Find the accordion body that contains the tables
        const $positionBody = $positionElement.find('.accordion-body[data-position-id]').first();

        if ($positionBody.length) {
            // Get all budget monthly tables (both employee and vacant)
            $positionBody.find('.budget-monthly-table').each(function() {
                const $table = $(this);
                const $totalRow = $table.find('.table-total-row');

                if ($totalRow.length) {
                    // Get the current basic salary total (column 2, index 1)
                    const currentSalaryTotal = parseFloat($totalRow.find('.total-current-salary').text().replace('$', '').replace(',', '').trim() || 0);

                    // Get the proposed basic salary total (column 3, index 2)
                    const proposedSalaryTotal = parseFloat($totalRow.find('.total-proposed-salary').text().replace('$', '').replace(',', '').trim() || 0);

                    // Sum all cost configuration totals using data-cost-id attribute
                    let costConfigTotal = 0;
                    $totalRow.find('td[data-cost-id]').each(function() {
                        const costValue = parseFloat($(this).text().replace('$', '').replace(',', '').trim() || 0);
                        if (!isNaN(costValue)) {
                            costConfigTotal += costValue;
                        }
                    });

                    // Add current salary + proposed salary + all cost configurations for this table
                    const tableTotal = currentSalaryTotal + proposedSalaryTotal + costConfigTotal;
                    total += tableTotal;

                    console.log('Table total:', {
                        currentSalary: currentSalaryTotal,
                        proposedSalary: proposedSalaryTotal,
                        costConfigs: costConfigTotal,
                        tableTotal: tableTotal
                    });
                }
            });
        }

        console.log('Position total calculated (including current & proposed salary):', total);
        return total;
    }

    // Calculate section total from all positions
    function calculateSectionTotal($sectionElement) {
        let total = 0;

        // Find all positions within this section
        $sectionElement.find('.position-accordion').each(function() {
            total += calculatePositionTotal($(this));
        });

        return total;
    }

    // Calculate department total from all sections and direct positions
    function calculateDepartmentTotal($deptElement) {
        let total = 0;

        // Sum all sections
        $deptElement.find('.section-accordion').each(function() {
            total += calculateSectionTotal($(this));
        });

        // Sum direct positions (not in sections)
        const $deptBody = $deptElement.find('> .accordion-item > [id^="collapseDept"]').first();
        if ($deptBody.length) {
            $deptBody.find('> .accordion-body > .position-accordion, > .accordion-body > .ms-3 > .position-accordion').each(function() {
                // Make sure this position is not inside a section
                if ($(this).closest('.section-accordion').length === 0) {
                    total += calculatePositionTotal($(this));
                }
            });
        }

        return total;
    }

    // Calculate division total from all departments
    function calculateDivisionTotal($divisionElement) {
        let total = 0;

        $divisionElement.find('.department-accordion').each(function() {
            total += calculateDepartmentTotal($(this));
        });

        return total;
    }

    // Event listeners for modal cost configuration changes
    $(document).on('change', '.budget-cost-checkbox, .budget-cost-amount, .budget-cost-currency', function() {
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });

    // When current basic salary changes, recalculate pension and update total
    $(document).on('input change', '#formCurrentSalary', function() {
        const currentSalary = parseFloat($(this).val() || 0);

        // Recalculate pension amounts
        $('.budget-cost-card[data-is-percentage="1"]').each(function() {
            const $card = $(this);
            const isOvertimeItem = $card.data('is-overtime') == '1';

            // Only auto-calculate for non-overtime percentage items (like Pension)
            if (!isOvertimeItem) {
                const costId = $card.data('cost-id');
                const percentage = parseFloat($card.find('.budget-cost-percentage').val() || 0);
                const year = new Date().getFullYear();
                const calculatedAmount = currentSalary * (percentage / 100);
                $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));
            }
        });

        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });

    // When proposed basic salary changes, update total
    $(document).on('input change', '#formBasicSalary', function() {
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });
});
</script>

@include('resorts.renderfiles.budget_cost_modal')

@endsection

