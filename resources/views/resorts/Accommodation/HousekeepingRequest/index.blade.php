@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
            </div>
        </div>

        <div class="card mb-4">
            <form id="HousekeepingRequestForm" data-parsley-validate>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="btn-group" role="group" aria-label="Request against">
                            <button type="button" class="btn eb-btn-secondary active" id="modeEmployeeBtn">By Employee</button>
                            <button type="button" class="btn eb-btn-secondary" id="modeRoomBtn">By Building / Room</button>
                        </div>
                    </div>

                    <div class="row gx-4 g-3 mb-3" id="employeeModeBlock">
                        <div class="col-lg-6">
                            <label for="emp_id" class="form-label">EMPLOYEE<span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select" name="emp_id" id="emp_id"
                                data-parsley-errors-container="#emp_id_error">
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->resortAdmin->first_name ?? '' }} {{ $emp->resortAdmin->last_name ?? '' }} ({{ $emp->Emp_id }})</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#emp_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Employee</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Employee">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Employee</span></div>
                                        @foreach($employees as $emp)
                                        <div class="dd-item" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ $emp->resortAdmin->first_name ?? '' }} {{ $emp->resortAdmin->last_name ?? '' }} ({{ $emp->Emp_id }})</span></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="emp_id_error"></div>
                        </div>
                        <div class="col-lg-6">
                            <div id="empAccommodationInfo" class="d-none">
                                <label class="form-label">ROOM (AUTO-FILLED)</label>
                                <div class="card p-2 px-3 bg-light border-0">
                                    <small class="text-muted">Selected employee's current accommodation</small>
                                    <div id="empAccommodationText" class="fw-semibold"></div>
                                </div>
                            </div>
                            <div id="empNoAccommodation" class="d-none text-danger small">This employee has no accommodation assigned — a request cannot be raised until one is.</div>
                        </div>
                    </div>

                    <div class="row gx-4 g-3 mb-3 d-none" id="roomModeBlock">
                        <div class="col-md-4">
                            <label for="building_id" class="form-label">BUILDING<span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select buildingAvailable" name="building_id" id="building_1"
                                data-parsley-errors-container="#Building">
                                <option value="">Select Building</option>
                                @foreach($buildings as $b)
                                    <option value="{{ $b->id }}">{{ $b->BuildingName }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#building_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Building</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Building">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Building</span></div>
                                        @foreach($buildings as $b)
                                        <div class="dd-item" role="option" data-value="{{ $b->id }}"><span class="dd-nm">{{ $b->BuildingName }}</span></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div id="Building"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="AvailableFloor_1" class="form-label">FLOOR<span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select AvailableFloor" data-id="1" id="AvailableFloor_1" name="FloorNo"></select>
                            <div class="dd" data-target="#AvailableFloor_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Floor</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Floor"><div class="dd-scroll"></div></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="RoomNo_1" class="form-label">ROOM NO.<span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select RoomNo" data-id="1" id="RoomNo_1" name="RoomNo"></select>
                            <div class="dd" data-target="#RoomNo_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Room</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Room"><div class="dd-scroll"></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="row gx-4 g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">ELIGIBLE SERVICES<span class="red-mark">*</span></label>
                            <div id="serviceCheckboxes" class="d-flex flex-wrap gap-3">
                                <small class="text-muted">Select an employee, or a building &amp; room, to see eligible services.</small>
                            </div>
                            <div id="service_ids_error"></div>
                        </div>
                    </div>

                    <div class="row gx-4 g-3 mb-3">
                        <div class="col-md-4">
                            <label for="scheduled_date" class="form-label">DATE<span class="red-mark">*</span></label>
                            <input type="date" class="form-control" name="scheduled_date" id="scheduled_date" required
                                data-parsley-required-message="Please select a date.">
                        </div>
                        <div class="col-md-4">
                            <label for="scheduled_time" class="form-label">TIME<span class="red-mark">*</span></label>
                            <input type="time" class="form-control" name="scheduled_time" id="scheduled_time" required
                                data-parsley-required-message="Please select a time.">
                        </div>
                        <div class="col-md-4"></div>
                        <div class="col-md-8">
                            <label for="remarks" class="form-label">SPECIAL INSTRUCTIONS <small class="text-muted">(Optional)</small></label>
                            <textarea class="form-control" id="remarks" rows="3" name="remarks" placeholder="Any special instructions"></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn eb-btn-primary ms-1 btn-sm">Submit Request</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Housekeeping Requests</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-housekeepingRequests w-100">
                        <thead>
                            <tr>
                                <th>Requested For</th>
                                <th>Service</th>
                                <th>Scheduled</th>
                                <th>Raised By</th>
                                <th>Status</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('#HousekeepingRequestForm').parsley();

    function switchMode(toRoom) {
        $('#modeEmployeeBtn').toggleClass('active', !toRoom);
        $('#modeRoomBtn').toggleClass('active', toRoom);
        $('#employeeModeBlock').toggleClass('d-none', toRoom);
        $('#roomModeBlock').toggleClass('d-none', !toRoom);

        if (toRoom) {
            $('#emp_id').val('').trigger('change');
            loadEligibleServices(null);
        } else {
            $('#building_1').val('').trigger('change');
            $('#serviceCheckboxes').html('<small class="text-muted">Select an employee, or a building &amp; room, to see eligible services.</small>');
        }
    }

    $('#modeEmployeeBtn').on('click', function () { switchMode(false); });
    $('#modeRoomBtn').on('click', function () { switchMode(true); });

    function loadEligibleServices(empId) {
        $.ajax({
            url: "{{ route('resort.accommodation.HousekeepingRequestEligibleServices') }}",
            type: "POST",
            data: { _token: "{{ csrf_token() }}", emp_id: empId },
            success: function (response) {
                var html = '';
                if (response.success && response.data.length) {
                    $.each(response.data, function (i, svc) {
                        html += '<label class="form-check form-check-inline border rounded px-3 py-2">' +
                            '<input type="checkbox" class="form-check-input" name="service_ids[]" value="' + svc.id + '"> ' +
                            '<span class="form-check-label">' + svc.name + '</span></label>';
                    });
                } else {
                    html = '<small class="text-muted">' + (response.message || 'No eligible services found.') + '</small>';
                }
                $('#serviceCheckboxes').html(html);
            }
        });
    }

    $('#emp_id').on('change', function () {
        var empId = $(this).val();
        if (!empId) {
            $('#empAccommodationInfo').addClass('d-none');
            $('#empNoAccommodation').addClass('d-none');
            $('#serviceCheckboxes').html('<small class="text-muted">Select an employee, or a building &amp; room, to see eligible services.</small>');
            return;
        }

        loadEligibleServices(empId);

        $.ajax({
            url: "{{ route('resort.accommodation.getEmployeeAccommodation') }}",
            type: "GET",
            data: { emp_id: empId },
            success: function (response) {
                if (response.success && response.has_accommodation) {
                    $('#empAccommodationText').text(response.building_name + (response.floor ? ', Floor ' + response.floor : '') + (response.room ? ', Room ' + response.room : ''));
                    $('#empAccommodationInfo').removeClass('d-none');
                    $('#empNoAccommodation').addClass('d-none');
                } else {
                    $('#empAccommodationInfo').addClass('d-none');
                    $('#empNoAccommodation').removeClass('d-none');
                }
            }
        });
    });

    $(document).on('change', '.buildingAvailable', function () {
        var buildingId = $(this).val();
        $.ajax({
            url: "{{ route('resort.accommodation.config.getFloor') }}",
            type: "get",
            data: { "_token": "{{ csrf_token() }}", "buildingId": buildingId },
            success: function (response) {
                if (response.success) {
                    var floor = '<option value="">Select Floor</option>';
                    $.each(response.data, function (i, v) { floor += '<option value="' + v + '">' + v + '</option>'; });
                    $('#AvailableFloor_1').html(floor);
                    window.wisdomDD.rebuild('#AvailableFloor_1');
                }
            }
        });
    });

    $(document).on('change', '.AvailableFloor', function () {
        var building_id = $('#building_1').val();
        var AvailableFloor = $('#AvailableFloor_1').val();
        $.ajax({
            url: "{{ route('resort.accommodation.config.GetFloorWiseRooms') }}",
            type: "get",
            data: { "_token": "{{ csrf_token() }}", "AvailableFloor": AvailableFloor, "building_id": building_id },
            success: function (response) {
                if (response.success) {
                    var room = '<option value="">Select Room</option>';
                    $.each(response.data, function (i, v) { room += '<option value="' + v + '">' + v + '</option>'; });
                    $('#RoomNo_1').html(room);
                    window.wisdomDD.rebuild('#RoomNo_1');
                }
            }
        });
    });

    $(document).on('submit', '#HousekeepingRequestForm', function (e) {
        e.preventDefault();
        var form = $(this);
        if (!form.parsley().validate()) { return false; }

        if ($('#serviceCheckboxes input[name="service_ids[]"]:checked').length === 0) {
            toastr.error('Please select at least one service.', 'Error', { positionClass: 'toast-bottom-right' });
            return false;
        }
        var isRoomMode = $('#modeRoomBtn').hasClass('active');
        if (isRoomMode && !$('#building_1').val()) {
            toastr.error('Please select a building.', 'Error', { positionClass: 'toast-bottom-right' });
            return false;
        }
        if (!isRoomMode && !$('#emp_id').val()) {
            toastr.error('Please select an employee.', 'Error', { positionClass: 'toast-bottom-right' });
            return false;
        }

        var formData = form.serializeArray();
        if (!isRoomMode) {
            formData = formData.filter(function (f) { return f.name !== 'building_id' && f.name !== 'FloorNo' && f.name !== 'RoomNo'; });
        } else {
            formData = formData.filter(function (f) { return f.name !== 'emp_id'; });
        }

        $.ajax({
            url: "{{ route('resort.accommodation.HousekeepingRequestStore') }}",
            type: "POST",
            data: $.param(formData) + '&_token=' + "{{ csrf_token() }}",
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    form[0].reset();
                    form.parsley().reset();
                    $('#serviceCheckboxes').html('<small class="text-muted">Select an employee, or a building &amp; room, to see eligible services.</small>');
                    $('#empAccommodationInfo, #empNoAccommodation').addClass('d-none');
                    $('.table-housekeepingRequests').DataTable().ajax.reload();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (response) {
                var errors = response.responseJSON;
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function (key, error) { errs += error + '<br>'; });
                } else {
                    errs = (errors && errors.message) ? errors.message : 'Server error';
                }
                toastr.error(errs, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $('.table-housekeepingRequests').DataTable({
        processing: true,
        serverSide: true,
        order: [[2, 'desc']],
        ajax: "{{ route('resort.accommodation.HousekeepingRequestList') }}",
        columns: [
            { data: 'RequestedFor', name: 'RequestedFor' },
            { data: 'service_name', name: 'service_name' },
            { data: 'ScheduledOn', name: 'ScheduledOn' },
            { data: 'RaisedBy', name: 'RaisedBy' },
            { data: 'status', name: 'status' },
            { data: 'remarks', name: 'remarks' },
        ]
    });
});
</script>
@include('resorts._dropdown_script')
@endsection
