<!-- Budget Cost Assignment Modal -->
<div class="modal fade" id="budgetCostModal" tabindex="-1" aria-labelledby="budgetCostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="budgetCostModalLabel">
                        <i class="fas fa-wallet me-2"></i>Budget Cost Assignment
                    </h5>
                    <small class="text-muted">
                        <span class="me-3"><i class="fas fa-building me-1"></i><strong>Dept:</strong> <span id="modalDepartmentName">-</span></span>
                        <span class="me-3"><i class="fas fa-user-tie me-1"></i><strong>Position:</strong> <span id="modalPositionName">-</span></span>
                        <span><i class="fas fa-tag me-1"></i><strong>Type:</strong> <span id="modalTableType">-</span></span>
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: #f8f9fa; padding: 1.5rem; max-height: 65vh; overflow-y: auto;">
                <form id="budgetCostAssignmentForm">
                    @csrf
                    <input type="hidden" name="department_id" id="formDepartmentId">
                    <input type="hidden" name="position_id" id="formPositionId">
                    <input type="hidden" name="table_type" id="formTableType">
                    <input type="hidden" name="employee_id" id="formEmployeeId">
                    <input type="hidden" name="vacant_index" id="formVacantIndex">
                    @php
                        // MVRtoDoller field stores: 1 MVR = X USD
                        // Default: 1 MVR = 0.065 USD (approximately 15.42 MVR = 1 USD)
                        $mvrToDollarRate = 1/15.42; // Default value
                        $resortSettings = \App\Models\ResortSiteSettings::where('resort_id', auth()->guard('resort-admin')->user()->resort_id)->first();
                        if ($resortSettings && $resortSettings->DollertoMVR) {
                            $mvrToDollarRate = 1/$resortSettings->DollertoMVR;
                        }
                    @endphp
                    <input type="hidden" id="mvrToDollarRate" value="{{ $mvrToDollarRate }}" title="Exchange Rate: 1 MVR = {{ $mvrToDollarRate }} USD">
                    <!-- Salary Configuration Section -->
                      <!-- Details Select Box - Only visible for vacant positions -->
                      <div class="row mb-3 justify-content-end align-items-end" id="detailsSelectContainer" style="display: none;">
                        <div class="col-md-3 ">
                            <label class="form-label fw-bold" for="vacantDetailsSelect">
                                Details:
                            </label>
                            <select class="form-select" id="vacantDetailsSelect" name="details" style="min-width: 200px;">
                                <option value="">Select Details</option>
                                <option value="Xpat Only">For Xpat Only</option>
                                <option value="Locals Only">For Locals Only</option>
                                <option value="Both">For Both</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">

                            <label class="form-label fw-bold">
                                Current Basic Salary
                            </label>
                            <input type="number"
                                    class="form-control form-control-lg"
                                    id="formBasicSalary"
                                    name="basic_salary"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter current basic salary">

                        </div>
                        <div class="col-md-6">

                            <label class="form-label fw-bold">
                                Proposed Basic Salary
                            </label>
                            <input type="number"
                                    class="form-control form-control-lg"
                                    id="formCurrentSalary"
                                    name="current_salary"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter proposed basic salary">

                        </div>

                    </div>

                    <hr class="my-3">

                    <!-- Cost Items Grid - 3 Columns -->
                    <div class="row g-3" id="budgetCostTableBody">
                        @if(!empty($resortCosts))
                            @foreach($resortCosts as $cost)
                                @php
                                    // Check if this is a percentage-based item (Overtime, Pension, etc.)
                                    // Remove all spaces and special characters for more robust matching
                                    $particularsOriginal = $cost->particulars ?? '';
                                    $costTitle = $cost->cost_title ?? '';
                                    $particularsLower = strtolower(trim($particularsOriginal));
                                    $costTitleLower = strtolower(trim($costTitle));
                                    $particularsClean = strtolower(preg_replace('/[\s\-_]+/', '', $particularsOriginal));
                                    $costTitleClean = strtolower(preg_replace('/[\s\-_]+/', '', $costTitle));

                                    // SUPER AGGRESSIVE overtime detection - multiple fallback checks
                                    $isOvertimeItem = false;

                                    // PRIMARY CHECK: Exact match for known overtime items
                                    $knownOvertimeNames = [
                                        'overtime - holiday',
                                        'overtime - normal',
                                        'overtime-holiday',
                                        'overtime-normal',
                                        'ot - holiday',
                                        'ot - normal',
                                        'ot-holiday',
                                        'ot-normal',
                                        'overtime holiday',
                                        'overtime normal'
                                    ];

                                    if (in_array($particularsLower, $knownOvertimeNames) || in_array($costTitleLower, $knownOvertimeNames)) {
                                        $isOvertimeItem = true;
                                    }

                                    // SECONDARY CHECK: Contains "overtime" anywhere
                                    if (!$isOvertimeItem && (
                                        strpos($particularsLower, 'overtime') !== false ||
                                        strpos($particularsClean, 'overtime') !== false ||
                                        strpos($costTitleLower, 'overtime') !== false ||
                                        strpos($costTitleClean, 'overtime') !== false)) {
                                        $isOvertimeItem = true;
                                    }

                                    // TERTIARY CHECK: Pattern matching for OT variations
                                    if (!$isOvertimeItem && (
                                        preg_match('/\b(ot|overtime)[\s\-_]*(holiday|normal)\b/i', $particularsOriginal) ||
                                        preg_match('/\b(ot|overtime)[\s\-_]*(holiday|normal)\b/i', $costTitle))) {
                                        $isOvertimeItem = true;
                                    }

                                    // FALLBACK CHECK: If name contains "holiday" AND amount is between 1-3 (typical OT rates)
                                    if (!$isOvertimeItem &&
                                        (strpos($particularsLower, 'holiday') !== false || strpos($costTitleLower, 'holiday') !== false) &&
                                        is_numeric($cost->amount) && $cost->amount >= 1 && $cost->amount <= 3) {
                                        $isOvertimeItem = true;
                                    }

                                    $isPensionItem = (strpos($particularsLower, 'pension') !== false) || (strpos($costTitleLower, 'pension') !== false);

                                    // FORCE all overtime items to be treated as percentage-based with hours field
                                    // This overrides any database configuration
                                    $isPercentageBased = $isOvertimeItem || $isPensionItem;

                                    // Enhanced debug logging with all checks
                                    \Log::info("Budget Cost Debug - ID: {$cost->id} | Particulars: '{$particularsOriginal}' | Title: '{$costTitle}' | Amount: {$cost->amount} | CostType: " . ($cost->cost_type ?? 'N/A') . " | IsOvertime: " . ($isOvertimeItem ? 'YES' : 'NO') . " | IsPercentage: " . ($isPercentageBased ? 'YES' : 'NO'));
                                @endphp
                                <div class="col-md-4">
                                    <div class="budget-cost-card"
                                         data-cost-id="{{ $cost->id }}"
                                         data-is-percentage="{{ $isPercentageBased ? '1' : '0' }}"
                                         data-is-overtime="{{ $isOvertimeItem ? '1' : '0' }}"
                                         data-cost-name="{{ $cost->particulars }}"
                                         data-particulars-lower="{{ $particularsLower }}"
                                         data-cost-details="{{ $cost->details ?? '' }}"
                                         data-frequency="{{ $cost->frequency ?? 'Month' }}"
                                         data-original-amount="{{ $cost->amount }}"
                                         data-debug-info="Original: {{ $particularsOriginal }} | IsOT: {{ $isOvertimeItem ? 'YES' : 'NO' }} | IsPerc: {{ $isPercentageBased ? 'YES' : 'NO' }}">
                                        <div class="card-header-custom">
                                            <input type="checkbox"
                                                   class="form-check-input budget-cost-checkbox"
                                                   id="cost_{{ $cost->id }}"
                                                   name="budget_costs[{{ $cost->id }}][selected]"
                                                   value="1"
                                                   data-cost-id="{{ $cost->id }}">
                                            <label for="cost_{{ $cost->id }}" class="cost-label">
                                                {{ $cost->particulars }}
                                                @if($isOvertimeItem)
                                                    <span class="badge bg-success ms-1" style="font-size: 0.7rem;">✓ OT-DETECTED</span>
                                                @else
                                                    <span class="badge bg-warning ms-1" style="font-size: 0.7rem;">FIXED</span>
                                                @endif
                                            </label>
                                        </div>
                                        <div class="row card-body-custom">
                                            @if($isPercentageBased)
                                                <!-- For percentage-based items -->
                                                @if($isOvertimeItem)
                                                    <!-- Overtime items: Show Multiplier + Hours + Calculated Amount -->
                                    <div class="form-group-custom col-6">
                                        <label class="field-label">Percentage</label>
                                        <input type="number"
                                               class="form-control budget-cost-percentage"
                                               name="budget_costs[{{ $cost->id }}][percentage]"
                                               value="{{ $cost->amount }}"
                                               step="0.01"
                                               min="0"
                                               data-cost-id="{{ $cost->id }}"
                                               readonly
                                               style="background-color: #f0f0f0; cursor: not-allowed; font-weight: 500;"
                                               placeholder="0.00"
                                               title="Overtime rate multiplier: 1.25 for Normal OT, 1.50 for Holiday OT">
                                    </div>
                                    <div class="form-group-custom col-6">
                                        <label class="field-label text-primary fw-bold">
                                            <i class="fas fa-clock me-1"></i>Hours (Editable)
                                            <small class="rank-restriction-note text-danger" style="display: none; font-size: 0.65rem; font-weight: normal;">
                                                <br>Only for Line Worker/Supervisor
                                            </small>
                                        </label>
                                        @php
                                            // Set default hours to 2 for easy per-day calculation
                                            // Users can adjust this value and calculation will update automatically
                                            $defaultHours = 2;
                                            $otType = 'normal';
                                            if (stripos($cost->particulars, 'holiday') !== false) {
                                                $otType = 'holiday';
                                            }
                                        @endphp
                                        <input type="number"
                                               class="form-control budget-cost-hours"
                                               name="budget_costs[{{ $cost->id }}][hours]"
                                               value="{{ $defaultHours }}"
                                               step="0.01"
                                               min="0"
                                               data-cost-id="{{ $cost->id }}"
                                               data-default-hours="{{ $defaultHours }}"
                                               data-ot-type="{{ $otType }}"
                                               style="border: 2px solid #0d6efd; font-weight: 600; background-color: #fff;"
                                               placeholder="Enter hours"
                                               title="Enter overtime hours - calculation updates automatically">
                                    </div>
                                    <div class="form-group-custom col-12 mt-2">
                                        <label class="field-label">Calculated Amount</label>
                                        <input type="number"
                                               class="form-control budget-cost-amount"
                                               name="budget_costs[{{ $cost->id }}][value]"
                                               value="0.00"
                                               step="0.01"
                                               min="0"
                                               data-cost-id="{{ $cost->id }}"
                                               readonly
                                               style="background-color: #f0f0f0; cursor: not-allowed; font-weight: 500;"
                                               placeholder="0.00"
                                               title="Formula: (Basic Salary ÷ Days in Month ÷ 8) × Multiplier × Hours">
                                        <small class="text-muted" style="font-size: 0.7rem; display: block; margin-top: 3px;">
                                            Formula: (Basic Salary ÷ Days ÷ 8) × {{ $cost->amount }}x × Hours
                                        </small>
                                    </div>
                                                @else
                                                    <!-- Pension/Other percentage items: Show only Percentage + Calculated Amount (no hours) -->
                                    <div class="form-group-custom col-6">
                                        <label class="field-label">Percentage (%)</label>
                                        <input type="number"
                                               class="form-control budget-cost-percentage"
                                               name="budget_costs[{{ $cost->id }}][percentage]"
                                               value="{{ $cost->amount }}"
                                               step="0.01"
                                               min="0"
                                               data-cost-id="{{ $cost->id }}"
                                               readonly
                                               style="background-color: #f0f0f0; cursor: not-allowed; font-weight: 500;"
                                               placeholder="0.00"
                                               title="Percentage rate for calculation">
                                    </div>
                                    <div class="form-group-custom col-6">
                                        <label class="field-label">Calculated Amount</label>
                                        <input type="number"
                                               class="form-control budget-cost-amount"
                                               name="budget_costs[{{ $cost->id }}][value]"
                                               value="0.00"
                                               step="0.01"
                                               min="0"
                                               data-cost-id="{{ $cost->id }}"
                                               readonly
                                               style="background-color: #f0f0f0; cursor: not-allowed; font-weight: 500;"
                                               placeholder="0.00"
                                               title="Auto-calculated: Percentage × Current Basic Salary">
                                        <small class="text-muted" style="font-size: 0.7rem; display: block; margin-top: 3px;">
                                            Formula: {{ $cost->amount }}% × Current Basic Salary
                                        </small>
                                    </div>
                                                @endif
                                                <input type="hidden" class="budget-cost-currency" name="budget_costs[{{ $cost->id }}][currency]" value="USD" data-cost-id="{{ $cost->id }}">
                                                <input type="hidden" class="budget-cost-hours" name="budget_costs[{{ $cost->id }}][hours]" value="0" data-cost-id="{{ $cost->id }}" @if(!$isOvertimeItem) data-no-hours="1" @endif>
                                            @else
                                                <!-- For fixed amount items -->
                                                <div class="form-group-custom col-8">
                                                    <label class="field-label">Amount
                                                        @if(strtolower($cost->frequency ?? '') == 'daily')
                                                            <small class="text-muted" style="font-size: 0.65rem; display: block; margin-top: 2px;">
                                                                (Daily × Days in Month)
                                                            </small>
                                                        @endif
                                                    </label>
                                                    <input type="number"
                                                           class="form-control budget-cost-amount"
                                                           name="budget_costs[{{ $cost->id }}][value]"
                                                           value="{{ $cost->amount }}"
                                                           step="0.01"
                                                           min="0"
                                                           data-cost-id="{{ $cost->id }}"
                                                           data-original-amount="{{ $cost->amount }}"
                                                           placeholder="0.00">
                                                </div>
                                                <div class="form-group-custom col-4">
                                                    <label class="field-label">Currency</label>
                                                    <select class="form-select budget-cost-currency"
                                                            name="budget_costs[{{ $cost->id }}][currency]"
                                                            data-cost-id="{{ $cost->id }}">
                                                        <option value="USD" {{ $cost->amount_unit == 'USD' ? 'selected' : '' }}>USD</option>
                                                        <option value="MVR" {{ $cost->amount_unit == 'MVR' ? 'selected' : '' }}>MVR</option>
                                                    </select>
                                                </div>
                                            @endif
                                        </div>
                                        <input type="hidden" name="budget_costs[{{ $cost->id }}][cost_id]" value="{{ $cost->id }}">
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="total-display">
                        <small class="text-muted">Total Selected Amount:</small>
                        <h4 class="mb-0 text-primary"><strong>$<span id="totalSelectedAmount">0.00</span></strong></h4>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="submitBudgetCostAssignment">
                            <i class="fas fa-check me-1"></i>Submit Assignment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Budget Cost Card - 3 Column Layout */
