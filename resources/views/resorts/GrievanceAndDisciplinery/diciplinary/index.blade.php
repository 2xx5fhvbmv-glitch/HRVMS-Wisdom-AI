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
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('GrievanceAndDisciplinery.Disciplinary.CreateDisciplinary') }}" class=" btn btn-sm btn-theme @if(App\Helpers\Common::checkRouteWisePermission('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex',config('settings.resort_permissions.create')) == false) d-none @endif">Create Disciplinary</a>
                    </div>
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
                                        <h3>{{ $page_title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="IndexGrievanceDelegationRule" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Disciplinary Id </th>
                                    <th class="text-nowrap"> Category Name </th>
                                    <th class="text-nowrap"> Offence </th>
                                    <th> Employee Name </th>
                                    <th> Status </th>
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
        var DisciplineryCategory = $('#IndexGrievanceDelegationRule').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[6, 'desc']],
            ajax: '{{ route("GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex") }}',
            columns: [
                { data: 'Disciplinary_Id', name: 'Disciplinary_Id', className: 'text-nowrap' },
                { data: 'Category_name', name: 'Category_name', className: 'text-nowrap' },
                { data:'Offence',name:'Offence',className:'text-nowrap'},
                { data:'EmployeeName',name:'EmployeeName',className:'text-nowrap'},
                { data:'Status',name:'Status',className:'text-nowrap'},
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });

     
</script>
@endsection
