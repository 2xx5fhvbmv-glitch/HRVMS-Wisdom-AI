@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #final-settlement-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #final-settlement-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="final-settlement-hero">
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
                <form id="final-settlement-form" method="POST" data-parsley-validate>
    @csrf
                    <div class="ffs-wrap" id="ffsWrap">

                        {{-- ════════════════════ Settlement setup + Employee details ════════════════════ --}}
                        <div class="ffs-card ffs-combo ffs-span2">
                            <div class="ffs-cl">
                                <div class="ffs-ct"><h2>Settlement setup</h2></div>
                                <div class="ffs-grid ffs-g2">
                                    <div class="ffs-f ffs-fspan">
                                        <label for="select_emp">Select employee or employee ID <span class="ffs-req">*</span></label>
                                        <select class="form-select dd-native-select" name="select_emp" id="select_emp" onchange="getEmpDetails(this.value)"
                                            data-parsley-required="true" data-parsley-error-message="Please select an employee" data-parsley-errors-container="#select_emp_error">
                                            <option value="">Select Employee</option>
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
                                        @php $selectedEmp = !empty($preselectedEmployeeId) ? collect($employees)->first(function($e) use ($preselectedEmployeeId){ return (int)$e->employee->id === (int)$preselectedEmployeeId; }) : null; @endphp
                                        <div class="dd ffs-emp-dd" data-target="#select_emp">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">{{ $selectedEmp ? ($selectedEmp->employee->Emp_id . ' - ' . $selectedEmp->employee->resortAdmin->full_name) : 'Select Employee' }}</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Employee">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item{{ !$selectedEmp ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($employees)
                                                        @foreach($employees as $emp)
                                                        @php
                                                            $ddAdminId = $emp->employee->resortAdmin->id ?? null;
                                                            $ddFullName = $emp->employee->resortAdmin->full_name ?? '';
                                                            $ddParts = preg_split('/\s+/', trim($ddFullName));
                                                            $ddInitials = strtoupper(($ddParts[0][0] ?? '') . (isset($ddParts[1]) ? $ddParts[1][0] : '')) ?: '?';
                                                            // getResortUserPicturesBatch() always resolves to at least
                                                            // the app's generic silhouette default — only render <img>
                                                            // for a REAL uploaded photo, never the generic placeholder,
                                                            // so the initials fallback shows instead per the photo-first
                                                            // convention.
                                                            $ddDefaultPicture = url(config('settings.default_picture'));
                                                            $ddPhoto = $ddAdminId ? ($employeePictures[$ddAdminId] ?? null) : null;
                                                            if ($ddPhoto === $ddDefaultPicture) { $ddPhoto = null; }
                                                            $ddPosition = $emp->employee->position->position_title ?? null;
                                                            $ddDepartment = $emp->employee->department->name ?? null;
                                                            $ddSub = trim(collect([$emp->employee->Emp_id, collect([$ddPosition, $ddDepartment])->filter()->implode(' – ')])->filter()->implode(' · '));
                                                        @endphp
                                                        <div class="dd-item{{ !empty($preselectedEmployeeId) && (int) $preselectedEmployeeId === (int) $emp->employee->id ? ' active' : '' }}" role="option" data-value="{{ $emp->employee->id }}">
                                                            <span class="ffs-dd-av">
                                                                <span class="ffs-dd-av-fallback">{{ $ddInitials }}</span>
                                                                @if($ddPhoto)<img src="{{ $ddPhoto }}" alt="" onerror="this.remove()">@endif
                                                            </span>
                                                            <span class="ffs-dd-text">
                                                                <span class="dd-nm">{{ $emp->employee->Emp_id }} - {{ $ddFullName }}</span>
                                                                @if($ddSub)<span class="ffs-dd-sub">{{ $ddSub }}</span>@endif
                                                            </span>
                                                            <svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div id="select_emp_error"></div>
                                    </div>
                                    <div class="ffs-f">
                                        <label for="resignation_date">Resignation effective date <span class="ffs-req">*</span></label>
                                        <input type="text" id="resignation_date" name="resignation_date" class="ffs-inp ffs-ro" placeholder="Resignation effective date"
                                            disabled required>
                                    </div>
                                    <div class="ffs-f">
                                        <label for="last_day">Last working day <span class="ffs-req">*</span></label>
                                        <input type="text" id="last_day" name="last_day" class="ffs-inp ffs-ro" placeholder="Last working day" disabled required>
                                    </div>
                                </div>
                            </div>
                            <div class="ffs-cr ffs-pcard">
                                <div class="ffs-ct"><h2>Employee details</h2></div>
                                <div class="ffs-pc-empty" id="pcEmpty">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                                    Select an employee to load their details and settlement.
                                </div>
                                <div class="ffs-pc-body" id="pcBody">
                                    <div class="ffs-pc-head">
                                        <span class="ffs-pa" id="img-circle"><img src="" alt=""></span>
                                        <div class="ffs-pn" id="pcName"></div>
                                    </div>
                                    <div class="ffs-prow"><span class="ffs-k">Employee ID</span><span class="ffs-v" id="pcId"></span></div>
                                    <div class="ffs-prow"><span class="ffs-k">Department</span><span class="ffs-v" id="pcDept"></span></div>
                                    <div class="ffs-prow"><span class="ffs-k">Position</span><span class="ffs-v" id="pcPos"></span></div>
                                    <div class="ffs-prow"><span class="ffs-k">Status</span><span class="ffs-stbadge"><i class="ffs-dot"></i>Resigned</span></div>
                                </div>
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
                        <div class="ffs-card ffs-locked">
                            <div class="ffs-ct"><span class="ffs-ic ffs-up"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></span><h2>Earnings</h2></div>
                            <div class="ffs-line">
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Basic salary <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="basic_salary" class="form-control money-field" name="basic_salary"
                                            placeholder="0.00" data-parsley-required="true" readonly>
                                    </div>
                                </div>
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Earned salary <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span>
                                            {{-- Manual override toggle. Earned Salary is normally
                                                 derived from attendance (worked × daily rate); HR
                                                 can flip this to enter a manual figure when
                                                 attendance is incomplete. Edit ON: pencil → check
                                                 icon, input becomes editable, Gross Earning recomputes
                                                 on blur. Edit OFF: restores the attendance-derived
                                                 value and re-locks the field. --}}
                                            <a href="javascript:void(0)" id="earned_salary_edit_toggle" class="ffs-edit" title="Edit Earned Salary manually">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                        </div>
                                        {{-- Attendance-based earning breakdown — populated by
                                             getEmpDetails() with worked/expected days, daily
                                             rate. Same formula payroll uses (basic ÷ days_in_period)
                                             × (present + day_off). Red banner if attendance gap. --}}
                                        <div id="earning-breakdown" class="ffs-n"></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="earned_salary" class="form-control money-field" name="earned_salary"
                                            placeholder="0.00" data-parsley-required="true" readonly>
                                    </div>
                                </div>
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Service charge <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="service_charge" class="form-control money-field"
                                            name="service_charge" placeholder="0.00" data-parsley-required="true">
                                    </div>
                                </div>
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Leave balance <span class="ffs-u">days</span> <span class="ffs-req">*</span></div>
                                        <div id="leave-breakdown" class="ffs-n"></div>
                                    </div>
                                    <div class="ffs-lv">
                                        {{-- step="0.01" so HR-edited fractional days (4.60, 5.01, etc.)
                                             pass HTML5 + Parsley validation. Without it, type="number"
                                             defaults to step=1 and rejects any decimal as
                                             "This value seems to be invalid." --}}
                                        <input type="number" name="leave_balance" id="leave_balance" class="form-control" min="0" max="500" step="0.01" placeholder="0.00" readonly data-parsley-required="true" data-parsley-type="number"
                                            data-parsley-min="0" data-parsley-trigger="change">
                                    </div>
                                </div>
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Leave encashment <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                        <div id="leave-encashment-formula" class="ffs-n"></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="leave_encashment" name="leave_encashment" class="form-control money-field"
                                            placeholder="0.00" data-parsley-required="true" readonly>
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
                        <div class="ffs-card ffs-locked">
                            <div class="ffs-ct"><span class="ffs-ic ffs-dn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12l7 7 7-7"/></svg></span><h2>Deductions</h2></div>
                            <div class="ffs-line">
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">EWT / LWT <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                        {{-- Surfaced when taxable income clears the MIRA EWT
                                             threshold but the employee isn't EWT-enrolled or
                                             has no TIN on file. Same warning shape as the
                                             Employee Details "Salary Details" card. --}}
                                        <div id="ewt-warning" class="ffs-n ffs-warn" style="display:none">
                                            <span id="ewt-warning-text"></span>
                                        </div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="tax" class="form-control money-field" name="tax"
                                            placeholder="0.00" data-parsley-required="true" readonly>
                                    </div>
                                </div>
                                {{-- Pension column — only Maldivian (Local) employees
                                     contribute to MRPS. JS toggles visibility based on the
                                     selected employee's data-nationality option attr;
                                     foreigners get the column hidden AND
                                     data-parsley-required stripped so Parsley doesn't
                                     block submit on an invisible field. --}}
                                <div class="ffs-lrow" id="pension-col">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Pension / MRPS <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="pension" class="form-control money-field" name="pension"
                                            placeholder="0.00" data-parsley-required="true" readonly>
                                    </div>
                                </div>
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Loan or advance <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                        {{-- Loan vs Salary Advance bucket breakdown — built
                                             from payroll_recovery_schedule rows joined to
                                             payroll_advance. Hidden when nothing's
                                             outstanding so the line doesn't shout 0.00. --}}
                                        <div id="loan-breakdown" class="ffs-n"></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="loan_payment" name="loan_payment" class="form-control money-field"
                                            placeholder="0.00" data-parsley-required="true" readonly>
                                    </div>
                                </div>
                                {{-- Notice Period Charge — fetched from the Notice
                                     Period module config (employee_notice_period.period
                                     = required days). Charge = max(0, required − served)
                                     × dailySalary. Field is editable so HR can override
                                     (e.g. management waived part of it) but the
                                     breakdown below shows the computed reference value. --}}
                                <div class="ffs-lrow">
                                    <div class="ffs-lk">
                                        <div class="ffs-t">Notice period charge <span class="display-currency-label ffs-u">{{ $displayCurrencyCode ?? 'MVR' }}</span> <span class="ffs-req">*</span></div>
                                        <div id="notice-period-breakdown" class="ffs-n"></div>
                                    </div>
                                    <div class="ffs-lv">
                                        <input type="text" id="notice_period_charge" name="notice_period_charge"
                                            class="form-control money-field" placeholder="0.00"
                                            data-parsley-required="true">
                                    </div>
                                </div>
                            </div>

                            {{-- IMPORTANT: this initial row must carry the .deduction-row
                                 class so the submit JS picks it up. Without it the JS only
                                 iterated rows appended by "Add more" and silently dropped
                                 anything HR typed into the first slot (reported as
                                 "Uniform Damage 100 USD didn't reach the review page"). --}}
                            <div class="ffs-mdrow deduction-row">
                                <div class="ffs-f">
                                    <label>Add deduction</label>
                                    <select class="form-select dd-native-select deduction-select" id="deductionSelect_0" data-parsley-required-if="#deduction-amount-first" data-parsley-trigger="change">
                                        <option value="">Select Deduction</option>
                                        @foreach($deductions as $deduction)
                                            <option value="{{ $deduction->id }}" data-unit="{{ $deduction->currency }}">{{ $deduction->deduction_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="dd" data-target="#deductionSelect_0">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Deduction</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Deduction">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a deduction…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Deduction</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @foreach($deductions as $deduction)
                                                <div class="dd-item" role="option" data-value="{{ $deduction->id }}"><span class="dd-nm">{{ $deduction->deduction_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ffs-f">
                                    <label for="deduction-amount-first">Amount</label>
                                    <input type="number" id="deduction-amount-first" class="ffs-inp deduction-amount" placeholder="Enter amount" data-parsley-type="number" data-parsley-min="0" data-parsley-trigger="change">
                                </div>
                                <div class="ffs-f ffs-unitf">
                                    <label>Unit</label>
                                    <input type="text" class="ffs-inp amount-unit" placeholder="—" readonly>
                                </div>
                                <a href="#" class="ffs-addbtn add-fullFinal add-deduction"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Add more</a>
                            </div>
                            <div class="deductions-container"></div>
                        </div>

                        {{-- ───── Allowance breakdown | Leave balance & encashment ───── --}}
                        <div class="ffs-card ffs-locked">
                            <div class="ffs-ct"><h2>Allowance breakdown</h2></div>
                            <table class="table ffs-tbl" id="allowance-details">
                                <thead>
                                    <tr>
                                        <th>Allowance name</th>
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

                        {{-- ONE table that backs BOTH the "Leave balance (days)" and
                             "Leave encashment" inputs above. Each row shows the per-
                             category leave name (Annual / Sick / Day Off / Public Holiday
                             / Other), the unused days carried forward, and the days ×
                             daily_salary value. The Days column totals to the Leave
                             Balance input; the Amount column totals to the Leave
                             Encashment input. Populated by JS in getEmpDetails(). --}}
                        <div class="ffs-card ffs-locked">
                            <div class="ffs-ct">
                                <h2>Leave balance &amp; encashment</h2>
                                <span class="ffs-spacer"></span>
                                {{-- Reset button: re-renders the table from the server-supplied
                                     breakdown stashed at last render, discarding HR edits + any
                                     manually-added rows. Disabled until #payable-leaves has a
                                     stash (i.e. after the first employee fetch). --}}
                                <button type="button" class="btn btn-sm payroll-btn-secondary"
                                        id="reset-payable-leaves"
                                        title="Reset rows to the values calculated by the system">
                                    <i class="fa-solid fa-rotate-left me-1"></i>Reset
                                </button>
                                {{-- Add-row button: appends a manual entry to the table so HR can
                                     record settlement items that aren't in the leave grid (extra
                                     PH credits, day-off carry, ad-hoc adjustments). The row's
                                     Days field is editable and rolls into the totals. --}}
                                <button type="button" class="btn btn-sm payroll-btn-positive ms-2"
                                        id="add-payable-leave-row">
                                    <i class="fa-solid fa-plus me-1"></i>Add row
                                </button>
                            </div>
                            <table class="table ffs-tbl" id="payable-leaves">
                                <thead>
                                    <tr>
                                        <th>Leave type</th>
                                        <th class="text-end" style="width:120px;">Days</th>
                                        <th class="text-end">Rate</th>
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

                        {{-- ───── Settlement breakdown (full width) ─────
                             Same shape as the review page: per-source row
                             (Basic Salary for N worked days, Service Charge,
                             Leave Days Salary, Allowance) → Gross Earning at
                             the bottom. Lets HR see how the Earning Salary
                             figure is built without leaving the page. All
                             cells are converted to the display currency
                             through mvrToDisplay() in the JS below. --}}
                        <div class="ffs-card ffs-locked ffs-span2">
                            <div class="ffs-ct"><h2>Settlement breakdown</h2><span class="ffs-sub">Earnings − Deductions = Net settlement</span></div>
                            <div class="ffs-settle" id="settlement-details">
                                <div class="ffs-s2col" id="settlement-earnings-col">
                                    <div class="ffs-s2h">Earnings</div>
                                    <!-- Populated by JS -->
                                </div>
                                <div class="ffs-s2col ffs-ded" id="settlement-deductions-col">
                                    <div class="ffs-s2h">Deductions</div>
                                    <!-- Populated by JS -->
                                </div>
                                <div class="ffs-s2net">
                                    <span class="ffs-lbl">Net settlement</span>
                                    <span class="ffs-amt" id="settlement-details-total">0.00 {{ $displayCurrencyCode ?? 'MVR' }}</span>
                                </div>
                            </div>
                            <input type="hidden" name="last_working_date" id="last_working_date"/>
                            <input type="hidden" name="payroll_start_date" id="payroll_start_date"/>
                            <input type="hidden" name="payment_mode" id="payment_mode"/>
                            <div class="ffs-cfoot">
                                <button type="submit" id="submit" class="btn payroll-btn-primary ffs-submit @if(Common::checkRouteWisePermission('payslip.fullandfinalsettlement',config('settings.resort_permissions.create')) == false) d-none @endif" disabled>Submit settlement</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts._dropdown_styles')
<style>
    select.parsley-error + .select2 .select2-selection {
    border-color: #dc3545 !important; /* red border */
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* ════════════════════════════════════════════════════════════════
   Full & Final Settlement — frontend-only restyle. Scoped ffs-
   prefixed classes so nothing here leaks onto the shared
   .fullFinal-, .empDetails-user, .img-circle classes other pages
   (Accommodation, Leave PDF) still use.
   ════════════════════════════════════════════════════════════════ */
.ffs-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
.ffs-span2 { grid-column: 1 / -1; }
@media (max-width: 900px) { .ffs-wrap { grid-template-columns: 1fr; } }

.ffs-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 16px 40px rgba(1,70,83,.10); padding: 24px 26px; }

/* combined setup + employee details card */
.ffs-card.ffs-combo { padding: 0; display: grid; grid-template-columns: 1.25fr 1fr; }
.ffs-combo .ffs-cl { padding: 24px 30px 24px 26px; }
.ffs-combo .ffs-cr { padding: 24px 26px 24px 30px; border-left: 1px solid var(--line, #EEF2F2); }
@media (max-width: 900px) { .ffs-card.ffs-combo { grid-template-columns: 1fr; } .ffs-combo .ffs-cr { border-left: none; border-top: 1px solid var(--line, #EEF2F2); } }
.ffs-ct { display: flex; align-items: center; gap: 11px; margin-bottom: 18px; }
.ffs-ct .ffs-ic { width: 30px; height: 30px; flex: none; border-radius: 9px; display: grid; place-items: center; }
.ffs-ct .ffs-ic.ffs-up { background: var(--teal-soft, #f1f7f7); color: var(--teal); }
.ffs-ct .ffs-ic.ffs-dn { background: #fbeceb; color: #B4462F; }
.ffs-ct h2 { font-size: 18px; font-weight: 600; color: var(--ink); }
.ffs-ct .ffs-sub { font-size: 12.5px; color: #99A1A5; font-weight: 400; }
.ffs-ct .ffs-spacer { flex: 1; }

.ffs-grid { display: grid; gap: 18px 22px; }
.ffs-grid.ffs-g2 { grid-template-columns: 1fr 1fr; }
.ffs-fspan { grid-column: 1 / -1; }
.ffs-f label { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; letter-spacing: .2px; text-transform: uppercase; color: #6B7378; margin-bottom: 8px; }
.ffs-req { color: #C7CDCF; font-weight: 400; }
.ffs-edit { color: var(--teal); display: inline-grid; place-items: center; cursor: pointer; margin-left: 4px; }
.ffs-inp,
.ffs-card .form-control,
.ffs-card .form-select { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 13px 15px; font: inherit; font-size: 15px; color: var(--ink); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.ffs-card .form-control:focus,
.ffs-card .form-select:focus,
.ffs-inp:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }
.ffs-inp.ffs-ro,
.ffs-card .form-control[disabled],
.ffs-card .form-control[readonly] { background: #F7F8F8; color: #6B7378; border-color: transparent; }
.ffs-n { font-size: 12.5px; color: #99A1A5; margin-top: 8px; line-height: 1.5; }
.ffs-n.ffs-warn { color: #B4462F; }
.ffs-n.ffs-warn::before { content: "⚠  "; }
/* getEmpDetails() toggles Bootstrap's own text-danger/text-warning/
   text-muted utility classes on #earning-breakdown and
   #notice-period-breakdown (attendance-gap / notice-shortfall warnings)
   — compound selectors here so those colors win regardless of CSS
   load order against the plain .ffs-n grey default above. */
.ffs-n.text-danger { color: #B4462F; }
.ffs-n.text-warning { color: #B4462F; }
.ffs-n.text-muted { color: #99A1A5; }

/* statement-row line items (earnings / deductions) — same rhythm as settlement breakdown */
.ffs-line { border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; }
.ffs-lrow { display: flex; align-items: flex-start; gap: 16px; padding: 15px 16px; border-bottom: 1px solid #F3F6F6; }
.ffs-lrow:last-child { border-bottom: none; }
.ffs-lrow .ffs-lk { flex: 1; min-width: 0; padding-top: 2px; }
.ffs-lrow .ffs-lk .ffs-t { font-size: 14px; font-weight: 500; color: var(--ink); display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.ffs-lrow .ffs-lk .ffs-t .ffs-u { color: #99A1A5; font-weight: 400; font-size: 12.5px; }
.ffs-lrow .ffs-lv { flex: none; width: 148px; }
.ffs-lrow .ffs-lv .form-control { text-align: right; font-variant-numeric: tabular-nums; padding: 11px 13px; border-radius: 10px; }

/* employee-details panel (Incident "Reported by" style) */
.ffs-pcard .ffs-ct { border-bottom: 1px solid var(--line, #EEF2F2); padding-bottom: 14px; margin-bottom: 16px; }
.ffs-pc-empty { display: flex; align-items: center; gap: 12px; color: #99A1A5; font-size: 14px; padding: 6px 2px; }
.ffs-pc-body { display: none; }
.ffs-wrap.selected .ffs-pc-empty { display: none; }
.ffs-wrap.selected .ffs-pc-body { display: block; }
.ffs-pc-head { display: flex; align-items: center; gap: 14px; padding-bottom: 14px; border-bottom: 1px solid var(--line, #EEF2F2); }
.ffs-pa { width: 52px; height: 52px; flex: none; border-radius: 50%; background: var(--teal-soft, #f1f7f7); color: var(--teal); font-size: 16px; font-weight: 600; display: grid; place-items: center; overflow: hidden; }
.ffs-pa img { width: 100%; height: 100%; object-fit: cover; }
.ffs-pn { font-size: 17px; font-weight: 600; color: var(--ink); }
.ffs-prow { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 13px 0; border-bottom: 1px solid #F3F6F6; font-size: 14px; }
.ffs-prow:last-child { border-bottom: none; }
.ffs-prow .ffs-k { color: #6B7378; }
.ffs-prow .ffs-v { font-weight: 600; text-align: right; color: var(--ink); }
.ffs-stbadge { display: inline-flex; align-items: center; gap: 7px; background: #F7F8F8; color: #3A4145; font-size: 12.5px; font-weight: 600; padding: 5px 11px; border-radius: 20px; }
.ffs-stbadge .ffs-dot { width: 6px; height: 6px; border-radius: 50%; background: #99A1A5; }

/* locked downstream — dimmed + inert until an employee is selected */
.ffs-locked { transition: opacity .3s, filter .3s; }
.ffs-wrap:not(.selected) .ffs-locked { opacity: .4; filter: grayscale(.3); pointer-events: none; user-select: none; }
@media (prefers-reduced-motion: reduce) { .ffs-locked { transition: none; } }

/* tables */
.ffs-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; margin: 0; }
/* .ffs-tbl doubled on th/td/tfoot rules — the app's own default.css ships
   ".table thead th{padding:0 10px 12px;font-size:16px}" and
   ".table tbody td{padding:16px 10px}" (both 1-class-2-element selectors,
   higher specificity than a plain ".ffs-tbl th"/".ffs-tbl td"), which was
   silently winning and zeroing out the top/left padding here. Repeating
   the class is the standard, minimal specificity bump — it doesn't touch
   default.css, which every other .table on the site still needs untouched. */
.ffs-tbl.ffs-tbl th { background: var(--teal-soft, #f1f7f7); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6B7378; padding: 12px 15px !important; border-bottom: 1px solid var(--line, #EEF2F2); }
.ffs-tbl.ffs-tbl td { padding: 13px 15px !important; border-bottom: 1px solid #F3F6F6; font-size: 14px; vertical-align: middle; }
/* default.css also zeroes padding-left specifically on :first-child cells
   (!important, for other DataTables-style tables app-wide) — restore it
   here too, scoped to these two tables only. */
.ffs-tbl.ffs-tbl th:first-child,
.ffs-tbl.ffs-tbl td:first-child { padding-left: 15px !important; }
.ffs-tbl.ffs-tbl tr:last-child td { border-bottom: none; }
.ffs-tbl .text-end { text-align: right; font-variant-numeric: tabular-nums; }
.ffs-tbl.ffs-tbl tfoot tr td,
.ffs-tbl.ffs-tbl tfoot tr th { font-weight: 600; background: #fcfdfd; font-size: 14.5px; padding: 13px 15px !important; }
.ffs-tbl.ffs-tbl tfoot tr td:first-child,
.ffs-tbl.ffs-tbl tfoot tr th:first-child { padding-left: 15px !important; }
.ffs-tbl .form-control-sm { border: 1px solid var(--line, #EEF2F2); border-radius: 8px; padding: 8px 10px; font-size: 14px; }

.ffs-mdrow { display: grid; grid-template-columns: 1fr 1fr 90px auto; gap: 12px; align-items: end; margin-top: 18px; padding: 18px 26px 24px; border-top: 1px dashed var(--line, #EEF2F2); }
.ffs-mdrow .ffs-f label { margin-bottom: 8px; }
.ffs-unitf .ffs-inp { text-align: center; color: #99A1A5; }
.ffs-addbtn { background: var(--teal-soft, #f1f7f7); color: var(--teal); border: 1px solid #dcebeb; border-radius: 11px; padding: 12px 17px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; text-decoration: none; height: fit-content; }
.ffs-addbtn:hover { background: #e7f1f1; color: var(--teal); }

/* rows appended by "Add more" / removed by the .remove-deduction handler */
.ffs-mdrow-added { display: grid; grid-template-columns: 1fr 1fr 90px auto; gap: 12px; align-items: end; padding: 16px 26px; border-top: 1px solid #F3F6F6; }

/* settlement — two columns (earnings buildup | deductions) + full-width net bar */
.ffs-settle { border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; display: grid; grid-template-columns: 1fr 1fr; }
.ffs-s2col { padding-bottom: 6px; }
.ffs-s2col.ffs-ded { border-left: 1px solid var(--line, #EEF2F2); }
.ffs-s2h { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #99A1A5; padding: 15px 18px 4px; }
.ffs-s2row { display: flex; justify-content: space-between; align-items: baseline; gap: 14px; padding: 11px 18px; font-size: 14px; border-top: 1px solid #F3F6F6; }
.ffs-s2row .ffs-lbl { color: #3A4145; }
.ffs-s2row .ffs-amt { font-variant-numeric: tabular-nums; font-weight: 500; white-space: nowrap; }
.ffs-s2row.ffs-strong { font-weight: 600; background: #fcfdfd; }
.ffs-s2col.ffs-ded .ffs-s2row .ffs-amt { color: #B4462F; }
.ffs-s2net { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px 18px; background: var(--teal-soft, #f1f7f7); border-top: 1px solid var(--line, #EEF2F2); }
.ffs-s2net .ffs-lbl { font-weight: 600; font-size: 15px; color: var(--ink); }
.ffs-s2net .ffs-amt { color: var(--teal); font-weight: 600; font-size: 20px; font-variant-numeric: tabular-nums; }
@media (max-width: 640px) { .ffs-settle { grid-template-columns: 1fr; } .ffs-s2col.ffs-ded { border-left: none; border-top: 1px solid var(--line, #EEF2F2); } }

.ffs-cfoot { display: flex; justify-content: flex-end; margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--line, #EEF2F2); }
.ffs-submit:disabled { opacity: .55; cursor: default; transform: none !important; box-shadow: none !important; }

/* employee dropdown — each row: photo-first avatar (initials fallback)
   + a stacked name/position-department text block, vertically centered
   against the avatar (matches the Incident "Reported by" avatar-row
   pattern used elsewhere, rather than top-aligning against wrapped text). */
.ffs-emp-dd .dd-item { align-items: center; gap: 11px; }
.ffs-dd-av { position: relative; width: 34px; height: 34px; flex: none; border-radius: 50%; background: var(--teal-soft, #f1f7f7); overflow: hidden; }
.ffs-dd-av-fallback { position: absolute; inset: 0; display: grid; place-items: center; color: var(--teal); font-size: 12px; font-weight: 600; }
.ffs-dd-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.ffs-dd-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.ffs-emp-dd .dd-nm { flex: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ffs-dd-sub { font-size: 12px; color: #99A1A5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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

        // Manually trigger Parsley validation when a dropdown changes
        $(".dd-native-select").on('change', function () {
            var parsleyField = $(this).parsley();
            parsleyField.validate();

            // Add/remove the error class on the .dd-trigger based on validation
            if (parsleyField.isValid()) {
                $(this).siblings('.dd').find('.dd-trigger').removeClass('is-invalid');
            } else {
                $(this).siblings('.dd').find('.dd-trigger').addClass('is-invalid');
            }
        });

        // Parsley field validation handler
        window.Parsley.on('field:validated', function (fieldInstance) {
            var $element = fieldInstance.$element;
            if ($element.hasClass('dd-native-select')) {
                var $trigger = $element.siblings('.dd').find('.dd-trigger');
                if (fieldInstance.isValid()) {
                    $trigger.removeClass('is-invalid');
                } else {
                    $trigger.addClass('is-invalid');
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
            window.wisdomDD.sync('#select_emp');
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
                    ? '<button type="button" class="btn btn-sm eb-btn-critical text-danger p-0 payable-leave-remove"' +
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
        // Two-column settlement layout (Earnings | Deductions) — each
        // column keeps its own static .ffs-s2h header in the Blade
        // markup, so only the .ffs-s2row lines are cleared/rebuilt here.
        var $earnCol = $('#settlement-earnings-col');
        var $dedCol  = $('#settlement-deductions-col');
        $earnCol.find('.ffs-s2row').remove();
        $dedCol.find('.ffs-s2row').remove();
        var grossMvr = 0;
        earningRows.forEach(function (r) {
            grossMvr += r.value;
            $earnCol.append(
                '<div class="ffs-s2row"><span class="ffs-lbl">' + r.label + '</span>' +
                '<span class="ffs-amt">' + formatMoney(mvrToDisplay(r.value)) + '</span></div>'
            );
        });
        // Gross Earning subtotal — sits above the deductions column so HR
        // sees the pre-deduction figure clearly.
        $earnCol.append(
            '<div class="ffs-s2row ffs-strong"><span class="ffs-lbl">Gross earning</span>' +
            '<span class="ffs-amt">' + formatMoney(mvrToDisplay(grossMvr)) + '</span></div>'
        );

        var deductionsMvr = 0;
        deductionRows.forEach(function (d) {
            deductionsMvr += d.value;
            // Negative-styling cue: red text + leading minus so the
            // direction reads clearly even at a glance.
            $dedCol.append(
                '<div class="ffs-s2row"><span class="ffs-lbl">' + d.label + '</span>' +
                '<span class="ffs-amt">− ' + formatMoney(mvrToDisplay(d.value)) + '</span></div>'
            );
        });
        $dedCol.append(
            '<div class="ffs-s2row ffs-strong"><span class="ffs-lbl">Total deductions</span>' +
            '<span class="ffs-amt">− ' + formatMoney(mvrToDisplay(deductionsMvr)) + '</span></div>'
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

    var payableLeaveTypeUidCounter = 0; // always-incrementing, never reused — safe as a .dd data-target id even after rows are removed/re-added out of order
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
            var typeUid = 'payableLeaveType_' + (++payableLeaveTypeUidCounter);
            var opts = '<option value="" disabled selected>Select leave type…</option>';
            var ddItems = '<div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select leave type…</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>';
            types.forEach(function (t) {
                var safe = $('<i>').text(t).html();
                opts += '<option value="' + safe + '">' + safe + '</option>';
                ddItems += '<div class="dd-item" role="option" data-value="' + safe + '"><span class="dd-nm">' + safe + '</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>';
            });
            // Keep an "Other" path so HR can still record one-off items
            // that aren't in the leave grid (eg. a pre-existing comp day).
            opts += '<option value="__other__">Other…</option>';
            ddItems += '<div class="dd-item" role="option" data-value="__other__"><span class="dd-nm">Other…</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>';
            typeCell = '<select class="form-select form-select-sm dd-native-select payable-leave-type" id="' + typeUid + '">' + opts + '</select>' +
                '<div class="dd" data-target="#' + typeUid + '">' +
                    '<button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">' +
                        '<span class="dd-lbl">Select leave type…</span>' +
                        '<svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>' +
                    '</button>' +
                    '<div class="dd-panel" role="listbox" aria-label="Leave type"><div class="dd-scroll">' + ddItems + '</div></div>' +
                '</div>';
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
                '<button type="button" class="btn btn-sm eb-btn-critical text-danger p-0 payable-leave-remove"' +
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
        $(this).siblings('.dd').remove();
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
                    // Un-dims Earnings/Deductions/Allowance/Leave/Settlement
                    // and reveals the Employee details panel (CSS keyed off
                    // .selected on the wrapper).
                    document.getElementById('ffsWrap').classList.add('selected');
                    $('#submit').prop('disabled', false);

                    // Photo-first avatar with an initials fallback — the
                    // photo URL from Common::getResortUserPicture() is
                    // already guaranteed non-empty, but the file itself can
                    // still 404 (never-uploaded/removed), so onerror swaps
                    // in the initials chip rather than showing a broken image.
                    var fullName = response.data.full_name || '';
                    var nameParts = fullName.trim().split(/\s+/);
                    var initials = ((nameParts[0] || '').charAt(0) + (nameParts[1] ? nameParts[1].charAt(0) : '')).toUpperCase() || '?';
                    $("#img-circle").html(
                        '<img src="' + (response.data.profile_picture || '') + '" alt="' + fullName + '" onerror="this.parentNode.textContent=\'' + initials + '\'">'
                    );

                    // Employee details panel — Employee ID / Department /
                    // Position are separate rows (Status is static "Resigned"
                    // text in the Blade markup: every employee on this page
                    // is, by definition, a resigning employee awaiting
                    // settlement).
                    $("#pcName").text(fullName);
                    $("#pcId").text(response.data.emp_id);
                    $("#pcDept").text(response.data.department);
                    $("#pcPos").text(response.data.position);

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

                    // ─── Settlement Details (Earning breakdown) columns ───
                    // Rebuilt from the same numbers populating the input
                    // fields above (by recomputeGrossEarning() below) so the
                    // breakdown can NEVER disagree with the Earning Salary
                    // input. Same idea as the review page's Settlement
                    // Details card.
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

    // Keep .money-field `data-raw` in lock-step with the visible value
    // for editable inputs. The submit handler reads `data-raw` to know
    // what to POST — without this listener, anything HR types stays in
    // the input but is dropped on submit because `data-raw` still
    // holds the page-load value. Symptom reported: typing Service
    // Charge = 300, submitting, review then shows 0.
    //
    // Bound via delegation so it covers fields that are added later
    // (currently only the two top-of-form editable money inputs:
    // #service_charge and #notice_period_charge — basic_salary,
    // earned_salary, pension, tax, loan_payment, leave_encashment are
    // readonly and rely on their initial setFieldValueAndResetValidation).
    $(document).on('input change',
        '#service_charge, #notice_period_charge',
        function () {
            var stripped = stripMoney($(this).val());
            $(this).attr('data-raw', stripped === '' ? 0 : stripped);
        }
    );

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

    var deductionSelectUidCounter = 0; // always-incrementing, never reused — safe as a .dd data-target id even after rows are removed/re-added out of order
    $(".add-deduction").click(function(e) {
        e.preventDefault();
        var deductionUid = 'deductionSelect_' + (++deductionSelectUidCounter);
        $(".deductions-container").append(`
            <div class="ffs-mdrow-added deduction-row">
                <div class="ffs-f">
                    <label>Deduction</label>
                    <select class="form-select dd-native-select deduction-select" id="${deductionUid}" name="deductionFor[]">
                        <option value="">Select Deduction</option>
                        @foreach($deductions as $deduction)
                            <option value="{{$deduction->id}}" data-unit="{{$deduction->currency}}">{{$deduction->deduction_name}}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#${deductionUid}">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select Deduction</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Deduction">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a deduction…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Deduction</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($deductions as $deduction)
                                <div class="dd-item" role="option" data-value="{{$deduction->id}}"><span class="dd-nm">{{$deduction->deduction_name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ffs-f">
                    <label for="${deductionUid}_amt">Amount</label>
                    <input type="number" id="${deductionUid}_amt" class="ffs-inp deduction-amount" placeholder="Enter amount" name="deduction_amount[]"
                        data-parsley-type="number" data-parsley-min="0" data-parsley-required-if="#deductionFor" data-parsley-trigger="change">
                </div>
                <div class="ffs-f ffs-unitf">
                    <label>Unit</label>
                    <input type="text" class="ffs-inp amount-unit" name="amount_unit[]" placeholder="—" readonly>
                </div>
                <a href="#" class="btn payroll-btn-secondary btn-sm remove-deduction">Remove</a>
            </div>
        `);
    });

    // Remove Deduction
    $(document).on("click", ".remove-deduction", function(e) {
        e.preventDefault();
        $(this).closest(".ffs-mdrow-added").remove();
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
                    if ($element.hasClass('dd-native-select')) {
                        var $trigger = $element.siblings('.dd').find('.dd-trigger');
                        if (fieldInstance.isValid()) {
                            $trigger.removeClass('is-invalid');
                        } else {
                            $trigger.addClass('is-invalid');
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
@include('resorts._dropdown_script')
@endsection