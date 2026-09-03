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
                            <select id="yearSelect" class="form-select dd-native-select">
                                @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                                    <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <div class="dd" data-target="#yearSelect">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ date('Y') }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Year">
                                    <div class="dd-scroll">
                                        @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                                        <div class="dd-item{{ $i == date('Y') ? ' active' : '' }}" role="option" data-value="{{ $i }}"><span class="dd-nm">{{ $i }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endfor
                                    </div>
                                </div>
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
                        <div class="col-auto ms-auto">
                            <a href="{{route('payroll.ewt.index')}}" class="a-link">View Active Employees</a>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
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
                            <span class="d-block">${formatAmount(parseFloat(data), 'USD')}</span>
                            <small class="text-muted">Tax: ${formatAmount(parseFloat(row.total_ewt), 'USD')}</small>
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
                                                <span class="d-block">${formatAmount(parseFloat(data.earnings), 'USD')}</span>
                                                <span class="d-block text-danger">${formatAmount(parseFloat(data.ewt), 'USD')}</span>
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
@include('resorts._dropdown_script')
@endsection