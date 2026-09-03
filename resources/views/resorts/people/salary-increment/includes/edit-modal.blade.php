@php
     $employee_image = App\Helpers\Common::GetAdminResortProfile($peopleSalaryIncrement->employee->Admin_Parent_id);
     $employee = $peopleSalaryIncrement->employee;
@endphp
<div class="modal-body">
     <div class="salaryIncrementManag-block back">
          <div class="img-circle"><img src="{{$employee_image}}" alt="image"></div>
          <h6>{{$employee->resortAdmin->full_name}}</h6>
          <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
          <p>{{$employee->position->position_title}}</p>
          <span class="position mb-2">{{$employee->department->name}}</span>
          <form action="{{route('people.salary-increment.update', $peopleSalaryIncrement->id)}}" method="POST" class="row g-2 text-start mb-2" id="employee-increment-edit" >
               <input type="hidden" name="emp_id" value="{{$employee->id}}" id="">
               @php
                    $selectedIncrementType = $peopleSalaryIncrement->increment_type;
                    $incrementTypeMatched = collect($incrementTypes)->contains(fn($t) => $t->name == $selectedIncrementType);
                    $selectedPayIncreaseLabel = $payIncreaseTypes[$peopleSalaryIncrement->pay_increase_type] ?? null;
               @endphp
               <div class="col-12">
                    <select class="form-select increment-type dd-native-select" id="increment-type-select" name="increment_type" required>
                         <option selected value="">Increment Type</option>
                         @foreach ($incrementTypes as $increment_type)
                         <option value="{{$increment_type->name}}" @if($increment_type->name == $peopleSalaryIncrement->increment_type) selected @endif>{{$increment_type->name}}</option>
                         @endforeach
                    </select>
                    <div class="dd" data-target="#increment-type-select">
                         <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                              <span class="dd-lbl">{{ $incrementTypeMatched ? $selectedIncrementType : 'Increment Type' }}</span>
                              <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                         </button>
                         <div class="dd-panel" role="listbox" aria-label="Increment Type">
                              <div class="dd-scroll">
                                   <div class="dd-item{{ $incrementTypeMatched ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Increment Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                   @foreach ($incrementTypes as $increment_type)
                                   <div class="dd-item{{ $increment_type->name == $selectedIncrementType ? ' active' : '' }}" role="option" data-value="{{ $increment_type->name }}"><span class="dd-nm">{{ $increment_type->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                   @endforeach
                              </div>
                         </div>
                    </div>
               </div>

               <div class="col-12">
                    <select class="form-select increment-type dd-native-select" id="pay-increase-type-select" name="pay_increase_type" required>
                         <option selected value="">Pay Increase Type</option>
                         @foreach ($payIncreaseTypes as $key => $type)
                              <option value="{{$key}}" @if($key == $peopleSalaryIncrement->pay_increase_type) selected @endif>{{$type}}</option>
                         @endforeach
                    </select>
                    <div class="dd" data-target="#pay-increase-type-select">
                         <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                              <span class="dd-lbl">{{ $selectedPayIncreaseLabel ?? 'Pay Increase Type' }}</span>
                              <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                         </button>
                         <div class="dd-panel" role="listbox" aria-label="Pay Increase Type">
                              <div class="dd-scroll">
                                   <div class="dd-item{{ $selectedPayIncreaseLabel ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Pay Increase Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                   @foreach ($payIncreaseTypes as $key => $type)
                                        <div class="dd-item{{ $key == $peopleSalaryIncrement->pay_increase_type ? ' active' : '' }}" role="option" data-value="{{ $key }}"><span class="dd-nm">{{ $type }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                   @endforeach
                              </div>
                         </div>
                    </div>
               </div>
               
               <div class="col-12">
                    <input type="text" class="form-control form-control-small value" name="value" value="{{number_format($peopleSalaryIncrement->value)}}"
                         placeholder="value">
               </div>
               <div class="col-12"><input type="text" class="form-control form-control-small datepicker effective-date" name="effective_date" value="{{Carbon\Carbon::parse($peopleSalaryIncrement->effective_date)->format('d/m/Y')}}"
                         placeholder="Effective Date">
               </div>
               <div class="col-12"><input type="text" class="form-control form-control-small remark" name="remark" value="{{$peopleSalaryIncrement->remarks}}"
                         placeholder="Remark">
               </div>
          </form>
     </div>
</div>
<div class="modal-footer">
     <botton class="btn btn-themeBlue submit-employee-increment">Update</botton>
    <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
 </div>
