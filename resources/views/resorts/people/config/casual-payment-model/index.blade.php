@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>PEOPLE</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('people.config') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Payment model</h5>
                <p class="text-muted" style="font-size:13px;">Two models exist industry-wide — pick the one this resort uses.</p>
                <div class="d-flex flex-column gap-2">
                    <label class="d-flex align-items-center gap-2">
                        <input type="radio" name="casual_payment_model" value="lump_sum" id="cpm-lump-sum" {{ $paymentModel === 'lump_sum' ? 'checked' : '' }}>
                        Paid via service provider (lump sum) — Casual staff are never in this app's payroll; only attendance/leave/OT are tracked.
                    </label>
                    <label class="d-flex align-items-center gap-2">
                        <input type="radio" name="casual_payment_model" value="direct_pay" id="cpm-direct-pay" {{ $paymentModel === 'direct_pay' ? 'checked' : '' }}>
                        Paid directly, plus a separate admin fee to the service provider — the Casual Payroll run below applies.
                    </label>
                </div>
            </div>
        </div>

        {{-- WP3 (D3) — always visible regardless of the payment model above:
             Interns are always paid through the Permanent payroll run
             reading THIS rate (never employees.basic_salary), independent
             of whether this resort pays Casuals directly or via lump sum.
             Commission only applies to Casual (no service provider for
             Interns). --}}
        <div class="card mb-4" id="cpm-positions-card">
            <div class="card-body">
                <h5 class="mb-1">Position pay — configured once, reused every month</h5>
                <p class="text-muted mb-3" style="font-size:13px;">Basic salary and service-provider commission can be in different currencies — the total only shows when both match. Commission doesn't apply to Interns.</p>
                <div class="table-responsive">
                    <table class="table table-collapse">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Category</th>
                                <th>Department</th>
                                <th>Basic Salary</th>
                                <th>Currency</th>
                                <th>Provider Commission</th>
                                <th>Currency</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($positions as $pos)
                                @php $cfg = $pos->payConfig; $isCasual = $pos->employee_category === 'Casual'; @endphp
                                <tr data-position-id="{{ $pos->id }}">
                                    <td>{{ $pos->position_title }}</td>
                                    <td>{{ $pos->employee_category }}</td>
                                    <td>{{ $pos->department->name ?? '' }}</td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm cpm-basic" value="{{ $cfg->basic_salary ?? 0 }}"></td>
                                    <td>
                                        <select class="form-select form-select-sm cpm-basic-currency">
                                            <option value="USD" {{ ($cfg->basic_salary_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="MVR" {{ ($cfg->basic_salary_currency ?? 'USD') === 'MVR' ? 'selected' : '' }}>MVR</option>
                                        </select>
                                    </td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm cpm-commission" value="{{ $cfg->commission_amount ?? 0 }}" {{ $isCasual ? '' : 'disabled' }}></td>
                                    <td>
                                        <select class="form-select form-select-sm cpm-commission-currency" {{ $isCasual ? '' : 'disabled' }}>
                                            <option value="USD" {{ ($cfg->commission_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="MVR" {{ ($cfg->commission_currency ?? 'USD') === 'MVR' ? 'selected' : '' }}>MVR</option>
                                        </select>
                                    </td>
                                    <td class="cpm-total text-muted">—</td>
                                    <td>
                                        <button type="button" class="btn btn-sm wfp-btn-primary cpm-save-row">Save</button>
                                        <button type="button" class="btn btn-sm wfp-btn-secondary cpm-toggle-people" data-position-id="{{ $pos->id }}">People</button>
                                    </td>
                                </tr>
                                <tr class="cpm-people-row" data-position-id="{{ $pos->id }}" style="display:none;">
                                    <td colspan="9">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Name</th><th>Custom Salary</th><th>Currency</th><th></th></tr></thead>
                                            <tbody>
                                                @forelse ($pos->employees as $emp)
                                                    @php $override = $emp->payOverride; @endphp
                                                    <tr data-employee-id="{{ $emp->id }}">
                                                        <td>{{ trim(($emp->resortAdmin->first_name ?? '') . ' ' . ($emp->resortAdmin->last_name ?? '')) }}
                                                            @if($override)<span class="badge bg-info">custom</span>@endif
                                                        </td>
                                                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm cpm-emp-basic" value="{{ $override->basic_salary ?? '' }}" placeholder="position rate"></td>
                                                        <td>
                                                            <select class="form-select form-select-sm cpm-emp-currency">
                                                                <option value="USD" {{ ($override->basic_salary_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                                                <option value="MVR" {{ ($override->basic_salary_currency ?? 'USD') === 'MVR' ? 'selected' : '' }}>MVR</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm wfp-btn-primary cpm-save-emp">Save</button>
                                                            <button type="button" class="btn btn-sm wfp-btn-neutral cpm-clear-emp" {{ $override ? '' : 'disabled' }}>Use position rate</button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-muted">No one in this position yet.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9">No Casual/Intern positions yet — create some first under Workforce Planning → Position Configuration.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
    function cpmUpdateTotal(row) {
        const basic = parseFloat(row.find('.cpm-basic').val()) || 0;
        const commission = parseFloat(row.find('.cpm-commission').val()) || 0;
        const basicCur = row.find('.cpm-basic-currency').val();
        const commCur = row.find('.cpm-commission-currency').val();
        const totalCell = row.find('.cpm-total');
        if (basicCur === commCur) {
            totalCell.removeClass('text-muted').text(basicCur + ' ' + (basic + commission).toFixed(2));
        } else {
            totalCell.addClass('text-muted').html(basicCur + ' ' + basic.toFixed(2) + '<br>' + commCur + ' ' + commission.toFixed(2));
        }
    }

    $(function () {
        $('#cpm-positions-card tbody tr[data-position-id]').each(function () { cpmUpdateTotal($(this)); });

        $('#cpm-positions-card').on('input change', '.cpm-basic, .cpm-commission, .cpm-basic-currency, .cpm-commission-currency', function () {
            cpmUpdateTotal($(this).closest('tr'));
        });

        $('input[name="casual_payment_model"]').on('change', function () {
            $.ajax({
                url: '{{ route('people.casualPaymentModel.storeModel') }}',
                type: 'POST',
                data: { casual_payment_model: $(this).val(), _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                },
                error: function () {
                    toastr.error('Could not save payment model.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#cpm-positions-card').on('click', '.cpm-save-row', function () {
            const row = $(this).closest('tr');
            $.ajax({
                url: '{{ route('people.casualPaymentModel.storePositionPay') }}',
                type: 'POST',
                data: {
                    position_id: row.data('position-id'),
                    basic_salary: row.find('.cpm-basic').val(),
                    basic_salary_currency: row.find('.cpm-basic-currency').val(),
                    commission_amount: row.find('.cpm-commission').val(),
                    commission_currency: row.find('.cpm-commission-currency').val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#cpm-positions-card').on('click', '.cpm-toggle-people', function () {
            $('.cpm-people-row[data-position-id="' + $(this).data('position-id') + '"]').toggle();
        });

        $('#cpm-positions-card').on('click', '.cpm-save-emp', function () {
            const row = $(this).closest('tr');
            $.ajax({
                url: '{{ route('people.casualPaymentModel.storeEmployeePay') }}',
                type: 'POST',
                data: {
                    employee_id: row.data('employee-id'),
                    basic_salary: row.find('.cpm-emp-basic').val(),
                    basic_salary_currency: row.find('.cpm-emp-currency').val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    row.find('.cpm-clear-emp').prop('disabled', false);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#cpm-positions-card').on('click', '.cpm-clear-emp', function () {
            const row = $(this).closest('tr');
            const clearBtn = $(this);
            $.ajax({
                url: '{{ route('people.casualPaymentModel.destroyEmployeePay') }}',
                type: 'DELETE',
                data: { employee_id: row.data('employee-id'), _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    row.find('.cpm-emp-basic').val('');
                    clearBtn.prop('disabled', true);
                },
                error: function () {
                    toastr.error('Could not clear custom salary.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection
