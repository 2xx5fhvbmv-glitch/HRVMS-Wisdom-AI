
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
                    <div class="d-flex justify-content-end">
                        <a href="#createTask-modal" data-bs-toggle="modal" class="btn btn-theme @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.event',config('settings.resort_permissions.create')) == false) d-none @endif">Create Task</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card calendar-card calendarAccom-card">
            <div class="row g-4">
                <div class="col-xxl-9 col-lg-8 ">
                    <div id="calendar" class="calendar-event"></div>
                </div>
                <div class="col-xxl-3 col-lg-4 ">
                    <div class="leaveUser-main" id="calsidebar">
                      
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createTask-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Create Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="CreateMaintenanceForm" data-parsley-validate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="select_date" class="form-label">SELECT DATE <span class="red-mark">*</span></label>
                        <input type="text" class="form-control datepicker" name="date" id="start_date" placeholder="Select Date" required data-parsley-required-message="Please select a date." />
                    </div>

                    <div class="mb-3">
                        <label for="building" class="form-label">BUILDING <span class="red-mark">*</span></label>
                        <select class="form-select select2t-none buildingAvailable"
                                name="building_id"
                                id="Building"
                                required
                                data-parsley-required-message="Please select building."
                                data-parsley-errors-container="#building_error">
                            <option value="">-- Select Building --</option>
                            @foreach ($Building as $b)
                                <option value="{{ $b->id }}">{{ $b->BuildingName }}</option>
                            @endforeach
                        </select>
                        <div id="building_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="floor" class="form-label">FLOOR <span class="red-mark">*</span></label>
                        <select class="form-select select2t-none AvailableFloor"
                                id="AvailableFloor_1"
                                data-id="1"
                                name="FloorNo"
                                required
                                data-parsley-required-message="Please select floor."
                                data-parsley-errors-container="#floor_error">
                        </select>
                        <div id="floor_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="roomNo" class="form-label">ROOM NO <span class="red-mark">*</span></label>
                        <select class="form-select select2t-none RoomNo"
                                id="RoomNo_1"
                                data-id="1"
                                name="RoomNo"
                                required
                                data-parsley-required-message="Please select room no."
                                data-parsley-errors-container="#room_error">
                        </select>
                        <div id="room_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="inventory" class="form-label">Inventory <span class="red-mark">*</span></label>
                        <select class="form-select select2t-none"
                                name="item_id"
                                id="inventory"
                                required
                                data-parsley-required-message="Please select inventory."
                                data-parsley-errors-container="#inventory_error">
                            <option value="">-- Select Inventory --</option>
                            @foreach ($InventoryModule as $b)
                                <option value="{{ $b->id }}">{{ $b->ItemName }}</option>
                            @endforeach
                        </select>
                        <div id="inventory_error"></div>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">DESCRIPTION</label>
                        <input type="text"
                            class="form-control"
                            name="descriptionIssues"
                            placeholder="Type Here"
                            required
                            data-parsley-required-message="Please enter description." />
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        // $('#CreateMaintenanceForm').parsley().validate();
           GetSliderList();
        FullCalanderEvent();
        $("#Building").select2({
            placeholder: "Select Building",
            allowClear: true,
        });
        $(".AvailableFloor").select2({
          placeholder:'Select Floor',
        });
        $(".RoomNo").select2({
          placeholder:'Select RoomNo',
        });
        $("#start_date").datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
            });



        $('#CreateMaintenanceForm').validate({
            rules: {
                item_id: {
                    required: true,
                },
                date: {
                    required: true,
                },
                building_id: {
                    required: true,
                },
                RoomNo: {
                    required: true,
                },
                FloorNo: {
                    required: true,
                },
                descriptionIssues:
                {
                    required: true,
                },
                priority:
                {
                    required: true,
                },
            },
            messages: {
                item_id: {
                    required: "Please Select amenity.",
                },
                date: {
                    required: "Please Select date.",
                },
                building_id: {
                    required: "Please Select Building.",
                },
                RoomNo: {
                    required: "Please Select Room No.",
                },
                FloorNo: {
                    required: "Please Select Floor No.",
                },

                descriptionIssues: {
                    required: "Please Enter description.",
                },
                priority: {
                    required: "Please Select Priority.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.CreateMaintenanceRequest') }}", // Your route for file upload
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        
                        $('#calendar').fullCalendar('refetchEvents');
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            form.reset();
                            GetSliderList();
                            $("#createTask-modal").modal('hide');

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
            }
        });

            //    equal heigth js
            function equalizeHeights() {
                // Get the elements
                const block1 = document.getElementById('calendar');
                const block2 = document.getElementById('calsidebar');

                // Get the height of block1
                const block1Height = block1.offsetHeight;

                // Set the height of block2 to match block1's height
                block2.style.height = block1Height + 'px';
            }

            window.onload = equalizeHeights; // Initial height adjustment

            // Adjust heights on window resize
            window.onresize = equalizeHeights;
    });

    $(document).on('change','.buildingAvailable',function()
    {
            var buildingId= $(this).val();
            $.ajax({
                url: "{{ route('resort.accommodation.config.getFloor') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}","buildingId":buildingId},
                success: function(response) {
                    if (response.success)
                    {
                        var floor='<option></option>';
                        $.each(response.data, function(i, v) {
                            console.log('Creating option:', v);
                            floor += `<option value="${v}">${v}</option>`;
                        });
                        $("#AvailableFloor_1").html(floor).select2({
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
    $(document).on('change','.AvailableFloor',function()
    {
        var building_id = $("#Building").val();
        var AvailableFloor = $("#AvailableFloor_1").val();

            $.ajax({
                url: "{{ route('resort.accommodation.config.GetFloorWiseRooms') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}","AvailableFloor":AvailableFloor,"building_id":building_id},
                success: function(response) {
                    if (response.success)
                    {
                        var floor='<option></option>';
                        $.each(response.data, function(i, v) {
                            console.log('Creating option:', v);
                            floor += `<option value="${v}">${v}</option>`;
                        });
                        $("#RoomNo_1").html(floor).select2({
                                                              placeholder: 'Select Room',
                                                              allowClear: true,
                                                            });
                    }
                    else
                    {
                        toastr.error(response.message, "Error",
                        {
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



function FullCalanderEvent()
{
    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next',
            center: 'title',
            right: 'month,basicWeek,basicDay',
        },
        defaultDate: new Date(),
        navLinks: true,
        editable: false,
        eventLimit: true,
        events: '{{ route("resort.accommodation.getClanderData") }}',
        eventRender: function(event, element) {
            element.attr('data-bs-toggle', 'tooltip');
            element.attr('title', event.title);
        },
        eventAfterAllRender: function() {
            $('[data-bs-toggle="tooltip"]').tooltip(); // Bootstrap 5
        }
    });

}

function GetSliderList()
{
    $.ajax({
                url: "{{ route('resort.accommodation.event.sidelist') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}"},
                success: function(response) {
                    if (response.success)
                    {
                     $("#calsidebar").html(response.data);
                    }
                    else
                    {
                        toastr.error(response.message, "Error",
                        {
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
}
</script>
@endsection
