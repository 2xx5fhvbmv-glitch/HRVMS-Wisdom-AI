@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                            <h1>{{ $page_title }}</h1>
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
                                                        <input type="text" class="form-control dateRangeAb datepicker" name="hiddenInput" id="hiddenInput" readonly disabled>
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
                                                    <input class="form-check-input" type="checkbox" id="select-all" value="" checked>
                                                </div>
                                            </th>
                                            <th>Employee ID </th>
                                            <th>Employee </th>
                                            <th>Position </th>
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
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                                            <input type="search" class="form-control" id="attedsearchInput" placeholder="Search" />
                                            <i class="fa-solid fa-search"></i>
                                        </div>
                                    </div>
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
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                                        <input type="text" class="form-control" id="total-ser" placeholder="ENTER TOTAL SERVICE CHARGE AMOUNT" maxlength="10" required>
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
                                            <th>Position</th>
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
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                                            <th>Advance Loan</th>
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
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                                            <th>Service Charge days</th>
                                            <th>Normal</th>
                                            <th>Holiday</th>
                                            <th>Total</th>
                                            <th>Basic</th>
                                            <th>Earned</th>
                                            <th>Allowance</th>
                                            <th>Deductions</th>
                                            <th>Normal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                                                                
                                    </tbody>
                                    <tfoot>
                                        <tr></tr>
                                    </tfoot>
                                </table>
                            </div>
                            <hr class="hr-footer border-0">
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                                    <!-- <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Payroll Payment Date</h6>
                                        <div class="value" id="payroll-payment-date"></div>
                                    </div> -->
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
                            <!-- <a href=" # " class="a-link ">Save As Draft</a> -->
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
                    <button type="button" class="btn btn-themeSkyblue" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submit_note" class="btn btn-themeBlue">Submit</button>
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
    let employeeData = {}; // Store employee details
    //  console.log(currency);
    $(document).ready(function () {
        // Ensure Parsley is loaded
        let resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }

        // Set the cutoff day (from Laravel config)
        const cutoffDay = "{{ $cutoffDay ?? 25 }}";

        // Get today's date
        const today = moment();
        let startDate, endDate;

        // Calculate based on cutoff logic (only previous period active)
        const currentMonth = today.month();
        const currentYear = today.year();

        startDate = moment([currentYear, currentMonth - 1, cutoffDay]);
        endDate = moment([currentYear, currentMonth, cutoffDay - 1]);

        // Fallback for invalid date scenarios
        startDate = startDate.isValid() ? startDate : moment([currentYear, currentMonth - 1, 1]).endOf('month');
        endDate = endDate.isValid() ? endDate : moment([currentYear, currentMonth, 1]).endOf('month');

        // Initialize daterangepicker
        $("#hiddenInput").daterangepicker({
            autoApply: true,
            startDate: startDate,
            endDate: endDate,
            opens: 'right',
            parentEl: '#datapicker',
            alwaysShowCalendars: false,
            linkedCalendars: false,
            locale: {
                format: "DD-MM-YYYY",
            },
            isInvalidDate: function () {
                return true; // disables all dates
            }
        }, function (start, end) {
            // Still trigger display update
            $("#startDate").text("Start Date: " + start.format("DD-MM-YYYY")).removeClass('d-none');
            $("#endDate").text("End Date: " + end.format("DD-MM-YYYY")).removeClass('d-none');
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
                var dateRange = $("#hiddenInput").val();
                var dates = dateRange.split(' - ');

                if (!dateRange || dates.length !== 2) {
                    toastr.error("Please select a valid payroll period before proceeding.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var startDate = moment(dates[0], "DD-MM-YYYY", true).format("YYYY-MM-DD");
                var endDate = moment(dates[1], "DD-MM-YYYY", true).format("YYYY-MM-DD");

                // Prepare data for draft payroll entry
                var payrollData = {
                    start_date: startDate,
                    end_date: endDate,
                    status: "draft",
                    _token: '{{ csrf_token() }}' // CSRF Token for Laravel
                };

                // Save draft payroll data via AJAX
                $.ajax({
                    url: '{{ route("payroll.save.draft") }}', // Laravel route for saving draft
                    method: 'POST',
                    data: payrollData,
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Payroll draft saved successfully!",'Success', {
                                positionClass: 'toast-bottom-right'
                            });

                            // Store payroll ID for future use
                            localStorage.setItem("payroll_id", response.payroll_id);
                            localStorage.setItem("currentStep", currentStep);

                            moveToNextStep($currentFieldset);
                        } else {
                            toastr.error(response.message,'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (xhr) {
                        let errorMessage = "Error saving payroll draft.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage,'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;
            }

            // Step 2: Save selected employees
            if (currentStep === 2) {
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID

                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var selectedEmployees = [];
                var selectedEmployeesIds = [];

                $("#payroll-employees tbody tr").each(function () {
                    // console.log($(this).find("td:eq(6)").text().trim());
                    let isChecked = $(this).find(".form-check-input").prop("checked"); // Check if employee is selected
                    if (isChecked) {
                        selectedEmployees.push({
                            id: $(this).find("td:eq(1)").text().trim(), // Employee ID
                            name: $(this).find("td:eq(2)").text().trim(), // Employee Name
                            position: $(this).find("td:eq(3)").text().trim(), // Position
                            department: $(this).find("td:eq(4)").text().trim(), // Department
                            section: $(this).find("td:eq(5)").text().trim(), // Section
                            paymentMethod: $(this).find("td:eq(6)").text().trim() // Payment Method
                        });
                        selectedEmployeesIds.push({
                            id: $(this).find("td:eq(1)").text().trim() // Employee ID
                        })
                    }
                });

                if (selectedEmployees.length === 0) {
                    toastr.error("Please select at least one employee.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save employees in payroll
                $.ajax({
                    url: '{{ route("payroll.saveEmployees") }}', // Laravel route
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        employees: selectedEmployees,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Employees added to payroll successfully!", 'Success', {
                                positionClass: 'toast-bottom-right'
                            });
                            getstep3data(searchTerm="");
                            localStorage.setItem("selectedEmployees", JSON.stringify(selectedEmployees));
                            localStorage.setItem("currentStep", currentStep);
                            localStorage.setItem("selectedEmployeesIds", JSON.stringify(selectedEmployeesIds));

                            moveToNextStep($currentFieldset);
                        } else {
                            toastr.error(response.message, 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Error saving employees.", 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;
            }

            // Step 3: Save Attendance of selected employees
            if (currentStep === 3) {
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
                var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID


                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var AttendaceData = [];

                $("#table-timeAttendance tbody tr").each(function () {
                    let $row = $(this);
                    let employeeId = $row.find("td:eq(0)").text().trim(); // Employee ID

                    let present = $row.find("td:eq(3) input").length > 0 ? 
                        parseInt($row.find("td:eq(3) input").val().trim()) || 0 : 
                        parseInt($row.find("td:eq(3)").text().trim()) || 0;

                    let absent = $row.find("td:eq(4) input").length > 0 ? 
                        parseInt($row.find("td:eq(4) input").val().trim()) || 0 : 
                        parseInt($row.find("td:eq(4)").text().trim()) || 0;

                    let regularOT = $row.find("td:eq(6) input").length > 0 ? 
                        parseFloat($row.find("td:eq(6) input").val().trim()) || 0 : 
                        parseFloat($row.find("td:eq(6)").text().trim()) || 0;

                    let holidayOT = $row.find("td:eq(7) input").length > 0 ? 
                        parseFloat($row.find("td:eq(7) input").val().trim()) || 0 : 
                        parseFloat($row.find("td:eq(7)").text().trim()) || 0;

                    let totalOT = $row.find("td:eq(8) input").length > 0 ? 
                        parseFloat($row.find("td:eq(8) input").val().trim()) || 0 : 
                        parseFloat($row.find("td:eq(8)").text().trim()) || 0;

                    AttendaceData.push({
                        id: employeeId,
                        name: $row.find("td:eq(1)").text().trim(), // Employee Name
                        department: $row.find("td:eq(2)").text().trim(), // Department
                        present: present,
                        absent: absent,
                        leaveTypes: $row.find("td:eq(5)").text().trim(), // Leave Types
                        regularOT: regularOT,
                        holidayOT: holidayOT,
                        totalOT: totalOT
                    });
                });

                if (AttendaceData.length === 0) {
                    toastr.error("Something is wrong to fetch attendance data.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save employees in payroll
                $.ajax({
                    url: '{{ route("payroll.saveAttendance") }}', // Laravel route
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        attendance: AttendaceData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Attendance added to payroll successfully!",'Success', {
                                positionClass: 'toast-bottom-right'
                            });

                            // Load currency rates before moving to next step
                            loadCurrencyRates(searchTerm = "", resortid, currency, currentStep)
                                .then(() => {
                                    moveToNextStep($currentFieldset);
                                    localStorage.setItem("currentStep", currentStep);
                                })
                                .catch(error => {
                                    toastr.error("Failed to load currency rates.", 'Error', {
                                        positionClass: 'toast-bottom-right'
                                    });
                                });
                        } else {
                            toastr.error(response.message, 'Error',{
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Error saving employees.", 'Error',{
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;
            }
            
            // Step 4: Save Service charges for employees
            if (currentStep === 4) {
                
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
                var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID

                var totalServiceCharge = parseFloat($("#total-ser").val()); // Get service charge amount

                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                if (isNaN(totalServiceCharge) || totalServiceCharge <= 0) {
                    toastr.error("Please enter a valid service charge amount.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var ServiceChargesData = [];

                ServiceChargesData.totalAmount = parseFloat($("#total-ser").val()) || 0;

                $("#table-serviceCharge tbody tr").each(function () {
                    // console.log($(this).find("td:eq(0)").text().trim());

                    ServiceChargesData.push({
                        id: $(this).find("td:eq(0)").text().trim(), // Employee ID
                        name: $(this).find("td:eq(1)").text().trim(), // Employee Name
                        position: $(this).find("td:eq(2)").text().trim(), // Position
                        department: $(this).find("td:eq(3)").text().trim(), // Department
                        section: $(this).find("td:eq(4)").text().trim(), // Section
                        totalWorkingDays: parseInt($(this).find("td:eq(5)").text().trim()) || 0, // Total Working Days
                        serviceCharge : $(this).find("td:eq(6)").text().trim().replace("$", "") || 0,// Distributed Service Charge
                        totalServiceCharge: totalServiceCharge
                    });
                });

                if (ServiceChargesData.length === 0) {
                    toastr.error("Please distribute service charge",' Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save employees in payroll
                $.ajax({
                    url: '{{ route("payroll.saveServiceCharges") }}', // Laravel route
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        ServiceChargesData : ServiceChargesData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Service Charges added to payroll successfully!", 'Error',{
                                positionClass: 'toast-bottom-right'
                            });
                            
                           // Load currency rates before moving to the next step
                            loadCurrencyRates(searchTerm = "",resortid, currency, currentStep).then(() => {
                                moveToNextStep($currentFieldset);
                            }).catch(error => {
                                console.error("Error loading currency rates:", error);
                                toastr.error("Failed to load currency rates.", 'Error',{
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                            
                        } else {
                            toastr.error(response.message, 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Error saving employees.",'Error',{
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;
            }

            // Step 5: Save Deductions for employees
            if (currentStep === 5) {
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
                var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID
                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var DeductionData = [];

                $("#table-deductions tbody tr").each(function () {
                    DeductionData.push({
                        id: $(this).find("td:eq(0)").text().trim(),
                        name: $(this).find("td:eq(1)").text().trim(),
                        department: $(this).find("td:eq(2)").text().trim(),
                        attendanceDeduction: $(this).find("td:eq(3)").text().trim().replace("$", "") || 0,
                        cityLedger: $(this).find("td:eq(4)").text().trim().replace("$", "") || 0,
                        staffShop: $(this).find("td:eq(5)").text().trim().replace("$", "") || 0,
                        advanceLoan : $(this).find("td:eq(6)").text().trim().replace("$", "") || 0,
                        pension: $(this).find("td:eq(7)").text().trim().replace("$", "") || 0,
                        ewt: $(this).find("td:eq(8)").text().trim().replace("$", "") || 0,
                        other: $(this).find("td:eq(9)").text().trim().replace("$", "") || 0,
                        total: $(this).find("td:eq(10)").text().trim().replace("$", "") || 0
                    });
                });

                if (DeductionData.length === 0) {
                    toastr.error("Something is wrong.some data are missing", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save employees in payroll
                $.ajax({
                    url: '{{ route("payroll.saveDeductions") }}', // Laravel route
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        DeductionData : DeductionData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Deductions added to payroll successfully!",'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                            
                           // Load currency rates before moving to the next step
                            loadCurrencyRates(searchTerm = "",resortid, currency, currentStep).then(() => {
                                moveToNextStep($currentFieldset);
                            }).catch(error => {
                                console.error("Error loading currency rates:", error);
                                toastr.error("Failed to load currency rates.", 'Error', {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                            
                        } else {
                            toastr.error(response.message, 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Error saving employees.", 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;

            }

            // Step 6: Save Earning review  for employees
            if (currentStep === 6) {
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
                var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID
                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var reviewData = [];
                const headers = [];

                $("#table-review thead tr th").each(function () {
                    headers.push({
                        index: $(this).index(),
                        text: $(this).text().trim(),
                        allowanceType: $(this).data('allowance') || null, // detect allowance columns
                        currency: $(this).data('currency') || 'USD' // default to Dollar if not specified
                    });
                });

                $("#table-review tbody tr").each(function () {
                    const $row = $(this);
                    const rowData = {
                        id: $row.find("td:eq(0)").text().trim(),
                        name: $row.find("td:eq(1)").text().trim(),
                        department: $row.find("td:eq(2)").text().trim(),
                        position: $row.find("td:eq(3)").text().trim(),
                        present: parseInt($row.find("td:eq(4)").text().trim()) || 0,
                        absent: parseInt($row.find("td:eq(5)").text().trim()) || 0,
                        serviceCharge: $row.find("td:eq(6)").text().trim().replace("$", "") || 0,
                        overtimeNormal: $row.find("td:eq(7)").text().trim().replace("$", "") || 0,
                        overtimeHoliday: $row.find("td:eq(8)").text().trim().replace("$", "") || 0,
                        overtimeTotal: $row.find("td:eq(9)").text().trim().replace("$", "") || 0,
                        earningsBasic: $row.find("td:eq(10)").text().trim().replace("$", "") || 0,
                        earnedSalary: $row.find("td:eq(11)").text().trim().replace("$", "") || 0,
                        earningsAllowance: 0, // will calculate below
                        earningsNormal: $row.find("td:eq(-2)").text().trim().replace("$", "") || 0,
                        totalDeductions: $row.find("td:eq(-1)").text().trim().replace("$", "") || 0,
                        allowances: []
                    };
                    
                    // Process allowances for this specific row
                    headers.forEach(header => {
                        // console.log(header);
                        if (header.allowanceType) {
                            const amount = parseFloat($row.find(`td:eq(${header.index})`).text().trim().replace("$", "")) || 0;
                            // console.log(amount);
                            rowData.allowances.push({
                                type: header.allowanceType,
                                amount: amount,
                                amount_unit: header.currency,
                            });
                            rowData.earningsAllowance += amount;
                        }
                    });
                    
                    // Add this row's data to the reviewData array
                    reviewData.push(rowData);
                });

                if (reviewData.length === 0) {
                    toastr.error("Something is wrong.some data are missing",'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save employees in payroll
                $.ajax({
                    url: '{{ route("payroll.saveReviews") }}', // Laravel route
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        reviewData : reviewData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success("Earning Reviews added to payroll successfully!", 'Error',{
                                positionClass: 'toast-bottom-right'
                            });
                            
                           // Load currency rates before moving to the next step
                            loadCurrencyRates(searchTerm = "",resortid, currency, currentStep).then(() => {
                                moveToNextStep($currentFieldset);
                            }).catch(error => {
                                console.error("Error loading currency rates:", error);
                                toastr.error("Failed to load currency rates.",'Error', {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                            
                        } else {
                            toastr.error(response.message, 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function () {
                        toastr.error("Error saving employees.", 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });

                return;

            }
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

        async function loadCurrencyRates(searchTerm="",resortId,currency,currentStep) {
            var rates = await fetchRates(resortId); // ✅ Wait for response
            var updatedCurrency = currency;
            // console.log(updatedCurrency);
            
            var conversionRate = updatedCurrency === 'Dollar' ? rates.usd_to_mvr : rates.mvr_to_usd;
            if(currentStep == 3)
            {
                getstep4data(searchTerm,updatedCurrency, conversionRate);
            }
            // console.log(conversionRate);
            else if(currentStep == 4)
            {
                getstep5data(updatedCurrency, conversionRate);
            }
            
            else if(currentStep == 5)
            {
                getstep6data(updatedCurrency, conversionRate);
            }

            else if(currentStep == 6)
            {
                calculatePayrollSummary(updatedCurrency, conversionRate);
            }
        }
        
        // distribute service charges
        $("#distribute-service-charge").click(async function (e) {
            e.preventDefault();

            var totalServiceCharge = parseFloat($("#total-ser").val().replace('$', '').replace(',', ''));
            if (isNaN(totalServiceCharge) || totalServiceCharge <= 0) {
                toastr.error("Please enter a valid service charge amount.", 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            var rates = await fetchRates(resortid);
            var serviceCharge = (selectedCurrency === "MVR") 
                ? totalServiceCharge * rates.usd_to_mvr 
                : totalServiceCharge;

            var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
            var selectedEmployees = localStorage.getItem("selectedEmployeesIds")?.split(',') || [];

            distributedServiceCharge = [];

            // 🔽 Step 2: Gather only eligible employees based on benefit grid
            const eligibleEmployees = await getEligibleEmployeesFromBackend(selectedEmployees); // Replace with actual API call

            // Calculate total workdays for eligible employees only
            var totalWorkdays = 0;
            $("#table-serviceCharge tbody tr").each(function () {
                var $row = $(this);
                var employeeId = $row.find("td:eq(0)").text();

                if (eligibleEmployees.includes(employeeId)) {
                    var workdays = parseFloat($row.find(".workdays").text());
                    totalWorkdays += workdays;
                }
            });

            // Distribute service charge to eligible employees only
            var distributedTotal = 0;
            $("#table-serviceCharge tbody tr").each(function () {
                var $row = $(this);
                var employeeId = $row.find("td:eq(0)").text();

                if (eligibleEmployees.includes(employeeId)) {
                    var workdays = parseFloat($row.find(".workdays").text());
                    var employeeShare = (serviceCharge / totalWorkdays) * workdays;
                    $row.find(".service-charge").text(`${currencySymbol}${employeeShare.toFixed(2)}`);
                    distributedTotal += employeeShare;

                    distributedServiceCharge.push({
                        id: employeeId,
                        service_charge_days: workdays,
                        amount: employeeShare.toFixed(2)
                    });
                } else {
                    $row.find(".service-charge").text(`${currencySymbol}0.00`);
                }
            });

            $("#total-service-charge").text(`${currencySymbol}${distributedTotal.toFixed(2)}`);
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
                toastr.error("Please select at least one employee before downloading the template.", 'Error', {
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
                            updateTotal($row,employeeId);
                        }
                    });
                });
            };
            reader.readAsArrayBuffer(file);
            toastr.success("File Uploaded Successfully!", 'Success', {
                positionClass: 'toast-bottom-right'
            });
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
                    updateTotal($row,employeeId);// Update the total column
                }
            });
            $('#addDeductionForm')[0].reset(); // Correct way

            $("#addDeduction-modal").modal("hide");
            toastr.success("Deduction added successfully!", 'Success', {
                positionClass: 'toast-bottom-right'
            });
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

        setTimeout(() => {
            updatePageLength();
        }, 1000);
        // Filter change event
        $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function () {
            employeeList();
        });

        $('#table-timeAttendance').on('click', '.add_note', function () {
            employeeData = {
                employee_id: $(this).data('employee-id'), // Might be null if first-time entry
                empid: $(this).data('emp-id'),
                payroll_id: $(this).data('payroll-id'),
                present: $(this).data('present'),
                absent: $(this).data('absent'),
                leave_type: $(this).data('leave-type'),
                regular_ot: $(this).data('regular-ot'),
                holiday_ot: $(this).data('holiday-ot'),
                total_ot: $(this).data('totel')
            };
            // console.log("Employee Data:", employeeData); // Debugging line
            $('#addnoteModal').modal('show');
        });

        $('#msform').on('submit', function (e) {
            e.preventDefault(); // Prevent form from submitting
            var currencySymbol = (selectedCurrency === 'Dollar') ? '$' : 'MVR ';

            var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
            var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID

            if (!payrollId) {
                toastr.error("Payroll draft not found. Please start again.", 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            var summaryData = [];
            // var payrollDraftDate = moment($("#payroll-darft-date").text().trim(), "DD-MM-YYYY", true);
            // var payrollPaymentDate = moment($("#payroll-payment-date").text().trim(), "DD-MM-YYYY", true);

            summaryData = {
                totalPayrollAmount: parseFloat($("#total_payroll_amount").text().trim().replace(currencySymbol, "")) || 0,
                totalEmployees: parseInt($("#total_employees").text().trim()) || 0,
                payrollDraftDate: $("#payroll-darft-date").text().trim(), 
                // payrollPaymentDate: $("#payroll-payment-date").text().trim(), 
            };

            // Check if summaryData is empty
            if (Object.keys(summaryData).length === 0) {
                toastr.error("Something is wrong. Some data are missing", 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            $('#submit').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

            // Send AJAX request to save employees in payroll
            $.ajax({
                url: '{{ route("payroll.saveSummary") }}', // Laravel route
                method: 'POST',
                data: {
                    payroll_id: payrollId,
                    summaryData : summaryData,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        // Optional: Redirect to a confirmation page or refresh
                        setTimeout(function () {
                            window.location.href = response.redirect_url;
                        }, 2000);
                        
                    } else {
                        toastr.error(response.message, 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    console.error("Error Response:", xhr);

                    let errorMessage = 'An error occurred while submitting payroll.';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

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
                        .html('Confirm and Lock Payroll');
                }
            });
        });

    });

    // Handle note submission
    $('#submit_note').on('click', function () {
        let noteText = $('#addNote').val().trim();
        // console.log("Employee Data:", employeeData); // Debugging line

        if (noteText === "") {
            alert("Note cannot be empty!");
            return;
        }

        $.ajax({
            url: "{{ route('payroll.saveAttendanceNote') }}", // Backend route
            method: "POST",
            data: {
                employee_id: employeeData.employee_id, // Employee ID
                empid : employeeData.empid,
                payroll_id: employeeData.payroll_id, // Payroll ID
                present: employeeData.present,
                absent: employeeData.absent,
                leave_type: employeeData.leave_type,
                regular_ot: employeeData.regular_ot,
                holiday_ot: employeeData.holiday_ot,
                total_ot: employeeData.total_ot,
                note: noteText, // User note
                _token: "{{ csrf_token() }}" // CSRF token
            },
            success: function (response) {
                if (response.success) {
                    $('#addnoteModal').modal('hide'); // Close modal
                    $('#addNote').val(''); // Clear textarea

    
                    alert("Note added successfully!");
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function () {
                alert("Failed to save note. Please try again.");
            }
        });
    });

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

    async function getEligibleEmployeesFromBackend(employeeIds) {
        try {
            let response = await $.ajax({
                url: '{{route("payroll.getEligibleEmployees")}}', // Adjust the route as needed
                method: 'POST',
                data: {
                    employee_ids: employeeIds,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            });
            return response; // should be an array of eligible IDs
        } catch (error) {
            console.error("Error fetching eligible employees:", error);
            return [];
        }
    }

    function updateSelectedCount(){
        var count = $('#payroll-employees tbody input[type="checkbox"]:checked').length;
        $('#selectedCount').text(count + " Employees Selected");
    }

    $("#attedsearchInput").on("keyup", function () {
        searchTerm = $('#attedsearchInput').val();
        getstep3data(searchTerm);
    });

    function getstep3data(searchTerm) {
        var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID

        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = [];

        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", 'Error',{ positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: '{{ route("fetch.time.attendance") }}',
            method: 'POST',
            data: { 
                employees: selectedEmployees,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                searchTerm :  searchTerm,
                payrollId:payrollId,
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
                        // console.log(employee);
                        var row = `<tr>
                            <td>${employee.employee_id}</td>
                            <td>
                                <div class="tableUser-block">
                                    <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                    <span>${employee.name}</span>
                                </div>
                            </td>
                            <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
                            <td class="editable">${employee.present}</td>
                            <td class="editable">${employee.absent}</td>
                            <td>${employee.leave_types}</td>
                            <td class="editable">${employee.regular_ot}</td>
                            <td class="editable">${employee.holiday_ot}</td>
                            <td >${employee.total_ot}</td>
                            <td>
                                <a href="#" class="btn-lg-icon icon-bg-skyblue edit-btn"><i class="fa-regular fa-pen"></i></a>
                                <a href="#" 
                                    class="a-link add_note" 
                                    data-employee-id='${employee.employee_id}'
                                    data-emp-id="${employee.empid}" 
                                    data-payroll-id="${payrollId}"
                                    data-present="${employee.present}"
                                    data-absent="${employee.absent}" 
                                    data-leave-type="${employee.leave_types}" 
                                    data-regular-ot="${employee.regular_ot}" 
                                    data-holiday-ot="${employee.holiday_ot}" 
                                    data-totel="${employee.total_ot}">+ Add Note</a>
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
                    // ✅ Implement Search Filtering
                    
                    updatePageLength() ;
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            },

        });

        return;
    }

    function getstep4data(searchTerm,currency, conversionRate = 1) {
        // console.log('step4');
        // console.log(searchTerm,currency, conversionRate);
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = [];

        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: '{{ route("fetch.time.attendance") }}',
            method: 'POST',
            data: { 
                employees: selectedEmployees,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                _token: '{{ csrf_token() }}' ,
                currency : currency, 
                conversionRate : conversionRate,
            }, // CSRF token required
            success: function (response) {
                if (response.success) {
                    var $tableBody = $("#table-serviceCharge tbody");
                    if ($.fn.DataTable.isDataTable("#table-serviceCharge")) {
                        $("#table-serviceCharge").DataTable().destroy();
                    }
                    $tableBody.empty(); // Clear existing rows
                    response.data.forEach(function (employee) {
                        let serviceCharge = getServiceChargeamountForEmployee(employee.employee_id);

                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR '; // Adjust currency symbol

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
                            <td class="service-charge">${currencySymbol}${serviceCharge}</td>
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

                    updatePageLength();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            }

        });

        return;
    }
        
    // function getstep5data(currency , conversionRate = 1) {
    //     var dateRange = $("#hiddenInput").val();
    //     var dates = dateRange.split(' - ');
    //     var startDate = moment(dates[0], "DD-MM-YYYY", true);
    //     var endDate = moment(dates[1], "DD-MM-YYYY", true);
    //     var selectedEmployees = [];
    //     var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID


    //     $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
    //         selectedEmployees.push($(this).val());
    //     });

    //     if (selectedEmployees.length === 0) {
    //         toastr.error("Please select at least one employee before proceeding.", { positionClass: 'toast-bottom-right' });
    //         return;
    //     }

    //     $.ajax({
    //         url: '{{ route("fetch.time.attendance") }}',
    //         method: 'POST',
    //         data: { 
    //             employees: selectedEmployees,
    //             startDate: startDate.format("YYYY-MM-DD"), 
    //             endDate: endDate.format("YYYY-MM-DD"),
    //             currency : currency, 
    //             conversionRate : conversionRate, // Send currency in request
    //             _token: '{{ csrf_token() }}' 
    //         },
    //         success: function (response) {
    //             if (response.success) {
    //                 var $tableBody = $("#table-deductions tbody");
    //                 if ($.fn.DataTable.isDataTable("#table-deductions")) {
    //                     $("#table-deductions").DataTable().destroy();
    //                 }

    //                 $tableBody.empty();

    //                 response.data.forEach(function (employee) {
    //                     var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR '; // Adjust currency symbol

    //                     var row = `<tr>
    //                         <td>${employee.employee_id}</td>
    //                         <td>
    //                             <div class="tableUser-block">
    //                                 <div class="img-circle"><img src="${employee.image}" alt="user"></div>
    //                                 <span>${employee.name}</span>
    //                             </div>
    //                         </td>
    //                         <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
    //                         <td class="attendance">${currencySymbol}${employee.absent_deduction}</td>
    //                         <td class="city-ladger">${currencySymbol}0.00</td>
    //                         <td class="staff-shop">${currencySymbol}0.00</td>
    //                         <td class="pension">${currencySymbol}0.00</td>
    //                         <td class="ewt">${currencySymbol}0.00</td>
    //                         <td class="other">${currencySymbol}0.00</td>
    //                         <td class="total-deduction">${currencySymbol}0.00</td>
    //                         <td>
    //                            <a href="javascript:void(0)" class="btn btn-themeSkyblueLight btn-small add-deduction-btn" data-emp-id="${employee.employee_id}">
    //                             Add Deduction
    //                         </a>
    //                         </td>
    //                     </tr>`;
    //                     $tableBody.append(row);
    //                 });

    //                 $("#table-deductions").DataTable({
    //                     responsive: true,
    //                     paging: false,
    //                     searching: false,
    //                     ordering: true,
    //                     autoWidth: false,
    //                     pageLength: 10
    //                 });
    //                 let  resortid ="{{  Auth::guard('resort-admin')->user()->resort_id }}";
    //                 updatePageLength();
    //                 $("#table-deductions tbody tr").each(async function () {
    //                     var $row = $(this);
    //                     var employeeId = $row[0];
    //                     var rates = await fetchRates(resortid);

    //                     var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                        
    //                     var employeeId = $row.find("td:eq(0)").text();

    //                     if (cityLedgerData[employeeId] !== undefined) {
    //                         // console.log(cityLedgerData[employeeId],updatedCurrency,conversionRate1);
    //                         // Get stored USD value and convert based on new currency
    //                         var cityLedgerUSD = cityLedgerData[employeeId];
    //                         var cityLedgerFinal = (currency === "MVR") 
    //                             ? cityLedgerUSD * rates.usd_to_mvr 
    //                             : cityLedgerUSD;
    //                         // console.log($row.find("td:eq(0)").text() ,employeeId );
    //                         if ($row.find("td:eq(0)").text() === employeeId) {
    //                             $row.find("td:eq(4)").text(currencySymbol + cityLedgerFinal); // Update displayed value
    //                             // updateTotal($row);
    //                         }

    //                     }

    //                     if (otherData[employeeId] !== undefined) {
    //                         // console.log(cityLedgerData[employeeId],updatedCurrency,conversionRate1);
    //                         // Get stored USD value and convert based on new currency
    //                         var amountInUSD = otherData[employeeId];
    //                         var FinalOtherAMount = (currency === "MVR") 
    //                             ? amountInUSD * rates.usd_to_mvr 
    //                             : amountInUSD;
    //                         // console.log($row.find("td:eq(0)").text() ,employeeId );
    //                         if ($row.find("td:eq(0)").text() === employeeId) {
                               
    //                             $row.find("td:eq(8)").text(currencySymbol + FinalOtherAMount.toFixed(2)); // Update displayed value
                                
    //                         }

    //                     }
    //                 });
                  
    //                 fetchStaffShopData(selectedEmployees, startDate, endDate, currency, conversionRate);
    //                 calculatePensionAndEWT(selectedEmployees, currency, conversionRate,payrollId);
    //                 // updateTotal($row,employee.empid);

    //             } else {
    //                 toastr.error(response.message, { positionClass: 'toast-bottom-right' });
    //             }
    //         },
    //         error: function () {
    //             toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
    //         }
    //     });
    // }

    async function getstep5data(currency, conversionRate = 1) {
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = [];
        var payrollId = localStorage.getItem("payroll_id");
        let resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
        var rates = await fetchRates(resortid);

        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        $.ajax({
            url: '{{ route("fetch.time.attendance") }}',
            method: 'POST',
            data: { 
                employees: selectedEmployees,
                startDate: startDate.format("YYYY-MM-DD"), 
                endDate: endDate.format("YYYY-MM-DD"),
                currency: currency,
                conversionRate: conversionRate,
                _token: '{{ csrf_token() }}' 
            },
            success: async function (response) {
                console.log(response);

                if (response.success) {
                    const employeeEarningsData = response.data.map(emp => ({
                        id: emp.empid,
                        earned_salary: emp.earned_salary,
                        totalOTPay : emp.totalOTPay,
                    }));
                    var $tableBody = $("#table-deductions tbody");
                    if ($.fn.DataTable.isDataTable("#table-deductions")) {
                        $("#table-deductions").DataTable().destroy();
                    }

                    $tableBody.empty();

                    // Fetch loan repayments data (you must build this endpoint or include it in the main response)
                    const advanceRepaymentData = await fetchAdvanceRecoveryData(selectedEmployees, startDate.format("YYYY-MM-DD"), endDate.format("YYYY-MM-DD"));

                    response.data.forEach(function (employee) {
                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                        let repaymentAmount = advanceRepaymentData[employee.empid] ?? 0;
                        let repaymentAmountFinal = (currency === "MVR") 
                            ? repaymentAmount * rates.usd_to_mvr  
                            : repaymentAmount;

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
                            <td class="advance-loan">${currencySymbol}${repaymentAmountFinal.toFixed(2)}</td>
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

                    updatePageLength();

                    $("#table-deductions tbody tr").each(async function () {
                        var $row = $(this);
                        var employeeId = $row.find("td:eq(0)").text();
                       
                        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';

                        // City Ledger update
                        if (cityLedgerData[employeeId] !== undefined) {
                            var cityLedgerUSD = cityLedgerData[employeeId];
                            var cityLedgerFinal = (currency === "MVR") 
                                ? cityLedgerUSD * rates.usd_to_mvr 
                                : cityLedgerUSD;
                            $row.find("td:eq(4)").text(currencySymbol + cityLedgerFinal.toFixed(2));
                        }

                        // Other deduction update
                        if (otherData[employeeId] !== undefined) {
                            var amountInUSD = otherData[employeeId];
                            var FinalOtherAmount = (currency === "MVR") 
                                ? amountInUSD * rates.usd_to_mvr 
                                : amountInUSD;
                            $row.find("td:eq(9)").text(currencySymbol + FinalOtherAmount.toFixed(2));
                        }
                    });

                    fetchStaffShopData(selectedEmployees, startDate, endDate, currency, conversionRate);
                    calculatePensionAndEWT(employeeEarningsData, currency, conversionRate, payrollId);

                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            }

        });
    }

    // Helper: fetch advance recovery data
    async function fetchAdvanceRecoveryData(employeeIds, startDate, endDate) {
        try {
            const response = await $.ajax({
                url: '{{ route("fetch.advance.recovery") }}', // Create this route
                method: 'POST',
                data: {
                    employee_ids: employeeIds,
                    start_date: startDate,
                    end_date: endDate,
                    _token: '{{ csrf_token() }}'
                }
            });
            return response.data || {};
        } catch (err) {
            console.error('Failed to fetch advance recovery:', err);
            return {};
        }
    }

    // async function getstep6data(currency, conversionRate = 1) {
    //     var payrollId = localStorage.getItem("payroll_id");
    //     var dateRange = $("#hiddenInput").val();
    //     var dates = dateRange.split(' - ');
    //     var startDate = moment(dates[0], "DD-MM-YYYY", true);
    //     var endDate = moment(dates[1], "DD-MM-YYYY", true);
    //     var selectedEmployees = [];

    //     $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
    //         selectedEmployees.push($(this).val());
    //     });

    //     if (selectedEmployees.length === 0) {
    //         toastr.error("Please select at least one employee before proceeding.", { positionClass: 'toast-bottom-right' });
    //         return;
    //     }

    //     $.ajax({
    //         url: '{{ route("fetch.time.attendance") }}',
    //         method: 'POST',
    //         data: {
    //             employees: selectedEmployees,
    //             startDate: startDate.format("YYYY-MM-DD"),
    //             endDate: endDate.format("YYYY-MM-DD"),
    //             currency: currency,
    //             conversionRate: conversionRate,
    //             payrollId: payrollId,
    //             _token: '{{ csrf_token() }}'
    //         },
    //         success: function (response) {
    //             if (response.success) {
    //                 let currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';

    //                 var $tableBody = $("#table-review tbody");
    //                 if ($.fn.DataTable.isDataTable("#table-review")) {
    //                     $("#table-review").DataTable().destroy();
    //                 }
    //                 $tableBody.empty();

    //                 const uniqueAllowances = new Set();
    //                 response.data.forEach(emp => {
    //                     (emp.allowances || []).forEach(a => uniqueAllowances.add(a.name));
    //                 });
    //                 const allowanceList = Array.from(uniqueAllowances);

    //                 // Update headers
    //                 let allowanceHeaderHtml = allowanceList.map(name => `<th>${name}</th>`).join('');

    //                 $("#table-review thead tr:eq(0)").html(`
    //                     <th colspan="4"></th>
    //                     <th colspan="3">Time & Attendance</th>
    //                     <th colspan="3">Overtime</th>
    //                     <th colspan="${2 + allowanceList.length + 2}">Earnings</th>
    //                 `);
    //                 $("#table-review thead tr:eq(1)").html(`
    //                     <th>ID</th>
    //                     <th>Employee Name</th>
    //                     <th>Department</th>
    //                     <th>Position</th>
    //                     <th>Present</th>
    //                     <th>Absent</th>
    //                     <th>Service Charge days</th>
    //                     <th>Normal</th>
    //                     <th>Holiday</th>
    //                     <th>Total</th>
    //                     <th>Basic</th>
    //                     <th>Earned</th>
    //                     ${allowanceHeaderHtml}
    //                     <th>Deductions</th>
    //                     <th>Normal</th>
    //                 `);

    //                 let footerTotals = {
    //                     present: 0, absent: 0, regular_ot: 0, holiday_ot: 0, total_ot: 0,
    //                     basic_salary: 0, earned_salary:0,total_deductions: 0,normal_pay: 0
    //                 };
    //                 let allowanceSums = Object.fromEntries(allowanceList.map(a => [a, 0]));

    //                 response.data.forEach(function (employee) {
    //                     let serviceCharge = getServiceChargedayForEmployee(employee.employee_id);
    //                     let allowanceMap = Object.fromEntries((employee.allowances || []).map(a => [a.name, a.amount]));

    //                     let allowanceCols = '';
    //                     allowanceList.forEach(name => {
    //                         let val = allowanceMap[name] || 0;
    //                         allowanceCols += `<td>${currencySymbol}${val.toFixed(2)}</td>`;
    //                         allowanceSums[name] += val;
    //                     });

    //                     footerTotals.present += employee.present;
    //                     footerTotals.absent += employee.absent;
    //                     footerTotals.regular_ot += employee.regular_ot;
    //                     footerTotals.holiday_ot += employee.holiday_ot;
    //                     footerTotals.total_ot += employee.total_ot;
    //                     footerTotals.basic_salary += employee.basic_salary;
    //                     footerTotals.earned_salary += employee.earned_salary;
    //                     footerTotals.total_deductions += (employee.total_deduction || 0);
    //                     footerTotals.normal_pay += employee.normal_pay;

    //                     var row = `<tr>
    //                         <td>${employee.employee_id}</td>
    //                         <td>
    //                             <div class="tableUser-block">
    //                                 <div class="img-circle"><img src="${employee.image}" alt="user"></div>
    //                                 <span>${employee.name}</span>
    //                             </div>
    //                         </td>
    //                         <td>${employee.department} <span class="badge badge-themeLight">${employee.code}</span></td>
    //                         <td>${employee.position}</td>
    //                         <td>${employee.present}</td>
    //                         <td>${employee.absent}</td>
    //                         <td>${currencySymbol}${employee.service_charge}</td>
    //                         <td>${currencySymbol}${employee.regularOTPay}</td>
    //                         <td>${currencySymbol}${employee.holidayOTPay}</td>
    //                         <td>${currencySymbol}${employee.totalOTPay}</td>
    //                         <td>${currencySymbol}${employee.basic_salary.toFixed(2)}</td>
    //                         <td>${currencySymbol}${employee.earned_salary.toFixed(2)}</td>
    //                         ${allowanceCols}
    //                         <td>
    //                         <td>${currencySymbol}${(employee.total_deduction || 0).toFixed(2)}</td>
    //                         <td>${currencySymbol}${employee.normal_pay.toFixed(2)}</td>
    //                     </tr>`;
    //                     $tableBody.append(row);
    //                 });

    //                 // Footer
    //               let footerHtml = `
    //                     <td colspan="4" class="text-end fw-bold">Total</td>
    //                     <td>${footerTotals.present}</td>
    //                     <td>${footerTotals.absent}</td>
    //                     <td>-</td>
    //                     <td>${footerTotals.regular_ot.toFixed(2)} hrs</td>
    //                     <td>${footerTotals.holiday_ot.toFixed(2)} hrs</td>
    //                     <td>${footerTotals.total_ot.toFixed(2)} hrs</td>
    //                     <td>${currencySymbol}${footerTotals.basic_salary.toFixed(2)}</td>
    //                     <td>${currencySymbol}${footerTotals.earned_salary.toFixed(2)}</td>
    //                 `;

    //                 // Add dynamic allowance totals
    //                 allowanceList.forEach(name => {
    //                     footerHtml += `<td>${currencySymbol}${allowanceSums[name].toFixed(2)}</td>`;
    //                 });

    //                 // Add deductions and normal pay total
    //                 footerHtml += `
    //                     <td>${currencySymbol}${footerTotals.total_deductions.toFixed(2)}</td>
    //                     <td>${currencySymbol}${footerTotals.normal_pay.toFixed(2)}</td>
    //                 `;

    //                 if (!$("#table-review tfoot").length) {
    //                     $("#table-review").append('<tfoot><tr id="table-footer"></tr></tfoot>');
    //                 }
    //                 $("#table-footer").html(footerHtml);


    //                 $("#table-review").DataTable({
    //                     responsive: true,
    //                     paging: false,
    //                     searching: false,
    //                     ordering: true,
    //                     autoWidth: false,
    //                     pageLength: 10
    //                 });
    //                 updatePageLength();

    //             } else {
    //                 toastr.error(response.message, { positionClass: 'toast-bottom-right' });
    //             }
    //         },
    //         error: function (xhr) {
    //             if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
    //                 toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
    //             } else {
    //                 toastr.error("Error fetching attendance data.", { positionClass: 'toast-bottom-right' });
    //             }
    //         }
    //     });
    // }

    async function getstep6data(currency, conversionRate = 1) {
        var payrollId = localStorage.getItem("payroll_id");
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
                currency: currency,
                conversionRate: conversionRate,
                payrollId: payrollId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    // console.log("111",response.data);
                    let currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                    var $table = $("#table-review");
                    var $tableBody = $table.find("tbody");

                    if ($.fn.DataTable.isDataTable("#table-review")) {
                        $table.DataTable().destroy();
                    }
                    $tableBody.empty();

                    // Option 1: Store objects in the Set with a custom stringifier
                    const uniqueAllowances = new Map();
                    response.data.forEach(emp => {
                        (emp.allowances || []).forEach(a => {
                            uniqueAllowances.set(a.name, a.unit || 'USD'); // Map name to unit
                        });
                    });

                    // Convert to array of objects for easier use
                    const allowanceList = Array.from(uniqueAllowances).map(([name, unit]) => ({
                        name: name,
                        unit: unit
                    }));
                    // console.log("Allowances with units:", allowanceList);

                    // Update thead - Fixed to use the unit from each allowance
                    let allowanceHeaderHtml = allowanceList.map(allowance => 
                        `<th data-allowance="${allowance.name}" data-currency="${allowance.unit}">${allowance.name}</th>`
                    ).join('');
                    // console.log(allowanceHeaderHtml);

                    $table.find("thead tr:eq(0)").html(`
                        <th colspan="4"></th>
                        <th colspan="3">Time & Attendance</th>
                        <th colspan="3">Overtime</th>
                        <th colspan="${2 + allowanceList.length + 2}">Earnings</th>
                    `);

                    $table.find("thead tr:eq(1)").html(`
                        <th>ID</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Service Charge</th>
                        <th>Normal</th>
                        <th>Holiday</th>
                        <th>Total</th>
                        <th>Basic</th>
                        <th>Earned</th>
                        ${allowanceHeaderHtml}
                        <th>Total Earnings</th>
                        <th>Deductions</th>
                    `);

                    let footerTotals = {
                        present: 0, absent: 0, service_charge:0, regularOTPay: 0, holidayOTPay: 0, totalOTPay: 0,
                        basic_salary: 0, earned_salary: 0,  normal_pay: 0, total_deductions: 0,
                    };
                    let allowanceSums = Object.fromEntries(allowanceList.map(a => [a.name, 0]));

                    response.data.forEach(function (employee) {
                        let allowanceMap = Object.fromEntries((employee.allowances || []).map(a => [a.name, a.amount]));

                        let allowanceCols = '';
                        // allowanceList.forEach(name => {
                        //     let val = allowanceMap[name] || 0;
                        //     allowanceCols += `<td>${currencySymbol}${val.toFixed(2)}</td>`;
                        //     allowanceSums[name] += val;
                        // });

                        allowanceList.forEach(a => {
                            let val = allowanceMap[a.name] || 0;
                            allowanceCols += `<td>${currencySymbol}${val.toFixed(2)}</td>`;
                            allowanceSums[a.name] += val;
                        });

                        footerTotals.present += employee.present;
                        footerTotals.absent += employee.absent;
                        footerTotals.service_charge += employee.service_charge;
                        footerTotals.regularOTPay += employee.regularOTPay;
                        footerTotals.holidayOTPay += employee.holidayOTPay;
                        footerTotals.totalOTPay += employee.totalOTPay;
                        footerTotals.basic_salary += employee.basic_salary;
                        footerTotals.earned_salary += employee.earned_salary;
                        footerTotals.normal_pay += employee.normal_pay;
                        footerTotals.total_deductions += (employee.total_deduction || 0);
                        

                        let row = `
                            <tr>
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
                                <td>${currencySymbol}${employee.service_charge}</td>
                                <td>${currencySymbol}${employee.regularOTPay}</td>
                                <td>${currencySymbol}${employee.holidayOTPay}</td>
                                <td>${currencySymbol}${employee.totalOTPay}</td>
                                <td>${currencySymbol}${employee.basic_salary.toFixed(2)}</td>
                                <td>${currencySymbol}${employee.earned_salary.toFixed(2)}</td>
                                ${allowanceCols}
                                <td>${currencySymbol}${employee.normal_pay.toFixed(2)}</td>
                                <td>${currencySymbol}${(employee.total_deduction || 0).toFixed(2)}</td>
                                
                            </tr>`;
                        $tableBody.append(row);
                    });

                    // Generate footer row
                    let footerHtml = `
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td>${footerTotals.present}</td>
                        <td>${footerTotals.absent}</td>
                        <td>${currencySymbol}${footerTotals.service_charge}</td>
                        <td>${currencySymbol}${footerTotals.regularOTPay.toFixed(2)} </td>
                        <td>${currencySymbol}${footerTotals.holidayOTPay.toFixed(2)} </td>
                        <td>${currencySymbol}${footerTotals.totalOTPay.toFixed(2)} </td>
                        <td>${currencySymbol}${footerTotals.basic_salary.toFixed(2)}</td>
                        <td>${currencySymbol}${footerTotals.earned_salary.toFixed(2)}</td>
                    `;
                    allowanceList.forEach(a => {
                        footerHtml += `<td>${currencySymbol}${allowanceSums[a.name].toFixed(2)}</td>`;
                    });
                    // allowanceList.forEach(a => {
                    //     footerHtml += `<td>${a.unit === 'USD' ? '$' : 'MVR '}${allowanceSums[a.name].toFixed(2)}</td>`;
                    // });
                    footerHtml += `
                        <td>${currencySymbol}${footerTotals.normal_pay.toFixed(2)}</td>
                        <td>${currencySymbol}${footerTotals.total_deductions.toFixed(2)}</td>
                        
                    `;

                    // Make sure the table has the right structure before manipulating it
                    if ($table.find("tfoot").length === 0) {
                        $table.append('<tfoot><tr id="table-footer"></tr></tfoot>');
                    } else {
                        // Completely replace the footer content to avoid partial DOM updates
                        $table.find("tfoot").html('<tr id="table-footer"></tr>');
                    }

                    // Update footer HTML and ensure it's in the DOM
                    $table.find("tfoot tr#table-footer").html(footerHtml);
                    
                    // Use setTimeout to ensure DOM is fully updated before DataTables initialization
                    setTimeout(function() {
                        $table.DataTable({
                            responsive: true,
                            paging: false,
                            searching: false,
                            ordering: true,
                            autoWidth: false,
                            pageLength: 10
                        });
                        
                        updatePageLength();
                    }, 0);
                                    

                    updatePageLength();
                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            }
        });
    }


    function calculatePayrollSummary(currency, conversionRate = 1) {
        var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
        if (!payrollId) {
            toastr.error("Payroll ID not found.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);

        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR '; // Adjust currency symbol


        var selectedEmployees = []; // Fix: Declare selectedEmployees
        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            selectedEmployees.push($(this).val());
        });

        if (selectedEmployees.length === 0) {
            toastr.error("Please select at least one employee before proceeding.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        var deductions = JSON.parse(localStorage.getItem("deductions")) || {}; // Get stored deductions
        // console.log(deductions);

        let totalEmployees = selectedEmployees.length;
        let draftDate = new Date().toISOString().split("T")[0]; // Current date as draft date
        console.log("Draft Date:", draftDate);
        let paymentDate = new Date();
        paymentDate.setDate(paymentDate.getDate() + 7); // Example: Payroll payment after 7 days

        $.ajax({
            url: '{{ route("fetch.totalPayroll.data") }}',
            method: 'POST',
            data: { 
                payrollId: payrollId, 
                _token: '{{ csrf_token() }}' 
            },
           success: function (response) {
                if (response.success) {

                    document.getElementById("total_payroll_amount").innerText = currencySymbol + parseFloat(response.total_payroll).toFixed(2);
                    document.getElementById("total_employees").innerText = response.total_employees;
                    document.getElementById("payroll-darft-date").innerText = draftDate;
                } else {
                    toastr.error("Failed to fetch total payroll.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            }

        });
    }

    $(document).on("click", ".edit-btn", function (e) {
        e.preventDefault();
        let $row = $(this).closest("tr");

        // Make editable
        $row.find(".editable").each(function () {
            let value = $(this).text().trim();
            $(this).html(`<input type="number" class="form-control edit-input" value="${value}">`);
        });

        // Change Edit button to Save
        $(this).replaceWith('<a href="#" class="btn btn-sm btn-themeBlue save-btn">Save</a>');
    });

    let activityLog = []; // Store changes

    $(document).on("click", ".save-btn", function (e) {
        e.preventDefault();
        let $row = $(this).closest("tr");
        let employeeId = $row.find("td:first").text().trim();

        let updatedData = {}; // Store new values
        let changes = []; // Store change logs

        $row.find(".editable").each(function () {
            let $input = $(this).find("input");
            let newValue = $input.val().trim();
            let oldValue = $input.data("original");

            // Store new values
            updatedData[$(this).attr("data-field")] = newValue;

            // If changed, log it
            if (newValue !== oldValue) {
                changes.push({
                    employee_id: employeeId,
                    field: $(this).attr("data-field"),
                    old_value: oldValue,
                    new_value: newValue,
                    updated_by: "{{ auth()->user()->id }}", // Current User ID
                    updated_at: new Date().toISOString()
                });
            }

            // Replace input with text
            $(this).html(newValue);
        });

        // Append changes to log
        if (changes.length > 0) {
            activityLog.push(...changes);
        }

        // Change Save button back to Edit
        $(this).replaceWith('<a href="#" class="btn-lg-icon icon-bg-skyblue edit-btn"><i class="fa-regular fa-pen"></i></a>');
    });

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
                                updateTotal($row,employee.Emp_id); // Update total column
                            }
                        });
                    });
                } else {
                    console.error("response.data is not an array:", response.data);
                }
            }
        });
    }

    // function calculatePensionAndEWT(employeeIds, currency, conversionRate = 1,payrollId) {
    //     $.ajax({
    //         url: '{{route("payroll.calculate.pensionandewt")}}',
    //         method: 'POST',
    //         data: {  
    //             employees: employeeIds,
    //             currency: currency, // Pass currency
    //             conversionRate: conversionRate,
    //             payrollId:payrollId,
    //             _token: '{{ csrf_token() }}' 
    //         },
    //         success: function (response) {
    //             // console.log(response); // Debugging: Check response data

    //             if (response.success) {
    //                 response.data.forEach(function (employee) {
    //                     $("#table-deductions tbody tr").each(function () {
    //                         var $row = $(this);
    //                         if ($row.find("td:eq(0)").text().trim() === employee.Emp_id.toString()) {
    //                             var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
    //                             var pensionFormatted = currencySymbol + employee.pension.toFixed(2);
    //                             var ewtFormatted = currencySymbol + employee.ewt.toFixed(2);

    //                             $row.find("td:eq(7)").text(pensionFormatted);
    //                             $row.find("td:eq(8)").text(ewtFormatted);
    //                             // console.log(employee.empid);
    //                             updateTotal($row,employee.Emp_id); // Update total column
    //                         }
    //                     });
    //                 });
    //             } else {
    //                 console.error("Failed to fetch Pension and EWT data.");
    //             }
    //         },
    //         error: function (xhr, status, error) {
    //             console.error("AJAX Error:", error);
    //         }
    //     });
    // }
    function calculatePensionAndEWT(employeeData, currency, conversionRate = 1, payrollId) {
        $.ajax({
            url: '{{ route("payroll.calculate.pensionandewt") }}',
            method: 'POST',
            data: {
                employees: employeeData, // Now an array of objects
                currency: currency,
                conversionRate: conversionRate,
                payrollId: payrollId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    response.data.forEach(function (employee) {
                        $("#table-deductions tbody tr").each(function () {
                            var $row = $(this);
                            if ($row.find("td:eq(0)").text().trim() === employee.Emp_id.toString()) {
                                var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                                var pensionFormatted = currencySymbol + employee.pension.toFixed(2);
                                var ewtFormatted = currencySymbol + employee.ewt.toFixed(2);

                                $row.find("td:eq(7)").text(pensionFormatted);
                                $row.find("td:eq(8)").text(ewtFormatted);
                                updateTotal($row, employee.Emp_id);
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

    function updateTotal($row,employeeId) {
        // console.log(employeeId);
        var currencySymbol = $row.find("td:eq(3)").text().includes('$') ? '$' : 'MVR ';
        
        var attendance = parseFloat($row.find("td:eq(3)").text().replace(currencySymbol, '')) || 0;
        var cityLedger = parseFloat($row.find("td:eq(4)").text().replace(currencySymbol, '')) || 0;
        var staffShop = parseFloat($row.find("td:eq(5)").text().replace(currencySymbol, '')) || 0;
        var advancedLoan = parseFloat($row.find("td:eq(6)").text().replace(currencySymbol, '')) || 0;
        var pension = parseFloat($row.find("td:eq(7)").text().replace(currencySymbol, '')) || 0;
        var ewt = parseFloat($row.find("td:eq(8)").text().replace(currencySymbol, '')) || 0;
        var other = parseFloat($row.find("td:eq(9)").text().replace(currencySymbol, '')) || 0;
        // console.log(other,"22222");

        var total = attendance + cityLedger + staffShop + advancedLoan + pension + ewt + other;

        // Retrieve stored deductions or create an empty object
        var deductions = JSON.parse(localStorage.getItem("deductions")) || {};
        
        // Store deduction per employee
        deductions[employeeId] = total;
        localStorage.setItem("deductions", JSON.stringify(deductions));

        $row.find("td:eq(10)").text(currencySymbol + total.toFixed(2));
    }

    function getServiceChargedayForEmployee(employeeId) {
        let serviceChargeEntry = distributedServiceCharge.find(emp => emp.id == employeeId);
        // console.log(serviceChargeEntry,"serviceChargeEntry");
        return serviceChargeEntry ? serviceChargeEntry.service_charge_days : "0";
    }

    function getServiceChargeamountForEmployee(employeeId) {
        let serviceChargeEntry = distributedServiceCharge.find(emp => emp.id == employeeId);
        // console.log(serviceChargeEntry,"serviceChargeEntry");
        return serviceChargeEntry ? serviceChargeEntry.amount : "0";
    }

    function employeeList()
    {
        let isChecked = $("#select-all").is(":checked");
        // console.log(isChecked);
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
            // var reviewtable = $("#table-review").DataTable();
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
                    // reviewtable.page.len(totalRecords).draw();
                }
            });
        } 
    }

    $(document).on("click",".add-deduction-btn",function()
    {
        let empId = $(this).data('emp-id'); // Get employee ID from button
        $('#select_emp').val(empId).trigger('change.select2'); // Set employee in select
        $("#addDeduction-modal").modal("show");
    });

</script>
@endsection