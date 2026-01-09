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
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('GrievanceAndDisciplinery.grivance.CreateGrivance') }}" class=" btn btn-sm btn-theme @if(App\Helpers\Common::checkRouteWisePermission('GrievanceAndDisciplinery.grivance.GrivanceIndex',config('settings.resort_permissions.create')) == false) d-none @endif">Create Grivance</a>
                    </div>
                </div> -->
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
                                    <th class="text-nowrap"> Grievance Id </th>
                                    <th class="text-nowrap"> Category Name </th>
                                    <th> Employee Name </th>
                                    <th> Confidentiality </th>
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

    $(document).ready(function() 
    {
        Index();
            $(document).on('click','#IndexGrievanceDelegationRule .delete-row-btn',function (e) 
            {
                var id = $(this).data('cat-id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('GrievanceAndDisciplinery.grivance.DeleteGrivance', ['id' => '__ID__']) }}".replace('__ID__', id),
                            type: "delete",
                            data: {
                                id: id,
                            },
                            success: function (response) 
                            {
                                Index();
                                $('.table-IndexLatterTemplete').DataTable().ajax.reload();                        
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            },
                            error: function(response)
                            {
                                var errors = response.responseJSON;
                                var errs = '';
                                $.each(errors.errors, function(key, error)
                                {
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                    }
                });
            });

    });
    function Index()
    {
        // Initialize DataTable for Grievance Index

        if ($.fn.DataTable.isDataTable('#IndexGrievanceDelegationRule'))
        {
            $('#IndexGrievanceDelegationRule').DataTable().destroy();
        }

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
            ajax: '{{ route("GrievanceAndDisciplinery.grivance.GrivanceIndex") }}',
            columns: [
                { data: 'Grievance_Id', name: 'Grievance_Id', className: 'text-nowrap' },
                { data: 'Grivance_CategoryName', name: 'Grivance_CategoryName', className: 'text-nowrap' },
                { data: 'Grivance_EmployeeName', name: 'Grivance_EmployeeName', className: 'text-nowrap' },
                { data:'Confidentiality',name:'Confidentiality',className:'text-nowrap'},
                { data:'status',name:'Status',className:'text-nowrap'},
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });

    }
      

</script>
@endsection
