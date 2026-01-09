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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>VISA MANAGEMENT</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">

                        <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 col ">
                            <label for="date-duration" class="form-label d-block">DURATION</label>
                                 <div class="dateRangeAb"  id="datapicker">
                                    <div>
                                        <!-- Hidden input field to attach the calendar to -->
                                        <input type="text" class="form-control" name="hiddenInput" id="hiddenInput">
                                    </div>
                                    <p id="startDate" class="d-none">Start Date:</p>
                                    <p id="endDate" class="d-none">End Date:</p>
                                </div>


                        </div>
                        <div class="col-auto">
                            <label for="date-duration" class="form-label d-block">&nbsp;</label>

                            <a href="#" class="btn btn-themeBlue btn-sm">Download</a>
                        </div>
                    </div>
                </div>
                <div id="append_liability">
                
                </div>

            </div>


        </div>
    </div>
  <div class="modal fade" id="EmployeeList-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="StatisEmployeeList"></h5>
                
                
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Profile</th>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                </tr>
                                <tbody id="appendempList">
                                </tbody>
                        </table>
                           
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
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
$("#PaymentRequestRejectedForm").parsley();
    $("#date").datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        orientation: "bottom auto",
    });

    $("#hiddenInput").daterangepicker({
       autoApply: true,
       startDate: moment().subtract(1, 'year'), // Start date set to one year ago
        endDate: moment().startOf('month'),
       opens: 'right',
       parentEl: '#datapicker',
       alwaysShowCalendars: true,
       linkedCalendars: false,
       locale: 
       {
          format: "DD/MM/YYYY", // Ensure the format matches your date parsing logic
       }
    });

       $("#hiddenInput").on("change",function()
       {
          LiabilityTableIndex();
       });


    $(document).on("click", ".findEmploees", function() {
        var date = $("#hiddenInput").val();
        var flag = $(this).data("flag");
            $.ajax({
                    url: "{{ route('resort.visa.FetchTotalEmployees') }}",
                    type: "GET",
                    data: {
                        date    : $("#hiddenInput").val(),
                        flag    : flag,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) 
                    {
                        $("#StatisEmployeeList").text("Employees List for " + flag);
                        $("#EmployeeList-modal-lg").modal("show");
                        $("#appendempList").html(response.html);
                    },
                    error: function(response) 
                    {
                            if (response.status === 422)
                            {
                                    var errors = response.responseJSON.errors; // Access error object
                                    var errs = '';
                                    $.each(errors, function (field, messages) {
                                        $.each(messages, function (index, message) {
                                            errs += message + '<br>'; // Append each message
                                        });
                                    });
                                    toastr.error(errs, "Validation Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                            }
                            else
                            {
                                    toastr.error("An unexpected error occurred.", "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                            }
                        }
            });
    });

    

   LiabilityTableIndex();

});


function LiabilityTableIndex() 
{

    $.ajax({
            url: "{{ route('resort.visa.Liabilities') }}",
            type: "GET",
            data: {
                date: $("#hiddenInput").val(),
            },
            success: function(response) 
            {
            $("#append_liability").html(response.html);
            },
        error: function(response) 
        {
                if (response.status === 422)
                {
                        var errors = response.responseJSON.errors; // Access error object
                        var errs = '';
                        $.each(errors, function (field, messages) {
                            $.each(messages, function (index, message) {
                                errs += message + '<br>'; // Append each message
                            });
                        });
                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                }
                else
                {
                        toastr.error("An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                }
            }
    });


   

    
}


</script>
@endsection