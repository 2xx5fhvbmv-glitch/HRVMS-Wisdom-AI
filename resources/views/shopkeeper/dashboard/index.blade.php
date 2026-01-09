@extends('shopkeeper.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <span>Welcome Back,</span>
                            <h1>{{$shopkeeper->name}}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto"><a href="{{route('shopkeeper.payment.add')}}" class="btn btn-theme">Add New Payment</a></div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4">
                <div class="col-xl-9 col-lg-8">
                    <div class="card card-serviceCharges">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Employees</h3>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <select class="form-select" id="month-filter">
                                            <option value="" selected>Select Month</option>
                                            <option value="01">January</option>
                                            <option value="02">February</option>
                                            <option value="03">March</option>
                                            <option value="04">April</option>
                                            <option value="05">May</option>
                                            <option value="06">June</option>
                                            <option value="07">July</option>
                                            <option value="08">August</option>
                                            <option value="09">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <select class="form-select" id="year-filter">
                                            <option value="" selected>Select Year</option>
                                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <a href="#" id="download-btn" class="btn btn-themeSkyblue btn-sm">Download</a>
                                </div>
                            </div>
                        </div> <!-- data-Table  -->
                        <table id="payment-table" class="table w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Emp ID</th>
                                    <th>Name</th>
                                    <th>Purchase Date </th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Status </th>
                                    <th>Action </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard mb-30">
                        <p class="fw-600 mb-0">Total Receivable Amount</p>
                        <strong id="total-receivable-amount">$0.00</strong>
                    </div>
                    <div class="card dashboard-boxcard timeAttend-boxcard  mb-30">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="fw-600 mb-0">Configuration</P>
                            <a href="{{route('shopkeeper.configuration')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                    <div class="card dashboard-boxcard timeAttend-boxcard  mb-30">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="fw-600 mb-0">Payment History</P>
                            <a href="{{route('shopkeeper.payment.history')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                    <div class="card dashboard-boxcard timeAttend-boxcard  mb-30">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="fw-600 mb-0">Products</P>
                            <a href="{{route('shopkeeper.products')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <!-- Deduction Modal -->
    <div class="modal fade" id="deduction-modal" tabindex="-1" aria-labelledby="deductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
        <form id="deductionForm">
            @csrf
            <div class="modal-header">
            <h5 class="modal-title" id="deductionModalLabel">Add Deduction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <!-- Hidden Payment ID -->
            <input type="hidden" name="paymentID" id="paymentID">
            <!-- Display Total Price (read-only) -->
            <div class="mb-3">
                <label for="totalPrice" class="form-label">Total Price</label>
                <input type="text" id="totalPrice" name="totalPrice" class="form-control" readonly>
            </div>
            <!-- Deduction Amount Input -->
            <div class="mb-3">
                <label for="deduction_amt" class="form-label">Deduction Amount</label>
                <input type="number" name="deduction_amt" id="deduction_amt" class="form-control" placeholder="Enter deduction amount" required>
            </div>
            
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-themeDanger">Apply Deduction</button>
            </div>
        </form>
        </div>
    </div>
    </div>

@endsection
    
@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        PaymentList();
        // When the month or year is changed, reload the DataTable
        $('#month-filter, #year-filter').on('change', function () {
            PaymentList(); // Reload DataTable
        });

        // Handle download button click
        $('#download-btn').on('click', function (e) {
            e.preventDefault(); // Prevent default link behavior

            var selectedMonth = $('#month-filter').val();  // Get selected month
            var selectedYear = $('#year-filter').val();    // Get selected year

            // Construct the download URL with filters
            var downloadUrl = "{{ route('dashboard.payment.download') }}?month=" + selectedMonth + "&year=" + selectedYear;

            // Redirect to download URL
            window.location.href = downloadUrl;
        });

    // When a deduction button is clicked
    $(document).on('click', '.deduct-now', function() {
        var paymentID = $(this).data('id');
        var price = $(this).data('amount');
        var cutoffDate = "{{ $cutoff_day ?? '1'}}"; // from your backend; ensure it's passed to the view
        var currentDate = new Date().toISOString().split('T')[0];

        // Check if deduction is allowed (assuming cutoffDate is just a day, or adjust logic accordingly)
        if (currentDate >= cutoffDate) {
            alert("Manual deduction is not allowed after the cutoff date.");
            return;
        }

        // Populate modal fields
        $('#paymentID').val(paymentID);
        $('#totalPrice').val(price);
        $('#deduction_amt').val('');
        // Optionally generate or update the QR code in #deductionQRContainer if needed

        // Show the modal (using Bootstrap 5)
        var deductionModal = new bootstrap.Modal(document.getElementById('deduction-modal'));
        deductionModal.show();
    });

    $(document).on('click', '.resend-consent', function() {
        var paymentID = $(this).data('id');
       
       $.ajax({
            url: "{{ route('shopkeeper.payment.sendConsent') }}",
            type: "POST",
            data: {
                paymentID: paymentID,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                toastr.error("An error occurred while resending consent.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // Handle form submission for deduction
    $('#deductionForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: "{{ route('payments.deduct') }}", // Your route for handling deduction
            type: "POST",
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    // Hide the modal
                    var deductionModalEl = document.getElementById('deduction-modal');
                    var modal = bootstrap.Modal.getInstance(deductionModalEl);
                    modal.hide();
                    // Optionally reload your DataTable or update UI
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                toastr.error("An error occurred while processing the deduction.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });
    });
    function PaymentList()
    {
        if ($.fn.DataTable.isDataTable('#payment-table'))
        {
            $('#payment-table').DataTable().destroy();
        }
        var selectedMonth = $('#month-filter').val(); // Get selected month
        var selectedYear = $('#year-filter').val(); // Get selected year
        var productTable = $('#payment-table').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order:[[9, 'desc']], 
            ajax: {
                url: "{{ route('dashboard.payment.list') }}",
                type: 'GET',
                data: function(d) {
                    d.month = selectedMonth;  // Send selected month
                    d.year = selectedYear;  // Send selected year
                },
                dataSrc: function(json) {
                    // Calculate total price from fetched data
                    let total = json.data.reduce((sum, payment) => sum + parseFloat(payment.price || 0), 0);
                    // Update total amount in the UI
                    $('#total-receivable-amount').text(`$${total.toFixed(2)}`);
                    return json.data;
                }
            },
            columns: [
                { data: 'order_id', name: 'order_id', className: 'text-nowrap' },
                { data: 'Emp_id', name: 'Emp_id', className: 'text-nowrap' },
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'purchased_date', name: 'purchased_date', className: 'text-nowrap' },
                { data: 'product', name: 'product', className: 'text-nowrap' },
                { data: 'quantity', name: 'quantity', className: 'text-nowrap' },
                { data: 'price', name: 'price', className: 'text-nowrap' },
                { data: 'status', name: 'status'},
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data: 'created_at', visible: false, searchable: false},
            ]
        });
    }
    
</script>
@endsection