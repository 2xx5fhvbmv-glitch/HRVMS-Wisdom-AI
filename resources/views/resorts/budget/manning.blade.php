@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards/pages — extra
       breathing room between the hero and the content below it, scoped to
       this page (.page-hedding's own margin-bottom is shared by every
       page's hero). padding-bottom, not margin: adjacent sibling margins
       collapse to the larger of the two rather than summing. Below
       Bootstrap's sm breakpoint the extra padding pushes content into the
       teal hero curve's rounded bottom-left corner (body::before,
       border-radius 0 0 50px 50px) — same collision found on Payroll —
       neutralized below 576px. */
    #budget-manning-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #budget-manning-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="budget-manning-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <form id="SendToFinance" method="POST"  >
                            @csrf
                            <input type="hidden" name="year" id="SendToFinanceYear" value="{{ $year }}">
                            <p class="mb-0 fw-500 departmentBudget"></p>
                            @if($employeeRankPosition['position'] == 'HR')
                                <button type="submit" class="btn btn-theme SendToFinance" {{ $isBudgetCompleted ? '' : 'disabled' }}>Send To Finance</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'Finance')
                                <button type="submit" class="btn btn-theme SendToGM" {{ $isBudgetCompleted ? '' : 'disabled' }}>Send To GM</button>
                            @endif
                            @if($employeeRankPosition['position'] == 'GM')
                                {{-- <button type="submit" class="btn btn-theme SendToCorporateOffice" >Send To Corporate Office</button> --}}
                                <button type="submit" class="btn btn-theme SendToCorporateOffice" >Approve Budget</button>

                                <a href="#revise-budgetmodal" 
                                    class="open-revise-modal btn btn-white ms-3"
                                    style="background: var(--teal-soft);"
                                    data-budget_id="{{ $deptData->Budget_id }}"
                                    data-dept_id="{{ $deptData->department->id }}"
                                    data-bs-toggle="modal">
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                        </span>
                                </a>
                            @endif
                            {{-- @if($employeeRankPosition['position'] == 'Corporate Office')
                                <button type="submit" class="btn btn-theme SendToHR" >Send To HR</button>
                            @endif --}}
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <div>

    <div class="card vm-card">
        @php
            // Reshape $departmentsData into the exact object shape the
            // client-side render function expects — the reference's own
            // DEPTS[] shape ({name, open, positions:[{title, count, seats}]}),
            // so the ported avatar()/seatCell()/posRows()/deptBlock()
            // functions need no logic changes, just real field names.
            $Rank = config('settings.Position_Rank');
            $DEPTS = $departmentsData->values()->map(function ($deptData, $key) use ($Rank) {
                $positions = $deptData['positions']->map(function ($pos) use ($Rank) {
                    $seats = $pos->employees->map(function ($employee) use ($Rank) {
                        return [
                            'name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                            'rank' => $Rank[$employee->rank] ?? '',
                            'nation' => $employee->nationality,
                            // No photo column is selected by this query — every
                            // seat renders via the initials fallback below, per
                            // the "never fabricate a photo" rule. Wiring a real
                            // photo would mean adding a column to the
                            // employeesByPosition select in ViewManning(), a
                            // backend change outside this task's scope.
                            'photo' => null,
                            'outOfBudget' => (bool) ($employee->out_of_budget ?? false),
                        ];
                    })->values()->all();
                    for ($i = 0; $i < (int) $pos->vacantcount; $i++) {
                        $seats[] = ['vacant' => true];
                    }
                    return [
                        'title' => $pos->position_title,
                        'count' => (int) ($pos->headcount ?? 0),
                        'seats' => $seats,
                    ];
                })->values();

                return [
                    'name' => $deptData['department']->name,
                    'open' => $key === 0,
                    'budgetId' => $deptData['Budget_id'],
                    'deptId' => $deptData['department']->id,
                    'positions' => $positions,
                ];
            })->values();
        @endphp

        <div class="vm-tools">
            <form method="GET" action="{{ route('resort.budget.manning') }}" id="yearFilterForm">
                <select class="form-select dd-native-select" id="yearFilter" name="year"
                    onchange="document.getElementById('yearFilterForm').submit();">
                    @php
                        $currentYear = date('Y');
                        $startYear = $currentYear - 10;
                        $endYear = $currentYear + 1;
                        // If no year in request, use current year
                        $selectedYear = request()->get('year', $currentYear);
                    @endphp
                    @for ($loopyear = $startYear; $loopyear <= $endYear; $loopyear++)
                        <option value="{{ $loopyear }}" {{ (int) $loopyear === (int) $selectedYear ? 'selected' : '' }}>{{ $loopyear }}</option>
                    @endfor
                </select>
                <div class="dd" data-target="#yearFilter">
                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dd-lbl">{{ $selectedYear }}</span>
                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dd-panel" role="listbox" aria-label="Year">
                        <div class="dd-scroll">
                            @for ($loopyear = $startYear; $loopyear <= $endYear; $loopyear++)
                                <div class="dd-item{{ (int) $loopyear === (int) $selectedYear ? ' active' : '' }}" role="option" data-value="{{ $loopyear }}"><span class="dd-nm">{{ $loopyear }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="vm-list"></div>
    </div>
        </div>
    </div>
</div>
{{-- //Revise Budget Modal --}}
<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form id="ReviseBudget" method="POST">
                    @csrf
                <div class="form-group mb-20">
                    <input type="hidden" name="budget_id" id="budget_id" value="">
                    <input type="hidden" name="department_id" id="department_id" value="">
                    <textarea class="form-control" name="ReviseBudgetComment" id="ReviseBudgetComment" rows="7" placeholder="Add Comment Regarding Revision"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@include('resorts.budget._view_manning_styles')
@endsection

@section('import-scripts')
@include('resorts._dropdown_script')
@php
    $vmCanRevise = in_array($employeeRankPosition['position'] ?? '', ['HR', 'Finance'], true);
@endphp
<script>
(function () {
    var VM_CAN_REVISE = @json($vmCanRevise);
    var DEPTS = @json($DEPTS);

    function vmEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function vmInitials(n) {
        return String(n).split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase();
    }
    var vmRevIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6M1 20v-6h6M20.5 9A9 9 0 006 5.3L1 10M23 14l-5 4.7A9 9 0 013.5 15"/></svg>';
    var vmChevIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';

    // Employee avatar: photo-first, initials fallback. s.photo is always
    // null today (see the @php block above) — every seat renders via
    // initials, matching "never fabricate a photo" — but the img/onerror
    // path is real and takes over the moment a real photo field exists.
    function vmAvatar(s) {
        if (s.photo) {
            return '<span class="vm-av"><img src="' + vmEsc(s.photo) + '" alt="' + vmEsc(s.name) + '" onerror="this.parentNode.textContent=\'' + vmInitials(s.name) + '\'"></span>';
        }
        return '<span class="vm-av">' + vmEsc(vmInitials(s.name)) + '</span>';
    }
    function vmSeatCell(s) {
        if (s.vacant) return '<div class="vm-emp"><span class="vm-vacant"><span class="vm-vd"></span>Vacant</span></div>';
        var oob = s.outOfBudget ? '<span class="vm-oob" title="More employees are assigned to this position than the budgeted headcount allows">Out of Budget</span>' : '';
        return '<div class="vm-emp">' + vmAvatar(s) + '<span class="vm-ename" title="' + vmEsc(s.name) + '">' + vmEsc(s.name) + '</span>' + oob + '</div>';
    }
    function vmRankCell(s) { return s.vacant ? '<span class="vm-dash">—</span>' : '<span class="vm-rank">' + vmEsc(s.rank) + '</span>'; }
    function vmNatCell(s) { return s.vacant ? '<span class="vm-dash">—</span>' : '<span class="vm-nat">' + vmEsc(s.nation) + '</span>'; }

    function vmPosRows(p) {
        var seats = p.seats || [];
        var count = (p.count != null) ? p.count : seats.length;
        if (seats.length === 0) {
            return '<tr class="vm-pstart">' +
                '<td class="vm-pos-c"><span class="vm-pos">' + vmEsc(p.title) + '</span></td>' +
                '<td class="vm-c-no vm-no vm-zero">' + String(count).padStart(2, '0') + '</td>' +
                '<td colspan="3"><span class="vm-dash">—</span></td>' +
                '</tr>';
        }
        return seats.map(function (s, i) {
            var lead = i === 0
                ? '<td class="vm-pos-c" rowspan="' + seats.length + '"><span class="vm-pos">' + vmEsc(p.title) + '</span></td>' +
                  '<td class="vm-c-no vm-no" rowspan="' + seats.length + '">' + String(count).padStart(2, '0') + '</td>'
                : '';
            return '<tr class="' + (i === 0 ? 'vm-pstart' : '') + '">' + lead +
                '<td class="vm-c-emp">' + vmSeatCell(s) + '</td>' +
                '<td class="vm-c-rank">' + vmRankCell(s) + '</td>' +
                '<td class="vm-c-nat">' + vmNatCell(s) + '</td>' +
                '</tr>';
        }).join('');
    }

    function vmDeptBlock(d, idx) {
        var filled = 0, vac = 0, pos = d.positions.length;
        d.positions.forEach(function (p) { (p.seats || []).forEach(function (s) { s.vacant ? vac++ : filled++; }); });
        var meta = pos + ' position' + (pos !== 1 ? 's' : '') + ' · ' + filled + ' filled' +
            (vac ? ' · <span class="vm-vac">' + vac + ' vacant</span>' : '');
        var revise = VM_CAN_REVISE
            ? '<a href="#revise-budgetmodal" class="open-revise-modal vm-revise" data-bs-toggle="modal" data-budget_id="' + vmEsc(d.budgetId) + '" data-dept_id="' + vmEsc(d.deptId) + '">' + vmRevIcon + 'Revise Budget</a>'
            : '';
        return '<div class="vm-dept' + (d.open ? ' open' : '') + '" data-i="' + idx + '">' +
            '<div class="vm-dhd">' +
                '<span class="vm-chev">' + vmChevIcon + '</span>' +
                '<div><div class="vm-dname">' + vmEsc(d.name) + '</div><div class="vm-dmeta">' + meta + '</div></div>' +
                '<div class="vm-sp"></div>' +
                revise +
            '</div>' +
            '<div class="vm-dbody">' +
                '<table>' +
                    '<thead><tr>' +
                        '<th class="vm-c-pos">Positions</th>' +
                        '<th class="vm-c-no">No. of Positions</th>' +
                        '<th class="vm-c-emp">Employee Name</th>' +
                        '<th class="vm-c-rank">Rank</th>' +
                        '<th class="vm-c-nat">Nation</th>' +
                    '</tr></thead>' +
                    '<tbody>' + d.positions.map(vmPosRows).join('') + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    var $list = document.getElementById('vm-list');
    if ($list) $list.innerHTML = DEPTS.map(vmDeptBlock).join('');

    document.querySelectorAll('.vm-dept').forEach(function (el) {
        var hd = el.querySelector('.vm-dhd');
        if (hd) hd.addEventListener('click', function (e) {
            // Let a click on Revise Budget open its modal without also
            // toggling the accordion — the existing .open-revise-modal
            // handler below is document-delegated, so it still needs this
            // click to bubble; we just skip OUR OWN toggle for it.
            if (e.target.closest('.vm-revise')) return;
            el.classList.toggle('open');
        });
    });
})();

$(document).on('click', '.open-revise-modal', function () {
    let budgetId = $(this).data("budget_id");
    let deptId = $(this).data("dept_id");

    $("#budget_id").val(budgetId);
    $("#department_id").val(deptId);
});
</script>
@endsection
