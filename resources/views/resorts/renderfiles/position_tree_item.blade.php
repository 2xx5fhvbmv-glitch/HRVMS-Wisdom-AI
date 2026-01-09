{{-- Position Tree Item --}}
<div class="accordion mb-2 position-accordion" id="{{ $accordionId }}">
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading{{ $accordionId }}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $accordionId }}" aria-expanded="false"
                    aria-controls="collapse{{ $accordionId }}">
                <i class="fas fa-user-tie me-2"></i>
                <span>{{ $positionName }}</span>
                <span class="badge badge-info ms-2 small">Filled: {{ $positionData['max_counts']['max_filledcount'] }}</span>
                <span class="badge badge-warning ms-1 small">Vacant: {{ $positionData['max_counts']['max_vacantcount'] }}</span>
                <span class="badge badge-dark ms-2 small positionGrandTotal">Budget: ${{ number_format($positionGrandTotal ?? 0, 2) }}</span>
            </button>
        </h2>

        <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse"
             aria-labelledby="heading{{ $accordionId }}">
            <div class="accordion-body p-3">

                {{-- Combined Employee and Vacant Table --}}
                <div class="table-responsive table-wrapper">
                    <table class="table table-sm table-hover align-middle mb-0 table-sticky">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap sticky-col sticky-col-1">Name</th>
                                <th class="text-nowrap sticky-col sticky-col-2">Status</th>
                                <th class="text-nowrap sticky-col sticky-col-3">Rank</th>
                                <th class="text-nowrap sticky-col sticky-col-4">Nationality</th>
                                <th class="text-nowrap text-end sticky-col sticky-col-5">Current Basic Salary</th>
                                <th class="text-nowrap text-end sticky-col sticky-col-6">Proposed Basic Salary</th>
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
                                        // Get MVR to Dollar conversion rate
                                        $mvrToDollarRate = 1/15.42; // Default value (1 MVR = 1/15.42 USD)
                                        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', auth()->guard('resort-admin')->user()->resort_id)->first();
                                        if ($resortSettings && $resortSettings->DollertoMVR) {
                                            $mvrToDollarRate = 1/$resortSettings->DollertoMVR;
                                        }

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
                                    @endphp
                                    <tr data-row-id="employee-{{ $employee->emp_id }}">
                                        <td class="sticky-col sticky-col-1">{{ ucwords(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) }}</td>
                                        <td class="sticky-col sticky-col-2"><span class="badge badge-success small">Filled</span></td>
                                        <td class="sticky-col sticky-col-3">
                                            @php
                                                $Rank = config('settings.Position_Rank');
                                                $AvailableRank = !empty($employee->rank) && array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                            @endphp
                                            {{ $AvailableRank }}
                                        </td>
                                        <td class="sticky-col sticky-col-4">{{ $employee->nationality ?? '-' }}</td>
                                        <td class="text-end sticky-col sticky-col-5 basic-salary-cell" data-value="{{ $displayBasicSalary }}">${{ number_format($displayBasicSalary, 2) }}</td>
                                        <td class="text-end sticky-col sticky-col-6 current-salary-cell" data-value="{{ $displayCurrentSalary }}">${{ number_format($displayCurrentSalary, 2) }}</td>
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
                                                data-currency="{{ $costCurrency }}">{{ $displaySymbol }}{{ number_format($displayValue, 2) }}</td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            @endif

                            {{-- Vacant Positions - Each as a separate row --}}
                            @if($positionData['max_counts']['max_vacantcount'] > 0)
                                @for($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++)
                                    @php
                                        // Get MVR to Dollar conversion rate
                                        $mvrToDollarRate = 1/15.42; // Default value (1 MVR = 1/15.42 USD)
                                        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', auth()->guard('resort-admin')->user()->resort_id)->first();
                                        if ($resortSettings && $resortSettings->DollertoMVR) {
                                            $mvrToDollarRate = 1/$resortSettings->DollertoMVR;
                                        }

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
                                        <td class="text-muted sticky-col sticky-col-1">vacant {{ $i }}</td>
                                        <td class="sticky-col sticky-col-2"><span class="badge badge-warning small">Vacant</span></td>
                                        <td class="sticky-col sticky-col-3">
                                            @php
                                                $Rank = config('settings.Position_Rank');
                                                $AvailableRank = array_key_exists($positionData['rank'], $Rank) ? $Rank[$positionData['rank']] : '';
                                            @endphp
                                            {{ $AvailableRank }}
                                        </td>
                                        <td class="text-muted sticky-col sticky-col-4">-</td>
                                        <td class="text-end sticky-col sticky-col-5 basic-salary-cell {{ $vacantBasicSalary > 0 ? '' : 'text-muted' }}" data-value="{{ $vacantBasicSalary }}">
                                            ${{ number_format($vacantBasicSalary, 2) }}
                                        </td>
                                        <td class="text-end sticky-col sticky-col-6 current-salary-cell {{ $vacantCurrentSalary > 0 ? '' : 'text-muted' }}" data-value="{{ $vacantCurrentSalary }}">
                                            ${{ number_format($vacantCurrentSalary, 2) }}
                                        </td>
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
                                            <td class="text-end scrollable-col cost-cell {{ $vacantConfig ? '' : 'text-muted' }}"
                                                data-cost-id="{{ $cost->id }}"
                                                data-value="{{ $costValue }}"
                                                data-currency="{{ $costCurrency }}">{{ $displaySymbol }}{{ number_format($displayValue, 2) }}</td>
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
                                    // Get MVR to Dollar conversion rate
                                    $mvrToDollarRate = 1/15.42; // Default value (1 MVR = 1/15.42 USD)
                                    $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', auth()->guard('resort-admin')->user()->resort_id)->first();
                                    if ($resortSettings && $resortSettings->DollertoMVR) {
                                        $mvrToDollarRate = 1/$resortSettings->DollertoMVR;
                                    }

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

                                    // Calculate grand total for this position
                                    $positionGrandTotal = $totalBasicSalary + $totalCurrentSalary + array_sum($costTotals);

                                    $totalRows = count($positionData['employees'] ?? []) + $positionData['max_counts']['max_vacantcount'];
                                @endphp
                                <tr class="table-secondary fw-bold border-top border-2">
                                    <td class="sticky-col sticky-col-1 fw-bold">TOTAL</td>
                                    <td class="sticky-col sticky-col-2"></td>
                                    <td class="sticky-col sticky-col-3"></td>
                                    <td class="sticky-col sticky-col-4 text-end"><small>{{ $totalRows }} Position(s)</small></td>
                                    <td class="text-end sticky-col sticky-col-5 fw-bold">${{ number_format($totalBasicSalary, 2) }}</td>
                                    <td class="text-end sticky-col sticky-col-6 fw-bold">${{ number_format($totalCurrentSalary, 2) }}</td>
                                    @foreach ($resortCosts as $cost)
                                        <td class="text-end scrollable-col fw-bold">${{ number_format($costTotals[$cost->id], 2) }}</td>
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
/* Sticky Column Styles */
.table-wrapper {
    position: relative;
    overflow-x: auto;
}

