@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #settlement-list-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #settlement-list-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="settlement-list-hero">
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
                        <th>Emp ID</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Last Working Date</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>
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
        finalsettlementList();

        $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function () {
            finalsettlementList();
        });
        populateMonthYearDropdowns();

        

    });

    function finalsettlementList() {
        if ($.fn.DataTable.isDataTable('#employee-table')) {
            $('#employee-table').DataTable().destroy();
        }

        $('#employee-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            order:[[8, 'desc']],
            ajax: {
                url: "{{ route('final.settlement.getdata') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.department = $('#departmentFilter').val();
                    d.position = $('#positionFilter').val();
                }
            },
            columns: [
                {
                    data: 'employee',
                    render: function (data) {
                        return data.emp_id ?? 'N/A';
                    }
                },
                {
                    data: 'employee',
                    render: function (data) {
                        return `<div class="tableUser-block">
                                    <div class="img-circle">
                                        <img src="${data.profile_picture}" alt="profile">
                                    </div>
                                    <span>${data.name}</span>
                                </div>`;
                    }
                },
                {
                    data: 'position',
                    render: function (data) {
                        return data ?? 'N/A';
                    }
                },
                {
                    data: 'department',
                    render: function (data) {
                        return data ?? 'N/A';
                    }
                },
                {
                    data: 'last_working_date',
                    name: 'last_working_date'
                },
                {
                    data: 'net_pay',
                    name: 'net_pay'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action'
                },
                {data:'created_at',visible:false,searchable:false},
            ]
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

        // Update months when the year dropdown changes
        yearDropdown.change(function () {
            let selectedYear = $(this).val();
            monthDropdown.empty();
            let maxMonth = selectedYear == currentYear ? currentMonth : 12;

            for (let i = 1; i <= maxMonth; i++) {
                let isSelected = i === currentMonth && selectedYear == currentYear ? "selected" : "";
                monthDropdown.append(`<option value="${i}" ${isSelected}>${months[i - 1]}</option>`);
            }
        });
    }

</script>
@include('resorts._dropdown_script')
@endsection