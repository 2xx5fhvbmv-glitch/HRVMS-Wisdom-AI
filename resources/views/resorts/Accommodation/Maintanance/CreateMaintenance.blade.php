
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #maintenance-request-create-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #maintenance-request-create-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="maintenance-request-create-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.accommodation.MaintanaceRequestlist') }}" class="btn eb-btn-secondary">View All</a>
                </div>
            </div>
        </div>

        <div class="card">
        <form id="CreateMaintenanceForm" data-parsley-validate enctype="multipart/form-data">   
            <div class="row gx-4 g-3 mb-3">
                <div class="col-lg-6">
                    <label for="raised_by" class="form-label">RAISED BY (EMPLOYEE)<span class="red-mark">*</span></label>
                    <select class="form-select dd-native-select" name="raised_by" id="raised_by"
                        required
                        data-parsley-trigger="submit"
                        data-parsley-required-message="Please select an employee."
                        data-parsley-errors-container="#raised_by_error">
                        <option></option>
                        @php
                            $employees = \App\Models\Employee::with('resortAdmin')
                                ->where('resort_id', Auth::guard('resort-admin')->user()->resort_id)
                                ->where('status', 'Active')
                                ->get();
                            $selectedEmp = $employees->first(fn($emp) => $emp->Admin_Parent_id == Auth::guard('resort-admin')->user()->id);
                        @endphp
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                @if($emp->Admin_Parent_id == Auth::guard('resort-admin')->user()->id) selected @endif>
                                {{ $emp->resortAdmin->first_name }} {{ $emp->resortAdmin->last_name }} ({{ $emp->Emp_id }})
                            </option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#raised_by">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">{{ $selectedEmp ? $selectedEmp->resortAdmin->first_name.' '.$selectedEmp->resortAdmin->last_name.' ('.$selectedEmp->Emp_id.')' : 'Select Employee' }}</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Employee">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item{{ $selectedEmp ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($employees as $emp)
                                <div class="dd-item{{ ($selectedEmp && $selectedEmp->id === $emp->id) ? ' active' : '' }}" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ $emp->resortAdmin->first_name }} {{ $emp->resortAdmin->last_name }} ({{ $emp->Emp_id }})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div id="raised_by_error"></div>
                </div>

                <div class="col-lg-6">
                    <label for="amenity" class="form-label">AFFECTED AMENITY <small class="text-muted">(Optional)</small></label>
                    <select class="form-select dd-native-select" name="item_id" id="amenity"
                        data-parsley-errors-container="#affected_amenity">
                        <option></option>
                        @if($InventoryItems->isNotEmpty())
                            @foreach ($InventoryItems as $i)
                                <option value="{{ $i->id }}">{{ $i->ItemName }}</option>
                            @endforeach
                        @endif
                    </select>
                    <div class="dd" data-target="#amenity">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select Amenity</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Amenity">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an amenity…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Amenity</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @if($InventoryItems->isNotEmpty())
                                    @foreach ($InventoryItems as $i)
                                    <div class="dd-item" role="option" data-value="{{ $i->id }}"><span class="dd-nm">{{ $i->ItemName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div id="affected_amenity"></div>
                </div>

                                <!-- IMAGE UPLOAD -->
                <div class="col-lg-3 col-sm-6">
                    <label for="image" class="form-label">IMAGE</label>
                    <div class="uploadFile-block">
                        <div class="uploadFile-btn">
                            <a href="#" class="btn eb-btn-accent btn-sm">Upload File</a>
                            <input type="file" name="Image" id="image_check" accept="image/*"
                                data-parsley-filemimetypes="image/jpeg, image/png, image/gif, image/svg+xml, image/webp, image/heic, image/heif"
                                data-parsley-errors-container="#image_error">
                        </div>
                        <div class="uploadFile-text">PNG, JPEG</div>
                        <div id="image_error"></div>
                        <div id="image_filename" class="text-info mt-1"></div> <!-- Filename will appear here -->
                    </div>
                </div>

                <!-- VIDEO UPLOAD -->
                <div class="col-lg-3 col-sm-6">
                    <label for="video" class="form-label">VIDEO</label>
                    <div class="uploadFile-block">
                        <div class="uploadFile-btn">
                            <a href="#" class="btn eb-btn-accent btn-sm">Upload File</a>
                            <input type="file" name="Video" id="video" accept="video/*"
                                data-parsley-filemimetypes="video/mp4, video/quicktime"
                                data-parsley-errors-container="#video_error">
                        </div>
                        <div class="uploadFile-text">mp4, MOV</div>
                        <div id="video_error"></div>
                        <div id="video_filename" class="text-info mt-1"></div> <!-- Filename will appear here -->
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="building" class="form-label">BUILDING<span class="red-mark">*</span></label>
                    <select class="form-select dd-native-select buildingAvailable" name="building_id" id="building_1"
                        required
                           data-parsley-trigger="submit"
                        data-parsley-required-message="Please select building."
                        data-parsley-errors-container="#Building">
                        <option></option>
                        @if($Building->isNotEmpty())
                            @foreach ($Building as $b)
                                <option value="{{ $b->id }}">{{ $b->BuildingName }}</option>
                            @endforeach
                        @endif
                    </select>
                    <div class="dd" data-target="#building_1">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select Building</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Building">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a building…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Building</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @if($Building->isNotEmpty())
                                    @foreach ($Building as $b)
                                    <div class="dd-item" role="option" data-value="{{ $b->id }}"><span class="dd-nm">{{ $b->BuildingName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div id="Building"></div>
                </div>

                <div class="col-sm-6">
                    <label for="floor" class="form-label">FLOOR <span class="red-mark">*</span></label>
                    <select class="form-select dd-native-select AvailableFloor" id="AvailableFloor_1" data-id="1"
                        name="FloorNo" required
                        data-parsley-trigger="submit"
                        data-parsley-required-message="Please select floor."
                        data-parsley-errors-container="#AvailableFloor">
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
                    <div id="AvailableFloor"></div>
                </div>

                <div class="col-sm-6">
                    <label for="roomNo" class="form-label">ROOM NO.<span class="red-mark">*</span></label>
                    <select class="form-select dd-native-select RoomNo" data-id="1" id="RoomNo_1"
                        name="RoomNo" required
                        data-parsley-trigger="submit"
                        data-parsley-required-message="Please select room."
                        data-parsley-errors-container="#RoomNo">
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
                    <div id="RoomNo"></div>
                </div>

                <div class="col-md-6">
                    <label for="desIssues" class="form-label">DESCRIPTION OF ISSUE<span class="red-mark">*</span></label>
                    <textarea class="form-control" id="desIssues" rows="4" name="descriptionIssues"
                        required
                        data-parsley-required-message="Please describe the issue."
                        placeholder="Affected Amenity"></textarea>
                </div>

                <div class="col-md-6">
                    <label for="priority" class="form-label">PRIORITY<span class="red-mark">*</span></label>
                    <select class="form-select dd-native-select" id="priority" name="priority"
                        required
                        data-parsley-required-message="Please select priority."
                        data-parsley-errors-container="#priority_error">
                        <option></option>
                        <option value="High">High</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                    </select>
                    <div class="dd" data-target="#priority">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select Priority</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Priority">
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Priority</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                <div class="dd-item" role="option" data-value="High"><span class="dd-nm">High</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                <div class="dd-item" role="option" data-value="Low"><span class="dd-nm">Low</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                <div class="dd-item" role="option" data-value="Medium"><span class="dd-nm">Medium</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            </div>
                        </div>
                    </div>
                    <div id="priority_error"></div>
                </div>
            </div>

            <div class="d-none d-md-block" style="height: 252px;"></div>
            <div class="card-footer text-end">
                <button type="submit" class="btn eb-btn-primary ms-1 btn-sm">Submit</button>
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
$(document).ready(function() {
    $('#CreateMaintenanceForm').parsley();

            $('#image_check').on('change', function () {
                const fileName = this.files[0]?.name || '';
                $('#image_filename').text(fileName);
            });

            $('#video').on('change', function () {
                const fileName = this.files[0]?.name || '';
                $('#video_filename').text(fileName);
            });
    $('.dd-native-select').on('change', function () {
        $(this).parsley().validate();
    });

    // Auto-fill accommodation when employee is selected
    $('#raised_by').on('change', function() {
        var empId = $(this).val();
        if (!empId) {
            // Reset fields to editable
            $('#building_1').prop('disabled', false).val('').trigger('change');
            $('#building_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
            $('#AvailableFloor_1').prop('disabled', false).html('<option value="">Select Floor</option>');
            $('#AvailableFloor_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
            window.wisdomDD.rebuild('#AvailableFloor_1');
            $('#RoomNo_1').prop('disabled', false).html('<option value="">Select Room</option>');
            $('#RoomNo_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
            window.wisdomDD.rebuild('#RoomNo_1');
            $('.auto-filled-badge').remove();
            return;
        }

        $.ajax({
            url: "{{ route('resort.accommodation.getEmployeeAccommodation') }}",
            type: "GET",
            data: { emp_id: empId },
            success: function(response) {
                if (response.success && response.has_accommodation) {
                    var autoFloor = response.floor;
                    var autoRoom = response.room;
                    var autoBuildingId = response.building_id;

                    // Set building without triggering the AJAX floor fetch
                    $('#building_1').val(autoBuildingId);
                    window.wisdomDD.sync('#building_1');
                    $('#building_1').prop('disabled', true);
                    $('#building_1').siblings('.dd').find('.dd-trigger').prop('disabled', true);

                    // Set floor and room directly
                    $('#AvailableFloor_1').html('<option value="' + autoFloor + '" selected>' + autoFloor + '</option>');
                    window.wisdomDD.rebuild('#AvailableFloor_1');
                    $('#AvailableFloor_1').prop('disabled', true);
                    $('#AvailableFloor_1').siblings('.dd').find('.dd-trigger').prop('disabled', true);

                    $('#RoomNo_1').html('<option value="' + autoRoom + '" selected>' + autoRoom + '</option>');
                    window.wisdomDD.rebuild('#RoomNo_1');
                    $('#RoomNo_1').prop('disabled', true);
                    $('#RoomNo_1').siblings('.dd').find('.dd-trigger').prop('disabled', true);

                    // Add hidden inputs so disabled fields still submit
                    $('.auto-filled-hidden').remove();
                    $('#CreateMaintenanceForm').append(
                        '<input type="hidden" name="building_id" value="' + autoBuildingId + '" class="auto-filled-hidden">' +
                        '<input type="hidden" name="FloorNo" value="' + autoFloor + '" class="auto-filled-hidden">' +
                        '<input type="hidden" name="RoomNo" value="' + autoRoom + '" class="auto-filled-hidden">'
                    );

                    // Show auto-filled indicator
                    $('.auto-filled-badge').remove();
                    $('#building_1').closest('.col-md-6, .col-sm-6').append('<small class="text-success auto-filled-badge"><i class="fa-solid fa-check-circle"></i> Auto-filled from accommodation</small>');
                } else {
                    // No accommodation - reset to editable
                    $('#building_1').prop('disabled', false).val('').trigger('change');
                    $('#building_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
                    $('#AvailableFloor_1').prop('disabled', false).html('<option value="">Select Floor</option>');
                    $('#AvailableFloor_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
                    window.wisdomDD.rebuild('#AvailableFloor_1');
                    $('#RoomNo_1').prop('disabled', false).html('<option value="">Select Room</option>');
                    $('#RoomNo_1').siblings('.dd').find('.dd-trigger').prop('disabled', false);
                    window.wisdomDD.rebuild('#RoomNo_1');
                    $('.auto-filled-badge').remove();
                    $('.auto-filled-hidden').remove();
                }
            }
        });
    });

    $(document).on('submit', '#CreateMaintenanceForm', function (e) {
     e.preventDefault();
        var form = $(this);

        // Only trigger validation here
            if (!form.parsley().validate()) {
                return false;
            }

        var formData = new FormData(this);

        $.ajax({
            url: "{{ route('resort.accommodation.StoreMaintenanceRequest') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    form[0].reset();
                    form.parsley().reset();
                    window.location.href = "{{ route('resort.accommodation.MaintanaceRequestlist') }}";

                } else {
            
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                }
            },
            error: function (response) {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function (key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });


    
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
        var building_id = $("#building_1").val();
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


    function PendingTaskList()
    {
        if ($.fn.dataTable.isDataTable('.table-accomMainten')) {
            $('.table-accomMainten').DataTable().destroy();
        }

        var TableAccomMainten = $('.table-accomMainten').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[8, 'desc']],
            ajax: '{{ route("resort.accommodation.HrGetMaintananceRequest") }}',
            columns: [
                { data: 'RequestedBy', name: 'RequestedBy', className: 'text-nowrap' },
                { data: 'EffectedAmenity', name: 'EffectedAmenity', className: 'text-nowrap' },
                { data: 'Location', name: 'Location', className: 'text-nowrap' },
                { data: 'Priority', name: 'Priority', className: 'text-nowrap' },
                { data: 'AssgingedStaff', name: 'AssgingedStaff ', className: 'text-nowrap' },
                { data: 'Date', name: 'Date', className: 'text-nowrap' },
                { data: 'Status', name: 'Status', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
</script>
@include('resorts._dropdown_script')
@endsection
