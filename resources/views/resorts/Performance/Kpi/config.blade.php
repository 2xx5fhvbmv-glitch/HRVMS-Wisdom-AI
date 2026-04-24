@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 justify-content-between align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.kpi.KpiList') }}" class="btn btn-themeGray btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-performance-kpilist table-kpi-config w-100">
                    <thead>
                        <tr>
                            <th>Property Goals</th>
                            <th>Budget</th>
                            <th>Weight</th>
                            <th class="bg-poor">Poor</th>
                            <th class="bg-fair">Fair</th>
                            <th class="bg-good">Good</th>
                            <th class="bg-superb">Superb</th>
                            <th style="width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kpis as $k)
                            <tr id="kpi-config-row-{{ $k->id }}">
                                <td>{{ ucfirst($k->property_goal) }}</td>
                                <td data-col="budget">{{ \App\Helpers\Common::formatCurrency($k->PropertyGoalbudget, 'MVR', 0) }}</td>
                                <td data-col="value">{{ $k->PropertyGoalweightage !== null && $k->PropertyGoalweightage !== '' ? $k->PropertyGoalweightage.'%' : '-' }}</td>
                                <td class="bg-poor">
                                    <div data-col="poor_range">{{ $k->poor_range !== null && $k->poor_range !== '' ? $k->poor_range.'%' : '-' }}</div>
                                    <div class="small fw-bold" data-col="poor">{{ $k->poor !== null ? $k->poor : '-' }}</div>
                                </td>
                                <td class="bg-fair">
                                    <div data-col="fair_range">{{ $k->fair_range !== null && $k->fair_range !== '' ? $k->fair_range.'%' : '-' }}</div>
                                    <div class="small fw-bold" data-col="fair">{{ $k->fair !== null ? $k->fair : '-' }}</div>
                                </td>
                                <td class="bg-good">
                                    <div data-col="good_range">{{ $k->good_range !== null && $k->good_range !== '' ? $k->good_range.'%' : '-' }}</div>
                                    <div class="small fw-bold" data-col="good">{{ $k->good !== null ? $k->good : '-' }}</div>
                                </td>
                                <td class="bg-superb">
                                    <div data-col="superb_range">{{ $k->superb_range !== null && $k->superb_range !== '' ? $k->superb_range.'%' : '-' }}</div>
                                    <div class="small fw-bold" data-col="superb">{{ $k->superb !== null ? $k->superb : '-' }}</div>
                                </td>
                                <td>
                                    <button class="btn btn-theme btn-sm edit-kpi-config-btn"
                                            data-id="{{ $k->id }}"
                                            data-poor-range="{{ $k->poor_range }}"
                                            data-fair-range="{{ $k->fair_range }}"
                                            data-good-range="{{ $k->good_range }}"
                                            data-superb-range="{{ $k->superb_range }}"
                                            data-poor="{{ $k->poor }}"
                                            data-fair="{{ $k->fair }}"
                                            data-good="{{ $k->good }}"
                                            data-superb="{{ $k->superb }}">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No KPIs created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit Config Modal --}}
