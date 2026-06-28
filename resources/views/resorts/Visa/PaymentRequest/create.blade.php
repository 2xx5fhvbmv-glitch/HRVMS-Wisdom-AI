@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
 <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Visa Management</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 m-0">
                    <div class="row g-md-3 g-2 ">
                        <div class="col-lg-auto  order-last order-xxl-2">
                            <label class="form-label">PAYMENT TYPE</label>
                            <div class="row mt-2">
                                 <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="all"   type="checkbox" id="work-permit-check"
                                            value="Status1" >
                                        <label class="form-check-label text-nowrap" for="work-permit-check">All</label>
                                    </div>
                                </div>
                                 <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="work_permit" type="checkbox" id="work-permit-check"
                                            value="Status1" >
                                        <label class="form-check-label text-nowrap" for="work-permit-check">Work
                                            Permit</label>
                                    </div>
                                </div>
                                <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="slot_payment"   type="checkbox" id="slot-fee-check"
                                            value="Status1" >
                                        <label class="form-check-label" for="slot-fee-check">Slot Fee</label>
                                    </div>
                                </div>
                                {{-- <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="visa" type="checkbox" id="work-permit-check"
                                            value="Status1" >
                                        <label class="form-check-label text-nowrap" for="work-permit-check">Visa</label>
                                    </div>
                                </div> --}}
                                <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="insurance" type="checkbox" id="insurance-check"
                                            value="Status1" >
                                        <label class="form-check-label" for="insurance-check">Insurance</label>
                                    </div>
                                </div>
                                <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input PaymentType" data-flag="MedicalReport" type="checkbox" id="medical-check"
                                            value="Status1" >
                                        <label class="form-check-label text-nowrap" for="medical-check">Medical (work
                                            permit)</label>
                                    </div>
                                </div>
                                <!-- <div class="col-auto ">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="passport-check"
                                            value="Status1" >
                                        <label class="form-check-label" for="passport-check">Passport</label>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <div class="col text-end order-md-2 order-1 order-xxl-last">
                            <span class=" Overall-tot-amount text-nowrap">Total amount: MVR 0.00</span>
                        </div>

                    </div>
                </div>
                <div class="card-title border-0">
                    <h3>Select Employee</h3>
                </div>
                <div class="table-responsive mb-md-4 mb-3">
                    <table class="table-lableNew  table-fileuncateDocView w-100" id="payment-request-table">
                        <thead>
                            
                            <tr>
                                <th>
                                    <div class="form-check no-label">
                                        <input class=" AllCheck" name="employee_ids[]" type="checkbox" id="select-all" value="" >
                                    </div>
                                   
                                </th>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                {{-- Visa Expiry column commented out per request --}}
                                {{-- <th class="d-none">Visa Expiry</th> --}}
                                <th>Work Permit</th>
                                <th>Slot Fee</th>
                                <th>Insurance</th>
                                <th>Medical (work permit)</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                {{-- Visa Expiry total cell commented out per request --}}
                                {{-- <th class="d-none">MVR 0.00</th> --}}
                                <th>MVR 0.00</th>
                                <th>MVR 0.00</th>
                                <th>MVR 0.00</th>
                                <th>MVR 0.00</th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="bg-themeGrayLight emp-select">
                        <div class="row g-3">
                            <div class="col-auto">
                                <p class="fw-600" id="selectedCount">0 Employees Selected</p>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="a-linkTheme" id="unselectAll">Unselect All</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <!-- <a href="#" class="a-link">Save as Draft</a> -->
                    <button type="button" class="btn btn-themeBlue btn-sm SubmitEmployee">Submit</button>
                </div>
            </div>


        </div>
    </div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){

    $('.PaymentType').on('change', function() {
    
        var flag = $(this).data('flag');
        if (flag === 'all') {
            // If "all" checkbox is checked/unchecked, set all other checkboxes to match
            var isChecked = $(this).is(':checked');
            $('.PaymentType').not(this).prop('checked', isChecked);
        } else {
            // If any other checkbox is checked, uncheck the "all" checkbox
            if ($(this).is(':checked')) {
                $('.PaymentType[data-flag="all"]').prop('checked', false);
            } else if ($('.PaymentType:checked').not('[data-flag="all"]').length === 0) {
                // If no other checkboxes are checked, check the "all" checkbox
                $('.PaymentType[data-flag="all"]').prop('checked', true);
            }
        }
        PaymentRequestTable();
    });

    $(".AllCheck").on('click', function() {
      
        var isChecked = $(this).is(':checked');
        $('#payment-request-table tbody input[type="checkbox"]').prop('checked', isChecked);
        PaymentRequestTable();
    });

     $('#unselectAll').on('click', function(e){
        $(".AllCheck").prop('checked', false);
        $('#payment-request-table tbody input[type="checkbox"]').prop('checked', false);
       PaymentRequestTable();

    });
    PaymentRequestTable();


    $(".SubmitEmployee").on("click", function(){

        var selectedEmployees = [];
        // months[encodedEmployeeId] = { wp: N, slot: M } — how many upcoming
        // months of Work Permit / Slot fees HR chose to pay for that employee.
        var months = {};
        $('#payment-request-table tbody input.ChildCheck:checked').each(function() {

            selectedEmployees.push($(this).val());

            var emp = $(this).data('emp');
            if (emp) {
                var $row = $(this).closest('tr');
                months[emp] = {
                    wp:   parseInt($row.find('.fee-months[data-fee="wp"]').val(), 10)   || 1,
                    slot: parseInt($row.find('.fee-months[data-fee="slot"]').val(), 10) || 1
                };
            }
        });

        if (selectedEmployees.length === 0)
        {
            toastr.error("Please select at least one employee", "Error", {
                            positionClass: 'toast-bottom-right'
                        });

        }

        $.ajax({
            url: "{{ route('resort.visa.PaymentRequestSubmit') }}",
            type: 'POST',
            data: {
                employee_ids: selectedEmployees,
                months: months,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                   
                     toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                         window.location.href = response.redirect;
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr, status, error) {
                toster.error("An error occurred while processing your request.");
            }
        });

      
    });

    $(document).on("click",".ChildCheck", function() {

        var selectedEmployees = [];
        $('#payment-request-table tbody input.ChildCheck:checked').each(function() {
            selectedEmployees.push($(this).val());
        });
        $("#selectedCount").html(selectedEmployees.length + ' Employees Selected');
        updateTopTotal();
    });

    // Multi-month Work Permit / Slot fee: when HR changes the "Months" count, the
    // fee cell shows the sum of the next N upcoming unpaid dues, and the row +
    // top totals update to match.
    $(document).on("input change", ".fee-months", function () {
        var $input = $(this);
        var dues = $input.data('dues') || [];
        var n = parseInt($input.val(), 10) || 1;
        if (n < 1) { n = 1; $input.val(1); }
        if (n > dues.length) { n = dues.length; $input.val(dues.length); }
        var sum = 0;
        for (var i = 0; i < n && i < dues.length; i++) { sum += parseFloat(dues[i].amt) || 0; }
        var $cell = $input.closest('.fee-cell');
        $cell.find('.fee-amt').first().text('MVR ' + sum.toFixed(2));
        var note = n > 1
            ? 'Paying ' + n + ' months in advance (through ' + feeThroughDate(dues, n) + ')'
            : 'Paying current month only';
        $cell.find('.fee-months-note').text(note);
        recomputeRowTotal($input.closest('tr'));
        updateTopTotal();
    });
});

