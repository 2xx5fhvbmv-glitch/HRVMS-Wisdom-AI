@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #advance-salary-tracker-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #advance-salary-tracker-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="advance-salary-tracker-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <form id="filterForm" method="GET" action="{{route('people.info-update.list')}}">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" name="search" class="form-control "
                                        placeholder="Search Employee by name" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            @php
                                $selectedDept = $departments->firstWhere('id', request()->get('department'));
                                $selectedPos = $positions->firstWhere('id', request()->get('position'));
                                // Mirrors the original option-by-option `selected` checks exactly
                                // (including the pre-existing quirk that the "Approved" option is
                                // marked selected when the query string literally equals
                                // "In Progress", not "Approved") rather than a value->label lookup.
                                $statusReq = request()->get('status');
                                $selectedStatusLbl = match(true) {
                                    $statusReq === 'Pending' => 'Pending',
                                    $statusReq === 'In Progress' => 'In Progress',
                                    $statusReq === 'Schedule' => 'Schedule',
                                    $statusReq === 'Complete' => 'Complete',
                                    $statusReq === 'Rejected' => 'Rejected',
                                    default => null,
                                };
                            @endphp
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select dd-native-select" id="repay-tracker-department" name="department" data-placeholder="Management">
                                    <option></option>
                                    @foreach($departments as $department)
                                        <option value="{{$department->id}}"  @if(request()->get('department') == $department->id) selected @endif>{{$department->name}} ({{$department->code}})</option>
                                    @endforeach
                                </select>
                                <div class="dd" data-target="#repay-tracker-department">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedDept ? $selectedDept->name.' ('.$selectedDept->code.')' : 'Management' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Department">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedDept ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Management</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @foreach($departments as $department)
                                                <div class="dd-item{{ request()->get('department') == $department->id ? ' active' : '' }}" role="option" data-value="{{$department->id}}"><span class="dd-nm">{{$department->name}} ({{$department->code}})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select dd-native-select" id="repay-tracker-position" name="position" data-placeholder="Position">
                                    <option></option>
                                    @foreach($positions as $position)
                                        <option value="{{$position->id}}" @if(request()->get('position') == $position->id) selected @endif>{{$position->position_title}} -({{$position->code}})</option>
                                    @endforeach
                                </select>
                                <div class="dd" data-target="#repay-tracker-position">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedPos ? $selectedPos->position_title.' -('.$selectedPos->code.')' : 'Position' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Position">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedPos ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @foreach($positions as $position)
                                                <div class="dd-item{{ request()->get('position') == $position->id ? ' active' : '' }}" role="option" data-value="{{$position->id}}"><span class="dd-nm">{{$position->position_title}} -({{$position->code}})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select dd-native-select" id="repay-tracker-status" name="status" data-placeholder="Status">
                                    <option></option>
                                    <option value="Pending"  @if(request()->get('status') =='Pending') selected @endif>Pending</option>
                                    <option value="Approved" @if(request()->get('status') =='In Progress') selected @endif>In Progress</option>
                                    <option value="Schedule" @if(request()->get('status') =='Schedule') selected @endif>Schedule</option>
                                    <option value="Complete" @if(request()->get('status') =='Complete') selected @endif>Complete</option>
                                    <option value="Rejected" @if(request()->get('status') =='Rejected') selected @endif>Rejected</option>
                                </select>
                                <div class="dd" data-target="#repay-tracker-status">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedStatusLbl ?? 'Status' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Status">
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedStatusLbl ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ request()->get('status') == 'Pending' ? ' active' : '' }}" role="option" data-value="Pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ request()->get('status') == 'In Progress' ? ' active' : '' }}" role="option" data-value="Approved"><span class="dd-nm">In Progress</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ request()->get('status') == 'Schedule' ? ' active' : '' }}" role="option" data-value="Schedule"><span class="dd-nm">Schedule</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ request()->get('status') == 'Complete' ? ' active' : '' }}" role="option" data-value="Complete"><span class="dd-nm">Complete</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item{{ request()->get('status') == 'Rejected' ? ' active' : '' }}" role="option" data-value="Rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <input type="text" class="form-control" id="from_date1" placeholder="DD/MM/YYYY" name="date" data-parsley-required="true"  data-parsley-errors-container="#from-date-error1">
                            </div>
                        </div>
                    </form>
            
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-lable table-repayTrackPeopleEmp mb-1" id="repayTrackPeopleEmpTable">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Total Advance/Loan Amount</th>
                                <th>Latest Deduction Month</th>
                                <th>Repayment Status</th>
                                <th>Action</th>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>  

function initializeTable() {
        if ($.fn.dataTable.isDataTable('#repayTrackPeopleEmpTable')) {
            $('#repayTrackPeopleEmpTable').DataTable().destroy();
        }

        $('#repayTrackPeopleEmpTable').DataTable({
            "searching": true,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('people.advance-salary-repayment-tracker.list') }}",
                type: 'GET',
                data: function (d) {
                    d.search = $('input[name=search]').val();
                    d.department = $('select[name=department]').val();
                    d.position = $('select[name=position]').val();
                }
            },
            columns: [
                {data: 'Emp_id', name: 'Emp_id'},
                {data: 'employee_name', name: 'employee_name'},
                {data: 'position', name: 'position'},
                {data: 'department', name: 'department'},
                {data: 'request_amount', name: 'request_amount'},
                {data: 'last_payment', name: 'last_payment'},
                {data: 'recovery_status', name: 'recovery_status'},
                {data: 'action', name: 'action'},
                {data:'created_at',visible:false,searchable:false},
            ],
            order: [[8, 'desc']],
        });
}

$(document).ready(function() {
    flatpickr('#from_date1', {
          dateFormat: 'd/m/Y',
          allowInput: true,
          appendTo: document.body
      });
    initializeTable();


    // Trigger DataTable reload on filter change or search keyup
    $('input[name=search]').on('keyup', function() {
        $('#repayTrackPeopleEmpTable').DataTable().ajax.reload();
    });
    $('select[name=department], select[name=position], select[name=status]').on('change', function() {
        $('#repayTrackPeopleEmpTable').DataTable().ajax.reload();
    });
    $('#from_date1').on('change', function() {
        $('#repayTrackPeopleEmpTable').DataTable().ajax.reload();
    });
});

</script>
@include('resorts._dropdown_script')
@endsection

