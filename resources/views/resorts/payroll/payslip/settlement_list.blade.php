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
                        <select id="departmentFilter" class="form-select select2t-none">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select  id="positionFilter" class="form-select select2t-none">
                            <option value="">All Positions</option>
                            <!-- Example: populate dynamically or statically -->
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                            @endforeach
                        </select>
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
@endsection

@section('import-scripts')
<script>
    $(document).ready(function()
    {
        $('.select2t-none').select2();
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
@endsection