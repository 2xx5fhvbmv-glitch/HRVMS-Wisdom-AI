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
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select select2" name="department" data-placeholder="Management">
                                    <option></option>
                                    @foreach($departments as $department)
                                        <option value="{{$department->id}}"  @if(request()->get('department') == $department->id) selected @endif>{{$department->name}} ({{$department->code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select select2" name="position" data-placeholder="Position">
                                    <option></option>
                                    @foreach($positions as $position)
                                        <option value="{{$position->id}}" @if(request()->get('position') == $position->id) selected @endif>{{$position->position_title}} -({{$position->code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select select2" name="status" data-placeholder="Status">
                                    <option></option>
                                    <option value="Pending"  @if(request()->get('status') =='Pending') selected @endif>Pending</option>
                                    <option value="Approved" @if(request()->get('status') =='In Progress') selected @endif>In Progress</option>
                                    <option value="Schedule" @if(request()->get('status') =='Schedule') selected @endif>Schedule</option>
                                    <option value="Complete" @if(request()->get('status') =='Complete') selected @endif>Complete</option>
                                    <option value="Rejected" @if(request()->get('status') =='Rejected') selected @endif>Rejected</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <input type="text" class="form-control datepicker" id="from_date1" placeholder="DD/MM/YYYY" name="date" data-parsley-required="true"  data-parsley-errors-container="#from-date-error1">
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
                                <th>Total Advance/ Loan Amount</th>
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
    $('.datepicker').datepicker({
          format: 'dd/mm/yyyy',
          autoclose: true
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
@endsection

