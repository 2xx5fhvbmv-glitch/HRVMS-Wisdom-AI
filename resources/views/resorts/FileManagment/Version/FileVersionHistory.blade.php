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
                            <span>File Management</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                     
                      

                        <!-- <div class="col-xl-5 col-lg-3 col-md-2 col-sm-6 ms-auto text-end">
                            <button id="mark-attendance-btn" class="btn btn-themeBlue btn-sm">Mark Attendance</button>
                        </div> -->
                    </div>
                </div>
                <table class="table-lableNew  table-FileVersionDashboardList w-100">
                        <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Modified By</th>
                            <th>Timestamp</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
@endsection

@section('import-css')
<style></style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        FileVersionDashboardList();
    });
    function FileVersionDashboardList()
{
    if ($.fn.dataTable.isDataTable('.table-FileVersionDashboardList')) 
    {
        $('.table-FileVersionDashboardList').DataTable().destroy();
    }

    var TableAccomMainten = $('.table-FileVersionDashboardList').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        order: [[4, 'desc']],   
        ajax: {
            url: '{{ route("FileManage.FileVersionList") }}',
            type: 'GET',
            data: function (d) {
                d.ResortDepartment ="Dashboard";
            }
        },
        columns: [
            { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
            { data: 'ModifiedBy', name: 'ModifiedBy', className: 'text-nowrap' },
            { data: 'Timestamp', name: 'Timestamp', className: 'text-nowrap' },
            { data: 'Size', name: 'Size', className: 'text-nowrap' },
             {data:'created_at',visible:false,searchable:false},
        ]
    });
}
   
</script>
@endsection