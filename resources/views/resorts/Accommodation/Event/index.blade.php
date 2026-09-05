
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #accommodation-event-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #accommodation-event-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="accommodation-event-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#createTask-modal" data-bs-toggle="modal" class="btn eb-btn-accent @if(App\Helpers\Common::checkRouteWisePermission('resort.accommodation.event',config('settings.resort_permissions.create')) == false) d-none @endif">Create Task</a>
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
                        <select class="form-select dd-native-select buildingAvailable"
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
                        <div class="dd" data-target="#Building">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">-- Select Building --</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Building">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a building…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">-- Select Building --</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @foreach ($Building as $b)
                                    <div class="dd-item" role="option" data-value="{{ $b->id }}"><span class="dd-nm">{{ $b->BuildingName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div id="building_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="floor" class="form-label">FLOOR <small class="text-muted">(Optional)</small></label>
                        <select class="form-select dd-native-select AvailableFloor"
                                id="AvailableFloor_1"
                                data-id="1"
                                name="FloorNo"
                                data-parsley-errors-container="#floor_error">
                        </select>
                        <div class="dd" data-target="#AvailableFloor_1">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Floor</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Floor">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a floor…"></div>
                                <div class="dd-scroll"></div>
                            </div>
                        </div>
                        <div id="floor_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="roomNo" class="form-label">ROOM NO <small class="text-muted">(Optional)</small></label>
                        <select class="form-select dd-native-select RoomNo"
                                id="RoomNo_1"
                                data-id="1"
                                name="RoomNo"
                                data-parsley-errors-container="#room_error">
                        </select>
                        <div class="dd" data-target="#RoomNo_1">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Room</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Room">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a room…"></div>
                                <div class="dd-scroll"></div>
                            </div>
                        </div>
                        <div id="room_error"></div>
                    </div>

                    <div class="mb-3">
                        <div class="row g-3">
                            <div class="col-6">
                                <label for="start_time" class="form-label">START TIME <span class="red-mark">*</span></label>
                                <input type="time" class="form-control" name="start_time" id="start_time"
                                    required data-parsley-required-message="Please select start time." />
                            </div>
                            <div class="col-6">
                                <label for="end_time" class="form-label">END TIME <span class="red-mark">*</span></label>
                                <input type="time" class="form-control" name="end_time" id="end_time"
                                    required data-parsley-required-message="Please select end time." />
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="priority" class="form-label">PRIORITY <span class="red-mark">*</span></label>
                        <select class="form-select dd-native-select" name="priority" id="priority"
                            required data-parsley-required-message="Please select priority.">
                            <option value="">-- Select Priority --</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                        <div class="dd" data-target="#priority">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">-- Select Priority --</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Priority">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">-- Select Priority --</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="High"><span class="dd-nm">High</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="Medium"><span class="dd-nm">Medium</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="Low"><span class="dd-nm">Low</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">DESCRIPTION <span class="red-mark">*</span></label>
                        <input type="text"
                            class="form-control"
                            name="descriptionIssues"
                            placeholder="Type Here"
                            required
                            data-parsley-required-message="Please enter description." />
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                    <button type="submit" class="btn eb-btn-primary">Submit</button>
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
    $(document).ready(function () {
        // $('#CreateMaintenanceForm').parsley().validate();
           GetSliderList();
        FullCalanderEvent();
        flatpickr('#start_date', {
                dateFormat: 'd/m/Y',
                allowInput: true,
                appendTo: document.body
            });



        $('#CreateMaintenanceForm').validate({
            rules: {
                date: {
                    required: true,
                },
                building_id: {
                    required: true,
                },
                start_time: {
                    required: true,
                },
                end_time: {
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
                date: {
                    required: "Please select date.",
                },
                building_id: {
                    required: "Please select building.",
                },
                start_time: {
                    required: "Please select start time.",
                },
                end_time: {
                    required: "Please select end time.",
                },
                descriptionIssues: {
                    required: "Please enter description.",
                },
                priority: {
                    required: "Please select priority.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form);

                $.ajax({
                    url: "{{ route('resort.accommodation.StoreMaintenanceRequest') }}",
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
                            window.wisdomDD.sync('#Building');
                            window.wisdomDD.sync('#priority');
                            window.wisdomDD.rebuild('#AvailableFloor_1');
                            window.wisdomDD.rebuild('#RoomNo_1');
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
                        var floor='<option value="">Select Floor</option>';
                        $.each(response.data, function(i, v) {
                            console.log('Creating option:', v);
                            floor += `<option value="${v}">${v}</option>`;
                        });
                        $("#AvailableFloor_1").html(floor);
                        window.wisdomDD.rebuild('#AvailableFloor_1');

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
                        var floor='<option value="">Select Room</option>';
                        $.each(response.data, function(i, v) {
                            console.log('Creating option:', v);
                            floor += `<option value="${v}">${v}</option>`;
                        });
                        $("#RoomNo_1").html(floor);
                        window.wisdomDD.rebuild('#RoomNo_1');
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
@include('resorts._dropdown_script')
@endsection
