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
                <table id="table-AuditLogsList" class="table data-Table table-AuditLogsList w-100">
                    <thead>
                        <tr>
                            <th>Action Type</th>
                            <th>File Name</th>
                            <th>Modified By</th>
                            <th>Last Modified</th>
                            <th>Time</th>
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
        AuditLogsList();
    });
    function AuditLogsList() {
            if ($.fn.dataTable.isDataTable('.table-AuditLogsList')) 
            {
                $('.table-AuditLogsList').DataTable().destroy();
            }

            var TableAccomMainten = $('.table-AuditLogsList').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[5, 'desc']],
                ajax: {
                    url: '{{ route("FileManage.AuditLogsDashboardList") }}',
                    type: 'GET',
                    data: function (d) {
                        d.flag ="index" ;
                    }
                },
                columns: [
                    { data: 'ActionType', name: 'ActionType', className: 'text-nowrap' },
                    { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
                    { data: 'ModifiedBy', name: 'ModifiedBy', className: 'text-nowrap' },
                    { data: 'LastModified', name: 'LastModified', className: 'text-nowrap' },
                    { data: 'Time', name: 'Time', className: 'text-nowrap' },
                     {data:'created_at',visible:false,searchable:false},
                ]
            });
    }
   
</script>
@endsection