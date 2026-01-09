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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto  ms-auto">
                        <a class="btn btn-theme" href="{{route('people.promotion.history')}}">View History</a>
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
                        <div class="col-xxl-2 col-lg-3 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" name="deptFilter" id="deptFilter" aria-label="Default select example">
                                <option value="">Department</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="positionFilter">
                                <option value="">By Position</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="promotion-table" class="table  table-peopleEmpPromo  w-100">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Old Department</th>
                                <th>New Department</th>
                                <th>Old Position</th>
                                <th>New Position</th>
                                <th>Old Salary</th>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $('.select2t-none').select2();
        getPromotionData();

        $('#searchInput, #deptFilter, #positionFilter').on('keyup change', function () {
            getPromotionData();
        });
    });

    function getPromotionData() {
        if ($.fn.dataTable.isDataTable('#promotion-table')) {
            $('#promotion-table').DataTable().destroy();
        }

        $('#promotion-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            order:[[11, 'desc']],
            ajax: {
                url: '{{ route("people.promotion.list") }}',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.searchTerm = $('#searchInput').val();
                    d.position_id = $('#positionFilter').val();
                }
            },
            columns: [
                { data: 'employee_id', name: 'employee_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'current_department', name: 'current_department' },
                { data: 'new_department', name: 'new_department' },
                { data: 'current_position', name: 'current_position' },
                { data: 'new_position', name: 'new_position' },
                { data: 'current_salary', name: 'current_salary' },
                { data: 'new_salary', name: 'new_salary' },
                { data: 'effective_date', name: 'effective_date' },
                { data : 'status', name: 'status'},
                { data: 'actions', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false }
            ]
        });
    }

</script>
@endsection