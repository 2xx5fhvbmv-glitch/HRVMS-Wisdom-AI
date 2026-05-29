{{-- Employment audit-log partial — rendered server-side by
     EmployeeController::employmentLogs and injected via AJAX into the
     #employment-logs-wrap container at the bottom of the Employment
     tab. One row per changed field, with the previous + new value, the
     editor's name and the timestamp. --}}
<div class="table-responsive">
    <table class="table table-applicants w-100 mb-2">
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
                <tr>
                    <td>{{ $log->label ?: $log->field }}</td>
                    <td>{{ $log->old_value !== null && $log->old_value !== '' ? $log->old_value : '—' }}</td>
                    <td>{{ $log->new_value !== null && $log->new_value !== '' ? $log->new_value : '—' }}</td>
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
@if($logs->total() > $logs->perPage())
    <div class="pagination-custom d-flex justify-content-end">
        {{-- appends() left out: the AJAX caller rewrites ?page= via the
             data-page attribute on the pager <a>, see detail.blade.php. --}}
        {!! $logs->links('pagination::bootstrap-4') !!}
    </div>
@endif
