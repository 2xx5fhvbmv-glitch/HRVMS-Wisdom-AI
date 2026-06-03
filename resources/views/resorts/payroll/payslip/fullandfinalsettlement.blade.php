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
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card">
                    <form id="final-settlement-form" method="POST" data-parsley-validate>
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-xl-4 col-md-6">
                                <label for="select_emp" class="form-label">SELECT EMPLOYEE OR EMPLOYEE ID<span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="select_emp" id="select_emp" onchange="getEmpDetails(this.value)"
                                    data-parsley-required="true" data-parsley-error-message="Please select an employee" data-parsley-errors-container="#select_emp_error">
                                    <option value="">Select Employees</option>
                                    @if($employees)
                                        @foreach($employees as $emp)
                                            {{-- Each option carries the employee's nationality so the
                                                 pension column can be hidden client-side for non-Maldivian
                                                 (foreign) employees — pension contribution is a Maldives-
                                                 specific deduction and shouldn't appear in their F&F. --}}
                                            <option value="{{$emp->employee->id}}"
                                                data-nationality="{{ $emp->employee->nationality }}"
                                                @if(!empty($preselectedEmployeeId) && (int) $preselectedEmployeeId === (int) $emp->employee->id) selected @endif>
                                                {{$emp->employee->Emp_id}} - {{$emp->employee->resortAdmin->full_name}}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="select_emp_error"></div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="empDetails-user">
                                    <div class="img-circle" id="img-circle">
                                        <img src="">
                                    </div>
                                    <div>
                                        <h4> <span class="badge badge-themeNew"></span></h4>
                                        <p></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="resignation_date" class="form-label">RESIGNATION EFFECTIVE DATE<span class="red-mark">*</span></label>
                                <input type="text" id="resignation_date" name="resignation_date" class="form-control" placeholder="RESIGNATION EFFECTIVE DATE"
                                    disabled required >
                            </div>
                            <div class="col-xl-4 col-sm-6">
                                <label for="last_day" class="form-label">LAST WORKING DAY<span class="red-mark">*</span></label>
                                <input type="text" id="last_day" name="last_day" class="form-control" placeholder="LAST WORKING DAY" disabled required>
                            </div>
                        </div>

                        {{-- ════════════════════ EARNINGS ════════════════════
                             Money paid OUT to the employee at settlement:
                             Basic Salary, Earning Salary, Service Charge,
                             Leave Balance + Leave Encashment. Sums to the
                             Allowance Breakdown + Payable Leaves Breakdown
                             totals further down.

                             Money fields use type=text + class="money-field"
                             so JS can format them with thousands separators
                             (1,000.00) — type=number strips commas. All
                             readonly so users can't introduce bogus commas.
                             Submit handler strips commas back before POST so
                             Laravel's `numeric` validator stays happy. ──── --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                <i class="fa-solid fa-arrow-down me-2 text-success"></i>Earnings
                            </div>
                            <div class="card-body fullFinal-block">
                                <div class="row g-md-4 g-3">
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="basic_salary" class="form-label">Basic Salary (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="basic_salary" class="form-control money-field" name="basic_salary"
                                            placeholder="Basic salary" data-parsley-required="true" readonly>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="earned_salary" class="form-label d-flex align-items-center">
                                            <span>Earned Salary (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></span>
                                            {{-- Manual override toggle. Earned Salary is normally
                                                 derived from attendance (worked × daily rate); HR
                                                 can flip this to enter a manual figure when
                                                 attendance is incomplete. Edit ON: pencil → check
                                                 icon, input becomes editable, Gross Earning recomputes
                                                 on blur. Edit OFF: restores the attendance-derived
                                                 value and re-locks the field. --}}
                                            <a href="javascript:void(0)" id="earned_salary_edit_toggle"
                                               class="ms-2 text-themeSkyblue" title="Edit Earned Salary manually"
                                               style="font-size:12px;">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                        </label>
                                        <input type="text" id="earned_salary" class="form-control money-field" name="earned_salary"
                                            placeholder="Earned salary" data-parsley-required="true" readonly>
                                        {{-- Attendance-based earning breakdown — populated by
                                             getEmpDetails() with worked/expected days, daily
                                             rate. Same formula payroll uses (basic ÷ days_in_period)
                                             × (present + day_off). Red banner if attendance gap. --}}
                                        <small id="earning-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="service_charge" class="form-label">Service Charge (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="service_charge" class="form-control money-field"
                                            name="service_charge" placeholder="Service Charge" data-parsley-required="true">
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="leave_balance" class="form-label">Leave Balance (days)<span class="red-mark">*</span></label>
                                        {{-- step="0.01" so HR-edited fractional days (4.60, 5.01, etc.)
                                             pass HTML5 + Parsley validation. Without it, type="number"
                                             defaults to step=1 and rejects any decimal as
                                             "This value seems to be invalid." --}}
                                        <input type="number" name="leave_balance" id="leave_balance" class="form-control" min="0" max="500" step="0.01" placeholder="Leave Balance" readonly data-parsley-required="true" data-parsley-type="number"
                                            data-parsley-min="0" data-parsley-trigger="change">
                                        <small id="leave-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="leave_encashment" class="form-label">LEAVE ENCASHMENT (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="leave_encashment" name="leave_encashment" class="form-control money-field"
                                            placeholder="Leave Encashment" data-parsley-required="true" readonly>
                                        <small id="leave-encashment-formula"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ════════════════════ DEDUCTIONS ════════════════════
                             Money withheld AT settlement: EWT, Pension, and the
                             Loan/Advance recovery. Matches the right-hand
                             "Deductions" column on the reference Final Pay
                             Settlement PDF (MRPS Employee Mandatory Contribution
                             = Pension; Notice Period / Adjustments live in the
                             dynamic Deduction block below). ─────────────────── --}}
                        {{-- ═════════════════════════════════════════════════════════
                             Card order (per HR layout request):
                               1. Earnings (above)
                               2. Allowance Breakdown
                               3. Deductions (EWT / Pension / Loan / Notice)
                               4. Deduction repeater (ad-hoc)
                               5. Leave Balance & Encashment Breakdown
                               6. Settlement Breakdown (totals, last)
                             Moving Allowance up and the ad-hoc Deduction up keeps
                             every settlement input above the Leave Breakdown table
                             so HR reviews inputs first, then sees the rolled-up
                             totals at the bottom.
                             ═════════════════════════════════════════════════════ --}}

                        {{-- ───── Allowance Breakdown (was below — moved up so it
                             sits next to Earnings, since allowances ARE earnings). --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                Allowance Breakdown
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="allowance-details">
                                    <thead>
                                        <tr>
                                            <th>Allowance Name</th>
                                            <th class="text-end">Amount (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="text-end">Total</th>
                                            <th class="text-end" id="total-allowances">0.00 {{ $displayCurrencyCode ?? 'MVR' }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                <i class="fa-solid fa-arrow-up me-2 text-danger"></i>Deductions
                            </div>
                            <div class="card-body fullFinal-block">
                                <div class="row g-md-4 g-3">
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="tax" class="form-label">EWT (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="tax" class="form-control money-field" name="tax"
                                            placeholder="EWT" data-parsley-required="true" readonly>
                                        {{-- Surfaced when taxable income clears the MIRA EWT
                                             threshold but the employee isn't EWT-enrolled or
                                             has no TIN on file. Same warning shape as the
                                             Employee Details "Salary Details" card. --}}
                                        <small id="ewt-warning"
                                               class="form-text d-block text-danger mt-1"
                                               style="font-size:11px; display:none !important;">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                            <span id="ewt-warning-text"></span>
                                        </small>
                                    </div>
                                    {{-- Pension column — only Maldivian (Local) employees
                                         contribute to MRPS. JS toggles visibility based on the
                                         selected employee's data-nationality option attr;
                                         foreigners get the column hidden AND
                                         data-parsley-required stripped so Parsley doesn't
                                         block submit on an invisible field. --}}
                                    <div class="col-xl-4 col-sm-6" id="pension-col">
                                        <label for="pension" class="form-label">PENSION / MRPS (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="pension" class="form-control money-field" name="pension"
                                            placeholder="Pension" data-parsley-required="true" readonly>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="loan_payment" class="form-label">LOAN OR ADVANCE PAYMENT? (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="loan_payment" name="loan_payment" class="form-control money-field"
                                            placeholder="Enter Amount" data-parsley-required="true" readonly>
                                        {{-- Loan vs Salary Advance bucket breakdown — built
                                             from payroll_recovery_schedule rows joined to
                                             payroll_advance. Hidden when nothing's
                                             outstanding so the line doesn't shout 0.00. --}}
                                        <small id="loan-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                    {{-- Notice Period Charge — fetched from the Notice
                                         Period module config (employee_notice_period.period
                                         = required days). Charge = max(0, required − served)
                                         × dailySalary. Field is editable so HR can override
                                         (e.g. management waived part of it) but the
                                         breakdown below shows the computed reference value. --}}
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="notice_period_charge" class="form-label">Notice Period Charge (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)<span class="red-mark">*</span></label>
                                        <input type="text" id="notice_period_charge" name="notice_period_charge"
                                            class="form-control money-field" placeholder="Notice Period Charge"
                                            data-parsley-required="true">
                                        <small id="notice-period-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ───── Deduction repeater (was below — moved up so the
                             ad-hoc rows sit next to the Deductions card and the
                             Leave Breakdown / Settlement totals follow as the
                             final summary cards). --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">Deduction</div>
                            <div class="fullFinal-block">
                                <div class="row g-md-4 g-3">
                                    <!-- Initial deduction row -->
                                    <div class="col-xl-3 col-sm">
                                        <select class="form-select select2t-none deduction-select" data-parsley-required-if="#deduction-amount-first" data-parsley-trigger="change">
                                            <option value="">Select Deduction</option>
                                            @foreach($deductions as $deduction)
                                                <option value="{{ $deduction->id }}" data-unit="{{ $deduction->currency }}">{{ $deduction->deduction_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-sm">
                                        <input type="number" id="deduction-amount-first" class="form-control deduction-amount" placeholder="Enter Amount" data-parsley-type="number" data-parsley-min="0" data-parsley-trigger="change">
                                    </div>
                                    <div class="col-xl-3 col-sm">
                                        <input type="text" class="form-control amount-unit" placeholder="Amount Unit" readonly>
                                    </div>
                                    <div class="col-xl-3 col-auto align-self-end">
                                        <a href="#" class="btn btn-themeSkyblue btn-sm add-fullFinal add-deduction">Add More</a>
                                    </div>
                                </div>
                            </div>
                            <div class="deductions-container"></div>
                        </div>

                        {{-- ───── Leave Balance & Encashment Breakdown ─────
                             ONE table that backs BOTH the "Leave Balance (days)" and
                             "LEAVE ENCASHMENT" inputs above. Each row shows the per-
                             category leave name (Annual / Sick / Day Off / Public Holiday
                             / Other), the unused days carried forward, and the days ×
                             daily_salary value. The Days column totals to the Leave
                             Balance input; the Amount column totals to the LEAVE
                             ENCASHMENT input. Populated by JS in getEmpDetails(). --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head d-flex align-items-center">
                                <span>Leave Balance &amp; Encashment Breakdown</span>
                                <small class="text-muted ms-2 flex-grow-1" style="font-size:12px; font-weight:400;">
                                    Days column → Leave Balance · Amount column → Leave Encashment
                                </small>
                                {{-- Reset button: re-renders the table from the server-supplied
                                     breakdown stashed at last render, discarding HR edits + any
                                     manually-added rows. Disabled until #payable-leaves has a
                                     stash (i.e. after the first employee fetch). --}}
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2"
                                        id="reset-payable-leaves"
                                        title="Reset rows to the values calculated by the system">
                                    <i class="fa-solid fa-rotate-left me-1"></i>Reset to original
                                </button>
                                {{-- Add-row button: appends a manual entry to the table so HR can
                                     record settlement items that aren't in the leave grid (extra
                                     PH credits, day-off carry, ad-hoc adjustments). The row's
                                     Days field is editable and rolls into the totals. --}}
                                <button type="button" class="btn btn-sm btn-themeSkyblue ms-2"
                                        id="add-payable-leave-row">
                                    <i class="fa-solid fa-plus me-1"></i>Add row
                                </button>
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="payable-leaves">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th class="text-end" style="width:120px;">Days</th>
                                            <th class="text-end">Daily Rate</th>
                                            <th class="text-end">Amount (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)</th>
                                            <th style="width:40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th class="text-end" id="payable-leaves-days-total">0.00</th>
                                            <th class="text-end">—</th>
                                            <th class="text-end" id="payable-leaves-amount-total">0.00 {{ $displayCurrencyCode ?? 'MVR' }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- ───── Settlement Details Breakdown ─────
                             Same shape as the review page: per-source row
                             (Basic Salary for N worked days, Service Charge,
                             Leave Days Salary, Allowance) → Gross Earning at
                             the bottom. Lets HR see how the Earning Salary
                             figure is built without leaving the page. All
                             cells are converted to the display currency
                             through mvrToDisplay() in the JS below. --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                Settlement Breakdown
                                <small class="text-muted ms-2" style="font-size:12px; font-weight:400;">
                                    Earnings − Deductions → Net Settlement
                                </small>
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="settlement-details">
                                    <thead>
                                        <tr>
                                            <th>Source</th>
                                            <th class="text-end">Amount (<span class="display-currency-label">{{ $displayCurrencyCode ?? 'MVR' }}</span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="settlement-details-body">
                                        <!-- Populated by JS: earnings → Gross Earning subtotal → deductions → Total Deductions subtotal -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <th class="fw-bold">Net Settlement</th>
                                            <th class="text-end fw-bold" id="settlement-details-total">0.00 {{ $displayCurrencyCode ?? 'MVR' }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="last_working_date" id="last_working_date"/>
                        <input type="hidden" name="payroll_start_date" id="payroll_start_date"/>
                        <input type="hidden" name="payment_mode" id="payment_mode"/>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue  @if(Common::checkRouteWisePermission('payslip.fullandfinalsettlement',config('settings.resort_permissions.create')) == false) d-none @endif">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    select.parsley-error + .select2 .select2-selection {
    border-color: #dc3545 !important; /* red border */
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        var $form = $("#final-settlement-form");
        $form.parsley({
            excluded: 'input[type=button], input[type=submit], input[type=reset]',
            trigger: 'change',
            successClass: 'is-valid',
            errorClass: 'is-invalid'
        });

        // Initialize Select2
        $('.select2t-none').select2({
            allowClear: true,
            closeOnSelect: false
        });

        // Manually trigger Parsley validation when Select2 changes
        $(".select2t-none").on('change', function () {
            var parsleyField = $(this).parsley();
            parsleyField.validate();

            // Add/remove the error class to the Select2 container based on validation
            if (parsleyField.isValid()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            } else {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        // Parsley field validation handler
        window.Parsley.on('field:validated', function (fieldInstance) {
            var $element = fieldInstance.$element;
            if ($element.hasClass('select2t-none')) {
                // Update the Select2 container's appearance
                var $select2Container = $element.next('.select2-container').find('.select2-selection');
                if (fieldInstance.isValid()) {
                    $select2Container.removeClass('is-invalid');
                } else {
                    $select2Container.addClass('is-invalid');
                }
            }
        });

        // Fix for Select2 + Parsley (force Parsley to trigger on change)
        window.Parsley.on('field:validated', function(fieldInstance) {
            if ($(fieldInstance.$element).hasClass('select2-hidden-accessible')) {
                if (fieldInstance.validationResult !== true) {
                    fieldInstance._ui.$errorsWrapper.show();
                } else {
                    fieldInstance._ui.$errorsWrapper.hide();
                }
            }
        })

        // Use event delegation for deduction selects
        $(document).on('change', '.deduction-select', function() {
            let selectedOption = $(this).find(':selected');
            let deductionUnit = selectedOption.data('unit'); // Get unit from selected deduction
            $(this).closest('.row').find('.amount-unit').val(deductionUnit); // Set unit in the closest amount unit field
        });

        // ────────────────────────────────────────────────────────────
        // Pension visibility — pension contribution is Maldives-only,
        // so a foreign (non-Maldivian) employee should never see the
        // PENSION* column. Watch the employee select and toggle the
        // wrapper + validators based on the option's data-nationality.
        // ────────────────────────────────────────────────────────────
        function togglePensionByNationality() {
            var $opt = $('#select_emp option:selected');
            var nat = ($opt.data('nationality') || '').toString().toLowerCase();
            var isLocal = nat === 'maldivian' || nat === 'local';
            var $col = $('#pension-col');
            var $input = $('#pension');
            if (isLocal) {
                $col.show();
                $input.attr('data-parsley-required', 'true');
            } else {
                $col.hide();
                $input.val(0);
                // Strip the required validator on hidden input so Parsley
                // doesn't block submit on a field the user can't see.
                $input.removeAttr('data-parsley-required');
                if ($.fn.parsley) {
                    try { $input.parsley().reset(); } catch (e) {}
                }
            }
        }
        $('#select_emp').on('change', togglePensionByNationality);

        // ────────────────────────────────────────────────────────────
        // Deep-link pre-select. Exit Clearance → "Full And Final
        // Settlement" passes ?empId=<base64>; controller validates +
        // sets $preselectedEmployeeId. If present, populate the select
        // (via Select2's change.select2), kick the AJAX details fetch,
        // and apply the pension toggle.
        // ────────────────────────────────────────────────────────────
        @if(!empty($preselectedEmployeeId))
            $('#select_emp').val('{{ $preselectedEmployeeId }}').trigger('change.select2');
            togglePensionByNationality();
            getEmpDetails('{{ $preselectedEmployeeId }}');
        @endif
    });

    // ────────────────────────────────────────────────────────────────
    // Display-currency wiring. The resort's currency setting (toggled
    // via the top-right currency switcher) decides whether amounts
    // render in MVR or USD. The service computes everything in MVR
    // (canonical); we convert on display only. Raw MVR is kept on
    // `data-raw` per money-field so the submit handler can post raw
    // values back to the server without round-trip conversion error.
    const FNF_DISPLAY_CURRENCY = '{{ $displayCurrencyCode ?? "MVR" }}';
    const FNF_DOLLAR_TO_MVR    = {{ $dollarToMvr ?? 15.42 }};

    // ────────────────────────────────────────────────────────────────
    // renderPayableLeaveRow(row, opts)
    // Appends one row to the Leave Breakdown table — same shape for both
    // server-supplied rows (Annual / Public Holiday / Day Off) and HR-added
    // manual rows. opts.custom=true → editable type-name input + trash icon.
    // Server rows render the leave-type as plain text + an Encashable badge.
    // Reads dailyRate from the table's data-attr so the function can live
    // at module scope (called from getEmpDetails, reset handler, add-row).
    // ────────────────────────────────────────────────────────────────
    function renderPayableLeaveRow(row, opts) {
        opts = opts || {};
        var dailyRate  = parseFloat($('#payable-leaves').data('daily-rate') || 0);
        var encashable = (row.is_encashable !== false);
        var custom     = !!opts.custom;
        var days       = parseFloat(row.available_days || 0);
        var typeCell;
        if (custom) {
            typeCell = '<input type="text" class="form-control form-control-sm payable-leave-type"' +
                ' value="' + $('<i>').text(row.leave_type || '').html() + '"' +
                ' placeholder="Leave / credit name">';
        } else {
            typeCell = (row.leave_type || 'Leave') +
                ' <span class="badge bg-success-subtle text-success ms-1"' +
                ' style="font-size:10px;">Encashable</span>';
        }
        var $tr = $('<tr>' +
            '<td>' + typeCell + '</td>' +
            '<td class="text-end">' +
                '<input type="number" min="0" step="0.01"' +
                ' class="form-control form-control-sm text-end payable-leave-days"' +
                ' value="' + days.toFixed(2) + '">' +
            '</td>' +
            '<td class="text-end">' + formatMoney(mvrToDisplay(dailyRate)) + '</td>' +
            '<td class="text-end payable-leave-amount">' +
                formatMoney(mvrToDisplay(days * dailyRate)) +
            '</td>' +
            '<td class="text-end">' +
                (custom
                    ? '<button type="button" class="btn btn-sm btn-link text-danger p-0 payable-leave-remove"' +
                      ' title="Remove row"><i class="fa-solid fa-trash"></i></button>'
                    : '') +
            '</td>' +
        '</tr>');
        $tr.data('encashable', encashable);
        $('#payable-leaves tbody').append($tr);
    }

    // ────────────────────────────────────────────────────────────────
    // recalcPayableLeaves()
    // Sums the editable Days inputs in the Leave Breakdown table, rewrites
    // each row's Amount cell + the table totals, and pushes the result
    // into the headline #leave_balance / #leave_encashment inputs (which
    // are what the form actually posts). Then recomputeGrossEarning()
    // so the Settlement Breakdown stays consistent with the leave edits.
    // ────────────────────────────────────────────────────────────────
    function recalcPayableLeaves() {
        var dailyRate = parseFloat($('#payable-leaves').data('daily-rate') || 0);
        var daysTotal = 0;
        var amountTotalMvr = 0;
        $('#payable-leaves tbody tr').each(function () {
            var $row  = $(this);
            var days  = parseFloat($row.find('.payable-leave-days').val() || 0);
            if (!isFinite(days) || days < 0) days = 0;
            var amtMvr = days * dailyRate;
            $row.find('.payable-leave-amount').text(
                formatMoney(mvrToDisplay(amtMvr))
            );
            // Only encashable rows hit the totals — non-encashable rows
            // (if any sneak in) still render but contribute zero.
            if ($row.data('encashable') !== false) {
                daysTotal      += days;
                amountTotalMvr += amtMvr;
            }
        });
        $('#payable-leaves-days-total').text(daysTotal.toFixed(2));
        $('#payable-leaves-amount-total')
            .text(formatMoney(mvrToDisplay(amountTotalMvr), FNF_DISPLAY_CURRENCY))
            .data('raw-mvr', amountTotalMvr);

        // Headline inputs the form actually submits. #leave_balance is the
        // total days; #leave_encashment is the amount in the currently-
        // displayed currency (because the input is shown next to a USD/MVR
        // label and the user sees that). Use setFieldValueAndResetValidation
        // when it's available so Parsley re-validates without flashing
        // "invalid" mid-edit.
        var displayAmt = mvrToDisplay(amountTotalMvr);
        if (typeof setFieldValueAndResetValidation === 'function') {
            setFieldValueAndResetValidation('#leave_balance', daysTotal.toFixed(2));
            setFieldValueAndResetValidation('#leave_encashment', displayAmt);
        } else {
            $('#leave_balance').val(daysTotal.toFixed(2)).trigger('change');
            $('#leave_encashment').val(displayAmt).trigger('change');
        }

        // Persisted snapshot of the per-row breakdown — every row's
        // current type + days + per-row amount as HR left it. Posted
        // alongside the headline totals so the review page can render
        // the SAME numbers HR signed off on, rather than re-running
        // FinalSettlementService::getLeaveBalance() and showing tomorrow's
        // freshly-accrued Day Off count instead of yesterday's signed value.
        var breakdownSnapshot = [];
        $('#payable-leaves tbody tr').each(function () {
            var $r = $(this);
            var $typeCell = $r.find('.payable-leave-type');
            var typeName = $typeCell.length
                ? ($typeCell.val() || '').trim()
                : $r.find('td').first().text().replace(/Encashable/, '').trim();
            if (!typeName) return;
            var days = parseFloat($r.find('.payable-leave-days').val() || 0);
            if (!isFinite(days) || days < 0) days = 0;
            breakdownSnapshot.push({
                leave_type: typeName,
                available_days: days,
                encashable_days: $r.data('encashable') === false ? 0 : days,
                amount_mvr: days * dailyRate,
                is_encashable: $r.data('encashable') !== false,
            });
        });
        // Hidden input on the F&F form; created lazily so older renders
        // of the page still post the field if HR edits anything.
        if ($('#leave_breakdown_json').length === 0) {
            $('#final-settlement-form').append(
                '<input type="hidden" name="leave_breakdown_json" id="leave_breakdown_json">'
            );
        }
        $('#leave_breakdown_json').val(JSON.stringify(breakdownSnapshot));

        // The compact one-liner under the Leave Balance input must reflect
        // the LIVE rows (server breakdown + HR edits + added rows), not the
        // frozen response payload.
        var $leaveBreak = $('#leave-breakdown');
        var parts = [];
        $('#payable-leaves tbody tr').each(function () {
            var $r = $(this);
            var typeCell = $r.find('.payable-leave-type');
            var type = typeCell.length ? typeCell.val() : $r.find('td').first().text().replace(/Encashable/, '').trim();
            var d = parseFloat($r.find('.payable-leave-days').val() || 0).toFixed(2);
            parts.push((type || 'Leave') + ': ' + d + 'd');
        });
        $leaveBreak.html(parts.length
            ? '<i class="fa-solid fa-list-ul me-1"></i>' + parts.join(' · ')
            : '');

        // Encashment formula text under the LEAVE ENCASHMENT input — same
        // numbers, refreshed.
        $('#leave-encashment-formula').html(
            '<i class="fa-solid fa-calculator me-1"></i>' +
            daysTotal.toFixed(2) + ' days × ' + formatMoney(mvrToDisplay(dailyRate)) + ' ' + FNF_DISPLAY_CURRENCY + '/day' +
            ' = ' + formatMoney(displayAmt) + ' ' + FNF_DISPLAY_CURRENCY
        );

        recomputeGrossEarning();
    }

    // ────────────────────────────────────────────────────────────────
    // recomputeGrossEarning()
    // Rebuilds the Settlement Breakdown table from base components stashed
    // on #settlement-details + the LIVE leave encashment amount. Triggered
    // by recalcPayableLeaves and by anything else that moves one of the
    // contributing values (Earned Salary manual override, allowance edits).
    // ────────────────────────────────────────────────────────────────
    function recomputeGrossEarning() {
        var $sd = $('#settlement-details');
        if (!$sd.length || $sd.data('base-earned') === undefined) return;
        var dailyRate     = parseFloat($sd.data('base-daily-rate') || 0);
        var workedDays    = $sd.data('base-earned-days') || 0;
        var earnedMvr     = parseFloat($sd.data('base-earned') || 0);
        var allowancesMvr = parseFloat($sd.data('base-allowances') || 0);
        // Local helper: display-currency input → MVR for the math layer.
        // Used for Service Charge (editable, currency = display) and the
        // three top-of-form deduction inputs below.
        function displayToMvr(displayVal) {
            var n = parseFloat(displayVal);
            if (!isFinite(n)) return 0;
            return FNF_DISPLAY_CURRENCY === 'USD' && FNF_DOLLAR_TO_MVR > 0
                ? n * FNF_DOLLAR_TO_MVR
                : n;
        }
        // Service Charge — read LIVE from the input so HR edits ripple
        // straight into Gross Earning, instead of using the stashed
        // data-raw which only updates on employee-fetch.
        var serviceMvr    = displayToMvr(stripMoney($('#service_charge').val()));
        // Leave encashment — read from the live table total, NOT the
        // server response (which is stale once HR has edited a row).
        var leaveMvr      = parseFloat($('#payable-leaves-amount-total').data('raw-mvr') || 0);

        // ─── Earnings ──────────────────────────────────────────────
        var earningRows = [
            { label: 'Earned Salary <small class="text-muted">(' + workedDays + ' paid day(s) × ' +
                formatMoney(mvrToDisplay(dailyRate)) + ' ' + FNF_DISPLAY_CURRENCY + '/day)</small>',
              value: earnedMvr },
            { label: 'Total Allowances',  value: allowancesMvr },
            { label: 'Service Charge',    value: serviceMvr },
            { label: 'Leave Encashment',  value: leaveMvr },
        ];

        // ─── Deductions ────────────────────────────────────────────
        // All three top-of-form deduction inputs (EWT / Loan / Notice
        // Period Charge) are in the DISPLAY currency. The dynamic
        // Deduction repeater rows below the table are also user-entered
        // and may be USD or MVR — read the unit alongside the amount.
        // Convert everything back to MVR via the displayToMvr() helper
        // declared at the top of this function so the arithmetic stays
        // in MVR.
        var ewtDisplay   = stripMoney($('#tax').val());
        var loanDisplay  = stripMoney($('#loan_payment').val());
        var noticeDisplay = stripMoney($('#notice_period_charge').val());

        var deductionRows = [
            { label: 'EWT',                   value: displayToMvr(ewtDisplay) },
            { label: 'Loan / Advance Payment', value: displayToMvr(loanDisplay) },
            { label: 'Notice Period Charge',  value: displayToMvr(noticeDisplay) },
        ];

        // Dynamic Deduction repeater rows (Select Deduction + Amount + Unit).
        // Each row contributes one labelled line. Unit determines whether
        // we convert to MVR before subtracting.
        $('#settlement-details').data('base-earned'); // no-op (keep $sd hot)
        $('.deduction-select, select[name="deductionFor[]"]').each(function () {
            var $row = $(this).closest('.row');
            var name = $.trim($(this).find('option:selected').text());
            if (!name || name === 'Select Deduction') return;
            var amountVal = parseFloat($row.find('.deduction-amount').val() || 0);
            if (!isFinite(amountVal) || amountVal <= 0) return;
            var unit = ($row.find('.amount-unit').val() || 'MVR').toUpperCase();
            var amtMvr = unit === 'USD' && FNF_DOLLAR_TO_MVR > 0
                ? amountVal * FNF_DOLLAR_TO_MVR
                : amountVal;
            deductionRows.push({ label: name, value: amtMvr });
        });

        // ─── Render ───────────────────────────────────────────────
        var $body = $('#settlement-details-body').empty();
        var grossMvr = 0;
        earningRows.forEach(function (r) {
            grossMvr += r.value;
            $body.append(
                '<tr><td>' + r.label + '</td>' +
                '<td class="text-end">' + formatMoney(mvrToDisplay(r.value)) + '</td></tr>'
            );
        });
        // Gross Earning subtotal (carries the same role the old footer
        // played; sits above the deduction rows so HR sees the
        // pre-deduction figure clearly). Use <th> for both cells so the
        // bold weight applies to the AMOUNT too — Bootstrap's
        // `fw-bold table-light` on <tr> didn't always inherit through
        // to the cell text on some themes (reported as "amount should
        // be bold" by HR).
        $body.append(
            '<tr class="table-light fw-bold">' +
                '<th class="fw-bold">Gross Earning</th>' +
                '<th class="text-end fw-bold">' + formatMoney(mvrToDisplay(grossMvr)) + '</th>' +
            '</tr>'
        );

        var deductionsMvr = 0;
        deductionRows.forEach(function (d) {
            deductionsMvr += d.value;
            // Negative-styling cue: red text + leading minus so the
            // direction reads clearly even at a glance.
            $body.append(
                '<tr class="text-danger"><td>' + d.label + '</td>' +
                '<td class="text-end">− ' + formatMoney(mvrToDisplay(d.value)) + '</td></tr>'
            );
        });
        $body.append(
            '<tr class="table-light fw-bold">' +
                '<th class="fw-bold">Total Deductions</th>' +
                '<th class="text-end text-danger fw-bold">− ' + formatMoney(mvrToDisplay(deductionsMvr)) + '</th>' +
            '</tr>'
        );

        var netMvr = grossMvr - deductionsMvr;
        $('#settlement-details-total').text(
            formatMoney(mvrToDisplay(netMvr), FNF_DISPLAY_CURRENCY)
        );
    }

    // Delegated event wiring — bound once at DOM ready so renders inside
    // getEmpDetails() don't stack handlers. Three events cover the table:
    //   • Days input change → recalc totals + Gross Earning
    //   • Trash icon → remove a manually-added row + recalc
    //   • Add row button → append a fresh editable row
    $(document).on('input change', '#payable-leaves tbody .payable-leave-days', function () {
        recalcPayableLeaves();
    });
    $(document).on('click', '#payable-leaves tbody .payable-leave-remove', function () {
        $(this).closest('tr').remove();
        recalcPayableLeaves();
    });
    // Any deduction input change → rebuild the Settlement Breakdown so
    // the Total Deductions + Net Settlement rows reflect the new value.
    // Covers all three top-of-form inputs + every dynamic Deduction
    // repeater row (select + amount + unit), plus row add/remove via
    // the .add-deduction / .remove-deduction buttons (handled separately
    // in their existing handlers; this listener catches the resulting
    // .deduction-amount edits).
    $(document).on('input change',
        // Earnings card inputs that aren't covered by the leave-table
        // listener or the Earned-Salary toggle: Service Charge is the
        // only editable money field here, but include it so HR edits
        // ripple straight into Gross Earning + Net Settlement.
        '#service_charge, ' +
        // Deductions card top inputs (EWT / Loan / Notice).
        '#tax, #loan_payment, #notice_period_charge, ' +
        // Dynamic Deduction repeater rows (amount / select / unit).
        '.deduction-amount, .deduction-select, .amount-unit, ' +
        'select[name="deductionFor[]"], input[name="deduction_amount[]"]',
        function () {
            recomputeGrossEarning();
        }
    );
    // Removing a repeater row → recompute (the row is gone before our
    // change listener fires, so a separate click handler is cleaner).
    $(document).on('click', '.remove-deduction', function () {
        // The existing remove-deduction handler removes the .row; queue
        // recompute after the DOM mutation so the deleted row is out
        // of consideration when we sum.
        setTimeout(recomputeGrossEarning, 0);
    });
    // Earned Salary manual override. Pencil → check, input toggles
    // readonly, and any HR-entered value rolls into Gross Earning via
    // recomputeGrossEarning() (which re-reads the base-earned data-attr).
    // On lock-back, the auto-derived value is restored.
    $(document).on('click', '#earned_salary_edit_toggle', function () {
        var $input = $('#earned_salary');
        var $icon  = $(this).find('i');
        var nowEditing = !($input.prop('readonly') === false);
        if (nowEditing) {
            // Stash the auto-derived display value before HR overwrites
            // so the lock-back restore is reliable.
            $input.data('auto-display', $input.val());
            $input.prop('readonly', false).focus();
            $icon.removeClass('fa-pencil').addClass('fa-circle-check');
            $(this).attr('title', 'Lock Earned Salary back to attendance-derived value');
        } else {
            $input.prop('readonly', true);
            var auto = $input.data('auto-display');
            if (typeof auto !== 'undefined') $input.val(auto);
            $icon.removeClass('fa-circle-check').addClass('fa-pencil');
            $(this).attr('title', 'Edit Earned Salary manually');
            // Reset base-earned back to the attendance-derived MVR so
            // Gross Earning matches the auto value again.
            var $sd = $('#settlement-details');
            var savedMvr = $sd.data('auto-earned-mvr');
            if (typeof savedMvr !== 'undefined') {
                $sd.data('base-earned', savedMvr);
                recomputeGrossEarning();
            }
        }
    });
    $(document).on('input change blur', '#earned_salary', function () {
        // Only react when the field is in manual mode — the auto-flow
        // updates the input via setFieldValueAndResetValidation and would
        // otherwise loop into recomputeGrossEarning unnecessarily.
        if ($(this).prop('readonly')) return;
        var display = stripMoney($(this).val());
        // Convert back to MVR for the stash + Gross Earning calc (the
        // base-earned data-attr is always MVR; recomputeGrossEarning
        // mvrToDisplay()-s it for rendering).
        var mvr = FNF_DISPLAY_CURRENCY === 'USD' && FNF_DOLLAR_TO_MVR > 0
            ? display * FNF_DOLLAR_TO_MVR
            : display;
        $('#settlement-details').data('base-earned', mvr);
        recomputeGrossEarning();
    });

    // Reset → discard HR edits + manually-added rows and rebuild the table
    // from the server-supplied breakdown stashed during the last employee
    // fetch. No network round-trip; just replays the original snapshot.
    $(document).on('click', '#reset-payable-leaves', function () {
        var original = $('#payable-leaves').data('original-breakdown');
        var totalDays = parseFloat($('#payable-leaves').data('original-total-days') || 0);
        if (!original) return; // No employee loaded yet
        $('#payable-leaves tbody').empty();
        if (original.length) {
            original.forEach(function (row) { renderPayableLeaveRow(row); });
        } else if (totalDays > 0) {
            renderPayableLeaveRow({
                leave_type: 'Total Leave Balance',
                available_days: totalDays,
                is_encashable: true,
            });
        }
        recalcPayableLeaves();
    });

    $(document).on('click', '#add-payable-leave-row', function () {
        var dailyRate = parseFloat($('#payable-leaves').data('daily-rate') || 0);
        // Build the leave-type dropdown options. Source: the employee's
        // eligible leave types stashed at fetch (mirrors the dropdown HR
        // sees on the employee details page). Falls back to a free-text
        // input if no employee has been picked yet.
        var types = $('#payable-leaves').data('eligible-leave-types') || [];
        if (!Array.isArray(types)) types = [];
        var typeCell;
        if (types.length) {
            var opts = '<option value="" disabled selected>Select leave type…</option>';
            types.forEach(function (t) {
                var safe = $('<i>').text(t).html();
                opts += '<option value="' + safe + '">' + safe + '</option>';
            });
            // Keep an "Other" path so HR can still record one-off items
            // that aren't in the leave grid (eg. a pre-existing comp day).
            opts += '<option value="__other__">Other…</option>';
            typeCell = '<select class="form-select form-select-sm payable-leave-type">' + opts + '</select>';
        } else {
            typeCell = '<input type="text" class="form-control form-control-sm payable-leave-type"' +
                ' value="" placeholder="Leave / credit name">';
        }
        var $tr = $('<tr>' +
            '<td>' + typeCell + '</td>' +
            '<td class="text-end"><input type="number" min="0" step="0.01"' +
                ' class="form-control form-control-sm text-end payable-leave-days"' +
                ' value="0.00"></td>' +
            '<td class="text-end">' + formatMoney(mvrToDisplay(dailyRate)) + '</td>' +
            '<td class="text-end payable-leave-amount">' + formatMoney(0) + '</td>' +
            '<td class="text-end">' +
                '<button type="button" class="btn btn-sm btn-link text-danger p-0 payable-leave-remove"' +
                ' title="Remove row"><i class="fa-solid fa-trash"></i></button>' +
            '</td>' +
        '</tr>');
        $tr.data('encashable', true);
        $('#payable-leaves tbody').append($tr);
        $tr.find('.payable-leave-type').trigger('focus');
        recalcPayableLeaves();
    });

    // Handle the "Other…" dropdown choice — swap the <select> for a
    // free-text input so HR can type a custom name. recalcPayableLeaves
    // re-reads the type for the one-line breakdown summary.
    $(document).on('change', '#payable-leaves tbody select.payable-leave-type', function () {
        if ($(this).val() !== '__other__') {
            recalcPayableLeaves();
            return;
        }
        var $input = $('<input type="text" class="form-control form-control-sm payable-leave-type"' +
            ' value="" placeholder="Leave / credit name">');
        $(this).replaceWith($input);
        $input.trigger('focus');
        recalcPayableLeaves();
    });

    function mvrToDisplay(mvrValue) {
        var n = parseFloat(mvrValue);
        if (!isFinite(n)) return 0;
        if (FNF_DISPLAY_CURRENCY === 'USD' && FNF_DOLLAR_TO_MVR > 0) {
            return n / FNF_DOLLAR_TO_MVR;
        }
        return n;
    }

    // ────────────────────────────────────────────────────────────────
    // formatMoney(amount, currency)
    // Project-wide money display: thousands-separated, 2-decimal, with
    // optional currency suffix. Used by both the allowance table cells
    // and the readonly money-field inputs (basic salary, EWT, pension,
    // leave encashment, etc.). Mirrors what the backend
    // `Common::formatCurrency` produces on PDF / Blade renders so the
    // F&F page reads the same as payslips and the experience letter.
    //   formatMoney(144331.2)         → "144,331.20"
    //   formatMoney(300, 'USD')       → "300.00 USD"
    //   formatMoney(0, 'MVR')         → "0.00 MVR"
    //   formatMoney(null)             → "0.00"
    // ────────────────────────────────────────────────────────────────
    function formatMoney(amount, currency) {
        var n = parseFloat(amount);
        if (!isFinite(n)) n = 0;
        var formatted = n.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return currency ? formatted + ' ' + currency : formatted;
    }

    // Strip thousands-separators back to a raw float string. Used on
    // form submit so the server still receives "144331.20", not
    // "144,331.20" (Laravel's `numeric` validator would reject commas).
    function stripMoney(raw) {
        return String(raw || '').replace(/,/g, '').trim();
    }

    // Format a backend date string into the project-standard DISPLAY
    // format used everywhere in the people / payroll / exit-clearance
    // modules: `d M Y` (e.g. `20 May 2026`). This is what
    // `Carbon::format('d M Y')` produces server-side — Tax, payroll
    // summary, experience certificate, view-details cards, etc. all use
    // it, so the F&F page should match. `dd/mm/yyyy` is the datepicker
    // input format, not the display format, so it's wrong for these
    // readonly fields.
    //
    // Accepts ISO `YYYY-MM-DD`, the `dd/mm/yyyy` datepicker form, and
    // already-formatted `d M Y` strings (pass-through). Returns 'N/A'
    // for nulls so the field doesn't render the word "null".
    function formatBackendDate(raw) {
        if (!raw) return 'N/A';
        var s = String(raw);
        var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        // ISO `YYYY-MM-DD` (optionally followed by time)
        var iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (iso) {
            var m = parseInt(iso[2], 10);
            return iso[3] + ' ' + MONTHS[m - 1] + ' ' + iso[1];
        }
        // Datepicker `dd/mm/yyyy`
        var dmy = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (dmy) {
            return dmy[1] + ' ' + MONTHS[parseInt(dmy[2], 10) - 1] + ' ' + dmy[3];
        }
        return s;
    }

    function getEmpDetails(empId){
        if (!empId) return; // If no employee is selected, do nothing

        $.ajax({
            url: "{{route('employee.get.details')}}", // Route to fetch employee details
            type: "GET",
            data: { employee_id: empId },
            success: function(response) {
                if (response.success) {
                    console.log(response.data);
                    // Update Profile Picture
                    $("#img-circle img").attr("src", response.data.profile_picture || "assets/images/user-2.svg");

                    // Update Name and Employee ID
                    $(".empDetails-user h4").html(response.data.full_name + 
                        ` <span class="badge badge-themeNew">#${response.data.emp_id}</span>`);

                    // Update Position & Department
                    $(".empDetails-user p").text(response.data.position + " - " + response.data.department);

                    // Update Resignation & Last Working Day — normalise
                    // backend ISO `YYYY-MM-DD` to `dd/mm/yyyy` so the F&F
                    // page matches the rest of the system (datepickers,
                    // payroll views) instead of leaking raw DB format.
                    $("#resignation_date").val(formatBackendDate(response.data.resignation_date));
                    $("#last_day").val(formatBackendDate(response.data.last_working_day));
                    $('#last_working_date').val(formatBackendDate(response.data.last_working_day));
                    $('#payroll_start_date').val(formatBackendDate(response.data.payroll_start));
                    $('#payment_mode').val(response.data.payment_mode || "Cash");
                    
                    // Set values and reset validation field by field
                    // Round to 2 decimals at the UI boundary so a stray
                    // float like 20.740000000000002 doesn't reach
                    // Parsley's number validator (it rejects the long
                    // form as "This value seems to be invalid").
                    setFieldValueAndResetValidation(
                        '#leave_balance',
                        Number(parseFloat(response.data.leave_balance || 0).toFixed(2))
                    );
                    setFieldValueAndResetValidation('#basic_salary', response.data.basic_salary_mvr || 0);
                    setFieldValueAndResetValidation('#earned_salary', response.data.earned_salary || 0);
                    setFieldValueAndResetValidation('#pension', response.data.pension || 0);
                    setFieldValueAndResetValidation('#tax', response.data.ewt || 0);

                    // ─── Earned Salary breakdown (attendance-based) ───
                    // Same definition as payroll's earned_salary column:
                    //   earned = (basic ÷ days_in_period) × (present + day_off)
                    // Shows the period window, paid days vs expected,
                    // daily rate so HR can audit how the Earned Salary
                    // figure was derived. When attendance is incomplete
                    // (paid < expected) the muted text flips to a red
                    // warning so HR fixes
                    // attendance before issuing the payout.
                    var $earnBreak = $('#earning-breakdown');
                    var workedDays   = parseInt(response.data.worked_days || 0, 10);
                    var expectedDays = parseInt(response.data.expected_working_days || 0, 10);
                    var dailyRate    = parseFloat(response.data.daily_salary || 0);
                    var proratedBase = parseFloat(response.data.proratedBasic || 0);
                    var periodLabel  = response.data.attendance_period_start && response.data.attendance_period_end
                        ? response.data.attendance_period_start + ' → ' + response.data.attendance_period_end
                        : '';
                    // Three distinct narratives so HR never sees the
                    // useless "0 / 0 days" line that read as a bug:
                    //   • expected = 0  → period window collapsed (last
                    //     working day before payroll start, or joining
                    //     after payroll end). Show the period only.
                    //   • worked < expected (gap) → red warning.
                    //   • worked = expected → muted breakdown.
                    if (expectedDays <= 0) {
                        $earnBreak.removeClass('text-danger').addClass('text-muted').html(
                            '<i class="fa-solid fa-circle-info me-1"></i>' +
                            'No working days fall inside this payroll period' +
                            (periodLabel ? ' (' + periodLabel + ')' : '') +
                            ' — earning is built from leave encashment + allowances only.'
                        );
                    } else {
                        var earnLine = (periodLabel ? 'Period ' + periodLabel + ' · ' : '') +
                            'worked ' + workedDays + ' / ' + expectedDays + ' days' +
                            ' · daily rate ' + formatMoney(mvrToDisplay(dailyRate)) + ' ' + FNF_DISPLAY_CURRENCY +
                            ' · prorated basic ' + formatMoney(mvrToDisplay(proratedBase)) + ' ' + FNF_DISPLAY_CURRENCY;
                        if (response.data.attendance_gap) {
                            $earnBreak.removeClass('text-muted').addClass('text-danger')
                                .html('<i class="fa-solid fa-triangle-exclamation me-1"></i>' +
                                      'Attendance gap — ' + earnLine +
                                      '. Verify check-in records before submitting.');
                        } else {
                            $earnBreak.removeClass('text-danger').addClass('text-muted').text(earnLine);
                        }
                    }

                    // ─── Payable Leaves Breakdown table ───
                    // Renders the same `leave_breakdown` data into a
                    // full-width table matching the reference Final Pay
                    // Settlement layout (Annual Leave Days / Sick Leave
                    // Days / Day Off's Days / Public Holiday's / Other
                    // Leaves). Days × dailyRate = Amount per row;
                    // totals at the bottom must equal the LEAVE
                    // ENCASHMENT field above. The small `#leave-breakdown`
                    // line under the Leave Balance input still gets the
                    // compact one-liner for at-a-glance scanning.
                    var breakdown = response.data.leave_breakdown || [];
                    // PHP can serialize an associative array with non-
                    // sequential keys as a JS object instead of an
                    // array (then `.length` is undefined). Normalise.
                    if (!Array.isArray(breakdown) && typeof breakdown === 'object') {
                        breakdown = Object.values(breakdown);
                    }
                    var $leaveTbody = $('#payable-leaves tbody').empty();
                    var totalLeaveDaysFromService = parseFloat(response.data.leave_balance || 0);

                    // Stash dailyRate where the row renderer + add-row handler
                    // can both read it without re-parsing the DOM each time.
                    // Also stash a deep copy of the server breakdown + the
                    // server-derived fallback so the Reset button can rebuild
                    // the table exactly as it first arrived, discarding HR
                    // edits and any manually-added rows.
                    // Eligible leave types — drives the dropdown on manually
                    // added rows. PHP can deliver an associative array that
                    // jQuery treats as an object, so normalise to a plain
                    // string[] for the renderer.
                    var eligibleTypes = response.data.eligible_leave_types || [];
                    if (!Array.isArray(eligibleTypes) && typeof eligibleTypes === 'object') {
                        eligibleTypes = Object.values(eligibleTypes);
                    }
                    $('#payable-leaves')
                        .data('daily-rate', dailyRate)
                        .data('original-breakdown', JSON.parse(JSON.stringify(breakdown)))
                        .data('original-total-days', totalLeaveDaysFromService)
                        .data('eligible-leave-types', eligibleTypes);
                    $('#reset-payable-leaves').prop('disabled', false);

                    if (breakdown.length) {
                        breakdown.forEach(function (row) {
                            renderPayableLeaveRow(row);
                        });
                    } else if (totalLeaveDaysFromService > 0) {
                        // Service produced a leave-balance total but the
                        // per-category breakdown is missing (data
                        // anomaly: no benefit-grid match for this
                        // employee's rank/grade, or the breakdown was
                        // dropped server-side). Surface a single editable
                        // fallback row so the table can't disagree with
                        // the Leave Balance / Leave Encashment inputs above.
                        renderPayableLeaveRow({
                            leave_type: 'Total Leave Balance',
                            available_days: totalLeaveDaysFromService,
                            is_encashable: true,
                        });
                    }
                    // Always recompute totals + push to the headline inputs
                    // (handles the empty-but-add-row case too).
                    recalcPayableLeaves();

                    // ─── Settlement Details (Earning breakdown) table ───
                    // Rebuild from the same numbers populating the input
                    // fields above so the table can NEVER disagree with
                    // the Earning Salary input. Same idea as the review
                    // page's Settlement Details card.
                    var $sdBody = $('#settlement-details-body').empty();
                    // Mirrors the payroll review's component shape:
                    //   earned_salary  → (basic ÷ days) × paid_days
                    //   + allowances   → SUM of EmployeeAllowance rows
                    //   + service_charge
                    //   + leave_encashment (F&F-only — payroll doesn't carry it)
                    //   = Gross Earning
                    // Stash the non-leave components on the Settlement Details
                    // wrapper so recomputeGrossEarning() can rebuild the table
                    // every time HR edits a row in the Leave Breakdown.
                    // Earned Salary / Allowances / Service Charge come from
                    // the employee fetch and don't move; Leave Encashment is
                    // the only live input.
                    var $sd = $('#settlement-details');
                    $sd.data('base-earned',           proratedBase);
                    // `auto-earned-mvr` is the lock-back restore value for
                    // the Earned Salary manual-override toggle. base-earned
                    // can be overwritten by the manual edit handler; this
                    // copy stays put so the icon's "back to attendance"
                    // flip can refill the input + Gross Earning row.
                    $sd.data('auto-earned-mvr',       proratedBase);
                    $sd.data('base-earned-days',      workedDays || 0);
                    $sd.data('base-daily-rate',       dailyRate);
                    $sd.data('base-allowances',       parseFloat(response.data.total_allowances_mvr || 0));
                    $sd.data('base-service-charge',   parseFloat($('#service_charge').attr('data-raw') || 0));
                    // Clear any leftover manual-edit state from a previous
                    // employee selection so the pencil icon shows again.
                    var $earnedInput = $('#earned_salary').prop('readonly', true);
                    $earnedInput.removeData('auto-display');
                    $('#earned_salary_edit_toggle i').removeClass('fa-circle-check').addClass('fa-pencil');
                    recomputeGrossEarning();

                    // The compact #leave-breakdown one-liner and the
                    // #leave-encashment-formula text are now both maintained
                    // by recalcPayableLeaves(), called above when the table
                    // renders. Keeping them here would clobber HR edits with
                    // the frozen server payload, since the dup-update runs
                    // after the recalc.

                    // EWT / TIN compliance warning. Shows when taxable
                    // income is at/above the MIRA EWT threshold but the
                    // employee isn't EWT-enrolled or doesn't have a TIN.
                    // Wording matches the Employee Details page card so
                    // HR sees a consistent message in both places.
                    var $ewtWarn = $('#ewt-warning');
                    var $ewtText = $('#ewt-warning-text');
                    if (response.data.ewt_registration_needed) {
                        var bits = [];
                        if (!response.data.ewt_enrolled) bits.push('not enrolled for EWT');
                        if (!response.data.tin_present)  bits.push('no TIN on file');
                        $ewtText.text(
                            'Taxable income ' + FNF_DISPLAY_CURRENCY + ' ' +
                            formatMoney(mvrToDisplay(response.data.taxable_income || 0)) +
                            ' clears the EWT threshold — employee is ' + bits.join(' & ') +
                            '. Verify before issuing the settlement.'
                        );
                        $ewtWarn.css('display', 'block');
                    } else {
                        $ewtWarn.css('display', 'none');
                    }
                    setFieldValueAndResetValidation('#leave_encashment', response.data.leave_encashment || 0);
                    setFieldValueAndResetValidation('#loan_payment', response.data.loan_recovery || 0);
                    setFieldValueAndResetValidation('#notice_period_charge', response.data.notice_period_charge_mvr || 0);

                    // ─── Notice Period Charge breakdown ───
                    // Spells out required vs served vs shortfall days
                    // × daily rate so HR can see how the charge was
                    // built. The rule title (from the Notice Period
                    // module config) is shown in parentheses when set.
                    var $noticeBreak = $('#notice-period-breakdown');
                    var noticeReq      = parseInt(response.data.notice_required_days || 0, 10);
                    var noticeServed   = parseInt(response.data.notice_served_days || 0, 10);
                    var noticeShort    = parseInt(response.data.notice_shortfall_days || 0, 10);
                    var noticeCharge   = parseFloat(response.data.notice_period_charge_mvr || 0);
                    var noticeRule     = response.data.notice_rule_title;
                    if (noticeReq <= 0) {
                        // Notice Period module not configured for this
                        // resort. Tell HR what to do rather than show 0.
                        $noticeBreak.removeClass('text-muted').addClass('text-warning').html(
                            '<i class="fa-solid fa-circle-info me-1"></i>' +
                            'Notice Period module not configured — set a rule in ' +
                            'People → Configuration → Notice Period.'
                        );
                    } else if (noticeShort > 0) {
                        $noticeBreak.removeClass('text-warning').addClass('text-muted').html(
                            '<i class="fa-solid fa-calculator me-1"></i>' +
                            (noticeRule ? '(' + noticeRule + ') ' : '') +
                            'required ' + noticeReq + 'd · served ' + noticeServed + 'd · ' +
                            'shortfall ' + noticeShort + 'd × ' + formatMoney(mvrToDisplay(dailyRate)) + ' ' + FNF_DISPLAY_CURRENCY + '/day = ' +
                            formatMoney(mvrToDisplay(noticeCharge)) + ' ' + FNF_DISPLAY_CURRENCY
                        );
                    } else {
                        $noticeBreak.removeClass('text-warning').addClass('text-muted').html(
                            '<i class="fa-solid fa-check me-1"></i>' +
                            (noticeRule ? '(' + noticeRule + ') ' : '') +
                            'Full notice served (' + noticeServed + ' of ' + noticeReq + ' days) — no charge.'
                        );
                    }

                    // ─── Loan / Salary-advance recovery breakdown ───
                    // Pulls from `payroll_recovery_schedule` rows joined
                    // to `payroll_advance`. Hidden when the employee has
                    // no outstanding installments so the line doesn't
                    // shout "0.00" at HR when there's genuinely nothing.
                    var $loanBreak  = $('#loan-breakdown');
                    var loanMvr     = parseFloat(response.data.loan_outstanding_mvr || 0);
                    var advanceMvr  = parseFloat(response.data.advance_outstanding_mvr || 0);
                    var loanN       = parseInt(response.data.loan_installment_count || 0, 10);
                    var advanceN    = parseInt(response.data.advance_installment_count || 0, 10);
                    if (loanMvr <= 0 && advanceMvr <= 0) {
                        $loanBreak.text('').css('display', 'none');
                    } else {
                        var loanParts = [];
                        if (loanMvr > 0) {
                            loanParts.push('Loan: ' + formatMoney(mvrToDisplay(loanMvr), FNF_DISPLAY_CURRENCY) +
                                ' (' + loanN + ' installment' + (loanN === 1 ? '' : 's') + ')');
                        }
                        if (advanceMvr > 0) {
                            loanParts.push('Salary Advance: ' + formatMoney(mvrToDisplay(advanceMvr), FNF_DISPLAY_CURRENCY) +
                                ' (' + advanceN + ' installment' + (advanceN === 1 ? '' : 's') + ')');
                        }
                        $loanBreak.html('<i class="fa-solid fa-money-bill-transfer me-1"></i>' +
                            loanParts.join(' · '))
                            .css('display', 'block');
                    }
                    setFieldValueAndResetValidation('#service_charge', 0);
                    
                  

                    // Populate allowance breakdown
                    let $allowanceTableBody = $("#allowance-details tbody");
                    $allowanceTableBody.empty();

                   // Store allowance data globally or attach to a hidden input
                    let allowanceData = response.data.allowances || [];
                    let totalAllowances = response.data.allowances_mvr || response.data.total_allowances_mvr || 0;

                    // Inject into table. Money cells go through
                    // formatMoney() so they get thousands separators
                    // (1,000.00 USD) and the unit is appended — was
                    // raw `100` before, hiding whether it was USD/MVR.
                    let tbody = $("#allowance-details tbody");
                    tbody.empty();
                    // Two columns now (Name + Amount in display currency).
                    // The earlier "Converted Amount" column duplicated info
                    // already in the Amount cell and added noise — HR only
                    // needs to see the per-allowance value in the active
                    // display currency.
                    allowanceData.forEach(a => {
                        tbody.append(`<tr>
                            <td>${a.name}</td>
                            <td class="text-end">${formatMoney(mvrToDisplay(a.converted_amount), FNF_DISPLAY_CURRENCY)}</td>
                        </tr>`);
                    });
                    $("#total-allowances").text(formatMoney(mvrToDisplay(totalAllowances), FNF_DISPLAY_CURRENCY));

                    // Store allowances as hidden input
                    if ($("#allowances_json").length) {
                        $("#allowances_json").val(JSON.stringify(allowanceData));
                    } else {
                        $("#final-settlement-form").append(`<input type="hidden" name="allowances" id="allowances_json" value='${JSON.stringify(allowanceData)}'>`);
                    }
                    
                    // Re-initialize form validation
                    $('#final-settlement-form').parsley().reset();
                } else {
                    alert("Employee details not found!");
                }
            },
            error: function() {
                alert("Error fetching employee details.");
            }
        });
    }

    // Helper function to set value and reset validation
    function setFieldValueAndResetValidation(selector, value) {
        const field = $(selector);
        // Money fields get thousands-separated display ("144,331.20"); the
        // raw numeric is preserved on `data-raw` so the submit handler can
        // restore it before POST (Laravel rejects commas). The raw value
        // is ALWAYS the canonical MVR amount — submit posts MVR. The
        // VISIBLE value is converted to the display currency (USD ÷
        // 15.42 when in USD mode) so the page reflects the top-right
        // currency switcher. Non-money fields (leave_balance) get the
        // value as-is.
        if (field.hasClass('money-field')) {
            var rawMvr = value == null ? 0 : value;
            field.attr('data-raw', rawMvr);
            field.val(formatMoney(mvrToDisplay(rawMvr)));
        } else {
            field.val(value);
        }

        // Reset field validation
        if (field.parsley()) {
            field.parsley().reset();

            // If the field has a value and is required, validate it
            if (value && field.attr('data-parsley-required')) {
                field.parsley().validate();
            }
        }
    }

    $(".add-deduction").click(function(e) {
        e.preventDefault();
        $(".deductions-container").append(`
            <div class="fullFinal-block">
                <div class="row g-md-4 g-3 deduction-row">
                    <div class="col-xl-3 col-sm">
                        <select class="form-select select2t-none deduction-select" name="deductionFor[]">
                            <option value="">Select Deduction</option>
                            @foreach($deductions as $deduction)
                                <option value="{{$deduction->id}}" data-unit="{{$deduction->currency}}">{{$deduction->deduction_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-sm">
                        <input type="number" class="form-control deduction-amount" placeholder="Enter Amount" name="deduction_amount[]"
                            data-parsley-type="number" data-parsley-min="0" data-parsley-required-if="#deductionFor" data-parsley-trigger="change">
                    </div>
                    <div class="col-xl-3 col-sm">
                         <input type="text" class="form-control amount-unit" name="amount_unit[]" placeholder="Amount Unit" redaonly>
                     </div>
                    <div class="col-xl-3 col-auto align-self-end">
                        <a href="#" class="btn btn-danger btn-sm remove-deduction">Remove</a>
                    </div>
                </div>
            </div>
        `);
        $('.select2t-none').select2();
    });

    // Remove Deduction
    $(document).on("click", ".remove-deduction", function(e) {
        e.preventDefault();
        $(this).closest(".fullFinal-block").remove();
    });

  
    document.addEventListener('DOMContentLoaded', function() {
        function initSelect2AndValidation() {
            if ($.fn.select2 && $.fn.parsley) {
                // Initialize Select2
                $(".select2t-none").select2();

                // Add Parsley validation specifically for Select2
                $(".select2t-none").on('change', function() {
                    $(this).parsley().validate();
                });

                // Ensure Select2 trigger changes in Parsley
                $(".select2t-none").on('select2:select', function() {
                    $(this).trigger('change');
                });
            }
        }

        // Initialize Parsley Validation
        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#final-settlement-form').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                window.Parsley.addValidator('requiredIf', {
                    requirementType: 'string',
                    validateString: function (value, selector) {
                        var relatedField = $(selector); // Get the related field
                        console.log(relatedField);
                        if (!relatedField.length) {
                            return true; // If the related field is not found, skip validation
                        }
                        var relatedValue = relatedField.val(); // Get the value of the related field
                        return !(relatedValue === '1' && value.trim() === ''); // Validation condition
                    },
                    messages: {
                        en: 'This field is required when the condition is met.'
                    }
                });

                window.Parsley.on('field:validated', function (fieldInstance) {
                    var $element = fieldInstance.$element;
                    if ($element.hasClass('select2t-none')) {
                        // Update the Select2 container's appearance
                        var $select2Container = $element.next('.select2-container').find('.select2-selection');
                        if (fieldInstance.isValid()) {
                            $select2Container.removeClass('is-invalid');
                        } else {
                            $select2Container.addClass('is-invalid');
                        }
                    }
                });

            }
        }

        // Alpha-only Input Handling
        function initAlphaOnlyInputs() {
            $('.alpha-only').on('keyup blur', function() {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
            });
        }

        // Form Submission Handling
        function initFormSubmission() {
            $('#final-settlement-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                // Strip thousands-separators from every money-field BEFORE
                // FormData snapshots the form, otherwise the server sees
                // e.g. "144,331.20" and Laravel's `numeric` validator
                // rejects the comma. The visible value is restored after
                // the snapshot so the UI keeps its formatting.
                let _moneyBackup = {};
                $('.money-field').each(function() {
                    var $f = $(this);
                    var raw = $f.attr('data-raw');
                    if (raw == null || raw === '') {
                        raw = stripMoney($f.val());
                    }
                    _moneyBackup[$f.attr('id')] = $f.val();
                    $f.val(raw);
                });

                var _restoreMoney = function() {
                    $.each(_moneyBackup, function(id, formattedVal) {
                        $('#' + id).val(formattedVal);
                    });
                };

                if (form.parsley().validate()) {
                    let formData = new FormData(this);

                    // Snapshot done — restore formatted display.
                    _restoreMoney();

                    // Get structured deductions
                    let deductions = [];
                    $(".deduction-row").each(function () {
                        let id = $(this).find('.deduction-select').val();
                        let amount = $(this).find('.deduction-amount').val();
                        let unit = $(this).find('.amount-unit').val();
                        if (id && amount) {
                            deductions.push({ id: id, amount: amount, unit: unit });
                        }
                    });

                    // Add deductions as JSON string
                    formData.append('deductions', JSON.stringify(deductions));

                    // Optional: You can do the same for earnings if needed

                    // Disable button
                    $('#submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

                    // Ajax request
                    $.ajax({
                        url: '{{ route("final.settlement.store") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            setTimeout(function () {
                                window.location.href = "{{ route('final.settlement.review', ':id') }}".replace(':id', response.final_settlement_id);
                            }, 2000);
                        },
                        error: function(xhr) {
                            let errorMessage = 'Submission failed.';
                            if (xhr.responseJSON?.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            toastr.error(errorMessage, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $('#submit').prop('disabled', false).html('Submit Application');
                        }
                    });
                } else {
                    // Validation failed — restore money-field formatting so
                    // the user doesn't see ugly raw `144331.2` values
                    // while fixing other errors.
                    _restoreMoney();
                    return false;
                }
            });
        }


        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
            initAlphaOnlyInputs();
            initFormSubmission();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
    });
</script>
@endsection