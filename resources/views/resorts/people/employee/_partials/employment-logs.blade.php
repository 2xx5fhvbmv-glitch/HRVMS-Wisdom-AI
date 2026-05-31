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
                {{-- All cells left-aligned to match the column headers.
                     Earlier the money cells had `text-end` so 4,500.00
                     lined up by the decimal — but that mismatched the
                     left-aligned "Old Value" / "New Value" headers and
                     the date cells in the same column, making the table
                     look misaligned. Consistency with the headers wins. --}}
                <tr>
                    <td>{{ $log->label ?: $log->field }}</td>
                    <td>{{ $formatLogValue($log->field, $log->old_value) }}</td>
                    <td>{{ $formatLogValue($log->field, $log->new_value) }}</td>
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
    /* All headers AND data cells left-aligned. The page's parent theme
       (.table-applicants in default.css) right-aligns numeric-looking
       cells, which was making "4,500.00" and the Date & Time timestamps
       drift to the right edge of their columns while the headers stayed
       on the left. Force left for every cell so the whole table reads
       as one consistent column block. !important is required because
       the inherited rule has higher specificity than this <style>. */
    .employment-log-table th,
    .employment-log-table td {
        text-align: left !important;
        vertical-align: middle;
    }
    /* nth-child indexes are 1-based: Field, Old, New, Edited By, Date. */
    .employment-log-table th:nth-child(1),
    .employment-log-table td:nth-child(1) { min-width: 140px; }
    .employment-log-table th:nth-child(4),
    .employment-log-table td:nth-child(4) { min-width: 130px; }
    /* Keep the timestamp on a single line. */
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
