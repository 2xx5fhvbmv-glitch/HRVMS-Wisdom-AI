@php
     // payroll_advance.currency is the literal currency the employee
     // requested IN (MVR or USD) — not a USD-stored value to convert to
     // the resort's display currency, so it must use formatRequestCurrency()
     // (shows the stored currency as-is), same fix already applied to the
     // main request-list column. formatCurrency(...,'USD') here always
     // showed "$" regardless of what the employee actually requested.
     $scheduleCurrency = $payroll_advance_data->currency ?: 'USD';
@endphp
@foreach($month_year_array as $key => $value)
<tr>
     <td>
          <select class="form-select repay-month-select" aria-label="Default select example">
               @foreach($availableMonths as $month)
                    <option value="{{$month}}" @if($value['month'] == $month) selected @endif>{{$month}}</option>
               @endforeach
          </select>
     </td>
     <td>{{ Common::formatRequestCurrency($value['installment_amount'], $scheduleCurrency) }}</td>
     <td>
          <div class="position-relative">
               <input type="text" class="form-control interest-input"
                       name="{{$value['month']}}-interest"
                       data-month="{{$value['month']}}"
                       data-installment="{{$value['installment_amount']}}"
                       data-remaining="{{$value['remaining_balance']}}"
                       data-payroll_advance_id="{{$payroll_advance_data->id}}"
                       placeholder="Enter Interest Value" value="{{ $value['interest']}}">
               <i class="fa-solid fa-percent"></i>
          </div>
     </td>
     <td>{{ Common::formatRequestCurrency($value['remaining_balance'], $scheduleCurrency) }}</td>
</tr>
@endforeach
