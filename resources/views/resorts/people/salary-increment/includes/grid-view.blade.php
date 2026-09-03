<div class="row g-md-4 g-3 mb-md-4 mb-3">
@if($employees->count()> 0)
    @foreach ($employees as $employee)
        @php
            // Use getResortUserPicture (modern helper) — handles orphaned
            // Admin_Parent_id without crashing and returns the default
            // picture URL when the employee has no profile photo.
            $employee_image = App\Helpers\Common::getResortUserPicture($employee->Admin_Parent_id ?? null);
            $defaultPicture = url(config('settings.default_picture'));
        @endphp
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="salaryIncrementManag-main">
                <div class="salaryIncrementManag-inner">
                    <div class="salaryIncrementManag-block front">
                        <div class="img-circle"><img src="{{$employee_image}}" alt="image"
                             onerror="this.onerror=null;this.src='{{ $defaultPicture }}';"></div>
                        <h6>{{$employee->resortAdmin->full_name}}</h6>
                        <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                        <p>{{$employee->position->position_title}}</p>
                        <span class="position">{{$employee->department->name}}</span>
                        <span class="date">Joining Date: {{Carbon\Carbon::parse($employee->joining_date)->format('d M Y')}}</span>

                        @php
                            // Legacy data: some employee rows have
                            // basic_salary stored in MVR with
                            // basic_salary_currency='MVR' (pre-USD-only
                            // convention). Trust the per-row column when
                            // present so MVR-stored values get converted to
                            // the resort's display currency correctly.
                            // formatCurrency('MVR', value) handles the
                            // MVR→display conversion at render time.
                            $basicCurrency = $employee->basic_salary_currency
                                ?: 'USD';
                        @endphp
                        <div class="d-flex bg">
                            <p>Current Basic Salary</p>
                            <p>{!! App\Helpers\Common::formatCurrency($employee->basic_salary, $basicCurrency) !!}</p>
                        </div>
                        <div class="bg">
                            <div class="d-flex">
                                <p>Last Increment Amount:</p>
                                {{-- The increment amount is in the same
                                     currency as the basic_salary it
                                     incremented. Reuse $basicCurrency. --}}
                                <p>{!! !empty($employee->last_increment_salary_amount)
                                    ? App\Helpers\Common::formatCurrency($employee->last_increment_salary_amount, $basicCurrency)
                                    : '-' !!}</p>
                            </div>
                            <div class="d-flex">
                                <p>Last Increment Date:</p>
                                {{-- Render the date only when an increment was actually recorded.
                                     Carbon::parse(null) silently returns "now()", so the old
                                     `Carbon::parse(@$employee->incremented_date)->format(...) ?? '-'`
                                     produced today's date for every employee with no increment. --}}
                                <p>{{ !empty($employee->incremented_date)
                                    ? Carbon\Carbon::parse($employee->incremented_date)->format('d M Y')
                                    : '-' }}</p>
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
                        <div class="img-circle"><img src="{{$employee_image}}" alt="image"
                             onerror="this.onerror=null;this.src='{{ $defaultPicture }}';"></div>
                        <h6>{{$employee->resortAdmin->full_name}}</h6>
                        <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                        <p>{{$employee->position->position_title}}</p>
                        <span class="position mb-2">{{$employee->department->name}}</span>
                        <form class="row g-2 text-start mb-2 employee-increment-form"
                              data-employee-id="{{$employee->id}}"
                              data-current-salary="{{ (float) $employee->basic_salary }}"
                              data-budgeted-salary="{{ (float) ($employee->budgeted_salary ?? 0) }}"
                              data-parsley-validate>
                            <input type="hidden" name="emp_id" value="{{$employee->id}}" id="">
                            <div class="col-12">
                                <select class="form-select increment-type dd-native-select" id="increment-type-{{ $employee->id }}" name="increment_type" required data-parsley-required-message="Please select an increment type.">
                                    <option value="">Increment Type</option>
                                    @foreach ($incrementTypes as $increment_type)
                                        <option value="{{$increment_type->name}}">{{$increment_type->name}}</option>
                                    @endforeach
                                </select>
                                <div class="dd" data-target="#increment-type-{{ $employee->id }}">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Increment Type</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Increment Type">
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Increment Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @foreach ($incrementTypes as $increment_type)
                                                <div class="dd-item" role="option" data-value="{{ $increment_type->name }}"><span class="dd-nm">{{ $increment_type->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                {{-- "Pay Increase Type" placeholder option removed per
                                     request. Defaults to Fixed; switchable to Percentage. --}}
                                @php $selectedPayIncrease = $payIncreaseTypes['Fixed'] ?? reset($payIncreaseTypes); @endphp
                                <select class="form-select pay-increase-type dd-native-select" id="pay-increase-type-{{ $employee->id }}" name="pay_increase_type">
                                    @foreach ($payIncreaseTypes as $key => $type)
                                        <option value="{{$key}}" {{ $key === 'Fixed' ? 'selected' : '' }}>{{$type}}</option>
                                    @endforeach
                                </select>
                                <div class="dd" data-target="#pay-increase-type-{{ $employee->id }}">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedPayIncrease }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Pay Increase Type">
                                        <div class="dd-scroll">
                                            @foreach ($payIncreaseTypes as $key => $type)
                                                <div class="dd-item{{ $key === 'Fixed' ? ' active' : '' }}" role="option" data-value="{{ $key }}"><span class="dd-nm">{{ $type }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Drop `form-control-small` on the three text inputs so they
                                 match the default-size selects above. Font size + height
                                 are now consistent across every row in the card. --}}
                            <div class="col-12">
                                <div class="input-group value-input-group">
                                    {{-- Currency prefix (system symbol) shown when Fixed is selected --}}
                                    <span class="input-group-text currency-prefix">{{ App\Helpers\Common::GetResortCurrencySymbol() }}</span>
                                    <input type="number" class="form-control value" name="value" placeholder="Enter fixed value" required data-parsley-required-message="Enter a value." min="0" max="999999.99">
                                    {{-- Percentage suffix shown when Percentage is selected --}}
                                    <span class="input-group-text percent-suffix d-none">%</span>
                                </div>
                                {{-- Budget-exceed warning — toggled by JS in index.blade.php
                                     when the proposed new salary (current + increment) is
                                     higher than the position's budgeted_salary. Mirrors the
                                     same yellow banner used by the promotion module. --}}
                                <div class="alert alert-warning py-1 px-2 mt-1 mb-0 budget-exceed-warning d-none"
                                     style="font-size:0.78rem; border-left:4px solid #f0ad4e;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <span class="budget-exceed-text"></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control datepicker effective-date" name="effective_date" placeholder="Effective Date" required data-parsley-required-message="Select effective date.">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control remark" name="remark" placeholder="Remark" required data-parsley-required-message="Enter remark.">
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