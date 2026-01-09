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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>VISA MANAGEMENT</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme" disabled="disabled">Sent To Finance</a>
                            <a href="#revise-budgetmodal" data-bs-toggle="modal" class="btn btn-white ms-3">Revise
                                Budget</a>
                        </div>
                    </div> -->
                </div>
            </div>

            <div>
                <form  id="DepositRefundForm" data-parsley-validate>
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <div class="row g-3 justify-content-between align-items-center">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="card-title border-0 m-0 p-0">
                                            <h3>Have you applied for the deposit refund?</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-sm-4">
                                    <div class="input-group">
                                        <input type="search" class="form-control search" placeholder="Search">
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="DepositRefundTable" >
                            @if($EmployeeResignation->isNotEmpty())
                                @foreach($EmployeeResignation as $resignation)
                                    <div class="PayReq-Details-box">
                                        <div class="d-sm-flex justify-content-between ">
                                            <div class=" d-flex align-items-center">
                                                <div class="img-circle"><img src="{{$resignation['profile_pic']}}" alt="user">
                                                </div>
                                                <div>
                                                    <h6>{{$resignation['employee_name']}}<span class="badge badge-themeNew">{{$resignation['Emp_id']}}</span> </h6>
                                                    <p>{{$resignation['department']}} , {{$resignation['position']}} </p>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mt-sm-0 mt-3 justify-content-end">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input toggle-checkbox Paymentcheck" type="checkbox" data-id="{{$resignation['id']}}" id="Paymentcheck_{{$resignation['id']}}-yes-check"
                                                        value="Status1" >
                                                    <label class="form-check-label text-nowrap" for="check">Yes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input toggle-checkbox PaymentcheckCancle" type="checkbox" data-id="{{$resignation['id']}}"  id="no-check-{{$resignation['id']}}"
                                                        value="Status1">
                                                    <label class="form-check-label text-nowrap" for="check">No</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border-top d-none" id="Toggle-{{$resignation['id']}}">
                                            <div class="row gx-md-3 g-2">

                                            @if($VisaWallets->isNotEmpty())
                                                @foreach($VisaWallets as $wallet)
                                                    <div class="col-lg-4 col-sm-6">
                                                        <div class="DepRefReq-checkbox d-flex align-items-center justify-content-between">
                                                            <div>
                                                                <p>{{ $wallet->WalletName }}</p>
                                                                <span>Current Balance: MVR {{ $wallet->Amt }}</span>
                                                            </div>
                                                            <div class="form-check form-check-inline p-0 me-0">
                                                                <input 
                                                                    class="form-check-input" 
                                                                    type="radio" 
                                                                    name="wallet_option[{{ $wallet->id}}][{{$resignation['employee_id'] }}]" {{-- Grouping only by employee --}}
                                                                    id="wallet-radio-{{ $resignation['employee_id'] }}-{{ $loop->index }}" 
                                                                    value="{{ $wallet->Amt }}"
                                                                >
                                                                <label class="form-check-label" for="wallet-radio-{{ $resignation['employee_id'] }}-{{ $loop->index }}"></label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                            
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            @endif
                        
                        </div>

                        <div class="card-footer mt-3">

                            <!-- <a href="#" class=" btn btn-themeBlue btn-sm float-end next ">Send To Finance</a> -->

                            <button class=" btn btn-themeBlue btn-sm float-end  ">Approved </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
 $(document).ready(function () {
    $(document).on("click", ".Paymentcheck", function () 
    {
        let empId = $(this).data("id"); 
        $("#no-check-"+empId).prop("checked", false);

        $("#Toggle-"+empId).toggleClass("d-none");
    });
    $(document).on("click", ".PaymentcheckCancle", function () 
    {
        let empId = $(this).data("id"); 
        $("#Toggle-"+empId).find('input[type="radio"]').prop("checked", false);
        $("#Paymentcheck_"+empId+"-yes-check").prop("checked", false);
        
        $("#Toggle-"+empId).toggleClass("d-none");
    });

    $("#DepositRefundForm").parsley();

    $("#DepositRefundForm").on("submit", function (e) {
        e.preventDefault();
        if ($(this).parsley().isValid()) {
            // Handle form submission
            let formData = $(this).serialize();
            
            $.ajax({
                    url: "{{ route('visa.deposit.refund.store') }}",
                    type: "POST",
                    data: formData,
                    success: function (response) {
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $("#DepositRefundTable").html(response.html); // Update the table with new data
                    },
                    error: function (xhr, status, error) {
                        let errorMsg = "An unexpected error occurred.";

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.msg && xhr.responseJSON.errors) {
                                // Build detailed message from errors
                                let details = Object.values(xhr.responseJSON.errors)
                                                    .flat()
                                                    .join("<br>");
                                errorMsg = xhr.responseJSON.msg + "<br>" + details;
                            } else if (xhr.responseJSON.msg) {
                                // Just the main message
                                errorMsg = xhr.responseJSON.msg;
                            } else if (xhr.responseJSON.errors) {
                                // Only validation errors
                                errorMsg = Object.values(xhr.responseJSON.errors)
                                                .flat()
                                                .join("<br>");
                            }
                        }

                        toastr.error(errorMsg, "Error", {
                            positionClass: 'toast-bottom-right',
                            timeOut: 5000
                        });
                    }
                });


        }
    });
    

    $(".search").on("keyup", function () {
        let search = $(this).val().toLowerCase();
       
            $.ajax({
                    url: "{{ route('visa.deposit.refund.search') }}",
                    type: "POST",
                    data: {"search":search, "_token": "{{ csrf_token() }}"},
                    success: function (response) 
                    {
                        $("#DepositRefundTable").html(response.html); // Update the table with new data
                    },
                    error: function (xhr, status, error) {
                        let errorMsg = "An unexpected error occurred.";

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.msg && xhr.responseJSON.errors) {
                                // Build detailed message from errors
                                let details = Object.values(xhr.responseJSON.errors)
                                                    .flat()
                                                    .join("<br>");
                                errorMsg = xhr.responseJSON.msg + "<br>" + details;
                            } else if (xhr.responseJSON.msg) {
                                // Just the main message
                                errorMsg = xhr.responseJSON.msg;
                            } else if (xhr.responseJSON.errors) {
                                // Only validation errors
                                errorMsg = Object.values(xhr.responseJSON.errors)
                                                .flat()
                                                .join("<br>");
                            }
                        }

                        toastr.error(errorMsg, "Error", {
                            positionClass: 'toast-bottom-right',
                            timeOut: 5000
                        });
                    }
                });
        
    });
    
});


</script>
@endsection