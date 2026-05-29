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
                    {{-- This form EDITS existing pending increments (posts to
                         bulk-update). It's not a "create new increment" form —
                         that flow lives on /resort/people/salary-increment.
                         Labels reflect the edit-existing intent so HR doesn't
                         confuse it with the cards-based create page. --}}
                    <div class="salaryIncrementManageForm-bgBlock bg-themeGrayLight mb-md-4 mb-3 d-none">
                         <h6 class="fw-600 mb-2">Edit Selected Pending Increments: <span id="employeeCount">0</span> Selected</h6>
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
                                   {{-- Default to Fixed; matches the create flow which no
                                        longer shows the "Pay Increase Type" placeholder. --}}
                                   <select class="form-select select2t-none pay-increase-type" name="pay_increase_type" required>
                                        @foreach ($payIncreaseTypes as $key => $type)
                                             <option value="{{ $key }}" {{ $key === 'Fixed' ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                   </select>
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <div class="input-group value-input-group">
                                        <span class="input-group-text currency-prefix">{{ Common::GetResortCurrencySymbol() }}</span>
                                        <input type="number" class="form-control value" name="value" placeholder="Enter fixed value" min="0" max="999999.99" />
                                        <span class="input-group-text percent-suffix d-none">%</span>
                                   </div>
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <input type="text" class="form-control datepicker" name="effective_date" placeholder="Effective Date" />
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <input type="text" class="form-control" name="remark" placeholder="Remark" />
                              </div>
                              <div class="col-xxl col-xl-3 col-md-4 col-sm-6">
                                   <button class="btn btn-themeBlue w-100" type="submit">Update Selected</button>
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

{{-- Read-only modal — initiator + approval chain + pending stage. Shared
     by HR / Finance / GM so anyone can see the full trail at a glance. --}}
<div class="modal fade" id="viewData-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Salary Increment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="ajax-view-modal-body"></div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    /* Salary Increment list — when resort displays MVR, currency cells
       become "MVR 1,542.00" (3 tokens) and used to wrap "MVR" on one
       line and "1,542.00" on the next inside the narrow Increment
       column. nowrap on every body cell keeps each value on one line;
       headers still wrap normally. */
    #salaryIncrementList tbody td {
        white-space: nowrap;
        vertical-align: middle;
        font-size: 13px;
        padding: 8px 10px;
    }
    #salaryIncrementList thead th {
        white-space: normal;
        font-size: 12.5px;
        padding: 8px 10px;
        vertical-align: middle;
    }
    /* The "Last Activity" stage column carries two rows — Finance and
       GM, each "label: badge — reason". User wants each row on a SINGLE
       LINE (no wrap between badge and reason); when the reason is too
       long the cell scrolls horizontally so the column width stays
       bounded.
       The cell itself stacks the two inner <div>s vertically; each div
       is whitespace:nowrap so the row never wraps mid-text. */
    #salaryIncrementList tbody td:nth-last-child(2) {
        min-width: 240px;
        max-width: 320px;
        line-height: 1.4;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
    }
    #salaryIncrementList tbody td:nth-last-child(2) > div {
        white-space: nowrap;
    }
    #salaryIncrementList tbody td:nth-last-child(2)::-webkit-scrollbar {
        height: 5px;
    }
    #salaryIncrementList tbody td:nth-last-child(2)::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.18);
        border-radius: 3px;
    }
    /* Remark column — long free text shouldn't blow up the table. */
    #salaryIncrementList tbody td:nth-last-child(3) {
        white-space: normal;
        max-width: 180px;
        word-break: break-word;
    }
</style>
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

          // View modal (read-only approval-chain panel). Same delegated
          // pattern as Edit — fetches HTML on demand and drops it into the
          // viewData-modal body.
          $(document).on('click', '.open-ajax-view-modal', function(e) {
               e.preventDefault();
               var url = $(this).attr('href');
               $('#ajax-view-modal-body').html('<div class="modal-body text-center py-4 text-muted">Loading…</div>');
               $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                         if (response.status === 'success') {
                              $('#ajax-view-modal-body').html(response.html);
                         } else {
                              $('#ajax-view-modal-body').html('<div class="modal-body text-danger">Failed to load details.</div>');
                         }
                    },
                    error: function() {
                         $('#ajax-view-modal-body').html('<div class="modal-body text-danger">Failed to load details.</div>');
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
                         // Use formatAmount() so the symbol AND value both
                         // switch when the resort display currency toggles.
                         // The old `currencySymbol + ' ' + data` printed the
                         // right symbol but never converted the USD-stored
                         // value, so MVR-display showed "MVR 7200" instead
                         // of "MVR 111,024".
                         { data: 'previous_salary',  name: 'previous_salary',  render: function(d) { return formatAmount(d, 'USD'); } },
                         { data: 'new_salary',       name: 'new_salary',       render: function(d) { return formatAmount(d, 'USD'); } },
                         { data: 'increment_amount', name: 'increment_amount', render: function(d) { return formatAmount(d, 'USD'); } },
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