.budget-cost-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.budget-cost-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.card-header-custom {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    gap: 10px;
}

.cost-label {
    margin: 0;
    font-weight: 500;
    font-size: 14px;
    color: #495057;
    cursor: pointer;
    flex: 1;
}

.card-body-custom {
    padding: 15px;
    flex: 1;
}

.form-group-custom {
    margin-bottom: 0;
}

.field-label {
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.budget-cost-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.budget-cost-card input[type="checkbox"]:checked + label {
    color: #0d6efd;
}

.budget-cost-amount,
.budget-cost-currency {
    font-size: 14px;
    height: 38px;
}

.total-display {
    padding: 10px 15px;
    background: #e7f3ff;
    border-radius: 8px;
}
</style>

<script>
$(document).ready(function() {
    // Debug: Log all budget cost cards and their detection status
    console.log('=== Budget Cost Card Debug ===');
    $('.budget-cost-card').each(function() {
        const costName = $(this).data('cost-name');
        const isOvertime = $(this).data('is-overtime');
        const isPercentage = $(this).data('is-percentage');
        const debugInfo = $(this).data('debug-info');
        console.log(`Cost: "${costName}" | IsOvertime: ${isOvertime} | IsPercentage: ${isPercentage}`);
        console.log(`  Debug: ${debugInfo}`);
    });
    console.log('=== End Debug ===');

    // Store employee rank for overtime validation
    let employeeRank = '';
    let currentBasicSalary = 0;
    let currentMonth = new Date().getMonth() + 1; // Default to current month

    // Function to get days in a month for a given year and month
    // Expose globally so it can be called from other scripts
    window.getDaysInMonth = function(year, month) {
        // month is 1-12, but Date constructor expects 0-11 for month
        // So we use month (1-12) and day 0 to get the last day of the previous month
        // which gives us the last day of the current month
        return new Date(year, month, 0).getDate();
    };

    // Function to get current year from page
    // Expose globally so it can be called from other scripts
    window.getCurrentYear = function() {
        // Try to get year from year filter select
        const yearSelect = $('#yearFilter');
        if (yearSelect.length && yearSelect.val()) {
            return parseInt(yearSelect.val());
        }
        // Fallback to current year
        return new Date().getFullYear();
    };

    // Function to apply daily frequency multiplier to cost amounts
    // Expose globally so it can be called from other scripts
    window.applyDailyFrequencyMultiplier = function() {
        const month = $('#budgetCostModal').data('edit-month') || new Date().getMonth() + 1;
        const year = window.getCurrentYear();
        const daysInMonth = window.getDaysInMonth(year, month);

        console.log(`Applying daily frequency multiplier for month ${month}/${year} (${daysInMonth} days)`);

        $('.budget-cost-card').each(function() {
            const $card = $(this);
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const isPercentage = $card.data('is-percentage') == '1';

            // Only apply to fixed amount items (not percentage-based) with daily frequency
            if (!isPercentage && frequency === 'daily') {
                const costId = $card.data('cost-id');
                const $amountInput = $card.find('.budget-cost-amount');
                // Get original amount from data attribute first, then fallback to input value
                let originalAmount = parseFloat($amountInput.data('original-amount') || 0);

                // If no original amount stored, try to get it from the input's initial value
                if (originalAmount === 0) {
                    originalAmount = parseFloat($card.data('original-amount') || $amountInput.attr('value') || 0);
                    // Store it for future use
                    $amountInput.data('original-amount', originalAmount);
                }

                if (originalAmount > 0) {
                    // Calculate monthly amount: daily amount × days in month
                    const monthlyAmount = originalAmount * daysInMonth;
                    $amountInput.val(monthlyAmount.toFixed(2));

                    console.log(`Daily cost "${$card.data('cost-name')}": $${originalAmount} × ${daysInMonth} days = $${monthlyAmount.toFixed(2)}`);
                }
            }
        });
    };

    // Function to calculate overtime amount
    // Formula: (Basic Salary ÷ Days in Month ÷ 8 Hours) × Multiplier × Hours
    function calculateOvertimeAmount(multiplier, hours, basicSalary, month) {
        if (!basicSalary || basicSalary <= 0) {
            console.log('Cannot calculate OT: Basic salary is 0 or invalid');
            return 0;
        }

        if (!hours || hours <= 0) {
            return 0;
        }

        // Get days in the specific month
        const year = new Date().getFullYear();
        const daysInMonth = new Date(year, month, 0).getDate();

        // Step 1: Calculate hourly rate
        // Hourly Rate = Basic Salary ÷ Days in Month ÷ 8 Hours
        const dailySalary = basicSalary / daysInMonth;
        const hourlyRate = dailySalary / 8;

        // Step 2: Apply overtime multiplier (1.25 for Normal, 1.50 for Holiday)
        // The multiplier is already stored as 1.25 or 1.50, NOT as percentage
        // Overtime Hourly Rate = Hourly Rate × Multiplier
        const overtimeHourlyRate = hourlyRate * multiplier;

        // Step 3: Calculate total overtime amount
        // Total = Overtime Hourly Rate × Hours
        const totalAmount = overtimeHourlyRate * hours;

        console.log(`OT Calculation:
            Basic Salary: $${basicSalary}
            Month: ${month} (${daysInMonth} days)
            Daily Salary: $${dailySalary.toFixed(4)}
            Hourly Rate: $${hourlyRate.toFixed(4)}
            OT Multiplier: ${multiplier}x (NOT percentage)
            OT Hourly Rate: $${overtimeHourlyRate.toFixed(4)}
            Hours: ${hours}
            Total OT Amount: $${totalAmount.toFixed(2)}`);

        return totalAmount;
    }

    // Function to calculate pension amount (percentage of CURRENT basic salary)
    // Pension - Employer Contribution is calculated based on Current Basic Salary, not Proposed
    function calculatePensionAmount(percentage, currentBasicSalary) {
        if (!currentBasicSalary || currentBasicSalary <= 0) {
            return 0;
        }

        // Formula: Current Basic Salary × (Percentage / 100)
        // Pension is calculated on the current salary, not the proposed salary
        const totalAmount = currentBasicSalary * (percentage / 100);

        return totalAmount;
    }

    // Auto-calculate pension amounts when modal data is set
    // Pension is calculated based on CURRENT Basic Salary (not Proposed)
    function autoCalculatePensionAmounts(currentBasicSalary) {
        console.log('Auto-calculating pension based on Current Basic Salary:', currentBasicSalary);

        $('.budget-cost-card[data-is-percentage="1"]').each(function() {
            const $card = $(this);
            const isOvertimeItem = $card.data('is-overtime') == '1';
            const costName = $card.data('cost-name') || '';

            // Only auto-calculate for non-overtime percentage items (like Pension)
            if (!isOvertimeItem) {
                const costId = $card.data('cost-id');
                const percentage = parseFloat($card.find('.budget-cost-percentage').val() || 0);
                const calculatedAmount = calculatePensionAmount(percentage, currentBasicSalary);
                $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));

                // Auto-select pension checkbox if it's a pension item
                const isPension = costName.toLowerCase().includes('pension');
                if (isPension && calculatedAmount > 0) {
                    $card.find('.budget-cost-checkbox').prop('checked', true);
                    console.log(`Auto-selected and calculated ${costName}: ${percentage}% of $${currentBasicSalary} = $${calculatedAmount.toFixed(2)}`);
                }
            }
        });
    }

    // Auto-calculate overtime amounts with default hours when modal opens
    function autoCalculateOvertimeAmounts(basicSalary, month) {
        console.log('Auto-calculating overtime with default hours...');
        $('.budget-cost-card[data-is-overtime="1"]').each(function() {
            const $card = $(this);
            const costId = $card.data('cost-id');
            const costName = $card.data('cost-name');
            const $hoursInput = $card.find('.budget-cost-hours');
            const multiplier = parseFloat($card.find('.budget-cost-percentage').val() || 0);

            // Get default hours from the input field
            const defaultHours = parseFloat($hoursInput.val() || 0);
            const otType = $hoursInput.data('ot-type');

            if (defaultHours > 0 && basicSalary > 0) {
                // Calculate overtime amount with default hours
                const calculatedAmount = calculateOvertimeAmount(multiplier, defaultHours, basicSalary, month);
                $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));

                console.log(`Auto-calculated ${costName}: ${defaultHours} hours × ${multiplier}x = $${calculatedAmount.toFixed(2)}`);
            }
        });
    }

    // Function to check if overtime is allowed based on rank
    // Only Line Workers (rank 6) and Supervisors (rank 5) can have overtime hours
    function isOvertimeAllowed(rank) {
        if (!rank) {
            console.log('Overtime check - No rank provided | Allowed: false');
            return false;
        }

        // Convert to string and normalize
        const rankStr = String(rank).trim();
        const rankLower = rankStr.toLowerCase();
        const rankClean = rankLower.replace(/[\s\-_]+/g, ''); // Remove spaces, hyphens, underscores

        // PRIMARY CHECK: Rank codes from config/settings.php
        // '5' => 'SUP' (Supervisor)
        // '6' => 'LINE WORKERS' (Line Worker)
        const allowedRankCodes = ['5', '6'];
        const isAllowedByCode = allowedRankCodes.includes(rankStr);

        // SECONDARY CHECK: Text representations from config
        const allowedRankTexts = ['sup', 'lineworkers', 'line workers', 'supervisor', 'line worker'];
        const isAllowedByText = allowedRankTexts.some(allowed => {
            const allowedClean = allowed.replace(/[\s\-_]+/g, '');
            return rankLower === allowed || rankClean === allowedClean || rankLower.includes(allowed);
        });

        // TERTIARY CHECK: Keyword matching for flexibility
        const containsSup = rankLower.includes('sup');
        const containsLine = rankLower.includes('line');
        const isAllowedByKeyword = containsSup || containsLine;

        const isAllowed = isAllowedByCode || isAllowedByText || isAllowedByKeyword;

        console.log(`Overtime Rank Check:
            Raw: "${rank}"
            Code Match: ${isAllowedByCode} (looking for '5' or '6')
            Text Match: ${isAllowedByText} (looking for SUP or LINE WORKERS)
            Keyword Match: ${isAllowedByKeyword}
            FINAL RESULT: ${isAllowed ? '✅ ALLOWED' : '❌ DENIED'}`);

        return isAllowed;
    }

    // When hours input changes, calculate the amount
    $(document).on('input', '.budget-cost-hours', function() {
        const $card = $(this).closest('.budget-cost-card');
        const costId = $(this).data('cost-id');
        const hours = parseFloat($(this).val() || 0);
        const multiplier = parseFloat($card.find('.budget-cost-percentage').val() || 0);
        const isOvertimeItem = $card.data('is-overtime') == '1';

        // Check if overtime is allowed for this employee
        if (isOvertimeItem && !isOvertimeAllowed(employeeRank)) {
            // Force hours to 0 and show warning
            $(this).val(0);
            $card.find('.budget-cost-amount').val('0.00');
            toastr.warning(`Overtime is only allowed for Line Workers and Supervisors. Current rank: ${employeeRank}`, 'Not Allowed', {
                positionClass: 'toast-bottom-right',
                timeOut: 3000
            });
            return;
        }

        // Calculate amount only if overtime is allowed
        const calculatedAmount = calculateOvertimeAmount(multiplier, hours, currentBasicSalary, currentMonth);
        $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));

        // Update modal total
        updateModalTotal();
    });

    // Function to fetch holiday hours for a specific month
    function fetchHolidayHoursForMonth(year, month, $hoursInput, $card) {
        $.ajax({
            url: "{{ route('resort.budget.holiday.hours') }}",
            method: 'GET',
            data: {
                year: year,
                month: month
            },
            success: function(response) {
                if (response.success && response.holiday_hours !== undefined) {
                    const holidayHours = response.holiday_hours;
                    $hoursInput.val(holidayHours);

                    // Recalculate the amount with the new hours
                    const multiplier = parseFloat($card.find('.budget-cost-percentage').val() || 0);
                    const calculatedAmount = calculateOvertimeAmount(multiplier, holidayHours, currentBasicSalary, month);
                    $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));

                    // Update modal total
                    updateModalTotal();

                    console.log(`Auto-populated Overtime Holiday hours for ${year}-${month}: ${holidayHours} hours`);
                } else {
                    console.error('Failed to fetch holiday hours:', response);
                }
            },
            error: function(xhr) {
                console.error('Error fetching holiday hours:', xhr);
                toastr.error('Failed to fetch holiday hours. Please try again.', 'Error', {
                    positionClass: 'toast-bottom-right',
                    timeOut: 3000
                });
            }
        });
    }

    // Check if a cost is overtime holiday
    function isOvertimeHoliday($card) {
        const costName = ($card.data('cost-name') || '').toLowerCase();
        const otType = $card.find('.budget-cost-hours').data('ot-type');
        return otType === 'holiday' || costName.includes('holiday');
    }

    // When checkbox is changed for overtime items
    $(document).on('change', '.budget-cost-checkbox', function() {
        const $card = $(this).closest('.budget-cost-card');
        const isOvertimeItem = $card.data('is-overtime') == '1';
        const isChecked = $(this).is(':checked');
        const costName = $card.data('cost-name');

        if (isOvertimeItem && isChecked && !isOvertimeAllowed(employeeRank)) {
            // Prevent selection of overtime for non-eligible ranks
            $(this).prop('checked', false);
            toastr.warning(`Overtime "${costName}" is only allowed for Line Workers and Supervisors. Current rank: ${employeeRank}`, 'Not Allowed', {
                positionClass: 'toast-bottom-right',
                timeOut: 3000
            });
            return;
        }

        // If overtime holiday is checked, auto-populate hours for the current month
        if (isOvertimeItem && isChecked && isOvertimeHoliday($card)) {
            const $hoursInput = $card.find('.budget-cost-hours');
            const year = window.getCurrentYear();
            const month = currentMonth || $('#budgetCostModal').data('edit-month') || new Date().getMonth() + 1;

            // Fetch and populate holiday hours
            fetchHolidayHoursForMonth(year, month, $hoursInput, $card);
        }

        // Update total when checkbox changes
        updateModalTotal();
    });

    // Update modal total calculation
    window.updateModalTotal = function() {
        let total = 0;
        const mvrToUsdRate = parseFloat($('#mvrToDollarRate').val() || 0.065);

        console.log('=== Modal Total Calculation ===');
        console.log(`Exchange Rate (MVRtoDoller): 1 MVR = ${mvrToUsdRate} USD`);

        // Add Current Basic Salary to total
        const currentBasicSalary = parseFloat($('#formCurrentSalary').val() || 0);
        if (currentBasicSalary > 0) {
            total += currentBasicSalary;
            console.log(`Current Basic Salary: $${currentBasicSalary.toFixed(2)} USD`);
        }

        // Add Proposed Basic Salary to total
        const proposedBasicSalary = parseFloat($('#formBasicSalary').val() || 0);
        if (proposedBasicSalary > 0) {
            total += proposedBasicSalary;
            console.log(`Proposed Basic Salary: $${proposedBasicSalary.toFixed(2)} USD`);
        }

        $('.budget-cost-checkbox:checked').each(function() {
            const costId = $(this).data('cost-id');
            const costName = $(this).closest('.budget-cost-card').data('cost-name');
            const amount = parseFloat($(`.budget-cost-amount[data-cost-id="${costId}"]`).val() || 0);
            const currency = $(`.budget-cost-currency[data-cost-id="${costId}"]`).val() || 'USD';

            let amountInUSD = amount;

            // Convert MVR to USD if needed
            // MVRtoDoller field stores: 1 MVR = X USD
            // Formula: USD = MVR × MVRtoDoller
            // Example: If amount is 1000 MVR and rate is 0.065, then USD = 1000 × 0.065 = 65 USD
            if (currency === 'MVR') {
                amountInUSD = amount * mvrToUsdRate;
                console.log(`${costName}: ${amount} MVR × ${mvrToUsdRate} = $${amountInUSD.toFixed(2)} USD`);
            } else {
                console.log(`${costName}: $${amount} USD (no conversion)`);
            }

            total += amountInUSD;
        });

        console.log(`TOTAL (in USD): $${total.toFixed(2)}`);
        console.log('=== End Modal Total ===');

        $('#totalSelectedAmount').text(total.toFixed(2));
    };

    // Store employee rank and salary when modal opens
    window.setBudgetModalEmployeeData = function(rank, basicSalary, proposedSalary, month) {
        employeeRank = rank || '';
        currentBasicSalary = parseFloat(basicSalary) || 0;
        const currentProposedSalary = parseFloat(proposedSalary) || 0;
        currentMonth = parseInt(month) || new Date().getMonth() + 1;

        console.log('Employee data set:', {
            rank: employeeRank,
            basicSalary: currentBasicSalary,
            proposedSalary: currentProposedSalary,
            month: currentMonth
        });

        // Auto-calculate pension amounts for non-overtime percentage items
        // Pension uses CURRENT Basic Salary, not Proposed
        autoCalculatePensionAmounts(currentBasicSalary);

        // Auto-calculate overtime amounts for overtime items with default hours
        // For overtime holiday, we'll populate hours when checkbox is checked
        autoCalculateOvertimeAmounts(currentBasicSalary, currentMonth);

        // Auto-populate holiday hours for already checked overtime holiday items
        const currentYear = window.getCurrentYear();
        $('.budget-cost-card[data-is-overtime="1"]').each(function() {
            const $card = $(this);
            if (isOvertimeHoliday($card)) {
                const $checkbox = $card.find('.budget-cost-checkbox');
                if ($checkbox.is(':checked')) {
                    const $hoursInput = $card.find('.budget-cost-hours');
                    fetchHolidayHoursForMonth(currentYear, currentMonth, $hoursInput, $card);
                }
            }
        });

        // Update modal total after auto-calculations
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }

        // Disable/Enable overtime items based on rank
        if (!isOvertimeAllowed(employeeRank)) {
            console.log(`Overtime NOT allowed for rank: "${employeeRank}" - Disabling overtime fields`);
            $('.budget-cost-card[data-is-overtime="1"]').each(function() {
                const $card = $(this);
                const costName = $card.data('cost-name');

                // Set hours to 0 and disable the input
                $card.find('.budget-cost-hours').val(0).prop('disabled', true).prop('readonly', true);

                // Set calculated amount to 0.00 and keep it disabled
                $card.find('.budget-cost-amount').val('0.00');

                // Disable checkbox - overtime cannot be selected for this rank
                $card.find('.budget-cost-checkbox').prop('disabled', true).prop('checked', false);

                // Show rank restriction note
                $card.find('.rank-restriction-note').show();

                // Visual indication - slightly dimmed but still visible
                $card.css('opacity', '0.6');

                // Add visual indicator with red border to show it's disabled
                $card.find('.budget-cost-hours').css({
                    'background-color': '#f8f9fa',
                    'cursor': 'not-allowed',
                    'border-color': '#dc3545',
                    'border-width': '2px'
                });

                console.log(`Disabled overtime: ${costName}`);
            });
        } else {
            console.log(`Overtime IS allowed for rank: "${employeeRank}" - Enabling overtime fields`);
            $('.budget-cost-card[data-is-overtime="1"]').each(function() {
                const $card = $(this);
                const costName = $card.data('cost-name');

                // Enable hours input
                $card.find('.budget-cost-hours').prop('disabled', false).prop('readonly', false);

                // Enable checkbox
                $card.find('.budget-cost-checkbox').prop('disabled', false);

                // Hide rank restriction note
                $card.find('.rank-restriction-note').hide();

                // Full visibility
                $card.css('opacity', '1');

                // Restore editable styling with blue border
                $card.find('.budget-cost-hours').css({
                    'background-color': '#fff',
                    'cursor': 'text',
                    'border-color': '#0d6efd',
                    'border-width': '2px'
                });

                console.log(`Enabled overtime: ${costName}`);
            });
        }
    };

    // Show/hide Details select box based on table type
    function toggleDetailsSelect() {
        const tableType = $('#formTableType').val();
        if (tableType === 'vacant') {
            $('#detailsSelectContainer').show();
        } else {
            $('#detailsSelectContainer').hide();
            $('#vacantDetailsSelect').val('');
        }
    }

    // Monitor table_type changes
    $(document).on('change', '#formTableType', function() {
        toggleDetailsSelect();
    });

    // Handle Details select change - filter and auto-select cost configurations
    $(document).on('change', '#vacantDetailsSelect', function() {
        const selectedDetails = $(this).val();

        if (!selectedDetails) {
            // If no selection, uncheck all cost configurations
            $('.budget-cost-checkbox').prop('checked', false);
            updateModalTotal();
            return;
        }

        // Determine which cost details to match
        let matchingDetails = [];
        if (selectedDetails === 'Both') {
            matchingDetails = ['Xpat Only', 'Locals Only', 'Both'];
        } else {
            matchingDetails = [selectedDetails, 'Both'];
        }

        // Uncheck all first
        $('.budget-cost-checkbox').prop('checked', false);

        // Check and select matching cost configurations
        $('.budget-cost-card').each(function() {
            const $card = $(this);
            const costDetails = $card.data('cost-details') || '';

            if (matchingDetails.includes(costDetails)) {
                const $checkbox = $card.find('.budget-cost-checkbox');
                $checkbox.prop('checked', true);

                // If it's a percentage-based item, trigger auto-calculation
                const isPercentage = $card.data('is-percentage') == '1';
                if (isPercentage) {
                    const costId = $card.data('cost-id');
                    const isOvertime = $card.data('is-overtime') == '1';

                    if (isOvertime) {
                        // For overtime, use default hours
                        const $hoursInput = $card.find('.budget-cost-hours');
                        const defaultHours = parseFloat($hoursInput.data('default-hours') || 2);
                        $hoursInput.val(defaultHours);

                        // Trigger hours input change to calculate
                        $hoursInput.trigger('input');
                    } else {
                        // For pension/other percentage items, auto-calculate
                        if (typeof window.setBudgetModalEmployeeData === 'function') {
                            // Re-trigger auto-calculation
                            const currentSalary = parseFloat($('#formCurrentSalary').val() || 0);
                            if (currentSalary > 0) {
                                const $percentageInput = $card.find('.budget-cost-percentage');
                                const percentage = parseFloat($percentageInput.val() || 0);
                                const calculatedAmount = calculatePensionAmount(percentage, currentSalary);
                                $card.find('.budget-cost-amount').val(calculatedAmount.toFixed(2));
                            }
                        }
                    }
                }
            }
        });

        // Apply daily frequency multiplier for newly selected items
        if (typeof window.applyDailyFrequencyMultiplier === 'function') {
            window.applyDailyFrequencyMultiplier();
        }

        // Update modal total
        updateModalTotal();
    });

    // Expose toggleDetailsSelect globally for use in other scripts
    window.toggleDetailsSelect = toggleDetailsSelect;

    // Initialize Details select visibility when modal is shown
    $('#budgetCostModal').on('shown.bs.modal', function() {
        // Toggle Details select based on current table type
        toggleDetailsSelect();

        // Apply daily frequency multiplier after a short delay to ensure all data is loaded
        setTimeout(function() {
            if (typeof window.applyDailyFrequencyMultiplier === 'function') {
                window.applyDailyFrequencyMultiplier();
            }
            // Update total after applying daily multipliers
            if (typeof window.updateModalTotal === 'function') {
                window.updateModalTotal();
            }
        }, 100);
    });

    // Reset Details select when modal is hidden
    $('#budgetCostModal').on('hidden.bs.modal', function() {
        // Reset Details select to empty and hide it
        $('#vacantDetailsSelect').val('');
        $('#detailsSelectContainer').hide();

        // Reset daily frequency amounts to original values
        $('.budget-cost-card').each(function() {
            const $card = $(this);
            const frequency = ($card.data('frequency') || '').toLowerCase();
            const isPercentage = $card.data('is-percentage') == '1';

            if (!isPercentage && frequency === 'daily') {
                const $amountInput = $card.find('.budget-cost-amount');
                const originalAmount = parseFloat($amountInput.data('original-amount') || 0);
                if (originalAmount > 0) {
                    $amountInput.val(originalAmount.toFixed(2));
                }
            }
        });
    });

    // Event listeners for salary input fields to update total
    $(document).on('input change', '#formCurrentSalary, #formBasicSalary', function() {
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });

    // When amount input changes for daily frequency items, recalculate based on original amount
    $(document).on('input change', '.budget-cost-amount', function() {
        const $input = $(this);
        const costId = $input.data('cost-id');
        const $card = $input.closest('.budget-cost-card');
        const frequency = ($card.data('frequency') || '').toLowerCase();
        const isPercentage = $card.data('is-percentage') == '1';

        // If it's a daily frequency item and user changes the value, we need to update the original amount
        // so that when month changes, it recalculates correctly
        if (!isPercentage && frequency === 'daily') {
            const month = $('#budgetCostModal').data('edit-month') || new Date().getMonth() + 1;
            const year = window.getCurrentYear();
            const daysInMonth = window.getDaysInMonth(year, month);
            const currentValue = parseFloat($input.val() || 0);

            // Calculate what the daily amount should be based on current value
            // This allows users to edit the monthly total, and we'll recalculate daily rate
            if (daysInMonth > 0 && currentValue > 0) {
                const dailyAmount = currentValue / daysInMonth;
                $input.data('original-amount', dailyAmount.toFixed(2));
                // Also update the card's original amount
                $card.data('original-amount', dailyAmount.toFixed(2));
            }
        }

        // Update total
        if (typeof window.updateModalTotal === 'function') {
            window.updateModalTotal();
        }
    });
});
</script>

