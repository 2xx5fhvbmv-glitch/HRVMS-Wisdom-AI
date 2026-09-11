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
            @foreach (['Casual' => 'Casual', 'Intern' => 'Intern', 'Permanent' => 'Permanent', 'All' => 'Everyone'] as $catValue => $catLabel)
                <button type="button" class="btn btn-sm npa-tab-btn {{ $catValue === 'Casual' ? 'wfp-btn-primary' : 'wfp-btn-secondary' }}"
                        data-category="{{ $catValue }}" onclick="npaSwitchTab('{{ $catValue }}', this)">{{ $catLabel }}</button>
            @endforeach
            <input type="date" id="npa-date" class="form-control form-control-sm" style="max-width:160px;" value="{{ date('Y-m-d') }}" onchange="npaLoadList()">
        </div>

        <div class="card">
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
    </div>
</div>
@endsection

@section('import-scripts')
<script>
let npaCategory = 'Casual';
const npaShifts = @json($shifts);

function npaSwitchTab(category, btn) {
    npaCategory = category;
    document.querySelectorAll('.npa-tab-btn').forEach(b => { b.classList.remove('wfp-btn-primary'); b.classList.add('wfp-btn-secondary'); });
    btn.classList.remove('wfp-btn-secondary');
    btn.classList.add('wfp-btn-primary');
    npaLoadList();
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
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm npa-status-select" style="width:auto;">${npaStatusOptionsHtml(emp.status)}</select>
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

function npaMark(empId, btn) {
    const row = btn.closest('tr');
    const status = row.querySelector('.npa-status-select').value;
    const date = document.getElementById('npa-date').value;
    $.ajax({
        url: '{{ route("resort.timeandattendance.nonpermanent.mark") }}',
        type: 'POST',
        data: { emp_id: empId, status: status, date: date, _token: '{{ csrf_token() }}' },
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

$(document).ready(function() { npaLoadList(); });
</script>
@endsection
