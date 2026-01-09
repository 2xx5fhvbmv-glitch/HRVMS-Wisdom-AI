<tr class="details-row">
    <td>{{ $month }}</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td>${{ number_format($salary, 2) }}</td>
    <td>${{ number_format($ot, 2) }}</td>
    <td>${{ number_format($insurance, 2) }}</td>
    <td>${{ number_format($recruitment ?? 0, 2) }}</td>
    <td>
        ${{ number_format(
            $salary + $ot + $insurance + ($recruitment ?? 0) +
            $visa + $work_permit + $medical + $quota +
            $allowances->sum('amount'), 2)
        }}
    </td>
    <td></td>
</tr>
