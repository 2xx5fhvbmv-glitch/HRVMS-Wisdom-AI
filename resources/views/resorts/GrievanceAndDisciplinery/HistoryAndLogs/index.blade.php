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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Config Pages</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <!-- <div class="d-flex justify-content-end">
                        <a href="{{ route('GrievanceAndDisciplinery.grivance.CreateGrivance') }}" class=" btn btn-sm btn-theme">Create Grivance</a>
                    </div> -->
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                <div class="card">
    <div class="card-header">
        <div class="row g-md-3 g-2 align-items-center">
            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                <div class="input-group">
                    <input type="search" class="form-control search" id="search" placeholder="Search" />
                    <i class="fa-solid fa-search"></i>
                </div>
            </div>
            <div class="col-xl-2 col-md-3 col-sm-4 col-7">
                <select class="form-select" name="emp" id="emp">
                    <option value="">Select Employee</option>
                    @if($Employee->isNotEmpty())
                        @foreach($Employee as $e)
                            <option value="{{$e->id}}">{{$e->resortAdmin->first_name}} {{$e->resortAdmin->last_name}}</option>
                        @endforeach
                    @endif
                </select>
            </div>

        </div>
    </div>
    <table id="IndexGrievancehistorylogs" class="table data-Table table-history-andlogs w-100">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee Name </th>
                <th>Department</th>
                <th>Section</th>
                <th>Position</th>
                <th>Grievance</th>
                <th>Note</th>
                <th>Status</th>
                    <th>Actions</th>
            
            </tr>
        </thead>
        <tbody>
            
        </tbody>
    </table>
</div>
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

    $(document).ready(function () {

        $("#emp").select2({
            placeholder:"Select Employee"
        })
        IndexGrievancehistorylogs();
    });

    $("#emp").on("change",function(){
        IndexGrievancehistorylogs();        
    });
   

    $(".search").on("keyup",function(){
        IndexGrievancehistorylogs();        
    });
   
    
function IndexGrievancehistorylogs(){
    
    if ($.fn.DataTable.isDataTable('#IndexGrievancehistorylogs')) {
        $('#IndexGrievancehistorylogs').DataTable().destroy();
    }
    
    // Clear the table contents
    var DisciplineryCategory = $('#IndexGrievancehistorylogs').DataTable({
    "searching": false,
    "bLengthChange": false,
    "bFilter": true,
    "bInfo": true,
    "bAutoWidth": false,
    "scrollX": true,
    "iDisplayLength": 6,
    processing: true,
    serverSide: true,
    order:[[9, 'desc']],
    ajax: {
        url: '{{ route("GrievanceAndDisciplinery.Disciplinary.HistoryAndLogs") }}',
        type: 'GET',
        data: function(d) {
            d.search = $(".search").val();
            d.emp =  $("#emp").val();
        }
    },
    columns: [
        { data: 'ID', name: 'ID', className: 'text-nowrap' },
        { data: 'Employee_Name', name: 'EmployeeName', className: 'text-nowrap' },
        { data: 'Department', name: 'Department', className: 'text-nowrap' },
        { data: 'Section', name: 'Section', className: 'text-nowrap' },
        { data: 'Position', name: 'Position', className: 'text-nowrap' },
        { data: 'GrivanceName', name: 'Grievance', className: 'text-nowrap' },
        { data: 'Note', name: 'Note', className: 'text-nowrap' },
        { data: 'Status', name: 'Status', className: 'text-nowrap' },
        { data: 'Action', name: 'Action', orderable: false, searchable: false },
        {data:'created_at',visible:false,searchable:false},
    ]
});

    
    }
</script>


@endsection
