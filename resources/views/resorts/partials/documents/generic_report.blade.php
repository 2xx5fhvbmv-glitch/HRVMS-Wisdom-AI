{{-- Generic report document: every report PDF that has no bespoke layout (all predefined reports via PredefinedReportActions
     plus saved reports). Binds to what the shared PDF view already receives: $report->name, $columns, $data, $insightsHtml.
     Wide reports (more than 7 columns) print landscape. --}}
@extends('resorts.partials.documents._frame')

@php
    $cols = array_values($columns ?? []);
    $rows = array_values($data ?? []);
    $n    = count($rows);
    $nc   = count($cols);

    // Module label for the letterhead, from the report route that is downloading (resort.report.<key>.export).
    $MODULES = [
        'wfp' => 'Workforce Planning', 'payroll' => 'Payroll', 'visa' => 'Visa & Immigration', 'filemgmt' => 'File Management',
        'survey' => 'Survey', 'learning' => 'Learning & Development', 'grievance' => 'Grievance & Disciplinary', 'incident' => 'Incident',
        'accommodation' => 'Accommodation', 'sos' => 'SOS', 'compliance' => 'Compliance', 'onboarding' => 'Onboarding',
        'employee' => 'Employee Master', 'announcement' => 'Announcements', 'probation' => 'Probation', 'promotion' => 'Promotion',
        'transfer' => 'Transfer', 'salary_increment' => 'Salary Increment', 'salary_advance_loan' => 'Salary Advance & Loan',
        'resignation_exit' => 'Resignation & Exit',
    ];
    $routeName = (string) optional(request()->route())->getName();
    $module    = preg_match('/^resort\.report\.([a-z_]+)\.export$/', $routeName, $m) ? ($MODULES[$m[1]] ?? 'Reports') : 'Reports';

    $text = function ($r, $c) {
        $v = $r[$c] ?? 'N/A';
        return is_array($v) ? implode(', ', array_map('strval', $v)) : trim((string) $v);
    };
    // Numbers, currency and percentages align right; everything else left.
    $isNum = fn($v) => (bool) preg_match('/^[(\-+]?\s*(MVR|USD|Rf|\$)?\s*[\d,]+(\.\d+)?\s*%?\)?$/i', $v);
    $right = []; $chip = []; $cells = [];
    foreach ($cols as $c) {
        $vals = [];
        foreach ($rows as $i => $r) { $cells[$i][$c] = $v = $text($r, $c); if ($v !== '' && $v !== 'N/A') $vals[] = $v; }
        $right[$c] = $vals && count(array_filter($vals, $isNum)) >= 0.8 * count($vals);
        $chip[$c]  = (bool) preg_match('/(^|\s)status$/i', $c);
    }
    $chipClass = function (string $s) {
        $k = strtolower($s);
        if (in_array($k, ['active', 'approved', 'completed', 'paid', 'resolved', 'accepted', 'done', 'confirmed'], true)) return 'ok';
        if (in_array($k, ['pending', 'probation', 'in progress', 'on leave', 'notice period', 'suspended', 'open', 'draft', 'submitted'], true)) return 'warn';
        if (in_array($k, ['rejected', 'terminated', 'resigned', 'inactive', 'cancelled', 'canceled', 'declined', 'overdue', 'expired', 'absconded'], true)) return 'bad';
        return '';
    };

    // A report that supplies its own trailing "Total" row keeps it (styled as the grand total); it is not a record.
    $lastRowIsTotal = $n && strtolower($cells[$n - 1][$cols[0] ?? ''] ?? '') === 'total';
    $recs = $n - ($lastRowIsTotal ? 1 : 0);
    $body = $lastRowIsTotal ? array_slice($cells, 0, -1) : $cells;

    // Key figures: the record count, plus how many distinct values the first few category-like columns have.
    $plural = fn($w) => preg_match('/y$/i', $w) ? preg_replace('/y$/i', 'ies', $w) : (preg_match('/s$/i', $w) ? $w . 'es' : $w . 's');
    $figs = [['k' => 'Records', 'v' => e($recs), 'n' => 'In this report']];
    if ($recs) {
        foreach ($cols as $c) {
            if (count($figs) >= 4) break;
            if (!preg_match('/^(department|nationality|position|grade|status|employment status|category|type|module|division|section|location|building|gender|designation)$/i', $c)) continue;
            $figs[] = ['k' => $plural($c), 'v' => e(count(array_unique(array_column($body, $c)))), 'n' => 'Distinct values'];
        }
    }

    $doc = [
        'kicker'       => 'Human Resources · ' . $module,
        'title'        => $report->name,
        'subtitle'     => $recs . ($recs === 1 ? ' record' : ' records') . ' · ' . $nc . ($nc === 1 ? ' column' : ' columns'),
        'meta'         => ['Generated' => now()->format('j M Y, H:i'), 'Records' => (string) $recs],
        'figs'         => $figs,
        'confidential' => 'Confidential: HR data',
        'orientation'  => $nc > 7 ? 'landscape' : 'portrait',
    ];
@endphp

@section('body')
    <h2><span class="no">1</span>Report data</h2>
    <p class="lead">All records included in this report.</p>
    <table class="dt{{ $nc > 10 ? ' dense' : '' }}">
        <thead>
            <tr>
                @foreach($cols as $c)<th class="{{ $right[$c] ? 'r' : '' }}">{{ $c }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($cells as $i => $row)
                <tr class="{{ $lastRowIsTotal && $i === $n - 1 ? 'gt' : '' }}">
                    @foreach($cols as $c)
                        @if($chip[$c] && $row[$c] !== '' && $row[$c] !== 'N/A')
                            <td><span class="status {{ $chipClass($row[$c]) }}">{{ $row[$c] }}</span></td>
                        @else
                            <td class="{{ $right[$c] ? 'r nw' : '' }}">{{ $row[$c] }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max($nc, 1) }}" class="c">No data available</td></tr>
            @endforelse
            @if($recs && !$lastRowIsTotal)
                <tr class="gt"><td colspan="{{ max($nc, 1) }}">Total: {{ $recs }} {{ $recs === 1 ? 'record' : 'records' }}</td></tr>
            @endif
        </tbody>
    </table>

    @if(!empty($insightsHtml))
        <h2><span class="no">2</span>Insights</h2>
        <p class="lead">A short summary of the report data.</p>
        <div class="ins">{!! $insightsHtml !!}</div>
    @endif
@endsection
