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

                        <div class="col-auto ms-auto">
                            <a href="#" class="btn eb-btn-secondary btn-sm">Download</a>
                        </div>
                    </div>
                </div>
                <div id="append_liability">
                
                </div>

            </div>


        </div>
    </div>
  <!-- Per-category liability breakdown (how Total / Paid / Balance add up per employee) -->
  <div class="modal fade" id="liability-breakdown-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="liabilityBreakdownTitle">Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="liabilityBreakdownBody"></div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Close</a>
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
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                    </div>
    
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){
$("#PaymentRequestRejectedForm").parsley();

    // Per-category breakdown — show how the card's Total / Paid / Balance add up
    // across each expat employee.
    $(document).on("click", ".liabilityBreakdown", function() {
        var flag = $(this).data("flag");
        $("#liabilityBreakdownTitle").text("Liability Breakdown");
        $("#liabilityBreakdownBody").html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0">Loading breakdown…</p></div>');
        $("#liability-breakdown-modal").modal("show");
        $.ajax({
            url: "{{ route('resort.visa.LiabilityBreakdown') }}",
            type: "GET",
            global: false,
            data: { flag: flag, "_token": "{{ csrf_token() }}" },
            success: function(response) {
                if (response && response.success) {
                    $("#liabilityBreakdownTitle").text((response.label || "Liability") + " — Breakdown");
                    $("#liabilityBreakdownBody").html(response.html);
                } else {
                    $("#liabilityBreakdownBody").html('<p class="text-muted mb-0">' + ((response && response.message) || 'Could not load the breakdown.') + '</p>');
                }
            },
            error: function() {
                $("#liabilityBreakdownBody").html('<p class="text-danger mb-0">Something went wrong loading the breakdown.</p>');
            }
        });
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