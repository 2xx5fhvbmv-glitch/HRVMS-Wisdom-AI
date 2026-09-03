@foreach($month_year_array as $key => $value)
<tr>
     <td>
          <select class="form-select dd-native-select" id="paySchedMonth-{{ $key }}" aria-label="Default select example" disabled>
               {{-- @foreach($availableMonths as  $month)
                    <option value="{{$month}}"  @if($value['month'] == $month) selected @endif>{{$month}}</option>
               @endforeach --}}
               <option value="{{$value['month']}}">{{$value['month']}}</option>
          </select>
          <div class="dd" data-target="#paySchedMonth-{{ $key }}">
               <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" disabled>
                    <span class="dd-lbl">{{ $value['month'] }}</span>
                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
               </button>
               <div class="dd-panel" role="listbox" aria-label="Payroll month">
                    <div class="dd-scroll">
                         <div class="dd-item active" role="option" data-value="{{ $value['month'] }}"><span class="dd-nm">{{ $value['month'] }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                    </div>
               </div>
          </div>
     </td>
     <td>{{ Common::formatCurrency($value['installment_amount'], 'USD') }}</td>
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
     <td>{{ Common::formatCurrency($value['remaining_balance'], 'USD') }}</td>
</tr>
@endforeach
