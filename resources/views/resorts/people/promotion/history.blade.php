@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #promotion-history-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #promotion-history-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="promotion-history-hero">
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
                            <select class="form-select dd-native-select" name="select_employee" id="select_employee"
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
                            @php $selectedEmp = $employees ? $employees->firstWhere('id', $decodedId) : null; @endphp
                            <div class="dd" data-target="#select_employee">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ $selectedEmp ? $selectedEmp->Emp_id.' - '.$selectedEmp->resortAdmin->full_name : 'Select Employee' }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Employee">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item{{ $selectedEmp ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($employees)
                                            @foreach($employees as $employee)
                                            <div class="dd-item{{ ($decodedId == $employee->id) ? ' active' : '' }}" role="option" data-value="{{ $employee->id }}"><span class="dd-nm">{{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
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
                                <th>Old Benefit Grid</th>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        flatpickr('.datepicker', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
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
                { data: 'new_benifit_grid', name: 'new_benifit_grid' },
                { data: 'created_at', visible: false, searchable: false }
            ]
        });
    }
</script>
@include('resorts._dropdown_script')
@endsection