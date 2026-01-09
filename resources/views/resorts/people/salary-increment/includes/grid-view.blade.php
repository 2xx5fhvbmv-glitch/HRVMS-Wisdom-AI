<div class="row g-md-4 g-3 mb-md-4 mb-3">
@if($employees->count()> 0)
    @foreach ($employees as $employee)
        @php
            $employee_image = App\Helpers\Common::GetAdminResortProfile($employee->Admin_Parent_id);
        @endphp
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="salaryIncrementManag-main">
                <div class="salaryIncrementManag-inner">
                    <div class="salaryIncrementManag-block front">
                        <div class="img-circle"><img src="{{$employee_image}}" alt="image"></div>
                        <h6>{{$employee->resortAdmin->full_name}}</h6>
                        <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                        <p>{{$employee->position->position_title}}</p>
                        <span class="position">{{$employee->department->name}}</span>
                        <span class="date">Joining Date: {{Carbon\Carbon::parse($employee->joining_date)->format('d/m/Y')}}</span>

                        <div class="d-flex bg">
                            <p>Current Basic Salary</p>
                            <p>{{number_format($employee->basic_salary,'1')}}</p>
                        </div>
                        <div class="bg">
                            <div class="d-flex">
                                <p>Last Increment Amount:</p>
                                <p>{{@$employee->last_increment_salary_amount ?? '-'}}</p>
                            </div>
                            <div class="d-flex">
                                <p>Last Increment Date:</p>
                                <p>{{Carbon\Carbon::parse(@$employee->incremented_date)->format('d M Y') ?? '-'}}</p>
                            </div>
                            <div class="text-center"><span class="badge badge-info">{{@$employee->last_salary_increment_type ?? ''}}</span>
                            </div>
                        </div>
                        <div>
                            <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm flipBtn">Set
                                Increment</a>
                        </div>
                    </div>
                    <div class="salaryIncrementManag-block back">
                        <div class="img-circle"><img src="{{$employee_image}}" alt="image"></div>
                        <h6>{{$employee->resortAdmin->full_name}}</h6>
                        <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                        <p>{{$employee->position->position_title}}</p>
                        <span class="position mb-2">{{$employee->department->name}}</span>
                        <form class="row g-2 text-start mb-2 employee-increment-form" data-employee-id="{{$employee->id}}" data-parsley-validate>
                            <input type="hidden" name="emp_id" value="{{$employee->id}}" id="">
                            <div class="col-12">
                                <select class="form-select select2t-none increment-type" name="increment_type" required data-parsley-required-message="Please select an increment type.">
                                    <option value="">Increment Type</option>
                                    @foreach ($incrementTypes as $increment_type)
                                        <option value="{{$increment_type->name}}">{{$increment_type->name}}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-12">
                                    <select class="form-select select2t-none pay-increase-type" name="pay_increase_type" required data-parsley-required-message="Please select a pay increase type.">
                                        <option value="">Pay Increase Type</option>
                                        @foreach ($payIncreaseTypes as $key => $type)
                                            <option value="{{$key}}">{{$type}}</option>
                                        @endforeach
                                    </select>
                            </div>
                            <div class="col-12">
                                <input type="number" class="form-control form-control-small value" name="value" placeholder="Amount/Percentage" required data-parsley-required-message="Enter a value." min="0" max="999999.99">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-small datepicker effective-date" name="effective_date" placeholder="Effective Date" required data-parsley-required-message="Select effective date.">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-small remark" name="remark" placeholder="Remark" required data-parsley-required-message="Enter remark.">
                            </div>
                        </form>
                        <div>
                            <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm flipBtnBack"><i
                                    class="fa-solid fa-arrow-left me-2"></i>
                                Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
<div class="col-12">
    <div class="text-center">
        <h4>No Employee Found!</h4>
    </div>
</div>
@endif
</div>
<div class="mt-3">
    {!! $employees->withQueryString()->links() !!}
</div>