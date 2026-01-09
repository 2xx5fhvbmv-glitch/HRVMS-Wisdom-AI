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
                <div class="card-header border-0 pb-0">


                    <div class="row g-md-3 g-2 align-items-center justify-content-between">
                        <div class="col-auto">
                            <p>Please verify the details extracted from the uploaded screenshot(s) below</p>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6  ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="exp-Date-userbox expiry-dat-box">
                        <div class="row align-items-lg-center">
                               <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="Quota_Slot_Fee" role="tabpanel" aria-labelledby="tab1">
                                        <table id="ExpiryIndex" class="table">
                                        
                                        </table>
                                    </div>
                                </div>
                        </div>
                    </div>

                   
                  

                </div>
                <div class="card-footer mt-3">
                    <!-- <a href="#" class=" btn btn-themeBlue btn-sm float-end next ">Submit</a> -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ExpiryUpdate-modal" tabindex="-1" aria-labelledby="quotaslot-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Payment Type </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="VisaQuotaslot" data-parsley-validate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="emp_id" id="QuotaSlot_emp_id" class="form-control" placeholder="Enter Employee ID">
                        <input type="hidden" name="flag" id="QuotaSlot_flag" class="form-control" placeholder="Enter Flag">

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label d-block">Payment Type<span class="red-mark">*</span></label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_lumpsum" class="form-check-input"
                                        value="Lumpsum" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_lumpsum">Lumpsum</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_installment" class="form-check-input"
                                        value="Installment" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_installment">Installment</label>
                                </div>
                                <div id="payment_type_error" class="text-danger"></div>
                            </div>
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

$(document).ready(function() 
{
    FetchIndexDate();
    $("#datepickerXpact").datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    $(document).on('click', '.Categories', function(e) {
        // Remove 'active' from all

        $('.Categories').removeClass('active');

        // Add 'active' to clicked one
        $(this).addClass('active');

        // Fetch flag and call function
        var flag = $(this).data('flag');
        FetchIndexDate(flag);
    });
    $(document).on('keyup', '.Search', function() 
    {
        var flag = 'all';
        FetchIndexDate(flag);
    });

    $(document).on('click',".EditvisaDate",function(e)
    {
        e.preventDefault();
        var id = $(this).data('id');
        $("#ExpiryUpdate-modal").modal('show');
    });
    

});

function FetchIndexDate(flag)
{
        if($.fn.DataTable.isDataTable('#ExpiryIndex'))
        {
            $('#ExpiryIndex').DataTable().destroy();
        }
       var productTable = $('#ExpiryIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: false,
            iDisplayLength: 15,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('resort.visa.VerifyDetails') }}",
                type: 'GET',
                data: function(d)
                {
                    d.flag = flag||'all';
                    d.search = $('.Search').val();
                    d.status = $("#statusFilter").val();
              
                }
            },
            columns: [
                {
                    data: 'profile_view',
                    name: 'profile_view',
                    orderable: false,
                    searchable: false
                }
            ],
            headerCallback: function(thead, data, start, end, display) {
                // Hide the header row
                $(thead).remove();
            }
        });
}
</script>
@endsection
