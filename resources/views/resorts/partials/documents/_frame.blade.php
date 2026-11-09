{{-- Shared frame for downloadable formal documents (Schedule of Labor Costs, Expat Employee Register).
     A document's own view does @extends('resorts.partials.documents._frame'), sets $doc and fills @section('body'):
       $doc = ['kicker' => 'Human Resources · Payroll', 'title' => '…', 'subtitle' => '…',
               'meta' => ['Label' => 'value', …], 'figs' => [['k' => '…', 'v' => '…', 'n' => '…'] ×4],
               'confidential' => 'Confidential: payroll data']
     Letterhead and footer are plain tables (DomPDF has no flex/grid). Only the resort name is shown in the footer; logo and address are not part of this frame yet;
     it would slot into the first cell of the letterhead and the footer row below. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $doc['title'] }}</title>
    <style>
@page { size: A4 {{ $doc['orientation'] ?? 'portrait' }}; margin: 14mm 12mm; }
@include('resorts.partials.documents._styles')
    </style>
</head>
<body>
    {{-- Letterhead --}}
    <table class="lh">
        <tr>
            <td>
                <div class="org">{{ $doc['kicker'] }}</div>
                <h1>{{ $doc['title'] }}</h1>
                <div class="lh-sub">{{ $doc['subtitle'] }}</div>
            </td>
            <td class="lh-r">
                @foreach($doc['meta'] as $label => $value)
                    <b>{{ $label }}</b> {{ $value }}<br>
                @endforeach
            </td>
        </tr>
    </table>

    {{-- Key figures --}}
    <table class="figs">
        <tr>
            @foreach($doc['figs'] as $i => $fig)
                <td class="{{ $i === 0 ? 'f1' : '' }}" style="width: {{ round(100 / count($doc['figs']), 2) }}%">
                    <div class="fig-k">{{ $fig['k'] }}</div>
                    <div class="fig-v">{!! $fig['v'] !!}</div>
                    <div class="fig-n">{{ $fig['n'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    @yield('body')

    {{-- Footer: the resort's own name (same source as the app header), never the product name --}}
    @php $resortName = optional(optional(Auth::guard('resort-admin')->user())->resort)->resort_name; @endphp
    <table class="foot">
        <tr>
            <td>{{ $doc['confidential'] }}</td>
            <td class="r">{{ $resortName ? '©' . date('Y') . ' ' . $resortName : '' }}</td>
        </tr>
    </table>
</body>
</html>
