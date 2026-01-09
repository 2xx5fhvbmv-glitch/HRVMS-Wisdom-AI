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
                             
                            <input type="text" class="form-control datepicker" name="date" id="date">
                                          
                                             

                        </div>


                    </div>
                </div>
         
                <div class="table-responsive mb-md-4 mb-3">
                    <table class="table-lableNew  table-fileuncateDocView w-100" id="payment-request-tableIndex">
                        <thead>
                            
                            <tr>
                                <th>Payment Request ID</th>
                                <th>Payment Requested Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>

                    
                </div>
            </div>


        </div>
    </div>
    <div class="modal fade" id="PaymentRequestRejected-modal" tabindex="-1" aria-labelledby="quotaslot-modal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Rejection Reason </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="PaymentRequestRejectedForm" data-parsley-validate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="Payment_id" id="Payment_id" class="form-control" placeholder="Enter Employee ID">

                        <div class="row mb-3">
                            
                                <label class="form-label d-block">Rejection Reason<span class="red-mark">*</span></label>
                                    <div class="form-check form-check-inline">
                                        <textarea name="Reason" class="form-control" id="Reason" required data-parsley-required="true"
                                         data-parsley-required-message="Please enter a rejection reason"
                                        data-parsley-errors-container="#ReasonError"></textarea>
                                
                                    </div>
                          <div id="ReasonError"></div>  
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue FileUploadButton" href="javascript:void(0)">Submit</button>
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
$(document).ready(function(){
$("#PaymentRequestRejectedForm").parsley();
    $("#date").datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true,
        orientation: "bottom auto",
    });

    $("#date").on("change",function()
    {
        PaymentRequestTableIndex();
    });
    $(document).on("click",".PaymentRequestRejected",function(){

        var Payment_id = $(this).data('id');
        $("#Payment_id").val(Payment_id);
        $("#PaymentRequestRejected-modal").modal('show');
    });
   PaymentRequestTableIndex();

    

    $("#PaymentRequestRejectedForm").on('submit', function(e) {
        e.preventDefault();
        if ($("#PaymentRequestRejectedForm").parsley().isValid()) {
            $.ajax({
                type: "POST",
                url: "{{ route('resort.visa.PaymentRequestRejected') }}",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        toastr.success(response.msg, "success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#PaymentRequestRejected-modal').modal('hide');
                        PaymentRequestTableIndex();
                    } else {
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("An error occurred while processing your request.");
                }
            });
        }
    });

});


function PaymentRequestTableIndex() {


  
     if($.fn.DataTable.isDataTable('#payment-request-tableIndex'))
        {
            $('#payment-request-tableIndex').DataTable().destroy();
        }
       var productTable = $('#payment-request-tableIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: false,
            iDisplayLength: 15,
            processing: true,
            serverSide: true,
            order: [[4, 'desc']],
            ajax: {
                url: "{{ route('resort.visa.PaymentRequestIndex') }}",
                type: 'GET',
                data: function (d) {

                    d.date = $('#date').val();
           
                }
            },
            columns: [
                { data: 'PaymentRequestID', name: 'PaymentRequestID' },
                { data: 'PaymentRequestedDate', name: 'PaymentRequestedDate' },
                { data: 'Status', name: 'Status' },
                { data: 'Action', name: 'Action' },
                {data:'created_at', visible:false,searchable:false},
            ],
           
        }); 


   

    
}


</script>
@endsection