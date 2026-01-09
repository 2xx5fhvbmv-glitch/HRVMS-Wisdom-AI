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
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card ">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-5 col-sm-4 col-6">
                            <div class="input-group">
                                <input type="search" class="form-control" id="search-input" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                            <option value="n/a">Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In-Progress">In-Progress</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6"> 
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>                                               
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                        
                    </div>
                </div>
                <div class="">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants" id="salaryAdvanceListTable">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Employee Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Request Amount</th>
                                    <th>Request Type</th>
                                    <th>Request Date</th>
                                    <th>Pourpose</th>
                                    <th>Current Status</th>
                                    <th>Rank Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>  
    $(".datepicker").datepicker({
        format: 'dd/mm/yyyy', 
        autoclose: true,   // Close the picker after selection
        todayHighlight: true  // Highlight today's date
    });
    function initializeSalaryAdvanceListTable() {
        if ($.fn.dataTable.isDataTable('#salaryAdvanceListTable')) {
            $('#salaryAdvanceListTable').DataTable().destroy();
        }

        $('#salaryAdvanceListTable').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            ajax: {
            url: "{{ route('people.advance-salary.list') }}",
            type: 'GET',
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.date = $('#dateFilter').val();
                d.searchTerm = $('#search-input').val();
            }
            },
            columns: [
              
            {data: 'Emp_id', name: 'Emp_id'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'position', name: 'position'},
            {data: 'department', name: 'department'},
            {data: 'request_amount', name: 'request_amount'},
            {data: 'request_type', name: 'request_type'},
            {data: 'request_date', name: 'request_date'},
            {data: 'pourpose', name: 'pourpose'},
            {data: 'status', name: 'status'},
            {data: 'rank_status', name: 'rank_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'created_at', visible: false, searchable: false },
            ],
            columnDefs: [
                { width: '5%', targets: 0 }, // Action column
                { width: '13%', targets: 9 }, // Action column
            ],
            order: [[11, 'desc']],
        });
}

$(document).ready(function() {
    initializeSalaryAdvanceListTable();
    $('#search-input,  #statusFilter, #dateFilter').on('keyup change', function () {
       
        initializeSalaryAdvanceListTable();
    });
});

$('#clearFilter').on('click', function() {
    $('#search-input').val('');
    $('#statusFilter').val('n/a').trigger('change');
    $('#dateFilter').val('');
    initializeSalaryAdvanceListTable();
});



</script>
@endsection

