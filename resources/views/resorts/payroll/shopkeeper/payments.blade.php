@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #shopkeeper-payments-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #shopkeeper-payments-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="shopkeeper-payments-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('shopkeepers.index') }}" class="btn payroll-btn-ghost btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to shopkeepers</a>
                </div>
            </div>
        </div>

        <div class="sk-sumbar">
            <div class="sk-sum-amt">
                <span class="sk-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20M6 15h4"/></svg></span>
                <div><div class="sk-lab">Total payable amount</div><div class="sk-big" id="total-payable-amount">{{ Common::GetResortCurrencySymbol() }} 0.00</div></div>
            </div>
            <div class="sk-legend">
                <span class="sk-lg"><span class="sk-dot" style="background:#2E7FB0"></span>Consented</span>
                <span class="sk-lg"><span class="sk-dot" style="background:#1F7A54"></span>Paid</span>
                <span class="sk-lg"><span class="sk-dot" style="background:#C8891F"></span>Partial paid</span>
            </div>
        </div>

        <div class="sk-card">
            <div class="sk-toolbar">
                <div class="sk-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" class="search" placeholder="Search">
                </div>
                <div class="form-group mb-0">
                    <select class="form-select dd-native-select" id="month-filter">
                        <option value="">All months ({{ date('Y') }})</option>
                        @php $months = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December']; @endphp
                        @foreach ($months as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#month-filter">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">All months ({{ date('Y') }})</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Month">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a month…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All months ({{ date('Y') }})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach ($months as $value => $label)
                                <div class="dd-item" role="option" data-value="{{ $value }}"><span class="dd-nm">{{ $label }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" id="payment-download-btn" class="sk-ghostbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>Download</button>
                <span class="sk-spacer-flex"></span>
                @if(!empty($canUpdatePaymentStatus))
                <label class="sk-chkall"><input type="checkbox" class="sk-chk" id="payment-check-all" aria-label="Check all">Check all</label>
                <button type="button" id="payment-bulk-paid-btn" class="sk-okbtn" disabled><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Mark selected as paid</button>
                @endif
            </div>

            <table id="payment-table" class="sk-tbl">
                <thead>
                    <tr>
                        @if(!empty($canUpdatePaymentStatus))
                        <th class="text-center" style="width: 40px;">Select</th>
                        @endif
                        <th>ID</th>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Purchase date</th>
                        <th>Product</th>
                        <th class="sk-num">Qty</th>
                        <th class="sk-num">Price</th>
                        <th>Currency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts.payroll.shopkeeper._shopkeeper_styles')
@endsection

@section('import-scripts')
<script type="text/javascript">
    var currencySymbol = "{{ Common::GetResortCurrencySymbol() }}";
    $(document).ready(function () {
        PaymentList();
        $(document).on('change', '#month-filter', function() {
            PaymentList();
        });
        $(document).on('keyup', '.search', function() {
            PaymentList();
        });
        $('#payment-download-btn').on('click', function() {
            var month = $('#month-filter').val() || '';
            var searchTerm = $('.search').val() || '';
            var url = "{{ route('resort.shopkeeper.payments.export', ['id' => $shopkeeper->id]) }}?month=" + encodeURIComponent(month) + "&search_term=" + encodeURIComponent(searchTerm);
            window.location.href = url;
        });
    });

    function PaymentList() {
        var month = $('#month-filter').val() || '';

        if ($.fn.DataTable.isDataTable('#payment-table')) {
            $('#payment-table').DataTable().destroy();
        }

        var columns = [
            { data: 'order_id', name: 'order_id', render: function (data, type) {
                if (type !== 'display') return data;
                var v = data == null ? '' : ('' + data);
                var esc = v.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                return '<span class="sk-idc" title="' + esc + '">' + esc + '</span>';
            } },
            { data: 'Emp_id', name: 'Emp_id', className: 'sk-empc' },
            { data: 'name', name: 'name' },
            { data: 'purchased_date', name: 'purchased_date' },
            { data: 'product', name: 'product' },
            { data: 'quantity', name: 'quantity', className: 'sk-num' },
            { data: 'price', name: 'price', className: 'sk-num' },
            { data: 'currency_type', name: 'currency_type' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
        ];
        @if(!empty($canUpdatePaymentStatus))
        columns.unshift({ data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' });
        @endif

        $('#payment-table').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            // scrollX removed: it split this table into an independently-
            // sized header clone (.dt-scroll-head) + body (.dt-scroll-body),
            // whose column widths drift a few px apart from each other with
            // no explicit per-column widths defined — that's what caused
            // Purchase date / Emp ID's header text to sit at a different
            // horizontal position than its own column's data. A single
            // un-split table has one <thead>/<tbody> sharing exact column
            // boundaries, so that goes away; it also removed the need for a
            // wrapping div, matching the plain .sk-tbl used on the View-all
            // screen (same table, same reasoning).
            pageLength: 15,
            processing: true,
            serverSide: true,
            order: [[ @if(!empty($canUpdatePaymentStatus)) 1 @else 0 @endif , 'desc']],
            ajax: {
                url: "{{ route('resort.shopkeeper.payments.list', ['id' => $shopkeeper->id]) }}",
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('.search').val();
                    d.month = month;
                },
                dataSrc: function (json) {
                    var total = (json.total_amount != null) ? parseFloat(json.total_amount) : json.data.reduce(function (sum, p) { return sum + parseFloat(p.price || 0); }, 0);
                    $('#total-payable-amount').text(formatAmount(total, 'USD'));
                    return json.data;
                }
            },
            columns: columns
        });
    }

    @if(!empty($canUpdatePaymentStatus))
    var $skMarkPaidBtn = $('#payment-bulk-paid-btn');
    var skMarkPaidLabel = $skMarkPaidBtn.text().trim(); // "Mark selected as paid", set once from the server-rendered button

    function skSyncPaymentControls() {
        var total = $('#payment-table').DataTable().$('.payment-row-checkbox').length;
        var checked = $('#payment-table').DataTable().$('.payment-row-checkbox:checked').length;
        $('#payment-check-all').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
        $skMarkPaidBtn.prop('disabled', checked === 0);
        $skMarkPaidBtn.contents().filter(function () { return this.nodeType === 3; }).last()[0].nodeValue =
            checked > 0 ? (' ' + skMarkPaidLabel + ' (' + checked + ')') : (' ' + skMarkPaidLabel);
    }

    $(document).on('change', '#payment-check-all', function() {
        var checked = $(this).prop('checked');
        $('#payment-table').DataTable().$('.payment-row-checkbox').each(function() {
            $(this).prop('checked', checked);
        });
        skSyncPaymentControls();
    });

    $(document).on('change', '.payment-row-checkbox', function() {
        skSyncPaymentControls();
    });

    $(document).on('click', '#payment-bulk-paid-btn', function() {
        var ids = [];
        $('#payment-table').DataTable().$('.payment-row-checkbox:checked').each(function() {
            var id = $(this).data('payment-id');
            if (id) ids.push(id);
        });
        if (ids.length === 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Please select at least one payment.', "Notice", { positionClass: 'toast-bottom-right' });
            } else {
                alert('Please select at least one payment.');
            }
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('resort.shopkeeper.payments.bulkUpdateStatus') }}",
            type: "POST",
            data: {
                payment_ids: ids,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    } else {
                        alert(response.message);
                    }
                    $('#payment-check-all').prop('checked', false).prop('indeterminate', false);
                    PaymentList();
                    skSyncPaymentControls();
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'Update failed', "Error", { positionClass: 'toast-bottom-right' });
                    } else {
                        alert(response.message || 'Update failed');
                    }
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update payments.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                } else {
                    alert(msg);
                }
            },
            complete: function() {
                // Re-enable relative to the current selection, not
                // unconditionally — a successful update already cleared
                // the selection and disabled the button on purpose.
                skSyncPaymentControls();
            }
        });
    });
    @endif
</script>
@include('resorts._dropdown_script')
@endsection
