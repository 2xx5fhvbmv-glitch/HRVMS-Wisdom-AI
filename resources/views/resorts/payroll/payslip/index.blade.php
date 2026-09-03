@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #payslip-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #payslip-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="payslip-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select id="departmentFilter" class="form-select dd-native-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div class="dd" data-target="#departmentFilter">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">All Departments</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Department">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @foreach($departments as $department)
                                    <div class="dd-item" role="option" data-value="{{ $department->id }}"><span class="dd-nm">{{ $department->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select  id="positionFilter" class="form-select dd-native-select">
                            <option value="">All Positions</option>
                            <!-- Example: populate dynamically or statically -->
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                            @endforeach
                        </select>
                        <div class="dd" data-target="#positionFilter">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">All Positions</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Position">
                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @foreach($positions as $position)
                                    <div class="dd-item" role="option" data-value="{{ $position->id }}"><span class="dd-nm">{{ $position->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto">
                        <a href="#" class="a-link">View Previous Payslips</a>
                    </div> -->
                </div>
            </div>
            <!-- data-Table  -->
            <table id="employee-table" class="table w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Name </th>
                        <th>Department </th>
                        <th>Position</th>
                        <th>Email ID</th>
                        <th>Action </th>
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="share-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Share Payslip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label for="select_emp" class="form-label">SELECT EMPLOYEE</label>
                    <select class="form-select dd-native-select" id="select_emp" aria-label="Default select example">
                        <option selected>Select</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->resortAdmin->first_name}} {{ $employee->resortAdmin->last_name}}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#select_emp">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Employee">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value="Select"><span class="dd-nm">Select</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($employees as $employee)
                                <div class="dd-item" role="option" data-value="{{ $employee->id }}"><span class="dd-nm">{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="month" class="form-label">MONTH</label>
                    <select class="form-select dd-native-select month" id="month" aria-label="Default select example"></select>
                    <div class="dd" data-target="#month">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl"></span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Month">
                            <div class="dd-scroll"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="year" class="form-label">YEAR</label>
                    <select class="form-select dd-native-select year" id="year" aria-label="Default select example"></select>
                    <div class="dd" data-target="#year">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl"></span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Year">
                            <div class="dd-scroll"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn payroll-btn-secondary ms-auto">Cancel</a>
                <a href="#" class="btn payroll-btn-primary" id="sharePayslipBtn">Submit</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-assign">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">View Payslip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label for="select_emp1" class="form-label">SELECT EMPLOYEE</label>
                    <select class="form-select dd-native-select" id="select_emp1" aria-label="Default select example">
                        <option selected>Select</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->resortAdmin->first_name}} {{ $employee->resortAdmin->last_name}}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#select_emp1">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">Select</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Employee">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item active" role="option" data-value="Select"><span class="dd-nm">Select</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($employees as $employee)
                                <div class="dd-item" role="option" data-value="{{ $employee->id }}"><span class="dd-nm">{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="month1" class="form-label">MONTH</label>
                    <select class="form-select dd-native-select month" id="month1" aria-label="Default select example"></select>
                    <div class="dd" data-target="#month1">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl"></span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Month">
                            <div class="dd-scroll"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="year1" class="form-label">YEAR</label>
                    <select class="form-select dd-native-select year" id="year1" aria-label="Default select example"></select>
                    <div class="dd" data-target="#year1">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl"></span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Year">
                            <div class="dd-scroll"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn payroll-btn-secondary ms-auto">Cancel</a>
                <a href="#" class="btn payroll-btn-secondary" id="viewPayslipBtn">Submit</a>

            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function()
    {
        employeeList();

        $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function () {
            employeeList();
        });
        populateMonthYearDropdowns();

        $('#employee-table').on('click', 'a[href="#share-modal"]', function () {
            let employeeId = $(this).data('id'); // Get employee ID from the clicked button
        
            // Set employee ID in the modal
            $('#share-modal').find('#select_emp').val(employeeId);
            window.wisdomDD.sync('#select_emp');

            // If there's a field displaying the employee name in the modal, update it too
            let employeeName = $(this).closest('tr').find('td:nth-child(2)').text().trim();
            $('#share-modal').find('#selectedEmployeeName').text(employeeName);
        });

        $('#employee-table').on('click', 'a[href="#view-modal"]', function () {
            let employeeId = $(this).data('id'); // Get employee ID from the clicked button
        
            // Set employee ID in the modal
            $('#view-modal').find('#select_emp1').val(employeeId);
            window.wisdomDD.sync('#select_emp1');

            // If there's a field displaying the employee name in the modal, update it too
            let employeeName = $(this).closest('tr').find('td:nth-child(2)').text().trim();
            $('#view-modal').find('#selectedEmployeeName').text(employeeName);
        });

        $("#viewPayslipBtn").click(function (e) {
            e.preventDefault(); // Prevent default action

            let employeeId = $("#select_emp1").val();
            let month = $("#month1").val();
            let year = $("#year1").val();

            $.ajax({
                url: "{{route('payroll.payslip.view')}}",
                type: "POST",
                data: {
                    employee_id: employeeId,
                    month: month,
                    year: year,
                    _token: $('meta[name="csrf-token"]').attr('content') // For CSRF protection in Laravel
                },
                success: function(response) {
                    console.log(response.success);
                    if (response.success == true) {
                        // Open the payslip in a new window or modal
                        window.open("{{ route('payslip.show') }}", "_blank");
                    } else {
                        // alert("Payslip not found for the selected month and year.");
                        toastr.error("Payslip not found for the selected month and year.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        
                    }
                },
                error: function() {
                    toastr.error("Something went wrong. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $("#sharePayslipBtn").click(function (e) {
            e.preventDefault();

            let $btn = $(this); // Cache the button reference
            let employeeId = $("#select_emp").val();
            let month = $("#month").val();
            let year = $("#year").val();

            if (!employeeId || !month || !year) {
                toastr.error("Please select all fields.", "Error", { positionClass: 'toast-bottom-right' });
                return;
            }

            $btn.addClass("disabled").css("pointer-events", "none").text("Sharing...");

            $.ajax({
                url: "{{ route('payroll.payslip.share') }}",
                type: "POST",
                data: {
                    employee_id: employeeId,
                    month: month,
                    year: year,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        $("#share-modal").modal("hide");
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error("Something went wrong. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                },
                complete: function () {
                    $btn.removeClass("disabled").css("pointer-events", "auto").text("Submit");
                }
            });
        });


    });

    function employeeList()
    {
        if ($.fn.DataTable.isDataTable('#employee-table'))
        {
            $('#employee-table').DataTable().destroy();
        }
        let table = $('#employee-table').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength":10,
            processing: true,
            serverSide: true,
            order:[[6,'desc']],
            ajax: {
                url: "{{ route('payslip.employee.list') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.department = $('#departmentFilter').val();
                    d.position = $('#positionFilter').val();
                },
            },
            columns: [
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
                        return ` ${data.department_name} <span class="badge badge-themeLight">${data.department_code}</span>`;
                    }
                },
                { 
                    data: 'email', 
                    render: function(data, type, row) {
                        return ` ${data.email} `
                    }
                },
                {
                    data: 'action',
                    render: function(data, type, row) {
                        return `<a href="#share-modal" data-bs-toggle="modal" data-id='${row.id}' class="btn payroll-btn-secondary btn-small mb-2">Share</a>
                        <a href="#view-modal" data-bs-toggle="modal" data-id='${row.id}' class="btn payroll-btn-secondary btn-small">View Payslip</a>`;
                    }
                },
                { data: 'created_at', visible: false, searchable: false }
            ],
        });
    }

    function populateMonthYearDropdowns() {
        let monthDropdown = $(".month");
        let yearDropdown = $(".year");

        let months = [
            "Jan", "Feb", "Mar", "Apr", "May", "Jun",
            "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
        ];

        let currentDate = new Date();
        let currentYear = currentDate.getFullYear();
        let currentMonth = currentDate.getMonth() + 1; // JS months are 0-based (Jan = 0)
        let startYear = currentYear - 5; // Show past 5 years

        // Populate Year Dropdown
        yearDropdown.empty();
        for (let year = startYear; year <= currentYear; year++) {
            let isSelected = year === currentYear ? "selected" : "";
            yearDropdown.append(`<option value="${year}" ${isSelected}>${year}</option>`);
        }

        // Populate Month Dropdown
        monthDropdown.empty();
        let selectedYear = yearDropdown.val(); // Get the currently selected year

        let maxMonth = selectedYear == currentYear ? currentMonth : 12;
        for (let i = 1; i <= maxMonth; i++) {
            let isSelected = i === currentMonth && selectedYear == currentYear ? "selected" : "";
            monthDropdown.append(`<option value="${i}" ${isSelected}>${months[i - 1]}</option>`);
        }
        window.wisdomDD.rebuild('#year');
        window.wisdomDD.rebuild('#year1');
        window.wisdomDD.rebuild('#month');
        window.wisdomDD.rebuild('#month1');

        // Update months when the year dropdown changes
        yearDropdown.change(function () {
            let selectedYear = $(this).val();
            monthDropdown.empty();
            let maxMonth = selectedYear == currentYear ? currentMonth : 12;

            for (let i = 1; i <= maxMonth; i++) {
                let isSelected = i === currentMonth && selectedYear == currentYear ? "selected" : "";
                monthDropdown.append(`<option value="${i}" ${isSelected}>${months[i - 1]}</option>`);
            }
            window.wisdomDD.rebuild('#month');
            window.wisdomDD.rebuild('#month1');
        });
    }

</script>
@include('resorts._dropdown_script')
@endsection