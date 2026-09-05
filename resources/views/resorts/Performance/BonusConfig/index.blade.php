@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #performance-bonus-config-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-bonus-config-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="performance-bonus-config-hero">
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
                                    <div class="edit-cell d-none" style="max-width:180px;">
                                        <select class="form-select dd-native-select month-input" id="month-input-{{ $row->rank }}">
                                            <option value="">Select Month</option>
                                            @foreach($months as $m)
                                                <option value="{{ $m }}" {{ $row->month === $m ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                        <div class="dd" data-target="#month-input-{{ $row->rank }}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">{{ $row->month ?: 'Select Month' }}</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Month">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a month…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item{{ !$row->month ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Select Month</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @foreach($months as $m)
                                                        <div class="dd-item{{ $row->month === $m ? ' active' : '' }}" role="option" data-value="{{ $m }}"><span class="dd-nm">{{ $m }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="view-cell" data-col="year">{{ $row->year ?: '-' }}</span>
                                    <div class="edit-cell d-none" style="max-width:140px;">
                                        <select class="form-select dd-native-select year-input" id="year-input-{{ $row->rank }}">
                                            <option value="">Select Year</option>
                                            @foreach($years as $y)
                                                <option value="{{ $y }}" {{ (int)$row->year === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                        <div class="dd" data-target="#year-input-{{ $row->rank }}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">{{ $row->year ?: 'Select Year' }}</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Year">
                                                <div class="dd-scroll">
                                                    <div class="dd-item{{ !$row->year ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Select Year</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @foreach($years as $y)
                                                        <div class="dd-item{{ (int)$row->year === (int)$y ? ' active' : '' }}" role="option" data-value="{{ $y }}"><span class="dd-nm">{{ $y }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn perf-btn-secondary btn-sm edit-bonus-btn" data-rank="{{ $row->rank }}">Edit</button>
                                    <button class="btn perf-btn-primary btn-sm save-bonus-btn d-none" data-rank="{{ $row->rank }}">Save</button>
                                    <button class="btn perf-btn-neutral btn-sm cancel-bonus-btn d-none" data-rank="{{ $row->rank }}">Cancel</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
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
