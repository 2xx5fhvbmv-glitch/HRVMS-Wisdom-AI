@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
@php
    use App\Helpers\Common;
    $emp        = $transfer->employee;
    $admin      = optional($emp)->resortAdmin;
    $effDate    = $transfer->effective_date ? \Carbon\Carbon::parse($transfer->effective_date)->format('d M Y') : '—';
    $tempFrom   = $transfer->temporary_from ? \Carbon\Carbon::parse($transfer->temporary_from)->format('d M Y') : null;
    $tempTo     = $transfer->temporary_to   ? \Carbon\Carbon::parse($transfer->temporary_to)->format('d M Y')   : null;
    $statusBadge = match ($transfer->status) {
        'Approved' => 'badge-themeSuccess',
        'Rejected' => 'badge-themeDanger',
        'On Hold'  => 'badge-themeSkyblue',
        default    => 'badge-themeWarning',
    };
@endphp

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>People</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('people.transfer.list') }}" class="btn btn-themeGrayLight btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to Transfer List
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Header card: employee + status + key dates ──────────────── --}}
        <div class="card mb-3">
            <div class="card-header">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <div class="img-circle" style="width:64px;height:64px;">
                            <img src="{{ Common::getResortUserPicture(optional($emp)->Admin_Parent_id ?? null) }}" alt="user" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        </div>
                    </div>
                    <div class="col">
                        <h4 class="mb-1 fw-600">{{ optional($admin)->full_name ?? '—' }}
                            <span class="badge badge-white">#{{ optional($emp)->Emp_id ?? '—' }}</span>
                        </h4>
                        <p class="mb-0">{{ optional(optional($emp)->position)->position_title ?? '—' }} — {{ optional(optional($emp)->department)->name ?? '—' }}</p>
                    </div>
                    <div class="col-auto text-end">
                        <span class="badge {{ $statusBadge }} mb-1" style="font-size:13px;">{{ $transfer->status }}</span>
                        <div class="text-muted small">Transfer #{{ $transfer->id }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-md-3 g-2 mb-md-4 mb-3 p-3">
                <div class="col-md-3 col-sm-6">
                    <div class="text-muted small text-uppercase">Effective Date</div>
                    <div class="fw-600">{{ $effDate }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="text-muted small text-uppercase">Transfer Type</div>
                    <div class="fw-600">{{ $transfer->transfer_status }}</div>
                </div>
                @if($transfer->transfer_status === 'Temporary' && $tempFrom)
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small text-uppercase">Temporary From</div>
                        <div class="fw-600">{{ $tempFrom }}</div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small text-uppercase">Temporary To</div>
                        <div class="fw-600">{{ $tempTo }}</div>
                    </div>
                @endif
                <div class="col-md-3 col-sm-6">
                    <div class="text-muted small text-uppercase">Submitted</div>
                    <div class="fw-600">{{ \Carbon\Carbon::parse($transfer->created_at)->format('d M Y h:i A') }}</div>
                </div>
                @if($transfer->reverted_at)
                    <div class="col-md-3 col-sm-6">
                        <div class="text-muted small text-uppercase">Auto-Reverted</div>
                        <div class="fw-600 text-info">{{ \Carbon\Carbon::parse($transfer->reverted_at)->format('d M Y h:i A') }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Rejection / On-Hold reason callout ──────────────────────── --}}
        @if($transfer->status === 'Rejected' && !empty($rejectionReason))
            <div class="alert alert-danger mb-3">
                <strong><i class="fa-solid fa-circle-xmark me-1"></i> Rejection Reason:</strong>
                {{ $rejectionReason }}
            </div>
        @elseif($transfer->status === 'On Hold' && !empty($onHoldReason))
            <div class="alert alert-warning mb-3">
                <strong><i class="fa-solid fa-pause me-1"></i> On-Hold Reason:</strong>
                {{ $onHoldReason }}
            </div>
        @endif

        <div class="row g-3">
            {{-- ── Current → Target movement ─────────────────────────────── --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-title p-3 pb-0">
                        <h3>Movement</h3>
                    </div>
                    <div class="p-3">
                        <table class="table table-lable mb-0">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-muted">Current</th>
                                    <th class="text-muted">Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>Department</th>
                                    <td>{{ optional($transfer->currentDepartment)->name ?? '—' }}</td>
                                    <td class="text-primary fw-600">{{ optional($transfer->targetDepartment)->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Section</th>
                                    <td>{{ optional(\App\Models\ResortSection::find($transfer->current_section_id))->name ?? '—' }}</td>
                                    <td class="text-primary fw-600">{{ optional(\App\Models\ResortSection::find($transfer->target_section_id))->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Position</th>
                                    <td>{{ optional($transfer->currentPosition)->position_title ?? '—' }}</td>
                                    <td class="text-primary fw-600">{{ optional($transfer->targetPosition)->position_title ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Reporting To</th>
                                    <td colspan="2">
                                        @if($transfer->reporting)
                                            {{ optional($transfer->reporting->resortAdmin)->full_name ?? '—' }}
                                            <span class="text-muted small">({{ optional($transfer->reporting->position)->position_title ?? '—' }})</span>
                                        @else — @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Salary block ─────────────────────────────────────────── --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-title p-3 pb-0">
                        <h3>Salary</h3>
                    </div>
                    <div class="p-3">
                        <table class="table table-lable mb-0">
                            <tr>
                                <th>Current Basic Salary</th>
                                <td class="text-end fw-600">{{ number_format((float) (optional($emp)->basic_salary ?? 0), 2) }} {{ optional($emp)->basic_salary_currency ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Budgeted Salary <span class="text-muted">(target position)</span></th>
                                <td class="text-end fw-600">
                                    @if($transfer->budgeted_salary !== null)
                                        {{ number_format((float) $transfer->budgeted_salary, 2) }}
                                    @else — @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Proposed Salary</th>
                                <td class="text-end fw-600 text-primary">
                                    @if($transfer->proposed_salary !== null)
                                        {{ number_format((float) $transfer->proposed_salary, 2) }}
                                    @else — @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Allowances / Benefit Grid for the TARGET position ─── --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-title p-3 pb-0">
                        <h3>Allowances &amp; Benefit Grid <span class="text-muted small">(target position)</span></h3>
                    </div>
                    <div class="p-3" id="target_allowance_block">
                        <div class="text-muted">Loading…</div>
                    </div>
                </div>
            </div>

            {{-- ── Reason for transfer + additional notes ───────────────── --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-title p-3 pb-0">
                        <h3>Reason for Transfer</h3>
                    </div>
                    <div class="p-3">
                        <p class="mb-0">{{ $transfer->reason_for_transfer ?: '—' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-title p-3 pb-0">
                        <h3>Additional Notes</h3>
                    </div>
                    <div class="p-3">
                        <p class="mb-0">{{ $transfer->additional_notes ?: '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- ── Approval Timeline (Finance → GM) ─────────────────────── --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-title p-3 pb-0">
                        <h3>Approval Timeline</h3>
                    </div>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-lable mb-0">
                                <thead>
                                    <tr>
                                        <th>Stage</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Acted On</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfer->approvals->sortBy('id') as $approval)
                                        @php
                                            $approverEmp   = $approval->approver;
                                            $approverName  = optional(optional($approverEmp)->resortAdmin)->full_name ?? '—';
                                            $approverEmpId = optional($approverEmp)->Emp_id;
                                            $rowBadge = match ($approval->status) {
                                                'Approved' => 'badge-themeSuccess',
                                                'Rejected' => 'badge-themeDanger',
                                                'On Hold'  => 'badge-themeSkyblue',
                                                default    => 'badge-themeWarning',
                                            };
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $approval->approval_rank }}</strong></td>
                                            <td>{{ $approverName }} @if($approverEmpId)<span class="text-muted small">(#{{ $approverEmpId }})</span>@endif</td>
                                            <td><span class="badge {{ $rowBadge }}">{{ $approval->status }}</span></td>
                                            <td>
                                                @if($approval->approved_at)
                                                    {{ \Carbon\Carbon::parse($approval->approved_at)->format('d M Y h:i A') }}
                                                @else — @endif
                                            </td>
                                            <td>{{ $approval->remarks ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Transfer Letter — Preview + Send to employee ──────────── --}}
            @if($transfer->status === 'Approved')
                <div class="col-12">
                    <div class="card">
                        <div class="p-3 d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('people.transfer.preview-letter', $transfer->id) }}"
                               target="_blank"
                               class="btn btn-themeSkyblue btn-sm">
                                <i class="fa-regular fa-file-pdf"></i> Preview Transfer Letter
                            </a>
                            <button type="button" id="sendTransferLetterBtn"
                               data-url="{{ route('people.transfer.send-letter', $transfer->id) }}"
                               class="btn btn-themeBlue btn-sm">
                                <i class="fa-regular fa-paper-plane"></i> Send to Employee
                            </button>
                            <span class="text-muted small ms-2">
                                @if($transfer->letter_dispatched === 'Yes')
                                    Last sent to the employee. Re-send anytime.
                                @else
                                    Click <strong>Send</strong> to email the letter to the transferred employee.
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
    // Pull the target position's Allowances + Benefit Grid (same endpoint
    // the Add Vacancy and Initiate Transfer forms use). Keeps a single
    // source of truth so any change to a cost's config reflects here too.
    $(document).ready(function () {
        var positionId = {{ (int) $transfer->target_position_id }};
        if (!positionId) {
            $('#target_allowance_block').html('<span class="text-muted">No target position.</span>');
            return;
        }

        $.ajax({
            url: '{{ route("resort.vacancies.getstatus") }}',
            method: 'POST',
            data: {
                position_id: positionId,
                requested_vacancy: 1,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response && response.all_allowances && response.all_allowances.length > 0) {
                    var html = '<table class="table table-sm table-bordered mb-0"><thead><tr>'
                             + '<th>Particulars</th><th>Amount</th></tr></thead><tbody>';
                    response.all_allowances.forEach(function (item) {
                        var displayAmt = item.unit === '%'
                            ? item.raw_amount + '% (' + item.amount + ')'
                            : item.amount;
                        html += '<tr><td>' + item.name + '</td><td>' + displayAmt + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    $('#target_allowance_block').html(html);
                } else {
                    $('#target_allowance_block').html('<span class="text-muted">No allowances configured for this position.</span>');
                }
            },
            error: function () {
                $('#target_allowance_block').html('<span class="text-danger">Could not load allowances.</span>');
            }
        });

        // Send Transfer Letter — confirm, then POST to the send endpoint.
        // Reuses the existing generateAndSendTransferLetter() flow under the
        // hood, so the letter is saved into File Management AND emailed.
        $(document).on('click', '#sendTransferLetterBtn', function () {
            var $btn = $(this);
            var url  = $btn.data('url');
            if (typeof Swal === 'undefined') {
                if (!confirm('Send the Transfer Letter to the employee now?')) return;
                doSend();
                return;
            }
            Swal.fire({
                title: 'Send Transfer Letter?',
                text: 'The letter will be emailed to the transferred employee.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, send',
            }).then(function (r) {
                if (r.isConfirmed) doSend();
            });

            function doSend() {
                var orig = $btn.html();
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Sending…');
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (resp) {
                        if (resp && resp.success) {
                            toastr.success(resp.message || 'Letter sent.', 'Success', { positionClass: 'toast-bottom-right' });
                            setTimeout(function () { window.location.reload(); }, 1200);
                        } else {
                            toastr.error((resp && resp.message) || 'Could not send the letter.', 'Error', { positionClass: 'toast-bottom-right' });
                            $btn.prop('disabled', false).html(orig);
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) || 'Could not send the letter.';
                        toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                        $btn.prop('disabled', false).html(orig);
                    }
                });
            }
        });
    });
</script>
@endsection
