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
                      <div class="col-auto  ms-auto"><a class="btn btn-theme @if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.create')) == false) d-none @endif" href="{{ route('people.salary-increment.grid-index') }}">Initiate Increment 
                             </a></div>
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

                 <div class="@if(Common::checkRouteWisePermission('people.salary-increment.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                    <div class="salaryIncrementManageForm-bgBlock bg-themeGrayLight mb-md-4 mb-3 d-none">
                         <h6 class="fw-600 mb-2">Bulk Action: <span id="employeeCount">0</span> Employees</h6>
                         <form action="{{ route('people.salary-increment.bulk-update') }}" method="POST" class="row g-md-3 g-2 salary-increment-bulk-form">
                              @csrf
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <select class="form-select select2t-none" name="increment_type" id="select_build">
                                        <option selected disabled>Increment Type</option>
                                        @foreach ($incrementTypes as $increment_type)
                                             <option value="{{ $increment_type->name }}">{{ $increment_type->name }}</option>
                                        @endforeach
                                   </select>
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <select class="form-select select2t-none pay-increase-type" name="pay_increase_type" required>
                                        <option selected value="">Pay Increase Type</option>
                                        @foreach ($payIncreaseTypes as $key => $type)
                                             <option value="{{ $key }}">{{ $type }}</option>
                                        @endforeach
                                   </select>
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <input type="text" class="form-control" name="value" placeholder="Enter value" />
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <input type="text" class="form-control datepicker" name="effective_date" placeholder="Effective Date" />
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <input type="text" class="form-control" name="remark" placeholder="Remark" />
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <button class="btn btn-themeBlue w-100" type="submit">Apply To Selected</button>
                              </div>
                         </form>
                    </div>
               </div>
                    <div class="table-responsive mb-4">
                         <table id="salaryIncrementList" class="table  table-salaryIncreSummary  w-100 mb-0" style="max-height: 500px;">
                              <thead>
                                   <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Current Salary</th>
                                        <th>New Salary</th>
                                        <th>Increment</th>
                                        <th>Increment Type</th>
                                        <th>Effective Date</th>
                                        <th>Remark</th>
                                        <th>Last Activty</th>
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


 <div class="modal fade" id="editData-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-editData">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Update Salary Increment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="ajax-modal-body">
                
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

           $(".datepicker").datepicker({
               format: 'dd/mm/yyyy', 
               autoclose: true,   
               todayHighlight: true  
          });

          $(document).on('click', '.open-ajax-modal', function(e) {
               e.preventDefault();
               var url = $(this).attr('href');
               $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                         if(response.status == 'success'){
                              $('#ajax-modal-body').html(response.html);
                         }
                    },
                    error: function() {
                         toastr.success(response.message, "Error", {
                              positionClass: 'toast-bottom-right'
                         });
                    }
               });
          });

          $(document).on('click', '.submit-employee-increment', function(e) {
                 e.preventDefault();
                 var $form = $('#employee-increment-edit');
                 var actionUrl = $form.attr('action');
                 var formData = $form.serialize();
                 $.ajax({
                          url: actionUrl,
                          type: 'POST',
                          data: formData,
                          headers: {
                          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                          },
                          success: function(response) {
                              if(response.status === 'success') {
                                   $('#editData-modal').modal('hide');
                                   toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                   });
                                   loadDataTable();
                              } 
                          },
                          error: function(xhr) {
                                 toastr.error(xhr.responseJSON.message || "An error occurred", "Error", {
                                         positionClass: 'toast-bottom-right'
                                 });
                          }
                 });
          });

         loadDataTable();
     });
         
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
                  order:[[13, 'desc']],
                  ajax: {
                         url: '{{ route("people.salary-increment.employee.list") }}',
                         dataSrc: function(json) {
                               if (json.data.length === 0) {
                                     $('#bulkSubmitBtn').hide(); 
                               } else {
                                     $('#bulkSubmitBtn').show(); 
                               }
                               return json.data;
                         }
                  },
                  columns: [
                         { 
                               data: null, 
                               orderable: false, 
                               searchable: false, 
                               render: function (data, type, row) {
                                     return `<input type="checkbox" class="row-checkbox" data-id="${row.id}">`;
                               }
                         },
                         { data: 'Emp_id', name: 'Emp_id' },
                         { data: 'employee_name', name: 'employee_name' },
                         { data: 'position_title', name: 'position_title' },
                         { data: 'department_name', name: 'department_name' },
                         { data: 'previous_salary', name: 'previous_salary', render: function(data) { return '$' + data; } },
                         { data: 'new_salary', name: 'new_salary', render: function(data) { return '$' + data; } },
                         { data: 'increment_amount', name: 'increment_amount', render: function(data) { return '$' + data; } },
                         { data: 'increment_type', name: 'increment_type' },
                         { data: 'effective_date', name: 'effective_date' },
                         { data: 'remarks', name: 'remarks' },
                         { data: 'last_activity', name: 'last_activity' },
                         { data: 'action', name: 'action' },
                         { data: 'created_at', visible: false, searchable: false },
                  ]
            });
     }

     $(document).on('change', '.row-checkbox', function () {
          const selectedRows = $('.row-checkbox:checked').length;
          if (selectedRows > 0) {
               $('.salaryIncrementManageForm-bgBlock').removeClass('d-none'); // Show the bulk action form
               $('#bulkActionFooter').removeClass('d-none'); // Show the bulk action form

               $('#employeeCount').text(selectedRows); // Update the employee count
          } else {
               $('.salaryIncrementManageForm-bgBlock').addClass('d-none'); // Hide the bulk action form
               $('#bulkActionFooter').addClass('d-none'); // Hide the bulk action form
               $('#employeeCount').text(0); // Reset the employee count
          }
     });

     $(document).on('change', '#selectAll', function () {
          const isChecked = $(this).is(':checked');
          $('.row-checkbox').prop('checked', isChecked).trigger('change');
     });

     $('.salary-increment-bulk-form').on('submit', function (e) {
          e.preventDefault();

          const selectedIds = [];
          $('.row-checkbox:checked').each(function () {
               selectedIds.push($(this).data('id'));
          });

          if (selectedIds.length === 0) {
               toastr.error("Please select at least one employee.", "Validation Error", {
                    positionClass: 'toast-bottom-right'
               });
               return;
          }

          const formData = $(this).serializeArray();
          const data = {};
          formData.forEach(function (field) {
               data[field.name] = field.value;
          });

          data.selected_ids = selectedIds; // Add selected employee IDs to the data

          $.ajax({
               url: '{{ route("people.salary-increment.bulk-update") }}',
               type: 'POST',
               data: {
                    _token: '{{ csrf_token() }}',
                    ...data
               },
               success: function (response) {
                    if (response.status === 'success') {
                         toastr.success(response.message, "Success", {
                              positionClass: 'toast-bottom-right'
                         });
                         loadDataTable(); // Reload the table
                         $('.salaryIncrementManageForm-bgBlock').addClass('d-none'); // Hide the bulk action form
                    }
               },
               error: function (xhr) {
                    toastr.error(xhr.responseJSON.message || "An error occurred", "Error", {
                         positionClass: 'toast-bottom-right'
                    });
               }
          });
     });
</script>
@endsection


