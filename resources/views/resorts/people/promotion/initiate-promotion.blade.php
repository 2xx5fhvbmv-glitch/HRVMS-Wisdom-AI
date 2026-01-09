@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content') 
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Initiate Promotion</a></div> -->
                </div>
            </div>

            <div class="card card-IniPromo">
                <!-- <div class="row g-2 mb-md-4 mb-2">
                    <div class="col-lg-4 col-sm-6 col">
                        <div class="input-group">
                            <input type="search" class="form-control " placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-auto"><a href="#" class="btn btn-themeGrayLight">Filter</a></div>
                </div> -->
                <div class="iniPromotion-block bg-themeGrayLight">
                    <div class="row g-lg-4 g-2">
                        <div class="col-xl-4 col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input filter-checkbox" id="filter-disciplinary" data-filter="disciplinary">
                                <label class="form-check-label" for="filter-disciplinary">Exclude employees with
                                    active disciplinary actions</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" data-filter="probation" id="filter-probation">
                                <label class="form-check-label" for="filter-probation">Exclude employees on probation</label>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input filter-checkbox" id="filter-promoted" data-filter="promoted">
                                <label class="form-check-label" for="filter-promoted">Exclude employees who recently got a promotion</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input filter-checkbox" id="filter-training" data-filter="training">
                                <label class="form-check-label" for="filter-training">Exclude employees who have not completed mandatory onboarding training</label>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="initiate-promotion" data-parsley-validate>
                    <div class="iniProSelectEmp-block">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3 align-items-center">
                            <div class="col-lg-4">
                                <label for="select_employee" class="form-label">SELECT EMPLOYEE <span class="red-mark">*</span></label>
                                <select id="select_employee" name="select_employee" class="select2t-none form-select" aria-label="Default select example" onchange="getEmpDetails(this.value)" required 
                                data-parsley-required-message="Please select an Employee" data-parsley-errors-container="#emp-error">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="emp-error"></div>
                            </div>
                            <div class="col-lg-4 col-sm-6">
                                <input type="hidden" id="old_position_id" name="old_position_id"/>
                                <div class="d-flex align-items-center" id="empDetails"></div>
                            </div>
                            <div class="col-md-2 col-sm-3  col-5 bor">
                                <label class="form-label">Hired Date</label>
                                <span id="hired-date"></span>
                            </div>
                            <div class="col-lg-2 col-sm-3  col-7 bor">
                                <label class="form-label">Last Promotion Date</label>
                                <span id="last-promotion-date"></span>
                            </div>
                        </div>
                    </div>
                    <div class="cardBorder-block mb-4">
                        <div class="card-title">
                            <h3>Current Details</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table-lableNew w-100">
                                <tr>
                                    <td>Basic Salary:</td>
                                    <td id="basic_salary"></td>
                                    <input type="hidden" id="hdn_old_basic_salary" name="hdn_old_basic_salary"/>
                                </tr>
                                <tr>
                                    <td>Job Description:</td>
                                    <td>
                                        <a 
                                        class="a-link view-job-description" 
                                        id="job-description-link">
                                            View Job Description
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Current Benefit Grid:</td>
                                    <td>
                                        <a 
                                        class="a-link view-benifit-grid" 
                                        id="view-benifit-grid">
                                            View Benifit Grid
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="cardBorder-block mb-4">
                        <div class="card-title">
                            <h3>New Details</h3>
                        </div>
                        <div class="row g-md-4 g-3 align-items-end">
                            <div class="col-sm-6">
                                <label for="new_position" class="form-label">NEW POSITION <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" name="new_position" id="new_position" aria-label="Default select example" required 
                                data-parsley-required-message="Please select new position" data-parsley-errors-container="#pos-error" onchange="getDetails(this.value)">
                                    <option value="">Select New Position</option>
                                    @if($positions)
                                        @foreach($positions as $pos)
                                            <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="pos-error"></div>
                            </div>
                            <div class="col-sm-6">
                                <label for="level" class="form-label">LEVEL <span class="red-mark">*</span></label>
                                <select id="level" class="form-select select2t-none" name="level" @if(isset($isViewMode) && $isViewMode) disabled @endif required 
                                data-parsley-required-message="Please select level" data-parsley-errors-container="#level-error">
                                    <option value="">Select Level</option>
                                    @if(!empty($emp_grade))
                                        @foreach ($emp_grade as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="level-error"></div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label for="salary_inc" class="form-label">SALARY INCREMENT <span class="red-mark">*</span></label>
                                <input type="number" class="form-control" id="salary_inc" name="salary_inc"
                                    placeholder="Percentage" min="0" max="100" step="0.01" 
                                    data-parsley-required-message="Please enter salary increment in Percentage"
                                    data-parsley-type="number"
                                    data-parsley-one-or-other="#salary_amt"
                                    data-parsley-trigger="keyup"
                                    data-parsley-errors-container="#salary_inc_error">
                                <div id="salary_inc_error"></div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label for="salary_amt" class="form-label d-none d-sm-block">&nbsp;</label>
                                <input type="number" class="form-control" id="salary_amt" name="salary_amt"
                                    placeholder="Fixed Amount" min="0" step="0.01" max="9999999999.99"
                                    data-parsley-one-or-other="#salary_inc"
                                    data-parsley-required-message="Please enter salary increment in Fixed Amount"
                                    data-parsley-type="number"
                                    data-parsley-trigger="keyup"
                                    data-parsley-errors-container="#salary_amt_error">
                                <div id="salary_amt_error"></div>
                            </div>
                            <div class="col-sm-6">
                                <label for="effective_date" class="form-label">EFFECTIVE DATE <span class="red-mark">*</span></label>
                                <input type="text" class="form-control datepicker" name="effective_date" id="effective_date" placeholder="Effective Date" required 
                                data-parsley-required-message="Please select effective date">
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew w-100">
                                    <tr>
                                        <td>New Basic Salary:</td>
                                        <td id="new_basic_salary"></td>
                                        <input type="hidden" id="hdn_new_basic_salary" name="hdn_new_basic_salary"/>
                                    </tr>
                                    <tr>
                                        <td>Job Description:</td>
                                        <td>
                                            <a class="a-link view-job-description" id="new-job-description-link">
                                                View New Job Description
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <label for="benefit_grid" class="form-label">BENEFIT GRID UPDATE <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" id="benefit_grid" name="benefit_grid"
                                    aria-label="Default select example" required 
                                    data-parsley-required-message="Please select benefit grid" data-parsley-errors-container="#benefitgrid-error">
                                    <option value="">BENEFIT GRID UPDATE</option>
                                    @if($benefitGrids)
                                        @foreach($benefitGrids as $grid)
                                        <option value="{{ $grid->emp_grade }}">{{ config('settings.eligibilty')[$grid->emp_grade] ?? 'N/A' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="benefitgrid-error"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="comments" class="form-label">COMMENTS</label>
                                <textarea id="comments" rows="3" class="form-control" name="comments" placeholder="COMMENTS"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row align-items-center g-3">
                            <!-- <div class="col-auto"> <a href="#" class="a-link">Save As Draft</a></div> -->
                            <div class="col-auto ms-auto">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    let allOptions = {};

    $(document).ready(function () {
        const $dropdown = $('#select_employee');
        $dropdown.find('option').each(function () {
            allOptions[$(this).val()] = $(this);
        });

        $('.filter-checkbox').on('change', function () {
            applyFilters();
        });
         // Custom Parsley validator to ensure at least one of the fields is filled
        Parsley.addValidator('oneorother', {
            requirementType: 'string',
            validateString: function (value, otherSelector) {
                const otherVal = $(otherSelector).val();
                return value.trim() !== '' || otherVal.trim() !== '';
            },
            messages: {
                en: 'Enter either percentage or fixed amount.'
            }
        });

        let form1 = $("#initiate-promotion").parsley(); // Initialize Parsley

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            startDate: new Date() // Disables all past dates

        });

        $('.select2t-none').select2();

        function parseSalary(value) {
            return parseFloat(value.replace(/,/g, '')) || 0;
        }

        $('#salary_inc').on('input', function () {
            const perc = parseFloat($(this).val());
            const basicSalary = parseSalary($('#basic_salary').text());

            if (!isNaN(perc) && basicSalary > 0) {
                const calcAmount = ((perc / 100) * basicSalary).toFixed(2);
                $('#salary_amt')
                    .val(calcAmount)
                    .prop('readonly', true);

                const new_basic_salary = (parseFloat(calcAmount) + basicSalary).toFixed(2);
                $('#new_basic_salary').text(new_basic_salary);
                $('#hdn_new_basic_salary').val(new_basic_salary);

            } else {
                $('#salary_amt')
                    .val('')
                    .prop('readonly', false);
                $('#new_basic_salary').text('');
                $('#hdn_new_basic_salary').val();

            }
        });

        $('#salary_amt').on('input', function () {
            const amt = parseFloat($(this).val());
            const basicSalary = parseSalary($('#basic_salary').text());

            if (!isNaN(amt) && basicSalary > 0) {
                const calcPerc = ((amt / basicSalary) * 100).toFixed(2);
                $('#salary_inc')
                    .val(calcPerc)
                    .prop('readonly', true);

                const new_basic_salary = (amt + basicSalary).toFixed(2);
                $('#new_basic_salary').text(new_basic_salary);
                $('#hdn_new_basic_salary').val(new_basic_salary);

            } else {
                $('#salary_inc')
                    .val('')
                    .prop('readonly', false);
                $('#new_basic_salary').text('');
                $('#hdn_new_basic_salary').val();

            }
        });

        $('#initiate-promotion').on('submit', function (e) {
            e.preventDefault();
                  
            if (!form1.isValid()) {
                form1.validate();
                return false;
            }   

            const form = $(this);

            $.ajax({
                url: "{{ route('promotion.submit') }}", // Define this route in your web.php or controller
                type: "POST",
                data: form.serialize(),
                beforeSend: function () {
                    // Optional: disable submit button, show loader, etc.
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = response.redirect_url;
                        }, 2000);
                        // toastr.success("Promotion submitted successfully.");
                        form[0].reset();

                    } else {
                        toastr.error(response.message || "Something went wrong.", "Error" ,{
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error("Server error. Please try again.", "Error" ,{
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

    });

    function getEmpDetails(empId) {
        if (!empId) return;

        $.ajax({
            url: "{{ route('employee.get.details') }}",
            type: "GET",
            data: { employee_id: empId },
            success: function (response) {
                if (response.success) {
                    $('#empDetails').empty();

                    const html = `
                        <div class="position-relative me-lg-4 me-md-3 me-2">
                            <div class="img-circle userImg-block">
                                <img src="${response.data.profile_picture}" alt="user">
                            </div>
                        </div>
                        <div>
                            <h4 class="fw-600">${response.data.full_name} 
                                <span class="badge badge-themeNew">#${response.data.emp_id}</span>
                            </h4>
                            <p>${response.data.department} - ${response.data.position}</p>
                        </div>`;
                    $('#empDetails').append(html);

                    $('#hired-date').text(response.data.hired_date);
                    $('#basic_salary').text(response.data.basic_salary);
                    $('#hdn_old_basic_salary').val(response.data.basic_salary);
                    $('#last-promotion-date').text(response.data.last_promotion_date || 'N/A');
                    $('#old_position_id').val(response.data.pos_id);
                    // ✅ Set position ID on the View Job Description link
                    
                    if (response.data.job_desc_url) {
                        $('#job-description-link')
                            .attr('href', response.data.job_desc_url)
                            .attr('target', '_blank')
                            .show();
                    } else {
                        $('#job-description-link')
                            .removeAttr('href')
                            .removeAttr('target')
                            .hide(); // Or disable with .addClass('disabled') or similar
                    }
                    if (response.data.benefit_grid_url) {
                        $('#view-benifit-grid')
                            .attr('href', response.data.benefit_grid_url)
                            .attr('target', '_blank')
                            .show();
                    } else {
                        $('#view-benifit-grid')
                            .removeAttr('href')
                            .removeAttr('target')
                            .hide(); // Or disable with .addClass('disabled') or similar
                    }
                } else {
                    toastr.error("Employee details not found!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function () {
                toastr.error("Error fetching employee details.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    }

    function getDetails(posId){
        if (!posId) return;

        $.ajax({
            url: "{{ route('position.get.details') }}",
            type: "GET",
            data: { position_id: posId },
            success: function (response) {
                if (response.success) {       
                    const gridLevel = response.data.benefit_grid_level;

                    console.log(gridLevel);

                    // Set value in Select2 dropdown
                    if (gridLevel) {
                        $('#benefit_grid').val(gridLevel).trigger('change');
                        $('#level').val(gridLevel).trigger('change');
                    }
                    if (response.data.job_desc_url) {
                        $('#new-job-description-link')
                            .attr('href', response.data.job_desc_url)
                            .attr('target', '_blank')
                            .show();
                    } else {
                        $('#new-job-description-link')
                            .removeAttr('href')
                            .removeAttr('target')
                            .hide(); // Or disable with .addClass('disabled') or similar
                    }
                   
                } else {
                    toastr.error("Employee details not found!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function () {
                toastr.error("Error fetching employee details.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    }

    function applyFilters() {
        const filters = [];

        $('.filter-checkbox:checked').each(function () {
            filters.push($(this).data('filter'));
        });

        const $dropdown = $('#select_employee');

        // Restore all options first
        $dropdown.html('');
        Object.values(allOptions).forEach(opt => {
            $dropdown.append(opt.clone());
        });

        if (filters.length > 0) {
            $.ajax({
                url: '{{ route("resort.promotion.getFilteredEmployees") }}', // define this route
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    filters: filters
                },
                success: function (response) {
                    // response should be array of employee IDs to exclude
                    response.exclude_ids.forEach(id => {
                        $dropdown.find(`option[value="${id}"]`).remove();
                    });
                },
                error: function (err) {
                    console.error('Error fetching filtered employees', err);
                }
            });
        }
    }
</script>
@endsection