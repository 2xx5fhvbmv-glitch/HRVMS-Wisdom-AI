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

        <div class="card" id="cpm-positions-card" style="{{ $paymentModel === 'direct_pay' ? '' : 'display:none;' }}">
            <div class="card-body">
                <h5 class="mb-1">Position pay — configured once, reused every month</h5>
                <p class="text-muted mb-3" style="font-size:13px;">Basic salary and service-provider commission can be in different currencies — the total only shows when both match.</p>
                <div class="table-responsive">
                    <table class="table table-collapse">
                        <thead>
                            <tr>
                                <th>Position</th>
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
                                @php $cfg = $pos->payConfig; @endphp
                                <tr data-position-id="{{ $pos->id }}">
                                    <td>{{ $pos->position_title }}</td>
                                    <td>{{ $pos->department->name ?? '' }}</td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm cpm-basic" value="{{ $cfg->basic_salary ?? 0 }}"></td>
                                    <td>
                                        <select class="form-select form-select-sm cpm-basic-currency">
                                            <option value="USD" {{ ($cfg->basic_salary_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="MVR" {{ ($cfg->basic_salary_currency ?? 'USD') === 'MVR' ? 'selected' : '' }}>MVR</option>
                                        </select>
                                    </td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm cpm-commission" value="{{ $cfg->commission_amount ?? 0 }}"></td>
                                    <td>
                                        <select class="form-select form-select-sm cpm-commission-currency">
                                            <option value="USD" {{ ($cfg->commission_currency ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="MVR" {{ ($cfg->commission_currency ?? 'USD') === 'MVR' ? 'selected' : '' }}>MVR</option>
                                        </select>
                                    </td>
                                    <td class="cpm-total text-muted">—</td>
                                    <td><button type="button" class="btn btn-sm wfp-btn-primary cpm-save-row">Save</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8">No Casual positions yet — create some first under Workforce Planning → Position Configuration.</td></tr>
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
            $('#cpm-positions-card').toggle($(this).val() === 'direct_pay');
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
    });
</script>
@endsection
