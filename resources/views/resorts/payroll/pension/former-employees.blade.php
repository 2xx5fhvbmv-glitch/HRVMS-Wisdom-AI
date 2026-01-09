@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                            <span>Payroll</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="departmentFilter" class="form-select select2t-none">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select  id="positionFilter" class="form-select select2t-none">
                                <option value="">All Positions</option>
                                <!-- Example: populate dynamically or statically -->
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="{{route('payroll.pension.index')}}" class="a-link">View Active Employees</a>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="table-pension" class="table data-Table table-pension  w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name </th>
                            <th>Department </th>
                            <th>Position</th>
                            <th>Basic Salary</th>
                            <th>Time</th>
                            <th>Person Contribution</th>
                            <th>Employee </th>
                            <th>Employer </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Your data rows will be inserted here by DataTables -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7">Total:</th>
                            <th class="fw-600">$0.00</th>
                            <th class="fw-600">$0.00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
     
@endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('.select2t-none').select2();
    loadPensionTable();

    $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function () {
        loadPensionTable();
    });
});

function loadPensionTable() {
    if ($.fn.DataTable.isDataTable('#table-pension')) {
        $('#table-pension').DataTable().destroy();
    }

    $('#table-pension').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 10,
        processing: true,
        serverSide: true,
        order:[[9, 'desc']],
        "ajax": {
            url: "{{ route('payroll.pension.former-employees') }}",
            data: function (d) {
                d.searchTerm = $('#searchInput').val();
                d.department = $('#departmentFilter').val();
                d.position = $('#positionFilter').val();
            },
            type: "GET",
        },
        // "createdRow": function(row, data, dataIndex) {
        //     if (data.row_class) {
        //         $(row).addClass(data.row_class);
        //     }
        // },
        "footerCallback": function (row, data, start, end, display) {
            var api = this.api();
            var json = api.ajax.json();
            
            if (json && json.totals) {
                // Update footer cells directly (more reliable than recreating tfoot)
                $(api.column(7).footer()).html(
                    '$' + parseFloat(json.totals.employee_pension || 0).toFixed(2)
                );
                $(api.column(8).footer()).html(
                    '$' + parseFloat(json.totals.employer_pension || 0).toFixed(2)
                );
            }
        },
        "columns": [
            { data: 'Emp_id' },
            { data: 'name' },
            { data: 'department' },
            { data: 'position' },
            { data: 'basic_salary' },
            { data: 'time' },
            { data: 'pension_percentage' },
            { 
                data: 'employee_pension', 
                render: function(data, type, row) {
                    return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                }
            },
            { 
                data: 'employer_pension',  // Note: Fix spelling to match server response
                render: function(data, type, row) {
                    return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                }
            },
            { data: 'created_at', visible: false, searchable: false }
        ],
        "initComplete": function() {
            // Initialize footer if it doesn't exist
            if (!$('#table-pension tfoot').length) {
                $('#table-pension').append(
                    '<tfoot><tr>' +
                    '<th colspan="7" class="text-end fw-600">Total:</th>' +
                    '<th class="fw-600">$0.00</th>' +
                    '<th class="fw-600">$0.00</th>' +
                    '</tr></tfoot>'
                );
            }
        }
    });
}
</script>
@endsection