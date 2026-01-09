@extends('resorts.layouts.app')
@section('page_tab_title' ,"Promotion Dashboard")

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
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                    <div class="col-auto  ms-auto">
                        <a class="btn btn-theme" href="{{route('people.promotion.initiate')}}">Initiate Promotion</a>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-lg-4 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Total Number Of Employees</p>
                                <strong>{{$total_employees}}</strong>
                            </div>
                            <a href="{{route('people.employees')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Pending Promotions</p>
                                <strong>{{$pending_promotion ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.promotion.list')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">                           
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card dashboard-boxcard timeAttend-boxcard">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-0  fw-500">Approved Promotions</p>
                                <strong>{{$approved_promotion ?? 0}}</strong>
                            </div>
                            <a href="{{route('people.promotion.list')}}">
                                <img src="{{ URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Promotion Statistics</h3>
                        </div>
                        <canvas id="myLineChart" width="802" height="293" class="mb-3"></canvas>
                        <div class="row gx-md-4 g-2 justify-content-center">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>This Year
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeLightBlue"></span>Last Year
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Basic Salary</h3>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group">
                                        <select class="form-select" id="basicSalaryFilter">
                                            <option value="month" selected>Month-Wise</option>
                                            <option value="quarter">Quarter-Wise</option>
                                            <option value="year">Year-Wise</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <canvas id="barChart" width="802" height="293" class="mb-3"></canvas>
                        <div class="row gx-md-4 g-2 justify-content-center">
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-theme"></span>Current Basic
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="doughnut-label">
                                    <span class="bg-themeLightBlue"></span>New Basic
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class=" card">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-2">
                                <div class="col">
                                    <h3 class="text-nowrap">List of Promotions</h3>
                                </div>
                                <div class="col-xl-2 col-auto">
                                    <div class="form-group">
                                        <select class="form-select form-select-large select2t-none" id="empFilter"
                                            aria-label="Default select example">
                                            <option value="">Select Employee</option>
                                            @if($employees)
                                                @foreach($employees as $employee)
                                                    <option value="{{ $employee->id }}">
                                                        {{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-auto">
                                    <div class="form-group">
                                    <select class="form-select select2t-none" name="deptFilter" id="deptFilter" aria-label="Default select example">
                                        <option value="">Department</option>
                                        @if($departments)
                                            @foreach($departments as $dept)
                                                <option value="{{$dept->id}}">{{$dept->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-auto">
                                    <div class="form-group">
                                        <select class="form-select form-select-large select2t-none"
                                            aria-label="Default select example" id="statusFilter">
                                            <option value="">Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Approved">Approved</option>
                                            <option value="Rejected">Rejected</option>
                                            <option value="On Hold">On Hold</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-sm-3  col-auto"> 
                                    <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('people.promotion.list')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-lableNew  table-listPromotions w-100" id="promotionTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee Name</th>
                                        <th>Current Position</th>
                                        <th>New Position</th>
                                        <th>Current Salary</th>
                                        <th>New Salary</th>
                                        <th>Effective Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    let barChart;

    $(document).ready(function(){
        $('.select2t-none').select2();
        $(".datepicker").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        getPromotionTable();
        window.positions = @json($positions);

        $('#empFilter, #deptFilter, #statusFilter, #dateFilter').on('change', function () {
            getPromotionTable();
        });

        // Initial load
        fetchData('month');

        // Filter listener
        document.getElementById('basicSalaryFilter').addEventListener('change', function () {
            fetchData(this.value);
        });

    });

    function getPromotionTable(){
        if ($.fn.dataTable.isDataTable('#promotionTable')) {
            $('#promotionTable').DataTable().destroy();
        }
        let table = $('#promotionTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            order:[[9, 'desc']],
            ajax: {
                url: "{{ route('people.promotion.filter') }}",
                data: function (d) {
                    d.employee = $('#empFilter').val();
                    d.department = $('#deptFilter').val();
                    d.status = $('#statusFilter').val();

                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
                    
                }
            },
            columns: [
                { data: 'promotion_id', name: 'promotion_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'current_position', name: 'current_position' },
                { data: 'new_position', name: 'new_position' },
                { data: 'current_salary', name: 'current_salary' },
                { data: 'new_salary', name: 'new_salary' },
                { data: 'effective_date', name: 'effective_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });

    }

    function renderBarChart(labels, currentBasic, newBasic) {
        const ctx = document.getElementById('barChart').getContext('2d');
        if (barChart) barChart.destroy(); // Destroy previous instance if exists

        barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current Basic',
                        data: currentBasic,
                        backgroundColor: '#014653',
                        borderColor: '#014653',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    },
                    {
                        label: 'New Basic',
                        data: newBasic,
                        backgroundColor: '#2EACB3',
                        borderColor: '#2EACB3',
                        borderWidth: 1,
                        borderRadius: 3,
                        barThickness: 14
                    },
                ]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (tooltipItem) {
                                const value = tooltipItem.raw.toLocaleString();
                                return ` $${value}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: true }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { display: false },
                        border: { display: true },
                        ticks: { stepSize: 5 }
                    }
                }
            }
        });
    }

    function fetchData(filter) {
        $.ajax({
            url: "{{ route('promotion.basic-salary.data') }}",
            method: 'GET',
            data: {
                filter: 'month',  // or 'quarter' / 'year'
                year: new Date().getFullYear()  // optional if needed
            },
            success: function(response) {
                console.log(response); // Use response.labels, response.currentBasic, etc.
                renderBarChart(response.labels, response.currentBasic, response.newBasic);
            },
            error: function(xhr) {
                console.error("Error fetching basic salary data:", xhr.responseText);
            }
        });

        

    }

    $(document).on("click", "#promotionTable .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var promotionId = $(this).data('id');

        // Fetch existing values
        var $newPosition = $row.find("td:nth-child(4)");
        var currentPosition = $newPosition.text().trim();

        var positionSelectId = 'edit-new-position-' + promotionId;

        // Generate position options
        var positionOptions = '<option value="">Select Position</option>';
        window.positions.forEach(function(position) {
            var selected = (currentPosition === position.position_title.trim()) ? 'selected' : '';
            positionOptions += `<option value="${position.id}" ${selected}>${position.position_title}</option>`;
        });

        // Inject select with dynamic ID
        $newPosition.html(`
            <select id="${positionSelectId}" name="position_id" class="form-select select2-modal">
                ${positionOptions}
            </select>
        `);

        // Initialize Select2
        $('#' + positionSelectId).select2({
            placeholder: 'Select Position',
            dropdownParent: $('#promotionTable'),
            width: '100%'
        });

        // Salary and effective date handling
        var $newSalary = $row.find("td:nth-child(6)");
        var $effectiveDate = $row.find("td:nth-child(7)");

        var currentNewSalary = $newSalary.text().trim();
        var currentEffectiveDate = $effectiveDate.text().trim();

        var effectiveDateId = 'edit-effective-date-' + promotionId;

        $newSalary.html(`<input type="number" class="form-control" value="${currentNewSalary.replace(/\D/g, '')}" />`);
        $effectiveDate.html(`<input type="text" id="${effectiveDateId}" class="form-control" />`);

        // Replace action buttons
        var $actionCell = $row.find("td:last-child");
        $actionCell.html(`
            <button class="btn btn-sm btn-success update-row-btn" data-promotion-id="${promotionId}">Update</button>
            <button class="btn btn-sm btn-secondary cancel-row-btn" data-promotion-id="${promotionId}">Cancel</button>
        `);

        // Store original values for cancel functionality
        $row.data('original-effective-date', currentEffectiveDate);
        

        // Destroy and re-initialize datepicker
        $('#' + effectiveDateId)
            .datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto',
                container: 'body'
            })
            .on('changeDate', function () {
                $(this).val($(this).datepicker('getFormattedDate'));
            });

        // Now set value
        if (currentEffectiveDate) {
            var parts = currentEffectiveDate.split('/');
            if (parts.length === 3) {
                var dateObj = new Date(parts[2], parts[1] - 1, parts[0]);
                $('#' + effectiveDateId).datepicker('update', dateObj);
            }
        }


        // Set initial date if exists
        try {
            if (currentEffectiveDate) {
                var parts = currentEffectiveDate.split('/');
                if (parts.length === 3) {
                    var dateObj = new Date(parts[2], parts[1] - 1, parts[0]);
                    $('#' + effectiveDateId).datepicker('update', dateObj);
                }
            }
        } catch (e) {
            console.error("Error setting dates:", e);
        }
    });

    $(document).on("click", ".update-row-btn", function () {
        const $btn = $(this);
        const promotionId = $btn.data("promotion-id");
        const $row = $btn.closest("tr");

        const positionSelectId = `edit-new-position-${promotionId}`;
        const effectiveDateId = `edit-effective-date-${promotionId}`;

        const newPositionId = $(`#${positionSelectId}`).val();
        const newSalary = $row.find("td:nth-child(6) input").val().trim();
        let effectiveDate = $(`#${effectiveDateId}`).val();

        // Fallback to original date if no new date selected
        if (!effectiveDate) {
            effectiveDate = $row.data('original-effective-date'); // you stored this in edit-row-btn click
        }

        $.ajax({
            url: "{{route('promotion.inlineUpdate')}}", // adjust route as needed
            type: "POST",
            data: {
                _token: $("meta[name='csrf-token']").attr("content"),
                id: promotionId,
                position_id: newPositionId,
                new_salary: newSalary,
                effective_date: effectiveDate
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    getPromotionTable();               
                } else {
                    toastr.error(response.message || 'Update Failed', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function (xhr) {
                let res = xhr.responseJSON;
                let message = res?.message || 'Something went wrong. Please try again.';
                toastr.error(message, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
</script>
<script>
    const labels = @json($labels);
    const thisYearCounts = @json(array_values($thisYearCounts));
    const lastYearCounts = @json(array_values($lastYearCounts));

    const ctx = document.getElementById('myLineChart').getContext('2d');
    const myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '{{ $currentYear }}',
                    data: thisYearCounts,
                    borderColor: '#014653',
                    backgroundColor: '#014653',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: '{{ $lastYear }}',
                    data: lastYearCounts,
                    borderColor: '#2EACB3',
                    backgroundColor: '#2EACB3',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            layout: {
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 0,
                    right: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });
</script>
@endsection