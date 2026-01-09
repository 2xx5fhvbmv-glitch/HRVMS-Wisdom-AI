@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')   
<div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>SOS</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
                </div>
            </div>

            <div class="card">
                <div class="row g-md-4 g-3">
                    <div class="col-xl-8 col-lg-7">
                        <div class="sosResortMap-map">
                            {{-- <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d118106.62874800457!2d73.01718890467897!3d22.322186957943526!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395fc8ab91a3ddab%3A0xac39d3bfe1473fb8!2sVadodara%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1739533430153!5m2!1sen!2sin"
                                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe> --}}

                            <div id="map" style="height: 600px;"></div>
                        </div>
                    </div>
                    <input type="hidden" id="sos_history_id" value="{{ $id }}">
                    <div class="col-xl-4 col-lg-5">
                        <div class="sosResortMap-block">
                            <form id="employeeFilterForm">
                            @csrf
                                <div class="row g-2">
                                    <div class="col-sm-6"> 
                                        <select class="form-select select2t-none" id="roleFilter" name="roleId" aria-label="Default select example">
                                            <option  value="">Select role</option>
                                            {{-- @foreach($Roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach --}}

                                            @foreach(config('settings.Position_Rank') as $key => $rank)
                                                <option value="{{ $key }}">{{ $rank }}</option>
                                            @endforeach

                                        </select>
                                    </div>
                                    <div class="col-sm-6"> 
                                        <select class="form-select select2t-none" id="teamFilter" name="teamId" aria-label="Default select example">
                                            <option value="">Select Team</option>
                                            @foreach($getAllTeams as $team)
                                                <option value="{{ $team->team->id }}">{{ $team->team->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6"> 
                                        <select class="form-select select2t-none" id="statusFilter" name="safety_status" aria-label="Default select example">
                                            <option value="">Safety Status</option>
                                            <option value="safe">Safe</option>
                                            <option value="Unsafe">Unsafe</option>
                                            <option value="Unknown">Unknown</option>
                                        </select></div>
                                    <div class="col-sm-6 d-flex">
                                        <button type="submit" class="btn btn-themeBlue btn-sm mx-1">Submit</button>
                                        <button type="button" id="resetFilterBtn" class="btn btn-themeSkyblueLight btn-sm">Reset</button>
                                    </div>
                                    <div id="filterError" class="text-danger small mt-1"></div>
                                </div>
                            </form>
                            <hr class="mb-md-4 mb-3">
                            
                            <div id="employeStatusSection">
                                @include('resorts.renderfiles.EmployeeListLiveLocationView', ['employeesStatusList' => $employeesStatusList])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBZjz2AtrseoGKhTyZfTeZoUVvD9aFSS6Q"></script>
<script>

    let map;
    let markers = {};
    let currentFilter = null;

    const resortLat = {{ $lat }};
    const resortLng = {{ $lng }};

    let dangerZone = null;
    let dangerRadiusMeters = 500;
    let inDangerCount = 0;

    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            // center: { lat: 22.3152, lng: 73.1444 },
            center: { lat: resortLat, lng: resortLng },
            zoom: 14
        });

        dangerZone = new google.maps.Circle({
            strokeColor: "#FF0000",
            strokeOpacity: 0.8,
            strokeWeight: 1,
            fillColor: "#FF0000",
            fillOpacity: 0.15,
            map: map,
            center: { lat: resortLat, lng: resortLng },
            radius: dangerRadiusMeters
        });

        loadLiveData();
        setInterval(loadLiveData, 10000); // Auto-refresh every 10s
    }

    function updateMapMarkers(locations) {
        
        // Clear markers not in new data
        Object.keys(markers).forEach(id => {
            if (!locations.find(loc => loc.id == id)) {
                markers[id].setMap(null);
                delete markers[id];
            }
        });

        // Add/update markers
        locations.forEach(user => {
            const pos = { lat: user.lat, lng: user.lng };
            if (markers[user.id]) {
                markers[user.id].setPosition(pos);
            } else {
                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: `${user.name} (${user.status})`,
                    icon: (user.status === 'Unsafe' || user.status === 'Unknown') ? 'http://maps.google.com/mapfiles/ms/icons/red-dot.png' : 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="display: flex; align-items: center; padding: 5px;">
                            <img src="${user.image}" alt="${user.name}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">
                            <div>
                                <div style="font-weight:500; font-size:14px; margin-bottom: 5px">${user.name}</div>
                                <div style="font-size:12px; color:##666666; margin-bottom: 5px">${user.department}</div>
                                <div style="font-size:12px; color:##666666; margin-bottom: 5px">${user.role}</div>
                                <div style="color: ${(user.status === 'Unsafe' || user.status === 'Unknown') ? '#dc3545' : '#198754'}; font-size:12px;">${user.status}</div>
                            </div>
                        </div>`,
                    pixelOffset: new google.maps.Size(0, -5),
                    disableAutoPan: false
                });

                // Add custom styles to remove default InfoWindow chrome
                const iwOuter = $('.gm-style-iw');
                iwOuter.css({
                    'background-color': 'white',
                    'box-shadow': '0 2px 7px 1px rgba(0,0,0,0.3)',
                    'border-radius': '5px',
                    'padding': '0'
                });
                iwOuter.parent().parent().css({background: 'none'});


                marker.addListener("click", () => {
                    infoWindow.open(map, marker);
                });

                markers[user.id] = marker;

            }
        });
    }

    function loadLiveData() {
        const sosId = $('#sos_history_id').val();
        const apiUrl = "{{ route('sos.filterMapEmployeeList', ':id') }}".replace(':id', sosId);

        const dataPayload = {
            "_token": "{{ csrf_token() }}"
        };

        // Apply current filter if set
        if (currentFilter) {
            dataPayload.teamId = currentFilter.teamId;
            dataPayload.roleId = currentFilter.roleId;
            dataPayload.safety_status = currentFilter.status;
        }

        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: dataPayload,
            success: function(response) {
                if (response.success) {
                    updateMapMarkers(response.locations);
                }
            },
            error: function(error) {
                console.error('Error fetching locations:', error);
            }
        });
    }

    window.onload = initMap;
    
    $(document).ready(function () {
        
        /* $('#teamFilter, #statusFilter, #roleFilter').change(function () {
            let sosHistoryId = $('#sos_history_id').val();
            let teamId = $("#teamFilter").val();
            let status = $('#statusFilter').val();
            let roleId = $('#roleFilter').val();

            $.ajax({
                url: "{{ route('sos.filterMapEmployeeList', '') }}/" + sosHistoryId,
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    teamId: teamId,
                    roleId: roleId,
                    show_status: status,
                },
                success: function(data) {
                    if (data.success) {
                        $("#employeStatusSection").html(data.html);
                    } else {
                        toastr.error("Something went wrong.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';

                    if(errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = "An unexpected error occurred.";
                    }

                    toastr.error(errs, "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        }); */
         
        $('#employeeFilterForm').parsley();
        
        $('#roleFilter, #teamFilter, #statusFilter').on('change', function () {
            const role = $('#roleFilter').val();
            const team = $('#teamFilter').val();
            const status = $('#statusFilter').val();

            if (role || team || status) {
                $('#filterError').text('');
            }
        });

        // Update form submit handler
        $('#employeeFilterForm').on('submit', function (e) {
            e.preventDefault();
            
            const role = $('#roleFilter').val();
            const team = $('#teamFilter').val();
            const status = $('#statusFilter').val();

            // Validate: must select at least one
            if (!role && !team && !status) {
                $('#filterError').text('Please select at least one filter (Role, Team, or Safety Status).');
                return;
            } else {
                $('#filterError').text(''); 
            }

            let sosHistoryId = $('#sos_history_id').val();
            let form = $(this);

            if ($(this).parsley().isValid()) {
                let formData = new FormData(this);
                
                $.ajax({
                    url: "{{ route('sos.filterMapEmployeeList', '') }}/" + sosHistoryId,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.success) {
                            $("#employeStatusSection").html(data.html);
                            $('#employeStatusSection').parsley().reset();
                            updateMapMarkers(data.locations);

                            // Store current filter in JS
                            currentFilter = {
                                teamId: $("#teamFilter").val(),
                                roleId: $("#roleFilter").val(),
                                status: $("#statusFilter").val()
                            };
                        } else {
                            toastr.error("Something went wrong.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';

                        if (errors && errors.errors) {
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                        } else {
                            errs = "An unexpected error occurred.";
                        }

                        toastr.error(errs, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            }
        });

        $('#resetFilterBtn').on('click', function () {
            $('#employeeFilterForm')[0].reset();
            $('#roleFilter').val('').trigger('change');
            $('#teamFilter').val('').trigger('change'); 
            $('#statusFilter').val('').trigger('change'); 
            $('#filterError').text('');
            $('#employeeFilterForm').parsley().reset();
            currentFilter = null;
            loadLiveData(); // Load all employees

            // Optionally reload the employee list UI too   
            const sosId = $('#sos_history_id').val();
            const apiUrl = "{{ route('sos.filterMapEmployeeList', ':id') }}".replace(':id', sosId);

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        $("#employeStatusSection").html(response.html);
                    }
                },
                error: function(error) {
                    console.error('Error resetting filter:', error);
                }
            });
        });

    });

</script>
@endsection