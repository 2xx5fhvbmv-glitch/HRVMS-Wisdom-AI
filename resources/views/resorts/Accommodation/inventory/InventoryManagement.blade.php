
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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.accommodation.inventory') }}"  class="btn btn-theme  @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.Inventory',config('settings.resort_permissions.create')) == false) d-none @endif">Add New
                        Inventory</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2  col-lg-4 col-md-5 col-sm-4 col-6">
                        <select class="form-select" id="buildingAvailable">
                            <option value=""></option>
                            @if($BuildingModel->isNotEmpty()) 
                                @foreach($BuildingModel as $b)
                                    <option value="{{$b->id}}">{{$b->BuildingName}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select" id="AvailableFloor" >
                            <option value=""></option>
                         
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select"id="FloorWiseRoom">
                            <option value=""></option>
                          
                        </select>
                    </div>
                </div>
            </div>
            <table id="table-invenManagement" class="table  table-invenManagement  w-100">
                <thead>
                    <tr>
                       
                        <th>ItemName </th>
                        <th>Item </th>   
                        <th>Building Name. </th>
                        <th>Floor No</th>
                        <th>Room No.</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
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
@endsection

@section('import-scripts')
<script>
$(document).ready(function()
{
    $("#buildingAvailable").select2({
        placeholder: 'Select Building',
        allowClear: true,
    });
    $("#AvailableFloor").select2({
        placeholder: 'Select Floor',
        allowClear: true,
    });
    $("#FloorWiseRoom").select2({
        placeholder: 'Select Room',
        allowClear: true,
    });
    InventoryList();
});
    $(document).on('click', '.unassign', function () {
        var availableAccId = $(this).data('id');
        var item = $(this).data('item');
        var resort_id = $(this).data('resort_id');

        Swal.fire({
            title: "Are you sure?",
            text: "If you unassign, the item will be removed from the accommodation list.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, unassign it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('resort.accommodation.UnassignItem') }}",
                    type: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "availableAccId": availableAccId,
                        "item": item,
                        "resort_id": resort_id
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire("Unassigned!", response.message, "success");
                            InventoryList();
                        } else {
                            Swal.fire("Error!", response.message, "error");
                        }
                    },
                    error: function (response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function (key, error) {
                            errs += error + '<br>';
                        });
                        Swal.fire("Validation Error", errs, "error");
                    }
                });
            }
        });
    });
    $(document).on('change','#buildingAvailable',function(){

        var buildingId= $(this).val();
        var flag= "A";
        InventoryList();
    
            $.ajax({
                url: "{{ route('resort.accommodation.config.getFloor') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}","buildingId":buildingId},
                success: function(response) {

                    if (response.success)
                    {
                        var floor='<option></option>';
                        $.each(response.data, function(i, v) {
                            floor += `<option value="${v}">${v}</option>`;
                        });
                            $("#AvailableFloor").html(floor) .select2({
                                                                        placeholder: 'Select Floor',
                                                                        allowClear: true,
                                                                    });
             

                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
    });

    $(document).on('change','#AvailableFloor',function()
    {
        var location = $(this).data('id');
        var flag="A"
        InventoryList();

            var building_id = $("#buildingAvailable").val();
            var AvailableFloor = $("#AvailableFloor").val();

        $.ajax({
                url: "{{ route('resort.accommodation.config.GetFloorWiseRooms') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}","AvailableFloor":AvailableFloor,"building_id":building_id},
                success: function(response) {
                    if (response.success)
                    {
                        var rooms='<option></option>';
                        console.log(response.data);
                        $.each(response.data, function(i, v) {
                            rooms += `<option value="${v}">${v}</option>`;
                        });
                        
                            $("#FloorWiseRoom").html(rooms).select2({
                                                                        placeholder: 'Select Rooms',
                                                                        allowClear: true,
                                                                    });
                        



                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
    });

    $(document).on('keyup','.search',function()
    { 
        InventoryList();
    });
function InventoryList()
{

    if ($.fn.dataTable.isDataTable('#table-invenManagement'))
    {
        $('#table-invenManagement').DataTable().clear().destroy();
    }

    var InvenotryIndex = $('#table-invenManagement').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        order: [[9, 'desc']],  
        ajax: {
                url: "{{ route('resort.accommodation.InventoryManagement') }}",
                type: 'GET',
                data: function(d) 
                {
                    d.searchTerm         =  $('.search').val();
                    d.buildingAvailable  =  $("#buildingAvailable").val();
                    d.AvailableFloor     =  $("#AvailableFloor").val();
                    d.FloorWiseRoom      =  $("#FloorWiseRoom").val();
                }
            },
        columns: [
            { data: 'ItemName', name: 'ItemName', className: 'text-nowrap' },
            { data: 'ItemCode', name: 'ItemCode', className: 'text-nowrap' },
            { data: 'BuildingName', name: 'BuildingName', className: 'text-nowrap' },
            { data: 'Floor', name: 'FloorNo', className: 'text-nowrap' },
            { data: 'Room', name: 'RoomNo', className: 'text-nowrap' },
            { data: 'AssignedTo', name: 'AssignTo', className: 'text-nowrap' },
            { data: 'Date', name: 'Date', className: 'text-nowrap' },
            { data: 'ItemStatus', name: 'Status', className: 'text-nowrap' },
            { data: 'action', name: 'action', className: 'text-nowrap' },
             {data:'created_at',visible:false,searchable:false},


        ]
    });
}
</script>
@endsection
