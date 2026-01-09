@extends('resorts.layouts.app')
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
                                 <span>People</span>
                                 <h1>{{ $page_title }}</h1>
                            </div>
                      </div>
                      <div class="col-auto  ms-auto">
                        
                        <div class="dropdown @if($downloadBtn ==false) d-none @endif">
                            <button class="btn btn-theme dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Download
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('people.salary-increment.download',['file'=>'pdf']) }}">Pdf
                             </a></li>
                                <li><a class="dropdown-item" href="{{ route('people.salary-increment.download',['file'=>'excel']) }}">Excel 
                             </a></li>
                            </ul>
                            </div>
                        </div>
                 </div>
            </div>

            <div class="card card-salaryIncrementSum">
                 <div class="card-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>Employees Details</h3>
                        </div>  
                        <div>
                            <h3><a href="{{ route('people.salary-increment.history-list') }}">View History</a></h3>
                        </div>  
                    </div>  

                 </div>

                 <div class="table-responsive mb-4">
                      <table id="salaryIncrementList" class="table table-salaryIncreSummary  w-100 mb-0" style="max-height: 500px;">
                            <thead>
                                 <tr>
                                      <th>Employee ID</th>
                                      <th>Employee Name</th>
                                      <th>Position</th>
                                      <th>Department</th>
                                      <th>Current Salary</th>
                                      <th>New Salary</th>
                                      <th>Increment</th>
                                      <th>Increment Type</th>
                                      <th>Effective Date</th>
                                      <th>Remarks</th>
                                 </tr>
                            </thead>
                            <tbody>
                            </tbody>
                      </table>
                 </div>

                 <div class="cardBorder-block mb-4">
                      <div class="row g-md-4 g-2 align-items-end">
                            <div class="col-md-6">
                                <div class="card-title">
                                    <h3>Financial Impact Analysis</h3>
                                </div>
                                <div class="bg-themeGrayLight d-flex">
                                    <h6>Current Basic Salary (Monthly)</h6><strong class="current-basic-salary">$0</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="bg-themeGrayLight d-flex">
                                      <h6>New Basic Salary (Monthly)</h6><strong class="new-basic-salary">$0</strong>
                                 </div>
                            </div>
                      </div>
                      <div class="row g-md-4 g-2 mt-2 align-items-end">
                            <div class="col-md-6">
                                 <div class="bg-themeGrayLight d-flex">
                                      <h6>Payroll Cost Increase(Monthly)</h6><strong class="monthly-payroll-increase">$0</strong>
                                 </div>
                            </div>
                            <div class="col-md-6">
                                 <div class="bg-themeGrayLight d-flex">
                                      <h6>Payroll Cost Increase (Annual)</h6><strong class="annual-payroll-increase">$0</strong>
                                 </div>
                            </div>
                      </div>
                 </div>
                @if($hasGMApproval == true || $hasFinanceApproval == true)
                    <div class="card-footer">
                        <div class="row g-2">
                                <div class="col-auto">
                                    <a href="#" class="btn btn-themeNeon btn-sm request-change" data-bs-toggle="modal" data-bs-target="#reqChange-modal">Request Change</a>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="#" class="btn btn-themeSkyblue btn-sm request-hold" data-status="Hold">On Hold</a>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="btn btn-themeBlue btn-sm submit-status-btn" data-status="Approved">Approve</a>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="btn btn-themeDanger btn-sm submit-status-btn" data-status="Rejected">Reject</a>
                                </div>
                        </div>
                    </div>
                @endif
            </div>
      </div>
 </div>

<div class="modal fade" id="reqChange-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-reqChange">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('people.salary-increment.request-change')}}" id="requestChange">
                    @csrf
                    <textarea id="requestChange" class="form-control" name="request_change" rows="3" placeholder="write your Comment (required)" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-themeBlue ">Submit</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reqHold-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-reqHold">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hold Increment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('people.salary-increment.hold-request')}}" id="requestChange">
                    @csrf
                    <input type="text" class="form-control datepicker" id="holdDate" name="hold_date" placeholder="Hold Date" />

                    <textarea id="requestChange" class="form-control mt-2" name="request_hold" rows="3" placeholder="write your Comment (required)" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="holdSubmitBtn" class="btn btn-themeBlue ">Submit</button>
            </div>
        </div>
    </div>
