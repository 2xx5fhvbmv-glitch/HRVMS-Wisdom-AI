@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #performance-kpi-list-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-kpi-list-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="performance-kpi-list-hero">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    @if(\App\Helpers\Common::isHRHOD())
                        <a href="{{ route('Performance.kpi.config') }}" class="btn perf-btn-secondary">KPI Config</a>
                    @endif
                    @if(($userRank ?? null) == 8)
                        <a href="{{ route('Performance.kpi.create') }}" class="btn perf-btn-accent">Create New KPI</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-sm-6">
                        <div class="input-group">
                            <input type="search" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-5 col-sm-6">
                        @php
                            $currentYear = date('Y');
                            $futureYear = $currentYear + 1;
                        @endphp
                        <select class="form-select dd-native-select Year" id="kpiYearFilter">
                            <option value="All">Select Duration</option>
                            <option value="{{ $currentYear }}">{{ $currentYear }}</option>
                            <option value="{{ $futureYear }}">{{ $futureYear }}</option>
                        </select>
                        <div class="dd" data-target="#kpiYearFilter">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Duration</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Duration">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="All"><span class="dd-nm">Select Duration</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="{{ $currentYear }}"><span class="dd-nm">{{ $currentYear }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="{{ $futureYear }}"><span class="dd-nm">{{ $futureYear }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-performance-kpilist w-100">
                    <thead>
                        <tr>
                            <th>Property Goals</th>
                            <th>Target Budget</th>
                            <th>Target Weight</th>
                            <th>Achieved Budget</th>
                            <th>Achieved Weight</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- GM Reject Modal --}}
<div class="modal fade" id="rejectKpiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rejectKpiForm">
            @csrf
            <input type="hidden" id="rejectKpiId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject KPI Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="remarks" class="form-control" rows="4" required placeholder="Reason for rejection"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn perf-btn-neutral btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn perf-btn-critical btn-sm">Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    datatablelist();
});

$(document).on("change", ".Year", datatablelist);
$(document).on("keyup", ".search", datatablelist);

function datatablelist() {
    if ($.fn.DataTable.isDataTable('.table-performance-kpilist')) {
        $('.table-performance-kpilist').DataTable().destroy();
    }
    $('.table-performance-kpilist').DataTable({
        searching: false,
        bLengthChange: false,
        bInfo: true,
        scrollX: true,
        iDisplayLength: 10,
        processing: true,
        serverSide: true,
        order: [[7, 'desc']],
        ajax: {
            url: "{{ route('Performance.kpi.KpiList') }}",
            type: 'GET',
            data: function(d) {
                d.Year = $(".Year").val();
                d.searchTerm = $('.search').val();
            }
        },
        columns: [
            { data: 'PropertyGoals', name: 'PropertyGoals' },
            { data: 'budget', name: 'budget' },
            { data: 'Value', name: 'Value' },
            { data: 'Actual', name: 'Actual' },
            { data: 'Result', name: 'Result' },
            { data: 'status_badge', name: 'status_badge', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'created_at', visible: false, searchable: false },
        ]
    });
}

// GM Approve
$(document).on('click', '.gm-approve-btn', function() {
    const id = $(this).data('id');
    wisdomConfirm({
        role: 'positive',
        title: 'Approve this KPI response?',
        confirmText: 'Yes, approve'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('resort/performance/kpi/approve') }}/" + id,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    datatablelist();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
});

// GM Delete
$(document).on('click', '.kpi-delete-btn', function() {
    const id = $(this).data('id');
    wisdomConfirm({
        role: 'destructive',
        title: 'Delete this KPI?',
        text: 'This will permanently remove the KPI and all its actual entries.',
        confirmText: 'Yes, delete'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('resort/performance/kpi/destroy') }}/" + id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    datatablelist();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
});

// GM Reject
$(document).on('click', '.gm-reject-btn', function() {
    $('#rejectKpiId').val($(this).data('id'));
    $('#rejectKpiForm')[0].reset();
    $('#rejectKpiId').val($(this).data('id'));
    $('#rejectKpiModal').modal('show');
});

$(document).on('submit', '#rejectKpiForm', function(e) {
    e.preventDefault();
    const id = $('#rejectKpiId').val();
    const remarks = $('[name="remarks"]', this).val();
    $.ajax({
        url: "{{ url('resort/performance/kpi/reject') }}/" + id,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', remarks: remarks },
        success: function(res) {
            if (res.success) {
                $('#rejectKpiModal').modal('hide');
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                datatablelist();
            }
        },
        error: function(xhr) {
            var errs = xhr.responseJSON?.errors;
            if (errs) {
                var msg = '';
                $.each(errs, function(k, v) { msg += v + '<br>'; });
                toastr.error(msg, 'Validation Error', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
            }
        }
    });
});

</script>
@endsection