// Coverage-end date when paying N months: the next unpaid due (dues[n]) — the
// due one month after the last month paid. e.g. dues = Dec, Jan, Feb… and you
// pay 2 (Dec+Jan) → covered "through" Feb's due date. Falls back to last-paid +
// 1 month when the schedule doesn't extend that far. Returned as "dd Mon yyyy".
function feeMonthNames() {
    return ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
}
function feeIsoToDMY(iso) {
    if (!iso) return '';
    var p = String(iso).split('-');
    if (p.length < 3) return iso;
    var mon = feeMonthNames()[parseInt(p[1], 10) - 1] || '';
    return p[2] + ' ' + mon + ' ' + p[0];
}
function feeThroughDate(dues, n) {
    if (dues[n] && dues[n].date) return feeIsoToDMY(dues[n].date);
    var last = dues[n - 1] && dues[n - 1].date;
    if (!last) return '';
    var p = String(last).split('-');
    var dt = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    dt.setMonth(dt.getMonth() + 1);
    return ('0' + dt.getDate()).slice(-2) + ' ' + feeMonthNames()[dt.getMonth()] + ' ' + dt.getFullYear();
}

// Row data-total = sum of every fee amount shown in the row, so the live top
// total reflects multi-month selections.
function recomputeRowTotal($row) {
    var total = 0;
    $row.find('.fee-amt').each(function () {
        total += parseFloat(($(this).text() || '').replace(/[^0-9.]/g, '')) || 0;
    });
    $row.find('input.ChildCheck').attr('data-total', total.toFixed(2)).data('total', total);
}