.table-sticky {
    border-collapse: separate;
    border-spacing: 0;
}

/* Sticky columns with solid white background */
.sticky-col {
    position: sticky;
    background-color: #ffffff !important;
    z-index: 10;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

/* Header sticky columns with light background */
.table-light .sticky-col {
    background-color: #f8f9fa !important;
}

/* Scrollable columns with solid white background */
.scrollable-col {
    background-color: #ffffff !important;
    min-width: 120px;
}

/* Header scrollable columns */
.table-light .scrollable-col {
    background-color: #f8f9fa !important;
}

/* Ensure table rows have solid backgrounds */
.table-sticky tbody tr {
    background-color: #ffffff !important;
}

.table-sticky tbody tr:hover {
    background-color: #f8f9fa !important;
}

.table-sticky tbody tr:hover td {
    background-color: #f8f9fa !important;
}

/* Totals row styling */
.table-sticky tbody tr.table-secondary td {
    background-color: #e2e3e5 !important;
}

.table-sticky tbody tr.table-secondary:hover td {
    background-color: #d6d8db !important;
}

/* Define left positions for each sticky column */
.sticky-col-1 {
    left: 0;
    min-width: 150px;
}

.sticky-col-2 {
    left: 150px;
    min-width: 90px;
}

.sticky-col-3 {
    left: 240px;
    min-width: 100px;
}

.sticky-col-4 {
    left: 340px;
    min-width: 110px;
}

.sticky-col-5 {
    left: 450px;
    min-width: 180px;
}

.sticky-col-6 {
    left: 630px;
    min-width: 130px;
    border-right: 2px solid #dee2e6;
}

/* Custom scrollbar */
.table-wrapper::-webkit-scrollbar {
    height: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Ensure proper alignment */
.table-sticky thead th,
.table-sticky tbody td {
    white-space: nowrap;
}
</style>
