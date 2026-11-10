{{-- Expat Employee Register (Visa › Reports). Binds to the rows the report already builds: Employee ID, Employee Name,
     Nationality, Position, Department, Joining Date, Employment Status. Counts are computed here from those rows. --}}
@extends('resorts.partials.documents._frame')

@php
    $rows   = array_values($data ?? []);
    $n      = count($rows);
    $val    = fn($r, $k) => trim((string) ($r[$k] ?? '')) !== '' ? trim((string) $r[$k]) : 'N/A';
    $active = count(array_filter($rows, fn($r) => strtolower($val($r, 'Employment Status')) === 'active'));

    // [name, count] sorted by count desc, then name
    $tally = function (string $key) use ($rows, $val) {
        $c = [];
        foreach ($rows as $r) { $k = $val($r, $key); $c[$k] = ($c[$k] ?? 0) + 1; }
        $out = [];
        foreach ($c as $k => $v) { $out[] = [$k, $v]; }
        usort($out, fn($a, $b) => $b[1] <=> $a[1] ?: strcasecmp($a[0], $b[0]));
        return $out;
    };
    $nat = $tally('Nationality');
    $dep = $tally('Department');

    $depNote = '—';
    if (count($dep) > 1 && $dep[0][1] === $dep[1][1]) { $depNote = $dep[0][0] . ' and ' . $dep[1][0] . ' lead (' . $dep[0][1] . ' each)'; }
    elseif (count($dep)) { $depNote = $dep[0][0] . ' (' . $dep[0][1] . ')'; }

    $chip = function (string $s) {
        $k = strtolower($s);
        if ($k === 'active') return 'ok';
        if (in_array($k, ['probation', 'pending', 'on leave', 'notice period', 'suspended'], true)) return 'warn';
        if (in_array($k, ['terminated', 'resigned', 'inactive', 'absconded', 'deactivated'], true)) return 'bad';
        return '';
    };

    $doc = [
        'kicker'       => 'Human Resources · People',
        'title'        => 'Expat Employee Register',
        'subtitle'     => 'Expatriate employees by nationality, department and position',
        'meta'         => [
            'Generated' => now()->format('j M Y, H:i'),
            'Scope'     => 'Expatriate employees',
            'Employees' => $active === $n ? $n . ' · all active' : $active . ' active of ' . $n,
        ],
        'figs'         => [
            ['k' => 'Expat employees', 'v' => e($n), 'n' => 'On the register'],
            ['k' => 'Nationalities', 'v' => e(count($nat)), 'n' => count($nat) ? 'Largest: ' . $nat[0][0] . ' (' . $nat[0][1] . ')' : '—'],
            ['k' => 'Departments', 'v' => e(count($dep)), 'n' => $depNote],
            ['k' => 'Active', 'v' => e($active), 'n' => 'of ' . $n . ' employees'],
        ],
        'confidential' => 'Confidential: employee personal data',
    ];

    // Summary table (Nationality / Department): name · employees · share + bar
    $summary = function (string $label, array $list) use ($n) {
        $h = '<table class="dt"><thead><tr><th>' . e($label) . '</th><th class="r">Employees</th><th class="r">Share</th></tr></thead><tbody>';
        foreach ($list as [$name, $c]) {
            $p = $n ? $c / $n * 100 : 0;
            $h .= '<tr><td>' . e($name) . '</td><td class="r">' . $c . '</td><td class="r share"><span class="bar" style="width:' . round($p * 0.4) . 'px"></span>' . round($p) . '%</td></tr>';
        }
        return $h . '<tr class="gt"><td>Total</td><td class="r">' . $n . '</td><td class="r">100%</td></tr></tbody></table>';
    };
@endphp

@section('body')
    <h2><span class="no">1</span>Employee register</h2>
    <p class="lead">All expatriate employees on record.</p>
    <table class="dt">
        <thead>
            <tr>
                <th>Employee ID</th><th>Employee name</th><th>Nationality</th><th>Position</th>
                <th>Department</th><th class="r">Joining date</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                @php $st = $val($r, 'Employment Status'); @endphp
                <tr>
                    <td class="id">{{ $val($r, 'Employee ID') }}</td>
                    <td class="nm">{{ $val($r, 'Employee Name') }}</td>
                    <td>{{ $val($r, 'Nationality') }}</td>
                    <td>{{ $val($r, 'Position') }}</td>
                    <td>{{ $val($r, 'Department') }}</td>
                    <td class="r">{{ $val($r, 'Joining Date') }}</td>
                    <td><span class="status {{ $chip($st) }}">{{ $st }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="c">No data available</td></tr>
            @endforelse
            <tr class="gt"><td colspan="7">Total: {{ $n }} employees</td></tr>
        </tbody>
    </table>

    <h2><span class="no">2</span>Summary</h2>
    <p class="lead">Headcount by nationality and by department.</p>
    <table class="split">
        <tr>
            <td>{!! $summary('Nationality', $nat) !!}</td>
            <td class="gap"></td>
            <td>{!! $summary('Department', $dep) !!}</td>
        </tr>
    </table>
@endsection
