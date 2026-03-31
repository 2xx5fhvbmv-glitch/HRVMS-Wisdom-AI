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
                                <li><span>Deductions</span></li>
                                <li><span>Service Charge Distribution</span></li>
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
                                                <div class="mb-3">
                                                    <label for="payrollPeriodSelect" class="form-label fw-600">Select Payroll Period</label>
                                                    @php
                                                        // Find the last (most recent) unpaid period index
                                                        $latestUnpaidIndex = null;
                                                        foreach ($availablePeriods as $idx => $p) {
                                                            if (!$p['is_paid']) $latestUnpaidIndex = $idx;
                                                        }
                                                    @endphp
                                                    <select id="payrollPeriodSelect" class="form-select">
                                                        @foreach($availablePeriods as $index => $period)
                                                            <option value="{{ $period['start_date'] }}|{{ $period['end_date'] }}"
                                                                {{ $period['is_paid'] ? 'disabled' : '' }}
                                                                @if($index === $latestUnpaidIndex && !$period['is_pending_approval']) selected @endif
                                                                data-payroll-id="{{ $period['payroll_id'] ?? '' }}"
                                                                data-status="{{ $period['is_pending_approval'] ? 'pending_approval' : ($period['is_paid'] ? 'paid' : 'unpaid') }}">
                                                                {{ $period['label'] }} {{ $period['status_label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                @if(isset($pendingApprovalPayrolls) && $pendingApprovalPayrolls->isNotEmpty())
                                                <div class="mb-3 mt-3 p-3 rounded" style="background:#fff3cd; border:1px solid #ffc107;">
                                                    <label class="form-label fw-600"><i class="fa-solid fa-clock me-1 text-warning"></i> Payrolls Pending Approval</label>
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($pendingApprovalPayrolls as $pp)
                                                            <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border">
                                                                <div>
                                                                    <strong>{{ \Carbon\Carbon::parse($pp->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($pp->end_date)->format('d M Y') }}</strong>
                                                                    <span class="badge {{ $pp->status === 'approved' ? 'badge-themeSuccess' : 'badge-themeWarning' }} ms-2">{{ ucfirst(str_replace('_', ' ', $pp->status)) }}</span>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-themeBlue view-pending-payroll" data-payroll-id="{{ $pp->id }}">
                                                                    <i class="fa-solid fa-eye me-1"></i> View
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @endif

                                                <div id="datapicker">
                                                    <!-- Hidden input used by JS to read selected date range -->
                                                    <input type="text" class="dateRangeAb datepicker d-none" name="hiddenInput" id="hiddenInput" readonly>
                                                    <p id="startDate" class="d-none"></p>
                                                    <p id="endDate" class="d-none"></p>
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
                                            <th>Position</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Unpaid Leave</th>
                                            <th>Day Off</th>
                                            <th>Other Leaves</th>
                                            <th>Regular OT Hours</th>
                                            <th>Friday OT Hours</th>
                                            <th>Holiday OT Hours</th>
                                            <th>Total OT</th>
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

                        <fieldset data-step="5">
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
                                <table id="table-serviceCharge" class="table table-serviceCharge w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name</th>
                                            <th>Position</th>
                                            <th>Total Working Days</th>
                                            <th>Service Charge</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Total Service Charge:</th>
                                            <th colspan="1" id="total-service-charge" class="fw-bold">{{ Common::GetResortCurrencySymbol() }} 0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <hr class="hr-footer border-0">
                            <button type="button" class="btn btn-themeBlue btn-sm float-end next">Next</button>
                            <a href="#" class="btn btn-themeSkyblue btn-sm float-end previous me-2">Back</a>
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
                            <ul class="nav nav-tabs mb-3" id="reviewTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-review-tab="attendance" type="button">Time & Attendance</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-review-tab="overtime" type="button">Overtime</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-review-tab="earnings" type="button">Earnings</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-review-tab="deductions" type="button">Deductions</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-review-tab="summary" type="button">Summary</button>
                                </li>
                            </ul>
                            <div class="table-responsive">
                                <table id="table-review" class="table table-review   w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employee Name</th>
                                            <th>Position</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Day Off</th>
                                            <th>Service Charge</th>
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
                                        <h6>Basic Earned Salary</h6>
                                        <div class="value" id="total_earned_salary"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Allowances</h6>
                                        <div class="value" id="total_allowances_conf"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Overtime Pay</h6>
                                        <div class="value" id="total_ot_conf"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Service Charge</h6>
                                        <div class="value" id="total_service_charge_conf"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block" style="border-top:1px solid #28a745; background:#f0fff4;">
                                        <h6 style="color:#28a745;">Total Earnings</h6>
                                        <div class="value" id="total_earnings_conf" style="font-weight:600; color:#28a745;"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block" style="border-top:1px solid #dc3545; background:#fff5f5;">
                                        <h6 style="color:#dc3545;">Total Deductions</h6>
                                        <div class="value" id="total_deductions_conf" style="font-weight:600; color:#dc3545;"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Total Employees</h6>
                                        <div class="value" id="total_employees"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block">
                                        <h6>Payroll Draft Date</h6>
                                        <div class="value" id="payroll-darft-date"></div>
                                    </div>
                                    <div class="bg-themeGrayLight payrollConf-block" style="border-top:2px solid #0d6efd; background:#f0f4ff;">
                                        <h6 style="color:#0d6efd;">Net Payroll (Total Earnings - Total Deductions)</h6>
                                        <div class="value" id="total_payroll_amount" style="font-size:1.5rem; font-weight:700; color:#0d6efd;"></div>
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
                                                    <li>Deductions</li>
                                                </ul>
                                            </div>
                                            <div class="col-xxl-6 col-xl-5 col-lg-12 col-sm-6">
                                                <ul class="listing-wrapper ">
                                                    <li>Service Charge Distribution</li>
                                                    <li>Review</li>
                                                    <!-- <li>Statistics</li> -->
                                                    <li>Payroll Confirmation</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Approval Timeline --}}
                            <div class="col-lg-12 mb-3" id="approval-timeline-wrapper">
                                <div class="bg-themeGrayLight p-3 rounded">
                                    <h6 class="fw-600 mb-3">Approval Status</h6>
                                    <ul class="manning-timeline text-start" id="approval-timeline">
                                        <li id="approval-step-1" class="">
                                            <span>Reviewed by Finance EXCOM</span>
                                            <small class="d-block text-muted" id="approval-step-1-info"></small>
                                        </li>
                                        <li id="approval-step-2" class="">
                                            <span>Reviewed by HR EXCOM</span>
                                            <small class="d-block text-muted" id="approval-step-2-info"></small>
                                        </li>
                                        <li id="approval-step-3" class="">
                                            <span>Approved by GM</span>
                                            <small class="d-block text-muted" id="approval-step-3-info"></small>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-themeSkyblue btn-sm" id="downloadPayrollExcel">
                                    <i class="fa-solid fa-file-excel me-1"></i> Download Excel
                                </button>
                            </div>
                            <hr class="hr-footer border-0">

                            {{-- After locked / Rejection message --}}
                            <div class="alert alert-success d-none text-center mb-3" id="payrollLockedMessage">
                                <i class="fa-solid fa-circle-check me-1"></i> This payroll has been approved and locked. Thank you.
                            </div>

                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <a href="#" class="btn btn-themeSkyblue btn-sm previous">Back</a>
                                <button type="button" class="btn btn-themeGray btn-sm" id="saveAsDraft">Save as Draft</button>

                                {{-- Supervisor: Send for Approval --}}
                                <button type="button" class="btn btn-themeBlue btn-sm d-none" id="sendForApproval">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Send Payroll for Approval
                                </button>

                                {{-- Approver: Approve/Reject --}}
                                <button type="button" class="btn btn-themeBlue btn-sm d-none" id="approvePayroll">
                                    <i class="fa-solid fa-check me-1"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger btn-sm d-none" id="rejectPayroll">
                                    <i class="fa-solid fa-times me-1"></i> Reject
                                </button>

                                {{-- Supervisor: Confirm and Lock (only after all approvals) --}}
                                <button type="button" class="btn btn-themeBlue btn-sm d-none" id="showLockModal">
                                    <i class="fa-solid fa-lock me-1"></i> Confirm and Lock Payroll
                                </button>
                                <button type="submit" class="d-none" id="submit"></button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- Confirm & Lock Payroll Modal --}}
    <div class="modal fade" id="confirmLockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>You are about to permanently lock this payroll.</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Once locked:</p>
                    <ul class="mb-3" style="list-style: none; padding-left: 0;">
                        <li class="mb-2"><i class="fa-solid fa-lock text-danger me-2"></i> Payroll data cannot be edited or reversed</li>
                        <li class="mb-2"><i class="fa-solid fa-calculator text-danger me-2"></i> All earnings, deductions, and calculations become final</li>
                        <li class="mb-2"><i class="fa-solid fa-money-bill-wave text-danger me-2"></i> This action may impact employee payments and financial records</li>
                    </ul>
                    <div class="alert alert-warning mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i> Ensure all entries are verified before proceeding.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-themeGray" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmLockBtn">
                        <i class="fa-solid fa-lock me-1"></i> Confirm & Lock
                    </button>
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

    {{-- Staff Shop Transaction Details Modal --}}
    <div class="modal fade" id="staffShopModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Staff Shop Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0"></div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .leave-tooltip{position:fixed;background:#2C2C2C;color:#fff;padding:12px 16px;border-radius:10px;font-size:13px;z-index:9999;min-width:180px;max-width:280px;box-shadow:0 4px 20px rgba(0,0,0,.3);display:none;pointer-events:none}
    .leave-tooltip.show{display:block!important}
    .modal-body .leave-tooltip{position:static!important;display:block!important;background:#fff!important;color:#333!important;padding:0!important;box-shadow:none!important;pointer-events:auto!important;max-width:100%!important;min-width:auto!important;border-radius:0!important}
    @keyframes payroll-spin{to{transform:rotate(360deg)}}
    #table-review .col-overtime, #table-review .col-earnings, #table-review .col-deductions, #table-review .col-summary { display: none; }
    #table-review.show-overtime .col-attendance { display: none; }
    #table-review.show-overtime .col-overtime { display: table-cell; }
    #table-review.show-earnings .col-attendance { display: none; }
    #table-review.show-earnings .col-overtime { display: none; }
    #table-review.show-earnings .col-earnings { display: table-cell; }
    #table-review.show-deductions .col-attendance { display: none; }
    #table-review.show-deductions .col-overtime { display: none; }
    #table-review.show-deductions .col-deductions { display: table-cell; }
    #table-review.show-summary .col-attendance { display: none; }
    #table-review.show-summary .col-overtime { display: none; }
    #table-review.show-summary .col-summary { display: table-cell; }
    .staff-shop-popover{position:fixed;z-index:99999;background:#fff;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.18);border:1px solid #e0e0e0;max-width:380px;pointer-events:none}
    .staff-shop-popover .leave-tooltip{position:static!important;display:block!important;background:#fff!important;color:#333!important;padding:0!important;box-shadow:none!important;pointer-events:auto!important;max-width:100%!important;min-width:auto!important;border-radius:8px!important}
    .leave-tooltip::after{content:'';position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);border-left:8px solid transparent;border-right:8px solid transparent;border-top:8px solid #2C2C2C}
    .leave-tooltip.arrow-top::after{bottom:auto;top:-8px;border-top:none;border-bottom:8px solid #2C2C2C}
    .leave-tooltip .tooltip-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .leave-tooltip .tooltip-type{font-weight:600;font-size:14px}
    .leave-tooltip .tooltip-count{display:inline-block;padding:2px 10px;border-radius:4px;font-size:12px;font-weight:600}
    .leave-tooltip .tooltip-info{line-height:1.8}
    .leave-tooltip .tooltip-info div{margin-bottom:2px;font-size:12px}
    .leave-tooltip .tooltip-info .info-label{color:#ccc;margin-right:8px}
    .leave-tooltip .tooltip-info .info-value{color:#fff;font-weight:500}
    .leave-type-badge{transition:transform .15s;cursor:pointer}
    .leave-type-badge:hover{transform:scale(1.05)}
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
    var selectedEmployeeSet = new Set(); // Persist selection across pages

    // Format number with commas: 9999.98 → 9,999.98
    function fmtNum(val) {
        var num = parseFloat(val) || 0;
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Helper: get selected employee IDs (numeric) — uses checkboxes or falls back to localStorage
    function getSelectedEmployeeIds() {
        var ids = [];
        $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
            ids.push($(this).val());
        });
        // Fallback 1: use persistent set
        if (ids.length === 0 && selectedEmployeeSet.size > 0) {
            ids = Array.from(selectedEmployeeSet);
        }
        // Fallback 2: use localStorage
        if (ids.length === 0) {
            try {
                var saved = JSON.parse(localStorage.getItem("selectedEmployeeNumericIds") || "[]");
                if (saved.length > 0) {
                    ids = saved.map(String);
                    saved.forEach(function(id) { selectedEmployeeSet.add(String(id)); });
                }
            } catch(e) {}
        }
        // Fallback 3: fetch from payroll DB
        if (ids.length === 0) {
            var payrollId = localStorage.getItem("payroll_id");
            if (payrollId) {
                $.ajax({
                    url: '{{ route("fetch.time.attendance") }}',
                    method: 'POST',
                    async: false,
                    data: { payrollId: payrollId, _token: '{{ csrf_token() }}', getEmployeesOnly: true },
                    success: function(res) {
                        if (res.success && res.employee_ids) {
                            ids = res.employee_ids.map(String);
                            res.employee_ids.forEach(function(id) { selectedEmployeeSet.add(String(id)); });
                            localStorage.setItem("selectedEmployeeNumericIds", JSON.stringify(res.employee_ids));
                        }
                    }
                });
            }
        }
        return ids;
    }

    // Check if view-only mode (approvers viewing payroll)
    var isViewOnly = new URLSearchParams(window.location.search).get('viewonly') === '1';
    var resumePayrollId = new URLSearchParams(window.location.search).get('resume');

    // If resuming a payroll, set localStorage
    if (resumePayrollId) {
        localStorage.setItem('payroll_id', resumePayrollId);
        if (!localStorage.getItem('currentStep')) {
            localStorage.setItem('currentStep', '7');
        }
    }

    $(document).ready(function () {
        let resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
        if (typeof $.fn.parsley !== 'function') {
            console.warn('Parsley.js is not loaded — form validation may not work');
        }

        // View-only mode: disable all editable elements
        if (isViewOnly) {
            setTimeout(function() {
                $('input, select, textarea').not('#searchField, #review-search').prop('disabled', true);
                $('.editable').off('dblclick click');
                $('.add-deduction-btn, #distribute-service-charge, #upload-city-ledger-button, #OverTimeform, #download-city-ledger-template, #upload-city-ledger, #saveAsDraft').hide();
                // Hide Action column in deductions table
                $('#table-deductions th:last-child, #table-deductions td:last-child').hide();
                // Disable checkboxes in employee selection
                $('#payroll-employees input[type="checkbox"]').prop('disabled', true);
                $('#selectAll, .unselect-all').hide();
                $('.previous').show();
            }, 1000);

            // Disable checkboxes when employee table reloads
            $(document).on('draw.dt', '#payroll-employees', function() {
                $('#payroll-employees input[type="checkbox"]').prop('disabled', true);
            });

            // Hide action column when deductions table reloads
            $(document).on('draw.dt', '#table-deductions', function() {
                $('#table-deductions th:last-child, #table-deductions td:last-child').hide();
            });
        }

        // Set the cutoff day (from Laravel config)
        const cutoffDay = "{{ $cutoffDay ?? 25 }}";

        // Get initial period from dropdown
        let selectedPeriod = $('#payrollPeriodSelect').val();
        let periodParts = selectedPeriod ? selectedPeriod.split('|') : [];
        let startDate = periodParts.length === 2 ? moment(periodParts[0], "YYYY-MM-DD") : moment().subtract(1, 'month').date(cutoffDay);
        let endDate = periodParts.length === 2 ? moment(periodParts[1], "YYYY-MM-DD") : moment().date(cutoffDay).subtract(1, 'day');

        // Fallback for invalid date scenarios
        if (!startDate.isValid()) startDate = moment().subtract(1, 'month').startOf('month');
        if (!endDate.isValid()) endDate = moment().startOf('month').subtract(1, 'day');

        // Initialize daterangepicker
        function initDateRangePicker(start, end) {
            $("#hiddenInput").daterangepicker({
                autoApply: true,
                startDate: start,
                endDate: end,
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
            }, function (s, e) {
                // dates stored in hidden input; no visible output needed
            });
        }

        // Only init daterangepicker with dropdown value if NOT restoring a saved step
        // (restore logic will set hiddenInput from localStorage/DB instead)
        var _savedStep = parseInt(localStorage.getItem("currentStep")) || 0;
        var _savedPayrollId = localStorage.getItem("payroll_id");
        if (_savedStep <= 1 || !_savedPayrollId) {
            initDateRangePicker(startDate, endDate);
        }

        // Update daterangepicker when period dropdown changes
        $('#payrollPeriodSelect').on('change', function() {
            var val = $(this).val();
            var parts = val.split('|');
            var newStart = moment(parts[0], "YYYY-MM-DD");
            var newEnd = moment(parts[1], "YYYY-MM-DD");
            initDateRangePicker(newStart, newEnd);
            // dates stored in hidden input; no visible output needed
        });

        // dates are tracked via hiddenInput value only
        $(".select2t-none").select2();

        // ── Restore step on page refresh ──
        var savedStep = parseInt(localStorage.getItem("currentStep")) || 0;
        var savedPayrollId = localStorage.getItem("payroll_id");
        if (savedStep > 1 && savedPayrollId) {
            // Jump to the saved step
            $("fieldset").each(function(index) {
                var stepNum = $(this).data('step');
                if (stepNum < savedStep) {
                    $(this).css({ 'visibility': 'hidden', 'position': 'absolute', 'opacity': 0 });
                    $("#progressbar li").eq(index).addClass("active");
                } else if (stepNum == savedStep) {
                    $(this).css({ 'visibility': 'visible', 'opacity': 1, 'position': 'relative' });
                    $("#progressbar li").eq(index).addClass("active current");
                } else {
                    $(this).css({ 'visibility': 'hidden', 'position': 'absolute', 'opacity': 0 });
                }
            });

            // Restore selected employees (numeric IDs) from localStorage into the persistent Set
            var savedNumericIds = localStorage.getItem("selectedEmployeeNumericIds");
            if (savedNumericIds) {
                try {
                    var numIds = JSON.parse(savedNumericIds);
                    numIds.forEach(function(id) {
                        selectedEmployeeSet.add(String(id));
                    });
                } catch(e) {}
            }

            // Restore date range from localStorage into hidden input (overwrite dropdown default)
            var savedDateRange = localStorage.getItem("payroll_dateRange");
            if (savedDateRange) {
                $("#hiddenInput").val(savedDateRange);
            }

            // If no employees in Set or no date range, fetch from DB before loading step data
            if ((selectedEmployeeSet.size === 0 || !savedDateRange) && savedPayrollId) {
                $.ajax({
                    url: '{{ route("fetch.time.attendance") }}',
                    method: 'POST',
                    async: false,
                    data: { payrollId: savedPayrollId, _token: '{{ csrf_token() }}', getEmployeesOnly: true },
                    success: function(res) {
                        if (res.success && res.employee_ids) {
                            res.employee_ids.forEach(function(id) { selectedEmployeeSet.add(String(id)); });
                            localStorage.setItem("selectedEmployeeNumericIds", JSON.stringify(res.employee_ids));
                        }
                        if (res.date_range) {
                            $("#hiddenInput").val(res.date_range);
                            localStorage.setItem("payroll_dateRange", res.date_range);
                        }
                    }
                });
            }

            // Trigger data load for current step
            setTimeout(function() {
                if (savedStep === 3) {
                    getstep3data('');
                } else if (savedStep === 4) {
                    getstep5data(currency, 1); // Step 4 = Deductions
                } else if (savedStep === 5) {
                    getstep4data('', currency, 1); // Step 5 = Service Charge
                } else if (savedStep === 6) {
                    getstep6data(currency, 1);
                } else if (savedStep === 7) {
                    // Load confirmation summary
                    calculatePayrollSummary(currency, 1);
                    loadApprovalStatus();
                }
            }, 500);
        }

        // getSelectedEmployeeIds() is defined in outer scope above $(document).ready

        $(".next").click(async function (e) {
            e.preventDefault();
            try {
            var $currentFieldset = $(this).closest("fieldset");
            var currentStep = $currentFieldset.data('step');
            console.log('Next clicked, step:', currentStep);

            // View-only mode: just navigate forward without saving
            if (isViewOnly) {
                var nextStep = currentStep + 1;
                localStorage.setItem("currentStep", nextStep);
                // Load data for next step
                if (nextStep === 3) getstep3data('');
                else if (nextStep === 4) getstep5data(currency, 1);
                else if (nextStep === 5) getstep4data('', currency, 1);
                else if (nextStep === 6) getstep6data(currency, 1);
                else if (nextStep === 7) calculatePayrollSummary(currency, 1);
                moveToNextStep($currentFieldset);
                return;
            }

            if (currentStep === 1) {
                var dateRange = $("#hiddenInput").val();
                console.log('Step 1 Continue - hiddenInput value:', dateRange);

                // If hiddenInput is empty, try to get from dropdown
                if (!dateRange || dateRange.trim() === '') {
                    var selectedPeriod = $('#payrollPeriodSelect').val();
                    if (selectedPeriod) {
                        var parts = selectedPeriod.split('|');
                        if (parts.length === 2) {
                            var s = moment(parts[0], "YYYY-MM-DD");
                            var e = moment(parts[1], "YYYY-MM-DD");
                            dateRange = s.format("DD-MM-YYYY") + ' - ' + e.format("DD-MM-YYYY");
                            $("#hiddenInput").val(dateRange);
                            console.log('Fallback from dropdown:', dateRange);
                        }
                    }
                }

                var dates = dateRange ? dateRange.split(' - ') : [];

                if (!dateRange || dates.length !== 2) {
                    toastr.error("Please select a valid payroll period before proceeding.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var startDate = moment(dates[0], "DD-MM-YYYY", true).format("YYYY-MM-DD");
                var endDate = moment(dates[1], "DD-MM-YYYY", true).format("YYYY-MM-DD");

                // Check if this period has a payroll in pending_approval/approved state
                var pendingPayrolls = @json($pendingApprovalPayrolls ?? []);
                var matchingPending = pendingPayrolls.find(function(p) {
                    return p.start_date === startDate && p.end_date === endDate;
                });
                if (matchingPending) {
                    // Redirect to step 7 readonly
                    localStorage.setItem('payroll_id', matchingPending.id);
                    localStorage.setItem('currentStep', '7');
                    window.location.href = "{{ route('payroll.run') }}?resume=" + matchingPending.id + "&viewonly=1";
                    return;
                }

                // Prepare data for draft payroll entry
                var payrollData = {
                    start_date: startDate,
                    end_date: endDate,
                    status: "draft",
                    _token: '{{ csrf_token() }}'
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
                            localStorage.setItem("payroll_dateRange", $("#hiddenInput").val());

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

                // Sync current page checkboxes to the persistent Set before collecting
                updateSelectedCount();

                var selectedEmployees = [];
                var selectedEmployeesIds = [];

                // Collect from persistent Set (works across all pages)
                selectedEmployeeSet.forEach(function(empId) {
                    selectedEmployeesIds.push({ id: empId });
                });

                // Also build selectedEmployees from visible rows for localStorage cache
                $("#payroll-employees tbody tr").each(function () {
                    let checkbox = $(this).find(".form-check-input");
                    if (checkbox.prop("checked")) {
                        selectedEmployees.push({
                            id: $(this).find("td:eq(1)").text().trim(),
                            name: $(this).find("td:eq(2)").text().trim(),
                            position: $(this).find("td:eq(3)").text().trim(),
                            department: $(this).find("td:eq(4)").text().trim(),
                            section: $(this).find("td:eq(5)").text().trim(),
                            paymentMethod: $(this).find("td:eq(6)").text().trim()
                        });
                    }
                });

                if (selectedEmployeeSet.size === 0) {
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
                        employee_ids: Array.from(selectedEmployeeSet),
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
                            // Save numeric IDs for API calls on restore
                            localStorage.setItem("selectedEmployeeNumericIds", JSON.stringify(Array.from(selectedEmployeeSet)));

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

                    // Skip empty DataTable rows ("No data available in table")
                    if ($row.find("td.dataTables_empty").length > 0 || $row.find("td").length <= 1) {
                        return; // continue to next row
                    }

                    let employeeId = $row.find("td:eq(0)").text().trim(); // Employee ID

                    // Skip rows with invalid/empty employee IDs
                    if (!employeeId || employeeId === '' || !/^[A-Za-z0-9_-]+$/.test(employeeId)) {
                        return; // continue to next row
                    }

                    // T&A columns: 0:ID, 1:Name, 2:Position, 3:Present, 4:Absent, 5:UnpaidLeave, 6:DayOff, 7:LeaveTypes, 8:RegOT, 9:FridayOT, 10:HolOT, 11:TotalOT
                    let present = $row.find("td:eq(3) input").length > 0 ?
                        parseInt($row.find("td:eq(3) input").val().trim()) || 0 :
                        parseInt($row.find("td:eq(3)").text().trim()) || 0;

                    let absent = $row.find("td:eq(4) input").length > 0 ?
                        parseInt($row.find("td:eq(4) input").val().trim()) || 0 :
                        parseInt($row.find("td:eq(4)").text().trim()) || 0;

                    let unpaidLeave = parseInt($row.find("td:eq(5)").text().trim()) || 0;
                    let dayOff = parseInt($row.find("td:eq(6)").text().trim()) || 0;

                    let regularOT = $row.find("td:eq(8) input").length > 0 ?
                        parseFloat($row.find("td:eq(8) input").val().trim()) || 0 :
                        parseFloat($row.find("td:eq(8)").text().trim()) || 0;

                    let fridayOT = $row.find("td:eq(9) input").length > 0 ?
                        parseFloat($row.find("td:eq(9) input").val().trim()) || 0 :
                        parseFloat($row.find("td:eq(9)").text().trim()) || 0;

                    let holidayOT = $row.find("td:eq(10) input").length > 0 ?
                        parseFloat($row.find("td:eq(10) input").val().trim()) || 0 :
                        parseFloat($row.find("td:eq(10)").text().trim()) || 0;

                    let totalOT = $row.find("td:eq(11) input").length > 0 ?
                        parseFloat($row.find("td:eq(11) input").val().trim()) || 0 :
                        parseFloat($row.find("td:eq(11)").text().trim()) || 0;

                    AttendaceData.push({
                        id: employeeId,
                        name: $row.find("td:eq(1)").text().trim(),
                        position: $row.find("td:eq(2)").text().trim(),
                        present: present,
                        absent: absent,
                        unpaidLeave: unpaidLeave,
                        dayOff: dayOff,
                        leaveTypes: $row.find("td:eq(7)").text().trim(),
                        regularOT: regularOT,
                        fridayOT: fridayOT,
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
            
            // Step 5: Save Service charges for employees
            if (currentStep === 5) {

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

                    var empId = $(this).find("td:eq(0)").text().trim();
                    // Get USD amount from distributedServiceCharge array (stored in USD)
                    var scEntry = distributedServiceCharge.find(e => e.id === empId);
                    var scAmountUSD = scEntry ? parseFloat(scEntry.amount) : 0;

                    ServiceChargesData.push({
                        id: empId,
                        name: $(this).find("td:eq(1)").text().trim(),
                        position: $(this).find("td:eq(2)").text().trim(),
                        totalWorkingDays: parseInt($(this).find("td:eq(3)").text().trim()) || 0,
                        serviceCharge: scAmountUSD, // Always in USD for DB storage
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
                            toastr.success("Service Charges added to payroll successfully!", 'Success',{
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

            // Step 4: Save Deductions for employees
            if (currentStep === 4) {
                var payrollId = localStorage.getItem("payroll_id"); // Retrieve stored payroll ID
                var selectedEmployees = localStorage.getItem("selectedEmployees"); // Retrieve stored payroll ID
                if (!payrollId) {
                    toastr.error("Payroll draft not found. Please start again.", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                var DeductionData = [];

                function stripCurrency(val) {
                    return parseFloat(val.replace(/[^0-9.\-]/g, '')) || 0;
                }

                $("#table-deductions tbody tr").each(function () {
                    if ($(this).find("td.dataTables_empty").length > 0) return;
                    DeductionData.push({
                        id: $(this).find("td:eq(0)").text().trim(),
                        name: $(this).find("td:eq(1)").text().trim(),
                        department: $(this).find("td:eq(2)").text().trim(),
                        attendanceDeduction: stripCurrency($(this).find("td:eq(3)").text()),
                        cityLedger: stripCurrency($(this).find("td:eq(4)").text()),
                        staffShop: stripCurrency($(this).find("td:eq(5)").text()),
                        advanceLoan: stripCurrency($(this).find("td:eq(6)").text()),
                        pension: stripCurrency($(this).find("td:eq(7)").text()),
                        ewt: stripCurrency($(this).find("td:eq(8)").text()),
                        other: stripCurrency($(this).find("td:eq(9)").text()),
                        total: stripCurrency($(this).find("td:eq(10)").text())
                    });
                });

                console.log('Deduction data count:', DeductionData.length, 'Sample:', DeductionData[0]);

                if (DeductionData.length === 0) {
                    toastr.error("Something is wrong.some data are missing", 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                // Send AJAX request to save deductions
                console.log('Saving deductions...', DeductionData.length, 'records');
                $.ajax({
                    url: '{{ route("payroll.saveDeductions") }}',
                    method: 'POST',
                    data: {
                        payroll_id: payrollId,
                        DeductionData : DeductionData,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        console.log('Save deductions response:', response);
                        if (response.success) {
                            toastr.success("Deductions saved!", 'Success', {
                                positionClass: 'toast-bottom-right'
                            });
                            // Load service charge data for step 5, then move
                            loadCurrencyRates(searchTerm = "", resortid, currency, currentStep).then(() => {
                                moveToNextStep($currentFieldset);
                            }).catch(error => {
                                console.error("Error loading currency rates:", error);
                                moveToNextStep($currentFieldset);
                            });
                            getstep6data(currency, 1);
                        } else {
                            toastr.error(response.message || "Failed to save deductions.", 'Error', {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (xhr) {
                        console.error('Save deductions error:', xhr.status, xhr.responseText);
                        toastr.error("Error saving deductions.", 'Error', {
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
                    function stripVal(td) { return parseFloat($(td).text().replace(/[^0-9.\-]/g, '')) || 0; }
                    // Use class-based selectors to avoid column index issues with hidden tabs
                    var $earningsCols = $row.find('td.col-earnings');
                    var $deductionsCols = $row.find('td.col-deductions');
                    var $summaryCols = $row.find('td.col-summary');
                    const rowData = {
                        id: $row.find("td:eq(0)").text().trim(),
                        name: $row.find("td:eq(1)").text().trim(),
                        position: $row.find("td:eq(2)").text().trim(),
                        present: stripVal($row.find("td:eq(3)")),
                        absent: stripVal($row.find("td:eq(4)")),
                        serviceCharge: stripVal($earningsCols.eq(0)), // SC is first earnings col
                        overtimeNormal: stripVal($row.find("td.col-overtime:eq(0)")),
                        overtimeFriday: stripVal($row.find("td.col-overtime:eq(1)")),
                        overtimeHoliday: stripVal($row.find("td.col-overtime:eq(2)")),
                        overtimeTotal: stripVal($row.find("td.col-overtime:eq(3)")),
                        earningsBasic: stripVal($earningsCols.eq(1)), // Earned is second earnings col
                        earnedSalary: stripVal($earningsCols.eq(1)),
                        earningsAllowance: 0,
                        earningsNormal: stripVal($earningsCols.last()), // Total Earnings is last earnings col
                        totalDeductions: stripVal($deductionsCols.eq($deductionsCols.length - 2)), // Total Deductions
                        netSalary: stripVal($deductionsCols.last()), // Net Salary
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
                            toastr.success("Earning Reviews added to payroll successfully!", 'Success',{
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
            } catch(err) {
                console.error('Payroll next step error:', err);
                toastr.error('An error occurred: ' + err.message, 'Error', { positionClass: 'toast-bottom-right' });
            }
        });

        // Function to transition to the next step
        function moveToNextStep($currentFieldset) {
            var $nextFieldset = $currentFieldset.next("fieldset");

            if ($nextFieldset.length > 0) {
                var nextStep = $nextFieldset.data('step');
                localStorage.setItem("currentStep", nextStep);

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
                        // Load approval status when arriving at step 7
                        if (nextStep === 7) {
                            loadApprovalStatus();
                        }
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
                getstep5data(updatedCurrency, conversionRate); // Step 3 → Step 4 (Deductions)
            }
            else if(currentStep == 4)
            {
                getstep4data(searchTerm, updatedCurrency, conversionRate); // Step 4 → Step 5 (Service Charge)
            }
            else if(currentStep == 5)
            {
                getstep6data(updatedCurrency, conversionRate); // Step 5 → Step 6 (Review)
            }

            else if(currentStep == 6)
            {
                calculatePayrollSummary(updatedCurrency, conversionRate);
            }
        }
        
        // distribute service charges
        $("#distribute-service-charge").click(async function (e) {
            e.preventDefault();

            var totalServiceCharge = parseFloat($("#total-ser").val().replace(currencySymbol, '').replace(',', ''));
            if (isNaN(totalServiceCharge) || totalServiceCharge <= 0) {
                toastr.error("Please enter a valid service charge amount.", 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            var rates = await fetchRates(resortid);
            // Service charge is entered in display currency — convert to USD for internal storage
            var serviceChargeUSD = (currency === 'MVR')
                ? totalServiceCharge * rates.mvr_to_usd
                : totalServiceCharge;
            // For display, use the entered amount directly (already in display currency)
            var serviceCharge = totalServiceCharge;

            var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
            var selectedEmployees = JSON.parse(localStorage.getItem("selectedEmployeesIds") || '[]');

            distributedServiceCharge = [];

            // 🔽 Step 2: Gather only eligible employees based on benefit grid
            var eligibleEmployees = [];
            try {
                var result = await getEligibleEmployeesFromBackend(selectedEmployees);
                if (Array.isArray(result)) eligibleEmployees = result;
            } catch(err) {
                console.error('Eligible employees fetch failed:', err);
            }
            console.log('Selected employees:', selectedEmployees);
            console.log('Eligible employees:', eligibleEmployees);

            // If no eligible employees found, fall back to all employees in table
            var useAllEmployees = (!eligibleEmployees || eligibleEmployees.length === 0);
            if (useAllEmployees) {
                console.warn('No eligible employees from backend, distributing to all employees.');
            }

            // Calculate total workdays for eligible employees only
            var totalWorkdays = 0;
            $("#table-serviceCharge tbody tr").each(function () {
                var $row = $(this);
                var employeeId = $row.find("td:eq(0)").text().trim();

                if (useAllEmployees || eligibleEmployees.includes(employeeId)) {
                    var workdays = parseFloat($row.find(".workdays").text()) || 0;
                    totalWorkdays += workdays;
                }
            });

            console.log('Total workdays:', totalWorkdays);

            if (totalWorkdays === 0) {
                toastr.error("No working days found. Cannot distribute service charge.", 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }

            // Distribute service charge to eligible employees only
            var distributedTotal = 0;
            var distributedTotalUSD = 0;
            var eligibleRows = [];
            $("#table-serviceCharge tbody tr").each(function () {
                var $row = $(this);
                var employeeId = $row.find("td:eq(0)").text().trim();

                if (useAllEmployees || eligibleEmployees.includes(employeeId)) {
                    var workdays = parseFloat($row.find(".workdays").text()) || 0;
                    var employeeShare = Math.floor(((serviceCharge / totalWorkdays) * workdays) * 100) / 100;
                    var employeeShareUSD = Math.floor(((serviceChargeUSD / totalWorkdays) * workdays) * 100) / 100;
                    distributedTotal += employeeShare;
                    distributedTotalUSD += employeeShareUSD;

                    eligibleRows.push({ $row: $row, employeeId: employeeId, workdays: workdays, share: employeeShare, shareUSD: employeeShareUSD });
                } else {
                    $row.find(".service-charge").text(`${currencySymbol}0.00`);
                }
            });

            // Adjust last eligible employee to absorb rounding difference
            if (eligibleRows.length > 0) {
                var lastRow = eligibleRows[eligibleRows.length - 1];
                var roundingDiff = Math.round((serviceCharge - distributedTotal) * 100) / 100;
                var roundingDiffUSD = Math.round((serviceChargeUSD - distributedTotalUSD) * 100) / 100;
                lastRow.share += roundingDiff;
                lastRow.shareUSD += roundingDiffUSD;
                distributedTotal += roundingDiff;
            }

            // Apply values to rows
            eligibleRows.forEach(function(item) {
                item.$row.find(".service-charge").text(`${currencySymbol}${fmtNum(item.share)}`);
                distributedServiceCharge.push({
                    id: item.employeeId,
                    service_charge_days: item.workdays,
                    amount: item.shareUSD.toFixed(2)
                });
            });

            $("#total-service-charge").text(`${currencySymbol}${fmtNum(distributedTotal)}`);
        });


        $("#download-city-ledger-template").click(function (e) {
            e.preventDefault();

            // Fetch selected employees from persistent set
            var selectedEmployees = [];
            if (selectedEmployeeSet.size > 0) {
                $("#payroll-employees tbody tr").each(function () {
                    var empId = $(this).find("td:eq(1)").text().trim();
                    var empName = $(this).find("td:eq(2)").text().trim();
                    if (selectedEmployeeSet.has($(this).find("input[type='checkbox']").val())) {
                        selectedEmployees.push({ id: empId, name: empName });
                    }
                });
            }

            // Fallback: try checked checkboxes
            if (selectedEmployees.length === 0) {
                $("#payroll-employees tbody input[type='checkbox']:checked").each(function () {
                    var employeeId = $(this).closest("tr").find("td:eq(1)").text().trim();
                    var employeeName = $(this).closest("tr").find("td:eq(2)").text().trim();
                    selectedEmployees.push({ id: employeeId, name: employeeName });
                });
            }

            // Fallback: use deductions table if on that step
            if (selectedEmployees.length === 0) {
                $("#table-deductions tbody tr").each(function () {
                    var empId = $(this).find("td:eq(0)").text().trim();
                    var empName = $(this).find("td:eq(1)").text().trim();
                    if (empId) selectedEmployees.push({ id: empId, name: empName });
                });
            }

            if (selectedEmployees.length === 0) {
                toastr.error("No employees found. Please select employees first.", 'Error', {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Create Excel template with styling
            var workbook = XLSX.utils.book_new();
            var worksheetData = [
                ["Employee ID", "Employee Name", "City Ledger Amount (USD)"]
            ];
            selectedEmployees.forEach(function (employee) {
                worksheetData.push([employee.id, employee.name, 0]);
            });

            var worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

            // Set column widths
            worksheet['!cols'] = [
                { wch: 15 },  // Employee ID
                { wch: 30 },  // Employee Name
                { wch: 25 }   // City Ledger Amount
            ];

            // Lock Employee ID and Name columns (read-only hint via cell protection)
            // Note: SheetJS free doesn't support full cell styling, but we set number format for amount column
            for (var i = 1; i <= selectedEmployees.length; i++) {
                var amountCell = worksheet[XLSX.utils.encode_cell({r: i, c: 2})];
                if (amountCell) {
                    amountCell.t = 'n'; // Set as number type
                    amountCell.v = 0;
                }
            }

            XLSX.utils.book_append_sheet(workbook, worksheet, "City Ledger");
            XLSX.writeFile(workbook, "City_Ledger_Template.xlsx");

            toastr.success("Template downloaded with " + selectedEmployees.length + " employees.", 'Success', {
                positionClass: 'toast-bottom-right'
            });
        });

        $("#upload-city-ledger-button").click(function () {
            $("#upload-city-ledger").click();
        });
    
        // Function to handle file upload
        $("#upload-city-ledger").change(async function (e) {
            var file = e.target.files[0];
            if (!file) return;

            // Validate file type
            var validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'];
            if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
                toastr.error("Please upload a valid Excel file (.xlsx, .xls, or .csv).", 'Error', { positionClass: 'toast-bottom-right' });
                $(this).val('');
                return;
            }

            var rates = await fetchRates(resortid);
            var currencySymbol = (selectedCurrency === 'Dollar') ? '$' : 'MVR ';

            var reader = new FileReader();
            reader.onload = function (e) {
                try {
                    var data = new Uint8Array(e.target.result);
                    var workbook = XLSX.read(data, { type: 'array' });
                    var worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                    if (jsonData.length <= 1) {
                        toastr.warning("Excel file is empty or has no data rows.", 'Warning', { positionClass: 'toast-bottom-right' });
                        return;
                    }

                    var updatedCount = 0;
                    var notFoundIds = [];

                    jsonData.slice(1).forEach(function (row) {
                        if (!row[0]) return;
                        var employeeId = String(row[0]).trim();
                        var cityLedgerUSD = parseFloat(row[2]) || 0;

                        // Store original USD value
                        cityLedgerData[employeeId] = cityLedgerUSD;

                        // Convert based on selected currency
                        var cityLedgerFinal = (selectedCurrency === "MVR")
                            ? cityLedgerUSD * (rates.usd_to_mvr || 15.42)
                            : cityLedgerUSD;

                        var found = false;
                        $("#table-deductions tbody tr").each(function () {
                            var $row = $(this);
                            if ($row.find("td:eq(0)").text().trim() === employeeId) {
                                $row.find("td:eq(4)").text(currencySymbol + fmtNum(cityLedgerFinal));
                                updateTotal($row, employeeId);
                                found = true;
                                updatedCount++;
                            }
                        });

                        if (!found) notFoundIds.push(employeeId);
                    });

                    if (updatedCount > 0) {
                        toastr.success(updatedCount + " employee(s) city ledger updated successfully!", 'Success', { positionClass: 'toast-bottom-right' });
                    }
                    if (notFoundIds.length > 0) {
                        toastr.warning("Employee IDs not found in table: " + notFoundIds.join(', '), 'Warning', { positionClass: 'toast-bottom-right' });
                    }
                } catch (err) {
                    console.error('Excel parse error:', err);
                    toastr.error("Failed to parse Excel file. Please check the format.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            };
            reader.onerror = function () {
                toastr.error("Failed to read the file.", 'Error', { positionClass: 'toast-bottom-right' });
            };
            reader.readAsArrayBuffer(file);
            // Reset file input so same file can be re-uploaded
            $(this).val('');
        });

        $("#addDeductionForm").submit(async function (e) {
            e.preventDefault();

            var employeeId = $("#select_emp").val();
            var deductionAmount = parseFloat($("#amount").val()) || 0;
            var deductionUnit = $("#amount_unit").val(); // Get selected currency (MVR or USD)
            var rates = await fetchRates(resortid);
            var amountInUSD = deductionUnit === "Rufiyaa" ? (deductionAmount * rates.mvr_to_usd ) : deductionAmount;

            otherData[employeeId] = (otherData[employeeId] || 0) + amountInUSD;

            var finalAmount = (selectedCurrency === "MVR")
                        ? amountInUSD * rates.usd_to_mvr
                        : amountInUSD;

            var currencySymbol = (selectedCurrency === 'Dollar') ? '$' : 'MVR ';
            var updated = false;

            $("#table-deductions tbody tr").each(function () {
                var $row = $(this);
                if ($row.find("td:eq(0)").text().trim() === employeeId) {
                    var currentOther = parseFloat($row.find("td:eq(9)").text().replace(currencySymbol, '').trim()) || 0;
                    var newOther = currentOther + finalAmount;
                    $row.find("td:eq(9)").text(currencySymbol + fmtNum(newOther));
                    updateTotal($row, employeeId);
                    updated = true;
                }
            });

            $('#addDeductionForm')[0].reset();
            $("#addDeduction-modal").modal("hide");

            if (updated) {
                toastr.success("Deduction of " + currencySymbol + fmtNum(finalAmount) + " added for " + employeeId + ".", 'Success', {
                    positionClass: 'toast-bottom-right'
                });
            } else {
                toastr.warning("Employee " + employeeId + " not found in deductions table.", 'Warning', {
                    positionClass: 'toast-bottom-right'
                });
            }
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

            if ($previousFieldset.length === 0) return;

            var step = $previousFieldset.data('step');

            // Update progress bar and save step
            var index = $("fieldset").index($currentFieldset);
            $("#progressbar li").eq(index).removeClass("current active");
            $("#progressbar li").eq(index - 1).addClass("current");
            localStorage.setItem("currentStep", step);

            // Animate transition
            $currentFieldset.animate({ opacity: 0 }, {
                duration: 300,
                complete: function () {
                    $currentFieldset.css({ visibility: 'hidden', position: 'absolute' });
                    $previousFieldset.css({ visibility: 'visible', opacity: 1, position: 'relative' });

                    // Reload data for the step we're going back to
                    setTimeout(async function() {
                        var resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
                        var rates = await fetchRates(resortid);
                        var conv = (currency === 'Dollar') ? rates.usd_to_mvr : rates.mvr_to_usd;

                        if (step === 3) {
                            getstep3data('');
                        } else if (step === 4) {
                            getstep5data(currency, conv); // Step 4 = Deductions
                        } else if (step === 5) {
                            getstep4data('', currency, conv); // Step 5 = Service Charge
                        } else if (step === 6) {
                            getstep6data(currency, conv);
                        }
                    }, 200);
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
            selectedEmployeeSet.clear();
            updateSelectedCount();
            employeeList();
        });

        // "Select All" checkbox functionality
        $('#select-all').on('change', function(){
            var isChecked = $(this).is(':checked');
            if (!isChecked) {
                selectedEmployeeSet.clear();
            }
            $('#payroll-employees tbody input[type="checkbox"]').prop('checked', isChecked);
            updateSelectedCount();
            employeeList();
            updatePageLength();
        });
        // Initialize count on page load in case some checkboxes are pre-checked
        updateSelectedCount();
        employeeList();

        setTimeout(() => {
            updatePageLength();
        }, 1000);
        // Filter change event
        $('#searchInput, #departmentFilter, #positionFilter, #sectionFilter').on('keyup change', function () {
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

                        // Clear payroll state from localStorage
                        localStorage.removeItem("currentStep");
                        localStorage.removeItem("payroll_id");
                        localStorage.removeItem("payroll_dateRange");
                        localStorage.removeItem("selectedEmployees");
                        localStorage.removeItem("selectedEmployeesIds");
                        localStorage.removeItem("selectedEmployeeNumericIds");

                        // Redirect to confirmation page
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

    // Show lock confirmation modal
    $('#showLockModal').on('click', function() {
        $('#confirmLockModal').modal('show');
    });

    // Confirm lock — trigger the original submit
    $('#confirmLockBtn').on('click', function() {
        $('#confirmLockModal').modal('hide');
        $('#submit').trigger('click');
    });

    // Save as Draft — keeps payroll in draft status, redirects to dashboard
    $('#saveAsDraft').on('click', function() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) {
            toastr.error("No payroll found to save.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: '{{ route("payroll.save.draft") }}',
            method: 'POST',
            data: {
                payroll_id: payrollId,
                status: 'draft',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("Payroll saved as draft successfully!", 'Success', { positionClass: 'toast-bottom-right' });
                    // Clear localStorage
                    localStorage.removeItem('currentStep');
                    localStorage.removeItem('payroll_id');
                    localStorage.removeItem('selectedEmployees');
                    localStorage.removeItem('selectedEmployeesIds');
                    localStorage.removeItem('selectedEmployeeNumericIds');
                    localStorage.removeItem('deductions');
                    localStorage.removeItem('payroll_dateRange');
                    // Redirect to dashboard after short delay
                    setTimeout(function() {
                        window.location.href = '{{ route("payroll.dashboard") }}';
                    }, 1000);
                } else {
                    toastr.error(response.message || "Failed to save draft.", 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function() {
                toastr.error("Error saving draft.", 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function() {
                $('#saveAsDraft').prop('disabled', false).html('Save as Draft');
            }
        });
    });

    // ===== PAYROLL APPROVAL WORKFLOW =====
    function loadApprovalStatus() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) return;

        $.ajax({
            url: '{{ route("payroll.approval.status") }}',
            method: 'POST',
            data: { payroll_id: payrollId, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (!res.success) return;

                var approvals = res.approvals || [];
                var status = res.payroll_status;

                // Update timeline
                var hasRejection = false;
                approvals.forEach(function(a) {
                    var $li = $('#approval-step-' + a.step_order);
                    var $info = $('#approval-step-' + a.step_order + '-info');
                    $li.removeClass('active text-danger'); // Reset
                    if (a.status === 'approved') {
                        $li.addClass('active');
                        $info.html('<i class="fa-solid fa-check text-success"></i> Approved by ' + a.approver_name + ' on ' + new Date(a.approved_at).toLocaleDateString());
                    } else if (a.status === 'rejected') {
                        hasRejection = true;
                        $li.addClass('text-danger');
                        var rejectHtml = '<i class="fa-solid fa-times text-danger"></i> Rejected by ' + a.approver_name;
                        if (a.remarks) {
                            rejectHtml += '<div class="mt-1 p-2 bg-white border border-danger rounded" style="font-size:12px;"><strong>Reason:</strong> ' + a.remarks + '</div>';
                        }
                        $info.html(rejectHtml);
                    } else {
                        $info.html('<span class="text-muted">Pending</span>');
                    }
                });

                // Show/hide buttons based on status and user role
                $('#sendForApproval, #approvePayroll, #rejectPayroll, #showLockModal, #payrollLockedMessage, #saveAsDraft').addClass('d-none');
                $('.previous').show(); // Default: show back button

                if (status === 'draft') {
                    if (hasRejection) {
                        // Rejected: supervisor can edit and re-send
                        if (res.is_supervisor) {
                            $('#sendForApproval').removeClass('d-none');
                            $('#saveAsDraft').removeClass('d-none');
                            // Show rejection alert with edit guidance
                            $('#payrollLockedMessage').removeClass('d-none')
                                .removeClass('alert-success alert-info').addClass('alert-danger')
                                .html('<i class="fa-solid fa-triangle-exclamation me-1"></i> This payroll was rejected. You can go back to edit the payroll and resubmit for approval.');
                        }
                    } else if (res.is_supervisor) {
                        // Draft (no rejection) — supervisor can send
                        $('#sendForApproval').removeClass('d-none');
                        $('#saveAsDraft').removeClass('d-none');
                    }
                } else if (status === 'pending_approval') {
                    // Approver: show approve/reject if it's their turn
                    if (res.user_approval_step) {
                        var pendingStep = approvals.find(a => a.step_order == res.user_approval_step && a.status === 'pending');
                        var prevApproved = approvals.filter(a => a.step_order < res.user_approval_step).every(a => a.status === 'approved');
                        if (pendingStep && prevApproved) {
                            $('#approvePayroll, #rejectPayroll').removeClass('d-none');
                        }
                    }
                    // Supervisor sees "Waiting for approvals" message
                    if (res.is_supervisor) {
                        $('#payrollLockedMessage').removeClass('d-none')
                            .removeClass('alert-success').addClass('alert-info')
                            .html('<i class="fa-solid fa-clock me-1"></i> Payroll has been sent for approval. Waiting for approvers.');
                    }
                } else if (status === 'approved') {
                    // All 3 approved — supervisor can lock
                    if (res.is_supervisor) {
                        $('#showLockModal').removeClass('d-none');
                    } else {
                        $('#payrollLockedMessage').removeClass('d-none')
                            .html('<i class="fa-solid fa-circle-check me-1"></i> All approvals completed. Waiting for supervisor to lock the payroll.');
                    }
                } else if (status === 'locked') {
                    $('#payrollLockedMessage').removeClass('d-none')
                        .html('<i class="fa-solid fa-lock me-1"></i> This payroll has been approved and locked. Thank you.');
                    $('.previous').addClass('d-none');
                    $('#saveAsDraft').addClass('d-none');
                }
            }
        });
    }

    // Load approval status when step 7 is visible
    var observer = new MutationObserver(function() {
        var step7 = $('fieldset[data-step="7"]');
        if (step7.css('visibility') === 'visible') {
            loadApprovalStatus();
        }
    });
    $('fieldset[data-step="7"]').each(function() {
        observer.observe(this, { attributes: true, attributeFilter: ['style'] });
    });

    // Send for Approval
    $('#sendForApproval').on('click', function() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Sending...');

        $.ajax({
            url: '{{ route("payroll.send.approval") }}',
            method: 'POST',
            data: { payroll_id: payrollId, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    loadApprovalStatus();
                } else {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            complete: function() {
                $('#sendForApproval').prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send Payroll for Approval');
            }
        });
    });

    // Approve Payroll
    $('#approvePayroll').on('click', function() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Approving...');

        $.ajax({
            url: '{{ route("payroll.approve") }}',
            method: 'POST',
            data: { payroll_id: payrollId, action: 'approve', _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    loadApprovalStatus();
                } else {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            complete: function() {
                $('#approvePayroll').prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Approve');
            }
        });
    });

    // Reject Payroll
    $('#rejectPayroll').on('click', function() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) return;

        var remarks = prompt("Please enter a reason for rejection:");
        if (remarks === null) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Rejecting...');

        $.ajax({
            url: '{{ route("payroll.approve") }}',
            method: 'POST',
            data: { payroll_id: payrollId, action: 'reject', remarks: remarks, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.warning(res.message, 'Warning', { positionClass: 'toast-bottom-right' });
                    loadApprovalStatus();
                } else {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            complete: function() {
                $('#rejectPayroll').prop('disabled', false).html('<i class="fa-solid fa-times me-1"></i> Reject');
            }
        });
    });

    // View pending approval payroll — jump to step 7 readonly
    $(document).on('click', '.view-pending-payroll', function() {
        var payrollId = $(this).data('payroll-id');
        localStorage.setItem('payroll_id', payrollId);
        localStorage.setItem('currentStep', '7');
        window.location.href = "{{ route('payroll.run') }}?resume=" + payrollId + "&viewonly=1";
    });

    // Review tab switching
    $(document).on('click', '#reviewTabs .nav-link', function() {
        $('#reviewTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        var tab = $(this).data('review-tab');
        var $table = $('#table-review');
        $table.removeClass('show-overtime show-earnings show-deductions show-summary');
        if (tab === 'overtime') $table.addClass('show-overtime');
        else if (tab === 'earnings') $table.addClass('show-earnings');
        else if (tab === 'deductions') $table.addClass('show-deductions');
        else if (tab === 'summary') $table.addClass('show-summary');
        // 'attendance' is default — no class needed
    });

    // Download Excel
    $('#downloadPayrollExcel').on('click', function() {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) {
            toastr.error("No payroll found.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        window.open("{{ url('resort/payroll/export-review') }}/" + payrollId + "/excel", '_blank');
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
        // Sync visible checkboxes to the persistent Set
        $('#payroll-employees tbody input[type="checkbox"]').each(function(){
            var empId = $(this).val();
            if($(this).prop('checked')){
                selectedEmployeeSet.add(empId);
            } else {
                selectedEmployeeSet.delete(empId);
            }
        });
        var count = selectedEmployeeSet.size;
        $('#selectedCount').text(count + " Employees Selected");
    }

    $("#attedsearchInput").on("keyup", function () {
        var table = $('#table-timeAttendance').DataTable();
        table.search($(this).val()).draw();
    });

    function showTableLoader(tableId) {
        var $table = $(tableId);
        var colCount = $table.find('thead th').length || 5;
        var loaderRow = '<tr class="payroll-loader-row"><td colspan="' + colCount + '" class="text-center py-5">' +
            '<div class="d-flex flex-column align-items-center">' +
            '<div style="width:40px;height:40px;border:3px solid #e0e0e0;border-top-color:#0d6efd;border-radius:50%;animation:payroll-spin 0.7s linear infinite;"></div>' +
            '<span class="text-muted mt-2 small">Loading data...</span>' +
            '</div></td></tr>';
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }
        $table.find('tbody').html(loaderRow);
    }

    function hideTableLoader(tableId) {
        $(tableId).find('.payroll-loader-row').remove();
    }

    function getstep3data(searchTerm) {
        showTableLoader('#table-timeAttendance');
        var payrollId = localStorage.getItem("payroll_id");

        if (!payrollId) {
            toastr.error("Payroll session expired. Please start again.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        var dateRange = $("#hiddenInput").val();
        // Fallback: restore from localStorage if hidden input is empty (page refresh)
        if (!dateRange) {
            dateRange = localStorage.getItem("payroll_dateRange") || '';
            if (dateRange) {
                $("#hiddenInput").val(dateRange);
            }
        }
        if (!dateRange) {
            toastr.error("Please select a payroll period first.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);

        if (!startDate.isValid() || !endDate.isValid()) {
            toastr.error("Invalid date range. Please go back to step 1.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        var selectedEmployees = getSelectedEmployeeIds();

        if (selectedEmployees.length === 0) {
            toastr.info("Restoring session...", '', { positionClass: 'toast-bottom-right', timeOut: 2000 });
            // Synchronous fetch from DB
            var xhr = $.ajax({
                url: '{{ route("fetch.time.attendance") }}',
                method: 'POST',
                async: false,
                data: { payrollId: payrollId, _token: '{{ csrf_token() }}', getEmployeesOnly: 1 }
            });
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success && res.employee_ids && res.employee_ids.length > 0) {
                    selectedEmployees = res.employee_ids;
                    res.employee_ids.forEach(function(id) { selectedEmployeeSet.add(String(id)); });
                    localStorage.setItem("selectedEmployeeNumericIds", JSON.stringify(selectedEmployees));
                }
                if (res.date_range && !dateRange) {
                    dateRange = res.date_range;
                    $("#hiddenInput").val(dateRange);
                    localStorage.setItem("payroll_dateRange", dateRange);
                    // Re-parse dates
                    dates = dateRange.split(' - ');
                    startDate = moment(dates[0], "DD-MM-YYYY", true);
                    endDate = moment(dates[1], "DD-MM-YYYY", true);
                }
            } catch(e) {
                console.error('Session restore failed:', e);
            }
        }

        if (selectedEmployees.length === 0) {
            toastr.error("Session expired. Please start payroll again from step 1.", 'Error',{ positionClass: 'toast-bottom-right' });
            return;
        }

        if (!startDate.isValid() || !endDate.isValid()) {
            toastr.error("Invalid date range. Please start payroll again from step 1.", 'Error', { positionClass: 'toast-bottom-right' });
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
            },
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
                            <td>${employee.position}</td>
                            <td class="editable">${employee.present}</td>
                            <td class="editable">${employee.absent}</td>
                            <td>${employee.unpaid_absent || 0}</td>
                            <td>${employee.day_off || 0}</td>
                            <td>${employee.leave_types}</td>
                            <td class="editable">${employee.regular_ot}</td>
                            <td class="editable">${employee.friday_ot || 0}</td>
                            <td class="editable">${employee.holiday_ot}</td>
                            <td>${employee.total_ot}</td>
                        </tr>`;
                        $tableBody.append(row);
                    });

                    $("#table-timeAttendance").DataTable({
                        responsive: true,
                        paging: false,
                        searching: true,
                        ordering: true,
                        autoWidth: false,
                        pageLength: 10,
                        dom: 'lrtip' // hide default search box, keep table+info+pagination
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
            complete: function() {
                hideTableLoader('#table-timeAttendance');
            }
        });

        return;
    }

    function getstep4data(searchTerm,currency, conversionRate = 1) {
        showTableLoader('#table-serviceCharge');
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = getSelectedEmployeeIds();

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
                    // Try to load saved service charge from DB if distributedServiceCharge is empty
                    var payrollId = localStorage.getItem("payroll_id");
                    if (distributedServiceCharge.length === 0 && payrollId) {
                        try {
                            var scResult = $.ajax({
                                url: '{{ route("fetch.time.attendance") }}',
                                method: 'POST',
                                async: false,
                                data: { payrollId: payrollId, _token: '{{ csrf_token() }}', getServiceChargeOnly: true }
                            }).responseJSON;
                            if (scResult.success && scResult.data) {
                                scResult.data.forEach(function(sc) {
                                    distributedServiceCharge.push({
                                        id: sc.Emp_id,
                                        service_charge_days: sc.total_working_days,
                                        amount: sc.service_charge_amount
                                    });
                                });
                                if (scResult.total_amount) {
                                    $('#total-ser').val(scResult.total_amount);
                                }
                            }
                        } catch(e) { console.log('No saved SC data'); }
                    }

                    var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                    response.data.forEach(function (employee) {
                        let serviceCharge = getServiceChargeamountForEmployee(employee.employee_id);
                        // If we have saved SC and currency is MVR, convert for display
                        if (serviceCharge !== "0" && currency === 'MVR' && conversionRate > 1) {
                            serviceCharge = fmtNum(parseFloat(serviceCharge) * conversionRate);
                        }

                        var row = `<tr>
                            <td>${employee.employee_id}</td>
                            <td>
                                <div class="tableUser-block">
                                    <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                    <span>${employee.name}</span>
                                </div>
                            </td>
                            <td>${employee.position}</td>
                            <td class="workdays">${employee.workdays}</td>
                            <td class="service-charge">${currencySymbol}${serviceCharge}</td>
                        </tr>`;
                        $tableBody.append(row);
                    });

                    // Update total footer if SC was restored
                    if (distributedServiceCharge.length > 0) {
                        var scTotal = 0;
                        $("#table-serviceCharge tbody .service-charge").each(function() {
                            scTotal += parseFloat($(this).text().replace(/[^0-9.-]/g, '')) || 0;
                        });
                        var cs = (currency === 'Dollar') ? '$' : 'MVR ';
                        $("#total-service-charge").text(cs + fmtNum(scTotal));
                    }

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
            },
            complete: function() {
                hideTableLoader('#table-serviceCharge');
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
    //                         <td>${employee.department}</td>
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
                               
    //                             $row.find("td:eq(8)").text(currencySymbol + fmtNum(FinalOtherAMount)); // Update displayed value
                                
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
        showTableLoader('#table-deductions');
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange.split(' - ');
        var startDate = moment(dates[0], "DD-MM-YYYY", true);
        var endDate = moment(dates[1], "DD-MM-YYYY", true);
        var selectedEmployees = getSelectedEmployeeIds();
        var payrollId = localStorage.getItem("payroll_id");
        let resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
        var rates = await fetchRates(resortid);

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
                            <td>${employee.department}</td>
                            <td class="attendance">${currencySymbol}${employee.absent_deduction}</td>
                            <td class="city-ladger">${currencySymbol}0.00</td>
                            <td class="staff-shop">${currencySymbol}0.00</td>
                            <td class="advance-loan">${repaymentAmountFinal > 0 ? currencySymbol + fmtNum(repaymentAmountFinal) : 'NA'}</td>
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
                            $row.find("td:eq(4)").text(currencySymbol + fmtNum(cityLedgerFinal));
                        }

                        // Other deduction update
                        if (otherData[employeeId] !== undefined) {
                            var amountInUSD = otherData[employeeId];
                            var FinalOtherAmount = (currency === "MVR") 
                                ? amountInUSD * rates.usd_to_mvr 
                                : amountInUSD;
                            $row.find("td:eq(9)").text(currencySymbol + fmtNum(FinalOtherAmount));
                        }
                    });

                    fetchStaffShopData(selectedEmployees, startDate, endDate, currency, conversionRate);
                    calculatePensionAndEWT(employeeEarningsData, currency, conversionRate, payrollId);

                } else {
                    toastr.error(response.message, 'Error', { positionClass: 'toast-bottom-right' });
                }
                hideTableLoader('#table-deductions');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
                hideTableLoader('#table-deductions');
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
    //                         allowanceCols += `<td>${currencySymbol}${fmtNum(val)}</td>`;
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
    //                         <td>${employee.department}</td>
    //                         <td>${employee.position}</td>
    //                         <td>${employee.present}</td>
    //                         <td>${employee.absent}</td>
    //                         <td>${currencySymbol}${employee.service_charge}</td>
    //                         <td>${currencySymbol}${employee.regularOTPay}</td>
    //                         <td>${currencySymbol}${employee.holidayOTPay}</td>
    //                         <td>${currencySymbol}${employee.totalOTPay}</td>
    //                         <td>${currencySymbol}${fmtNum(employee.basic_salary)}</td>
    //                         <td>${currencySymbol}${fmtNum(employee.earned_salary)}</td>
    //                         ${allowanceCols}
    //                         <td>
    //                         <td>${currencySymbol}${fmtNum((employee.total_deduction || 0))}</td>
    //                         <td>${currencySymbol}${fmtNum(employee.normal_pay)}</td>
    //                     </tr>`;
    //                     $tableBody.append(row);
    //                 });

    //                 // Footer
    //               let footerHtml = `
    //                     <td colspan="4" class="text-end fw-bold">Total</td>
    //                     <td>${footerTotals.present}</td>
    //                     <td>${footerTotals.absent}</td>
    //                     <td>-</td>
    //                     <td>${fmtNum(footerTotals.regular_ot)} hrs</td>
    //                     <td>${fmtNum(footerTotals.holiday_ot)} hrs</td>
    //                     <td>${fmtNum(footerTotals.total_ot)} hrs</td>
    //                     <td>${currencySymbol}${fmtNum(footerTotals.basic_salary)}</td>
    //                     <td>${currencySymbol}${fmtNum(footerTotals.earned_salary)}</td>
    //                 `;

    //                 // Add dynamic allowance totals
    //                 allowanceList.forEach(name => {
    //                     footerHtml += `<td>${currencySymbol}${fmtNum(allowanceSums[name])}</td>`;
    //                 });

    //                 // Add deductions and normal pay total
    //                 footerHtml += `
    //                     <td>${currencySymbol}${fmtNum(footerTotals.total_deductions)}</td>
    //                     <td>${currencySymbol}${fmtNum(footerTotals.normal_pay)}</td>
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
        showTableLoader('#table-review');
        var payrollId = localStorage.getItem("payroll_id");
        var dateRange = $("#hiddenInput").val();
        var dates = dateRange ? dateRange.split(' - ') : [];
        var startDate = dates.length === 2 ? moment(dates[0], "DD-MM-YYYY", true) : moment();
        var endDate = dates.length === 2 ? moment(dates[1], "DD-MM-YYYY", true) : moment();
        var selectedEmployees = getSelectedEmployeeIds();

        if (selectedEmployees.length === 0) {
            hideTableLoader('#table-review');
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

                    // Remove the section header row, use only one header row
                    $table.find("thead").html(`<tr>
                        <th>ID</th>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th class="col-attendance">Present</th>
                        <th class="col-attendance">Absent</th>
                        <th class="col-attendance">Day Off</th>
                        <th class="col-attendance">Other Leaves</th>
                        <th class="col-overtime">Regular OT</th>
                        <th class="col-overtime">Friday OT</th>
                        <th class="col-overtime">Holiday OT</th>
                        <th class="col-overtime">Total OT Pay</th>
                        <th class="col-earnings">Service Charge</th>
                        <th class="col-earnings">Basic Earned</th>
                        ${allowanceHeaderHtml.replace(/<th /g, '<th class="col-earnings" ')}
                        <th class="col-earnings">Total Earnings</th>
                        <th class="col-deductions">Attendance</th>
                        <th class="col-deductions">City Ledger</th>
                        <th class="col-deductions">Staff Shop</th>
                        <th class="col-deductions">Pension</th>
                        <th class="col-deductions">EWT</th>
                        <th class="col-deductions">Other</th>
                        <th class="col-deductions">Total Deductions</th>
                        <th class="col-deductions">Net Salary</th>
                        <th class="col-summary">Total Earnings</th>
                        <th class="col-summary">Total Deductions</th>
                        <th class="col-summary">Net Salary</th>
                    </tr>`);

                    let footerTotals = {
                        present: 0, absent: 0, service_charge: 0, regularOTPay: 0, fridayOTPay: 0, holidayOTPay: 0, totalOTPay: 0,
                        basic_salary: 0, earned_salary: 0, normal_pay: 0, total_deductions: 0,
                        absent_deduction: 0, city_ledger: 0, staff_shop: 0, pension: 0, ewt: 0, other_deduction: 0,
                    };
                    let allowanceSums = Object.fromEntries(allowanceList.map(a => [a.name, 0]));

                    response.data.forEach(function (employee) {
                        let allowanceMap = Object.fromEntries((employee.allowances || []).map(a => [a.name, a.amount]));

                        let allowanceCols = '';
                        // allowanceList.forEach(name => {
                        //     let val = allowanceMap[name] || 0;
                        //     allowanceCols += `<td>${currencySymbol}${fmtNum(val)}</td>`;
                        //     allowanceSums[name] += val;
                        // });

                        allowanceList.forEach(a => {
                            let val = allowanceMap[a.name] || 0;
                            allowanceCols += `<td>${currencySymbol}${fmtNum(val)}</td>`;
                            allowanceSums[a.name] += val;
                        });

                        footerTotals.present += employee.present;
                        footerTotals.absent += employee.absent;
                        footerTotals.service_charge += employee.service_charge;
                        footerTotals.regularOTPay += employee.regularOTPay;
                        footerTotals.fridayOTPay += (employee.fridayOTPay || 0);
                        footerTotals.holidayOTPay += employee.holidayOTPay;
                        footerTotals.totalOTPay += employee.totalOTPay;
                        footerTotals.basic_salary += employee.basic_salary;
                        footerTotals.earned_salary += employee.earned_salary;
                        footerTotals.normal_pay += employee.normal_pay;
                        footerTotals.total_deductions += (employee.total_deduction || 0);
                        footerTotals.absent_deduction += (employee.absent_deduction || 0);
                        footerTotals.city_ledger += (employee.city_ledger || 0);
                        footerTotals.staff_shop += (employee.staff_shop || 0);
                        footerTotals.pension += (employee.pension || 0);
                        footerTotals.ewt += (employee.ewt || 0);
                        footerTotals.other_deduction += (employee.other_deduction || 0);
                        

                        let netSalary = (employee.normal_pay || 0) - (employee.total_deduction || 0);

                        let row = `
                            <tr>
                                <td>${employee.employee_id}</td>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="${employee.image}" alt="user"></div>
                                        <span>${employee.name}</span>
                                    </div>
                                </td>
                                <td>${employee.position}</td>
                                <td class="col-attendance">${employee.present}</td>
                                <td class="col-attendance">${employee.absent}</td>
                                <td class="col-attendance">${employee.day_off}</td>
                                <td class="col-attendance">${employee.leave_types || '-'}</td>
                                <td class="col-overtime">${currencySymbol}${fmtNum(employee.regularOTPay)}</td>
                                <td class="col-overtime">${currencySymbol}${fmtNum(employee.fridayOTPay || 0)}</td>
                                <td class="col-overtime">${currencySymbol}${fmtNum(employee.holidayOTPay)}</td>
                                <td class="col-overtime">${currencySymbol}${fmtNum(employee.totalOTPay)}</td>
                                <td class="col-earnings">${currencySymbol}${fmtNum(employee.service_charge)}</td>
                                <td class="col-earnings">${currencySymbol}${fmtNum(employee.earned_salary)}</td>
                                ${allowanceCols.replace(/<td>/g, '<td class="col-earnings">')}
                                <td class="col-earnings">${currencySymbol}${fmtNum(employee.normal_pay)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.absent_deduction || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.city_ledger || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.staff_shop || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.pension || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.ewt || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.other_deduction || 0)}</td>
                                <td class="col-deductions">${currencySymbol}${fmtNum(employee.total_deduction || 0)}</td>
                                <td class="col-deductions"><strong>${currencySymbol}${fmtNum(netSalary)}</strong></td>
                                <td class="col-summary">${currencySymbol}${fmtNum(employee.normal_pay)}</td>
                                <td class="col-summary">${currencySymbol}${fmtNum(employee.total_deduction || 0)}</td>
                                <td class="col-summary"><strong>${currencySymbol}${fmtNum(netSalary)}</strong></td>
                            </tr>`;
                        $tableBody.append(row);
                    });

                    // Generate footer row
                    let totalNet = footerTotals.normal_pay - footerTotals.total_deductions;
                    let footerHtml = `
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td class="col-attendance">${footerTotals.present}</td>
                        <td class="col-attendance">${footerTotals.absent}</td>
                        <td class="col-attendance">-</td>
                        <td class="col-attendance">-</td>
                        <td class="col-overtime">${currencySymbol}${fmtNum(footerTotals.regularOTPay)}</td>
                        <td class="col-overtime">${currencySymbol}${fmtNum(footerTotals.fridayOTPay || 0)}</td>
                        <td class="col-overtime">${currencySymbol}${fmtNum(footerTotals.holidayOTPay)}</td>
                        <td class="col-overtime">${currencySymbol}${fmtNum(footerTotals.totalOTPay)}</td>
                        <td class="col-earnings">${currencySymbol}${fmtNum(footerTotals.service_charge)}</td>
                        <td class="col-earnings">${currencySymbol}${fmtNum(footerTotals.earned_salary)}</td>
                    `;
                    allowanceList.forEach(a => {
                        footerHtml += `<td class="col-earnings">${currencySymbol}${fmtNum(allowanceSums[a.name])}</td>`;
                    });
                    footerHtml += `
                        <td class="col-earnings">${currencySymbol}${fmtNum(footerTotals.normal_pay)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.absent_deduction)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.city_ledger)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.staff_shop)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.pension)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.ewt)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.other_deduction)}</td>
                        <td class="col-deductions">${currencySymbol}${fmtNum(footerTotals.total_deductions)}</td>
                        <td class="col-deductions"><strong>${currencySymbol}${fmtNum(totalNet)}</strong></td>
                        <td class="col-summary">${currencySymbol}${fmtNum(footerTotals.normal_pay)}</td>
                        <td class="col-summary">${currencySymbol}${fmtNum(footerTotals.total_deductions)}</td>
                        <td class="col-summary"><strong>${currencySymbol}${fmtNum(totalNet)}</strong></td>
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
                hideTableLoader('#table-review');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message, 'Validation Error', { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error("Error fetching attendance data.", 'Error', { positionClass: 'toast-bottom-right' });
                }
                hideTableLoader('#table-review');
            }
        });
    }


    function calculatePayrollSummary(currency, conversionRate = 1) {
        var payrollId = localStorage.getItem("payroll_id");
        if (!payrollId) {
            toastr.error("Payroll ID not found.", 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
        let draftDate = new Date().toISOString().split("T")[0];

        $.ajax({
            url: '{{ route("fetch.totalPayroll.data") }}',
            method: 'POST',
            data: {
                payrollId: payrollId,
                _token: '{{ csrf_token() }}'
            },
           success: function (response) {
                if (response.success) {

                    document.getElementById("total_payroll_amount").innerText = currencySymbol + fmtNum(parseFloat(response.total_payroll));
                    document.getElementById("total_employees").innerText = response.total_employees;
                    document.getElementById("payroll-darft-date").innerText = draftDate;
                    document.getElementById("total_earned_salary").innerText = currencySymbol + fmtNum(parseFloat(response.total_earned_salary || 0));
                    document.getElementById("total_allowances_conf").innerText = currencySymbol + fmtNum(parseFloat(response.total_allowances || 0));
                    document.getElementById("total_ot_conf").innerText = currencySymbol + fmtNum(parseFloat(response.total_ot || 0));
                    document.getElementById("total_service_charge_conf").innerText = currencySymbol + fmtNum(parseFloat(response.total_service_charge || 0));
                    document.getElementById("total_earnings_conf").innerText = currencySymbol + fmtNum(parseFloat(response.total_earnings || 0));
                    document.getElementById("total_deductions_conf").innerText = currencySymbol + fmtNum(parseFloat(response.total_deductions || 0));
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

    var staffShopTooltips = {}; // Store tooltip HTML by Emp_id
    var ewtTooltips = {}; // Store EWT tooltip HTML by Emp_id

    function fetchStaffShopData(employeeIds, startDate, endDate, currency,conversionRate =1) {
        $.ajax({
            url: '{{route("payroll.fetch.staffshop")}}',
            method: 'POST',
            data: {
                employees: employeeIds,
                startDate: startDate.format("YYYY-MM-DD"),
                endDate: endDate.format("YYYY-MM-DD"),
                currency: currency,
                conversionRate: conversionRate,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    var currencySymbol = (currency === 'Dollar') ? '$' : 'MVR ';
                    response.data.forEach(function (employee) {
                        $("#table-deductions tbody tr").each(function () {
                            var $row = $(this);
                            if ($row.find("td:eq(0)").text().trim() === employee.Emp_id) {
                                var $td = $row.find("td.staff-shop");
                                $td.text(currencySymbol + fmtNum(employee.total));

                                // Build tooltip with transaction details
                                if (employee.transactions && employee.transactions.length > 0) {
                                    var tooltipHtml = '<div style="min-width:280px;">' +
                                        '<div class="p-2 border-bottom" style="background:#f0f4ff;border-radius:8px 8px 0 0;">' +
                                            '<strong>Staff Shop Transactions</strong>' +
                                        '</div>' +
                                        '<div class="p-2">' +
                                        '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">' +
                                        '<thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>';

                                    employee.transactions.forEach(function(txn) {
                                        var statusBadge = txn.status === 'Partial Paid'
                                            ? '<span class="badge badge-themeWarning" style="font-size:9px;">Partial</span>'
                                            : '';
                                        tooltipHtml += '<tr>' +
                                            '<td>' + txn.date + '</td>' +
                                            '<td>' + txn.product + ' ' + statusBadge + '</td>' +
                                            '<td>' + txn.qty + '</td>' +
                                            '<td>' + currencySymbol + fmtNum(txn.deduction) + '</td>' +
                                        '</tr>';
                                        if (txn.cash_paid > 0) {
                                            tooltipHtml += '<tr><td colspan="4" style="font-size:10px;color:#888;">Cash paid: ' + currencySymbol + fmtNum(txn.cash_paid) + '</td></tr>';
                                        }
                                    });

                                    tooltipHtml += '</tbody></table>' +
                                        '<div class="border-top pt-1 mt-1 text-end"><strong>Total: ' + currencySymbol + fmtNum(employee.total) + '</strong></div>' +
                                        '</div></div>';

                                    $td.css('cursor', 'pointer');
                                    staffShopTooltips[employee.Emp_id] = tooltipHtml;
                                    console.log('Stored tooltip for', employee.Emp_id, 'length:', tooltipHtml.length);
                                }

                                updateTotal($row, employee.Emp_id);
                            }
                        });
                    });
                }
            }
        });
    }

    // Staff shop tooltip — hover to show transaction details
    var $staffTooltip = null;

    function getEmpIdFromRow($row) {
        var empId = $row.find('td:eq(0)').text().trim();
        if (!empId || !empId.match(/^DR-/)) {
            $row.find('td').each(function() {
                var t = $(this).text().trim();
                if (t.match(/^DR-\d+$/)) { empId = t; return false; }
            });
        }
        return empId;
    }

    $(document).on('mouseenter', 'td.staff-shop', function(e) {
        if ($staffTooltip) { $staffTooltip.remove(); $staffTooltip = null; }

        var empId = getEmpIdFromRow($(this).closest('tr'));
        var htmlContent = staffShopTooltips[empId];
        if (!htmlContent) return;

        var amountText = $(this).text().trim().replace(/[^0-9.]/g, '');
        if (!amountText || parseFloat(amountText) === 0) return;

        $staffTooltip = $('<div id="staff-shop-tooltip" class="staff-shop-popover"></div>');
        $staffTooltip[0].innerHTML = htmlContent;
        $('body').append($staffTooltip);

        var rect = this.getBoundingClientRect();
        var ttHeight = $staffTooltip.outerHeight();
        var ttWidth = $staffTooltip.outerWidth();
        var top = rect.top - ttHeight - 10;
        var left = rect.left + (rect.width / 2) - (ttWidth / 2);

        if (top < 10) top = rect.bottom + 10;
        if (left < 10) left = 10;
        if (left + ttWidth > window.innerWidth - 10) left = window.innerWidth - ttWidth - 10;

        $staffTooltip.css({ top: top, left: left });
    });

    $(document).on('mouseleave', 'td.staff-shop', function() {
        if ($staffTooltip) { $staffTooltip.remove(); $staffTooltip = null; }
    });

    // EWT tooltip — hover to show tax breakdown
    var $ewtTooltip = null;

    $(document).on('mouseenter', 'td.ewt', function(e) {
        if ($ewtTooltip) { $ewtTooltip.remove(); $ewtTooltip = null; }

        var empId = getEmpIdFromRow($(this).closest('tr'));
        var htmlContent = ewtTooltips[empId];
        if (!htmlContent) return;

        $ewtTooltip = $('<div class="staff-shop-popover"></div>');
        $ewtTooltip[0].innerHTML = htmlContent;
        $('body').append($ewtTooltip);

        var rect = this.getBoundingClientRect();
        var ttHeight = $ewtTooltip.outerHeight();
        var ttWidth = $ewtTooltip.outerWidth();
        var top = rect.top - ttHeight - 10;
        var left = rect.left + (rect.width / 2) - (ttWidth / 2);

        if (top < 10) top = rect.bottom + 10;
        if (left < 10) left = 10;
        if (left + ttWidth > window.innerWidth - 10) left = window.innerWidth - ttWidth - 10;

        $ewtTooltip.css({ top: top, left: left });
    });

    $(document).on('mouseleave', 'td.ewt', function() {
        if ($ewtTooltip) { $ewtTooltip.remove(); $ewtTooltip = null; }
    });

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
    //                             var pensionFormatted = currencySymbol + fmtNum(employee.pension);
    //                             var ewtFormatted = currencySymbol + fmtNum(employee.ewt);

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
                                var pensionFormatted = currencySymbol + fmtNum(employee.pension);
                                var ewtFormatted = currencySymbol + fmtNum(employee.ewt);

                                $row.find("td:eq(7)").text(pensionFormatted);
                                var $ewtTd = $row.find("td:eq(8)");
                                $ewtTd.text(ewtFormatted);

                                // Store EWT tooltip data
                                if (employee.ewt > 0 && employee.ewt_breakdown && employee.ewt_breakdown.length > 0) {
                                    var ewtHtml = '<div style="min-width:300px;">' +
                                        '<div class="p-2 border-bottom" style="background:#fff3e0;border-radius:8px 8px 0 0;">' +
                                            '<strong>EWT Calculation</strong>' +
                                        '</div>' +
                                        '<div class="p-2">' +
                                        '<div class="mb-2" style="font-size:11px;">' +
                                            '<div class="d-flex justify-content-between"><span>Total Earnings:</span><strong>' + currencySymbol + employee.total_earnings.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</strong></div>' +
                                            '<div class="d-flex justify-content-between"><span>Taxable Income:</span><strong>' + currencySymbol + employee.taxable_income.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</strong></div>' +
                                        '</div>' +
                                        '<table class="table table-sm table-borderless mb-0" style="font-size:11px;">' +
                                        '<thead><tr><th>Tax Slab</th><th>Rate</th><th>Taxable</th><th>Tax</th></tr></thead><tbody>';
                                    employee.ewt_breakdown.forEach(function(slab) {
                                        ewtHtml += '<tr><td>' + slab.slab + '</td><td>' + slab.rate + '</td><td>' + currencySymbol + slab.taxable.toLocaleString(undefined, {minimumFractionDigits:2}) + '</td><td>' + currencySymbol + fmtNum(slab.tax) + '</td></tr>';
                                    });
                                    ewtHtml += '</tbody></table>' +
                                        '<div class="border-top pt-1 mt-1 text-end"><strong>Total EWT: ' + ewtFormatted + '</strong></div>' +
                                        '</div></div>';
                                    ewtTooltips[employee.Emp_id] = ewtHtml;
                                    $ewtTd.css('cursor', 'pointer');
                                }

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

        $row.find("td:eq(10)").text(currencySymbol + fmtNum(total));
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
                    d.section = $('#sectionFilter').val();
                    d.isChecked = isChecked;
                    // Pass payroll period dates to filter employees with attendance data
                    var dateRange = $('#hiddenInput').val();
                    if (dateRange) {
                        var dates = dateRange.split(' - ');
                        if (dates.length === 2) {
                            d.startDate = moment(dates[0], 'DD-MM-YYYY', true).format('YYYY-MM-DD');
                            d.endDate = moment(dates[1], 'DD-MM-YYYY', true).format('YYYY-MM-DD');
                        }
                    }
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
                        return ` ${data.postion_title}`;
                    }
                },
                { 
                    data: 'department', 
                    render: function(data, type, row) {
                        return ` ${data.department_name}`;
                    }
                },
                { data: 'section', defaultContent: 'N/A' },
                { data: 'payment_method', defaultContent: 'Cash' }
            ],
            drawCallback: function(settings) {
                // Restore checkbox state from persistent Set after each page draw
                $('#payroll-employees tbody input[type="checkbox"]').each(function(){
                    var empId = $(this).val();
                    if(selectedEmployeeSet.has(empId)){
                        $(this).prop('checked', true);
                    }
                });
                $('#selectedCount').text(selectedEmployeeSet.size + " Employees Selected");
            }
        });
    }

    function updatePageLength() 
    {
        var isChecked = $("#select-all").is(":checked");
       
        if (isChecked) {
            var table = $('#payroll-employees').DataTable();
            if ($.fn.DataTable.isDataTable("#table-timeAttendance")) var newtable = $("#table-timeAttendance").DataTable();
            if ($.fn.DataTable.isDataTable("#table-serviceCharge")) var servicechargetable = $("#table-serviceCharge").DataTable();
            if ($.fn.DataTable.isDataTable("#table-deductions")) var deductiontable = $("#table-deductions").DataTable();
            // var reviewtable = $("#table-review").DataTable();
            $.ajax({
                url: '{{ route("payroll.employee.list") }}',
                type: 'GET',
                data: {
                    searchTerm : $('#searchInput').val(),
                    department : $('#departmentFilter').val(),
                    position : $('#positionFilter').val(),
                    section : $('#sectionFilter').val(),
                    isChecked : true,
                },
                success: function (response) {
                    var totalRecords = response.recordsTotal;
                    if (table && table.page) table.page.len(totalRecords).draw();
                    if (newtable && newtable.page) newtable.page.len(totalRecords).draw();
                    if (servicechargetable && servicechargetable.page) servicechargetable.page.len(totalRecords).draw();
                    if (deductiontable && deductiontable.page) deductiontable.page.len(totalRecords).draw();
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

    // Leave type tooltip
    $(document).on({
        mouseenter: function(e) {
            var raw = $(this).attr('data-leave-tooltip');
            if (!raw) return;
            var ta = document.createElement('textarea');
            ta.innerHTML = raw;
            raw = ta.value;
            try {
                var data = JSON.parse(raw);
                var color = data.color || '#000';
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var html = '<div class="leave-tooltip show">';
                html += '<div class="tooltip-header">';
                html += '<span class="tooltip-type" style="color:'+color+'">'+data.type+'</span>';
                html += '<span class="tooltip-count" style="background:'+color+';color:#fff;">'+data.count+' day'+(data.count>1?'s':'')+'</span>';
                html += '</div><div class="tooltip-info">';
                if (data.from) {
                    var fd = new Date(data.from), td = new Date(data.to || data.from);
                    html += '<div><span class="info-label">From:</span><span class="info-value">'+fd.getDate()+' '+months[fd.getMonth()]+' '+fd.getFullYear()+'</span></div>';
                    html += '<div><span class="info-label">To:</span><span class="info-value">'+td.getDate()+' '+months[td.getMonth()]+' '+td.getFullYear()+'</span></div>';
                }
                if (data.dates && data.dates.length > 0 && data.dates.length <= 5) {
                    html += '<div style="margin-top:4px;border-top:1px solid #444;padding-top:4px;"><span class="info-label">Dates:</span>';
                    data.dates.forEach(function(d) { var dt=new Date(d); html += '<span class="info-value" style="display:inline-block;margin:1px 3px;padding:1px 6px;background:#444;border-radius:3px;font-size:11px;">'+dt.getDate()+' '+months[dt.getMonth()]+'</span>'; });
                    html += '</div>';
                }
                html += '</div></div>';
                var $tip = $(html);
                $('body').append($tip);
                var $el = $(this), off = $el.offset(), w = $el.outerWidth(), h = $el.outerHeight();
                var tw = $tip.outerWidth(), th = $tip.outerHeight(), st = $(window).scrollTop();
                var left = (off.left - $(window).scrollLeft()) + (w/2) - (tw/2);
                var top = (off.top - st) - th - 12;
                if (left < 10) left = 10;
                if (left + tw > $(window).width() - 10) left = $(window).width() - tw - 10;
                var ac = '';
                if (top < 10) { top = (off.top - st) + h + 12; ac = 'arrow-top'; }
                $tip.addClass(ac).css({left:left+'px',top:top+'px'});
            } catch(ex) { console.error('Leave tooltip error:', ex); }
        },
        mouseleave: function() { $('.leave-tooltip').remove(); }
    }, '.leave-type-badge');

</script>
@endsection