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
                            <select id="yearSelect" class="form-select select2t-none">
                                @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                                    <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
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
                        <div class="col-auto ms-auto">
                            <a href="#" class="a-link">View Former Employees</a>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="ewtTaxTable" class="table w-100">
                    <thead class="bg-light">
                        <!-- Columns will be added dynamically -->
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
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
    $(document).ready(function() {
        $('.select2t-none').select2();
        let ewtTable;
        let currentYear = $('#yearSelect').val();
        console.log('Current Year:', currentYear);
        let allColumns = [
            {
                data: 'id',
                title: 'ID',
                width: '80px',
                className: 'text-center'
            },
            {
                data: 'name',
                title: 'Employee',
                render: function(data, type, row) {
                    let resignedHtml = '';
                    if (row.resigned) {
                        // Only show for approved resignations (adjust status check as needed)
                        if (row.resigned.status === 'Approved') {
                            resignedHtml = `
                                <span class="ttb-hover ttb-resigned ms-2">
                                    <i class="fa-regular fa-circle-exclamation text-themeDanger"></i>
                                    <span class="ttb-main">
                                        <span class="ttb-inner">
                                            <h6><span>Resigned</span> - ${row.resigned.formatted_date}</h6>
                                            <p>Last working day: ${row.resigned.last_working_day ? new Date(row.resigned.last_working_day).toLocaleDateString() : 'N/A'}</p>
                                            <p>Will be removed from the pension contributions ${row.resigned.last_working_day ? new Date(row.resigned.last_working_day).toLocaleDateString() : 'N/A'}</p>
                                        </span>
                                    </span>
                                </span>
                            `;
                        }
                    }
                    
                    return `
                        <div class="tableUser-block">
                            <div class="img-circle">
                                <img src="${row.profile_picture || '/images/default-avatar.png'}" 
                                    alt="${data}" onerror="this.src='/images/default-avatar.png'">
                            </div>
                            <span>${data}</span>
                            ${resignedHtml}
                        </div>
                    `;
                }
            },
            {
                data: 'department',
                title: 'Department',
                width: '150px',
                render: function(data, type, row) {
                    // Always show both name and code when available
                    if (data && row.departmentCode) {
                        return `
                            <div>
                                <div>${data}</div>
                                <span class="badge badge-themeLight mt-1">${row.departmentCode}</span>
                            </div>
                        `;
                    }
                    
                    // Show just department name if available
                    if (data) {
                        return data;
                    }
                    
                    // Show just department code if available
                    if (row.departmentCode) {
                        return `<span class="badge badge-themeLight">${row.departmentCode}</span>`;
                    }
                    
                    // Fallback
                    return '<span class="text-muted">-</span>';
                }
            },
            {
                data: 'position',
                title: 'Position',
                width: '150px'
            },
            {
                data: 'total_earnings',
                title: 'Annual Total',
                render: function(data, type, row) {
                    return `
                        <div class="text-end">
                            <span class="d-block">$${parseFloat(data).toFixed(2)}</span>
                            <small class="text-muted">Tax: $${parseFloat(row.total_ewt).toFixed(2)}</small>
                        </div>
                    `;
                },
                width: '120px',
                className: 'text-end'
            }
        ];

        function initializeDataTable(monthColumns = []) {
            // Destroy existing table if it exists
            if ($.fn.DataTable.isDataTable('#ewtTaxTable')) {
                ewtTable.destroy();
                $('#ewtTaxTable').empty();
            }

            // Combine static and dynamic columns
            let columns = [...allColumns, ...monthColumns];
            let ajaxUrl = `{{ route('payroll.ewt.getdata.former-employees', ['year' => '__YEAR__']) }}`.replace('__YEAR__', currentYear);


            ewtTable = $('#ewtTaxTable').DataTable({
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
                    url: ajaxUrl,
                     data: function(d) {
                        return {
                            search: $('#searchInput').val(),
                            department: $('#departmentFilter').val(),
                            position: $('#positionFilter').val(),
                            draw: d.draw,
                            start: d.start,
                            length: d.length
                        };
                    },
                    dataSrc: function(json) {
                        if (!json.success) {
                            console.error('Server error:', json.message);
                            return [];
                        }
                
                        // Process month columns if this is the initial load
                        if (monthColumns.length === 0 && json?.months) {
                            setTimeout(() => {
                                let newMonthColumns = json.months.map(month => ({
                                    data: 'monthly_data.' + month,
                                    title: month,
                                    render: function(data) {
                                        if (!data || data.earnings === 0) return '<div class="text-center">-</div>';
                                        
                                        return `
                                            <div class="text-end">
                                                <span class="d-block">$${parseFloat(data.earnings).toFixed(2)}</span>
                                                <span class="d-block text-danger">$${parseFloat(data.ewt).toFixed(2)}</span>
                                                <small class="text-muted">${data.tax_rate}% (${data.tax_slab})</small>
                                            </div>
                                        `;
                                    },
                                    width: '150px',
                                    className: 'text-end'
                                }));
                                
                                // Reinitialize with all columns
                                initializeDataTable(newMonthColumns);
                            }, 0);
                        }
                        return json.data;
                    }
                },
                columns: columns,
            });
        }

        // Initial load
        initializeDataTable();

        // Filter handlers
        $('#yearSelect').change(function() {
            currentYear = $(this).val();
            initializeDataTable(); // Reinitialize completely for new year
        });

        $('#searchInput, #departmentFilter, #positionFilter').on('keyup change', function() {
            if (ewtTable) {
                ewtTable.ajax.reload();
            }
        });
    });
</script>
@endsection