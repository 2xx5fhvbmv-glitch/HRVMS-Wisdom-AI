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
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Initiate Promotion</a></div> -->
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-lg-4 g-3">
                        <div class="col-md-4">
                            <label for="select_employee" class="form-label">SELECT EMPLOYEE</label>
                            <select class="form-select select2t-none" name="select_employee" id="select_employee"
                                aria-label="Default select example">
                                <option value="">Select Employee </option>
                                @if($employees)
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ $decodedId == $employee->id ? 'selected' : '' }}>
                                            {{$employee->Emp_id}} - {{ $employee->resortAdmin->full_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>    
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="promotion-history" class="table  table-promotionHistory  w-100">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Effective Date</th>
                                <th>Old Position</th>
                                <th>Current Position</th>
                                <th>Old Salary</th>
                                <th>Current Salary</th>
                                <th>Old JD</th>
                                <th>New JD</th>
                                <th>Old Benifit Grid</th>
                                <th>New Benefit Grid</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="row g-2">
                        <div class="col-auto ms-auto">
                            <a href="{{ route('promotion.history.export.pdf') }}" class="btn btn-themeSkyblue btn-sm">Export to PDF</a>
                            <a href="{{ route('promotion.history.export.excel') }}" class="btn btn-themeBlue btn-sm">Export to Excel</a>
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
    $(document).ready(function () {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });
        $('.select2t-none').select2();
        getPromotionHistory();

        $('#select_employee').on('keyup change', function () {
            getPromotionHistory();
        });
    });

    function getPromotionHistory() {
        if ($.fn.dataTable.isDataTable('#promotion-history')) {
            $('#promotion-history').DataTable().destroy();
        }

        $('#promotion-history').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            order: [[11, 'desc']],
            pageLength: 10,
            ajax: {
                url: '{{ route("people.promotion.history") }}',
                data: function (d) {
                    d.employee_id = $('#select_employee').val();
                    // d.searchTerm = $('#searchInput').val();
                    // d.position_id = $('#positionFilter').val();
                }
            },
            columns: [
                { data: 'employee_id', name: 'employee_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'effective_date', name: 'effective_date' },
                { data: 'old_position', name: 'old_position' },
                { data: 'new_position', name: 'new_position' },
                { data: 'old_salary', name: 'old_salary' },
                { data: 'new_salary', name: 'new_salary' },
                { data: 'old_jd', name: 'old_jd' },
                { data: 'new_jd', name: 'new_jd' },
                { data: 'old_benifit_grid', name: 'old_benifit_grid' },
                { data: 'new_benifit_grid', name: 'new_benifit_grid' }
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
</script>
@endsection