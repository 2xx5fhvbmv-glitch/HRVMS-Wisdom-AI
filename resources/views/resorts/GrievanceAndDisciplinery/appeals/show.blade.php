@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@php
    $statusBadge = [
        'Pending'    => 'badge-warning',
        'In_Hearing' => 'badge-info',
        'Resolved'   => 'badge-success',
        'Rejected'   => 'badge-danger',
        'Withdrawn'  => 'badge-secondary',
    ][$appeal->status] ?? 'badge-secondary';
    $isClosed = in_array($appeal->status, ['Resolved', 'Rejected', 'Withdrawn'], true);
    $grv = $appeal->grievance;
    $submitterAdmin = optional(optional($appeal->submitter)->resortAdmin);
    $submitterName  = trim(($submitterAdmin->first_name ?? '') . ' ' . ($submitterAdmin->last_name ?? '')) ?: '—';
@endphp

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3 align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Grievance & Disciplinary</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge {{ $statusBadge }}" style="font-size:13px;padding:6px 12px;">{{ str_replace('_',' ', $appeal->status) }}</span>
                    @if(!$isClosed)
                        <button class="btn eb-btn-secondary btn-sm ms-2" id="withdrawBtn">Withdraw Appeal</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Appeal Details</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th style="width:35%">Appeal No</th>
                                <td>{{ $appeal->appeal_no }}</td>
                            </tr>
                            <tr>
                                <th>Grievance ID</th>
                                <td>{{ optional($grv)->Grivance_id ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ optional(optional($grv)->category)->Category_Name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Submitted By</th>
                                <td>{{ $submitterName }}</td>
                            </tr>
                            <tr>
                                <th>Submitted On</th>
                                <td>{{ $appeal->submitted_at ? $appeal->submitted_at->format('d M Y, h:i A') : '—' }}</td>
                            </tr>
                            <tr>
                                <th>Reason for Appeal</th>
                                <td>{{ $appeal->reason ?? '—' }}</td>
                            </tr>
                            @if($appeal->decision)
                            <tr>
                                <th>Decision</th>
                                <td>
                                    <strong>{{ $appeal->decision }}</strong>
                                    @if($appeal->decision_at)
                                        <span class="text-muted small d-block">on {{ $appeal->decision_at->format('d M Y, h:i A') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($appeal->decision_notes)
                            <tr>
                                <th>Decision Notes</th>
                                <td>{{ $appeal->decision_notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- Hearings list --}}
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Hearings</h5>
                            @if(!$isClosed)
                                <button class="btn eb-btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleHearingModal">
                                    <i class="fa fa-plus me-1"></i> Schedule Hearing
                                </button>
                            @endif
                        </div>

                        @forelse($appeal->hearings as $h)
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <div>
                                        <strong>Hearing #{{ $h->sequence_no }}</strong>
                                        @php
                                            $hCss = ['Scheduled'=>'badge-info','Completed'=>'badge-success','Cancelled'=>'badge-secondary','Rescheduled'=>'badge-warning'][$h->status] ?? 'badge-secondary';
                                        @endphp
                                        <span class="badge {{ $hCss }} ms-2">{{ $h->status }}</span>
                                    </div>
                                    <div class="text-end">
                                        <div>{{ $h->hearing_date ? \Carbon\Carbon::parse($h->hearing_date)->format('d M Y') : '—' }}
                                            @if($h->hearing_time) {{ \Carbon\Carbon::parse($h->hearing_time)->format('h:i A') }} @endif
                                        </div>
                                        @if($h->location)<div class="text-muted small">{{ $h->location }}</div>@endif
                                    </div>
                                </div>
                                @if($h->outcome_notes)
                                    <hr class="my-2">
                                    <div class="small text-muted">{{ $h->outcome_notes }}</div>
                                @endif
                                @if(!$isClosed && $h->status === 'Scheduled')
                                    <div class="mt-2">
                                        <button class="btn btn-sm eb-btn-positive update-hearing-status" data-id="{{ $h->id }}" data-status="Completed">Mark Completed</button>
                                        <button class="btn btn-sm eb-btn-secondary update-hearing-status" data-id="{{ $h->id }}" data-status="Cancelled">Cancel</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hearings scheduled yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                @if(!$isClosed)
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Render Decision</h5>
                        <p class="text-muted small">Once a decision is recorded, the appeal is closed and the submitter is notified.</p>
                        <form id="decideForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Decision <span class="text-danger">*</span></label>
                                <select class="form-select" name="decision" required>
                                    <option value="">Select decision</option>
                                    <option value="Upheld">Upheld — original decision stands</option>
                                    <option value="Overturned">Overturned — original decision reversed</option>
                                    <option value="Modified">Modified — original decision partly changed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Final Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="final_status" required>
                                    <option value="">Select status</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Decision Notes</label>
                                <textarea class="form-control" name="decision_notes" rows="4" maxlength="5000" placeholder="Optional rationale, conditions, follow-up actions..."></textarea>
                            </div>
                            <button type="submit" class="btn eb-btn-primary">Record Decision</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Schedule Hearing Modal --}}
@if(!$isClosed)
<div class="modal fade" id="scheduleHearingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="scheduleHearingForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Hearing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hearing Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="hearing_date" placeholder="dd/mm/yyyy" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hearing Time</label>
                            <input type="time" class="form-control" name="hearing_time">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" maxlength="255" placeholder="e.g. HR Office / Boardroom">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Participants</label>
                            <select class="form-select select2-multi" name="participants[]" multiple>
                                @foreach($employees as $e)
                                    @php
                                        $admin = optional($e->resortAdmin);
                                        $name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                                    @endphp
                                    <option value="{{ $e->id }}">{{ $name !== '' ? $name : ('Employee #' . $e->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-neutral" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn eb-btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
$(function () {
    var appealId = '{{ base64_encode($appeal->id) }}';

    if ($.fn.datepicker) {
        $('.datepicker').datepicker({ format: 'dd/mm/yyyy', autoclose: true, todayHighlight: true });
    }
    if ($.fn.select2) {
        $('.select2-multi').select2({ width: '100%', placeholder: 'Select participants', dropdownParent: $('#scheduleHearingModal') });
    }

    function postJson(url, payload) {
        return $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
    }

    $('#scheduleHearingForm').on('submit', function (e) {
        e.preventDefault();
        var fd = $(this).serialize();
        postJson('{{ url("resort/grievance-and-disciplinary/appeals") }}/' + appealId + '/hearing', fd)
            .done(function (r) {
                if (r.success) {
                    toastr.success(r.message, 'Success', { positionClass: 'toast-bottom-right' });
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(r.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
                }
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to schedule hearing', 'Error', { positionClass: 'toast-bottom-right' });
            });
    });

    $(document).on('click', '.update-hearing-status', function () {
        var hid    = $(this).data('id');
        var status = $(this).data('status');
        if (!confirm('Mark this hearing as ' + status + '?')) return;
        postJson('{{ url("resort/grievance-and-disciplinary/appeals") }}/' + appealId + '/hearing-status', { hearing_id: hid, status: status })
            .done(function (r) {
                toastr.success(r.message, 'Success', { positionClass: 'toast-bottom-right' });
                setTimeout(() => location.reload(), 700);
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
            });
    });

    $('#decideForm').on('submit', function (e) {
        e.preventDefault();
        if (!confirm('Record this decision? The appeal will be closed.')) return;
        postJson('{{ url("resort/grievance-and-disciplinary/appeals") }}/' + appealId + '/decide', $(this).serialize())
            .done(function (r) {
                if (r.success) {
                    toastr.success(r.message, 'Success', { positionClass: 'toast-bottom-right' });
                    setTimeout(() => location.reload(), 800);
                } else {
                    toastr.error(r.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
                }
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to record decision', 'Error', { positionClass: 'toast-bottom-right' });
            });
    });

    $('#withdrawBtn').on('click', function () {
        if (!confirm('Withdraw this appeal? This action cannot be undone.')) return;
        postJson('{{ url("resort/grievance-and-disciplinary/appeals") }}/' + appealId + '/withdraw', {})
            .done(function (r) {
                toastr.success(r.message, 'Success', { positionClass: 'toast-bottom-right' });
                setTimeout(() => location.reload(), 800);
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
            });
    });
});
</script>
@endsection
