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
                            <span>Time And Attendance</span>
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

            <div>
                <div class="row g-4">
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <h3>Attendance Data</h3>
                            </div>
                            <div class="card-body">
                                <h5 class="mb-3">Export Attendance</h5>
                                <!-- Export Attendance Form -->
                                <form id="exportAttendance" method="POST" action="{{ route('attendance.export') }}" class="mb-4" data-parsley-validate>
                                    @csrf
                                    <div class="row g-3 align-items-center">
                                        <!-- Start Date -->
                                        <div class="col-md-4">
                                            <label for="start_date" class="form-label">Start Date:</label>
                                            <input type="text" id="start_date" name="start_date"
                                                class="form-control datepicker" placeholder="dd-mm-yyyy" required data-parsley-errors-container="#start-date-error">
                                            <div id="start-date-error"></div>
                                        </div>
                                        <!-- End Date -->
                                        <div class="col-md-4">
                                            <label for="end_date" class="form-label">End Date:</label>
                                            <input type="text" id="end_date" name="end_date"
                                                class="form-control datepicker" placeholder="dd-mm-yyyy" required
                                                data-parsley-date-after="#start_date"
                                                data-parsley-date-after-message="End Date must be after Start Date"
                                                data-parsley-errors-container="#end-date-error">
                                            <div id="end-date-error"></div>
                                        </div>
                                        <!-- Export Button -->
                                        <div class="col-md-4 text-end">
                                            <button type="submit" class="btn btn-themeBlue">Export Employees</button>
                                        </div>
                                    </div>
                                </form>

                                <hr>
                                <h5 class="mb-3">Import Attendance</h5>
                                <!-- File Upload Section -->
                                <form id="ImportAttandanceForm">
                                    @csrf
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4 mb-3">
                                            <a href="{{ route('resort.timeandattendance.DownloadAttendanceTemplate') }}" class="btn btn-themeSkyblue btn-sm">
                                                Download Template
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <small></small>
                                            {{-- <small id="error-message" class="text-themeDanger" >Only Excel files (.xls, .xlsx) are accepted.</small> --}}
                                            <div class="uploadFile-btn me-0">
                                                <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                                    onclick="document.getElementById('UploadImportattandance').click();">
                                                    Upload File
                                                </a>
                                                <input type="file" id="UploadImportattandance" name="UploadImportattandance"
                                                    style="opacity: 0; position: absolute; z-index: -1;"
                                                    onchange="displayImportFileName()">
                                                <div id="fileNameImportFile" style="margin-top: 10px; color: #333;"></div>
                                            </div>
                                            <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                        </div>
                                    </div>
                            </div>
                            {{-- <div class="card-footer text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div> --}}
                        </form>
                        </div>
                        <!-- <div class="card mb-30">
                            <div class="card-title">
                                <h3>Upload past Data</h3>
                            </div>
                            <form>
                                <div class="mb-2">
                                    <select class="form-select  ResortDivision" id="ResortDivision" name='ResortDivision'
                                        aria-label="Default select example">
                                        <option> </option>
                                        @if ($resort_divisions->isNotEmpty())
                                            @foreach ($resort_divisions as $resort_division)
                                                <option value="{{ $resort_division->id }}">{{ $resort_division->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select select2 Department" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-2">
                                    <select class="form-select  Section" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select select2 Position" aria-label="Default select example">
                                        <option></option>

                                    </select>
                                </div>
                                <div class="mb-3">
                                    <div class="uploadFile-block">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" id="uploadFile">
                                        </div>
                                        <div class="uploadFile-text">PDF or Excel</div>
                                    </div>
                                </div>
                            </form>
                            <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                            </div>
                        </div> -->
                        <!-- <div class="card">
                            <div class="card-title">
                                <h3>Color Theme</h3>
                            </div>
                            <form id="color-theme-form">
                                @csrf()
                                <div class="row gx-md-4 g-3 mb-3" id="color-theme-container">
                                    @if ($colorThemes)
                                        @foreach ($colorThemes as $index => $theme)
                                            <div class="col-sm-6 theme-row" data-theme-id="{{ $theme->id }}">
                                                <div class="inputCustom-color">
                                                    <input type="text" name="themes[{{ $index }}][name]"
                                                        placeholder="Label" class="form-control mb-2"
                                                        value="{{ $theme->name }}">
                                                    <input type="color" name="themes[{{ $index }}][color]"
                                                        value="{{ $theme->color }}" style="top: 28% !important;">
                                                    <button type="button"
                                                        class="btn btn-danger remove-theme-btn">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <!-- Default Themes -->
                                        <div class="col-sm-6 theme-row" data-theme-id="new">
                                            <div class="inputCustom-color">
                                                <input type="text" name="themes[0][name]"
                                                    placeholder="Label (e.g., On Time)" class="form-control mb-2"
                                                    value="On Time">
                                                <input type="color" name="themes[0][color]" value="#63C67E" style="top: 28% !important;">
                                                <button type="button"
                                                    class="btn btn-danger remove-theme-btn">Remove</button>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 theme-row" data-theme-id="new">
                                            <div class="inputCustom-color">
                                                <input type="text" name="themes[1][name]"
                                                    placeholder="Label (e.g., Late)" class="form-control mb-2"
                                                    value="Late">
                                                <input type="color" name="themes[1][color]" value="#A264F7" style="top: 28% !important;">
                                                <button type="button"
                                                    class="btn btn-danger remove-theme-btn">Remove</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <a href="#" id="add-more-theme" class="btn btn-themeSkyblue">Add More</a>
                                </div>
                            </form>
                            <div class="card-footer text-end">
                                <button type="button" id="submit-themes" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </div> -->
                    </div>
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <h3>Shift Settings</h3>
                            </div>
                            <form id="ShirfFrom" data-parsley-validate>
                                @csrf()
                                <div class="shift-main">
                                    <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm mb-3 add-shift">Add
                                        Shift</a>

                                    <!-- Dynamic Shift Blocks -->
                                    <div class="shift-block AppendShift">
                                        <input type="hidden" name="resort_id" value="{{ $resort_id }}">
                                        @if ($ShiftSettings->isNotEmpty())
                                            @foreach ($ShiftSettings as $s)
                                                <div class="row align-items-end g-2 mb-2 shift-row">
                                                    <input type="hidden" name="id[]" value="{{ $s->id }}">

                                                    <div class="col">
                                                        <label for="shift-name" class="form-label">SHIFT NAME</label>
                                                        <input type="text" name="ShiftName[]"
                                                            class="form-control shift-name" placeholder="First Shift"
                                                            required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter a shift name"
                                                            value="{{ $s->ShiftName }}">
                                                    </div>
                                                    <div class="col">
                                                        <label for="start-time" class="form-label">Start Time</label>
                                                        <input type="time" class="form-control start-time"
                                                            name="StartTime[]" required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter a start time"
                                                            value="{{ $s->StartTime }}">
                                                    </div>
                                                    <div class="col">
                                                        <label for="end-time" class="form-label">End Time</label>
                                                        <input type="time" class="form-control end-time"
                                                            name="EndTime[]" required data-parsley-required="true"
                                                            data-parsley-required-message="Please enter an end time"
                                                            value="{{ $s->EndTime }}">
                                                    </div>
                                                    <div class="col-auto">
                                                        <a href="#"
                                                            class="btn btn-themeBlue btn-sm btn-minus minus-shift"
                                                            data-id="{{ $s->id }}">
                                                            <i class="fa-solid fa-minus"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row align-items-end g-2 mb-2 shift-row">
                                                <div class="col">
                                                    <label for="shift-name" class="form-label">SHIFT NAME</label>
                                                    <input type="text" name="ShiftName[]"
                                                        class="form-control shift-name" placeholder="First Shift" required
                                                        data-parsley-required="true"
                                                        data-parsley-required-message="Please enter a shift name">
                                                </div>
                                                <div class="col">
                                                    <label for="start-time" class="form-label">Start Time</label>
                                                    <input type="time" class="form-control start-time"
                                                        name="StartTime[]" required data-parsley-required="true"
                                                        data-parsley-required-message="Please enter a start time">
                                                </div>
                                                <div class="col">
                                                    <label for="end-time" class="form-label">End Time</label>
                                                    <input type="time" class="form-control end-time" name="EndTime[]"
                                                        required data-parsley-required="true"
                                                        data-parsley-required-message="Please enter an end time">
                                                </div>
                                                <div class="col-auto">
                                                    <a href="#"
                                                        class="btn btn-themeBlue btn-sm btn-minus minus-shift"
                                                        data-id="0">
                                                        <i class="fa-solid fa-minus"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>



                        </div>
                        <div class="card mb-30 @if(Common::checkRouteWisePermission('resort.upcomingholiday.list',config('settings.resort_permissions.create')) == false) d-none @endif">
                            <div class="card-title">
                                <h3>Public Holidays</h3>
                                <div class="text-end">
                                    <a href="{{ route('resort.timeandattendance.publicholidaylist') }}"
                                        class="a-link">View All</a>
                                </div>
                            </div>
                            <div class="row g-md-3 g-2 align-items-center mb-3">
                                    <div class="col-md-4"><a
                                        href="{{ URL::asset('resorts_assets/Resort_holiday/ResrortHolidayFormat.xlsx') }}"
                                        class="btn btn-themeSkyblue btn-sm">Download Template</a>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn me-0">
                                                <form id="fileUploadForm" enctype="multipart/form-data" parsley-validate>

                                                    <a href="#" class="btn btn-themeBlue btn-sm"
                                                        onclick="document.getElementById('fileUpload').click();">
                                                        Upload File
                                                    </a>

                                                    <input type="file" id="fileUpload" style="display:none"
                                                        accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm,.csv"
                                                        onchange="UplodeAttandancedisplayFileName()">
                                                    <div id="fileName" style="margin-top: 10px; color: #333;"></div>
                                                    <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                {{-- <div class="col-auto ms-auto"><a href="#" class="a-link">Find Gov. Public
                                        Holidays</a>
                                </div> --}}
                            </div>
                            <div class="col-auto mb-3"><span style="font-size: 14px; font-weight: 600;">OR</span></div>

                            <div class="col-md-4 justify-content-end">
                                <a href="javascript:void();" class="btn btn-themeSkyblue btn-sm AddPublicHolidays">Add Public
                                    Holidays</a>
                            </div>

                            {{-- <div class="card-footer text-end"><a href="#"
                                    class="btn btn-themeBlue btn-sm">Submit</a>
                            </div> --}}
                        </div>
                        <div class="card card-confiTimeAtten">
                            <div class="row align-items-center g-2 mb-md-3 mb-2">
                                <div class="col-auto">
                                    <h6>Geo-Fencing Attendance</h6>
                                </div>
                                <div class="col-auto">
                                    <button type='button' class="btn btn-themeBlue btn-sm AddGeoFancing"
                                        style="display:none">Add Geo-Fencing</button>
                                </div>
                                <div class="col-auto">
                                    <button type='button' class="btn btn-theme btn-sm" id="openGeofenceManager"
                                        style="display:none">
                                        <i class="fa-solid fa-draw-polygon"></i> Manage Zones
                                    </button>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="form-check form-switch form-switchTheme switch-blue">
                                        <input class="form-check-input flexSwitchCheckDefaultCheck" type="checkbox"
                                            role="switch" id="flexSwitchCheckDefault" checked>
                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                    </div>
                                </div>
                            </div>

                            <!-- Geofence Zones Preview Table (max 5) -->
                            <div id="geofence-zones-preview" class="mt-2" style="display:none;">
                                @if(isset($geofenceZones) && $geofenceZones->count())
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                                            <thead>
                                                <tr>
                                                    <th style="width:30px;">#</th>
                                                    <th>Zone Name</th>
                                                    <th>Type</th>
                                                    <th>Grace</th>
                                                    <th>Status</th>
                                                    <th style="width:80px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($geofenceZones->take(5) as $index => $zone)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <span class="zone-dot" style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $zone->color }}; margin-right:5px;"></span>
                                                        {{ $zone->name }}
                                                    </td>
                                                    <td>
                                                        <i class="fa-solid fa-{{ $zone->shape_type === 'circle' ? 'circle' : 'draw-polygon' }} me-1" style="color:{{ $zone->color }};"></i>
                                                        {{ ucfirst($zone->shape_type) }}
                                                    </td>
                                                    <td>{{ $zone->grace_period }} min</td>
                                                    <td>
                                                        <span class="badge {{ $zone->status === 'active' ? 'badge-themeSuccess' : 'badge-themeWarning' }}" style="font-size:10px;">
                                                            {{ ucfirst($zone->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-nowrap gap-1">
                                                            <button class="btn btn-sm p-0 px-1 gf-edit-zone-config" data-id="{{ $zone->id }}" title="Edit">
                                                                <i class="fa-solid fa-pen text-primary" style="font-size:11px;"></i>
                                                            </button>
                                                            <button class="btn btn-sm p-0 px-1 gf-toggle-zone-config" data-id="{{ $zone->id }}" title="{{ $zone->status === 'active' ? 'Pause' : 'Activate' }}">
                                                                <i class="fa-solid fa-{{ $zone->status === 'active' ? 'pause' : 'play' }} text-warning" style="font-size:11px;"></i>
                                                            </button>
                                                            <button class="btn btn-sm p-0 px-1 gf-delete-zone-config" data-id="{{ $zone->id }}" title="Delete">
                                                                <i class="fa-solid fa-trash text-danger" style="font-size:11px;"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-1">
                                        <a href="{{ route('resort.geofences.page') }}" class="small text-primary">
                                            @if($geofenceZones->count() > 5)
                                                View All {{ $geofenceZones->count() }} Zones
                                            @else
                                                Manage Zones
                                            @endif
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                @else
                                    <small class="text-muted">No zones configured. Click "Manage Zones" to add.</small>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {{-- Model Start --}}
    <!-- Modal Structure -->
    <div class="modal fade" id="GeoFacing-modal" tabindex="-1" aria-labelledby="GeoFacing-modalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-GeoFacingModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Geo Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="GeoLocation">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <input type="hidden" name="resort_id" value="{{ $resort_id }}">
                            <input type="hidden" name="polygon_coords" id="polygon_coords"
                                value="{{ isset($ResortGeoLocation) && $ResortGeoLocation->polygon_coords ? $ResortGeoLocation->polygon_coords : '' }}">

                            <!-- Drawing toolbar -->
                            <div class="col-md-12">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" id="btn-draw-polygon" class="btn btn-sm btn-themeBlue">
                                        <i class="fa-solid fa-draw-polygon"></i> Draw Polygon
                                    </button>
                                    <button type="button" id="btn-clear-polygon" class="btn btn-sm btn-themeDanger" style="display:none;">
                                        <i class="fa-solid fa-trash"></i> Clear Polygon
                                    </button>
                                    <button type="button" id="btn-place-marker" class="btn btn-sm btn-themeGray">
                                        <i class="fa-solid fa-map-marker-alt"></i> Place Marker
                                    </button>
                                    <span id="draw-status" class="text-muted small ms-2"></span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div id="geoMap" style="width:100%; height:400px; border-radius:8px; border:1px solid #ddd;"></div>
                                <small class="text-muted mt-1 d-block">
                                    Use <strong>Draw Polygon</strong> to click points on the map and define a geo-fence boundary.
                                    Use <strong>Place Marker</strong> to set the center point.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Center Latitude</label>
                                <input type="text" class="form-control" placeholder="Latitude" id="latitude"
                                    value="{{ isset($ResortGeoLocation) && $ResortGeoLocation->latitude != null ? $ResortGeoLocation->latitude : '' }}"
                                    name="latitude">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Center Longitude</label>
                                <input type="text" class="form-control" placeholder="Longitude" id="longitude"
                                    name="longitude"
                                    value="{{ isset($ResortGeoLocation) && $ResortGeoLocation->longitude != null ? $ResortGeoLocation->longitude : '' }}">
                            </div>

                            <!-- Polygon coordinates display -->
                            <div class="col-md-12" id="polygon-coords-display" style="display:none;">
                                <label class="form-label">Polygon Coordinates <span class="badge badge-themeLight" id="polygon-point-count">0 points</span></label>
                                <div id="polygon-coords-list" style="max-height:120px; overflow-y:auto; font-size:12px; background:#f8f9fa; border-radius:6px; padding:8px;">
                                    <em class="text-muted">No polygon drawn yet</em>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Geofence Zone Manager Modal --}}
    <div class="modal fade" id="GeofenceManager-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-draw-polygon me-2"></i>Geofence Zone Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        {{-- Map Area (70%) --}}
                        <div class="col-lg-8">
                            <div id="geofenceMap" style="width:100%; height:550px;"></div>
                            {{-- Drawing toolbar --}}
                            <div class="d-flex align-items-center gap-2 p-2 border-top bg-light">
                                <button type="button" id="gf-tool-polygon" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-draw-polygon"></i> Polygon
                                </button>
                                <button type="button" id="gf-tool-circle" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-regular fa-circle"></i> Circle
                                </button>
                                <button type="button" id="gf-tool-undo" class="btn btn-sm btn-outline-secondary" disabled>
                                    <i class="fa-solid fa-undo"></i> Undo
                                </button>
                                <button type="button" id="gf-tool-clear" class="btn btn-sm btn-outline-danger" disabled>
                                    <i class="fa-solid fa-trash"></i> Clear
                                </button>
                                <span id="gf-draw-status" class="text-muted small ms-auto"></span>
                            </div>
                        </div>
                        {{-- Zone List (30%) --}}
                        <div class="col-lg-4 border-start" style="max-height:600px; overflow-y:auto;">
                            {{-- Add/Edit Zone Form --}}
                            <div class="p-3 border-bottom bg-light">
                                <h6 id="gf-form-title" class="mb-2">Add New Zone</h6>
                                <input type="hidden" id="gf-edit-id" value="">
                                <div class="mb-2">
                                    <input type="text" id="gf-zone-name" class="form-control form-control-sm" placeholder="Zone name (e.g. Restaurant A)">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-7">
                                        <label class="form-label small mb-0">Color</label>
                                        <div class="d-flex gap-1" id="gf-color-picker">
                                            <span class="gf-color-dot active" data-color="#FF4444" style="background:#FF4444;"></span>
                                            <span class="gf-color-dot" data-color="#4CAF50" style="background:#4CAF50;"></span>
                                            <span class="gf-color-dot" data-color="#2196F3" style="background:#2196F3;"></span>
                                            <span class="gf-color-dot" data-color="#FF9800" style="background:#FF9800;"></span>
                                            <span class="gf-color-dot" data-color="#9C27B0" style="background:#9C27B0;"></span>
                                            <span class="gf-color-dot" data-color="#00BCD4" style="background:#00BCD4;"></span>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label small mb-0">Grace (min)</label>
                                        <select id="gf-grace-period" class="form-select form-select-sm">
                                            <option value="5">5 min</option>
                                            <option value="10" selected>10 min</option>
                                            <option value="15">15 min</option>
                                            <option value="30">30 min</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="gf-save-zone" class="btn btn-sm btn-theme flex-fill" disabled>
                                        <i class="fa-solid fa-check"></i> Save Zone
                                    </button>
                                    <button type="button" id="gf-cancel-edit" class="btn btn-sm btn-themeGray" style="display:none;">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            {{-- Zone List --}}
                            <div id="gf-zone-list" class="p-2">
                                <div class="text-center text-muted py-4">
                                    <i class="fa-solid fa-map-location-dot fa-2x mb-2"></i>
                                    <p class="small mb-0">No zones yet. Draw on the map to create one.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .gf-color-dot {
            display: inline-block; width: 22px; height: 22px; border-radius: 50%;
            cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s;
        }
        .gf-color-dot:hover, .gf-color-dot.active { border-color: #333; }
        .gf-zone-card {
            background: #fff; border: 1px solid #e9ecef; border-radius: 8px;
            padding: 10px 12px; margin-bottom: 8px; transition: box-shadow 0.2s;
        }
        .gf-zone-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .gf-zone-card .zone-dot {
            display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 6px;
        }
    </style>

    <div class="modal fade" id="PublicHoliday-modal" tabindex="-1" aria-labelledby="PublicHoliday-modalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-PublicHoliday">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Public Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="PublicHolidayForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <!-- Latitude Input -->
                            <input type="hidden" name="resort_id" value="{{ $resort_id }}">

                            <div class="col-md-12">
                                <select name="PublicHoliday" id="PublicHoliday" class="form-select">
                                    <option value="" data-id="0">Select Holiday</option>
                                    @foreach ($PublicHoliday as $p)
                                        <option value="{{ $p->id }}" data-id="{{ $p->id }}"
                                            data-date='{{ $p->holiday_date }}'>{{ $p->name }}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="col-md-12">
                                <input type="text" name="ResortPublicHolidayDate" id="ResortPublicHolidayDate"
                                    class="form-control" style="display:none" readonly></input>
                            </div>

                            <label class="mx-auto d-block text-center mb-2">OR</label>
                            <div class="col-md-12">
                                <input type="date" name="PublicHolidaydate" placeholder="Holiday Date"
                                    id="PublicHolidaydate" class="form-control"></input>
                            </div>

                            <div class="col-md-12">
                                <input type="text" name="PublicHolidayName" placeholder="Holiday Name"
                                    id="PublicHolidayName" class="form-control"></input>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=drawing"></script>

    <script type="text/javascript">
        $(document).ready(function() {
             // Custom validator for checking if one date is after another
             window.Parsley.addValidator('dateAfter', {
    validateString: function (value, selector) {
        let startDate = document.querySelector(selector)?.value;
        if (!startDate || !value) return true;

        function parseDate(dateStr) {
            let [d, m, y] = dateStr.split('-');

            // Convert 2-digit year → 4-digit year
            if (y.length === 2) {
                y = '20' + y;
            }

            return new Date(y, m - 1, d); // month is 0-based
        }

        let start = parseDate(startDate);
        let end   = parseDate(value);

        return end > start;
    },
    messages: {
        en: 'End Date must be after Start Date.'
    }
});


            $("#fileUploadForm").parsley();
            $("#start_date , #end_date").datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,      // Close the picker after selection
                todayHighlight: true  // Highlight today's date
            });
            $("#ResortDivision").select2({
                placeholder: "Select Division"
            });
            $(".Department").select2({
                placeholder: "Select Department"
            });
            $(".Section").select2({
                placeholder: "select Section"
            });
            $(".Position").select2({
                placeholder: "select Position"
            });


            $('#ImportAttandanceForm').validate({
                rules: {
                    UploadImportattandance: {
                        required: true,
                    }
                },
                messages: {
                    UploadImportattandance: {
                        required: "Please select a file to upload.",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    formData.append('fileUpload', $('#fileUpload')[0].files[0]); // Append the file to the form data

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.ImportAttandance') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                toastr.error(response.msg, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            if(response.responseJSON)

                            {
                                var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                            }

                        }
                    });
                }
            });

            $('#fileUploadForm').validate({
                rules: {
                    fileUpload: {
                        required: true,
                        extension: "xls|xlsx" // Only allow .xls or .xlsx files
                    }
                },
                messages: {
                    fileUpload: {
                        required: "Please select a file to upload.",
                        extension: "Only .xls or .xlsx files are allowed."
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "fileUpload") {
                        $('#file-extension-error').text('Only .xls or .xlsx files are allowed.').show();
                    } else {
                        error.insertAfter(element);
                    }
                },
                success: function(label, element) {
                    if ($(element).attr("name") == "fileUpload") {
                        $('#file-extension-error').hide();
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);
                    formData.append('fileUpload', $('#fileUpload')[0].files[
                    0]); // Append the file to the form data

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.HolidayfileUpload') }}", // Your route for file upload
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
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
                }
            });

            $(document).on('change', '.ResortDivision', function() {
                $.ajax({
                    url: "{{ route('resort.get.ResortDivision') }}",
                    type: "post",
                    data: {
                        division_id: $(this).val(),
                    },
                    success: function(data) {

                        // Clear the dropdown and add a placeholder option
                        $(".Department").html('<option value="">Select Department</option>');
                        if (data.success == true) {
                            let string = '<option></option>';
                            // Append new options
                            $.each(data.data, function(key, value) {

                                string += '<option value="' + value.id + '">' + value
                                    .name + '</option>';
                            });

                            $(".Department").html(string);

                        } else {
                            let string = '<option></option>';
                        }
                    },
                    error: function(response) {
                        toastr.error("Department Not Found", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });

            $(document).on('change', '.Department', function() {
                var deptId = $(this).val();
                $.ajax({
                    url: "{{ route('resort.ta.PositionSections') }}",
                    type: "post",
                    data: {
                        deptId: deptId
                    },
                    success: function(d) {
                        // Clear the dropdown and add a placeholder option


                        if (d.success == true) {

                            let string = '<option></option>';
                            $.each(d.data.ResortPosition, function(key, value) {
                                string += '<option value="' + value.id + '">' + value
                                    .position_title + '</option>';
                            });
                            $(".Position").html(string);

                            let string1 = '<option></option>';
                            $.each(d.data.ResortSection, function(key, value) {
                                string1 += '<option value="' + value.id + '">' + value
                                    .name + '</option>';
                            });
                            $(".Section").html(string1);

                        }
                    },
                    error: function(response) {
                        toastr.error("Position Not Found", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
            $('#GeoFacing-modal').on('hidden.bs.modal', function() {
                $(this).attr('aria-hidden', 'true'); // Reapply aria-hidden on modal close
            });
            getcheckit();
            // Initialize Parsley validation on the form
            $('#ShirfFrom').parsley();

            // Add Shift Block Dynamically
            $(".add-shift").on("click", function(e) {
                e.preventDefault();

                const newShift = `
            <div class="row align-items-end g-2 mb-2 shift-row">
                <div class="col">
                    <label for="shift-name" class="form-label">SHIFT NAME</label>
                    <input type="text" name="ShiftName[]" class="form-control shift-name" required data-parsley-required-message="Please enter a shift name">
                </div>
                <div class="col">
                    <label for="start-time" class="form-label">Start Time</label>
                    <input type="time" class="form-control start-time" name="StartTime[]" required data-parsley-required-message="Please enter a start time">
                </div>
                <div class="col">
                    <label for="end-time" class="form-label">End Time</label>
                    <input type="time" class="form-control end-time" name="EndTime[]" required data-parsley-required-message="Please enter an end time">
                </div>
                <div class="col-auto">
                    <a href="#" class="btn btn-themeBlue btn-sm btn-minus minus-shift">
                        <i class="fa-solid fa-minus"></i>
                    </a>
                </div>
            </div>
            `;
                $(".AppendShift").append(newShift);


                $('#ShirfFrom').parsley().reset();
                $('#ShirfFrom').parsley().validate();
            });

            // Remove Shift Block
            $(document).on("click", ".minus-shift", function(e) {
                e.preventDefault();

                let id = $(this).data('id');


                // Show SweetAlert2 confirmation
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        if (id != 0) {
                            $(this).closest(".shift-row").remove();
                            $.ajax({
                                url: "{{ route('resort.timeandattendance.removeShift') }}",
                                type: "POST",
                                data: {
                                    id: id
                                }, // Pass the id in the data
                                success: function(response) {
                                    $('#respond-HoldModel').modal('hide');
                                    $(this).closest(".shift-row").remove();
                                    if (response.success) {
                                        // Update the view with the new data

                                        toastr.success(response.message, "Success", {
                                            positionClass: 'toast-bottom-right'
                                        });

                                        // Reset form validation if needed
                                        $('#ShirfFrom').parsley().reset();
                                        $('#ShirfFrom').parsley().validate();
                                    } else {

                                        toastr.error(response.message, "Error", {
                                            positionClass: 'toast-bottom-right'
                                        });
                                    }
                                },
                                error: function(response) {
                                    var errors = response.responseJSON;
                                    var errs = '';
                                    $.each(errors.errors, function(key,
                                    error) { // Adjust according to your response format
                                        errs += error + '<br>';
                                    });
                                    toastr.error(errs, {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            });

                        } else {
                            $(this).closest(".shift-row").remove();
                        }


                    } else {
                        // User canceled, do nothing
                        return;
                    }
                });
                $('#ShirfFrom').parsley().reset();
                $('#ShirfFrom').parsley().validate();
            });

            $(document).on("blur", ".start-time, .end-time", function() {
                const parentRow = $(this).closest(".shift-row");
                const startTime = parentRow.find(".start-time").val();
                const endTime = parentRow.find(".end-time").val();

                // Clear any previous Parsley validation messages
                parentRow.find(".end-time").parsley().reset();

                if (startTime && endTime) {
                    const start = new Date(`1970-01-01T${startTime}`);
                    const end = new Date(`1970-01-01T${endTime}`);

                    if (start >= end) {
                        // Manually trigger Parsley validation error for End Time
                        parentRow.find(".end-time")
                            .attr("data-parsley-error-message", "End Time must be later than Start Time")
                            .parsley()
                            .validate();
                    }
                }
            });


            // Form Submission
            $("#ShirfFrom").on("submit", function(e) {
                e.preventDefault();

                // Ensure form passes Parsley validation
                const isValid = $(this).parsley().isValid();
                if (!isValid) {
                    return; // Stop submission if validation fails
                }

                // Perform AJAX request if everything is valid
                const formData = new FormData(this);
                $.ajax({
                    url: "{{ route('resort.timeandattendance.ShiftStore') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success)
                        {

                            toastr.success(response.message, "Success", {
                                positionClass: "toast-bottom-right",
                            });
                            window.location.reload();

                        } else {
                            toastr.error(response.message, "Error", {
                                positionClass: "toast-bottom-right",
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {

                                toastr.error(messages, "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                            });
                        } else {
                            // General errors
                            toastr.error("An unexpected error occurred. Please try again.",
                                "Error", {
                                    positionClass: "toast-bottom-right",
                                });
                        }
                    },
                });
            });

            /// Geo Fencing Location




            $(".flexSwitchCheckDefaultCheck").on("change", function(e) {

                getcheckit();
            });

            var geoMap = null;
            var geoMarker = null;
            var geoPolygon = null;
            var polygonPath = [];
            var polygonMarkers = [];
            var isDrawingMode = false;
            var isMarkerMode = false;

            function initGeoMap(lat, lng) {
                var center = { lat: parseFloat(lat) || 4.1755, lng: parseFloat(lng) || 73.5093 };

                if (geoMap) {
                    geoMap.setCenter(center);
                    if (geoMarker) geoMarker.setPosition(center);
                    return;
                }

                geoMap = new google.maps.Map(document.getElementById('geoMap'), {
                    center: center,
                    zoom: 15,
                    mapTypeId: 'hybrid',
                    streetViewControl: false,
                });

                // Center marker
                geoMarker = new google.maps.Marker({
                    position: center,
                    map: geoMap,
                    draggable: true,
                    title: 'Resort Center',
                    icon: {
                        url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                    }
                });

                geoMarker.addListener('dragend', function(e) {
                    $('#latitude').val(e.latLng.lat().toFixed(8));
                    $('#longitude').val(e.latLng.lng().toFixed(8));
                });

                // Map click handler — depends on active mode
                geoMap.addListener('click', function(e) {
                    if (isDrawingMode) {
                        addPolygonPoint(e.latLng);
                    } else if (isMarkerMode) {
                        geoMarker.setPosition(e.latLng);
                        $('#latitude').val(e.latLng.lat().toFixed(8));
                        $('#longitude').val(e.latLng.lng().toFixed(8));
                        setMarkerMode(false);
                    }
                });

                // Load saved polygon if exists
                var savedCoords = $('#polygon_coords').val();
                if (savedCoords) {
                    try {
                        var coords = JSON.parse(savedCoords);
                        if (Array.isArray(coords) && coords.length >= 3) {
                            coords.forEach(function(c) {
                                polygonPath.push(new google.maps.LatLng(c.lat, c.lng));
                            });
                            drawPolygon();
                            updatePolygonDisplay();

                            // Fit map to polygon bounds
                            var bounds = new google.maps.LatLngBounds();
                            polygonPath.forEach(function(p) { bounds.extend(p); });
                            geoMap.fitBounds(bounds);
                        }
                    } catch(ex) {
                        console.error('Error parsing saved polygon:', ex);
                    }
                }
            }

            function setDrawingMode(active) {
                isDrawingMode = active;
                isMarkerMode = false;
                $('#btn-draw-polygon').toggleClass('btn-themeBlue', !active).toggleClass('btn-theme', active);
                $('#btn-place-marker').removeClass('btn-theme').addClass('btn-themeGray');
                $('#draw-status').text(active ? 'Click on the map to add polygon points. Click "Draw Polygon" again to finish.' : '');
                if (geoMap) geoMap.setOptions({ draggableCursor: active ? 'crosshair' : null });
            }

            function setMarkerMode(active) {
                isMarkerMode = active;
                isDrawingMode = false;
                $('#btn-place-marker').toggleClass('btn-themeGray', !active).toggleClass('btn-theme', active);
                $('#btn-draw-polygon').removeClass('btn-theme').addClass('btn-themeBlue');
                $('#draw-status').text(active ? 'Click on the map to place the center marker.' : '');
                if (geoMap) geoMap.setOptions({ draggableCursor: active ? 'pointer' : null });
            }

            function addPolygonPoint(latLng) {
                polygonPath.push(latLng);

                // Add small circle marker for the point
                var pointMarker = new google.maps.Marker({
                    position: latLng,
                    map: geoMap,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                        fillColor: '#FF4444',
                        fillOpacity: 1,
                        strokeColor: '#FFFFFF',
                        strokeWeight: 2,
                    },
                    draggable: true,
                    title: 'Point ' + polygonPath.length
                });

                var pointIndex = polygonPath.length - 1;
                pointMarker.addListener('dragend', function(e) {
                    polygonPath[pointIndex] = e.latLng;
                    drawPolygon();
                    updatePolygonDisplay();
                });

                polygonMarkers.push(pointMarker);
                drawPolygon();
                updatePolygonDisplay();
            }

            function drawPolygon() {
                if (geoPolygon) {
                    geoPolygon.setMap(null);
                }

                if (polygonPath.length < 2) return;

                geoPolygon = new google.maps.Polygon({
                    paths: polygonPath,
                    strokeColor: '#FF4444',
                    strokeOpacity: 0.9,
                    strokeWeight: 2,
                    fillColor: '#FF4444',
                    fillOpacity: 0.2,
                    map: geoMap,
                    editable: false,
                });
            }

            function clearPolygon() {
                if (geoPolygon) {
                    geoPolygon.setMap(null);
                    geoPolygon = null;
                }
                polygonMarkers.forEach(function(m) { m.setMap(null); });
                polygonMarkers = [];
                polygonPath = [];
                $('#polygon_coords').val('');
                updatePolygonDisplay();
                $('#btn-clear-polygon').hide();
                setDrawingMode(false);
            }

            function updatePolygonDisplay() {
                var count = polygonPath.length;
                $('#polygon-point-count').text(count + ' point' + (count !== 1 ? 's' : ''));

                if (count > 0) {
                    $('#polygon-coords-display').show();
                    $('#btn-clear-polygon').show();
                    var html = '<table class="table table-sm table-borderless mb-0" style="font-size:12px;">';
                    html += '<tr><th style="width:30px">#</th><th>Latitude</th><th>Longitude</th></tr>';
                    polygonPath.forEach(function(p, i) {
                        html += '<tr><td>' + (i + 1) + '</td><td>' + p.lat().toFixed(8) + '</td><td>' + p.lng().toFixed(8) + '</td></tr>';
                    });
                    html += '</table>';
                    $('#polygon-coords-list').html(html);

                    // Save to hidden input
                    var coordsArray = polygonPath.map(function(p) {
                        return { lat: parseFloat(p.lat().toFixed(8)), lng: parseFloat(p.lng().toFixed(8)) };
                    });
                    $('#polygon_coords').val(JSON.stringify(coordsArray));
                } else {
                    $('#polygon-coords-display').hide();
                    $('#polygon-coords-list').html('<em class="text-muted">No polygon drawn yet</em>');
                    $('#polygon_coords').val('');
                }
            }

            // Button handlers
            $(document).on('click', '#btn-draw-polygon', function() {
                if (isDrawingMode) {
                    // Finish drawing
                    setDrawingMode(false);
                    if (polygonPath.length >= 3) {
                        drawPolygon();
                    }
                } else {
                    setDrawingMode(true);
                }
            });

            $(document).on('click', '#btn-clear-polygon', function() {
                clearPolygon();
            });

            $(document).on('click', '#btn-place-marker', function() {
                if (isMarkerMode) {
                    setMarkerMode(false);
                } else {
                    setMarkerMode(true);
                }
            });

            // Update marker when lat/lng inputs change manually
            $(document).on('change', '#latitude, #longitude', function() {
                var lat = parseFloat($('#latitude').val());
                var lng = parseFloat($('#longitude').val());
                if (geoMap && geoMarker && !isNaN(lat) && !isNaN(lng)) {
                    var pos = { lat: lat, lng: lng };
                    geoMarker.setPosition(pos);
                    geoMap.setCenter(pos);
                }
            });

            $(document).on("click", ".AddGeoFancing", function() {
                $("#GeoFacing-modal").modal('show');

                var existingLat = $('#latitude').val();
                var existingLng = $('#longitude').val();

                if (existingLat && existingLng) {
                    setTimeout(function() { initGeoMap(existingLat, existingLng); }, 300);
                } else if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            if ($('#latitude').val() == '') {
                                $('#latitude').val(position.coords.latitude.toFixed(8));
                            }
                            if ($('#longitude').val() == '') {
                                $('#longitude').val(position.coords.longitude.toFixed(8));
                            }
                            setTimeout(function() {
                                initGeoMap($('#latitude').val(), $('#longitude').val());
                            }, 300);
                        },
                        function(error) {
                            console.error("Error obtaining location: ", error.message);
                            setTimeout(function() { initGeoMap(null, null); }, 300);
                        }
                    );
                } else {
                    setTimeout(function() { initGeoMap(null, null); }, 300);
                }
            });

            function getcheckit() {
                if ($('.flexSwitchCheckDefaultCheck').is(':checked')) {
                    $(".AddGeoFancing").show();
                    $("#openGeofenceManager").show();
                    $("#geofence-zones-preview").show();
                } else {
                    $(".AddGeoFancing").hide();
                    $("#openGeofenceManager").hide();
                    $("#geofence-zones-preview").hide();
                }
            }

            $('#GeoLocation').validate({
                rules: {
                    latitude: {
                        required: true

                    },
                    longitude: {
                        required: true
                    }
                },
                messages: {
                    latitude: {
                        required: "Please Calculate and enter Resort Latitude.",
                    },
                    longitude: {
                        required: "Please Calculate and enter Resort longitude..",
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.GeoFencing') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                                $("#GeoFacing-modal").modal("hide");

                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });
            // ── Geofence Zone Manager ──────────────────────────────────────

            var gfMap = null;
            var gfDrawingPolygon = null;      // Current polygon being drawn
            var gfDrawingCircle = null;       // Current circle being drawn
            var gfDrawingPath = [];           // Polygon vertices
            var gfDrawingMarkers = [];        // Vertex markers
            var gfCurrentTool = null;         // 'polygon' | 'circle' | null
            var gfCircleCenter = null;
            var gfCircleCenterMarker = null;
            var gfAllZoneOverlays = [];       // Rendered saved zones on map
            var gfZonesData = @json($geofenceZones ?? []);
            var gfColors = ['#FF4444','#4CAF50','#2196F3','#FF9800','#9C27B0','#00BCD4'];

            function initGeofenceMap() {
                if (gfMap) {
                    google.maps.event.trigger(gfMap, 'resize');
                    return;
                }

                var defaultCenter = { lat: parseFloat($('#latitude').val()) || 4.1755, lng: parseFloat($('#longitude').val()) || 73.5093 };

                gfMap = new google.maps.Map(document.getElementById('geofenceMap'), {
                    center: defaultCenter,
                    zoom: 15,
                    mapTypeId: 'hybrid',
                    streetViewControl: false,
                });

                // Map click — add polygon points or set circle center
                gfMap.addListener('click', function(e) {
                    if (gfCurrentTool === 'polygon') {
                        gfAddPolygonPoint(e.latLng);
                    } else if (gfCurrentTool === 'circle' && !gfCircleCenter) {
                        gfSetCircleCenter(e.latLng);
                    }
                });

                // Render saved zones
                renderSavedZones();
            }

            function renderSavedZones() {
                // Clear existing overlays
                gfAllZoneOverlays.forEach(function(o) { o.setMap(null); });
                gfAllZoneOverlays = [];

                gfZonesData.forEach(function(zone) {
                    try {
                        var coords = JSON.parse(zone.coordinates);
                        if (zone.shape_type === 'polygon' && Array.isArray(coords) && coords.length >= 3) {
                            var poly = new google.maps.Polygon({
                                paths: coords.map(function(c) { return { lat: c.lat, lng: c.lng }; }),
                                strokeColor: zone.color,
                                strokeOpacity: 0.9,
                                strokeWeight: 2,
                                fillColor: zone.color,
                                fillOpacity: zone.status === 'active' ? 0.25 : 0.08,
                                map: gfMap,
                            });
                            gfAllZoneOverlays.push(poly);
                        } else if (zone.shape_type === 'circle' && coords.center && coords.radius) {
                            var circ = new google.maps.Circle({
                                center: { lat: coords.center.lat, lng: coords.center.lng },
                                radius: coords.radius,
                                strokeColor: zone.color,
                                strokeOpacity: 0.9,
                                strokeWeight: 2,
                                fillColor: zone.color,
                                fillOpacity: zone.status === 'active' ? 0.25 : 0.08,
                                map: gfMap,
                            });
                            gfAllZoneOverlays.push(circ);
                        }
                    } catch(ex) { console.error('Zone render error:', ex); }
                });

                // Fit bounds to all zones
                if (gfAllZoneOverlays.length > 0) {
                    var bounds = new google.maps.LatLngBounds();
                    gfAllZoneOverlays.forEach(function(overlay) {
                        if (overlay instanceof google.maps.Polygon) {
                            overlay.getPath().forEach(function(p) { bounds.extend(p); });
                        } else if (overlay instanceof google.maps.Circle) {
                            bounds.union(overlay.getBounds());
                        }
                    });
                    gfMap.fitBounds(bounds);
                }

                renderZoneList();
            }

            function renderZoneList() {
                var html = '';
                if (!gfZonesData.length) {
                    html = '<div class="text-center text-muted py-4"><i class="fa-solid fa-map-location-dot fa-2x mb-2"></i><p class="small mb-0">No zones yet. Draw on the map to create one.</p></div>';
                } else {
                    gfZonesData.forEach(function(zone) {
                        var shapeIcon = zone.shape_type === 'circle' ? 'fa-circle' : 'fa-draw-polygon';
                        var shapeInfo = '';
                        try {
                            var coords = JSON.parse(zone.coordinates);
                            if (zone.shape_type === 'polygon') {
                                shapeInfo = coords.length + ' vertices';
                            } else if (zone.shape_type === 'circle') {
                                shapeInfo = Math.round(coords.radius) + 'm radius';
                            }
                        } catch(e) {}

                        html += '<div class="gf-zone-card" data-zone-id="' + zone.id + '">' +
                            '<div class="d-flex align-items-center">' +
                                '<span class="zone-dot" style="background:' + zone.color + ';"></span>' +
                                '<strong class="small">' + zone.name + '</strong>' +
                                '<span class="badge ms-auto ' + (zone.status === 'active' ? 'badge-themeSuccess' : 'badge-themeWarning') + '" style="font-size:10px;">' + zone.status + '</span>' +
                            '</div>' +
                            '<div class="d-flex align-items-center mt-1">' +
                                '<small class="text-muted"><i class="fa-solid ' + shapeIcon + ' me-1"></i>' + shapeInfo + ' &middot; ' + zone.grace_period + ' min grace</small>' +
                                '<div class="ms-auto">' +
                                    '<button class="btn btn-sm p-0 px-1 gf-edit-zone" data-id="' + zone.id + '" title="Edit"><i class="fa-solid fa-pen text-primary" style="font-size:11px;"></i></button>' +
                                    '<button class="btn btn-sm p-0 px-1 gf-toggle-zone" data-id="' + zone.id + '" title="' + (zone.status === 'active' ? 'Pause' : 'Activate') + '"><i class="fa-solid fa-' + (zone.status === 'active' ? 'pause' : 'play') + ' text-warning" style="font-size:11px;"></i></button>' +
                                    '<button class="btn btn-sm p-0 px-1 gf-delete-zone" data-id="' + zone.id + '" title="Delete"><i class="fa-solid fa-trash text-danger" style="font-size:11px;"></i></button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    });
                }
                $('#gf-zone-list').html(html);
            }

            // ── Drawing Tools ──

            function gfActivateTool(tool) {
                gfClearDrawing();
                gfCurrentTool = tool;
                $('#gf-tool-polygon').toggleClass('btn-primary btn-outline-primary', tool !== 'polygon').toggleClass('btn-primary', tool === 'polygon');
                $('#gf-tool-circle').toggleClass('btn-primary btn-outline-primary', tool !== 'circle').toggleClass('btn-primary', tool === 'circle');

                if (tool === 'polygon') {
                    $('#gf-draw-status').text('Click on the map to place polygon points. Double-click or click first point to close.');
                    gfMap.setOptions({ draggableCursor: 'crosshair' });
                } else if (tool === 'circle') {
                    $('#gf-draw-status').text('Click on the map to set the circle center, then drag to set radius.');
                    gfMap.setOptions({ draggableCursor: 'crosshair' });
                } else {
                    $('#gf-draw-status').text('');
                    gfMap.setOptions({ draggableCursor: null });
                }
            }

            function gfAddPolygonPoint(latLng) {
                gfDrawingPath.push(latLng);

                var marker = new google.maps.Marker({
                    position: latLng,
                    map: gfMap,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 7,
                        fillColor: getSelectedColor(),
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                    },
                    draggable: true,
                });

                var idx = gfDrawingPath.length - 1;
                marker.addListener('dragend', function(e) {
                    gfDrawingPath[idx] = e.latLng;
                    gfRedrawPolygon();
                });

                // Close polygon on clicking the first point
                if (idx === 0) {
                    marker.addListener('click', function() {
                        if (gfDrawingPath.length >= 3) {
                            gfFinishPolygon();
                        }
                    });
                }

                gfDrawingMarkers.push(marker);
                gfRedrawPolygon();
                $('#gf-tool-undo, #gf-tool-clear').prop('disabled', false);
                gfUpdateSaveButton();
            }

            function gfRedrawPolygon() {
                if (gfDrawingPolygon) gfDrawingPolygon.setMap(null);
                if (gfDrawingPath.length < 2) return;

                gfDrawingPolygon = new google.maps.Polygon({
                    paths: gfDrawingPath,
                    strokeColor: getSelectedColor(),
                    strokeOpacity: 0.9,
                    strokeWeight: 2,
                    fillColor: getSelectedColor(),
                    fillOpacity: 0.2,
                    map: gfMap,
                });
            }

            function gfFinishPolygon() {
                gfCurrentTool = null;
                gfMap.setOptions({ draggableCursor: null });
                $('#gf-draw-status').text('Polygon complete. Name it and save.');
                gfRedrawPolygon();
            }

            function gfSetCircleCenter(latLng) {
                gfCircleCenter = latLng;

                gfCircleCenterMarker = new google.maps.Marker({
                    position: latLng,
                    map: gfMap,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: getSelectedColor(),
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                    },
                    draggable: true,
                });

                // Start with 100m radius circle
                gfDrawingCircle = new google.maps.Circle({
                    center: latLng,
                    radius: 100,
                    strokeColor: getSelectedColor(),
                    strokeOpacity: 0.9,
                    strokeWeight: 2,
                    fillColor: getSelectedColor(),
                    fillOpacity: 0.2,
                    map: gfMap,
                    editable: true,
                });

                gfDrawingCircle.addListener('radius_changed', function() {
                    gfUpdateSaveButton();
                });
                gfDrawingCircle.addListener('center_changed', function() {
                    gfCircleCenter = gfDrawingCircle.getCenter();
                    gfCircleCenterMarker.setPosition(gfCircleCenter);
                });

                gfCircleCenterMarker.addListener('dragend', function(e) {
                    gfCircleCenter = e.latLng;
                    gfDrawingCircle.setCenter(e.latLng);
                });

                $('#gf-draw-status').text('Drag the circle edge to adjust radius. Name it and save.');
                gfCurrentTool = null;
                gfMap.setOptions({ draggableCursor: null });
                $('#gf-tool-undo, #gf-tool-clear').prop('disabled', false);
                gfUpdateSaveButton();
            }

            function gfClearDrawing() {
                if (gfDrawingPolygon) { gfDrawingPolygon.setMap(null); gfDrawingPolygon = null; }
                if (gfDrawingCircle) { gfDrawingCircle.setMap(null); gfDrawingCircle = null; }
                if (gfCircleCenterMarker) { gfCircleCenterMarker.setMap(null); gfCircleCenterMarker = null; }
                gfDrawingMarkers.forEach(function(m) { m.setMap(null); });
                gfDrawingMarkers = [];
                gfDrawingPath = [];
                gfCircleCenter = null;
                gfCurrentTool = null;
                gfMap && gfMap.setOptions({ draggableCursor: null });
                $('#gf-draw-status').text('');
                $('#gf-tool-undo, #gf-tool-clear').prop('disabled', true);
                $('#gf-tool-polygon, #gf-tool-circle').removeClass('btn-primary').addClass('btn-outline-primary');
                gfUpdateSaveButton();
            }

            function gfUndoLastPoint() {
                if (gfCurrentTool === 'polygon' && gfDrawingPath.length > 0) {
                    gfDrawingPath.pop();
                    var m = gfDrawingMarkers.pop();
                    if (m) m.setMap(null);
                    gfRedrawPolygon();
                    if (gfDrawingPath.length === 0) {
                        $('#gf-tool-undo, #gf-tool-clear').prop('disabled', true);
                    }
                    gfUpdateSaveButton();
                }
            }

            function getSelectedColor() {
                return $('#gf-color-picker .gf-color-dot.active').data('color') || '#FF4444';
            }

            function gfUpdateSaveButton() {
                var hasShape = gfDrawingPath.length >= 3 || (gfDrawingCircle && gfCircleCenter);
                $('#gf-save-zone').prop('disabled', !hasShape);
            }

            function gfGetCoordinatesJSON() {
                if (gfDrawingPath.length >= 3) {
                    return {
                        type: 'polygon',
                        coords: JSON.stringify(gfDrawingPath.map(function(p) {
                            return { lat: parseFloat(p.lat().toFixed(8)), lng: parseFloat(p.lng().toFixed(8)) };
                        }))
                    };
                } else if (gfDrawingCircle && gfCircleCenter) {
                    return {
                        type: 'circle',
                        coords: JSON.stringify({
                            center: { lat: parseFloat(gfCircleCenter.lat().toFixed(8)), lng: parseFloat(gfCircleCenter.lng().toFixed(8)) },
                            radius: Math.round(gfDrawingCircle.getRadius())
                        })
                    };
                }
                return null;
            }

            // ── Button Events ──

            $(document).on('click', '#gf-tool-polygon', function() { gfActivateTool('polygon'); });
            $(document).on('click', '#gf-tool-circle', function() { gfActivateTool('circle'); });
            $(document).on('click', '#gf-tool-undo', function() { gfUndoLastPoint(); });
            $(document).on('click', '#gf-tool-clear', function() { gfClearDrawing(); });

            $(document).on('click', '.gf-color-dot', function() {
                $('.gf-color-dot').removeClass('active');
                $(this).addClass('active');
            });

            // Open Geofence Manager
            $(document).on('click', '#openGeofenceManager', function() {
                $('#GeofenceManager-modal').modal('show');
                setTimeout(function() { initGeofenceMap(); }, 300);
            });

            // Double-click to close polygon
            $(document).on('dblclick', '#geofenceMap', function() {
                if (gfCurrentTool === 'polygon' && gfDrawingPath.length >= 3) {
                    gfFinishPolygon();
                }
            });

            // Save Zone
            $(document).on('click', '#gf-save-zone', function() {
                var name = $('#gf-zone-name').val().trim();
                if (!name) {
                    toastr.warning('Please enter a zone name.', '', { positionClass: 'toast-bottom-right' });
                    return;
                }
                var coordData = gfGetCoordinatesJSON();
                if (!coordData) {
                    toastr.warning('Please draw a polygon or circle first.', '', { positionClass: 'toast-bottom-right' });
                    return;
                }

                var editId = $('#gf-edit-id').val();
                var url = editId
                    ? "{{ url('resort/time-and-attendance/geofences/update') }}/" + editId
                    : "{{ route('resort.geofences.store') }}";

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        name: name,
                        color: getSelectedColor(),
                        shape_type: coordData.type,
                        coordinates: coordData.coords,
                        grace_period: $('#gf-grace-period').val(),
                    },
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                            $('#GeofenceManager-modal').modal('hide');
                            setTimeout(function() { location.reload(); }, 500);
                        } else {
                            toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to save zone.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                });
            });

            // Cancel Edit
            $(document).on('click', '#gf-cancel-edit', function() {
                gfClearDrawing();
                gfResetForm();
            });

            // Edit Zone
            $(document).on('click', '.gf-edit-zone', function() {
                var zoneId = $(this).data('id');
                var zone = gfZonesData.find(function(z) { return z.id == zoneId; });
                if (!zone) return;

                gfClearDrawing();
                $('#gf-edit-id').val(zone.id);
                $('#gf-zone-name').val(zone.name);
                $('#gf-grace-period').val(zone.grace_period);
                $('#gf-form-title').text('Edit Zone');
                $('#gf-cancel-edit').show();

                // Set color
                $('.gf-color-dot').removeClass('active');
                var colorMatch = $('.gf-color-dot[data-color="' + zone.color + '"]');
                if (colorMatch.length) colorMatch.addClass('active');
                else $('.gf-color-dot').first().addClass('active');

                // Load coordinates for editing
                try {
                    var coords = JSON.parse(zone.coordinates);
                    if (zone.shape_type === 'polygon' && Array.isArray(coords)) {
                        coords.forEach(function(c) {
                            gfAddPolygonPoint(new google.maps.LatLng(c.lat, c.lng));
                        });
                        gfFinishPolygon();
                    } else if (zone.shape_type === 'circle' && coords.center) {
                        gfSetCircleCenter(new google.maps.LatLng(coords.center.lat, coords.center.lng));
                        gfDrawingCircle.setRadius(coords.radius || 100);
                    }
                } catch(e) {}

                gfUpdateSaveButton();
            });

            // Toggle Zone Status
            $(document).on('click', '.gf-toggle-zone', function() {
                var zoneId = $(this).data('id');
                $.ajax({
                    url: "{{ url('resort/time-and-attendance/geofences/toggle') }}/" + zoneId,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                            gfRefreshZones();
                        }
                    }
                });
            });

            // Delete Zone
            $(document).on('click', '.gf-delete-zone', function() {
                var zoneId = $(this).data('id');
                if (!confirm('Are you sure you want to delete this zone?')) return;
                $.ajax({
                    url: "{{ url('resort/time-and-attendance/geofences/delete') }}/" + zoneId,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                            gfRefreshZones();
                        }
                    }
                });
            });

            function gfRefreshZones() {
                $.get("{{ route('resort.geofences.list') }}", function(res) {
                    if (res.success) {
                        gfZonesData = res.zones;
                        renderSavedZones();
                    }
                });
            }

            function gfResetForm() {
                $('#gf-edit-id').val('');
                $('#gf-zone-name').val('');
                $('#gf-grace-period').val('10');
                $('#gf-form-title').text('Add New Zone');
                $('#gf-cancel-edit').hide();
                $('#gf-save-zone').prop('disabled', true);
                $('.gf-color-dot').removeClass('active').first().addClass('active');
            }

            // Config page inline table actions — edit opens the GeofenceManager modal with zone loaded
            $(document).on('click', '.gf-edit-zone-config', function() {
                var zoneId = $(this).data('id');
                var zone = gfZonesData.find(function(z) { return z.id == zoneId; });
                if (!zone) {
                    // fallback: fetch fresh data
                    zone = @json($geofenceZones ?? []).find(function(z) { return z.id == zoneId; });
                }
                if (!zone) return;

                // Open the GeofenceManager modal
                $('#GeofenceManager-modal').modal('show');
                setTimeout(function() {
                    initGeofenceMap();

                    // Populate edit form
                    gfClearDrawing();
                    $('#gf-edit-id').val(zone.id);
                    $('#gf-zone-name').val(zone.name);
                    $('#gf-grace-period').val(zone.grace_period);
                    $('#gf-form-title').text('Edit Zone');
                    $('#gf-cancel-edit').show();

                    // Set color
                    $('.gf-color-dot').removeClass('active');
                    var colorMatch = $('.gf-color-dot[data-color="' + zone.color + '"]');
                    if (colorMatch.length) colorMatch.addClass('active');
                    else $('.gf-color-dot').first().addClass('active');

                    // Load coordinates
                    try {
                        var coords = JSON.parse(zone.coordinates);
                        if (zone.shape_type === 'polygon' && Array.isArray(coords)) {
                            coords.forEach(function(c) {
                                gfAddPolygonPoint(new google.maps.LatLng(c.lat, c.lng));
                            });
                            gfFinishPolygon();
                            // Fit bounds
                            var bounds = new google.maps.LatLngBounds();
                            gfDrawingPath.forEach(function(p) { bounds.extend(p); });
                            gfMap.fitBounds(bounds);
                        } else if (zone.shape_type === 'circle' && coords.center) {
                            gfSetCircleCenter(new google.maps.LatLng(coords.center.lat, coords.center.lng));
                            gfDrawingCircle.setRadius(coords.radius || 100);
                            gfMap.fitBounds(gfDrawingCircle.getBounds());
                        }
                    } catch(e) { console.error('Error loading zone:', e); }

                    gfUpdateSaveButton();
                }, 400);
            });

            $(document).on('click', '.gf-toggle-zone-config', function() {
                var zoneId = $(this).data('id');
                $.ajax({
                    url: "{{ url('resort/time-and-attendance/geofences/toggle') }}/" + zoneId,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                            setTimeout(function() { location.reload(); }, 500);
                        }
                    }
                });
            });

            $(document).on('click', '.gf-delete-zone-config', function() {
                var zoneId = $(this).data('id');
                if (!confirm('Delete this geofence zone?')) return;
                $.ajax({
                    url: "{{ url('resort/time-and-attendance/geofences/delete') }}/" + zoneId,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        if (res.success) {
                            toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                            setTimeout(function() { location.reload(); }, 500);
                        }
                    }
                });
            });

            // ── End Geofence Manager ──

            $(document).on("click", ".AddPublicHolidays", function() {

                $("#PublicHoliday-modal").modal('show');
                $("#PublicHoliday").select2();
            });

            $(document).on("change", "#PublicHoliday", function() {
                let selectedOption = $(this).find(':selected');
                let dataId = selectedOption.data('id');

                if (dataId == 0) {
                    $("#PublicHolidaydate").attr('disabled', false);
                    $("#PublicHolidayName").attr('disabled', false);
                    $("#ResortPublicHolidayDate").val(''); // Clear the date
                } else {
                    $("#PublicHolidaydate").attr('disabled', true);
                    $("#PublicHolidayName").attr('disabled', true);

                    let dates = selectedOption.data('date');
                    if (dates) {
                        // Ensure the date is in YYYY-MM-DD format
                        let formattedDate = '';

                        // Handle different date format possibilities
                        if (dates.includes('-')) {
                            // If already in YYYY-MM-DD format
                            formattedDate = dates;
                        } else if (dates.includes('/')) {
                            // Convert from DD/MM/YYYY to YYYY-MM-DD
                            let parts = dates.split('/');
                            formattedDate =
                                `${parts[2]}/${parts[1].padStart(2, '0')}/${parts[0].padStart(2, '0')}`;
                        }



                        $("#ResortPublicHolidayDate").val(formattedDate);
                        $("#ResortPublicHolidayDate").show();
                    } else {
                        $("#ResortPublicHolidayDate").val('');
                    }
                }
            });



            $(document).on("keyup", "#PublicHolidaydate , #PublicHolidayName", function() {

                if ($(this).val() != "") {
                    $("#PublicHoliday").attr('disabled', true);
                } else {
                    $("#PublicHoliday").attr('disabled', false);
                }

            });




            $.validator.addMethod(
                "requirePublicHolidayNameOrDate",
                function(value, element) {
                    // Check if the current field is valid based on the other field's value
                    if ($('#PublicHolidayName').val() !== '' && $('#PublicHolidaydate').val() !== '') {
                        return false; // Both fields cannot be filled
                    }
                    return true; // Valid if only one is filled
                },
                "You cannot fill both Holiday Name and Holiday Date. Please choose one."
            );

            $('#PublicHolidayForm').validate({
                rules: {
                    PublicHolidaydate: {
                        required: function() {
                            // Validate PublicHolidaydate if both other fields are empty
                            return $('#PublicHolidayName').val() === '' && $('#PublicHolidaydate')
                            .val() === '';
                        }
                    },
                    PublicHolidayName: {
                        required: function() {
                            // Validate PublicHolidayName if PublicHolidaydate is disabled
                            return $('#PublicHolidaydate').is(':disabled');
                        }
                    },
                    PublicHolidaydate: {
                        required: function() {
                            // Validate PublicHoliday if PublicHolidaydate is disabled
                            return $('#PublicHolidaydate').is(':disabled');
                        }
                    }
                },
                messages: {
                    PublicHolidaydate: {
                        required: "Please select a Holiday Date if Name and Public Holiday are empty."
                    },
                    PublicHolidayName: {
                        required: "Please enter a Holiday Name if Holiday Date is disabled."
                    },
                    PublicHolidaydate: {
                        required: "Please select a Public Holiday if Holiday Date is disabled."
                    }
                },
                submitHandler: function(form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.timeandattendance.ResortHoliday') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#respond-HoldModel').modal('hide');
                            $("#PublicHoliday-modal").modal('hide');
                            if (response.success) {


                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                             $("#PublicHoliday-modal").modal('hide');
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key,
                            error) { // Adjust according to your response format
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

        function displayImportFileName() {
            var fileInput = document.getElementById('UploadImportattandance');
            var fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file selected';


            document.getElementById('fileNameImportFile').innerText = fileName;

            // Trigger validation and submit only if valid
            var $form = $('#ImportAttandanceForm');

            if (fileInput.files.length > 0) {
                if ($form.valid()) {
                    $form.submit(); // This will call the `submitHandler` in `.validate({})`
                }
            } else {
                toastr.error('Please select a file before submitting.', "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        }
        function UplodeAttandancedisplayFileName() {
            var fileInput = document.getElementById('fileUpload');
            var fileName = fileInput.files[0] ? fileInput.files[0].name : 'No file selected';
            document.getElementById('fileName').innerText = fileName;
            $('#fileUploadForm').submit();

        }


        document.addEventListener('DOMContentLoaded', function() {
            // console.log('DOM fully loaded and parsed');

            const container = document.getElementById('color-theme-container');
            const addMoreButton = document.getElementById('add-more-theme');
            const submitButton = document.getElementById('submit-themes');

            // Dynamically add new color theme inputs
            addMoreButton.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent page reload
                let index = container.children.length; // Get the number of current rows
                console.log(index);
                let newThemeHTML = `
                <div class="col-sm-6 theme-row" data-theme-id="new">
                    <div class="inputCustom-color">
                        <input type="text" name="themes[${index}][name]" placeholder="Label (e.g., Overtime)" class="form-control mb-2">
                        <input type="color" name="themes[${index}][color]" value="#000000" style="top: 28% !important;">
                        <button type="button" class="btn btn-danger remove-theme-btn">Remove</button>
                    </div>
                </div>
            `;
                container.insertAdjacentHTML('beforeend', newThemeHTML); // Append new theme inputs
            });

            // Handle the "Remove" button click event
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-theme-btn')) {
                    e.preventDefault();
                    const themeRow = e.target.closest('.theme-row');
                    const themeId = themeRow.getAttribute('data-theme-id');
                    console.log(themeId); // This will log the correct theme ID

                    if (themeId !== 'new') {
                        // Send a request to delete the theme from the database
                        const formData = new FormData();
                        formData.append('theme_id', themeId); // Append the theme ID to FormData

                        fetch("{{ route('colorThemes.delete') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content,
                                },
                                body: formData, // Send FormData instead of JSON
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    toastr.success(data.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    themeRow.remove(); // Remove the row from the UI
                                } else {
                                    toastr.error(data.message, "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            })
                            .catch(err => {
                                toastr.error("An error occurred. Please try again.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                    } else {
                        themeRow.remove(); // Remove the row if it's newly added (not saved yet)
                    }
                }
            });


            // Submit form via AJAX
            submitButton.addEventListener('click', function() {
                const form = document.getElementById('color-theme-form');
                // console.log(form);
                let formData = new FormData(form);

                fetch("{{ route('colorThemes.save') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {

                        if (data.success == true) {
                            toastr.success(data.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            }); // Show success message

                            // Clear the current theme container
                            const container = document.getElementById('color-theme-container');
                            container.innerHTML = ''; // Remove all existing rows

                            // Dynamically add saved themes
                            data.themes.forEach((theme, index) => {
                                let themeHTML = `
                            <div class="col-sm-6 theme-row" data-theme-id="${theme.id}">
                                <div class="inputCustom-color">
                                    <input type="text" name="themes[${index}][name]" placeholder="Label" class="form-control mb-2" value="${theme.name}">
                                    <input type="color" name="themes[${index}][color]" value="${theme.color}" style="top: 28% !important;">
                                    <button type="button" class="btn btn-danger remove-theme-btn">Remove</button>
                                </div>
                            </div>
                        `;
                                container.insertAdjacentHTML('beforeend', themeHTML);
                            });
                        } else {
                            toastr.error(data.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            }); // Show error message
                        }
                    });
            });
        });
    </script>
    <script>
        // Validate file extension on file input change
        document.getElementById('UploadImportattandance').addEventListener('change', function() {
            const fileInput = this;
            const filePath = fileInput.value;
            const allowedExtensions = /(\.xls|\.xlsx)$/i;
            const errorSpan = document.getElementById('file-extension-error');
            if (!allowedExtensions.exec(filePath)) {
                errorSpan.textContent = 'Only .xls or .xlsx files are allowed.';
                errorSpan.style.display = 'block';
                fileInput.value = '';
                document.getElementById('fileNameImportFile').innerText = '';
            } else {
                errorSpan.style.display = 'none';
            }
        });


    </script>
@endsection
