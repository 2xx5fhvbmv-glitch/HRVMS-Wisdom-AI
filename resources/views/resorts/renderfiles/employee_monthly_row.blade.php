<tr class="details-row">
    <td>{{ $month }}</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td>{!! Common::formatCurrency($salary, 'USD') !!}</td>
    <td>{!! Common::formatCurrency($ot, 'USD') !!}</td>
    <td>{!! Common::formatCurrency($insurance, 'USD') !!}</td>
    <td>{!! Common::formatCurrency($recruitment ?? 0, 'USD') !!}</td>
    <td>
        {!! Common::formatCurrency(
            $salary + $ot + $insurance + ($recruitment ?? 0) +
            $visa + $work_permit + $medical + $quota +
            $allowances->sum('amount'), 'USD') !!}
    </td>
    <td></td>
</tr>
