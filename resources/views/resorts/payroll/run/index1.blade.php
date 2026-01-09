@extends('resorts.layouts.app')
@section('page_tab_title' ,"Payroll Config")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Payroll</span>
                            <h1>Run Payroll</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card overflow-hidden">
                    <form id="msform" name="formStore" method="POST"  class="runPayroll-form" enctype="multipart/form-data">
                        <!-- progressbar -->
                        <div class="progressbar-wrapper">
                            <ul id="progressbar"
                                class="progressbar-tab d-flex justify-content-between align-items-center w-100">
                                <li class="active current"> <span>Payroll Period Selection</span></li>
                                <li><span>Employee Selection</span></li>
                                <li><span>Time & Attendance</span></li>
                                <li><span>Service Charge Distribution</span></li>
                                <li><span>Deductions</span></li>
                                <li><span>Review</span></li>
                                <!-- <li><span>Statistics</span></li> -->
                                <li><span>Payroll Confirmation</span></li>
                            </ul>
                        </div>
                        <hr>
                        <input type="hidden" id="hiddenFormData" name="formData">

                        <fieldset data-step="1">
                            <div class="text-center mt-md-4 mt-2 pt-xl-2">
                                <div class="mb-md-5 pb-md-4 mb-3">
                                    <h4 class="fw-600">Select Payroll Period</h4>
                                </div>
                               
                                <div class="row justify-content-center">
                                    <div class="col-xxl-7 col-xl-8 col-lg-10 col-md-12">
                                        <div class="bg-themeGrayLight payrollPeriod-block mb-3">
                                            <div class="text-start mb-md-5 mb-4">
                                                <div class="dateRangeAb datepicker"  id="datapicker">
                                                    <div>
                                                        <!-- Hidden input field to attach the calendar to -->
                                                        <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput">
                                                    </div>
                                                    <p id="startDate" class="d-none">Start Date:</p>
                                                    <p id="endDate" class="d-none">End Date:</p>
                                                </div>                        
                                            </div>
                                            <button type="button" class="btn btn-themeBlue btn-sm next">   Continue</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none d-md-block" style="height:185px;"></div>
                            </div>
                        </fieldset>
                        <fieldset data-step="2">
                            <div class="card-header">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col-auto">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Employee Selection</h3>
                                            <p>Select Employee to include in this payroll</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Filters -->
                                    <div class="row g-2 mt-2">
                                        <div class="col-xl-3 col-lg-5 col-md-3 col-sm-8 ms-auto">
                                            <div class="input-group">
                                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                                <i class="fa-solid fa-search"></i>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <select id="departmentFilter" class="form-select select2t-none">
                                                <option value="">All Departments</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select id="sectionFilter" class="form-select select2t-none">
                                                <option value="">All Sections</option>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select  id="positionFilter" class="form-select select2t-none">
                                                <option value="">All Positions</option>
                                                <!-- Example: populate dynamically or statically -->
                                                @foreach($positions as $position)
                                                    <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <table id="payroll-employees" class="table table-empSelection ">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="form-check no-label">
                                                    <input class="form-check-input" type="checkbox" id="select-all" value="">
                                                </div>
                                            </th>
                                            <th>Employee ID </th>
                                            <th>Employee </th>
                                            <th>Positions </th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Payment Method</th>
                                        </tr>
                                    </thead>
                                   <tbody>

                                   </tbody>
                                </table>
                            </div>
                            <div class="bg-themeGrayLight emp-select">
                                <div class="row g-3">
                                    <div class="col-auto">
                                        <p class="fw-600" id="selectedCount">0 Employees Selected</p>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="a-linkTheme" id="unselectAll">Unselect All</a>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr-footer">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="3">
                            <div class="card-header">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col-auto">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Time & Attendance Data</h3>
                                            <p>Review Employees’ Time and Attendance Records</p>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-5 col-md-5 col-sm-8 ms-auto">
                                        <div class="input-group">
                                            <input type="search" class="form-control " placeholder="Search" />
                                            <i class="fa-solid fa-search"></i>
                                        </div>
                                    </div>
                                    <!-- <div class="col-auto">
                                        <a href="#" class="a-link">Show History</a>
                                    </div> -->
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="table-timeAttendance" class="table table-timeAttendance table-payroll-attendance  w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name </th>
                                            <th>Department </th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Leave Types</th>
                                            <th>Regular OT Hours</th>
                                            <th>Holiday OT Hours</th>
                                            <th>Total OT</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div>
                            <hr class="hr-footer border-0">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="4">
                            <div class="card-header">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Service Charge Distribution</h3>
                                            <p>Distribute the service charge amount among employees</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="total-ser" class="form-label">TOTAL SERVICE CHARGE AMOUNT</label>
                                <div class="row g-md-4 g-2">
                                    <div class="col-lg-6 col-md-8 col-sm">
                                        <input type="text" class="form-control" id="total-ser" placeholder="ENTER TOTAL SERVICE CHARGE AMOUNT" required>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" id="distribute-service-charge" class="btn btn-themeSkyblue">Distribute Amount</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="table-serviceCharge" class="table  table-serviceCharge w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name </th>
                                            <th>Positions </th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Total Working Days</th>
                                            <th>Service Charge</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="6" class="text-end">Total Service Charge:</th>
                                            <th colspan="1" id="total-service-charge" class="fw-bold">$0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="d-none d-md-block" style="height: 136px;"></div>
                            <hr class="hr-footer">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="5">
                            <div class="card-header">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col-auto">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Deductions</h3>
                                            <p>Review and Add Salary Deductions</p>
                                        </div>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <a id="download-city-ledger-template" href="#" class="a-link">Download City ledger Template</a>
                                    </div>
                                    <div class="col-auto">
                                        <div class="uploadFile-btn me-0">
                                            <a href="javascript:void(0)" id="upload-city-ledger-button" class="btn btn-themeBlue btn-sm"
                                                >
                                                Upload Excel
                                            </a>
                                            <input type="file" id="upload-city-ledger" name="UploadCityLadger"
                                                accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm" style="opacity: 0; position: absolute; z-index: -1;">
                                            <div id="fileNamecityladgerFile" style="margin-top: 10px; color: #333;"></div>
                                        </div>
                                    </div>
                                    <!-- <div class="col-auto">
                                        <a href="#" class="btn btn-themeSkyblue">Submit</a>
                                    </div> -->
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="table-deductions" class="table table-deductions  w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name</th>
                                            <th>Department </th>
                                            <th>Attendance</th>
                                            <th>City Ledger</th>
                                            <th>Staff Shop</th>
                                            <th>Pension</th>
                                            <th>EWT</th>
                                            <th>Other</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                            <hr class="hr-footer border-0">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="6">
                            <div class="card-header mb-2">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Review</h3>
                                            <p>Review the final payroll data</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="table-review" class="table table-review   w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th colspan="4"></th>
                                            <th colspan="3">Time & Attendance</th>
                                            <th colspan="3">Overtime</th>
                                            <th colspan="3">Earnings</th>
                                        </tr>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name </th>
                                            <th>Department </th>
                                            <th>Position</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Service Charge</th>
                                            <th>Normal</th>
                                            <th>Holiday</th>
                                            <th>Total</th>
                                            <th>Basic</th>
                                            <th>Allowance</th>
                                            <th>Normal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                                                                
                                    </tbody>
                                </table>
                            </div>
                            <hr class="hr-footer border-0">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                        <fieldset data-step="7">
                            <div class="card-header">
                                <div class="row justify-content-start align-items-center g-2">
                                    <div class="col">
                                        <div class="card-title m-0 p-0 border-0">
                                            <h3>Payroll Confirmation</h3>
                                            <p>Confirm the payroll summary before locking</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mb-3">
                                <div class="col-lg-6">
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Payroll</h6>
                                        <div class="value" id="total_payroll_amount"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Employees</h6>
                                        <div class="value" id="total_employees"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Payroll Draft Date</h6>
                                        <div class="value" id="payroll-darft-date"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Payroll Payment Date</h6>
                                        <div class="value" id="payroll-payment-date"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="bg-themeGrayLight payrollConfStep-block">
                                        <div class=" card-title mb-md-4">
                                            <h3>Steps Completed</h3>
                                        </div>
                                        <div class="row g-md-4 g-2">
                                            <div class="col-xxl-6 col-xl-7 col-lg-12 col-sm-6">
                                                <ul class="listing-wrapper">
                                                    <li>Payroll Period Selection</li>
                                                    <li>Employee Selection</li>
                                                    <li>Time & Attendance Data</li>
                                                    <li>Service Charge Distribution</li>
                                                </ul>
                                            </div>
                                            <div class="col-xxl-6 col-xl-5 col-lg-12 col-sm-6">
                                                <ul class="listing-wrapper ">
                                                    <li>Deductions</li>
                                                    <li>Review</li>
                                                    <!-- <li>Statistics</li> -->
                                                    <li>Payroll Confirmation</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="hr-footer border-0">
                            <a href=" # " class="a-link ">Save As Draft</a>
                            <button type="submit" class="btn btn-themeBlue btn-sm float-end mb-1" style="margin-right: 10px;" id="submit">Confirm and Lock Payroll</button>
                            <a href=" # " class=" btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addDeduction-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-lanTest">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addDeductionForm">
                    @csrf
                    <div class="modal-body">
                        <!-- Select Employee (Auto-filled from data-emp-id) -->
                        <div class="mb-3">
                            <label for="select_emp" class="form-label">SELECT EMPLOYEE</label>
                            <select class="form-select select2t-none" id="select_emp" aria-label="Default select example">
                                <option selected>Select</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->Emp_id }}">{{ $employee->resortAdmin->first_name}} {{ $employee->resortAdmin->last_name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Deduction Type -->
                        <div class="mb-3">
                            <label for="deductionFor" class="form-label">DEDUCTION FOR?</label>
                            <select class="form-select select2t-none" id="deductionFor" name="deductionFor">
                                <option value="">Select Deduction For</option>
                                @foreach($deductions as $deduction)
                                    <option value="{{ $deduction->id }}" data-unit="{{ $deduction->currency }}">{{ $deduction->deduction_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Amount & Deduction Unit (Auto-filled) -->
                        <div class="mb-3">
                            <label for="amount" class="form-label">AMOUNT</label>
                            <input type="text" class="form-control" id="amount" placeholder="Amount">
                        </div>

                        <div class="mb-3">
                            <label for="amount_unit" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="amount_unit" placeholder="Currency" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue" id="submitDeduction">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
     <!-- Modal HTML -->
    <div id="addnoteModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="addNote" name="add_note" class="form-control" rows="3" placeholder="Add Note (optional)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submit_note" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .dateRangeAb{position: relative;}
    .dateRangeAb .daterangepicker {
        position: absolute !important;
        background-color: #fff;
        width: 100%;
        /* min-width: 350px; */
    }
    .dateRangeAb .form-control {
        background-image: url('{{ URL::asset("resorts_assets/images/calendar.svg") }}');
        background-position: right 10px center;
        background-repeat: no-repeat;
    }
</style>
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript">
    var cityLedgerData = {};
    var otherData = {};
    var selectedCurrency = "{{$currency}}"; 
    let distributedServiceCharge = []; // Store data in memory
    var selectedEmployees = [];
    var currency = "{{$currency}}";
    //  console.log(currency);
    $(document).ready(function () {
        // Ensure Parsley is loaded
        let  resortid ="{{  Auth::guard('resort-admin')->user()->resort_id }}";
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }
        // Initialize the datepickers

        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: moment().subtract(1, 'months').startOf('month'),  // First day of last month
            endDate: moment().subtract(1, 'months').endOf('month'),
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: true,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY", // Ensure the format matches your date parsing logic
            }
        });

        $("#hiddenInput").on('apply.daterangepicker', function (ev, picker) {
            $("#startDate").text("Start Date: " + picker.startDate.format("DD-MM-YYYY"));
            $("#endDate").text("End Date: " + picker.endDate.format("DD-MM-YYYY"));
        });
        $(".select2t-none").select2();

        $(".next").click(async function (e) {
            e.preventDefault();

            var $currentFieldset = $(this).closest("fieldset");
            var currentStep = $currentFieldset.data('step');

            if (currentStep === 1) {
                
            }

            // Step 2: Validate employee selection and fetch attendance data
            if (currentStep === 2) {
                var dateRange = $("#hiddenInput").val();
                var dates = dateRange.split(' - ');
                var startDate = moment(dates[0], "DD-MM-YYYY", true);
                var endDate = moment(dates[1], "DD-MM-YYYY", true);

                var selectedEmployees = [];
                $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
                    selectedEmployees.push($(this).val()); // Get selected employee IDs
                });

                if (selectedEmployees.length === 0) {
                    toastr.error("Please select at least one employee before proceeding.", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Fetch attendance details for selected employees
                $.ajax({
                    url: '{{ route("fetch.time.attendance") }}',
                    method: 'POST',
                    data: { 
                        employees: selectedEmployees,
                        startDate: startDate.format("YYYY-MM-DD"), 
                        endDate: endDate.format("YYYY-MM-DD"),
                        _token: '{{ csrf_token() }}' 
                    }, // CSRF token required
                    success: function (response) {
                        if (response.success) {
                            var $tableBody = $("#table-timeAttendance tbody");
                            
                            // ✅ Destroy DataTable before updating (to prevent duplication)
                            if ($.fn.DataTable.isDataTable("#table-timeAttendance")) {
                                $("#table-timeAttendance").DataTable().destroy();
                            }

                            $tableBody.empty(); // Clear existing rows

                            response.data.forEach(function (employee) {
                                var row = `<tr>
                                    <td>${employee.employee_id}</td>
                                    <td>
                                        <div class="tableUser-block">
                                            <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                            <span>${employee.name}</span>
                                        </div>
                                    </td>
                                    <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
                                    <td>${employee.present}</td>
                                    <td>${employee.absent}</td>
                                    <td>${employee.leave_types}</td>
                                    <td>${employee.regular_ot}</td>
                                    <td>${employee.holiday_ot}</td>
                                    <td>${employee.total_ot}</td>
                                    <td>
                                        <a href="#" class="btn-lg-icon icon-bg-skyblue edit-btn"><i class="fa-regular fa-pen"></i></a>
                                        <a href="#" class="a-link add_note" data-attendance-id=${employee.attendance_id}>+ Add Note</a>
                                    </td>
                                </tr>`;
                                $tableBody.append(row);
                            });

                            $("#table-timeAttendance").DataTable({
                                responsive: true,
                                paging: false,
                                searching: false,
                                ordering: true,
                                autoWidth: false,
                                pageLength: 10
                            });
                            updatePageLength() ;
                            moveToNextStep($currentFieldset);
                        } else {
                            toastr.error(response.message, { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function () {
                        toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
                    }
                });

                return;
            }

            // Step 3: Validate employee selection and service charge distribution
            if (currentStep === 3) {
                var dateRange = $("#hiddenInput").val();
                var dates = dateRange.split(' - ');
                var startDate = moment(dates[0], "DD-MM-YYYY", true);
                var endDate = moment(dates[1], "DD-MM-YYYY", true);

                var selectedEmployees = [];
                $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
                    selectedEmployees.push($(this).val()); // Get selected employee IDs
                });

                if (selectedEmployees.length === 0) {
                    toastr.error("Please select at least one employee before proceeding.", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Fetch attendance details for selected employees
                $.ajax({
                    url: '{{ route("fetch.time.attendance") }}',
                    method: 'POST',
                    data: { 
                        employees: selectedEmployees,
                        startDate: startDate.format("YYYY-MM-DD"), 
                        endDate: endDate.format("YYYY-MM-DD"),
                        _token: '{{ csrf_token() }}' 
                    }, // CSRF token required
                    success: function (response) {
                        if (response.success) {
                            var $tableBody = $("#table-serviceCharge tbody");
                            if ($.fn.DataTable.isDataTable("#table-serviceCharge")) {
                                $("#table-serviceCharge").DataTable().destroy();
                            }
                            $tableBody.empty(); // Clear existing rows
                            response.data.forEach(function (employee) {
                                var row = `<tr>
                                    <td>${employee.employee_id}</td>
                                    <td>
                                        <div class="tableUser-block">
                                            <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                            <span>${employee.name}</span>
                                        </div>
                                    </td>
                                    <td>${employee.position} <span class="badge badge-themeLight">${employee.position_code}</span></td>
                                    <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
                                    <td>${employee.section}</td>
                                    <td class="workdays">${employee.workdays}</td>
                                    <td class="service-charge">0.00</td>
                                </tr>`;
                                $tableBody.append(row);
                            });

                            $("#table-serviceCharge").DataTable({
                                responsive: true,
                                paging: false,
                                searching: false,
                                ordering: true,
                                autoWidth: false,
                                pageLength: 10
                            });
                            updatePageLength() ;
                            moveToNextStep($currentFieldset);
                        } else {
                            toastr.error(response.message, { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function () {
                        toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
                    }
                });

                return;
            }

            if (currentStep === 4) {
                var totalServiceCharge = parseFloat($("#total-ser").val()); // Get service charge amount

                if (isNaN(totalServiceCharge) || totalServiceCharge <= 0) {
                    toastr.error("Please enter a valid service charge amount.", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }
                
                // Load currency rates before moving to the next step
                loadCurrencyRates(resortid, currency, currentStep).then(() => {
                    moveToNextStep($currentFieldset);
                }).catch(error => {
                    console.error("Error loading currency rates:", error);
                    toastr.error("Failed to load currency rates.", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }

            if (currentStep === 5) {
                loadCurrencyRates(resortid,currency,currentStep);
                moveToNextStep($currentFieldset);
            }

            if (currentStep === 6) {
                let currentDate = moment(); // Use Moment.js for consistency
                let formattedDraftDate = currentDate.format("DD-MMM-YYYY"); // Format as "DD-MMM-YYYY"

                var dateRange = $("#hiddenInput").val();
                var dates = dateRange.split(' - ');

                // Parse Start and End Dates
                var startDate = moment(dates[0], "DD-MM-YYYY", true);
                var endDate = moment(dates[1], "DD-MM-YYYY", true);

                // Get Next Month's First Date
                var nextMonthFirstDate = moment(endDate).add(1, "month").startOf("month");

                // Format Payment Date (e.g., "01-Mar-2025")
                let formattedPaymentDate = nextMonthFirstDate.format("DD-MMM-YYYY");

                console.log("Payment Date:", formattedPaymentDate);
                console.log("Draft Date:", formattedDraftDate);


                var selectedEmployees = [];
                $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
                    selectedEmployees.push($(this).val()); // Get selected employee IDs
                });

                let formData = collectAllStepData();  // Collect the form data
                let totalPayrollAmount = calculateTotalPayrollAmount(formData);

                payrollData = {
                    totalPayroll: totalPayrollAmount,
                    totalEmployees: selectedEmployees.length,
                    draftDate: formattedDraftDate,
                    paymentDate: formattedPaymentDate,
                };

                loadCurrencyRates(resortid, currency, currentStep).then(() => {
                    moveToNextStep($currentFieldset);
                    updatePayrollConfirmation(payrollData);
                });
            }

            // Move to the next step for other cases
            moveToNextStep($currentFieldset);
        });

        // Function to transition to the next step
        function moveToNextStep($currentFieldset) {
            var $nextFieldset = $currentFieldset.next("fieldset");

            if ($nextFieldset.length > 0) {
                $("#progressbar li").eq($("fieldset").index($currentFieldset)).removeClass("current");
                $("#progressbar li").eq($("fieldset").index($nextFieldset)).addClass("active current");

                $currentFieldset.animate({ opacity: 0 }, {
                    duration: 500,
                    step: function (now) {
                        var opacity = 1 - now;
                        $currentFieldset.css({ 'opacity': opacity });
                    },
                    complete: function () {
                        $currentFieldset.css({ 'visibility': 'hidden', 'position': 'absolute' });
                        $nextFieldset.css({ 'visibility': 'visible', 'opacity': 1, 'position': 'relative' });
                    }
                });
            }
        }

        async function loadCurrencyRates(resortId,currency,currentStep) {
            var rates = await fetchRates(resortId); // ✅ Wait for response
            var updatedCurrency = currency;
            console.log(updatedCurrency);

            var conversionRate = updatedCurrency === 'Dollar' ? rates.usd_to_mvr : rates.mvr_to_usd;
            // console.log(conversionRate);
            if(currentStep == 4)
            {
                GetFifthStep(updatedCurrency, conversionRate);
            }
            
            else if(currentStep == 5)
            {
                GetSixthStep(updatedCurrency, conversionRate);
            }
        }
        
        // distribute service charges

        $("#distribute-service-charge").click(function (e) {
            e.preventDefault();

            // Get the total service charge amount
            var totalServiceCharge = parseFloat($("#total-ser").val().replace('$', '').replace(',', ''));

            if (isNaN(totalServiceCharge) || totalServiceCharge <= 0) {
                toastr.error("Please enter a valid service charge amount.", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Calculate total workdays of all employees
            var totalWorkdays = 0;
            $("#table-serviceCharge tbody tr").each(function () {

                var workdays = parseFloat($(this).find(".workdays").text());
                totalWorkdays += workdays;
            });

            // Reset the distributedServiceCharge array before distributing
            distributedServiceCharge = [];
            // Distribute service charge based on workdays and calculate the total assigned amount
            var distributedTotal = 0;
            $("#table-serviceCharge tbody tr").each(function () {
                // var employeeId = $(this).data("employee-id"); // Get employee ID
                var $row = $(this);
                var employeeId = $row.find("td:eq(0)").text();
                var workdays = parseFloat($(this).find(".workdays").text());
                var employeeShare = (totalServiceCharge / totalWorkdays) * workdays;
                $(this).find(".service-charge").text(employeeShare.toFixed(2));
                distributedTotal += employeeShare; // Summing up the total assigned amount

                 // Store data in memory (array)
                distributedServiceCharge.push({
                    id: employeeId,
                    amount: employeeShare.toFixed(2)
                });
            });

            // Update the total service charge row
            $("#total-service-charge").text(`$${distributedTotal.toFixed(2)}`);
            // console.log("Distributed Service Charge Data:", distributedServiceCharge); // Debugging

        });

        $("#download-city-ledger-template").click(function (e) {
            e.preventDefault();

            // Fetch selected employees
            var selectedEmployees = [];
            $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
                var employeeId = $(this).closest("tr").find("td:eq(1)").text();
                var employeeName = $(this).closest("tr").find("td:eq(2)").text();
                selectedEmployees.push({ id: employeeId, name: employeeName });
            });

            if (selectedEmployees.length === 0) {
                toastr.error("Please select at least one employee before downloading the template.", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Create Excel template
            var workbook = XLSX.utils.book_new();
            var worksheetData = [["Employee ID", "Employee Name", "City Ledger Amount"]];
            selectedEmployees.forEach(function (employee) {
                worksheetData.push([employee.id, employee.name, ""]);
            });

            var worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
            XLSX.utils.book_append_sheet(workbook, worksheet, "City Ledger");

            // Download the file
            XLSX.writeFile(workbook, "City_Ledger_Template.xlsx");
        });

        $("#upload-city-ledger-button").click(function () {
            $("#upload-city-ledger").click();
        });
    
        // Function to handle file upload
        $("#upload-city-ledger").change(async function (e) {
            var file = e.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = async function (e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var worksheet = workbook.Sheets[workbook.SheetNames[0]];
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                jsonData.slice(1).forEach(function (row) {
                    var employeeId = row[0];
                    var cityLedgerUSD = parseFloat(row[2]) || 0; // City Ledger in USD
                    // console.log(cityLedgerUSD);
                    // Store original USD value in global object
                    cityLedgerData[employeeId] = cityLedgerUSD;

                    // Convert based on selected currency
                    var cityLedgerFinal = (selectedCurrency === "MVR") 
                        ? cityLedgerUSD * conversionRate 
                        : cityLedgerUSD; 

                    // console.log(conversionRate,cityLedgerFinal);

                    $("#table-deductions tbody tr").each(function () {
                        var $row = $(this);
                        var currencySymbol = (selectedCurrency === 'Dollar') ? '$' : 'MVR ';
                        var cityLedgerFinal1 = currencySymbol + cityLedgerFinal;
                        // console.log(cityLedgerFinal1,"cityLedgerFinal");
                        if ($row.find("td:eq(0)").text() === employeeId) {
                            $row.find("td:eq(4)").text(cityLedgerFinal1); // Update displayed value
                            updateTotal($row);
                        }
                    });
                });
            };
            reader.readAsArrayBuffer(file);
        });

        $("#addDeductionForm").submit(async function (e) {
            e.preventDefault();

            var employeeId = $("#select_emp").val();
            var deductionAmount = parseFloat($("#amount").val()) || 0;
            var deductionUnit = $("#amount_unit").val(); // Get selected currency (MVR or USD)
            var rates = await fetchRates(resortid);
            var amountInUSD = deductionUnit === "Rufiyaa" ? (deductionAmount * rates.mvr_to_usd ) : deductionAmount;

            otherData[employeeId] = amountInUSD;

            var finalAmount = (selectedCurrency === "MVR") 
                        ? amountInUSD * usd_to_mvr  
                        : amountInUSD; 
            // Convert to USD if needed

            $("#table-deductions tbody tr").each(function () {
                var $row = $(this);
                var currencySymbol = (selectedCurrency === 'Dollar') ? '$' : 'MVR ';

                if ($row.find("td:eq(0)").text() === employeeId) {
                    var currentOther = parseFloat($row.find("td:eq(8)").text().replace('$', '')) || 0;
                    var newOther = currentOther + finalAmount;
                    $row.find("td:eq(8)").text(currencySymbol + newOther.toFixed(2)); // Store in USD
                    updateTotal($row); // Update the total column
                }
            });
            $('#addDeductionForm')[0].reset(); // Correct way

            $("#addDeduction-modal").modal("hide");
        });

        $('#addDeduction-modal').on('hidden.bs.modal', function () {
            $(this).removeAttr('aria-hidden'); // Ensure the modal isn't hidden from screen readers
            $('#addDeductionForm')[0].reset(); // Reset form fields properly
            $('.add-deduction-btn:first').focus(); // Move focus to a valid element
        });

        $('#addDeduction-modal').on('shown.bs.modal', function () {
            $('#select_emp').focus(); // Focus on the first field in the modal when opened
        });

        // When a deduction type is selected, update the unit field
        $('#deductionFor').on('change', function () {
            let selectedOption = $(this).find(':selected');
            let deductionUnit = selectedOption.data('unit'); // Get unit from selected deduction
            $('#amount_unit').val(deductionUnit); // Set unit in the input field
        });

        $(".previous").click(function () {
            var $currentFieldset = $(this).closest("fieldset");
            var $previousFieldset = $currentFieldset.prev("fieldset");

            if ($previousFieldset.length === 0) return; // Stop if there is no previous step

            var step = $previousFieldset.data('step'); // Step identifier for the previous fieldset

            // Fetch draft data for the step via AJAX
            $.ajax({
                url: '{{ route("get.payroll.draft") }}',
                method: 'POST',
                data: { step: step },
                success: function (response) {
                    if (response.success) {
                        // Populate the previous fieldset with retrieved data
                        for (const [key, value] of Object.entries(response.data)) {
                            const input = $previousFieldset.find(`[name="${key}"]`);
                            if (input.length) {  
                                input.val(value);
                            }
                        }
                    }

                    // Update progress bar
                    var index = $("fieldset").index($currentFieldset);
                    $("#progressbar li").eq(index).removeClass("current active");
                    $("#progressbar li").eq(index - 1).addClass("current");

                    // Animate transition
                    $currentFieldset.animate({ opacity: 0 }, {
                        duration: 500,
                        step: function (now) {
                            var opacity = 1 - now;
                            $currentFieldset.css({ opacity: opacity });
                        },
                        complete: function () {
                            $currentFieldset.css({ visibility: 'hidden', position: 'absolute' });
                            $previousFieldset.css({ visibility: 'visible', opacity: 1, position: 'relative' });
                        }
                    });
                },
                error: function () {
                    alert('Error retrieving draft data.');
                }
            });
        });
   
        // When any checkbox is changed, update the count
        $('#payroll-employees tbody').on('change', 'input[type="checkbox"]', function(){
            updateSelectedCount();
        });

        // "Unselect All" link click handler
        $('#unselectAll').on('click', function(e){
            e.preventDefault();
            $('#select-all').prop('checked', false);
            updateSelectedCount();
            employeeList();
        });

        // "Select All" checkbox functionality (optional)
        $('#select-all').on('change', function(){
            var isChecked = $(this).is(':checked');
            $('#payroll-employees tbody input[type="checkbox"]').prop('checked', isChecked);
            updateSelectedCount();
            employeeList();
            updatePageLength() ;
        });
        // Initialize count on page load in case some checkboxes are pre-checked
        updateSelectedCount();
        employeeList();
        // Filter change event
        $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function () {
            employeeList();
        });

        $('#table-timeAttendance').on('click', '.add_note', function () {
            attendance_id = $(this).data('attendance-id');
            // Show the rejection modal
            $('#addnoteModal').modal('show');
        });

        $('#msform').on('submit', function (e) {
            // Prevent default form submission
            e.preventDefault();

            // Collect all step data and store in hidden input
            let collectedData = collectAllStepData();
            $("#hiddenFormData").val(JSON.stringify(collectedData));

            // console.log("Final Payroll Data:", collectedData);

            // Validate the form using Parsley.js
            const form = $(this);
            
            // Create FormData object
            var formData = new FormData(this);
            // console.log("Submitting FormData:", formData);

            // Disable submit button and show loader
            $('#submit')
                .prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

            // AJAX Submission
            $.ajax({
                url: '{{ route("payroll.store") }}', // Your Laravel route
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Ensures CSRF protection
                },
                success: function (response) {
                    console.log("Success Response:", response);

                    // Show success message
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    // Optional: Redirect to a confirmation page or refresh
                    setTimeout(function () {
                        window.location.href = response.redirect_url;
                    }, 2000);
                },
                error: function (xhr) {
                    console.error("Error Response:", xhr);

                    let errorMessage = 'An error occurred while submitting payroll.';

                    // Check if Laravel validation errors exist
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    // Show error message
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });

                    // Re-enable the submit button
                    $('#submit')
                        .prop('disabled', false)
                        .html('Submit Payroll');
                },
                complete: function () {
                    // Ensure submit button is enabled even if AJAX completes unexpectedly
                    $('#submit')
                        .prop('disabled', false)
                        .html('Submit Payroll');
                }
            });
        });

    });

    function updatePayrollConfirmation(payrollData) {
        $("#total_payroll_amount").text(payrollData.totalPayroll);
        $("#total_employees").text(payrollData.totalEmployees);
        $("#payroll-darft-date").text(payrollData.draftDate);
        $("#payroll-payment-date").text(payrollData.paymentDate);
    }

    async function fetchRates(resortId) {
        // alert(resortId);
        try {
            const response = await $.ajax({
                url: "{{ route('getCurrencyRates', ['resortId' => '__ID__']) }}".replace('__ID__', resortId),
                method: 'GET',
                dataType: 'json'
            });
            return response;
        } catch (error) {
            console.error('Error fetching conversion rates:', error);
            return { usd_to_mvr: 15.42, mvr_to_usd: 0.065 }; // Default values if error occurs
        }
    }

    function updateSelectedCount(){
        var count = $('#payroll-employees tbody input[type="checkbox"]:checked').length;
        // $('#selectedCount').text(count + " Employees Selected");
    }

    function GetFifthStep(currency , conversionRate = 1) {
        console.log(currency,conversionRate);

        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = [];

        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", { positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: '{{ route("fetch.time.attendance") }}',
            method: 'POST',
            data: { 
                employees: selectedEmployees,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                currency : currency, 
                conversionRate : conversionRate, // Send currency in request
                _token: '{{ csrf_token() }}' 
            },
            success: function (response) {
                if (response.success) {
                    var $tableBody = $("#table-deductions tbody");
                    if ($.fn.DataTable.isDataTable("#table-deductions")) {
                        $("#table-deductions").DataTable().destroy();
                    }

                    $tableBody.empty();

                    response.data.forEach(function (employee) {
                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR '; // Adjust currency symbol

                        var row = `<tr>
                            <td>${employee.employee_id}</td>
                            <td>
                                <div class="tableUser-block">
                                    <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                    <span>${employee.name}</span>
                                </div>
                            </td>
                            <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
                            <td class="attendance">${currencySymbol}${employee.absent_deduction}</td>
                            <td class="city-ladger">${currencySymbol}0.00</td>
                            <td class="staff-shop">${currencySymbol}0.00</td>
                            <td class="pension">${currencySymbol}0.00</td>
                            <td class="ewt">${currencySymbol}0.00</td>
                            <td class="other">${currencySymbol}0.00</td>
                            <td class="total-deduction">${currencySymbol}0.00</td>
                            <td>
                               <a href="javascript:void(0)" class="btn btn-themeSkyblueLight btn-small add-deduction-btn" data-emp-id="${employee.employee_id}">
                                Add Deduction
                            </a>
                            </td>
                        </tr>`;
                        $tableBody.append(row);
                    });

                    $("#table-deductions").DataTable({
                        responsive: true,
                        paging: false,
                        searching: false,
                        ordering: true,
                        autoWidth: false,
                        pageLength: 10
                    });
                    let  resortid ="{{  Auth::guard('resort-admin')->user()->resort_id }}";
                    updatePageLength();
                    $("#table-deductions tbody tr").each(async function () {
                        var $row = $(this);
                        var employeeId = $row[0];
                        var rates = await fetchRates(resortid);

                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                        
                        var employeeId = $row.find("td:eq(0)").text();

                        if (cityLedgerData[employeeId] !== undefined) {
                            // console.log(cityLedgerData[employeeId],updatedCurrency,conversionRate1);
                            // Get stored USD value and convert based on new currency
                            var cityLedgerUSD = cityLedgerData[employeeId];
                            var cityLedgerFinal = (currency === "MVR") 
                                ? cityLedgerUSD * rates.usd_to_mvr 
                                : cityLedgerUSD;
                            // console.log($row.find("td:eq(0)").text() ,employeeId );
                            if ($row.find("td:eq(0)").text() === employeeId) {
                                $row.find("td:eq(4)").text(currencySymbol + cityLedgerFinal); // Update displayed value
                                // updateTotal($row);
                            }

                        }

                        if (otherData[employeeId] !== undefined) {
                            // console.log(cityLedgerData[employeeId],updatedCurrency,conversionRate1);
                            // Get stored USD value and convert based on new currency
                            var amountInUSD = otherData[employeeId];
                            var FinalOtherAMount = (currency === "MVR") 
                                ? amountInUSD * rates.usd_to_mvr 
                                : amountInUSD;
                            // console.log($row.find("td:eq(0)").text() ,employeeId );
                            if ($row.find("td:eq(0)").text() === employeeId) {
                                $row.find("td:eq(8)").text(currencySymbol + FinalOtherAMount); // Update displayed value
                                // updateTotal($row);
                            }

                        }
                    });
                    let serviceCharge = getServiceChargeForEmployee(employee.employee_id);
                    fetchStaffShopData(selectedEmployees, startDate, endDate, currency, conversionRate);
                    calculatePensionAndEWT(selectedEmployees, currency, conversionRate);

                } else {
                    toastr.error(response.message, { positionClass: 'toast-bottom-right' });
                }
            },
            error: function () {
                toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
            }
        });
    }

    function fetchStaffShopData(employeeIds, startDate, endDate, currency,conversionRate =1) {
        $.ajax({
            url: '{{route("payroll.fetch.staffshop")}}',
            method: 'POST',
            data: {  
                employees: employeeIds,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                currency: currency, // Pass currency
                conversionRate: conversionRate,
                _token: '{{ csrf_token() }}' 
            },
            success: function (response) {
                // console.log(response); // Debugging: Check response data
                if (response.success) {
                    response.data.forEach(function (employee) {
                        $("#table-deductions tbody tr").each(function () {
                            var $row = $(this);
                            var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                            var staffshop = currencySymbol + employee.total;
                            // console.log(staffshop);
                            
                            if ($row.find("td:eq(0)").text() === employee.Emp_id) {
                                $row.find("td:eq(5)").text(staffshop || "-");
                                updateTotal($row); // Update total column
                            }
                        });
                    });
                } else {
                    console.error("response.data is not an array:", response.data);
                }
            }
        });
    }

    function calculatePensionAndEWT(employeeIds, currency, conversionRate = 1) {
        $.ajax({
            url: '{{route("payroll.calculate.pensionandewt")}}',
            method: 'POST',
            data: {  
                employees: employeeIds,
                currency: currency, // Pass currency
                conversionRate: conversionRate,
                _token: '{{ csrf_token() }}' 
            },
            success: function (response) {
                // console.log(response); // Debugging: Check response data

                if (response.success) {
                    response.data.forEach(function (employee) {
                        $("#table-deductions tbody tr").each(function () {
                            var $row = $(this);
                            if ($row.find("td:eq(0)").text().trim() === employee.Emp_id.toString()) {
                                var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                                var pensionFormatted = currencySymbol + employee.pension.toFixed(2);
                                var ewtFormatted = currencySymbol + employee.ewt.toFixed(2);

                                $row.find("td:eq(6)").text(pensionFormatted);
                                $row.find("td:eq(7)").text(ewtFormatted);

                                updateTotal($row); // Update total column
                            }
                        });
                    });
                } else {
                    console.error("Failed to fetch Pension and EWT data.");
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
            }
        });
    }

    function updateTotal($row) {
        var currencySymbol = $row.find("td:eq(3)").text().includes('$') ? '$' : 'MVR ';
        
        var attendance = parseFloat($row.find("td:eq(3)").text().replace(currencySymbol, '')) || 0;
        var cityLedger = parseFloat($row.find("td:eq(4)").text().replace(currencySymbol, '')) || 0;
        var staffShop = parseFloat($row.find("td:eq(5)").text().replace(currencySymbol, '')) || 0;
        var pension = parseFloat($row.find("td:eq(6)").text().replace(currencySymbol, '')) || 0;
        var ewt = parseFloat($row.find("td:eq(7)").text().replace(currencySymbol, '')) || 0;
        var other = parseFloat($row.find("td:eq(8)").text().replace(currencySymbol, '')) || 0;
        // console.log(other,"22222");

        var total = attendance + cityLedger + staffShop + pension + ewt + other;
        $row.find("td:eq(9)").text(currencySymbol + total.toFixed(2));
    }

    function GetSixthStep(currency, conversionRate = 1) {
        // console.log(currency,conversionRate);

        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = [];

        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", { positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: '{{ route("fetch.time.attendance") }}',
            method: 'POST',
            data: { 
                employees: selectedEmployees,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                currency : currency, 
                conversionRate : conversionRate, // Send currency in request
                _token: '{{ csrf_token() }}' 
            },
            success: function (response) {
                if (response.success) {
                    var $tableBody = $("#table-review tbody");
                    if ($.fn.DataTable.isDataTable("#table-review")) {
                        $("#table-review").DataTable().destroy();
                    }

                    $tableBody.empty();

                    response.data.forEach(function (employee) {
                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR '; // Adjust currency symbol
                        let serviceCharge = getServiceChargeForEmployee(employee.employee_id);

                        var row = `<tr>
                                    <td>${employee.employee_id}</td>
                                     <td>
                                        <div class="tableUser-block">
                                            <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                            <span>${employee.name}</span>
                                        </div>
                                    </td>
                                    <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
                                    <td>${employee.position}</td>
                                    <td>${employee.present}</td>
                                    <td>${employee.absent}</td>
                                    <td>${serviceCharge}</td>
                                    <td>${employee.regular_ot} hrs</td>
                                    <td>${employee.holiday_ot} hrs</td>
                                    <td>${employee.total_ot}</td>
                                    <td>$${employee.basic_salary}</td>
                                    <td>$120</td>
                                    <td>$110</td>
                                </tr>`;
                        $tableBody.append(row);
                    });

                    $("#table-review").DataTable({
                        responsive: true,
                        paging: false,
                        searching: false,
                        ordering: true,
                        autoWidth: false,
                        pageLength: 10
                    });
                    updatePageLength();
                    

                } else {
                    toastr.error(response.message, { positionClass: 'toast-bottom-right' });
                }
            },
            error: function () {
                toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
            }
        });
    }

    function getServiceChargeForEmployee(employeeId) {
        let serviceChargeEntry = distributedServiceCharge.find(emp => emp.id == employeeId);
        // console.log(serviceChargeEntry,"serviceChargeEntry");
        return serviceChargeEntry ? serviceChargeEntry.amount : "0.00";
    }

    function employeeList()
    {
        let isChecked = $("#select-all").is(":checked");
        var count = $('#payroll-employees tbody input[type="checkbox"]:checked').length;      
        $('#payroll-employees tbody').empty();
        if ($.fn.DataTable.isDataTable('#payroll-employees'))
        {
            $('#payroll-employees').DataTable().destroy();
        }
        let table = $('#payroll-employees').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength":10,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('payroll.employee.list') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.department = $('#departmentFilter').val();
                    d.position = $('#positionFilter').val();
                    d.isChecked = isChecked;
                },
                dataSrc: function (json) 
                {
                    $('#selectedCount').text(json.totalChecked + " Employees Selected");
                    return json.data; 
                }
            },
            columns: [
                { data: 'id', orderable: false, searchable: false, defaultContent: '' },
                { data: 'Emp_id'},
                { 
                    data: 'employee', 
                    render: function(data, type, row) {
                        return `<div class="tableUser-block"><div class="img-circle"><img src="${data.profile_picture}"></div><span> ${data.first_name} ${data.last_name}</span></div>`;
                    }
                },
                { 
                    data: 'position', 
                    render: function(data, type, row) {
                        return ` ${data.postion_title} <span class="badge badge-themeLight">${data.position_code}</span>`;
                    }
                },
                { 
                    data: 'department', 
                    render: function(data, type, row) {
                        return ` ${data.department_name} <span class="badge badge-themeLight">${data.department_code}</span>`;
                    }
                },
                { data: 'section', defaultContent: 'N/A' },
                { data: 'payment_method', defaultContent: 'Cash' }
            ],
            drawCallback: function(settings) {
                // ✅ Another way to update selectedCount after table reload
                let api = this.api();
                let totalChecked = api.ajax.json().totalChecked; // Get total checked from JSON response
                $("#selectedCount").val(totalChecked);
            }
        });
    }

    function updatePageLength() 
    {
        var isChecked = $("#select-all").is(":checked");
       
        if (isChecked) {
            var table = $('#payroll-employees').DataTable();
            var newtable =  $("#table-timeAttendance").DataTable();
            var servicechargetable =  $("#table-serviceCharge").DataTable();
            var deductiontable = $("#table-deductions").DataTable();
            var reviewtable = $("#table-review").DataTable();
            $.ajax({
                url: '{{ route("payroll.employee.list") }}',
                type: 'GET',
                data: {
                    searchTerm : $('#searchInput').val(),
                    department : $('#departmentFilter').val(),
                    position : $('#positionFilter').val(),
                    isChecked : true,
                },
                success: function (response) {
                    var totalRecords = response.recordsTotal;
                    table.page.len(totalRecords).draw();
                    newtable.page.len(totalRecords).draw();
                    servicechargetable.page.len(totalRecords).draw();
                    deductiontable.page.len(totalRecords).draw();
                    reviewtable.page.len(totalRecords).draw();
                }
            });
        } 
    }

    function collectAllStepData() {
        let formData = {
            payrollPeriod: {},
            employees: [],
            attendance: [],
            serviceCharge: {
                totalAmount: 0,
                distribution: []
            },
            deductions: {
                cityLedgerFile: "",
                details: []
            },
            review : [],
            payrollSummary : {}
        };

        // 🟢 Step 1: Get Payroll Period Data
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        formData.payrollPeriod = {
            startDate: startDate.format("YYYY-MM-DD"), 
            endDate: endDate.format("YYYY-MM-DD"),
            currency_unit : currency,
        };

        // 🟢 Step 2: Get Selected Employees Data
        $("#payroll-employees tbody tr").each(function () {
            console.log($(this).find("td:eq(6)").text().trim());
            let isChecked = $(this).find(".form-check-input").prop("checked"); // Check if employee is selected
            if (isChecked) {
                formData.employees.push({
                    id: $(this).find("td:eq(1)").text().trim(), // Employee ID
                    name: $(this).find("td:eq(2)").text().trim(), // Employee Name
                    position: $(this).find("td:eq(3)").text().trim(), // Position
                    department: $(this).find("td:eq(4)").text().trim(), // Department
                    section: $(this).find("td:eq(5)").text().trim(), // Section
                    paymentMethod: $(this).find("td:eq(6)").text().trim() // Payment Method
                });
            }
        });

        // 🟢 Step 3: Get Time & Attendance Data
        $("#table-timeAttendance tbody tr").each(function () {
            let attendanceData = {
                id: $(this).find("td:eq(0)").text().trim(), // Employee ID
                name: $(this).find("td:eq(1)").text().trim(), // Employee Name
                department: $(this).find("td:eq(2)").text().trim(), // Department
                present: parseInt($(this).find("td:eq(3)").text().trim()) || 0, // Present Days
                absent: parseInt($(this).find("td:eq(4)").text().trim()) || 0, // Absent Days
                leaveTypes: $(this).find("td:eq(5)").text().trim(), // Leave Types
                regularOT: parseFloat($(this).find("td:eq(6)").text().trim()) || 0, // Regular OT Hours
                holidayOT: parseFloat($(this).find("td:eq(7)").text().trim()) || 0, // Holiday OT Hours
                totalOT: parseFloat($(this).find("td:eq(8)").text().trim()) || 0 // Total OT Hours
            };

            formData.attendance.push(attendanceData);
        });

        // 🟢 Step 4: Get Service Charge Data
        formData.serviceCharge.totalAmount = parseFloat($("#total-ser").val()) || 0;

        $("#table-serviceCharge tbody tr").each(function () {
            let serviceChargeData = {
                id: $(this).find("td:eq(0)").text().trim(), // Employee ID
                name: $(this).find("td:eq(1)").text().trim(), // Employee Name
                position: $(this).find("td:eq(2)").text().trim(), // Position
                department: $(this).find("td:eq(3)").text().trim(), // Department
                section: $(this).find("td:eq(4)").text().trim(), // Section
                totalWorkingDays: parseInt($(this).find("td:eq(5)").text().trim()) || 0, // Total Working Days
                serviceCharge: parseFloat($(this).find("td:eq(6)").text().trim()) || 0 // Distributed Service Charge
            };

            formData.serviceCharge.distribution.push(serviceChargeData);
        });

        // 🟢 Step 5: Get Salary Deductions Data
       
        $("#table-deductions tbody tr").each(function () {
            // console.log($(this).find("td:eq(4)").text());
            let deductionData = {
                id: $(this).find("td:eq(0)").text().trim(),
                name: $(this).find("td:eq(1)").text().trim(),
                department: $(this).find("td:eq(2)").text().trim(),
                attendanceDeduction: $(this).find("td:eq(3)").text().trim().replace("$", "") || 0,
                cityLedger: $(this).find("td:eq(4)").text().trim().replace("$", "") || 0,
                staffShop: $(this).find("td:eq(5)").text().trim().replace("$", "") || 0,
                pension: $(this).find("td:eq(6)").text().trim().replace("$", "") || 0,
                ewt: $(this).find("td:eq(7)").text().trim().replace("$", "") || 0,
                other: $(this).find("td:eq(8)").text().trim().replace("$", "") || 0,
                total: $(this).find("td:eq(9)").text().trim().replace("$", "") || 0
            };
            formData.deductions.details.push(deductionData);
        });

         // 🟢 Step 6: Get Review Data
        $("#table-review tbody tr").each(function () {
            let reviewData = {
                id: $(this).find("td:eq(0)").text().trim(),
                name: $(this).find("td:eq(1)").text().trim(),
                department: $(this).find("td:eq(2)").text().trim(),
                position: $(this).find("td:eq(3)").text().trim(),
                present: parseInt($(this).find("td:eq(4)").text().trim()) || 0,
                absent: parseInt($(this).find("td:eq(5)").text().trim()) || 0,
                serviceCharge: parseInt($(this).find("td:eq(6)").text().trim()) || 0,
                overtimeNormal: parseFloat($(this).find("td:eq(7)").text().trim()) || 0,
                overtimeHoliday: parseFloat($(this).find("td:eq(8)").text().trim()) || 0,
                overtimeTotal: parseFloat($(this).find("td:eq(9)").text().trim()) || 0,
                earningsBasic: parseFloat($(this).find("td:eq(10)").text().trim()) || 0,
                earningsAllowance: parseFloat($(this).find("td:eq(11)").text().trim()) || 0,
                earningsNormal: parseFloat($(this).find("td:eq(12)").text().trim()) || 0
            };

            formData.review.push(reviewData);
        });

         // 🟢 Step 7: Get Payroll Summary

        var payrollDraftDate = moment($("#payroll-darft-date").text().trim(), "DD-MM-YYYY", true);
        var payrollPaymentDate = moment($("#payroll-payment-date").text().trim(), "DD-MM-YYYY", true);
        formData.payrollSummary = {
            totalPayrollAmount: parseFloat($("#total_payroll_amount").text().trim()) || 0,
            totalEmployees: parseInt($("#total_employees").text().trim()) || 0,
            payrollDraftDate: payrollDraftDate.format("YYYY-MM-DD"), 
            payrollPaymentDate: payrollPaymentDate.format("YYYY-MM-DD"), 
        };


        return formData; // Return collected data as an object
    }

    $(document).on("click",".add-deduction-btn",function()
    {
        let empId = $(this).data('emp-id'); // Get employee ID from button
        $('#select_emp').val(empId).trigger('change.select2'); // Set employee in select
        $("#addDeduction-modal").modal("show");
    });

    function calculateTotalPayrollAmount(formData) {
        // Step 1: Calculate Total Earnings
        let totalEarnings = formData.review.reduce((sum, emp) => {
            return sum + (parseFloat(emp.earningsBasic) || 0)
                    + (parseFloat(emp.earningsAllowance) || 0)
                    + (parseFloat(emp.earningsNormal) || 0)
                    + (parseFloat(emp.overtimeTotal) || 0);
        }, 0);

        // Step 2: Get Total Service Charge
        let totalServiceCharge = parseFloat(formData.serviceCharge.totalAmount) || 0;

        // Step 3: Calculate Total Deductions
        let totalDeductions = formData.deductions.details.reduce((sum, deduction) => {
            return sum + (parseFloat(deduction.total) || 0);
        }, 0);

        // Step 4: Compute Final Total Payroll Amount
        let totalPayrollAmount = totalEarnings + totalServiceCharge - totalDeductions;

        return totalPayrollAmount;
    }

</script>
@endsection