</div>

 <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-reject">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Increment </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    @csrf
                    <textarea class="form-control" name="reject_reason" rows="3" placeholder="Write your reason for rejection (required)" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-themeBlue" id="confirmReject">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
 <script>
     $(document).ready(function () {
         loadDataTable();
         
         function loadDataTable() {
                  if ($.fn.dataTable.isDataTable('#salaryIncrementList')) {
                         $('#salaryIncrementList').DataTable().destroy();
                  }
                 $('#salaryIncrementList').DataTable({
                          "searching": false,
                          "bLengthChange": false,
                          "bFilter": true,
                          "bInfo": true,
                          "bAutoWidth": false,
                          "scrollY": "450px",
                          "scrollCollapse": true,
                          "paging": false, // Hide pagination
                          "iDisplayLength": -1,
                          "lengthMenu": [[-1], ["All"]],
                          processing: true,
                          serverSide: true,
                          order:[[10, 'desc']],
                          ajax: {
                                url: '{{ route("people.salary-increment.summary-list") }}',
                                dataSrc: function (json) {

                                $('.current-basic-salary').text(`$${json.currentBasicSalary.toLocaleString()}`);
                                $('.new-basic-salary').text(`$${json.newBasicSalary.toLocaleString()}`);
                                $('.monthly-payroll-increase').text(`$${json.monthlyPayrollIncrease.toLocaleString()}`);
                                $('.annual-payroll-increase').text(`$${json.annualPayrollIncrease.toLocaleString()}`);
                                return json.data;
                              }     
                          },
                          columns: [
                                 { data: 'Emp_id', name: 'Emp_id' },
                                 { data: 'employee_name', name: 'employee_name' },
                                 { data: 'position_title', name: 'position_title' },
                                 { data: 'department_name', name: 'department_name' },
                                 { data: 'previous_salary', name: 'previous_salary' ,render: function(data) { return '$' + data; }},
                                 { data: 'new_salary', name: 'new_salary', render: function(data) { return '$' + data; }},
                                 { data: 'increment_amount', name: 'increment_amount',render: function(data) { return '$' + data; } },
                                 { data: 'increment_type', name: 'increment_type' },
                                 { data: 'effective_date', name: 'effective_date' },
                                 { data: 'remarks', name: 'remarks' },
                                 {data:'created_at',visible:false,searchable:false},
                          ]
                 });
          };

          $(document).on('click', '.submit-status-btn', function(e) {
               e.preventDefault();
               let status = $(this).data('status');
               let table = $('#salaryIncrementList').DataTable();
               let tableData = table.rows().data().toArray();

               if (status === 'Approved') {
                    Swal.fire({
                         title: 'Are you sure?',
                         text: "You are about to approve the salary increment.",
                         icon: 'warning',
                         showCancelButton: true,
                         confirmButtonText: 'Confirm',
                         cancelButtonText: 'Cancel',
                         confirmButtonColor: "#014653"
                    }).then((result) => {
                         if (result.isConfirmed) {
                              submitStatus(status, tableData);
                         }
                    });
               } else if (status === 'Rejected') {
                    $('#reject-modal').modal('show');
               } else {
                    submitStatus(status, tableData);
               }
          });

          $('#confirmReject').on('click', function() {
               let rejectReason = $('textarea[name="reject_reason"]').val();
               if (!rejectReason) {
                    toastr.error("Please provide a rejection reason", "Error", {
                         positionClass: 'toast-bottom-right'
                    });
                    return;
               }

               let table = $('#salaryIncrementList').DataTable();
               let tableData = table.rows().data().toArray();
               
               submitStatus('Rejected', tableData, rejectReason);
               $('#reject-modal').modal('hide');
          });

          function submitStatus(status, tableData, rejected_reason = null) {
               $.ajax({
                    url: '{{ route("people.salary-increment.update-status") }}',
                    method: 'POST',
                    data: {
                         _token: '{{ csrf_token() }}',
                         status: status,
                         payload: JSON.stringify(tableData),
                         rejected_reason: rejected_reason
                    },
                    success: function(response) {
                        console.log(response);
                        if(response.success == true) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            loadDataTable();
                            window.location.href = response.redirect_url;
                        }
                        else{
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr) {
                         alert('An error occurred. Please try again.');
                    }
               });
          }
     });


      $(document).ready(function () {
                // When the modal is shown, initialize the datepicker
                $('#reqHold-modal').on('shown.bs.modal', function () {
                    $('#holdDate').datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true,
                        todayHighlight: true,
                        container: '#reqHold-modal' // Ensures the picker stays within the modal
                    }).click();
                });

                $('#reqHold-modal').on('hidden.bs.modal', function () {
                    $('#holdDate').datepicker('destroy');
                });

                $('.request-hold').on('click', function(e) {
                    e.preventDefault();
                    $('#reqHold-modal').modal('show');
                });
            

         $('#holdSubmitBtn').on('click',function(e){
            e.preventDefault();
            let table = $('#salaryIncrementList').DataTable();
            let tableData = table.rows().data().toArray();
            let remark = $('textarea[name="request_hold"]').val();
            let hold_date = $('input[name="hold_date"]').val();  
            if(requestChange == ''){
                toastr.error("Please write your comment", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return ;
            }

            $.ajax({
                    url: '{{ route("people.salary-increment.hold-request") }}',
                    method: 'POST',
                    data: {
                         _token: '{{ csrf_token() }}',
                         remarks: remark,
                         due_date: hold_date,
                         payload: JSON.stringify(tableData)
                    },
                    success: function(response) {
                         if(response.success) {
                              toastr.success(response.message, "Success", {
                                   positionClass: 'toast-bottom-right'
                              });
                               $('#reqHold-modal').modal('hide');
                                loadDataTable();
                                window.location.href = response.redirect_url;
                         }
                    },
                    error: function(xhr) {
                         alert('An error occurred. Please try again.');
                    }
               });
        });
    });

    // Hold Request
      $(document).ready(function () {
        $('.request-change').on('click', function(e) {
            e.preventDefault();
            $('#reqChange-modal').modal('show');
        });

        $('#submitBtn').on('click',function(e){
            e.preventDefault();
            let table = $('#salaryIncrementList').DataTable();
            let tableData = table.rows().data().toArray();
            let requestChange = $('textarea[name="request_change"]').val();
            if(requestChange == ''){
                toastr.error("Please write your comment", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return ;
            }

            $.ajax({
                    url: '{{ route("people.salary-increment.request-change") }}',
                    method: 'POST',
                    data: {
                         _token: '{{ csrf_token() }}',
                         remarks: requestChange,
                         payload: JSON.stringify(tableData)
                    },
                    success: function(response) {
                         if(response.success) {
                              toastr.success(response.message, "Success", {
                                   positionClass: 'toast-bottom-right'
                              });
                               $('#reqChange-modal').modal('hide');
                                loadDataTable();
                                window.location.href = response.redirect_url;
                         }
                    },
                    error: function(xhr) {
                         alert('An error occurred. Please try again.');
                    }
               });
        });

       
    });

</script>
@endsection


