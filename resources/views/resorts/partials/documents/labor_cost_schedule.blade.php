{{-- Schedule of Labor Costs (Workforce Planning › Reports › Annual Workforce Plan).
     Binds to the rows the report already builds: Department, Position, Grade, Approved/Planned Headcount, Total Budget
     (a "$1,234.00" or "MVR 1,234.00" string, salary + allowances). Grouping, management split, totals and currency
     conversion: App\Services\Reports\LaborCostSchedule; this view only formats.

     Currency: MVR lines are converted to USD ONLY when the caller passes $fxRate (MVR per 1 USD; the Workforce Planning
     export passes the resort's DollertoMVR). Without it nothing is converted and MVR / USD are never added together. --}}
@extends('resorts.partials.documents._frame')

@php
    $FX = (isset($fxRate) && is_numeric($fxRate) && (float) $fxRate > 0) ? (float) $fxRate : null;

    // Grouping, management split and totals: App\Services\Reports\LaborCostSchedule (unit-tested).
    ['groups' => $groups, 'tree' => $tree, 'grp' => $grp, 'grand' => $grand, 'approved' => $approved, 'lines' => $lines]
        = \App\Services\Reports\LaborCostSchedule::build($data ?? [], $FX);
    $norm = fn($s) => \App\Services\Reports\LaborCostSchedule::norm($s);
    $f    = fn($n) => number_format(round($n));
    $src  = fn($n) => floor($n) == $n ? number_format($n) : number_format($n, 2);
    // Money cell for a ['USD' => x, 'MVR' => y] bucket: one number when converted, else one line per source currency.
    $cell = function (array $b) use ($FX, $f) {
        if ($FX) return $f($b['USD']);
        $o = [];
        if ($b['USD'] > 0) $o[] = 'USD ' . $f($b['USD']);
        if ($b['MVR'] > 0) $o[] = 'MVR ' . $f($b['MVR']);
        return $o ? implode('<br>', $o) : '0';
    };

    // A single sub-department that just repeats its department's name is not shown as its own row.
    $showSubs = fn($dept, $d) => count($d['subs']) > 1 || $norm(array_key_first($d['subs'])) !== $norm($dept);

    $U = $grand['t']['USD'];
    $pct = fn($x) => ($FX && $U > 0) ? number_format($x / $U * 100, 1) . '%' : '—';
    $per = fn($a) => ($FX && $a['hc'] > 0) ? $f($a['t']['USD'] / $a['hc']) : '—';
    $mgSum = $grand['m']['USD'];

    $doc = [
        'kicker'       => 'Human Resources · Payroll',
        'title'        => 'Schedule of Labor Costs',
        'subtitle'     => 'Annual Workforce Plan by department',
        'meta'         => [
            'Basis'              => 'Annual budgeted salary',
            'Reporting currency' => $FX ? 'US Dollar (USD)' : 'Source currency (MVR and USD, not combined)',
            'Exchange rate'      => $FX ? 'MVR ' . rtrim(rtrim(number_format($FX, 4, '.', ''), '0'), '.') . ' = USD 1' : 'Not applied',
            'Source'             => 'Annual Workforce Plan, ' . now()->format('j M Y H:i'),
        ],
        'figs'         => [
            ['k' => 'Total salaries & wages', 'v' => $FX ? '$' . $f($U) : $cell($grand['t']), 'n' => $FX ? 'Annual budget, restated in USD' : 'Annual budget, per source currency'],
            ['k' => 'Headcount', 'v' => e($grand['hc']), 'n' => $lines . ' positions · ' . ($approved === $grand['hc'] ? 'approved = planned' : 'Approved ' . $approved)],
            ['k' => 'Average cost per head', 'v' => $FX && $grand['hc'] ? '$' . $f($U / $grand['hc']) : '—', 'n' => $FX ? 'Per year' : 'Needs an exchange rate'],
            ['k' => 'Management share', 'v' => $pct($mgSum), 'n' => $grand['mh'] . ' heads' . ($FX ? ' · $' . $f($mgSum) : '')],
        ],
        'confidential' => 'Confidential: payroll data',
    ];

    // Summary row: department / sub-department / subtotal (cls: d | s | st | gt)
    $sumRow = function (string $cls, string $label, array $a, bool $pc) use ($cell, $per, $pct) {
        return '<tr class="' . $cls . '"><td class="l">' . e($label) . '</td><td class="r">' . $a['hc'] . '</td><td class="r">' . $cell($a['m']) . '</td><td class="r">'
            . $cell($a['n']) . '</td><td class="r">' . $cell($a['t']) . '</td><td class="r">' . $per($a) . '</td><td class="r">' . ($pc ? $pct($a['t']['USD']) : '') . '</td></tr>';
    };
    // Detail total row: sub-department / department / property
    $detCols = $FX ? 8 : 7;
    $totRow = function (string $cls, string $label, array $a) use ($FX, $cell, $per) {
        return '<tr class="' . $cls . '"><td class="l">' . e($label) . '</td><td></td><td></td><td class="r">' . $a['hc'] . '</td>'
            . ($FX ? '<td></td><td></td><td class="r">' . $cell($a['t']) . '</td><td class="r">' . $per($a) . '</td>'
                   : '<td class="r">' . $cell($a['t']) . '</td><td></td><td></td>') . '</tr>';
    };
@endphp

@section('body')
    <h2><span class="no">1</span>Summary of labor costs by department</h2>
    <p class="lead">Salaries &amp; wages split into management and non-management{{ $FX ? ', in USD' : ', per source currency' }}.</p>
    <table class="dt">
        <thead>
            <tr>
                <th>Department</th><th class="r">Headcount</th><th class="r">Management</th><th class="r">Non-management</th>
                <th class="r">Total S&amp;W</th><th class="r">Per head</th><th class="r">% of total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $g)
                <tr class="h"><td colspan="7">{{ $g }}</td></tr>
                @foreach($tree[$g] as $dept => $d)
                    {!! $sumRow('d', $dept, $d['a'], true) !!}
                    @if($showSubs($dept, $d))
                        @foreach($d['subs'] as $sub => $s)
                            {!! $sumRow('s', $sub, $s['a'], false) !!}
                        @endforeach
                    @endif
                @endforeach
                {!! $sumRow('st', 'Total ' . strtolower(explode(' ', $g)[0]), $grp[$g], true) !!}
            @endforeach
            <tr class="h"><td colspan="7">Payroll-related expenses</td></tr>
            @foreach(['Service charge distribution', 'Payroll taxes & MPS pension', 'Supplemental pay', 'Employee benefits'] as $x)
                <tr class="s nil"><td class="l">{{ $x }}</td><td></td><td></td><td></td><td class="r">—</td><td></td><td></td></tr>
            @endforeach
            {!! $sumRow('gt', 'Total labor cost', $grand, true) !!}
        </tbody>
    </table>

    <h2 class="brk"><span class="no">2</span>Departmental schedules</h2>
    <p class="lead">Every position from the source plan with the original budget amount, its currency{{ $FX ? ' and the USD equivalent' : '' }}.</p>
    <table class="dt">
        <thead>
            <tr>
                <th>Position</th><th>Grade</th><th>Class</th><th class="r">Headcount</th><th class="r">Source budget</th><th>Cur.</th>
                @if($FX)<th class="r">USD</th><th class="r">USD per head</th>@else<th class="r">Per head</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $g)
                @foreach($tree[$g] as $dept => $d)
                    <tr class="h"><td colspan="{{ $detCols }}">{{ $dept }}</td></tr>
                    @foreach($d['subs'] as $sub => $s)
                        @if($showSubs($dept, $d))<tr class="d"><td class="l" colspan="{{ $detCols }}">{{ $sub }}</td></tr>@endif
                        @foreach($s['rows'] as $r)
                            <tr class="s">
                                <td class="l">{{ $r['pos'] }}</td>
                                <td class="c">{{ $r['grade'] }}</td>
                                <td class="c">{{ $r['mg'] ? 'Mgmt' : 'Non-mgmt' }}</td>
                                <td class="r">{{ $r['hc'] }}</td>
                                <td class="r">{{ $src($r['amt']) }}</td>
                                <td class="c">{{ $r['cur'] }}</td>
                                @if($FX)
                                    <td class="r">{{ $f($r['v']) }}</td>
                                    <td class="r">{{ $r['hc'] ? $f($r['v'] / $r['hc']) : '—' }}</td>
                                @else
                                    <td class="r">{{ $r['hc'] ? $f($r['amt'] / $r['hc']) : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @if(count($d['subs']) > 1){!! $totRow('st', 'Total ' . $sub, $s['a']) !!}@endif
                    @endforeach
                    {!! $totRow('st', 'Total ' . $dept, $d['a']) !!}
                @endforeach
            @endforeach
            {!! $totRow('gt', 'Total property', $grand) !!}
        </tbody>
    </table>
@endsection
