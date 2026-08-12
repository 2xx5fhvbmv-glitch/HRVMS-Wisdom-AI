@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
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
            data: { name: $('#grade-name-input').val() },
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
            url: "{{ route('resort.benefitgradelevel.inlineUpdate', '') }}/" + gradeId,
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
        $.get("{{ route('resort.benefitgradelevel.ranksFor', '') }}/" + currentGradeId, function (response) {
            $('#map-ranks-select').val((response.ranks || []).map(String));
        }).always(function () {
            $('#mapRanksModal').modal('show');
        });
    });

    $('#map-ranks-form').on('submit', function (e) {
        e.preventDefault();
        var ranks = $('#map-ranks-select').val() || [];
        $.ajax({
            url: "{{ route('resort.benefitgradelevel.updateRanks', '') }}/" + currentGradeId,
            type: 'POST',
            data: { ranks: ranks },
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
</script>
@endsection
