@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <a href="{{ route('resort.timeandattendance.Configration') }}" class="btn btn-sm taa-btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                        </a>
                        <button type="button" class="btn btn-sm taa-btn-positive" id="openAddZoneModal">
                            <i class="fa-solid fa-plus"></i> Add New Zone
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="geofenceTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Zone Name</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Grace Period</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="width:120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($geofenceZones as $index => $zone)
                                    @php
                                        $coords = json_decode($zone->coordinates, true);
                                        $shapeInfo = '';
                                        if ($zone->shape_type === 'polygon' && is_array($coords)) {
                                            $shapeInfo = count($coords) . ' vertices';
                                        } elseif ($zone->shape_type === 'circle' && isset($coords['center'])) {
                                            $shapeInfo = round($coords['radius'] ?? 0) . 'm radius';
                                        }
                                    @endphp
                                    <tr data-zone-id="{{ $zone->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:{{ $zone->color }}; margin-right:6px; vertical-align:middle;"></span>
                                            <strong>{{ $zone->name }}</strong>
                                        </td>
                                        <td>
                                            <i class="fa-solid fa-{{ $zone->shape_type === 'circle' ? 'circle' : 'draw-polygon' }}" style="color:{{ $zone->color }};"></i>
                                            {{ ucfirst($zone->shape_type) }}
                                        </td>
                                        <td><small class="text-muted">{{ $shapeInfo }}</small></td>
                                        <td>{{ $zone->grace_period }} min</td>
                                        <td>
                                            <span class="badge {{ $zone->status === 'active' ? 'badge-themeSuccess' : 'badge-themeWarning' }}">
                                                {{ ucfirst($zone->status) }}
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $zone->created_at }}</small></td>
                                        <td>
                                            <div class="d-flex flex-nowrap gap-1">
                                                <button class="btn btn-sm taa-btn-secondary gfp-edit-zone" data-id="{{ $zone->id }}" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button class="btn btn-sm taa-btn-attention gfp-toggle-zone" data-id="{{ $zone->id }}" title="{{ $zone->status === 'active' ? 'Pause' : 'Activate' }}">
                                                    <i class="fa-solid fa-{{ $zone->status === 'active' ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button class="btn btn-sm taa-btn-critical gfp-delete-zone" data-id="{{ $zone->id }}" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-map-location-dot fa-2x mb-2 d-block"></i>
                                            No geofence zones configured yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- Add/Edit Zone Modal with Map --}}
