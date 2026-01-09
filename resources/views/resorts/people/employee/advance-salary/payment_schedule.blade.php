@foreach($month_year_array as $key => $value)
<tr>
     <td>
          <select class="form-select" aria-label="Default select example">
               {{-- @foreach($availableMonths as  $month)
                    <option value="{{$month}}"  @if($value['month'] == $month) selected @endif>{{$month}}</option>
               @endforeach --}}
               <option value="{{$value['month']}}">{{$value['month']}}</option>
          </select>
     </td>
     <td>${{$value['installment_amount']}}</td>
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
     <td>${{$value['remaining_balance']}}</td>
</tr>
@endforeach
