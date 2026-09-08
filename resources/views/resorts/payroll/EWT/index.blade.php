@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #ewt-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #ewt-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="ewt-hero">
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
                        <!-- <div class="col-auto ms-auto">
                            <a href="{{route('payroll.ewt.getdata.former-employees')}}" class="a-link">View Former Employees</a>
                        </div> -->
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
<style>
    .ewt-cell {
        position: relative;
        cursor: pointer;
    }
    .ewt-cell .ewt-tooltip {
        display: none;
        position: absolute;
        bottom: 100%;
        right: 0;
        background: #1a1a2e;
        color: #fff;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        min-width: 180px;
    }
    .ewt-cell .ewt-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        right: 20px;
        border: 6px solid transparent;
        border-top-color: #1a1a2e;
    }
    .ewt-cell:hover .ewt-tooltip {
        display: block;
    }
    /* Flip tooltip downward for first row */
    .ewt-tooltip-bottom .ewt-tooltip {
        bottom: auto !important;
        top: 100% !important;
    }
    .ewt-tooltip-bottom .ewt-tooltip::after {
        top: auto !important;
        bottom: 100% !important;
        border-top-color: transparent !important;
        border-bottom-color: #1a1a2e !important;
    }
    .ewt-tooltip .ewt-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 3px 0;
    }
    .ewt-tooltip .ewt-row + .ewt-row {
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .ewt-tooltip .ewt-label {
        color: #a0a0b8;
    }
    .ewt-tooltip .ewt-value {
        font-weight: 600;
    }

    /* Employee avatar — same 32px bump as the Payslip/Pension lists. */
    #ewtTaxTable .tableUser-block .img-circle { width: 32px; height: 32px; min-width: 32px; }

    /* Freeze ID/Employee/Department/Position/Annual Total while the Jan-Dec
       columns scroll horizontally underneath. DataTables' FixedColumns
       extension isn't part of this app's bundle. plain position:sticky
       works for the body cells (.dt-scroll-body is a real overflow:auto
       element) but NOT the header — DataTables 2 clones the header into
       its own <table> inside .dt-scroll-head with no id (only the body
       keeps #ewtTaxTable), and that clone's scrollLeft is synced
       programmatically rather than by the user actually scrolling it, so
       sticky's offset there just renders as a flat shift instead of
       freezing. The header is frozen with a JS scroll-position
       compensating transform instead (bound below, near ewtTable's init) —
       selectors below use .ewt-fz directly (not #ewtTaxTable-scoped) so
       they reach both the id-less header clone and the real body table. */
    .dataTable td.ewt-fz {
        position: sticky; z-index: 2; background: #fff;
    }
    .dataTable th.ewt-fz {
        /* The Jan-Dec headers render with no visible background of their
           own (thead.bg-light isn't actually painting — it's transparent
           in this table), so matching them means white, not --bs-light,
           despite still needing to stay opaque to occlude scrolled-under
           month headers. */
        position: relative; z-index: 3; background: #fff;
    }
    .ewt-fz-4 { box-shadow: 2px 0 4px rgba(0,0,0,.06); }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
        let ewtTable;
        let currentYear = $('#yearSelect').val();
        let allColumns = [
            {
                data: 'id',
                title: 'ID',
                width: '80px',
                className: 'text-center ewt-fz ewt-fz-0'
            },
            {
                data: 'name',
                title: 'Employee',
                width: '200px',
                className: 'ewt-fz ewt-fz-1',
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
                className: 'ewt-fz ewt-fz-2',
                render: function(data, type, row) {
                    // Department code badge removed — name only.
                    return data ? data : '<span class="text-muted">-</span>';
                }
            },
            {
                data: 'position',
                title: 'Position',
                width: '220px',
                className: 'ewt-fz ewt-fz-3 text-nowrap'
            },
            {
                data: 'total_ewt',
                title: 'Annual Total',
                render: function(data, type, row) {
                    return `
                        <div class="ewt-cell text-end">
                            <span class="d-block fw-600" style="font-size:14px;">${formatAmount(parseFloat(data), 'USD')}</span>
                            <div class="ewt-tooltip">
                                <div class="ewt-row"><span class="ewt-label">Total Earnings</span><span class="ewt-value">${formatAmount(parseFloat(row.total_earnings), 'USD')}</span></div>
                                <div class="ewt-row"><span class="ewt-label">Total Tax</span><span class="ewt-value">${formatAmount(parseFloat(data), 'USD')}</span></div>
                            </div>
                        </div>
                    `;
                },
                width: '120px',
                className: 'text-end ewt-fz ewt-fz-4'
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
                    url: "{{ route('payroll.ewt.getdata', ':year') }}".replace(':year', currentYear),
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
                                            <div class="ewt-cell text-end">
                                                <span class="d-block fw-600" style="font-size:14px;">${formatAmount(parseFloat(data.ewt), 'USD')}</span>
                                                <div class="ewt-tooltip">
                                                    <div class="ewt-row"><span class="ewt-label">Earnings</span><span class="ewt-value">${formatAmount(parseFloat(data.earnings), 'USD')}</span></div>
                                                    <div class="ewt-row"><span class="ewt-label">Tax Rate</span><span class="ewt-value">${data.tax_rate}%</span></div>
                                                    <div class="ewt-row"><span class="ewt-label">Slab</span><span class="ewt-value">${data.tax_slab}</span></div>
                                                </div>
                                            </div>
                                        `;
                                    },
                                    width: '100px',
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
                drawCallback: function() {
                    // Flip tooltip downward for first row to prevent clipping
                    var table = this.api();
                    table.rows().every(function(rowIdx) {
                        if (rowIdx === 0) {
                            $(this.node()).find('.ewt-cell').addClass('ewt-tooltip-bottom');
                        }
                    });
                    // Frozen-column offsets, computed from actual rendered widths
                    // rather than the declared column `width`s — DataTables' own
                    // layout (sort-icon padding, content like "Human Resources
                    // Coordinator" forcing extra width) doesn't always match what
                    // was asked for, so a hardcoded offset drifts and the frozen
                    // columns overlap the first scrolling month column.
                    setTimeout(function() {
                        // .dt-scroll-sizing clones exist purely for DataTables' own
                        // width measurement (zero-height, never visible) — the first
                        // real row inside .dt-scroll-body is the reliable source
                        // (each cell's right edge exactly meets the next one's left).
                        var firstRow = document.querySelector('.dt-scroll-body tbody tr') || document.querySelector('#ewtTaxTable tbody tr');
                        if (!firstRow) return;
                        var offsets = [], offset = 0;
                        for (var i = 0; i < 5; i++) {
                            var refCell = firstRow.querySelector('.ewt-fz-' + i);
                            offsets.push(offset);
                            if (refCell) offset += refCell.getBoundingClientRect().width;
                        }
                        // Body cells: .dt-scroll-body is genuinely overflow:auto, so
                        // plain position:sticky (CSS above) freezes them correctly.
                        for (var j = 0; j < 5; j++) {
                            document.querySelectorAll('.dt-scroll-body .ewt-fz-' + j).forEach(function(el) { el.style.left = offsets[j] + 'px'; });
                        }
                        // Header cells: .dt-scroll-head is a separate overflow:hidden
                        // clone whose scrollLeft DataTables syncs programmatically —
                        // sticky doesn't freeze against that, so cancel its shift with
                        // a matching transform instead, kept in sync on every scroll.
                        var scrollBody = document.querySelector('.dt-scroll-body');
                        function freezeHeader() {
                            var sl = scrollBody ? scrollBody.scrollLeft : 0;
                            document.querySelectorAll('.dt-scroll-headInner .ewt-fz').forEach(function(el) {
                                el.style.transform = 'translateX(' + sl + 'px)';
                            });
                        }
                        freezeHeader();
                        if (scrollBody && !scrollBody.dataset.ewtFreezeBound) {
                            scrollBody.dataset.ewtFreezeBound = '1';
                            scrollBody.addEventListener('scroll', freezeHeader);
                        }
                    }, 0);
                }
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
            initializeDataTable(); 
        });
    });
</script>
@include('resorts._dropdown_script')
@endsection