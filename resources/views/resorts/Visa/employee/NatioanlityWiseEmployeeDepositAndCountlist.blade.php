@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                        <span>Visa Management</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <table  id="NatioanlityWiseEmployeeDepositAndCount-table" class="table  w-100">
                        <thead>
                            <tr>
                                
                                <th>Nationality</th>
                                <th>Deposit Amount</th>
                                <th>Employees</th>
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
</div>
<div class="modal fade" id="NatioanlityWiseEmployee-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
              
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
                <div class="modal-body">
                    <div class="row">
                        <table   class="table " >
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Departmeent</th>
                                </tr>
                            </thead>
                            <tbody id="NatioanlityWiseEmployee-table">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    NatioanlityWiseEmployeeDepositAndCount();
    
       $(document).on("click",".OpenNatioanlityWiseEmployee",function(){
        let id=  $(this).attr('data-cat-id');
        $.ajax({
            url: "{{ route('resort.visa.NatioanlityWiseEmployeeList') }}",
            type: "GET",
            data: { id: id,"_token":"{{csrf_token()}}" },
            success: function(response) {
                if (response.success) 
                {
                    $("#NatioanlityWiseEmployee-modal").modal('show');
                    $("#NatioanlityWiseEmployee-table").empty();
                    $("#NatioanlityWiseEmployee-table").html(response.html);
                } 
                else 
                {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                console.log(errors);
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });

    });
});
function NatioanlityWiseEmployeeDepositAndCount()
{
    if($.fn.DataTable.isDataTable('#NatioanlityWiseEmployeeDepositAndCount-table')) {
        $('#NatioanlityWiseEmployeeDepositAndCount-table').DataTable().destroy();
    }

    $('#NatioanlityWiseEmployeeDepositAndCount-table').DataTable({
        searching: false,
        bLengthChange: false,
        bInfo: true,
        bAutoWidth: false,
        scrollX: false,
        iDisplayLength: 15,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('resort.visa.NatioanlityWiseEmployeeDepositAndCountDetails') }}",
            type: 'GET',
            dataSrc: function(json) {
                console.log(json);  // Debug: See the response
                return json.data;
            }
        },
        columns: [
            { data: 'Nationality', name: 'Nationality', className: 'text-nowrap' },
            { data: 'DepositAmount', name: 'DepositAmount', className: 'text-nowrap' },
            { data: 'Employeee', name: 'Employeee', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', className: 'text-nowrap' },

        ]
    });             

}


</script>
@endsection
