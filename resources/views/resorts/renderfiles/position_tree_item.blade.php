{{-- Position Tree Item --}}
<div class="accordion mb-2 position-accordion" id="{{ $accordionId }}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading{{ $accordionId }}">
            <button class="accordion-button collapsed cb-accordion-btn" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $accordionId }}" aria-expanded="false"
                    aria-controls="collapse{{ $accordionId }}">
                <div class="cb-row-main">
                    <span class="wb-level-tag">Position</span>
                    <span class="wb-group-row-name">{{ $positionName }}</span>
                    <div class="wb-group-row-meta">
                        <span class="cb-count-badge cb-count-filled">Filled: {{ $positionData['max_counts']['max_filledcount'] }}</span>
                        <span class="cb-count-badge cb-count-vacant">Vacant: {{ $positionData['max_counts']['max_vacantcount'] }}</span>
                    </div>
                </div>
                {{-- Canonical per-position total (same aggregator as the section/department/division badges). --}}
                <div class="wb-group-row-budget positionGrandTotal">{!! Common::formatCurrency($positionData['calculated_total'] ?? 0, 'USD') !!}</div>
                <i class="fas fa-chevron-right cb-chevron"></i>
            </button>
        </h2>

        <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse"
             aria-labelledby="heading{{ $accordionId }}">
            <div class="accordion-body p-3">

                {{-- Combined Employee and Vacant Table --}}
                <div class="table-responsive table-wrapper cb-table-wrapper">
                    <table class="table table-sm align-middle mb-0 table-sticky cb-table">
                        <thead>
                            <tr>
                                <th class="text-nowrap sticky-col sticky-col-1">Name</th>
                                <th class="text-nowrap sticky-col sticky-col-2">Status</th>
                                <th class="text-nowrap sticky-col sticky-col-3">Rank</th>
                                <th class="text-nowrap text-nowrap">Nationality</th>
                                <th class="text-nowrap text-end text-nowrap">Current Basic Salary</th>
                                <th class="text-nowrap text-end text-nowrap">Proposed Basic Salary</th>
                                @foreach ($header as $h)
                                    <th class="text-nowrap text-end scrollable-col">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Employees --}}
                            @if(!empty($positionData['employees']) && count($positionData['employees']) > 0)
                                @foreach($positionData['employees'] as $employee)
                                    @php
                                        // $mvrToDollarRate is passed in from the controller (fetched
                                        // once for the whole page — see viewConsolidatedBudget()).
                                        // Use configured salaries from configuration table, show 0 if not available
                                        $displayBasicSalary = $employee->configured_basic_salary ?? 0;
                                        $displayCurrentSalary = $employee->configured_current_salary ?? 0;

                                        // Create cost lookup array for this employee
                                        $employeeCostLookup = [];
                                        if (isset($employee->budget_configurations) && $employee->budget_configurations->isNotEmpty()) {
                                            foreach ($employee->budget_configurations as $config) {
                                                // Convert to USD if needed
                                                $valueInUSD = $config->currency === 'MVR'
                                                    ? $config->value * $mvrToDollarRate
                                                    : $config->value;
                                                $employeeCostLookup[$config->resort_budget_cost_id] = [
                                                    'value' => $valueInUSD,
                                                    'currency' => $config->currency
                                                ];
                                            }
                                        }
                                        $employeeFullName = trim(ucwords(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')));
                                        $employeeInitials = strtoupper(collect(explode(' ', $employeeFullName))->filter()->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode(''));
                                        // Same isDefault check as View Budget's wbAvatarHtml() —
                                        // getResortUserPicture() always returns a URL (a generic
                                        // placeholder image when there's no real photo), so a plain
                                        // truthiness check would never show initials. Comparing
                                        // against the actual default-picture URL is what View Budget
                                        // does to decide when to fall back to initials instead.
                                        $employeeHasRealPicture = !empty($employee->picture) && $employee->picture !== url(config('settings.default_picture'));
                                    @endphp
                                    <tr data-row-id="employee-{{ $employee->emp_id }}">
                                        <td class="sticky-col sticky-col-1">
                                            <div class="cb-name-cell">
                                                @if($employeeHasRealPicture)
                                                    <img class="cb-avatar" src="{{ $employee->picture }}" alt="">
                                                @else
                                                    <span class="cb-avatar cb-avatar-fallback">{{ $employeeInitials ?: '?' }}</span>
                                                @endif
                                                <span>{{ $employeeFullName ?: '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="sticky-col sticky-col-2"><span class="cb-status-badge cb-status-filled">Filled</span></td>
                                        <td class="sticky-col sticky-col-3">
                                            @php
                                                $Rank = config('settings.Position_Rank');
                                                $AvailableRank = !empty($employee->rank) && array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                            @endphp
                                            {{ $AvailableRank }}
                                        </td>
                                        <td class="text-nowrap">{{ $employee->nationality ?? '-' }}</td>
                                        <td class="text-end text-nowrap basic-salary-cell" data-value="{{ $displayBasicSalary }}">@include('resorts.renderfiles.partials.cb_money', ['value' => $displayBasicSalary])</td>
                                        <td class="text-end text-nowrap current-salary-cell" data-value="{{ $displayCurrentSalary }}">@include('resorts.renderfiles.partials.cb_money', ['value' => $displayCurrentSalary])</td>
                                        @foreach ($resortCosts as $cost)
                                            @php
                                                // Use yearly aggregated value or 0 if not configured
                                                $costValue = $employeeCostLookup[$cost->id]['value'] ?? 0;
                                                $costCurrency = $employeeCostLookup[$cost->id]['currency'] ?? 'USD';
                                                // For display: if currency is MVR, show MVR symbol, otherwise USD
                                                $originalValue = $costValue;
                                                if (isset($employeeCostLookup[$cost->id]) && $employeeCostLookup[$cost->id]['currency'] === 'MVR') {
                                                    // If it's MVR, we need to show the original MVR value, not the converted USD value
                                                    // The $costValue is already in USD (converted), so we need to convert back for display
                                                    $originalValue = $costValue / $mvrToDollarRate;
                                                }
                                                $displaySymbol = ($costCurrency === 'MVR') ? 'MVR ' : '$';
                                                $displayValue = ($costCurrency === 'MVR') ? $originalValue : $costValue;
                                            @endphp
                                            <td class="text-end scrollable-col cost-cell"
                                                data-cost-id="{{ $cost->id }}"
                                                data-value="{{ $costValue }}"
                                                data-currency="{{ $costCurrency }}">@if((float) $displayValue == 0)<span class="cb-zero-dash">&mdash;</span>@else{{ $displaySymbol }}{{ number_format($displayValue, 2) }}@endif</td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            @endif

                            {{-- Vacant Positions - Each as a separate row --}}
                            @if($positionData['max_counts']['max_vacantcount'] > 0)
                                @for($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++)
                                    @php
                                        // $mvrToDollarRate is passed in from the controller (fetched
                                        // once for the whole page — see viewConsolidatedBudget()).
                                        // Get vacant configuration if exists, show 0 if not available
                                        $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                                        $vacantBasicSalary = $vacantConfig ? ($vacantConfig['vacant_budget_cost']->basic_salary ?? 0) : 0;
                                        $vacantCurrentSalary = $vacantConfig ? ($vacantConfig['vacant_budget_cost']->current_salary ?? 0) : 0;

                                        // Create cost lookup array for this vacant position
                                        $vacantCostLookup = [];
                                        if ($vacantConfig && isset($vacantConfig['configurations'])) {
                                            foreach ($vacantConfig['configurations'] as $config) {
                                                // Convert to USD if needed
                                                $valueInUSD = $config->currency === 'MVR'
                                                    ? $config->value * $mvrToDollarRate
                                                    : $config->value;
                                                $vacantCostLookup[$config->resort_budget_cost_id] = [
                                                    'value' => $valueInUSD,
                                                    'currency' => $config->currency
                                                ];
                                            }
                                        }
                                    @endphp
                                    <tr data-row-id="vacant-{{ $positionData['position_id'] }}-{{ $i }}">
                                        <td class="sticky-col sticky-col-1">
                                            <div class="cb-name-cell">
                                                <span class="cb-avatar cb-avatar-vacant"><i class="fas fa-user"></i></span>
                                                <span class="text-muted">Vacant {{ $i }}</span>
                                            </div>
                                        </td>
                                        <td class="sticky-col sticky-col-2"><span class="cb-status-badge cb-status-vacant">Vacant</span></td>
                                        <td class="sticky-col sticky-col-3">
                                            @php
                                                $Rank = config('settings.Position_Rank');
                                                $AvailableRank = array_key_exists($positionData['rank'], $Rank) ? $Rank[$positionData['rank']] : '';
                                            @endphp
                                            {{ $AvailableRank }}
                                        </td>
                                        <td class="text-muted text-nowrap">-</td>
                                        <td class="text-end text-nowrap basic-salary-cell" data-value="{{ $vacantBasicSalary }}">@include('resorts.renderfiles.partials.cb_money', ['value' => $vacantBasicSalary])</td>
                                        <td class="text-end text-nowrap current-salary-cell" data-value="{{ $vacantCurrentSalary }}">@include('resorts.renderfiles.partials.cb_money', ['value' => $vacantCurrentSalary])</td>
                                        @foreach ($resortCosts as $cost)
                                            @php
                                                // Use yearly aggregated value or 0 if not configured
                                                $costValue = $vacantCostLookup[$cost->id]['value'] ?? 0;
                                                $costCurrency = $vacantCostLookup[$cost->id]['currency'] ?? 'USD';
                                                // For display: if currency is MVR, show MVR symbol, otherwise USD
                                                $originalValue = $costValue;
                                                if (isset($vacantCostLookup[$cost->id]) && $vacantCostLookup[$cost->id]['currency'] === 'MVR') {
                                                    // If it's MVR, we need to show the original MVR value, not the converted USD value
                                                    $originalValue = $costValue / $mvrToDollarRate;
                                                }
                                                $displaySymbol = ($costCurrency === 'MVR') ? 'MVR ' : '$';
                                                $displayValue = ($costCurrency === 'MVR') ? $originalValue : $costValue;
                                            @endphp
                                            <td class="text-end scrollable-col cost-cell"
                                                data-cost-id="{{ $cost->id }}"
                                                data-value="{{ $costValue }}"
                                                data-currency="{{ $costCurrency }}">@if((float) $displayValue == 0)<span class="cb-zero-dash">&mdash;</span>@else{{ $displaySymbol }}{{ number_format($displayValue, 2) }}@endif</td>
                                        @endforeach

                                    </tr>
                                @endfor
                            @endif

                            {{-- Empty State --}}
                            @if(empty($positionData['employees']) && $positionData['max_counts']['max_vacantcount'] == 0)
                                <tr>
                                    <td colspan="{{ count($header) + 6 }}" class="text-center text-muted py-3">
                                        <small><i class="fas fa-info-circle me-1"></i>No data available for this position</small>
                                    </td>
                                </tr>
                            @endif

                            {{-- Totals Row --}}
                            @if(!empty($positionData['employees']) || $positionData['max_counts']['max_vacantcount'] > 0)
                                @php
                                    // $mvrToDollarRate is passed in from the controller (fetched
                                    // once for the whole page — see viewConsolidatedBudget()).

                                    // Calculate totals
                                    $totalBasicSalary = 0;
                                    $totalCurrentSalary = 0;

                                    // Sum employee salaries (from configuration table only, 0 if not available)
                                    if(!empty($positionData['employees'])) {
                                        foreach($positionData['employees'] as $employee) {
                                            $totalBasicSalary += $employee->configured_basic_salary ?? 0;
                                            $totalCurrentSalary += $employee->configured_current_salary ?? 0;
                                        }
                                    }

                                    // Sum vacant salaries
                                    if($positionData['max_counts']['max_vacantcount'] > 0) {
                                        for($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++) {
                                            $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                                            if ($vacantConfig) {
                                                $totalBasicSalary += $vacantConfig['vacant_budget_cost']->basic_salary ?? 0;
                                                $totalCurrentSalary += $vacantConfig['vacant_budget_cost']->current_salary ?? 0;
                                            }
                                        }
                                    }

                                    // Initialize cost totals array
                                    $costTotals = [];
                                    foreach($resortCosts as $cost) {
                                        $costTotals[$cost->id] = 0;
                                    }

                                    // Calculate cost totals from employee configurations (YEARLY AGGREGATED)
                                    if(!empty($positionData['employees'])) {
                                        foreach($positionData['employees'] as $employee) {
                                            if (isset($employee->budget_configurations) && $employee->budget_configurations->isNotEmpty()) {
                                                foreach ($employee->budget_configurations as $config) {
                                                    // Convert to USD if needed (value is already yearly total)
                                                    $valueInUSD = $config->currency === 'MVR'
                                                        ? $config->value * $mvrToDollarRate
                                                        : $config->value;
                                                    $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                                                }
                                            }
                                        }
                                    }

                                    // Calculate cost totals from vacant configurations (YEARLY AGGREGATED)
                                    if($positionData['max_counts']['max_vacantcount'] > 0) {
                                        for($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++) {
                                            $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                                            if ($vacantConfig && isset($vacantConfig['configurations'])) {
                                                foreach ($vacantConfig['configurations'] as $config) {
                                                    // Convert to USD if needed (value is already yearly total)
                                                    $valueInUSD = $config->currency === 'MVR'
                                                        ? $config->value * $mvrToDollarRate
                                                        : $config->value;
                                                    $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="cb-total-row">
                                    <td class="sticky-col sticky-col-1 fw-bold">TOTAL</td>
                                    <td class="sticky-col sticky-col-2"></td>
                                    <td class="sticky-col sticky-col-3"></td>
                                    <td class="text-nowrap text-end"></td>
                                    <td class="text-end text-nowrap fw-bold">@include('resorts.renderfiles.partials.cb_money', ['value' => $totalBasicSalary])</td>
                                    <td class="text-end text-nowrap fw-bold">@include('resorts.renderfiles.partials.cb_money', ['value' => $totalCurrentSalary])</td>
                                    @foreach ($resortCosts as $cost)
                                        <td class="text-end scrollable-col fw-bold">@include('resorts.renderfiles.partials.cb_money', ['value' => $costTotals[$cost->id]])</td>
                                    @endforeach
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.cb-count-badge {
    font-size: 12px;
    font-weight: 600;
    border-radius: 999px;
    padding: 2px 10px;
}
.cb-count-filled { background: var(--wb-increase-bg); color: var(--wb-increase); }
.cb-count-vacant { background: var(--wb-vacant-bg); color: var(--wb-vacant); }

/* ---- Table wrapper: bounded height so the sticky header has something
   to stick within (mirrors View Budget's .wb-table-scroll). No
   overflow:hidden anywhere on this wrapper or the table itself — that
   combination is what silently breaks position:sticky on th/td (fixed
   on View Budget the same way this session). ---- */
.cb-table-wrapper {
    position: relative;
    overflow: auto;
    max-height: 420px;
    border-radius: 8px;
    border: 1px solid var(--line-2);
}

.cb-table {
    border-collapse: separate;
    border-spacing: 0;
}

.cb-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: var(--teal-3) !important;
    color: var(--teal) !important;
    border-bottom: 2px solid var(--teal) !important;
    white-space: normal !important;
    overflow-wrap: break-word;
    font-size: 12px !important;
    line-height: 1.3 !important;
    font-weight: 700;
    padding: 8px 10px !important;
    vertical-align: middle;
}
.cb-table thead th.sticky-col { z-index: 6; }

.cb-table tbody td { font-size: 0.813rem; }

.cb-table tbody tr:nth-child(even) td { background-color: var(--teal-soft); }
.cb-table tbody tr:hover td { background-color: var(--teal-3) !important; }

.cb-total-row td {
    background-color: var(--line-2) !important;
    border-top: 2px solid var(--teal);
    font-weight: 700;
}
.cb-total-row:hover td { background-color: var(--line-2) !important; }

/* Sticky columns keep an explicit background so scrolled-under content
   doesn't show through — same technique View Budget uses
   (.budget-monthly-table .wb-sticky-col). Scoped under .cb-table rather
   than a bare .sticky-col: Bootstrap 5's own
   ".table > :not(caption) > * > *" rule (which paints row/stripe/hover
   color via an inset box-shadow + background-color: var(--bs-table-bg))
   has slightly higher specificity than a bare single-class selector, so
   without this scoping it silently wins and the sticky columns render
   transparent — letting scrolled-under text/columns show straight
   through them. */
.cb-table .sticky-col {
    position: sticky;
    z-index: 2;
    background-color: #ffffff;
}
.cb-table tbody tr:nth-child(even) .sticky-col { background-color: var(--teal-soft); }
.cb-table tbody tr:hover .sticky-col { background-color: var(--teal-3) !important; }
.cb-total-row .sticky-col { background-color: var(--line-2) !important; }

.sticky-col-3 { border-right: 2px solid var(--line); }

/* ---- Name cell: avatar + name ---- */
.cb-name-cell { display: flex; align-items: center; gap: 8px; }
.cb-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
.cb-avatar-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--teal);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
}
.cb-avatar-vacant {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px dashed var(--faint);
    background: transparent;
    color: var(--faint);
    font-size: 12px;
}

/* ---- Status badges ---- */
.cb-status-badge {
    font-size: 11px;
    font-weight: 600;
    border-radius: 999px;
    padding: 2px 10px;
}
.cb-status-filled { background: var(--wb-increase-bg); color: var(--wb-increase); }
.cb-status-vacant { background: var(--wb-vacant-bg); color: var(--wb-vacant); }

.cb-zero-dash { color: var(--faint); font-weight: 400; }

/* Custom scrollbar */
.cb-table-wrapper::-webkit-scrollbar { height: 8px; width: 8px; }
.cb-table-wrapper::-webkit-scrollbar-track { background: var(--line-2); }
.cb-table-wrapper::-webkit-scrollbar-thumb { background: var(--faint); border-radius: 4px; }

.cb-table tbody td { white-space: nowrap; }

/* Define left positions for each sticky column */
.sticky-col-1 { left: 0; min-width: 170px; }
.sticky-col-2 { left: 170px; min-width: 90px; }
.sticky-col-3 { left: 260px; min-width: 90px; }
</style>
