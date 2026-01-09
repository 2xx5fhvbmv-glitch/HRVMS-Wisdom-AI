
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
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-4 ">
                <div class="card">
                    <div class="card-title">
                        <h3> BUILDING NAME </h3>
                        <div class="text-end">
                            <a href="{{ route('resort.accommodation.config.BuildingIndex') }}" class="a-link">View All</a>
                        </div>
                    </div>
                    <form id="BuildingForm">
                        @csrf
                        <div class="row g-xl-4 g-3 ">
                            <div class="col-md-12">
                                <label for="typeAccom" class=" form-label">Building Name</label>
                                <input type="text" class="form-control" name="BuildingName" id="BuildingName" placeholder="Building Name">
                            </div>
                        </div>

                        <div class="card-footer text-end mt-3">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-8 ">
                <div class="card">
                    <div class="card-title">
                        <h3> Floor And Room Name </h3>
                        <div class="text-end">
                            <a href="{{ route('resort.accommodation.config.FloorRoomIndex') }}" class="a-link">View All</a>
                        </div>
                    </div>
                    <form id="FloorAndRoomForm">
                        @csrf
                        <div class="row g-xl-4 g-3 ">
                            <div class="col-md-4 col-md-4 col-sm-6">
                                <label for="typeAccom" class=" form-label">Select Building</label>
                                <select class="form-select select2t-none" id="building_id" name="building_id">
                                    <option value=""></option>
                                    @if($BuildingData->isNotEmpty())
                                            @foreach ($BuildingData as $a)
                                                <option value="{{ $a->id }}">{{ $a->BuildingName }}</option>
                                            @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6">
                                <label for="floor" class="form-label">FLOOR</label>
                                <input type="number" min="1" class="form-control" id="Floor_1" name="Floor" placeholder="1 st floort ">

                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6">
                                <label for="roomNo" class=" form-label">ROOM NO.</label>
                                <input type="number" min="1" class="form-control" id="Room" name="Room" placeholder="1st floort">

                            </div>

                        </div>

                        <div class="card-footer text-end mt-3">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-12 ">
                <div class="card">
                    <div class="card-title">
                        <h3>Available Accommodation</h3>
                    </div>
                    <div class="availabel-main">
                        <div class="availabel-block mb-3">
                            <form id="AvailableAccommodationFileForm">
                                @csrf
                                <div class="row g-2 align-items-center mb-3">
                                    <div class="col-auto">
                                        <a href="{{ route('resort.accommodation.config.AccommodationTemplete') }}" class="btn btn-themeSkyblue btn-sm">Download Template</a>
                                    </div>
                                    <div class="col-auto">
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn me-0">
                                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                <input type="file" name="AvailableAccommodationFile" id="uploadFile" accept=".csv, .xls, .xlsx">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-themeBlue btn-sm availableFormSubmit">Submit</button>
                                    </div>
                                </div>
                            </form>
                            <form id="AvailableAccommodationForm" data-parsley-validate>
                                <div class="row g-xl-4 g-3 appendAvailableAccommodation">
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="building" class="form-label">BUILDING NAME</label>

                                        <select class="form-select select2t-none buildingAvailable" data-flag="A" id="building_1"data-id= "1" 
                                        name="BuildingName[1]" placeholder=" Select Building"
                                         required data-parsley-type="digits" 
                                        data-parsley-required-message="Building name is required." 
                                        
                                        data-parsley-errors-container="#type_accom_error_1">
                                            <option> </option>
                                            @if($BuildingData->isNotEmpty())
                                                    @foreach ($BuildingData as $a)
                                                        <option value="{{ $a->id }}">{{ $a->BuildingName }}</option>
                                                    @endforeach
                                            @endif
                                        </select>
                                       <div id="type_accom_error_1"></div>

                                    </div>


                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="floor" class="form-label">FLOOR</label>
                                        <select class="form-select select2t-none AvailableFloor" data-flag="A" id="AvailableFloor_1"
                                        data-id= "1" name="Floor[1]" placeholder=" Select Building" required   required 
                                         data-parsley-trigger="keyup" data-parsley-required-message="Please Select Floor."
                                             data-parsley-errors-container="#type_accom_error_2"
                                         >

                                        </select>
                                        <div id="type_accom_error_2"></div>


                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomNo" class=" form-label">ROOM NO.</label>
                                        <select class="form-select select2t-none RoomNo" data-id= "1" id="RoomNo_1" name="RoomNo[1]" 
                                         required    data-parsley-type="digits" data-parsley-trigger="keyup" 
                                         data-parsley-required-message="Please Select Floor."  data-parsley-errors-container="#type_accom_error_3">

                                        </select>
                                        <div id="type_accom_error_3"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="typeAccom" class=" form-label">TYPE OF ACCOMMODATION</label>
                                        <select class="form-select select2t-none" id="TypeAccommodation_1" name="Accommodation_type_id[1]" 
                                        required data-parsley-trigger="change".
                                         data-parsley-required-message="Please select accommodation type."
                                         data-parsley-errors-container="#type_accom_error_4">
                                        <option value=""></option>
                                        @if($AccommodationType->isNotEmpty())
                                                @foreach ($AccommodationType as $a)
                                                    <option value="{{ $a->id }}">{{ $a->AccommodationName }}</option>
                                                @endforeach
                                        @endif
                                        </select>
                                        <div id="type_accom_error_4"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="capacity" class="form-label">CAPACITY</label>
                                        <input type="number" min="1" class="form-control" id="Capacity_1" name="Capacity[1]" placeholder="4 people"requireddata-parsley-trigger="keyup" data-parsley-required-message="Capacity is required.">
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomType" class="form-label">ROOM TYPE</label>
                                        <select class="form-select select2t-none" id="RoomType_1" name="RoomType[1]" required data-parsley-trigger="change" data-parsley-required-message="Room type is required."
                                        data-parsley-errors-container="#type_accom_error_5">
                                            <?php
                                                $ROOM = config('settings.eligibilty');
                                            ?>
                                                @if($ROOM)
                                                    @foreach ($ROOM as $k=>$a)
                                                        <option value="{{ $k }}">{{ $a }}</option>
                                                    @endforeach
                                                @endif
                                        </select>

                                        <div id="type_accom_error_5"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="bedNo" class="form-label">BED NO.</label>
                                        <input type="number" class="form-control" id="BedNo_1"   name="BedNo[1]"  placeholder="2" required data-parsley-type="digits" data-parsley-trigger="keyup" data-parsley-required-message="Bed number is required.">
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for=" " class="form-label d-block">BLOCK FOR?</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="blockFor[1]" value="Male" id="male_1"
                                               required data-parsley-required-message="Please select an option.">
                                            <label class="form-check-label" for="male_1">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="blockFor[1]"  value="Female" id="female_1"
                                                 checked required data-parsley-required-message="Please select an option.">
                                            <label class="form-check-label" for="female_1">Female</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="invent" class="form-label">WHAT ARE THE INVENTORIES?</label>
                                        <select class="form-select select2t-none" multiple id="Inv_Cat_id_1" name="Inv_Cat_id[1][]" 
                                        aria-label=" Default select example" required 
                                        data-parsley-required-message="Please select an option."
                                        data-parsley-errors-container="#type_accom_error_6">

                                        <option value=""></option>
                                        @if($InventoryModule->isNotEmpty())
                                            @foreach ($InventoryModule as $a)
                                                <option value="{{ $a->id }}">{{ $a->ItemName }} / {{ $a->ItemCode }}</option>
                                            @endforeach
                                        @endif
                                        </select>
                                        <div id="type_accom_error_6"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">

                                        <label for="roomStatus" class="form-label">CLEANING SCHEDULE</label>
                                            <select class="form-select select2t-none" id="CleaningSchedule_1" name="CleaningSchedule[1]" aria-label="Default select example" required data-parsley-required-message="Please select an option.">
                                                <?php
                                                    $CleaningSchedule = config('settings.CleaningSchedule');
                                                ?>
                                                    @if($CleaningSchedule)
                                                        @foreach ($CleaningSchedule as $a)
                                                            <option value="{{ $a }}">{{ $a }}</option>
                                                        @endforeach
                                                    @endif
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="cleaning" class="form-label">ROOM STATUS</label>
                                        <select class="form-select select2t-none" name="RoomStatus[1]" id="RoomStatus_1" aria-label="Default select example" required data-parsley-required-message="Please select an option.">
                                            <?php
                                                    $RoomStatus = config('settings.RoomStatus');
                                                ?>
                                            @if($RoomStatus)
                                                @foreach ($RoomStatus as $r)
                                                    <option value="{{ $r }}">{{ $r }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="occupThres" class="form-label">OCCUPANCY THRESHOLDS</label>
                                        <input type="number" min="0" max="100" class="form-control" id="Occupancytheresold_1" name="Occupancytheresold[1]" placeholder="90%" required data-parsley-type="digits" data-parsley-trigger="keyup" data-parsley-required-message="Occupancy threshold is required.">
                                    </div>
                                </div>

                                <input type="hidden" name="AvailableCount" id="AvailableCount"  value="1">
                                <div class="mb-4">
                                    <button type="button" class="btn btn-themeSkyblue btn-sm addMore-availabel">Add More</a>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue btn-sm availableFormSubmit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 ">
           
                <form id="OccupancyThresholdForm" data-parsley-validate>
                    <div class="card">
                        <div class="card-title">
                            <h3>Occupancy Levels Hit A Critical Threshold</h3>
                        </div>
                        <div class="Occupancy-main">
                            <input type="hidden" id="AvailablethreshodCount" name="AvailablethreshodCount" value="1">

                            <div class="Occupancy-block mb-3 AppendOccupancyThreshold">
                                <div class="row g-xl-4 g-3 ">
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="BuildingName" class="form-label">Building Name</label>

                                        <select class="form-select select2t-none OccupancybuildingAvailable" data-flag="Occupancy" id="building_occupancy_1" data-id="1" 
                                        name="building_id[1]" placeholder="Select Building" 
                                        required data-parsley-type="digits" 
                                        data-parsley-required-message="Please select building." 
                                        data-parsley-errors-container="#type_occupancy_error_0_1">
                                        <option value=""></option>
                                            @if($BuildingData->isNotEmpty())
                                                @foreach ($BuildingData as $a)
                                                    <option value="{{ $a->id }}">{{ $a->BuildingName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="type_occupancy_error_0_1"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="Floor" class="form-label">Floor</label>

                                        <select class="form-select select2t-none AvailableFloor_Occupancy" id="AvailableFloor_Occupancy_1" data-flag="Occupancy" 
                                        data-id="1" name="Floor[1]" placeholder="Select Floor" required  
                                        data-parsley-trigger="change" data-parsley-required-message="Please select a floor."
                                        data-parsley-errors-container="#type_occupancy_f_error_0_1">
                                        </select>
                                        <div id="type_occupancy_f_error_0_1"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomNo" class="form-label">Room No.</label>
                                        <select class="form-select select2t-none RoomNo" data-id="1" id="RoomNo_Occupancy_1" 
                                        name="RoomNo[1]" required data-parsley-trigger="change" 
                                        data-parsley-required-message="Please select a room number."
                                        data-parsley-errors-container="#type_occupancy_room_error_0_1">
                                        </select>
                                        <div id="type_occupancy_room_error_0_1"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="ThresSoldLevel_1" class="form-label">Set Occupancy Level</label>
                                        <input type="number" min="0" max="100" class="form-control" 
                                        name="ThresSoldLevel[1]" id="ThresSoldLevel_1" placeholder="Set Occupancy level" 
                                        required data-parsley-type="number" data-parsley-min="0" data-parsley-max="100"
                                        data-parsley-required-message="Occupancy level is required."
                                        data-parsley-type-message="Please enter a valid number."
                                        data-parsley-min-message="Minimum value is 0."
                                        data-parsley-max-message="Maximum value is 100."
                                        data-parsley-errors-container="#type_occupancy_level_error_0_1">
                                        <div id="type_occupancy_level_error_0_1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <button type="button" class="btn btn-themeSkyblue btn-sm addMore-Occupancy_threshod">Add More</button>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </div>
                </form>

            </div>
            <div class="col-lg-6 ">
                <div class="card h-100">
                    <div class="card-title">
                        <h3>Quick Assignment</h3>
                    </div>
                    <form id="QuickAssignmentForm">
                        @csrf
                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-auto"><a href="{{route('resort.accommodation.config.QuickAssignmentTemplete')}}" class="btn btn-themeSkyblue btn-sm">Download
                                    Template</a></div>
                            <div class="col-auto">
                                <div class="uploadFile-block">
                                    <div class="uploadFile-btn me-0">
                                        <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                        <input type="file"  name="QuickAssignmentFile"id="uploadFile1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end"><button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="card">
                    <div class="card-title">
                        <h3>Escalation day</h3>
                    </div>
                    <form id="EscalationDay">
                        <div class="mb-3">
                            <input type="number" name="EscalationDay"
                            value="{{ old('EscalationDay', isset($EscalationDay) ? $EscalationDay->EscalationDay : '') }}"
                            class="form-control" placeholder="Define Time Duration">
                        </div>
                        <div class="card-footer text-end"><button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="card">
                    <div class="card-title">
                        <h3>Inventory Category</h3>
                        <div class="text-end">
                            <a href="{{ route('resort.accommodation.config.InvenotryCatIndex') }}" class="a-link">View All</a>
                        </div>
                    </div>
                    <form id="InventoryCategoryForm">
                        @csrf
                        <div class="mb-3">
                            <input type="text" class="form-control" name="CategoryName" placeholder="Category Name">
                        </div>
                        <div class="card-footer text-end"><button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6 ">
                <div class="card">
                    <div class="card-title">
                        <h3>Accommodation Type</h3>
                        <div class="text-end">
                            <a href="{{ route('resort.accommodation.config.AccommodationIndex') }}" class="a-link">View All</a>
                        </div>
                    </div>
                    <form id="AccommodationTypeForm">
                        @csrf
                        <div class="row g-xl-4 g-3 ">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="AccommodationName" id="AccommodationName" placeholder="Accommodation Type">

                            </div>
                            <div class="col-md-6">
                                <input type="color" class="form-control" name="Color" id="AccommodationName" placeholder="color">

                            </div>
                        </div>

                        <div class="card-footer text-end mt-3">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                    </form>
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
    $("#TypeAccommodation_1").select2({
       placeholder:'Type of Accommodation',
    });
   
    $("#Inv_Cat_id_1").select2({
       placeholder:'Type of Inventory',
    });
    $("#CleaningSchedule_1").select2({
       placeholder:'Type of Cleaning Schedule',
    });
    $("#RoomStatus_1").select2({
       placeholder:'Status of Room',
    });
    $("#SelectBuilding").select2({
       placeholder:'Select Building',
    });

    $("#building_id").select2({
       placeholder:'Select Building',
    });
    $("#building_1").select2({
       placeholder:'Select Building',
    });

    $(".OccupancybuildingAvailable").select2({
       placeholder:'Select Building',
    });


    $("#RoomType_1").select2({
       placeholder:'Type of Room',
    });

    $(".AvailableFloor").select2({
       placeholder:'Select Floor',
    });
    $(".RoomNo").select2({
       placeholder:'Select RoomNo',
    });


    $("#AvailableFloor_Occupancy_1").select2({
       placeholder:'Select Floor',
    });
    $('#InventoryCategoryForm').validate({
        rules: {
            CategoryName: {
                required: true,
            }
        },
        messages: {
            CategoryName: {
                required: "Please Enter Category Name.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.accommodation.StoreInvCategory') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();


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
    $('#AccommodationTypeForm').validate({
        rules: {
            AccommodationName: {
                required: true,
            }
        },
        messages: {
            AccommodationName: {
                required: "Please Enter Accommodation Name.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.accommodation.AccommodationTypeStore') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();

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

    $('#EscalationDay').validate({
        rules: {
            EscalationDay:
            {
                required: true,
            }
        },
        messages:
        {
            EscalationDay:
            {
                required: "Please Enter Escalation Day.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.accommodation.config.EscalationDay') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();

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


    $('#OccupancyThresholdForm').validate({
        rules: {
            "building_id[]": {
                required: true,
            },
            "Floor[]": {
                required: true,
            },
            "RoomNo[]": {
                required: true,
            },
            "ThresSoldLevel[]": {
                required: true,
            },
        },
        messages: {
            "building_id[]": {
                required: "Please Select Building Name.",
            },
            "Floor[]": {
                required: "Please Enter Floor Name.",
            },
            "RoomNo[]": {
                required: "Please Enter Room Name.",
            },
            "ThresSoldLevel[]": {
                required: "Please Enter ThreSold level.",
            },
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.accommodation.config.OccupancyThresholdForm') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();

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


    $('#AvailableAccommodationForm').on('submit', function(e) {
        e.preventDefault(); // Fix the typo here

        var form = this; // Use the form being submitted
        var formData = new FormData(form); // Create FormData object from the form
        $('#AvailableAccommodationForm').parsley().validate();

        $.ajax({
            url: "{{ route('resort.accommodation.AvailableAccommodationStore') }}", // Your route for file upload
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Include CSRF token
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    form.reset(); // Reset the form fields
                } else {
                    toastr.error(response.message, "error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON.errors;
                    var errs = '';

                    if (errors) {
                        $.each(errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = 'An unexpected error occurred.';
                    }

                    toastr.error(errs, "Validation Errors", {
                        positionClass: 'toast-bottom-right'
                    });
            }
        });
    });

    $('#BuildingForm').validate({
        rules: {
            BuildingName: {
                required: true,
            }
        },
        messages: {
            BuildingName: {
                required: "Please Enter Building Name.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.accommodation.StoreBuilding') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();
                        window.location.reload();


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



    $('#FloorAndRoomForm').validate({
        rules: {
            SelectBuilding:
            {
                required: true,
            },
            Floor: {
                required: true,
            },
            Room: {
                required: true,
            }
        },
        messages: {
            SelectBuilding:
            {
                required: "Please Select Building Name.",
            },
            Floor:
            {
                required: "Please Enter Floor No.",
            },
            Room:
            {
                required: "Please Enter Room No.",
                remote: "The Room already exists for the selected floor."
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.accommodation.StoreFloorandroom') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();
                        window.location.reload();

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
    });

    $('#AvailableAccommodationFileForm').validate({
        rules: {
            AvailableAccommodationFile:
            {
                required: true,
            }
        },
        messages: {
            AvailableAccommodationFile:
            {
                required: "Please Select File.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.accommodation.AccommodationAvailableFileStore') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();
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
    });

    $('#QuickAssignmentForm').validate({
        rules: {
            QuickAssignmentFile:
            {
                required: true,
            }
        },
        messages: {
            QuickAssignmentFile:
            {
                required: "Please Select File.",
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.accommodation.QuickAssignmentFileStore') }}", // Your route for file upload
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                        form.reset();
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
    });


});

    $(document).on('change','.buildingAvailable,.OccupancybuildingAvailable',function(){
        var location = $(this).data('id');

        var buildingId= $(this).val();
        var flag= $(this).data('flag');

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

                        if(flag=="Occupancy"){



                            $("#RoomNo_Occupancy_"+location).html('<option></option>');
                            $("#AvailableFloor_Occupancy_"+location).html(floor) .select2({
                                                                        placeholder: 'Select Floor',
                                                                        allowClear: true,
                                                                    });;
                        }
                        else
                        {
                            $("#RoomNo_" + location).html('<option></option>');
                            $("#AvailableFloor_" + location).html(floor) .select2({
                                                                        placeholder: 'Select Floor',
                                                                        allowClear: true,
                                                                    });
                        }

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

    $(document).on('change','.AvailableFloor,.AvailableFloor_Occupancy',function()
    {
        var location = $(this).data('id');
        var flag=$(this).data('flag');
        if(flag=="Occupancy")
        {
            var building_id = $("#building_occupancy_"+location).val();
            var AvailableFloor = $("#AvailableFloor_Occupancy_"+location).val();
        }
        else
        {
            var building_id = $("#building_"+location).val();
            var AvailableFloor = $("#AvailableFloor_"+location).val();

        }
        $.ajax({
                url: "{{ route('resort.accommodation.config.GetFloorWiseRooms') }}" ,
                type: "get",
                data: {"_token":"{{ csrf_token() }}","AvailableFloor":AvailableFloor,"building_id":building_id},
                success: function(response) {
                    if (response.success)
                    {
                        var rooms='<option></option>';
                        $.each(response.data, function(i, v) {
                            rooms += `<option value="${v}">${v}</option>`;
                        });
                        if(flag=="Occupancy")
                        {

                            $("#RoomNo_Occupancy_"+location).html(rooms).select2({
                                                                        placeholder: 'Select Rooms',
                                                                        allowClear: true,
                                                                    });
                        }
                        else
                        {
                            $("#RoomNo_"+location).html(rooms).select2({
                                                                        placeholder: 'Select Rooms',
                                                                        allowClear: true,
                                                                    });
                        }





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
    $(document).on('click','.addMore-availabel',function(){
        var AvailableCount = $("#AvailableCount").val();
        AvailableCount++;
        var row =`<hr>
                              <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="building" class="form-label">BUILDING NAME</label>

                                        <select class="form-select select2t-none buildingAvailable" id="building_${AvailableCount}"
                                        data-id= "${AvailableCount}" name="BuildingName[${AvailableCount}]"
                                         placeholder=" Select Building" required data-parsley-type="digits"
                                          data-parsley-required-message="Building name is required." data-parsley-errors-container="#type_accom_b_error_{{$k}}">
                                            <option> </option>
                                            @if($BuildingData->isNotEmpty())
                                                    @foreach ($BuildingData as $a)
                                                        <option value="{{ $a->id }}">{{ $a->BuildingName }}</option>
                                                    @endforeach
                                            @endif
                                        </select>
    
                                        <div id="type_accom_b_error_{{$k}}"></div>
                                    </div>


                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="floor" class="form-label">FLOOR</label>
                                        <select class="form-select select2t-none AvailableFloor"
                                         data-flag="A" id="AvailableFloor_${AvailableCount}"data-id= "${AvailableCount}" 
                                         name="Floor[${AvailableCount}]" placeholder=" Select Building" required   required
                                          data-parsley-errors-container="#type_accom_f_error_{{$k}}"
                                            data-parsley-trigger="keyup" data-parsley-required-message="Please Select Floor.">

                                        </select>
                                        <div id="type_accom_f_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomNo" class=" form-label">ROOM NO.</label>
                                        <select class="form-select select2t-none RoomNo"data-id= "${AvailableCount}" id="RoomNo_${AvailableCount}"
                                         name="RoomNo[${AvailableCount}]"  required   
                                          data-parsley-type="digits" data-parsley-trigger="keyup"
                                        data-parsley-errors-container="#type_accom_r_error_{{$k}}"
                                        data-parsley-required-message="Please Select Floor.">

                                        </select>
                                        <div id="type_accom_r_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="typeAccom" class=" form-label">TYPE OF ACCOMMODATION</label>
                                        <select class="form-select select2t-none" id="TypeAccommodation_${AvailableCount}"
                                         name="Accommodation_type_id[${AvailableCount}]" required data-parsley-trigger="change" 
                                         data-parsley-required-message="Please select accommodation type."
                                        data-parsley-errors-container="#type_accom_type_accom_r_error_{{$k}}">
                                        <option value=""></option>
                                        @if($AccommodationType->isNotEmpty())
                                                @foreach ($AccommodationType as $a)
                                                    <option value="{{ $a->id }}">{{ $a->AccommodationName }}</option>
                                                @endforeach
                                        @endif
                                        </select>
                                        <div id="type_accom_type_accom_r_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="capacity" class="form-label">CAPACITY</label>
                                        <input type="number" min="1" class="form-control" id="Capacity_${AvailableCount}" name="Capacity[${AvailableCount}]" placeholder="4 people"requireddata-parsley-trigger="keyup" data-parsley-required-message="Capacity is required.">
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomType" class="form-label">ROOM TYPE</label>
                                        <select class="form-select select2t-none" id="RoomType_${AvailableCount}" name="RoomType[${AvailableCount}]" required data-parsley-trigger="change" data-parsley-errors-container="#type_accom_type_accom_r_type_error_{{$k}}" data-parsley-required-message="Room type is required.">
                                            <?php
                                                $ROOM = config('settings.eligibilty');
                                            ?>
                                                @if($ROOM)
                                                    @foreach ($ROOM as $k=>$a)
                                                        <option value="{{ $k }}">{{ $a }}</option>
                                                    @endforeach
                                                @endif
                                        </select>
                                        <div id="type_accom_type_accom_r_type_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="bedNo" class="form-label">BED NO.</label>
                                        <input type="number" class="form-control" id="BedNo_${AvailableCount}"   name="BedNo[${AvailableCount}]"  placeholder="2" required data-parsley-type="digits" data-parsley-trigger="keyup" data-parsley-required-message="Bed number is required.">
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for=" " class="form-label d-block">BLOCK FOR?</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="blockFor[${AvailableCount}]" value="Male" id="male_${AvailableCount}"
                                               required data-parsley-required-message="Please select an option.">
                                            <label class="form-check-label" for="male_1">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="blockFor[${AvailableCount}]"  value="Female" id="female_${AvailableCount}"
                                                 checked required data-parsley-required-message="Please select an option.">
                                            <label class="form-check-label" for="female_1">Female</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="invent" class="form-label">WHAT ARE THE INVENTORIES?</label>
                                        <select class="form-select select2t-none" multiple id="Inv_Cat_id_${AvailableCount}" name="Inv_Cat_id[${AvailableCount}][]" aria-label=" Default select example" data-parsley-errors-container="#type_accom_type_accom_inventory_error_{{$k}}" required data-parsley-required-message="Please select an option.">

                                        <option value=""></option>
                                        @if($InventoryModule->isNotEmpty())
                                            @foreach ($InventoryModule as $a)
                                                <option value="{{ $a->id }}">{{ $a->ItemName }} / {{ $a->ItemCode }}</option>
                                            @endforeach
                                        @endif
                                        </select>
                                                                                <div id="type_accom_type_accom_inventory_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">

                                        <label for="roomStatus" class="form-label">CLEANING SCHEDULE</label>
                                            <select class="form-select select2t-none" id="CleaningSchedule_${AvailableCount}" data-parsley-errors-container="#type_accom_type_accom_cleaningschedule_error_{{$k}}" name="CleaningSchedule[${AvailableCount}]" aria-label="Default select example" required data-parsley-required-message="Please select an option.">
                                                <?php
                                                    $CleaningSchedule = config('settings.CleaningSchedule');
                                                ?>
                                                    @if($CleaningSchedule)
                                                        @foreach ($CleaningSchedule as $a)
                                                            <option value="{{ $a }}">{{ $a }}</option>
                                                        @endforeach
                                                    @endif
                                        </select>
                                        <div id="type_accom_type_accom_cleaningschedule_error_{{$k}}"></div>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="cleaning" class="form-label">ROOM STATUS</label>
                                        <select class="form-select select2t-none" name="RoomStatus[${AvailableCount}]" data-parsley-errors-container="#type_accom_type_accom_Room_status_error_{{$k}}" id="RoomStatus_${AvailableCount}" aria-label="Default select example" required data-parsley-required-message="Please select an option.">
                                            <?php
                                                    $RoomStatus = config('settings.RoomStatus');
                                                ?>
                                            @if($RoomStatus)
                                                @foreach ($RoomStatus as $r)
                                                    <option value="{{ $r }}">{{ $r }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="type_accom_type_accom_Room_status_error_{{$k}}"></div>

                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="occupThres" class="form-label">OCCUPANCY THRESHOLDS</label>
                                        <input type="number" min="0" max="100" class="form-control" id="Occupancytheresold_${AvailableCount}" name="Occupancytheresold[${AvailableCount}]" placeholder="90%" required data-parsley-type="digits" data-parsley-trigger="keyup" data-parsley-required-message="Occupancy threshold is required.">
                                    </div>`;
                                    $("#AvailableCount").val(AvailableCount);
        $(".appendAvailableAccommodation").append(row);
        AvailableAccommodationJsClass(AvailableCount);

    });

    $(document).on('click','.addMore-Occupancy_threshod',function(){

        var AvailablethreshodCount = $("#AvailablethreshodCount").val();
        AvailablethreshodCount++;
        var row='';

        row=`<div   id='OccupancyThresold_${AvailablethreshodCount}' >
                    <hr>   <div class="row g-xl-4 g-3 ">
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="BuildingName" class="form-label">Building Name</label>

                                        <select class="form-select select2t-none OccupancybuildingAvailable" data-flag="Occupancy" id="building_occupancy_${AvailablethreshodCount}" data-id="${AvailablethreshodCount}" 
                                        name="building_id[${AvailablethreshodCount}]" placeholder="Select Building" 
                                        required data-parsley-type="digits" 
                                        data-parsley-required-message="Please select building." 
                                        data-parsley-errors-container="#type_occupancy_error_${AvailablethreshodCount}_${AvailablethreshodCount}">
                                        <option value=""></option>
                                            @if($BuildingData->isNotEmpty())
                                                @foreach ($BuildingData as $a)
                                                    <option value="{{ $a->id }}">{{ $a->BuildingName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="type_occupancy_error_${AvailablethreshodCount}_${AvailablethreshodCount}"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="Floor" class="form-label">Floor</label>

                                        <select class="form-select select2t-none AvailableFloor_Occupancy" id="AvailableFloor_Occupancy_${AvailablethreshodCount}" data-flag="Occupancy" 
                                        data-id="${AvailablethreshodCount}" name="Floor[${AvailablethreshodCount}]" placeholder="Select Floor" required  
                                        data-parsley-trigger="change" data-parsley-required-message="Please select a floor."
                                        data-parsley-errors-container="#type_occupancy_f_error_${AvailablethreshodCount}_${AvailablethreshodCount}">
                                        </select>
                                        <div id="type_occupancy_f_error_${AvailablethreshodCount}_${AvailablethreshodCount}"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="roomNo" class="form-label">Room No.</label>
                                        <select class="form-select select2t-none RoomNo" data-id="${AvailablethreshodCount}" id="RoomNo_Occupancy_${AvailablethreshodCount}" 
                                        name="RoomNo[${AvailablethreshodCount}]" required data-parsley-trigger="change" 
                                        data-parsley-required-message="Please select a room number."
                                        data-parsley-errors-container="#type_occupancy_room_error_${AvailablethreshodCount}_${AvailablethreshodCount}">
                                        </select>
                                        <div id="type_occupancy_room_error_${AvailablethreshodCount}_${AvailablethreshodCount}"></div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label for="ThresSoldLevel_${AvailablethreshodCount}" class="form-label">Set Occupancy Level</label>
                                        <input type="number" min="0" max="100" class="form-control" 
                                        name="ThresSoldLevel[${AvailablethreshodCount}]" id="ThresSoldLevel_${AvailablethreshodCount}" placeholder="Set Occupancy level" 
                                        required data-parsley-type="number" data-parsley-min="0" data-parsley-max="100"
                                        data-parsley-required-message="Occupancy level is required."
                                        data-parsley-type-message="Please enter a valid number."
                                        data-parsley-min-message="Minimum value is 0."
                                        data-parsley-max-message="Maximum value is 100."
                                        data-parsley-errors-container="#type_occupancy_level_error_${AvailablethreshodCount}_${AvailablethreshodCount}">
                                        <div id="type_occupancy_level_error_${AvailablethreshodCount}_${AvailablethreshodCount}"></div>
                                    </div>
                                </div>`;
        $(".AppendOccupancyThreshold").append(row);
        $("#AvailablethreshodCount").val(AvailablethreshodCount);
        AvailableOccupancyThreshold(AvailablethreshodCount);
    });

    $(document).on("click",".RemoveOccupancyThreshold",function(){
        var id = $(this).attr('value');

        $("#OccupancyThresold_"+id).remove();
        id--;
        $("#AvailablethreshodCount").val(id);
    });
    function AvailableOccupancyThreshold(AvailableCount){




        $("#building_occupancy_"+AvailableCount).select2({
        placeholder:'Select building',
        });

        $("#AvailableFloor_Occupancy_"+AvailableCount).select2({
        placeholder:'Select Floor',
        });

        $("#RoomNo_Occupancy_"+AvailableCount).select2({
        placeholder:'Select Room',
        });


    }
    function AvailableAccommodationJsClass(AvailableCount){


    $("#building_"+AvailableCount).select2({
       placeholder:'Select building',
    });

    $("#AvailableFloor_"+AvailableCount).select2({
       placeholder:'Select Floor',
    });

    $("#RoomNo_"+AvailableCount).select2({
       placeholder:'Select Room',
    });
    $("#TypeAccommodation_"+AvailableCount).select2({
       placeholder:'Type of Accommodation',
    });
    $("#Inv_Cat_id_"+AvailableCount).select2({
       placeholder:'Type of Inventory',
    });
    $("#CleaningSchedule_"+AvailableCount).select2({
       placeholder:'Type of Cleaning Schedule',
    });
    $("#RoomStatus_"+AvailableCount).select2({
       placeholder:'Status of Room',
    });
    $("#RoomType_"+AvailableCount).select2({
       placeholder:'Type of Room',
    });


}
</script>
@endsection
