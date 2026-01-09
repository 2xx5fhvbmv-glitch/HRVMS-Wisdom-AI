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
               <div class="col-12">
                    <select class="form-select select2t-none increment-type" name="increment_type" required>
                         <option selected value="">Increment Type</option>
                         @foreach ($incrementTypes as $increment_type)
                         <option value="{{$increment_type->name}}" @if($increment_type->name == $peopleSalaryIncrement->increment_type) selected @endif>{{$increment_type->name}}</option>
                         @endforeach
                    </select></div>

               <div class="col-12">
                    <select class="form-select select2t-none increment-type" name="pay_increase_type" required>
                         <option selected value="">Pay Increase Type</option>
                         @foreach ($payIncreaseTypes as $key => $type)
                              <option value="{{$key}}" @if($key == $peopleSalaryIncrement->pay_increase_type) selected @endif>{{$type}}</option>
                         @endforeach
                    </select>
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
    <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal" aria-label="Close">Cancal</button>
 </div>