<div class="modal fade" id="editKpiConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editKpiConfigForm">
            @csrf
            <input type="hidden" id="editConfigId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Rating Thresholds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#9e9e9e;">POOR Range</label>
                            <input type="text" name="poor_range" class="form-control" placeholder="e.g. Below than 85%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#9e9e9e;">POOR Points</label>
                            <input type="number" step="0.01" name="poor" class="form-control" placeholder="e.g. 2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#4caf50;">FAIR Range</label>
                            <input type="text" name="fair_range" class="form-control" placeholder="e.g. 85 - 89.99%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#4caf50;">FAIR Points</label>
                            <input type="number" step="0.01" name="fair" class="form-control" placeholder="e.g. 4">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#2196f3;">GOOD Range</label>
                            <input type="text" name="good_range" class="form-control" placeholder="e.g. 90 - 94.99%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#2196f3;">GOOD Points</label>
                            <input type="number" step="0.01" name="good" class="form-control" placeholder="e.g. 6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#f44336;">SUPERB Range</label>
                            <input type="text" name="superb_range" class="form-control" placeholder="e.g. 95 - 100%">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" style="color:#f44336;">SUPERB Points</label>
                            <input type="number" step="0.01" name="superb" class="form-control" placeholder="e.g. 8">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-themeGray btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme btn-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .table-kpi-config td { vertical-align: middle; }
    .table-kpi-config .bg-poor   { background:#f2f2f2 !important; }
    .table-kpi-config .bg-fair   { background:#e8f5e9 !important; }
    .table-kpi-config .bg-good   { background:#e3f2fd !important; }
    .table-kpi-config .bg-superb { background:#ffebee !important; }
    .table-kpi-config thead th.bg-poor   { background:#9e9e9e !important; color:#fff; }
    .table-kpi-config thead th.bg-fair   { background:#4caf50 !important; color:#fff; }
    .table-kpi-config thead th.bg-good   { background:#2196f3 !important; color:#fff; }
    .table-kpi-config thead th.bg-superb { background:#f44336 !important; color:#fff; }
</style>
@endsection

@section('import-scripts')
<script>
$(document).on('click', '.edit-kpi-config-btn', function () {
    const $btn = $(this);
    $('#editConfigId').val($btn.data('id'));
    $('[name=poor_range]',   '#editKpiConfigForm').val($btn.data('poor-range') || '');
    $('[name=fair_range]',   '#editKpiConfigForm').val($btn.data('fair-range') || '');
    $('[name=good_range]',   '#editKpiConfigForm').val($btn.data('good-range') || '');
    $('[name=superb_range]', '#editKpiConfigForm').val($btn.data('superb-range') || '');
    $('[name=poor]',   '#editKpiConfigForm').val($btn.data('poor') || '');
    $('[name=fair]',   '#editKpiConfigForm').val($btn.data('fair') || '');
    $('[name=good]',   '#editKpiConfigForm').val($btn.data('good') || '');
    $('[name=superb]', '#editKpiConfigForm').val($btn.data('superb') || '');
    $('#editKpiConfigModal').modal('show');
});

$(document).on('submit', '#editKpiConfigForm', function (e) {
    e.preventDefault();
    const id = $('#editConfigId').val();
    $.ajax({
        url: "{{ url('resort/performance/kpi/config') }}/" + id,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (res) {
            if (!res.success) return;
            toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
            $('#editKpiConfigModal').modal('hide');

            // Update the row in place
            const d = res.data;
            const $row = $('#kpi-config-row-' + d.id);
            const withPct = v => (v !== null && v !== '' && v !== undefined) ? v + '%' : '-';
            $row.find('[data-col=poor_range]').text(withPct(d.poor_range));
            $row.find('[data-col=fair_range]').text(withPct(d.fair_range));
            $row.find('[data-col=good_range]').text(withPct(d.good_range));
            $row.find('[data-col=superb_range]').text(withPct(d.superb_range));
            $row.find('[data-col=poor]').text(d.poor !== null ? d.poor : '-');
            $row.find('[data-col=fair]').text(d.fair !== null ? d.fair : '-');
            $row.find('[data-col=good]').text(d.good !== null ? d.good : '-');
            $row.find('[data-col=superb]').text(d.superb !== null ? d.superb : '-');

            // Update the edit button's data-* so next edit has fresh values
            const $btn = $row.find('.edit-kpi-config-btn');
            $btn.data('poor-range', d.poor_range || '')
                .data('fair-range', d.fair_range || '')
                .data('good-range', d.good_range || '')
                .data('superb-range', d.superb_range || '')
                .data('poor', d.poor || '')
                .data('fair', d.fair || '')
                .data('good', d.good || '')
                .data('superb', d.superb || '');
        },
        error: function (xhr) {
            const errs = xhr.responseJSON?.errors;
            if (errs) {
                let msg = '';
                $.each(errs, (k, v) => msg += v + '<br>');
                toastr.error(msg, "Validation Error", { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Failed to save', "Error", { positionClass: 'toast-bottom-right' });
            }
        }
    });
});
</script>
@endsection
