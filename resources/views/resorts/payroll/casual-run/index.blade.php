@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>PAYROLL</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 1 — period --}}
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">1. Period</h5>
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label">Available periods</label>
                        <select id="cp-period-select" class="form-select form-select-sm">
                            <option value="">Select a period…</option>
                            @foreach ($availablePeriods as $p)
                                <option value="{{ $p['start_date'] }}|{{ $p['end_date'] }}" data-payroll-id="{{ $p['payroll_id'] }}">
                                    {{ $p['label'] }} {{ $p['status_label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm wfp-btn-primary" id="cp-start-draft">Start / Resume Draft</button>
                    </div>
                    <div class="col-auto">
                        <span id="cp-payroll-status" class="text-muted" style="font-size:13px;"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2 — employees --}}
        <div class="card mb-4" id="cp-step-employees" style="display:none;">
            <div class="card-body">
                <h5 class="mb-3">2. Select Casual employees</h5>
                <p class="text-muted" style="font-size:13px;">Only Casual employees whose position has a configured basic salary appear here.</p>
                <div class="table-responsive">
                    <table class="table" id="cp-employees-table" style="width:100%;">
                        <thead><tr><th></th><th>Name</th><th>Position</th><th>Department</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm wfp-btn-primary" id="cp-save-employees">Save Selected Employees</button>
            </div>
        </div>

        {{-- Step 3 — earnings/attendance --}}
        <div class="card mb-4" id="cp-step-earnings" style="display:none;">
            <div class="card-body">
                <h5 class="mb-3">3. Earnings (position basic salary + OT)</h5>
                <button type="button" class="btn btn-sm wfp-btn-secondary mb-3" id="cp-load-earnings">Load Earnings</button>
                <div id="cp-unconfigured-warning" class="alert alert-warning" style="display:none; font-size:13px;"></div>
                <div class="table-responsive">
                    <table class="table table-sm" id="cp-earnings-table">
                        <thead>
                            <tr>
                                <th>Name</th><th>Position</th><th>Present</th><th>Day Off</th><th>Unpaid</th>
                                <th>Regular OT (hrs)</th><th>Friday OT (hrs)</th><th>Holiday OT (hrs)</th>
                                <th>Basic Salary</th><th>Earned Salary</th><th>OT Pay</th><th>Commission (info only)</th><th>Normal Pay</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm wfp-btn-primary" id="cp-save-attendance">Save Attendance</button>
            </div>
        </div>

        {{-- Step 4 — deductions --}}
        <div class="card mb-4" id="cp-step-deductions" style="display:none;">
            <div class="card-body">
                <h5 class="mb-3">4. Deductions</h5>
                <div class="table-responsive">
                    <table class="table table-sm" id="cp-deductions-table">
                        <thead><tr><th>Name</th><th>Attendance</th><th>City Ledger</th><th>Advance/Loan</th><th>Pension</th><th>EWT</th><th>Other</th><th>Total</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm wfp-btn-primary" id="cp-save-deductions">Save Deductions</button>
            </div>
        </div>

        {{-- Step 5 — review + lock --}}
        <div class="card mb-4" id="cp-step-review" style="display:none;">
            <div class="card-body">
                <h5 class="mb-3">5. Review</h5>
                <div class="table-responsive">
                    <table class="table table-sm" id="cp-review-table">
                        <thead><tr><th>Name</th><th>Normal Pay</th><th>Total Deductions</th><th>Net Salary</th><th>Commission (not paid to employee)</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm wfp-btn-secondary" id="cp-save-review">Save Review</button>
                    <button type="button" class="btn btn-sm wfp-btn-primary" id="cp-lock-payroll">Lock Payroll</button>
                    <button type="button" class="btn btn-sm wfp-btn-neutral" id="cp-send-approval">Send for Approval</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
    let cpPayrollId = null;
    let cpStartDate = null;
    let cpEndDate = null;
    let cpEmployeesData = []; // last fetched earnings rows
    let cpDeductionsData = {}; // Emp_id -> deduction row values

    $('#cp-period-select').on('change', function () {
        const val = $(this).val();
        if (!val) return;
        [cpStartDate, cpEndDate] = val.split('|');
        cpPayrollId = $(this).find(':selected').data('payroll-id') || null;
    });

    $('#cp-start-draft').on('click', function () {
        if (!cpStartDate || !cpEndDate) {
            toastr.error('Select a period first.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: '{{ route('payroll.save.draft') }}',
            type: 'POST',
            data: { start_date: cpStartDate, end_date: cpEndDate, status: 'draft', payroll_category: 'Casual', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                cpPayrollId = res.payroll_id;
                $('#cp-payroll-status').text('Draft #' + cpPayrollId + ' — ' + cpStartDate + ' to ' + cpEndDate);
                $('#cp-step-employees').show();
                cpLoadEmployees();
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not start draft.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    function cpLoadEmployees() {
        $.ajax({
            url: '{{ route('resort.casualPayroll.employees') }}',
            type: 'GET',
            data: { draw: 1, start: 0, length: 500 },
            success: function (res) {
                const rows = res.data || [];
                $('#cp-employees-table tbody').html(rows.map(function (e) {
                    return `<tr data-employee-id="${e.id}" data-emp-code="${e.Emp_id}">
                        <td><input type="checkbox" class="cp-emp-checkbox" checked></td>
                        <td>${e.name}</td><td>${e.position}</td><td>${e.department}</td>
                    </tr>`;
                }).join('') || '<tr><td colspan="4" class="text-muted">No configured Casual employees found.</td></tr>');
            }
        });
    }

    $('#cp-save-employees').on('click', function () {
        const employeeIds = [];
        $('#cp-employees-table tbody tr[data-employee-id]').each(function () {
            if ($(this).find('.cp-emp-checkbox').is(':checked')) {
                employeeIds.push($(this).data('employee-id'));
            }
        });
        if (!employeeIds.length) {
            toastr.error('Select at least one employee.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: '{{ route('payroll.saveEmployees') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, employee_ids: employeeIds, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                $('#cp-step-earnings').show();
            },
            error: function () { toastr.error('Could not save employees.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-load-earnings').on('click', function () {
        const employeeIds = [];
        $('#cp-employees-table tbody tr[data-employee-id]').each(function () {
            if ($(this).find('.cp-emp-checkbox').is(':checked')) {
                employeeIds.push($(this).data('employee-id'));
            }
        });
        $.ajax({
            url: '{{ route('resort.casualPayroll.fetchTimeAttendance') }}',
            type: 'POST',
            data: { employees: employeeIds, startDate: cpStartDate, endDate: cpEndDate, currency: '{{ $currency }}', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error('Could not compute earnings.', 'Error', { positionClass: 'toast-bottom-right' }); return; }
                cpEmployeesData = res.data || [];
                if (res.unconfigured_employees && res.unconfigured_employees.length) {
                    $('#cp-unconfigured-warning').show().text('No pay config for: ' + res.unconfigured_employees.join(', ') + ' — excluded.');
                } else {
                    $('#cp-unconfigured-warning').hide();
                }
                $('#cp-earnings-table tbody').html(cpEmployeesData.map(function (e) {
                    return `<tr data-emp-code="${e.id}" data-employee-id="${e.employee_id}">
                        <td>${e.name}</td><td>${e.position}</td><td>${e.present}</td><td>${e.day_offs}</td><td>${e.unpaid_days}</td>
                        <td>${e.regular_ot}</td><td>${e.friday_ot}</td><td>${e.holiday_ot}</td>
                        <td>${e.currency} ${e.basic_salary}</td><td>${e.currency} ${e.earned_salary}</td>
                        <td>${e.currency} ${e.total_ot_pay}</td><td>${e.currency} ${e.service_provider_commission}</td>
                        <td>${e.currency} ${e.normal_pay}</td>
                    </tr>`;
                }).join(''));
                $('#cp-step-deductions').show();
                cpRenderDeductions();
            },
            error: function () { toastr.error('Could not load earnings.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-save-attendance').on('click', function () {
        const attendance = cpEmployeesData.map(function (e) {
            return {
                id: e.id, // Emp_id
                present: e.present,
                absent: e.unpaid_days,
                leaveTypes: '',
                // PayrollTimeAndAttendance has no Friday-specific column —
                // folded into holidayOT, same convention the Permanent run uses.
                regularOT: e.regular_ot,
                holidayOT: e.friday_ot + e.holiday_ot,
                notes: null,
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveAttendance') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, attendance: attendance, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
            },
            error: function () { toastr.error('Could not save attendance.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    function cpRenderDeductions() {
        $('#cp-deductions-table tbody').html(cpEmployeesData.map(function (e) {
            cpDeductionsData[e.id] = cpDeductionsData[e.id] || { attendanceDeduction: e.absent_deduction, cityLedger: 0, advanceLoan: 0, pension: 0, ewt: 0, other: 0 };
            const d = cpDeductionsData[e.id];
            return `<tr data-emp-code="${e.id}">
                <td>${e.name}</td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="attendanceDeduction" value="${d.attendanceDeduction}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="cityLedger" value="${d.cityLedger}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="advanceLoan" value="${d.advanceLoan}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="pension" value="${d.pension}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="ewt" value="${d.ewt}"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm cp-ded" data-field="other" value="${d.other}"></td>
                <td class="cp-ded-total">${cpDeductionTotal(d).toFixed(2)}</td>
            </tr>`;
        }).join(''));
    }

    function cpDeductionTotal(d) {
        return (parseFloat(d.attendanceDeduction) || 0) + (parseFloat(d.cityLedger) || 0) + (parseFloat(d.advanceLoan) || 0)
            + (parseFloat(d.pension) || 0) + (parseFloat(d.ewt) || 0) + (parseFloat(d.other) || 0);
    }

    $('#cp-deductions-table').on('input', '.cp-ded', function () {
        const row = $(this).closest('tr');
        const empCode = row.data('emp-code');
        const field = $(this).data('field');
        cpDeductionsData[empCode][field] = $(this).val();
        row.find('.cp-ded-total').text(cpDeductionTotal(cpDeductionsData[empCode]).toFixed(2));
    });

    $('#cp-save-deductions').on('click', function () {
        const deductionData = cpEmployeesData.map(function (e) {
            const d = cpDeductionsData[e.id];
            const total = cpDeductionTotal(d);
            return {
                id: e.id, attendanceDeduction: d.attendanceDeduction, cityLedger: d.cityLedger,
                staffShop: 0, advanceLoan: d.advanceLoan, pension: d.pension, ewt: d.ewt, other: d.other, total: total,
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveDeductions') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, DeductionData: deductionData, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                $('#cp-step-review').show();
                cpRenderReview();
            },
            error: function () { toastr.error('Could not save deductions.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    function cpRenderReview() {
        $('#cp-review-table tbody').html(cpEmployeesData.map(function (e) {
            const totalDeductions = cpDeductionTotal(cpDeductionsData[e.id]);
            const netSalary = e.normal_pay - totalDeductions;
            return `<tr data-emp-code="${e.id}">
                <td>${e.name}</td><td>${e.currency} ${e.normal_pay}</td><td>${e.currency} ${totalDeductions.toFixed(2)}</td>
                <td>${e.currency} ${netSalary.toFixed(2)}</td><td>${e.currency} ${e.service_provider_commission}</td>
            </tr>`;
        }).join(''));
    }

    $('#cp-save-review').on('click', function () {
        const reviewData = cpEmployeesData.map(function (e) {
            const totalDeductions = cpDeductionTotal(cpDeductionsData[e.id]);
            return {
                id: e.id,
                serviceCharge: 0, // §35 — Casual never gets service charge
                serviceProviderCommission: e.service_provider_commission,
                overtimeNormal: e.regular_ot_pay,
                overtimeFriday: e.friday_ot_pay,
                overtimeHoliday: e.holiday_ot_pay,
                overtimeTotal: e.total_ot_pay,
                earningsBasic: e.basic_salary,
                earnedSalary: e.earned_salary,
                earningsAllowance: 0,
                earningsNormal: e.normal_pay,
                totalDeductions: totalDeductions,
                allowances: [],
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveReviews') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, reviewData: reviewData, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
            },
            error: function () { toastr.error('Could not save review.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-lock-payroll').on('click', function () {
        if (!confirm('Lock this Casual payroll? This cannot be edited afterward.')) return;
        $.ajax({
            url: '{{ route('payroll.saveSummary') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                if (res.redirect_url) window.location.href = res.redirect_url;
            },
            error: function () { toastr.error('Could not lock payroll.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-send-approval').on('click', function () {
        $.ajax({
            url: '{{ route('payroll.send.approval') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message || 'Failed.', 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
            },
            error: function () { toastr.error('Could not send for approval.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });
</script>
@endsection