// Live total on top = sum of the SELECTED employees' fees (data-total is the
// per-employee amount in the resort display currency). 0 when nothing selected.
function updateTopTotal() {
    var total = 0;
    $('#payment-request-table tbody input.ChildCheck:checked').each(function () {
        total += parseFloat($(this).data('total')) || 0;
    });
    $('.Overall-tot-amount').html('<b>MVR ' + total.toFixed(2) + '</b>');
}

function PaymentRequestTable() {


     let isChecked = $("#select-all").is(":checked");
     
   
     if($.fn.DataTable.isDataTable('#payment-request-table'))
        {
            $('#payment-request-table').DataTable().destroy();
        }
       var productTable = $('#payment-request-table').DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: false,
            iDisplayLength: 15,
            processing: true,
            serverSide: true,
            order:[[9, 'desc']], {{-- created_at column (index shifted from 10 -> 9 after the Visa Expiry column was removed) --}}
            ajax: {
                url: "{{ route('resort.visa.PaymentRequest') }}",
                type: 'GET',
                data: function (d) {

                    let flags = $('.PaymentType:checked').map(function () {
                                return $(this).data('flag');
                            }).get();
                    d.flag = flags.length ? flags : [];
                    d.search = $('.search').val();
                   d.isChecked = isChecked;
                }
            },
            columns: [
                { data: 'CheckBox', name: 'CheckBox' , orderable: false, searchable: false },
                { data: 'EmployeeID', name: 'EmployeeID' },
                { data: 'EmployeeName', name: 'EmployeeName' },
                { data: 'Position', name: 'Position' },
                { data: 'Department', name: 'Department' },
                // { data: 'VisaExpiry', name: 'VisaExpiry', visible: false }, // Visa Expiry column commented out per request
                { data: 'WorkPermit', name: 'WorkPermit' },
                { data: 'SlotFees', name: 'SlotFees' },
                { data: 'Insurance', name: 'Insurance' },
                { data: 'Medical', name: 'Medical' },
                {data:'created_at', visible:false,searchable:false},
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                // api.ajax.json() is undefined if the AJAX failed (e.g. server
                // error / DB down) — guard it so the footer fails gracefully
                // instead of throwing "Cannot read properties of undefined".
                var resJson = api.ajax.json();
                if (resJson && resJson.totals) {
                    const totals = resJson.totals;
                    // Visa Expiry column removed. column(5) = WorkPermit, column(6) = SlotFees, column(7) = Insurance, column(8) = Medical
                    $(api.column(5).footer()).html('<b>' + totals.work_permit + '</b>');
                    $(api.column(6).footer()).html('<b>' + totals.slot_payment + '</b>');
                    $(api.column(7).footer()).html('<b>' + totals.insurance + '</b>');
                    $(api.column(8).footer()).html('<b>' + totals.medical + '</b>');
                // Top total reflects the SELECTED employees, not every shown row.
                updateTopTotal();
                $("#selectedCount").html($('#payment-request-table tbody input.ChildCheck:checked').length + ' Employees Selected');
                } else {
                    $(api.column(5).footer()).html('<b>0</b>');
                    $(api.column(6).footer()).html('<b>0</b>');
                    $(api.column(7).footer()).html('<b>0</b>');
                    $(api.column(8).footer()).html('<b>0</b>');
                    $('.Overall-tot-amount').html('<b>Total Amount: MVR 0</b>');
                }
            }
        }); 


   

    
}


</script>
@endsection