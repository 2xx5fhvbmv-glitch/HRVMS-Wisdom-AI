@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
<style>
    #benefit-grade-level-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #benefit-grade-level-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="benefit-grade-level-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>People</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <h3>Add Grade Level</h3>
                                </div>
                            </div>
                        </div>
                        <form id="grade-level-form" class="row g-2 align-items-end mb-4">
                            <div class="col-sm-6">
                                <label class="form-label">Grade Name <span class="red-mark">*</span></label>
                                <input type="text" class="form-control" name="name" id="grade-name-input" placeholder="e.g. HOD L1" required>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-theme">Add</button>
                            </div>
                        </form>

                        <table id="GradeLevelTable" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Grade Name</th>
                                    <th class="text-nowrap">Mapped Ranks</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-30 mt-1">
            <div class="col-xxl-12 col-xl-12 col-lg-12">
                <div class="card">
                    <div class="card-title">
                        <div class="row g-3 align-items-center justify-content-between">
                            <div class="col-auto">
                                <h3>Housekeeping Service Catalog</h3>
                                <p class="text-muted mb-0">Services HR can select from when raising a housekeeping request. Map which grade levels are eligible for which service via "Map Housekeeping Services" above.</p>
                            </div>
                        </div>
                    </div>
                    <form id="housekeeping-service-form" class="row g-2 align-items-end mb-4">
                        <div class="col-sm-6">
                            <label class="form-label">Service Name <span class="red-mark">*</span></label>
                            <input type="text" class="form-control" name="name" id="housekeeping-service-name-input" placeholder="e.g. Room Cleaning" required>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-theme">Add</button>
                        </div>
                    </form>

                    <ul class="list-group" id="housekeeping-service-list">
                        @forelse($housekeepingServices as $service)
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-service-id="{{ base64_encode($service->id) }}">
                                {{ $service->name }}
                                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-service-btn">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="Delete" class="img-fluid">
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item text-muted" id="no-services-row">No housekeeping services added yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mapRanksModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select the ranking for this grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="map-ranks-form">
                <div class="modal-body">
                    <p class="text-muted">A rank can only belong to one active grade at a time — selecting a rank here removes it from whichever grade currently holds it.</p>
                    <label class="form-label">Rank(s)</label>
                    <select id="map-ranks-select" class="form-select select2-modal" multiple>
                        @foreach($rankConfig as $rankValue => $rankLabel)
                            <option value="{{ $rankValue }}">{{ $rankLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="mapServicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select eligible housekeeping services for this grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="map-services-form">
                <div class="modal-body">
                    <label class="form-label">Housekeeping Service(s)</label>
                    <select id="map-services-select" class="form-select select2-modal" multiple>
                        @foreach($housekeepingServices as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                    @if($housekeepingServices->isEmpty())
                        <p class="text-muted mt-2">Add a service in the Housekeeping Service Catalog below first.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
    var currentGradeId = null;

    $(document).ready(function () {
        fetchGradeLevels();
    });

    function fetchGradeLevels() {
        if ($.fn.dataTable.isDataTable('#GradeLevelTable')) {
            $('#GradeLevelTable').DataTable().destroy();
        }
        $('#GradeLevelTable').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            processing: true,
            serverSide: true,
            ajax: '{{ route("resort.benefitgradelevel.list") }}',
            columns: [
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'ranks', name: 'ranks', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            error: function (xhr) {
                console.log(xhr.responseText);
            }
        });
    }

    $('#grade-level-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("resort.benefitgradelevel.store") }}',
            type: 'POST',
            data: { name: $('#grade-name-input').val(), "_token": "{{ csrf_token() }}" },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#grade-name-input').val('');
                    $('#GradeLevelTable').DataTable().ajax.reload();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = 'An unexpected error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(e => e[0] || e).join('<br>');
                }
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.delete-row-btn', function () {
        var gradeId = $(this).data('grade-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('resort.benefitgradelevel.destroy', ':id') }}".replace(':id', gradeId),
                    type: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                            $('#GradeLevelTable').DataTable().ajax.reload();
                        } else {
                            toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function (jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });

    $(document).on('click', '.edit-row-btn', function () {
        var $row = $(this).closest('tr');
        var gradeId = $(this).data('grade-id');
        var currentName = $row.find('td:nth-child(1)').text().trim();
        var editRowHtml = `
            <td class="py-1">
                <input type="text" class="form-control gradeNameInput" value="${currentName}" />
            </td>
            <td class="py-1">-</td>
            <td class="py-1">
                <a href="javascript:void(0)" class="btn btn-theme update-grade-btn" data-grade-id="${gradeId}">Submit</a>
            </td>
        `;
        $row.html(editRowHtml);
    });

    $(document).on('click', '.update-grade-btn', function () {
        var $row = $(this).closest('tr');
        var gradeId = $(this).data('grade-id');
        var name = $row.find('input').val();
        $.ajax({
            url: "{{ route('resort.benefitgradelevel.inlineUpdate', '__id__') }}".replace('__id__', gradeId),
            type: 'PUT',
            data: { Main_id: gradeId, name: name },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#GradeLevelTable').DataTable().ajax.reload();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = 'An unexpected error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(e => e[0] || e).join('<br>');
                }
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.map-rank-btn', function () {
        currentGradeId = $(this).data('grade-id');
        $('#map-ranks-select').val(null);
        $.get("{{ route('resort.benefitgradelevel.ranksFor', '__id__') }}".replace('__id__', currentGradeId), function (response) {
            $('#map-ranks-select').val((response.ranks || []).map(String));
        }).always(function () {
            $('#mapRanksModal').modal('show');
        });
    });

    $('#map-ranks-form').on('submit', function (e) {
        e.preventDefault();
        var ranks = $('#map-ranks-select').val() || [];
        $.ajax({
            url: "{{ route('resort.benefitgradelevel.updateRanks', '__id__') }}".replace('__id__', currentGradeId),
            type: 'POST',
            data: { ranks: ranks, "_token": "{{ csrf_token() }}" },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#mapRanksModal').modal('hide');
                    $('#GradeLevelTable').DataTable().ajax.reload();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (jqXHR) {
                toastr.error(jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.map-services-btn', function () {
        currentGradeId = $(this).data('grade-id');
        $('#map-services-select').val(null);
        $.get("{{ route('resort.benefitgradelevel.housekeepingServicesFor', '__id__') }}".replace('__id__', currentGradeId), function (response) {
            $('#map-services-select').val((response.service_ids || []).map(String));
        }).always(function () {
            $('#mapServicesModal').modal('show');
        });
    });

    $('#map-services-form').on('submit', function (e) {
        e.preventDefault();
        var serviceIds = $('#map-services-select').val() || [];
        $.ajax({
            url: "{{ route('resort.benefitgradelevel.updateHousekeepingServices', '__id__') }}".replace('__id__', currentGradeId),
            type: 'POST',
            data: { service_ids: serviceIds, "_token": "{{ csrf_token() }}" },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#mapServicesModal').modal('hide');
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (jqXHR) {
                toastr.error(jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $('#housekeeping-service-form').on('submit', function (e) {
        e.preventDefault();
        var $input = $('#housekeeping-service-name-input');
        $.ajax({
            url: '{{ route("resort.housekeepingservice.store") }}',
            type: 'POST',
            data: { name: $input.val(), "_token": "{{ csrf_token() }}" },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    $('#no-services-row').remove();
                    var encodedId = window.btoa(String(response.id));
                    $('#housekeeping-service-list').append(
                        '<li class="list-group-item d-flex justify-content-between align-items-center" data-service-id="' + encodedId + '">' +
                        $('<div>').text(response.name).html() +
                        '<a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-service-btn">' +
                        '<img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="Delete" class="img-fluid"></a></li>'
                    );
                    $('#map-services-select').append(new Option(response.name, response.id, false, false));
                    $input.val('');
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = 'An unexpected error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(e => e[0] || e).join('<br>');
                }
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    $(document).on('click', '.delete-service-btn', function () {
        var $row = $(this).closest('li');
        var serviceId = $row.data('service-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('resort.housekeepingservice.destroy', ':id') }}".replace(':id', serviceId),
                    type: 'DELETE',
                    data: { "_token": "{{ csrf_token() }}" },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                            $row.remove();
                            $('#map-services-select option[value="' + parseInt(window.atob(String(serviceId)), 10) + '"]').remove();
                        } else {
                            toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function (jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || 'An unexpected error occurred.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });
</script>
@endsection
