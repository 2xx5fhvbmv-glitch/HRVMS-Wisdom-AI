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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Configuration</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Benefit Grids</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="{{route('resort.benifitgrid.create')}}" class="btn btn-sm btn-theme @if(App\Helpers\Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="benefit-grid-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Employee Grade</th>
                                    <th class="text-nowrap">Effective Date</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

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
    var benefitgridTable = $('#benefit-grid-table').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        order:[[4, 'desc']],
        ajax: '{{ route("resort.benifitgrid.list") }}',
        columns: [
            { data: 'emp_grade', name: 'emp_grade', className: 'text-nowrap' },
            { data: 'effective_date', name: 'effective_date', className: 'text-nowrap' },
            { data: 'status', name: 'status', className: 'text-nowrap' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
             {data:'created_at',visible:false,searchable:false},
        ]
    });
</script>
@endsection