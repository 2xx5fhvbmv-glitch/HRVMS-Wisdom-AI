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
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                   
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="departmentFilter" class="form-select select2t-none">
                                <option value="">All Departments</option>
                                @if($departments)
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="positionFilter" class="form-select select2t-none">
                                <option value="">All Positions</option>
                                <!-- Example: populate dynamically or statically -->
                                @if($positions)
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}">{{ $position->position_title }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <!-- data-Table -->
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="mandatory-learning-table" class="table table-LearningProgram w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Learning Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Notify Before Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        getMandatoryLearningPrograms();

        $("#searchInput, #departmentFilter, #positionFilter").on("input change", debounce(function () {
            getMandatoryLearningPrograms();
        }, 300));
    });

    function getMandatoryLearningPrograms() {
        $('#mandatory-learning-table tbody').empty();
        if ($.fn.DataTable.isDataTable('#mandatory-learning-table')) {
            $('#mandatory-learning-table').DataTable().destroy();
        }

        var programTable = $('#mandatory-learning-table').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            order:[[4, 'desc']],
            ajax: {
                url: '{{ route("learning.mandatory-programs.list") }}',
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();  // Send search input
                    d.department = $('#departmentFilter').val();  // Send department filter
                    d.position = $('#positionFilter').val();  // Send position filter
                }
            },
            columns: [
                { data: 'program', name: 'Mandatory Learning', className: 'text-nowrap' },
                { data: 'department', name: 'Department', className: 'text-nowrap' },
                { data: 'position', name: 'Position', className: 'text-nowrap' },
                { data: 'notify_before_days', name: 'Notify Before Days', className: 'text-nowrap' }, // Fixed Notify Before Days
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
    function debounce(func, delay) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, arguments), delay);
        };
    }
</script>
@endsection