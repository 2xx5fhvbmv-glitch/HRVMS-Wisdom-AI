@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #salary-increment-history-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #salary-increment-history-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
      <div class="container-fluid">
            <div class="page-hedding" id="salary-increment-history-hero">
                 <div class="row  g-3">
                      <div class="col-auto">
                            <div class="page-title">
                                 <span>People</span>
                                 <h1>{{ $page_title }}</h1>
                            </div>
                      </div>
                 </div>
            </div>

            <div class="card card-salaryIncrementSum">
                 <div class="card-title">
                      <h3>Employees Details</h3>
                 </div>

                 <div class="table-responsive mb-4">
                      <table id="salaryIncrementList" class="table  table-salaryIncreSummary  w-100 mb-0" style="max-height: 500px;">
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
                                      <th>Status</th>
                                 </tr>
                            </thead>
                            <tbody>
                            </tbody>
                      </table>
                 </div>
            </div>
      </div>
 </div>

@endsection

@section('import-css')
<style>
    /* History page Status column carries up to 3 stacked rows on Rejected:
       the badge, "Rejected by Finance: <actor> on <date>", and "Reason:
       <text>". Each row must stay on a single line; when actor names
       and dates make the content overflow, scroll horizontally inside
       the cell instead of wrapping. Same pattern as
       /salary-increment/list and /summary-list. */
    #salaryIncrementList tbody td:last-child {
        min-width: 240px;
        max-width: 360px;
        line-height: 1.4;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
    }
    #salaryIncrementList tbody td:last-child > div {
        white-space: nowrap;
    }
    #salaryIncrementList tbody td:last-child::-webkit-scrollbar {
        height: 5px;
    }
    #salaryIncrementList tbody td:last-child::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.18);
        border-radius: 3px;
    }
</style>
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
                          order: [[11, 'desc']],
                          serverSide: true,
                          ajax: {
                                url: '{{ route("people.salary-increment.history-list") }}',   
                          },
                          columns: [
                                 { data: 'Emp_id', name: 'Emp_id' },
                                 { data: 'employee_name', name: 'employee_name' },
                                 { data: 'position_title', name: 'position_title' },
                                 { data: 'department_name', name: 'department_name' },
                                 // formatAmount converts USD-stored values
                                 // to the resort's display currency (MVR or
                                 // Dollar) AND switches the symbol. Was
                                 // emitting symbol-only via currencySymbol +
                                 // raw USD digits, which showed MVR with
                                 // USD numbers on MVR-display resorts.
                                 { data: 'previous_salary',  name: 'previous_salary',  render: function(d){ return formatAmount(d, 'USD'); }},
                                 { data: 'new_salary',       name: 'new_salary',       render: function(d){ return formatAmount(d, 'USD'); }},
                                 { data: 'increment_amount', name: 'increment_amount', render: function(d){ return formatAmount(d, 'USD'); }},
                                 { data: 'increment_type', name: 'increment_type' },
                                 { data: 'effective_date', name: 'effective_date' },
                                 { data: 'remarks', name: 'remarks' },
                                 { data: 'status', name: 'status' },
                                 {data:'created_at',visible:false,searchable:false},
                          ]
                 });
          };      
    });


</script>
@endsection