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
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <p class="mb-0 text-muted small">Set a bonus percentage per employee rank. Used to compute performance-based bonuses.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-performance-kpilist w-100" id="bonusConfigTable">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Rank</th>
                            <th>Bonus Percentage (Annual Basic Salary)</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $row)
                            <tr id="bonus-row-{{ $row->rank }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->rank_label }}</td>
                                <td>
                                    <span class="view-cell" data-col="value">
                                        {{ $row->bonus_percentage !== null ? $row->bonus_percentage.'%' : '-' }}
                                    </span>
                                    <div class="input-group edit-cell d-none" style="max-width:180px;">
                                        <input type="number" step="0.01" min="0" class="form-control bonus-input"
                                               value="{{ $row->bonus_percentage }}" placeholder="Bonus %">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="view-cell" data-col="month">{{ $row->month ?: '-' }}</span>
                                    <select class="form-select edit-cell d-none month-input" style="max-width:180px;">
                                        <option value="">Select Month</option>
                                        @foreach($months as $m)
                                            <option value="{{ $m }}" {{ $row->month === $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <span class="view-cell" data-col="year">{{ $row->year ?: '-' }}</span>
                                    <select class="form-select edit-cell d-none year-input" style="max-width:140px;">
                                        <option value="">Select Year</option>
                                        @foreach($years as $y)
                                            <option value="{{ $y }}" {{ (int)$row->year === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-theme btn-sm edit-bonus-btn" data-rank="{{ $row->rank }}">Edit</button>
                                    <button class="btn btn-themeBlue btn-sm save-bonus-btn d-none" data-rank="{{ $row->rank }}">Save</button>
                                    <button class="btn btn-themeGray btn-sm cancel-bonus-btn d-none" data-rank="{{ $row->rank }}">Cancel</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
function toggleRow($row, editing) {
    $row.find('.view-cell').toggleClass('d-none', editing);
    $row.find('.edit-cell').toggleClass('d-none', !editing);
    $row.find('.edit-bonus-btn').toggleClass('d-none', editing);
    $row.find('.save-bonus-btn, .cancel-bonus-btn').toggleClass('d-none', !editing);
}

$(document).on('click', '.edit-bonus-btn', function () {
    toggleRow($(this).closest('tr'), true);
});

$(document).on('click', '.cancel-bonus-btn', function () {
    const $row = $(this).closest('tr');
    // Reset input to the current view value
    const cur = $row.find('.view-cell').text().trim().replace('%','').replace('-','');
    $row.find('.bonus-input').val(cur);
    toggleRow($row, false);
});

$(document).on('click', '.save-bonus-btn', function () {
    const $btn  = $(this);
    const $row  = $btn.closest('tr');
    const rank  = $btn.data('rank');
    const value = $row.find('.bonus-input').val();
    const month = $row.find('.month-input').val();
    const year  = $row.find('.year-input').val();

    $.ajax({
        url: "{{ route('Performance.bonusConfig.update') }}",
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            rank: rank,
            bonus_percentage: value,
            month: month,
            year: year,
        },
        success: function (res) {
            if (!res.success) return;
            toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
            $row.find('.view-cell[data-col=value]').text(res.value !== null && res.value !== '' ? res.value + '%' : '-');
            $row.find('.view-cell[data-col=month]').text(res.month || '-');
            $row.find('.view-cell[data-col=year]').text(res.year || '-');
            toggleRow($row, false);
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
