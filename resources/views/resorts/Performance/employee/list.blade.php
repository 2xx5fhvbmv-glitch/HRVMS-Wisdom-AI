@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
    <style>
        #performance-employees-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #performance-employees-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="performance-employees-hero">
                <div class="row g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Performance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-empListPeopleEmp">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                            <div class="input-group">
                                <input type="search" class="form-control" id="search-input" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="deptFilter">
                                <option value="">Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#deptFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Departments</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($departments as $dept)
                                            <div class="dd-item" role="option" data-value="{{ $dept->id }}"><span class="dd-nm">{{ $dept->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="positionFilter">
                                <option value="">Positions</option>
                                @foreach($positions as $pos)
                                    <option value="{{ $pos->id }}">{{ $pos->position_title }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#positionFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Positions</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($positions as $pos)
                                            <div class="dd-item" role="option" data-value="{{ $pos->id }}"><span class="dd-nm">{{ $pos->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="appraisalStatusFilter">
                                <option value="">Appraisal Status</option>
                                <option value="Done">Done</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Not Started">Not Started</option>
                            </select>
                            <div class="dd" data-target="#appraisalStatusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Appraisal Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Appraisal Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Appraisal Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Done"><span class="dd-nm">Done</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="In Progress"><span class="dd-nm">In Progress</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Not Started"><span class="dd-nm">Not Started</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-1 col-md-2 col-sm-3 col-6">
                            <button class="btn perf-btn-neutral btn-sm" id="clearFilter">Clear</button>
                        </div>
                        <div class="col-auto ms-auto">
                            <a href="javascript:void(0);" class="btn btn-grid active">
                                <img src="{{ URL::asset('resorts_assets/images/grid.svg') }}" alt="icon">
                            </a>
                            <a href="javascript:void(0);" class="btn btn-list">
                                <img src="{{ URL::asset('resorts_assets/images/list.svg') }}" alt="icon">
                            </a>
                        </div>
                    </div>
                </div>

                <div class="list-main d-none">
                    <div class="table-responsive p-3">
                        <table class="table table-collapseNew" id="employeeListTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Rating</th>
                                    <th>Appraisal Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="grid-main p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label>
                                Show
                                <select id="gridPageSize" class="form-select form-select-sm dd-native-select" style="width:auto; display:inline-block;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <div class="dd" data-target="#gridPageSize" style="display:inline-block; width:auto;">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">10</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Page Size">
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value="10"><span class="dd-nm">10</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="25"><span class="dd-nm">25</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="50"><span class="dd-nm">50</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                                entries
                            </label>
                        </div>
                    </div>
                    <div id="grid-employee-container" class="row g-md-4 g-3 mb-4"></div>
                    <div id="grid-pagination"></div>
                </div>
            </div>
        </div>
    </div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-scripts')
<script>
    let employeeGridUrl = "{{ route('Performance.employees.grid') }}";
    let employeeListUrl = "{{ route('Performance.employees.listData') }}";

    function isGridActive() { return $('.btn-grid').hasClass('active'); }

    $(document).ready(function () {
        loadGridView();

        $(".btn-grid").on('click', function () {
            $(this).addClass('active');
            $(".btn-list").removeClass('active');
            $(".grid-main").removeClass('d-none');
            $(".list-main").addClass('d-none');
            loadGridView();
        });

        $(".btn-list").on('click', function () {
            $(this).addClass('active');
            $(".btn-grid").removeClass('active');
            $(".list-main").removeClass('d-none');
            $(".grid-main").addClass('d-none');
            loadListView();
        });

        $(document).on('keyup', '#search-input', function () {
            isGridActive() ? loadGridView() : reloadList();
        });
        $('#deptFilter, #positionFilter, #appraisalStatusFilter').on('change', function () {
            isGridActive() ? loadGridView() : reloadList();
        });
        $('#gridPageSize').on('change', function () { loadGridView(1); });

        $(document).on('click', '#grid-pagination .pagination a', function (e) {
            e.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            loadGridView(page);
        });

        $('#clearFilter').on('click', function () {
            $('#search-input').val('');
            $('#deptFilter').val('').trigger('change');
            $('#positionFilter').val('').trigger('change');
            $('#appraisalStatusFilter').val('').trigger('change');
            isGridActive() ? loadGridView() : reloadList();
        });
    });

    function reloadList() {
        if ($.fn.dataTable.isDataTable('#employeeListTable')) {
            $('#employeeListTable').DataTable().ajax.reload();
        } else {
            loadListView();
        }
    }

    function loadListView() {
        if ($.fn.dataTable.isDataTable('#employeeListTable')) {
            $('#employeeListTable').DataTable().destroy();
        }
        $('#employeeListTable').DataTable({
            searching: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: employeeListUrl,
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.appraisal_status = $('#appraisalStatusFilter').val();
                    d.searchTerm = $('#search-input').val();
                }
            },
            columns: [
                { data: 'Emp_id' },
                { data: 'applicant', orderable: false },
                { data: 'position', orderable: false },
                { data: 'department', orderable: false },
                { data: 'rating', orderable: false },
                { data: 'appraisal_status', orderable: false },
                { data: 'action', orderable: false, searchable: false },
            ]
        });
    }

    function loadGridView(page = 1) {
        let pageSize = $('#gridPageSize').val();
        $.ajax({
            url: `${employeeGridUrl}?page=${page}&pageSize=${pageSize}`,
            data: {
                department_id: $('#deptFilter').val(),
                position_id: $('#positionFilter').val(),
                appraisal_status: $('#appraisalStatusFilter').val(),
                searchTerm: $('#search-input').val(),
            },
            success: function (res) {
                $('#grid-employee-container').html(res.html);
                $('#grid-pagination').html(res.pagination);
            }
        });
    }
</script>
@endsection
