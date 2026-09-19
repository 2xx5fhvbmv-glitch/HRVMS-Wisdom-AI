@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #npa-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) { #npa-hero { padding-bottom: 0; } }
    .npa-tabs { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
    .npa-status-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .npa-status-pill.Present { background: #E7F4EC; color: #1F7A54; }
    .npa-status-pill.Absent { background: #FDEEEB; color: #E5573F; }
    .npa-status-pill.Sick { background: #FBF0DC; color: #D98A00; }
    .npa-status-pill.DayOff, .npa-status-pill.ShortLeave, .npa-status-pill.HalfDayLeave, .npa-status-pill.FullDayLeave { background: #F7F8F8; color: #6B7378; }
    .npa-status-pill.none { background: #F7F8F8; color: #99A1A5; }
    .npa-view-toggle { display: flex; gap: 4px; }
    .npa-month-table { font-size: 12.5px; }
    .npa-month-table th, .npa-month-table td { text-align: center; padding: 6px 4px; white-space: nowrap; }
    .npa-month-table td.npa-emp-name, .npa-month-table th.npa-emp-name { text-align: left; position: sticky; left: 0; background: #fff; z-index: 5; min-width: 160px; }
    .npa-day-dot { display: inline-block; width: 22px; height: 22px; line-height: 22px; border-radius: 50%; font-size: 10px; font-weight: 700; }
    .npa-day-dot.Present { background: #E7F4EC; color: #1F7A54; }
    .npa-day-dot.Absent { background: #FDEEEB; color: #E5573F; }
    .npa-day-dot.Sick { background: #FBF0DC; color: #D98A00; }
    .npa-day-dot.DayOff, .npa-day-dot.ShortLeave, .npa-day-dot.HalfDayLeave, .npa-day-dot.FullDayLeave { background: #F7F8F8; color: #6B7378; }
    .npa-day-dot.none { background: #FAFAFA; color: #C4C9CC; }
    .npa-summary-text { font-size: 11.5px; color: #6B7378; white-space: nowrap; }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding" id="npa-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Time And Attendance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.timeandattendance.AttandanceRegister') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Attendance Register
                    </a>
                </div>
            </div>
        </div>

        <div class="npa-tabs">
            {{-- Casual & Intern only — this page is a dedicated entry point,
                 separate from the main Attendance Register where Permanent
                 staff are already fully managed (§31). --}}
            @foreach (['Casual' => 'Casual', 'Intern' => 'Intern'] as $catValue => $catLabel)
                <button type="button" class="btn btn-sm npa-tab-btn {{ $catValue === 'Casual' ? 'wfp-btn-primary' : 'wfp-btn-secondary' }}"
                        data-category="{{ $catValue }}" onclick="npaSwitchTab('{{ $catValue }}', this)">{{ $catLabel }}</button>
            @endforeach
            <input type="date" id="npa-date" class="form-control form-control-sm" style="max-width:160px;" value="{{ date('Y-m-d') }}" onchange="npaLoadList()">
            <input type="month" id="npa-month" class="form-control form-control-sm" style="max-width:160px; display:none;" value="{{ date('Y-m') }}" onchange="npaLoadMonth()">
            <div class="npa-view-toggle ms-auto">
                <button type="button" id="npa-view-daily" class="btn btn-sm wfp-btn-primary" onclick="npaSwitchView('daily', this)">Daily</button>
                <button type="button" id="npa-view-monthly" class="btn btn-sm wfp-btn-secondary" onclick="npaSwitchView('monthly', this)">Monthly</button>
            </div>
        </div>

        <div class="card" id="npa-daily-card">
            <div class="card-body">
                <table class="table w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Emp ID</th>
                            <th>Type</th>
                            <th>Today's Status</th>
                            <th>Mark As</th>
                            <th>Roster Shift (for Casual/Intern)</th>
                        </tr>
                    </thead>
                    <tbody id="npa-table-body">
                        <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- §32 — one row per employee, one column per day of the selected
             month, plus a per-employee summary. Drives from
             nonPermanentMonth()'s JSON; reuses the daily view's status
             vocabulary/colors (.npa-day-dot mirrors .npa-status-pill) since
             Casual/Intern statuses are manually marked, not punch-derived
             like the main Attendance Register's monthly grid. --}}
        <div class="card" id="npa-monthly-card" style="display:none;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table npa-month-table" id="npa-month-table">
                        <thead><tr><th class="npa-emp-name">Loading…</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
let npaCategory = 'Casual';
let npaView = 'daily'; // 'daily' | 'monthly' — reset cleanly on every tab/view switch (§32's own bug-class warning: don't leak state across switches)
const npaShifts = @json($shifts);

function npaSwitchTab(category, btn) {
    npaCategory = category;
    document.querySelectorAll('.npa-tab-btn').forEach(b => { b.classList.remove('wfp-btn-primary'); b.classList.add('wfp-btn-secondary'); });
    btn.classList.remove('wfp-btn-secondary');
    btn.classList.add('wfp-btn-primary');
    npaLoadCurrentView();
}

function npaSwitchView(view, btn) {
    npaView = view;
    document.getElementById('npa-view-daily').className = 'btn btn-sm ' + (view === 'daily' ? 'wfp-btn-primary' : 'wfp-btn-secondary');
    document.getElementById('npa-view-monthly').className = 'btn btn-sm ' + (view === 'monthly' ? 'wfp-btn-primary' : 'wfp-btn-secondary');
    document.getElementById('npa-date').style.display = view === 'daily' ? '' : 'none';
    document.getElementById('npa-month').style.display = view === 'monthly' ? '' : 'none';
    document.getElementById('npa-daily-card').style.display = view === 'daily' ? '' : 'none';
    document.getElementById('npa-monthly-card').style.display = view === 'monthly' ? '' : 'none';
    npaLoadCurrentView();
}

function npaLoadCurrentView() {
    if (npaView === 'monthly') {
        npaLoadMonth();
    } else {
        npaLoadList();
    }
}

function npaStatusOptionsHtml(current) {
    const statuses = ['Present', 'Absent', 'Sick', 'DayOff', 'ShortLeave', 'HalfDayLeave', 'FullDayLeave'];
    return statuses.map(s => `<option value="${s}" ${s === current ? 'selected' : ''}>${s}</option>`).join('');
}

function npaShiftOptionsHtml() {
    if (!npaShifts.length) return '<option value="">No shifts configured</option>';
    return '<option value="">Select shift…</option>' + npaShifts.map(s => `<option value="${s.id}">${s.ShiftName || ('Shift ' + s.id)} (${s.StartTime}-${s.EndTime})</option>`).join('');
}

function npaLoadList() {
    const date = document.getElementById('npa-date').value;
    $.ajax({
        url: '{{ route("resort.timeandattendance.nonpermanent.list") }}',
        type: 'GET',
        data: { category: npaCategory, date: date },
        success: function(response) {
            const tbody = document.getElementById('npa-table-body');
            if (!response.employees || !response.employees.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No employees in this category.</td></tr>';
                return;
            }
            tbody.innerHTML = response.employees.map(function(emp) {
                const statusClass = emp.status || 'none';
                const isNonPermanent = emp.manning_category !== 'Permanent';
                return `
                <tr data-emp-id="${emp.emp_id}">
                    <td>${emp.name || '—'}</td>
                    <td>${emp.emp_code || ''}</td>
                    <td>${emp.employment_type}</td>
                    <td><span class="npa-status-pill ${statusClass}">${emp.status || 'Not marked'}</span></td>
                    <td>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm npa-status-select" style="width:auto;" onchange="npaToggleOtInput(this)">${npaStatusOptionsHtml(emp.status)}</select>
                            <input type="number" class="form-control form-control-sm npa-ot-input" style="width:80px; display:${emp.status === 'Present' ? '' : 'none'};" min="0" max="12" step="0.5" placeholder="OT hrs">
                            <button type="button" class="btn btn-sm wfp-btn-primary" onclick="npaMark(${emp.emp_id}, this)">Mark</button>
                        </div>
                    </td>
                    <td>
                        ${isNonPermanent ? `
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm npa-shift-select" style="width:auto;">${npaShiftOptionsHtml()}</select>
                            <button type="button" class="btn btn-sm wfp-btn-secondary" onclick="npaAllocateRoster(${emp.emp_id}, this)">Allocate</button>
                        </div>` : '<span class="text-muted">—</span>'}
                    </td>
                </tr>`;
            }).join('');
        }
    });
}

const npaSummaryLabels = { Present: 'P', Absent: 'A', Sick: 'S', DayOff: 'D', leave: 'L', not_marked: 'NM' };

function npaLoadMonth() {
    const month = document.getElementById('npa-month').value; // YYYY-MM
    const table = document.getElementById('npa-month-table');
    table.querySelector('thead').innerHTML = '<tr><th class="npa-emp-name">Loading…</th></tr>';
    table.querySelector('tbody').innerHTML = '';

    $.ajax({
        url: '{{ route("resort.timeandattendance.nonpermanent.month") }}',
        type: 'GET',
        data: { category: npaCategory, month: month },
        success: function (response) {
            if (!response.success) {
                table.querySelector('thead').innerHTML = `<tr><th class="npa-emp-name">${response.message || 'Could not load month.'}</th></tr>`;
                return;
            }
            if (!response.employees || !response.employees.length) {
                table.querySelector('thead').innerHTML = '<tr><th class="npa-emp-name">No employees in this category.</th></tr>';
                return;
            }

            // Day columns come straight from the first employee's `days`
            // map — every employee in the response covers the same month,
            // so the key set (dates) is identical for all of them.
            const dates = Object.keys(response.employees[0].days);
            let headHtml = '<tr><th class="npa-emp-name">Name</th>';
            dates.forEach(function (d) {
                headHtml += `<th>${d.slice(8, 10)}</th>`;
            });
            headHtml += '<th>Summary</th></tr>';
            table.querySelector('thead').innerHTML = headHtml;

            table.querySelector('tbody').innerHTML = response.employees.map(function (emp) {
                let row = `<tr><td class="npa-emp-name">${emp.name || '—'}</td>`;
                dates.forEach(function (d) {
                    const status = emp.days[d];
                    const cls = status || 'none';
                    const label = status ? status.charAt(0) : '';
                    const ot = emp.ot_hours && emp.ot_hours[d] ? ` (OT ${emp.ot_hours[d]}h)` : '';
                    row += `<td><span class="npa-day-dot ${cls}" title="${status || 'Not marked'}${ot}">${label}</span></td>`;
                });
                const summaryParts = Object.keys(npaSummaryLabels)
                    .map(k => `${npaSummaryLabels[k]}:${emp.summary[k] || 0}`)
                    .join(' ');
                row += `<td class="npa-summary-text">${summaryParts}</td></tr>`;
                return row;
            }).join('');
        },
        error: function (xhr) {
            table.querySelector('thead').innerHTML = `<tr><th class="npa-emp-name">${(xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load month.'}</th></tr>`;
        }
    });
}

// OT only makes sense for Present (Common::validateOvertimeHours()/
// nonPermanentMark() reject it otherwise) — hide it for every other status
// rather than let the supervisor fill it in for e.g. Absent and get a
// confusing 422 back.
function npaToggleOtInput(select) {
    const otInput = select.closest('.d-flex').querySelector('.npa-ot-input');
    otInput.style.display = select.value === 'Present' ? '' : 'none';
    if (select.value !== 'Present') otInput.value = '';
}

function npaMark(empId, btn) {
    const row = btn.closest('tr');
    const status = row.querySelector('.npa-status-select').value;
    const date = document.getElementById('npa-date').value;
    const otHours = row.querySelector('.npa-ot-input').value;
    $.ajax({
        url: '{{ route("resort.timeandattendance.nonpermanent.mark") }}',
        type: 'POST',
        data: { emp_id: empId, status: status, date: date, ot_hours: otHours || '', _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                npaLoadList();
            } else {
                toastr.error(response.message || 'Failed to mark attendance.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        },
        error: function(xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to mark attendance.', 'Error', { positionClass: 'toast-bottom-right' });
        }
    });
}

function npaAllocateRoster(empId, btn) {
    const row = btn.closest('tr');
    const shiftId = row.querySelector('.npa-shift-select').value;
    const date = document.getElementById('npa-date').value;
    if (!shiftId) {
        toastr.error('Select a shift first.', 'Error', { positionClass: 'toast-bottom-right' });
        return;
    }
    $.ajax({
        url: '{{ route("resort.timeandattendance.nonpermanent.allocateRoster") }}',
        type: 'POST',
        data: { emp_id: empId, shift_id: shiftId, date: date, _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error(response.message || 'Failed to allocate roster.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        },
        error: function(xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to allocate roster.', 'Error', { positionClass: 'toast-bottom-right' });
        }
    });
}

$(document).ready(function() { npaLoadCurrentView(); });
</script>
@endsection
