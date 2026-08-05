
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
                    <a href="{{ route('resort.accommodation.inventory') }}"  class="btn eb-btn-accent  @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.Inventory',config('settings.resort_permissions.create')) == false) d-none @endif">Add New
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



<!-- Assign Employee Modal -->
<div class="modal fade" id="assignEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="assignEmployeeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Assign Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assignEmpSelect" class="form-label">Select Employee</label>
                        <select class="form-select" id="assignEmpSelect" name="emp_id" required>
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignBedSelect" class="form-label">Select Bed</label>
                        <select class="form-select" id="assignBedSelect" name="assignId" required>
                            <option value="">Select Bed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="assignQuantity" name="quantity" min="1" value="1" placeholder="Enter quantity">
                    </div>
                    <input type="hidden" name="available_id" id="assignAvailableId">
                    <input type="hidden" name="item_id" id="assignItemId">
                    <input type="hidden" name="resort_id" id="assignResortId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-neutral" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn eb-btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('resorts._emotional_buttons_v2_styles')
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
    // Assign Employee - open modal with employee list
    $(document).on('click', '.assign-employee-btn', function () {
        var availableId = $(this).data('available-id');
        var itemId = $(this).data('item-id');
        var resortId = $(this).data('resort-id');
        var roomType = $(this).data('room-type');

        $('#assignAvailableId').val(availableId);
        $('#assignItemId').val(itemId);
        $('#assignResortId').val(resortId);

        // Fetch employees filtered by room type
        $.ajax({
            url: "{{ route('resort.accommodation.GetAccmmodationwiseEmployee') }}",
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "available_a_id": availableId,
                "RoomType": roomType
            },
            success: function(response) {
                if (response.success) {
                    // Populate employees
                    var options = '<option value="">Select Employee</option>';
                    $.each(response.Employees, function(i, v) {
                        options += `<option value="${v.EmployeeId}">${v.first_name} ${v.last_name} (${v.Emp_id})</option>`;
                    });
                    $('#assignEmpSelect').html(options);

                    // Populate available beds
                    var bedOptions = '<option value="">Select Bed</option>';
                    $.each(response.AssingAccommodation, function(i, v) {
                        bedOptions += `<option value="${v.id}">${v.BedNo}</option>`;
                    });
                    $('#assignBedSelect').html(bedOptions);

                    if ($('#assignEmpSelect').data('select2')) {
                        $('#assignEmpSelect').select2('destroy');
                    }
                    $('#assignEmpSelect').select2({
                        placeholder: 'Select Employee',
                        allowClear: true,
                        dropdownParent: $('#assignEmployeeModal')
                    });

                    if ($('#assignBedSelect').data('select2')) {
                        $('#assignBedSelect').select2('destroy');
                    }
                    $('#assignBedSelect').select2({
                        placeholder: 'Select Bed',
                        allowClear: true,
                        dropdownParent: $('#assignEmployeeModal')
                    });

                    $('#assignEmployeeModal').modal('show');
                } else {
                    toastr.error(response.message || 'Failed to load employees', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function() {
                toastr.error('Failed to load employees', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // Submit assign form
    $('#assignEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        var empId = $('#assignEmpSelect').val();
        var assignId = $('#assignBedSelect').val();

        if (!empId) {
            toastr.error('Please select an employee', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        if (!assignId) {
            toastr.error('Please select a bed', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: "{{ route('resort.accommodation.AssignAccommodationToEmp') }}",
            type: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                "emp_id": empId,
                "assignId": assignId
            },
            success: function(response) {
                if (response.success) {
                    $('#assignEmployeeModal').modal('hide');
                    InventoryList();
                    toastr.success(response.message || 'Employee assigned successfully', 'Success', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message || 'Failed to assign', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? (xhr.responseJSON.message || 'Failed to assign') : 'An error occurred';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.unassign', function () {
        var availableAccId = $(this).data('id');
        var item = $(this).data('item');
        var resort_id = $(this).data('resort_id');

        // First check how many employees are assigned
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
                if (response.success && response.multiple) {
                    // Multiple employees — show selection dialog
                    var empOptions = {};
                    $.each(response.employees, function(i, emp) {
                        empOptions[emp.assign_id] = emp.name + ' (' + emp.emp_id + ') - ' + emp.bed_no;
                    });

                    Swal.fire({
                        title: "Select Employee to Unassign",
                        text: response.message,
                        icon: "question",
                        input: "select",
                        inputOptions: empOptions,
                        inputPlaceholder: "Select an employee",
                        showCancelButton: true,
                        confirmButtonText: "Unassign",
                        confirmButtonColor: "#d33",
                        inputValidator: function(value) {
                            if (!value) return "Please select an employee";
                        }
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('resort.accommodation.UnassignItem') }}",
                                type: "POST",
                                data: {
                                    "_token": "{{ csrf_token() }}",
                                    "availableAccId": availableAccId,
                                    "resort_id": resort_id,
                                    "assignId": result.value
                                },
                                success: function(res) {
                                    if (res.success) {
                                        Swal.fire("Unassigned!", res.message, "success");
                                        InventoryList();
                                    } else {
                                        Swal.fire("Error!", res.message, "error");
                                    }
                                }
                            });
                        }
                    });
                } else if (response.success) {
                    // Single employee — already unassigned
                    Swal.fire("Unassigned!", response.message, "success");
                    InventoryList();
                } else {
                    Swal.fire("Error!", response.message, "error");
                }
            },
            error: function (response) {
                var errors = response.responseJSON;
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function (key, error) {
                        errs += error + '<br>';
                    });
                } else {
                    errs = errors ? errors.message : 'An error occurred';
                }
                        Swal.fire("Error", errs, "error");
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
