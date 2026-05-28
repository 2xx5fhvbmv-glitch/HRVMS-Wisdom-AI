@php
    use Carbon\Carbon;

    $employee = $peopleSalaryIncrement->employee;

    // Map per-stage rows in approval order. Finance acts first, GM second.
    $stages = [
        ['label' => 'Finance', 'row' => $peopleSalaryIncrement->peopleSalaryIncrementStatusFinance],
        ['label' => 'GM',      'row' => $peopleSalaryIncrement->peopleSalaryIncrementStatusGM],
    ];

    // "Pending from" = first stage whose status is Pending (or Hold/
    // Change-Request — those still need action). If everything's resolved
    // (Approved or Rejected on all stages) there's no one pending.
    $pendingFrom = null;
    foreach ($stages as $s) {
        if (!$s['row']) continue;
        $st = trim((string) $s['row']->status);
        if (in_array($st, ['Pending', 'Hold', 'Change-Request'], true)) {
            $pendingFrom = $s['label'];
            break;
        }
    }

    $badgeClassFor = function (string $status): string {
        return match (trim($status)) {
            'Approved'        => 'badge-themeSuccess',
            'Rejected'        => 'badge-themeDanger',
            'Hold', 'On Hold' => 'badge-themeSkyblue',
            'Change-Request'  => 'badge-themeSkyblue',
            default           => 'badge-themeWarning',
        };
    };

    $initiatorName = $initiator
        ? trim(($initiator->first_name ?? '') . ' ' . ($initiator->last_name ?? ''))
        : '—';
    $initiatedAt = $peopleSalaryIncrement->created_at
        ? Carbon::parse($peopleSalaryIncrement->created_at)->format('d M Y h:i A')
        : '—';
@endphp
<div class="modal-body">
    <div class="mb-3">
        <h6 class="fw-600 mb-1">{{ optional($employee->resortAdmin)->full_name ?? '—' }}
            <span class="badge badge-themeNew ms-1">{{ $employee->Emp_id ?? '—' }}</span>
        </h6>
        <div style="font-size:13px; color:#555;">
            {{ optional($employee->position)->position_title ?? '—' }} ·
            {{ optional($employee->department)->name ?? '—' }}
        </div>
    </div>

    <table class="table table-sm w-100 mb-3" style="font-size:13px;">
        <tbody>
            <tr>
                <td style="width:40%; color:#666;">Increment Type</td>
                <td>{{ $peopleSalaryIncrement->increment_type ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color:#666;">Pay Increase Type</td>
                <td>{{ $peopleSalaryIncrement->pay_increase_type ?? '—' }}</td>
            </tr>
            <tr>
                <td style="color:#666;">Current → New Salary</td>
                <td>
                    {!! App\Helpers\Common::formatCurrency($peopleSalaryIncrement->previous_salary, 'USD') !!}
                    →
                    <strong>{!! App\Helpers\Common::formatCurrency($peopleSalaryIncrement->new_salary, 'USD') !!}</strong>
                    <span style="color:#888;">
                        (+{!! App\Helpers\Common::formatCurrency($peopleSalaryIncrement->increment_amount, 'USD') !!})
                    </span>
                </td>
            </tr>
            <tr>
                <td style="color:#666;">Effective Date</td>
                <td>{{ $peopleSalaryIncrement->effective_date
                    ? Carbon::parse($peopleSalaryIncrement->effective_date)->format('d M Y')
                    : '—' }}</td>
            </tr>
            @if(!empty($peopleSalaryIncrement->remarks))
                <tr>
                    <td style="color:#666;">Original Remark</td>
                    <td>{{ $peopleSalaryIncrement->remarks }}</td>
                </tr>
            @endif
            @if(!empty($peopleSalaryIncrement->due_date))
                <tr>
                    <td style="color:#666;">Hold Until</td>
                    <td>{{ Carbon::parse($peopleSalaryIncrement->due_date)->format('d M Y') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="mb-3" style="font-size:13px;">
        <div><span style="color:#666;">Raised By:</span>
            <strong>{{ $initiatorName }}</strong>
            <span style="color:#888;"> on {{ $initiatedAt }}</span>
        </div>
        <div class="mt-1">
            <span style="color:#666;">Current Status:</span>
            <span class="badge {{ $badgeClassFor($peopleSalaryIncrement->status) }}"
                  style="font-size:11px; padding:2px 8px;">
                {{ $peopleSalaryIncrement->status }}
            </span>
            @if($pendingFrom)
                <span style="color:#888;">— pending from <strong>{{ $pendingFrom }}</strong></span>
            @endif
        </div>
    </div>

    <h6 class="fw-600 mb-2" style="font-size:13px;">Approval Chain</h6>
    <div class="table-responsive">
        <table class="table table-sm w-100" style="font-size:12px;">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Status</th>
                    <th>Acted On</th>
                    <th>Reason / Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stages as $s)
                    @php $row = $s['row']; @endphp
                    <tr>
                        <td><strong>{{ $s['label'] }}</strong></td>
                        <td>
                            @if($row)
                                <span class="badge {{ $badgeClassFor($row->status) }}"
                                      style="font-size:11px; padding:2px 6px;">
                                    {{ $row->status }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $row && !empty($row->action_date)
                                ? Carbon::parse($row->action_date)->format('d M Y h:i A')
                                : '—' }}
                        </td>
                        <td>
                            @if($row)
                                @if(!empty($row->reject_reason))
                                    <span style="color:#a00;">{{ $row->reject_reason }}</span>
                                @elseif(!empty($row->remarks))
                                    {{ $row->remarks }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Close</button>
</div>
