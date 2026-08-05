
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
                    <a href="{{ route('resort.accommodation.MaintanaceRequestlist') }}" class="btn eb-btn-secondary">View All</a>
                </div>
            </div>
        </div>

        <div class="card">
        <form id="CreateMaintenanceForm" data-parsley-validate enctype="multipart/form-data">   
            <div class="row gx-4 g-3 mb-3">
                <div class="col-lg-6">
                    <label for="raised_by" class="form-label">RAISED BY (EMPLOYEE)<span class="red-mark">*</span></label>
                    <select class="form-select select2t-none" name="raised_by" id="raised_by"
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
                        @endphp
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                @if($emp->Admin_Parent_id == Auth::guard('resort-admin')->user()->id) selected @endif>
                                {{ $emp->resortAdmin->first_name }} {{ $emp->resortAdmin->last_name }} ({{ $emp->Emp_id }})
                            </option>
                        @endforeach
                    </select>
                    <div id="raised_by_error"></div>
                </div>

                <div class="col-lg-6">
                    <label for="amenity" class="form-label">AFFECTED AMENITY <small class="text-muted">(Optional)</small></label>
                    <select class="form-select select2t-none" name="item_id" id="amenity"
                        data-parsley-errors-container="#affected_amenity">
                        <option></option>
                        @if($InventoryItems->isNotEmpty())
                            @foreach ($InventoryItems as $i)
                                <option value="{{ $i->id }}">{{ $i->ItemName }}</option>
                            @endforeach
                        @endif
                    </select>
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
                    <select class="form-select select2t-none buildingAvailable" name="building_id" id="building_1"
                        required
                           data-parsley-trigger="submit"
                        data-parsley-required-message="Please select Building."
                        data-parsley-errors-container="#Building">
                        <option></option>
                        @if($Building->isNotEmpty())
                            @foreach ($Building as $b)
                                <option value="{{ $b->id }}">{{ $b->BuildingName }}</option>
                            @endforeach
                        @endif
                    </select>
                    <div id="Building"></div>
                </div>

                <div class="col-sm-6">
                    <label for="floor" class="form-label">FLOOR <span class="red-mark">*</span></label>
                    <select class="form-select select2t-none AvailableFloor" id="AvailableFloor_1" data-id="1"
                        name="FloorNo" required
                        data-parsley-trigger="submit"
                        data-parsley-required-message="Please Select Floor."
                        data-parsley-errors-container="#AvailableFloor">
                    </select>
                    <div id="AvailableFloor"></div>
                </div>

                <div class="col-sm-6">
                    <label for="roomNo" class="form-label">ROOM NO.<span class="red-mark">*</span></label>
                    <select class="form-select select2t-none RoomNo" data-id="1" id="RoomNo_1"
                        name="RoomNo" required
                        data-parsley-trigger="submit"
                        data-parsley-required-message="Please Select Room."
                        data-parsley-errors-container="#RoomNo">
                    </select>
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
                    <select class="form-select select2t-none" id="priority" name="priority"
                        required
                        data-parsley-required-message="Please select priority."
                        data-parsley-errors-container="#priority_error">
                        <option></option>
                        <option value="High">High</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                    </select>
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
    $("#raised_by").select2({
        placeholder: "Select Employee",
        allowClear: true
    });
    $("#amenity").select2({
        placeholder: "Select Amenity",
        allowClear: true
    });
    $("#building").select2({
        placeholder: "Select Building",
        allowClear: true
    });


    $("#priority").select2({
       placeholder:'Select Priority',
    });
    $(".buildingAvailable").select2({
       placeholder:'Select Building',
    });
    $(".AvailableFloor").select2({
       placeholder:'Select Floor',
    });
    $(".RoomNo").select2({
       placeholder:'Select RoomNo',
    });

    
    $('.select2t-none').on('change', function () {
        $(this).parsley().validate();
    });

    // Auto-fill accommodation when employee is selected
    $('#raised_by').on('change', function() {
        var empId = $(this).val();
        if (!empId) {
            // Reset fields to editable
            $('#building_1').prop('disabled', false).val('').trigger('change');
            $('#AvailableFloor_1').prop('disabled', false).html('<option></option>');
            $('#RoomNo_1').prop('disabled', false).html('<option></option>');
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
                    if ($('#building_1').data('select2')) {
                        $('#building_1').trigger('change.select2');
                    }
                    $('#building_1').prop('disabled', true);

                    // Set floor and room directly
                    $('#AvailableFloor_1').html('<option value="' + autoFloor + '" selected>' + autoFloor + '</option>');
                    $('#AvailableFloor_1').prop('disabled', true);

                    $('#RoomNo_1').html('<option value="' + autoRoom + '" selected>' + autoRoom + '</option>');
                    $('#RoomNo_1').prop('disabled', true);

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
                    $('#AvailableFloor_1').prop('disabled', false).html('<option></option>');
                    $('#RoomNo_1').prop('disabled', false).html('<option></option>');
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
        var building_id = $("#building_1").val();
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
                        $("#RoomNo_1").html(floor) .select2({
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
@endsection
