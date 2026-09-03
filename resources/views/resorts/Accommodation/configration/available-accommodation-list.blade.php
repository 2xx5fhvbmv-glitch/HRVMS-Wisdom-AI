@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #available-accommodation-list-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #available-accommodation-list-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="available-accommodation-list-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.accommodation.AccommodationMaster') }}" class="btn eb-btn-secondary me-2">View Master</a>
                    <a href="{{ route('resort.accommodation.config.index') }}" class="btn eb-btn-secondary">Back to Configuration</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
            <table id="accommodationTable" class="table w-100">
                <thead>
                    <tr>
                        <th>Building</th>
                        <th>Floor</th>
                        <th>Room No</th>
                        <th>Type</th>
                        <th>Room Type</th>
                        <th>Block For</th>
                        <th>Capacity</th>
                        <th>Beds (Occupied/Total)</th>
                        <th>Cleaning</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div class="modal fade" id="editAccommodationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Accommodation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fw-500">Building</label>
                    <input type="text" class="form-control" id="editBuilding" readonly>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-500">Floor</label>
                        <input type="text" class="form-control" id="editFloor" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-500">Room No</label>
                        <input type="text" class="form-control" id="editRoomNo" readonly>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-500">Accommodation Type</label>
                        <select class="form-select dd-native-select" id="editAccommodationType">
                            <option value="">Select</option>
                            @php $accTypes = \App\Models\AccommodationType::where('resort_id', Auth::guard('resort-admin')->user()->resort_id)->get(); @endphp
                            @foreach($accTypes as $at)
                                <option value="{{ $at->id }}">{{ $at->AccommodationName }}</option>
                            @endforeach
                        </select>
                        <div class="dd" data-target="#editAccommodationType">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Accommodation Type">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a type…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @foreach($accTypes as $at)
                                    <div class="dd-item" role="option" data-value="{{ $at->id }}"><span class="dd-nm">{{ $at->AccommodationName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-500">Capacity</label>
                        <input type="number" class="form-control" id="editCapacity" min="0">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-500">Room Type</label>
                        <select class="form-select dd-native-select" id="editRoomType">
                            @php $ranks = config('settings.eligibilty'); @endphp
                            @if($ranks)
                                @foreach($ranks as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            @endif
                        </select>
                        @php $firstRank = $ranks ? reset($ranks) : null; @endphp
                        <div class="dd" data-target="#editRoomType">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">{{ $firstRank ?? 'Select' }}</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Room Type">
                                <div class="dd-scroll">
                                    @if($ranks)
                                        @foreach($ranks as $k => $v)
                                        <div class="dd-item{{ $loop->first ? ' active' : '' }}" role="option" data-value="{{ $k }}"><span class="dd-nm">{{ $v }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-500">Block For</label>
                        <select class="form-select dd-native-select" id="editBlockFor">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <div class="dd" data-target="#editBlockFor">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Male</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Block For">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="Male"><span class="dd-nm">Male</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="Female"><span class="dd-nm">Female</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-500">Cleaning Schedule</label>
                        <select class="form-select dd-native-select" id="editCleaning">
                            @php $schedule = config('settings.CleaningSchedule'); @endphp
                            @if($schedule)
                                @foreach($schedule as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#editCleaning">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">{{ $schedule[0] ?? 'Select' }}</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Cleaning Schedule">
                                <div class="dd-scroll">
                                    @if($schedule)
                                        @foreach($schedule as $s)
                                        <div class="dd-item{{ $loop->first ? ' active' : '' }}" role="option" data-value="{{ $s }}"><span class="dd-nm">{{ $s }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-500">Room Status</label>
                        <select class="form-select dd-native-select" id="editRoomStatus">
                            @php $statuses = config('settings.RoomStatus'); @endphp
                            @if($statuses)
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            @endif
                        </select>
                        <div class="dd" data-target="#editRoomStatus">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">{{ $statuses[0] ?? 'Select' }}</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Room Status">
                                <div class="dd-scroll">
                                    @if($statuses)
                                        @foreach($statuses as $s)
                                        <div class="dd-item{{ $loop->first ? ' active' : '' }}" role="option" data-value="{{ $s }}"><span class="dd-nm">{{ $s }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-500">Occupancy Threshold (%)</label>
                    <input type="number" class="form-control" id="editThreshold" min="0" max="100">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-500">Bed Names</label>
                    <div class="row g-2" id="editBedNames">
                        <div class="col-auto"><small class="text-muted">Loading...</small></div>
                    </div>
                </div>
                <input type="hidden" id="editAccommodationId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn eb-btn-neutral" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn eb-btn-primary" id="saveEditAccommodation">Save</button>
            </div>
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
    var table = $('#accommodationTable').DataTable({
        searching: false,
        bLengthChange: false,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 10,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('resort.accommodation.AvailableAccommodationIndex') }}",
            type: 'GET',
            data: function(d) {
                d.searchTerm = $('#searchInput').val();
            }
        },
        columns: [
            { data: 'BuildingName', name: 'BuildingName' },
            { data: 'Floor', name: 'Floor' },
            { data: 'RoomNo', name: 'RoomNo' },
            { data: 'AccommodationName', name: 'AccommodationName', render: function(data) { return data || '-'; } },
            { data: 'RoomTypeName', name: 'RoomTypeName' },
            { data: 'blockFor', name: 'blockFor' },
            { data: 'Capacity', name: 'Capacity', render: function(data) { return data || '-'; } },
            { data: 'Beds', name: 'Beds' },
            { data: 'CleaningSchedule', name: 'CleaningSchedule' },
            { data: 'RoomStatus', name: 'RoomStatus' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('#searchInput').on('keyup', function() {
        table.ajax.reload();
    });

    // Edit accommodation
    $(document).on('click', '.edit-accommodation-btn', function() {
        var $btn = $(this);
        $('#editAccommodationId').val($btn.data('id'));
        $('#editBuilding').val($btn.data('building'));
        $('#editFloor').val($btn.data('floor'));
        $('#editRoomNo').val($btn.data('room'));
        $('#editAccommodationType').val($btn.data('accommodationtype'));
        $('#editCapacity').val($btn.data('capacity'));
        $('#editRoomType').val($btn.data('roomtype'));
        $('#editBlockFor').val($btn.data('blockfor'));
        $('#editCleaning').val($btn.data('cleaning'));
        $('#editRoomStatus').val($btn.data('roomstatus'));
        $('#editThreshold').val($btn.data('threshold'));
        window.wisdomDD.sync('#editAccommodationType');
        window.wisdomDD.sync('#editRoomType');
        window.wisdomDD.sync('#editBlockFor');
        window.wisdomDD.sync('#editCleaning');
        window.wisdomDD.sync('#editRoomStatus');
        // Fetch beds for this room
        $('#editBedNames').html('<div class="col-auto"><small class="text-muted">Loading...</small></div>');
        window._editBeds = [];
        window._editRoomId = $btn.data('id');
        $.ajax({
            url: "{{ route('resort.accommodation.getBeds', '') }}/" + $btn.data('id'),
            type: 'GET',
            success: function(res) {
                if (res.success) {
                    window._editBeds = res.beds;
                    renderEditBeds();
                    // Set min capacity to number of occupied beds
                    var occupiedCount = res.beds.filter(function(b) { return b.emp_id && b.emp_id != 0; }).length;
                    $('#editCapacity').attr('min', Math.max(occupiedCount, 1));
                } else {
                    $('#editBedNames').html('<div class="col-auto"><small class="text-muted">No beds configured</small></div>');
                }
            }
        });
        $('#editAccommodationModal').modal('show');
    });

    function renderEditBeds() {
        var beds = window._editBeds || [];
        var capacity = parseInt($('#editCapacity').val()) || 0;
        var html = '';

        // Show existing beds
        $.each(beds, function(i, bed) {
            var occupied = bed.emp_id && bed.emp_id != 0;
            html += '<div class="col-lg-3 col-md-4 col-6 mb-1">' +
                '<input type="text" class="form-control form-control-sm edit-bed-name" data-bed-id="' + bed.id + '" value="' + (bed.BedNo || '') + '"' + (occupied ? ' style="border-color:#53CAFF;"' : '') + '>' +
                (occupied ? '<small class="text-muted" style="font-size:10px;">Occupied</small>' : '') +
            '</div>';
        });

        // Add new bed fields if capacity > existing beds
        if (capacity > beds.length) {
            for (var i = beds.length + 1; i <= capacity; i++) {
                html += '<div class="col-lg-3 col-md-4 col-6 mb-1">' +
                    '<input type="text" class="form-control form-control-sm edit-bed-name new-bed" data-bed-id="new_' + i + '" value="BedNo-' + i + '" style="border-color:#28a745;">' +
                    '<small class="text-success" style="font-size:10px;">New</small>' +
                '</div>';
            }
        }

        if (html === '') {
            html = '<div class="col-auto"><small class="text-muted">No beds configured</small></div>';
        }
        $('#editBedNames').html(html);
    }

    // When capacity changes in edit modal, update bed fields
    $(document).on('input change', '#editCapacity', function() {
        var capacity = parseInt($(this).val()) || 0;
        var beds = window._editBeds || [];
        var occupiedCount = beds.filter(function(b) { return b.emp_id && b.emp_id != 0; }).length;

        // Don't allow less than occupied count
        if (capacity < occupiedCount) {
            $(this).val(occupiedCount);
            toastr.error('Cannot reduce below ' + occupiedCount + ' occupied beds', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        renderEditBeds();
    });

    $(document).on('click', '#saveEditAccommodation', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('resort.accommodation.AvailableAccommodationUpdate', '') }}/" + $('#editAccommodationId').val(),
            type: 'PUT',
            data: function() {
                var d = {
                    _token: '{{ csrf_token() }}',
                    Accommodation_type_id: $('#editAccommodationType').val(),
                    Capacity: $('#editCapacity').val(),
                    RoomType: $('#editRoomType').val(),
                    blockFor: $('#editBlockFor').val(),
                    CleaningSchedule: $('#editCleaning').val(),
                    RoomStatus: $('#editRoomStatus').val(),
                    Occupancytheresold: $('#editThreshold').val(),
                    bedNames: {},
                    newBeds: []
                };
                $('.edit-bed-name').each(function() {
                    var bedId = String($(this).data('bed-id'));
                    if (bedId.startsWith('new_')) {
                        d.newBeds.push($(this).val());
                    } else {
                        d.bedNames[bedId] = $(this).val();
                    }
                });
                return d;
            }(),
            success: function(response) {
                if (response.success) {
                    $('#editAccommodationModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function() {
                toastr.error('Failed to update', 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save');
            }
        });
    });

    $(document).on('click', '.delete-accommodation-btn', function() {
        var id = $(this).data('id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Are you sure?',
            text: 'This will delete the room and all its bed assignments.',
            confirmText: 'Yes, delete it!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('resort.accommodation.AvailableAccommodationDestroy', '') }}/" + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                        } else {
                            toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function() {
                        toastr.error('Failed to delete', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });
});
</script>
@include('resorts._dropdown_script')
@endsection