<div class="modal fade" id="ZoneFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="zoneModalTitle"><i class="fa-solid fa-draw-polygon me-2"></i>Add New Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    {{-- Map --}}
                    <div class="col-lg-8">
                        <div id="zoneMap" style="width:100%; height:500px;"></div>
                        <div class="d-flex align-items-center gap-2 p-2 border-top bg-light">
                            <button type="button" id="zf-tool-polygon" class="btn btn-sm taa-btn-secondary">
                                <i class="fa-solid fa-draw-polygon"></i> Polygon
                            </button>
                            <button type="button" id="zf-tool-circle" class="btn btn-sm taa-btn-secondary">
                                <i class="fa-regular fa-circle"></i> Circle
                            </button>
                            <button type="button" id="zf-tool-undo" class="btn btn-sm taa-btn-secondary" disabled>
                                <i class="fa-solid fa-undo"></i> Undo
                            </button>
                            <button type="button" id="zf-tool-clear" class="btn btn-sm taa-btn-secondary" disabled>
                                <i class="fa-solid fa-trash"></i> Clear
                            </button>
                            <span id="zf-draw-status" class="text-muted small ms-auto"></span>
                        </div>
                    </div>
                    {{-- Form --}}
                    <div class="col-lg-4 border-start p-3">
                        <input type="hidden" id="zf-edit-id" value="">
                        <div class="mb-3">
                            <label class="form-label">Zone Name <span class="text-danger">*</span></label>
                            <input type="text" id="zf-zone-name" class="form-control" placeholder="e.g. Main Building">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <div class="d-flex gap-2 align-items-center flex-wrap" id="zf-color-picker">
                                <span class="zf-color-dot active" data-color="#FF4444" style="background:#FF4444;"></span>
                                <span class="zf-color-dot" data-color="#4CAF50" style="background:#4CAF50;"></span>
                                <span class="zf-color-dot" data-color="#2196F3" style="background:#2196F3;"></span>
                                <span class="zf-color-dot" data-color="#FF9800" style="background:#FF9800;"></span>
                                <span class="zf-color-dot" data-color="#9C27B0" style="background:#9C27B0;"></span>
                                <span class="zf-color-dot" data-color="#00BCD4" style="background:#00BCD4;"></span>
                                <label class="zf-color-dot" style="position:relative; cursor:pointer; background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red); border: 2px solid transparent;" title="Custom color">
                                    <input type="color" id="zf-custom-color" value="#FF4444" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grace Period</label>
                            <select id="zf-grace-period" class="form-select">
                                <option value="5">5 minutes</option>
                                <option value="10" selected>10 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                            </select>
                        </div>
                        <div class="mb-3" id="zf-coords-info" style="display:none;">
                            <label class="form-label">Coordinates <span class="badge bg-secondary" id="zf-point-count">0</span></label>
                            <div id="zf-coords-list" style="max-height:150px; overflow-y:auto; font-size:12px; background:#f8f9fa; border-radius:6px; padding:8px;"></div>
                        </div>
                        <hr>
                        <button type="button" id="zf-save-zone" class="btn taa-btn-primary w-100" disabled>
                            <i class="fa-solid fa-check"></i> Save Zone
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .zf-color-dot {
        display: inline-block; width: 28px; height: 28px; border-radius: 50%;
        cursor: pointer; border: 3px solid transparent; transition: border-color 0.2s;
    }
    .zf-color-dot:hover, .zf-color-dot.active { border-color: #333; }
</style>
@endsection

@section('import-css')
@include('resorts.timeandattendance._taa_buttons_v2_styles')
@endsection

@section('import-scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=drawing"></script>
<script type="text/javascript">
$(document).ready(function() {

    var zfMap = null;
    var zfDrawingPolygon = null;
    var zfDrawingCircle = null;
    var zfDrawingPath = [];
    var zfDrawingMarkers = [];
    var zfCurrentTool = null;
    var zfCircleCenter = null;
    var zfCircleCenterMarker = null;
    var zfAllZoneOverlays = [];
    var zonesData = @json($geofenceZones);

    function initZoneMap() {
        if (zfMap) {
            google.maps.event.trigger(zfMap, 'resize');
            return;
        }

        var defaultLat = {{ isset($ResortGeoLocation) && $ResortGeoLocation->latitude ? $ResortGeoLocation->latitude : 4.1755 }};
        var defaultLng = {{ isset($ResortGeoLocation) && $ResortGeoLocation->longitude ? $ResortGeoLocation->longitude : 73.5093 }};

        zfMap = new google.maps.Map(document.getElementById('zoneMap'), {
            center: { lat: defaultLat, lng: defaultLng },
            zoom: 15,
            mapTypeId: 'hybrid',
            streetViewControl: false,
        });

        zfMap.addListener('click', function(e) {
            if (zfCurrentTool === 'polygon') {
                zfAddPolygonPoint(e.latLng);
            } else if (zfCurrentTool === 'circle' && !zfCircleCenter) {
                zfSetCircleCenter(e.latLng);
            }
        });

        // Render all saved zones as background
        renderAllZonesOnMap();
    }

    function renderAllZonesOnMap() {
        zfAllZoneOverlays.forEach(function(o) { o.setMap(null); });
        zfAllZoneOverlays = [];

        zonesData.forEach(function(zone) {
            try {
                var coords = JSON.parse(zone.coordinates);
                if (zone.shape_type === 'polygon' && Array.isArray(coords) && coords.length >= 3) {
                    var poly = new google.maps.Polygon({
                        paths: coords.map(function(c) { return { lat: c.lat, lng: c.lng }; }),
                        strokeColor: zone.color,
                        strokeOpacity: 0.5,
                        strokeWeight: 1,
                        fillColor: zone.color,
                        fillOpacity: 0.1,
                        map: zfMap,
                    });
                    zfAllZoneOverlays.push(poly);
                } else if (zone.shape_type === 'circle' && coords.center && coords.radius) {
                    var circ = new google.maps.Circle({
                        center: { lat: coords.center.lat, lng: coords.center.lng },
                        radius: coords.radius,
                        strokeColor: zone.color,
                        strokeOpacity: 0.5,
                        strokeWeight: 1,
                        fillColor: zone.color,
                        fillOpacity: 0.1,
                        map: zfMap,
                    });
                    zfAllZoneOverlays.push(circ);
                }
            } catch(ex) {}
        });
    }

    function getZfColor() {
        var activeDot = $('#zf-color-picker .zf-color-dot.active');
        if (activeDot.find('#zf-custom-color').length) {
            return $('#zf-custom-color').val();
        }
        return activeDot.data('color') || '#FF4444';
    }

    // ── Drawing Tools ──

    function zfActivateTool(tool) {
        zfClearDrawing();
        zfCurrentTool = tool;
        $('#zf-tool-polygon').toggleClass('taa-btn-primary', tool === 'polygon').toggleClass('taa-btn-secondary', tool !== 'polygon');
        $('#zf-tool-circle').toggleClass('taa-btn-primary', tool === 'circle').toggleClass('taa-btn-secondary', tool !== 'circle');

        if (tool === 'polygon') {
            $('#zf-draw-status').text('Click on map to add points. Double-click or click first point to close.');
            zfMap.setOptions({ draggableCursor: 'crosshair' });
        } else if (tool === 'circle') {
            $('#zf-draw-status').text('Click on map to set center, then drag edge to set radius.');
            zfMap.setOptions({ draggableCursor: 'crosshair' });
        }
    }

    function zfAddPolygonPoint(latLng) {
        zfDrawingPath.push(latLng);

        var marker = new google.maps.Marker({
            position: latLng,
            map: zfMap,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 7,
                fillColor: getZfColor(),
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2,
            },
            draggable: true,
        });

        var idx = zfDrawingPath.length - 1;
        marker.addListener('dragend', function(e) {
            zfDrawingPath[idx] = e.latLng;
            zfRedrawPolygon();
            zfUpdateCoordsDisplay();
        });

        if (idx === 0) {
            marker.addListener('click', function() {
                if (zfDrawingPath.length >= 3) zfFinishPolygon();
            });
        }

        zfDrawingMarkers.push(marker);
        zfRedrawPolygon();
        zfUpdateCoordsDisplay();
        $('#zf-tool-undo, #zf-tool-clear').prop('disabled', false);
        zfUpdateSaveBtn();
    }

    function zfRedrawPolygon() {
        if (zfDrawingPolygon) zfDrawingPolygon.setMap(null);
        if (zfDrawingPath.length < 2) return;

        zfDrawingPolygon = new google.maps.Polygon({
            paths: zfDrawingPath,
            strokeColor: getZfColor(),
            strokeOpacity: 0.9,
            strokeWeight: 2,
            fillColor: getZfColor(),
            fillOpacity: 0.25,
            map: zfMap,
        });
    }

    function zfFinishPolygon() {
        zfCurrentTool = null;
        zfMap.setOptions({ draggableCursor: null });
        $('#zf-draw-status').text('Polygon complete. Enter name and save.');
        $('#zf-tool-polygon, #zf-tool-circle').removeClass('taa-btn-primary').addClass('taa-btn-secondary');
        zfRedrawPolygon();
    }

    function zfSetCircleCenter(latLng) {
        zfCircleCenter = latLng;

        zfCircleCenterMarker = new google.maps.Marker({
            position: latLng,
            map: zfMap,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: getZfColor(),
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2,
            },
            draggable: true,
        });

        zfDrawingCircle = new google.maps.Circle({
            center: latLng,
            radius: 100,
            strokeColor: getZfColor(),
            strokeOpacity: 0.9,
            strokeWeight: 2,
            fillColor: getZfColor(),
            fillOpacity: 0.25,
            map: zfMap,
            editable: true,
        });

        zfDrawingCircle.addListener('radius_changed', function() {
            zfUpdateCoordsDisplay();
            zfUpdateSaveBtn();
        });
        zfDrawingCircle.addListener('center_changed', function() {
            zfCircleCenter = zfDrawingCircle.getCenter();
            zfCircleCenterMarker.setPosition(zfCircleCenter);
            zfUpdateCoordsDisplay();
        });
        zfCircleCenterMarker.addListener('dragend', function(e) {
            zfCircleCenter = e.latLng;
            zfDrawingCircle.setCenter(e.latLng);
        });

        $('#zf-draw-status').text('Drag circle edge to adjust radius. Enter name and save.');
        zfCurrentTool = null;
        zfMap.setOptions({ draggableCursor: null });
        $('#zf-tool-polygon, #zf-tool-circle').removeClass('taa-btn-primary').addClass('taa-btn-secondary');
        $('#zf-tool-undo, #zf-tool-clear').prop('disabled', false);
        zfUpdateCoordsDisplay();
        zfUpdateSaveBtn();
    }

    function zfClearDrawing() {
        if (zfDrawingPolygon) { zfDrawingPolygon.setMap(null); zfDrawingPolygon = null; }
        if (zfDrawingCircle) { zfDrawingCircle.setMap(null); zfDrawingCircle = null; }
        if (zfCircleCenterMarker) { zfCircleCenterMarker.setMap(null); zfCircleCenterMarker = null; }
        zfDrawingMarkers.forEach(function(m) { m.setMap(null); });
        zfDrawingMarkers = [];
        zfDrawingPath = [];
        zfCircleCenter = null;
        zfCurrentTool = null;
        if (zfMap) zfMap.setOptions({ draggableCursor: null });
        $('#zf-draw-status').text('');
        $('#zf-tool-undo, #zf-tool-clear').prop('disabled', true);
        $('#zf-tool-polygon, #zf-tool-circle').removeClass('taa-btn-primary').addClass('taa-btn-secondary');
        $('#zf-coords-info').hide();
        zfUpdateSaveBtn();
    }

    function zfUndoLastPoint() {
        if (zfDrawingPath.length > 0) {
            zfDrawingPath.pop();
            var m = zfDrawingMarkers.pop();
            if (m) m.setMap(null);
            zfRedrawPolygon();
            zfUpdateCoordsDisplay();
            if (zfDrawingPath.length === 0) {
                $('#zf-tool-undo, #zf-tool-clear').prop('disabled', true);
                $('#zf-coords-info').hide();
            }
            zfUpdateSaveBtn();
        }
    }

    function zfUpdateSaveBtn() {
        var hasShape = zfDrawingPath.length >= 3 || (zfDrawingCircle && zfCircleCenter);
        $('#zf-save-zone').prop('disabled', !hasShape);
    }

    function zfUpdateCoordsDisplay() {
        if (zfDrawingPath.length > 0) {
            $('#zf-coords-info').show();
            $('#zf-point-count').text(zfDrawingPath.length + ' vertices');
            var html = '<table class="table table-sm table-borderless mb-0"><tr><th>#</th><th>Lat</th><th>Lng</th></tr>';
            zfDrawingPath.forEach(function(p, i) {
                html += '<tr><td>' + (i+1) + '</td><td>' + p.lat().toFixed(6) + '</td><td>' + p.lng().toFixed(6) + '</td></tr>';
            });
            html += '</table>';
            $('#zf-coords-list').html(html);
        } else if (zfDrawingCircle && zfCircleCenter) {
            $('#zf-coords-info').show();
            $('#zf-point-count').text('Circle');
            $('#zf-coords-list').html(
                '<div>Center: ' + zfCircleCenter.lat().toFixed(6) + ', ' + zfCircleCenter.lng().toFixed(6) + '</div>' +
                '<div>Radius: ' + Math.round(zfDrawingCircle.getRadius()) + ' meters</div>'
            );
        } else {
            $('#zf-coords-info').hide();
        }
    }

    function zfGetCoordsJSON() {
        if (zfDrawingPath.length >= 3) {
            return {
                type: 'polygon',
                coords: JSON.stringify(zfDrawingPath.map(function(p) {
                    return { lat: parseFloat(p.lat().toFixed(8)), lng: parseFloat(p.lng().toFixed(8)) };
                }))
            };
        } else if (zfDrawingCircle && zfCircleCenter) {
            return {
                type: 'circle',
                coords: JSON.stringify({
                    center: { lat: parseFloat(zfCircleCenter.lat().toFixed(8)), lng: parseFloat(zfCircleCenter.lng().toFixed(8)) },
                    radius: Math.round(zfDrawingCircle.getRadius())
                })
            };
        }
        return null;
    }

    function zfResetForm() {
        $('#zf-edit-id').val('');
        $('#zf-zone-name').val('');
        $('#zf-grace-period').val('10');
        $('#zoneModalTitle').html('<i class="fa-solid fa-draw-polygon me-2"></i>Add New Zone');
        $('.zf-color-dot').removeClass('active').first().addClass('active');
        zfClearDrawing();
    }

    // ── Events ──

    $(document).on('click', '#zf-tool-polygon', function() { zfActivateTool('polygon'); });
    $(document).on('click', '#zf-tool-circle', function() { zfActivateTool('circle'); });
    $(document).on('click', '#zf-tool-undo', function() { zfUndoLastPoint(); });
    $(document).on('click', '#zf-tool-clear', function() { zfClearDrawing(); });

    $(document).on('click', '.zf-color-dot', function(e) {
        if ($(e.target).is('#zf-custom-color')) return;
        $('.zf-color-dot').removeClass('active');
        $(this).addClass('active');
    });

    $(document).on('input', '#zf-custom-color', function() {
        var color = $(this).val();
        var parentLabel = $(this).closest('.zf-color-dot');
        parentLabel.data('color', color);
        parentLabel.css('background', color);
        $('.zf-color-dot').removeClass('active');
        parentLabel.addClass('active');
    });

    $(document).on('dblclick', '#zoneMap', function() {
        if (zfCurrentTool === 'polygon' && zfDrawingPath.length >= 3) {
            zfFinishPolygon();
        }
    });

    // Open Add modal
    $(document).on('click', '#openAddZoneModal', function() {
        zfResetForm();
        $('#ZoneFormModal').modal('show');
        setTimeout(function() { initZoneMap(); }, 300);
    });

    // Edit zone — load polygon/circle on map
    $(document).on('click', '.gfp-edit-zone', function() {
        var zoneId = $(this).data('id');
        var zone = zonesData.find(function(z) { return z.id == zoneId; });
        if (!zone) return;

        zfResetForm();
        $('#zf-edit-id').val(zone.id);
        $('#zf-zone-name').val(zone.name);
        $('#zf-grace-period').val(zone.grace_period);
        $('#zoneModalTitle').html('<i class="fa-solid fa-pen me-2"></i>Edit Zone: ' + zone.name);

        // Set color
        $('.zf-color-dot').removeClass('active');
        var match = $('.zf-color-dot[data-color="' + zone.color + '"]');
        if (match.length) match.addClass('active');
        else $('.zf-color-dot').first().addClass('active');

        $('#ZoneFormModal').modal('show');
        setTimeout(function() {
            initZoneMap();

            // Load existing shape
            try {
                var coords = JSON.parse(zone.coordinates);
                if (zone.shape_type === 'polygon' && Array.isArray(coords)) {
                    coords.forEach(function(c) {
                        zfAddPolygonPoint(new google.maps.LatLng(c.lat, c.lng));
                    });
                    zfFinishPolygon();

                    // Fit bounds to polygon
                    var bounds = new google.maps.LatLngBounds();
                    zfDrawingPath.forEach(function(p) { bounds.extend(p); });
                    zfMap.fitBounds(bounds);
                } else if (zone.shape_type === 'circle' && coords.center) {
                    zfSetCircleCenter(new google.maps.LatLng(coords.center.lat, coords.center.lng));
                    zfDrawingCircle.setRadius(coords.radius || 100);

                    zfMap.setCenter(zfCircleCenter);
                    zfMap.fitBounds(zfDrawingCircle.getBounds());
                }
            } catch(e) { console.error('Error loading zone for edit:', e); }

            zfUpdateSaveBtn();
        }, 400);
    });

    // Save zone
    $(document).on('click', '#zf-save-zone', function() {
        var name = $('#zf-zone-name').val().trim();
        if (!name) {
            toastr.warning('Please enter a zone name.', '', { positionClass: 'toast-bottom-right' });
            return;
        }
        var coordData = zfGetCoordsJSON();
        if (!coordData) {
            toastr.warning('Please draw a polygon or circle first.', '', { positionClass: 'toast-bottom-right' });
            return;
        }

        var editId = $('#zf-edit-id').val();
        var url = editId
            ? "{{ url('resort/time-and-attendance/geofences/update') }}/" + editId
            : "{{ route('resort.geofences.store') }}";

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: name,
                color: getZfColor(),
                shape_type: coordData.type,
                coordinates: coordData.coords,
                grace_period: $('#zf-grace-period').val(),
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#ZoneFormModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save zone.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // Toggle zone status
    $(document).on('click', '.gfp-toggle-zone', function() {
        var zoneId = $(this).data('id');
        $.ajax({
            url: "{{ url('resort/time-and-attendance/geofences/toggle') }}/" + zoneId,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    location.reload();
                }
            }
        });
    });

    // Delete zone
    $(document).on('click', '.gfp-delete-zone', function() {
        var zoneId = $(this).data('id');
        if (!confirm('Are you sure you want to delete this geofence zone?')) return;
        $.ajax({
            url: "{{ url('resort/time-and-attendance/geofences/delete') }}/" + zoneId,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    location.reload();
                }
            }
        });
    });

    // Also handle config page inline actions (same class pattern)
    $(document).on('click', '.gf-edit-zone-config', function() {
        window.location.href = "{{ route('resort.geofences.page') }}";
    });

    // Clear map state when modal closes
    $('#ZoneFormModal').on('hidden.bs.modal', function() {
        zfClearDrawing();
    });
});
</script>
@endsection
