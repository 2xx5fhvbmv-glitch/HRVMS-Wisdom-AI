{{-- Employment audit-log partial — rendered server-side by
     EmployeeController::employmentLogs and injected via AJAX into the
     #employment-logs-wrap container at the bottom of the Employment
     tab. One row per changed field, with the previous + new value, the
     editor's name and the timestamp.

     Old/New values are stored as plain strings on the audit row but
     come from heterogeneous source columns: dates (YYYY-MM-DD),
     decimals (basic_salary as "4500.00" or "4500"), and text. The
     formatter below normalises each per the row's `field` so the log
     reads consistently regardless of which writer produced the row
     (manual writeEmploymentAuditLog vs the EmployeeEmploymentAuditObserver). --}}
@php
    // Which source columns get money / date formatting. Mirrors
    // EmployeeEmploymentAuditObserver::TRACKED so the two writers
    // produce visually-consistent rows.
    $dateFields  = ['joining_date', 'probation_end_date', 'termination_date'];
    $moneyFields = ['basic_salary', 'proposed_salary'];

    $formatLogValue = function ($field, $value) use ($dateFields, $moneyFields) {
        if ($value === null || $value === '') {
            return '—';
        }
        if (in_array($field, $dateFields, true)) {
            // Parse permissively — covers "2026-04-02", "2026-04-02 00:00:00",
            // and "02/04/2026" without throwing on already-formatted strings.
            try {
                return \Carbon\Carbon::parse($value)->format('d M Y');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        if (in_array($field, $moneyFields, true)) {
            // Cast through float so "4500" and "4500.00" both render as
            // "4,500.00". Two-decimal formatting matches the rest of the
            // app (salary increment summary, payroll review, etc.).
            return number_format((float) $value, 2);
        }
        return $value;
    };
@endphp
<div class="table-responsive">
    <table class="table table-applicants w-100 mb-2 employment-log-table">
        <thead>
            <tr>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Edited By</th>
                <th>Date &amp; Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    $isMoney = in_array($log->field, $moneyFields, true);
                    // Right-align money cells so 4,500.00 / 12,750.00 line
                    // up by the decimal. All other columns stay left-aligned
                    // to match the surrounding tables on the page.
                    $valueAlignClass = $isMoney ? 'text-end' : '';
                @endphp
                <tr>
                    <td>{{ $log->label ?: $log->field }}</td>
                    <td class="{{ $valueAlignClass }}">{{ $formatLogValue($log->field, $log->old_value) }}</td>
                    <td class="{{ $valueAlignClass }}">{{ $formatLogValue($log->field, $log->new_value) }}</td>
                    <td>{{ optional($log->changedByAdmin)->full_name ?? 'System' }}</td>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No edits recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<style>
    /* Small alignment polish — headers left-aligned (default Bootstrap
       sometimes centers on certain themes), money headers right-aligned
       so the column heading sits over the formatted decimals. */
    .employment-log-table th { text-align: left; vertical-align: middle; }
    .employment-log-table td { vertical-align: middle; }
    /* nth-child indexes are 1-based: Field, Old, New, Edited By, Date.
       Field column gets a tiny min-width so wide labels like "Basic
       Salary Currency" don't push other columns. */
    .employment-log-table th:nth-child(1),
    .employment-log-table td:nth-child(1) { min-width: 140px; }
    .employment-log-table th:nth-child(4),
    .employment-log-table td:nth-child(4) { min-width: 130px; }
    .employment-log-table th:nth-child(5),
    .employment-log-table td:nth-child(5) { white-space: nowrap; }
</style>
@if($logs->total() > $logs->perPage())
    <div class="pagination-custom d-flex justify-content-end">
        {{-- appends() left out: the AJAX caller rewrites ?page= via the
             data-page attribute on the pager <a>, see detail.blade.php. --}}
        {!! $logs->links('pagination::bootstrap-4') !!}
    </div>
@endif
