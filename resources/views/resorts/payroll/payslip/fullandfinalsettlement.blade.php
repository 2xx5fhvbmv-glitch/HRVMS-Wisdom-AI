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
                                        <label for="basic_salary" class="form-label">Basic Salary (MVR)<span class="red-mark">*</span></label>
                                        <input type="text" id="basic_salary" class="form-control money-field" name="basic_salary"
                                            placeholder="Basic salary" data-parsley-required="true" readonly>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="earned_salary" class="form-label">Earning Salary (MVR)<span class="red-mark">*</span></label>
                                        <input type="text" id="earned_salary" class="form-control money-field" name="earned_salary"
                                            placeholder="Earning salary" data-parsley-required="true" readonly>
                                        {{-- Attendance-based earning breakdown — populated by
                                             getEmpDetails() with worked/expected days, daily
                                             rate, prorated basic. Red banner when attendance
                                             gap is detected. --}}
                                        <small id="earning-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="service_charge" class="form-label">Service Charge (MVR)<span class="red-mark">*</span></label>
                                        <input type="text" id="service_charge" class="form-control money-field"
                                            name="service_charge" placeholder="Service Charge" data-parsley-required="true">
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="leave_balance" class="form-label">Leave Balance (days)<span class="red-mark">*</span></label>
                                        <input type="number" name="leave_balance" id="leave_balance" class="form-control" min="0" max="500" placeholder="Leave Balance" readonly data-parsley-required="true" data-parsley-type="number"
                                            data-parsley-min="0" data-parsley-trigger="change">
                                        <small id="leave-breakdown"
                                               class="form-text d-block text-muted mt-1"
                                               style="font-size:11px;"></small>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="leave_encashment" class="form-label">LEAVE ENCASHMENT (MVR)<span class="red-mark">*</span></label>
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
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                <i class="fa-solid fa-arrow-up me-2 text-danger"></i>Deductions
                            </div>
                            <div class="card-body fullFinal-block">
                                <div class="row g-md-4 g-3">
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="tax" class="form-label">EWT (MVR)<span class="red-mark">*</span></label>
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
                                        <label for="pension" class="form-label">PENSION / MRPS (MVR)<span class="red-mark">*</span></label>
                                        <input type="text" id="pension" class="form-control money-field" name="pension"
                                            placeholder="Pension" data-parsley-required="true" readonly>
                                    </div>
                                    <div class="col-xl-4 col-sm-6">
                                        <label for="loan_payment" class="form-label">LOAN OR ADVANCE PAYMENT? (MVR)<span class="red-mark">*</span></label>
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
                                        <label for="notice_period_charge" class="form-label">Notice Period Charge (MVR)<span class="red-mark">*</span></label>
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

                        {{-- ───── Payable Leaves Breakdown ─────
                             Per-category leave breakdown modelled on the standard Final
                             Pay Settlement layout (Annual / Sick / Day Off / Public
                             Holiday / Other). Days column = unused balance from
                             getLeaveBalance(); Amount column = days × daily salary in
                             MVR. Total row sums to the same value the LEAVE
                             ENCASHMENT field shows above so HR / auditor can verify
                             where the encashment figure came from. Rows are
                             populated by JS in getEmpDetails() from
                             response.data.leave_breakdown. --}}
                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                Payable Leaves Breakdown
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="payable-leaves">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th class="text-end">Days</th>
                                            <th class="text-end">Amount (MVR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Leave Days Salary Total</th>
                                            <th class="text-end" id="payable-leaves-days-total">0.00</th>
                                            <th class="text-end" id="payable-leaves-amount-total">0.00 MVR</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="fullFinal-main mb-md-4 mb-3">
                            <div class="fullFinal-head">
                                Allowance Breakdown
                            </div>
                            <div class="card-body fullFinal-block">
                                <table class="table" id="allowance-details">
                                    <thead>
                                        <tr>
                                            <th>Allowance Name</th>
                                            <th>Amount</th>
                                            <th>Converted Amount (MVR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-end">Total</th>
                                            <th id="total-allowances">0.00 MVR</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

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

                    // ─── Earning Salary breakdown (attendance-based) ───
                    // Shows the period window, days worked vs expected,
                    // daily rate and prorated basic so HR can audit how
                    // the Earning Salary figure was derived. When
                    // attendance is incomplete (worked < expected) the
                    // muted text flips to a red warning so HR fixes
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
                            ' · daily rate ' + formatMoney(dailyRate) + ' MVR' +
                            ' · prorated basic ' + formatMoney(proratedBase) + ' MVR';
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
                    var leaveDaysTotal = 0;
                    var leaveAmountTotal = 0;
                    var totalLeaveDaysFromService = parseFloat(response.data.leave_balance || 0);

                    if (breakdown.length) {
                        breakdown.forEach(function (row) {
                            var days = parseFloat(row.available_days || 0);
                            var amount = days * dailyRate;
                            leaveDaysTotal += days;
                            leaveAmountTotal += amount;
                            $leaveTbody.append(
                                '<tr>' +
                                    '<td>' + (row.leave_type || 'Leave') + '</td>' +
                                    '<td class="text-end">' + days.toFixed(2) + '</td>' +
                                    '<td class="text-end">' + formatMoney(amount) + '</td>' +
                                '</tr>'
                            );
                        });
                    } else if (totalLeaveDaysFromService > 0) {
                        // Service produced a leave-balance total but the
                        // per-category breakdown is missing (data
                        // anomaly: no benefit-grid match for this
                        // employee's rank/grade, or the breakdown was
                        // dropped server-side). Surface a single fallback
                        // row so the table can't disagree with the Leave
                        // Balance / Leave Encashment inputs above.
                        leaveDaysTotal = totalLeaveDaysFromService;
                        leaveAmountTotal = totalLeaveDaysFromService * dailyRate;
                        $leaveTbody.append(
                            '<tr class="text-muted"><td>Total Leave Balance ' +
                                '<small>(no per-category breakdown — check benefit grid for this rank)</small>' +
                                '</td>' +
                                '<td class="text-end">' + totalLeaveDaysFromService.toFixed(2) + '</td>' +
                                '<td class="text-end">' + formatMoney(leaveAmountTotal) + '</td>' +
                            '</tr>'
                        );
                    } else {
                        // Truly empty — show empty-state row so the
                        // table isn't a blank box.
                        $leaveTbody.append(
                            '<tr><td colspan="3" class="text-center text-muted">' +
                                'No leave entitlements found for this employee.' +
                            '</td></tr>'
                        );
                    }
                    $('#payable-leaves-days-total').text(leaveDaysTotal.toFixed(2));
                    $('#payable-leaves-amount-total').text(formatMoney(leaveAmountTotal, 'MVR'));

                    // Compact one-liner under the Leave Balance input
                    // (kept for the at-a-glance read). Hides when there
                    // are no rows; the table below already shows the
                    // empty-state message.
                    var $leaveBreak = $('#leave-breakdown');
                    if (breakdown.length) {
                        var parts = breakdown.map(function (row) {
                            var days = parseFloat(row.available_days || 0).toFixed(2);
                            return (row.leave_type || 'Leave') + ': ' + days + 'd';
                        });
                        $leaveBreak.html('<i class="fa-solid fa-list-ul me-1"></i>' + parts.join(' · '));
                    } else {
                        $leaveBreak.text('');
                    }

                    // ─── Leave Encashment formula explainer ───
                    // Formula: total unused leave days × daily salary,
                    // where daily salary = basic_salary_mvr / payroll
                    // cycle days. Spelled out concretely with the actual
                    // numbers so HR doesn't have to reverse-engineer it.
                    var $encFormula = $('#leave-encashment-formula');
                    var leaveDays = parseFloat(response.data.leave_balance || 0);
                    var enc       = parseFloat(response.data.leave_encashment || 0);
                    $encFormula.html(
                        '<i class="fa-solid fa-calculator me-1"></i>' +
                        leaveDays.toFixed(2) + ' days × ' + formatMoney(dailyRate) + ' MVR/day' +
                        ' = ' + formatMoney(enc) + ' MVR'
                    );

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
                            'Taxable income MVR ' + formatMoney(response.data.taxable_income || 0) +
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
                            'shortfall ' + noticeShort + 'd × ' + formatMoney(dailyRate) + ' MVR/day = ' +
                            formatMoney(noticeCharge) + ' MVR'
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
                            loanParts.push('Loan: ' + formatMoney(loanMvr, 'MVR') +
                                ' (' + loanN + ' installment' + (loanN === 1 ? '' : 's') + ')');
                        }
                        if (advanceMvr > 0) {
                            loanParts.push('Salary Advance: ' + formatMoney(advanceMvr, 'MVR') +
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
                    allowanceData.forEach(a => {
                        tbody.append(`<tr>
                            <td>${a.name}</td>
                            <td>${formatMoney(a.original_amount, a.unit)}</td>
                            <td>${formatMoney(a.converted_amount, 'MVR')}</td>
                        </tr>`);
                    });
                    $("#total-allowances").text(formatMoney(totalAllowances, 'MVR'));

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
        // restore it before POST (Laravel rejects commas). Non-money
        // fields (leave_balance) get the value as-is.
        if (field.hasClass('money-field')) {
            field.attr('data-raw', value == null ? 0 : value);
            field.val(formatMoney(value));
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