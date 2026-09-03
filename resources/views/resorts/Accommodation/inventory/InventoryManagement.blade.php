
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #inventory-management-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #inventory-management-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="inventory-management-hero">
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
                        <select class="form-select dd-native-select" id="buildingAvailable">
                            <option value=""></option>
                            @if($BuildingModel->isNotEmpty())
                                @foreach($BuildingModel as $b)
                                    <option value="{{$b->id}}">{{$b->BuildingName}}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#buildingAvailable">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Building</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Building">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a building…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Building</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @if($BuildingModel->isNotEmpty())
                                        @foreach($BuildingModel as $b)
                                        <div class="dd-item" role="option" data-value="{{ $b->id }}"><span class="dd-nm">{{ $b->BuildingName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select dd-native-select" id="AvailableFloor" >
                            <option value=""></option>

                        </select>
                        <div class="dd" data-target="#AvailableFloor">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Floor</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Floor">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a floor…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Floor</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select dd-native-select"id="FloorWiseRoom">
                            <option value=""></option>

                        </select>
                        <div class="dd" data-target="#FloorWiseRoom">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Room</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Room">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a room…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Room</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
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
                        <select class="form-select dd-native-select" id="assignEmpSelect" name="emp_id" required>
                            <option value="">Select Employee</option>
                        </select>
                        <div class="dd" data-target="#assignEmpSelect">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Employee</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Employee">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="assignBedSelect" class="form-label">Select Bed</label>
                        <select class="form-select dd-native-select" id="assignBedSelect" name="assignId" required>
                            <option value="">Select Bed</option>
                        </select>
                        <div class="dd" data-target="#assignBedSelect">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Bed</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Bed">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Bed</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function()
{
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
                    window.wisdomDD.rebuild('#assignEmpSelect');

                    // Populate available beds
                    var bedOptions = '<option value="">Select Bed</option>';
                    $.each(response.AssingAccommodation, function(i, v) {
                        bedOptions += `<option value="${v.id}">${v.BedNo}</option>`;
                    });
                    $('#assignBedSelect').html(bedOptions);
                    window.wisdomDD.rebuild('#assignBedSelect');

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

                    wisdomConfirm({
                        role: 'confirm',
                        title: "Select Employee to Unassign",
                        text: response.message,
                        confirmText: "Unassign",
                        extra: {
                            input: "select",
                            inputOptions: empOptions,
                            inputPlaceholder: "Select an employee",
                            inputValidator: function(value) {
                                if (!value) return "Please select an employee";
                            }
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
                                        wisdomAlert({
                                            type: 'success',
                                            title: "Unassigned!",
                                            text: res.message
                                        });
                                        InventoryList();
                                    } else {
                                        wisdomAlert({
                                            type: 'error',
                                            title: "Error!",
                                            text: res.message
                                        });
                                    }
                                }
                            });
                        }
                    });
                } else if (response.success) {
                    // Single employee — already unassigned
                    wisdomAlert({
                        type: 'success',
                        title: "Unassigned!",
                        text: response.message
                    });
                    InventoryList();
                } else {
                    wisdomAlert({
                        type: 'error',
                        title: "Error!",
                        text: response.message
                    });
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
                        wisdomAlert({
                            type: 'error',
                            title: "Error",
                            text: errs
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
                        var floor='<option value="">Select Floor</option>';
                        $.each(response.data, function(i, v) {
                            floor += `<option value="${v}">${v}</option>`;
                        });
                        $("#AvailableFloor").html(floor);
                        window.wisdomDD.rebuild('#AvailableFloor');

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
                        var rooms='<option value="">Select Room</option>';
                        console.log(response.data);
                        $.each(response.data, function(i, v) {
                            rooms += `<option value="${v}">${v}</option>`;
                        });
                        $("#FloorWiseRoom").html(rooms);
                        window.wisdomDD.rebuild('#FloorWiseRoom');



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
@include('resorts._dropdown_script')
@endsection
