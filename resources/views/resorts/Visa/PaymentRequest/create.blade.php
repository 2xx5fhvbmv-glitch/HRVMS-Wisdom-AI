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
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-12 order-md-1 order-2 ">
                            <label class="form-label">DURATION</label>
                                <div class="dateRangeAb"  id="datapicker">
                                                <div>
                                                    <!-- Hidden input field to attach the calendar to -->
                                                    <input type="text" class="form-control" name="hiddenInput" id="hiddenInput">
                                                </div>
                                                <p id="startDate" class="d-none">Start Date:</p>
                                                <p id="endDate" class="d-none">End Date:</p>
                                            </div>

                        </div>

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
                            <span class=" Overall-tot-amount text-nowrap">Total amount: {{ Common::GetResortCurrencySymbol() }} 0.00</span>
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
                                <th>{{ Common::GetResortCurrencySymbol() }} 0.00</th>
                                <th>{{ Common::GetResortCurrencySymbol() }} 0.00</th>
                                <th>{{ Common::GetResortCurrencySymbol() }} 0.00</th>
                                <th>{{ Common::GetResortCurrencySymbol() }} 0.00</th>
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

    $("#hiddenInput").daterangepicker({
        autoApply: true,
        startDate: moment(),
        endDate: moment().add(7, 'days'),
        opens: 'right',
        parentEl: '#datapicker',
        alwaysShowCalendars: true,
        linkedCalendars: false,
        locale: {
            format: "DD-MM-YYYY", // Ensure the format matches your date parsing logic
        }
    });

    $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
        $("#startDate").text("Start Date: " + picker.startDate.format("DD-MM-YYYY"));
        $("#endDate").text("End Date: " + picker.endDate.format("DD-MM-YYYY"));
    });

    $("#hiddenInput").on("change", function() {
        var date = $(this).val();
        PaymentRequestTable();
    });
    
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
        $('#payment-request-table tbody input[type="checkbox"]:checked').each(function() {

         
            selectedEmployees.push($(this).val());
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
        $('#payment-request-table tbody input[type="checkbox"]:checked').each(function() {
            selectedEmployees.push($(this).val());
        });
        $("#selectedCount").html(selectedEmployees.length + ' Employees Selected');

    });
});


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
                    d.date = $('#hiddenInput').val();
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
                $('.Overall-tot-amount').html('<b>' + totals.overall + '</b>');
                $("#selectedCount").html(totals.totalChecked + ' Employees Selected');
                } else {
                    $(api.column(5).footer()).html('<b>0</b>');
                    $(api.column(6).footer()).html('<b>0</b>');
                    $(api.column(7).footer()).html('<b>0</b>');
                    $(api.column(8).footer()).html('<b>0</b>');
                    $('.Overall-tot-amount').html('<b>Total Amount: {{ Common::GetResortCurrencySymbol() }} 0</b>');
                }
            }
        }); 


   

    
}


</script>
@endsection